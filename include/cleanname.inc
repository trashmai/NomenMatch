<?php

error_reporting(E_WARNING);

define("UPPER","A-ZÀÂÅÅÃÄÁÆČÇÉÈÊËÍÌÎÏÑÓÒÔØÕÖÚÙÛÜßĶŘŠŞŽŒ");
define("LOWER","a-záááàâåãäăæčćçéèêëĕíìîïǐĭñńóòôøõöŏúùûüůśšşřğžźýýÿœœ");

/** 
 * 去除掉所有不是拉丁文的部份(包含marker)
 * @param unknown_type $string
 */
function canonical_form($string, $subgen_skip=false, $sp_keep=false){

#	error_reporting(E_WARNING);

        global $has_subgen,$cultivar;
        $has_subgen = false;
        
        $string = preg_replace('/([a-z])([A-Z])/', '$1 $2', $string);

        if(preg_match("/^X (.*)$/",$string,$arr)) {//取代掉雜交符號 X 放置在前頭的情形
          $string = $arr[1];
        }

	// 20150628 Mai edit: replace utf8 c2a0 (no-break space) with space
        $string = str_replace("\xC2\xA0", " ", $string);

        $string = str_replace(" tipo veneto","",$string);
        $string = str_replace("×"," ",$string);

	// 20150628 Mai edit: no slash allowed
        $string = array_shift(explode("/", $string));
        $words = preg_split("/[[:space:]]+/",trim($string));//以空白字元切割學名串

        $num = count($words);
        /**
         * 學名首字($words[0])字首須大寫,
         * 針對特殊狀況用以下條件過濾出乾淨的首字($words[0]),若無此情形則跳過
         * 1.$words[0]的字首有特殊符號 ?,",[,(
         * 2.(*)匹配$words[0]不可包含特殊符號 ?,",[],()
         * 3.$words[0]的字尾有特殊符號",],) 
         */
        if(preg_match("/^\??\"?\[?\(?([^\"\[\]\(\)]*)\)?\]?\"?$/",$words[0],$arr)){
          $words[0] = $arr[1];//去除特殊符,$arr[1]即為匹配出的乾淨字元
        }
        
        /**
         * 針對括號與前字相連狀況
         * Brassica(brassica)
         * 上述情形會match 出 $arr[1] = "Brassica(brassica"
         * 這裡要抽取出並放回原來陣列
         */
        if(preg_match("/^\??\"?\[?\(?([^\"\[\]\)]*)\)$/",$words[0],$arr)){
           $temp = explode('(',$arr[1]); 
           $words[0] = $temp[0];
           if($num > 1){//抽取出的括號字串插入$words[1]
             for($i=$num-1;$i > 0;$i--){
               $words[$i+1] = $words[$i]; 
             }
             $words[1] = '('.$temp[1].')';
             $num = count($words);//新插入,$num重算
           }else{
             $words[1] = '('.$temp[1].')';
           }
        }else if(preg_match("/^\(([^\"\[\]]*)\]?\"?$/",$words[1],$arr)){
         /**
         * 針對亞屬名與種小名連接情況
         * Brassica (brassica)campestris       
         */
           $temp = explode(')',$arr[1]);
           if(sizeof($temp) > 1 && strlen($temp[1]) > 0){//針對亞屬名與種小名連接情況
             $words[1] = '('.$temp[0].')'; 
             if($num > 2){//抽取出的括號字串插入$words[1]
               for($i=$num-1;$i > 1;$i--){
                 $words[$i+1] = $words[$i]; 
               }
               $words[2] = $temp[1];
               $num = count($words);//新插入,$num重算
             }else{
               $words[2] = $temp[1];
             }
           }//單純亞屬不處理
        }
        
        
        
        /**
         * 過濾$words[0]字尾出現問號者,若無此情形則跳過
         */
        if(preg_match("/^(.*)\?$/",$words[0],$arr)){ 
          $words[0] = $arr[1];
        }
        
        if($words[0]=="Not"){
          return "";
        }
        $words[0] = str_replace("[","",$words[0]);//過濾首字-移除左中括號
        $words[0] = str_replace("]","",$words[0]);//過濾首字-移除右中括號
        $words[0] = preg_replace("/{\?}/","",$words[0]);
        $words[0] = preg_replace("/\{[0-9\. ]*\}/","",$words[0]);//移除數字與小數點
        if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[0],$arr)){//確保都是拉丁字母組成
          $words[0] = $arr[1]; 
        }
        $words[0] = str_replace("[","",$words[0]);
        $words[0] = str_replace("]","",$words[0]);
        $return_string = $words[0];
        /**
         * patten /.../u的u是PCRE Pattern 的 Modifier,目的是針對utf-8
         */
        if(@preg_match("/^([".LOWER."].*)\)$/u",$words[1],$arr))//匹配學名次字($words[1]),看是否全部小寫且結尾有右括號者
        {
            $words[1] = $arr[1];//移除結尾右括號
            if(preg_match("/^(.*)\?$/",$words[1],$arr)) {//移除字尾?符號
              $words[1] = $arr[1];
            }
            /**
             * 1.移除字首(連續)不為拉丁字
             * 2.取出拉丁字部分
             * 3.移除字尾(連續)不為拉丁字
             */
            if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[1],$arr)) {
              $words[1] = $arr[1];
            }
            $return_string.=" $words[1]";//處理過的字加上空白append到$words[0]之後
            return $return_string;//結束比對
            
        }
        /**************開始處理次字****************************/
        $start_map = 1;
        
        
        if(preg_match("/^\(([".UPPER.LOWER."].*)\)$/u",$words[1],$arr)){//判定是否出現亞屬表示
            if (!$subgen_skip){
              $return_string.=" $words[1]";//有保留亞屬括號
            }
            $has_subgen = true;
            
            /**
             * 判斷sp. or spp.
             */
            if($sp_keep && (preg_match("/^sp{1}+[1-9]{0,1}+\.(.*)$/",trim($words[2])) || preg_match("/^(.*)spp\.(.*)$/",trim($words[2])))){
              $return_string.=" $words[2]";
              $start_map = 3;
              
            }else{
            /**
             * 有亞屬,判定第三字($words[2)是否皆小寫且字尾有右括號
             */
              if(@preg_match("/^([".LOWER."].*)\)$/u",$words[2],$arr)){
                $words[2] = $arr[1];
                if(preg_match("/^(.*)\?$/",$words[2],$arr)) {//移除字尾?符號
                  $words[2] = $arr[1];
                }
              /**
               * 移除頭尾非拉丁字母的部份
               */
                if(preg_match("/^[^".UPPER.LOWER."]*([".UPPER.LOWER."]*)[^".UPPER.LOWER."]*$/u",$words[2],$arr)) {
                  $words[2] = $arr[1];
                }
                $return_string.=" $words[2]";
                $start_map = 3;
              //return $return_string;//結束比對
              }
            }
        }    
        
        
        
        $sci_parts = sci_parts();
        $author_parts = author_parts();
        $junk_parts = junk_parts();
        //用預先的陣列去除首字之外的資訊
        for($i=$start_map ; $i<$num ; $i++)
        {   
            if($sp_keep && (preg_match("/^sp{1}+[1-9]{0,1}+\.(.*)$/",trim($words[$i])) || preg_match("/^(.*)spp\.(.*)$/",trim($words[$i])))){
              $return_string.=" $words[$i]";
              continue;
            }
          
            if(preg_match("/^[".UPPER."\(]/u",$words[$i])//首字大寫且包含括號(前面已處理過亞屬,這裡出現的多為作者,所以skip)
               || (preg_match("/[0-9]/",$words[$i])                //或包含 數字且不為"19XX-文字"
               &&!preg_match("/^[1-2]?[0-9]?\-?[".LOWER."]+$/u",$words[$i]))) {
              continue;
            }            
            //過濾頭尾非文數字的部份
            if(preg_match("/^[^0-9".UPPER.LOWER."]*([0-9".UPPER.LOWER."]*)[^0-9".UPPER.LOWER."]*$/u",$words[$i],$arr)){
              $words[$i] = $arr[1];
            }
            if(preg_match("/[".UPPER."]/u",$words[$i])||preg_match("/\[/u",$words[$i])||
            preg_match("/.\../u",$words[$i])||!preg_match("/[[:alpha:]]/u",$words[$i])) {
              continue;
            }

            // 20150702 Mai edit: skip only when junk is not in junk position
            if (!(($subgen_skip && ($i==1) && $words[$i] !== 'x') || ((!$subgen_skip) && ($i==2) && $words[$i] !== 'x'))) {
              if(@$sci_parts[$words[$i]] || @$author_parts[$words[$i]] || @$junk_parts[$words[$i]]) {
                continue;//發現有非拉丁文部份,跳過後面程序,直接往下個$i
              }
            }
            
            if(strlen($words[$i])>1){
              $return_string.=" $words[$i]";
            }
        }
        
        return trim($return_string);//去除掉所有不是拉丁文的部份回傳
    }
    
function italicized_form($string)
    {
        $canonicalForm = canonical_form($string);//去除掉所有不是拉丁文的部份
        
        $words = explode(" ",$canonicalForm);//經過canonical_form只會剩下拉丁文,這裡用空白切割開
        
        foreach($words as $w)
        {
          $pattern = "/(^|[^0-9".UPPER.LOWER."])".preg_quote($w, "/")."([^0-9".UPPER.LOWER."]|$)/";   
                 
          $replacement = "\\1|-n-|".$w."|-/n-|\\2";          
          $string = preg_replace($pattern,$replacement,$string);//針對原始字串中的拉丁文全加上特殊tag
            /**
             * 預防$w(拉丁文)出現脫逸字元
             * preg_quote() 他會在所有的特殊字元 前加上反斜線脫逸字元. 
             * $keywords = "$40 for a g3/400";
			 * $keywords = preg_quote ($keywords, "/");
			 * echo $keywords; // returns \$40 for a g3\/400
             * 
             *  $pattern = /(^|[^0-9所有拉丁字母])拉丁文([^0-9所有拉丁字母]|$)/
             *  抓出目標"拉丁文", 前後的條件:
             *  (^|[^0-9所有拉丁字母])這段說明:"拉丁文"是字串開頭 或 前面連接的不為數字或拉丁文字
             *  ([^0-9所有拉丁字母]|$)這段說明:"拉丁文"後前面連接的不為數字 或 當好是字串結尾
             *  
             *  explain:
             *     ^     =>目標是整個比對輸入列的開始位置
             *     利用『|』把幾個 RE 連接起來時﹐例如 RE1 | RE2 | ... | REn 時﹐ 代表字串只要符合 RE1, RE2, ..., REn 中的任何一個即成立
             *     [^xyz]=>比對不在中中括弧內出現的任一個字元
             *     $	 =>目標結尾是整個比對輸入列的結束位置
             *     
             *     (x)	 =>比對 x 並將符合的部分存入一個變數
             *     如:執行preg_match_all($pattern, $string,$arr);
             *     
             *     以下的$arr[1]與$arr[2]即為()匹配到的
             *     
             *     $arr = 
             *     array(3) {
             *     	[0]=>
             *     	array(1) {
             *         [0]=>
             *         string(9) "Brassica "
             *      }
             *      [1]=>
             *      array(1) {
             *         [0]=>
             *         string(0) ""           ====>匹配到此為輸入列開頭
             *      }
             *      [2]=>
             *      array(1) {
             *         [0]=>
             *         string(1) " "          ====>匹配到之後連接為空白符
             *      }
             *     }
             *     
             *  ********************************************************
             *  利用 ( ) 記憶比對結果
             *  當 pattern 中有 ( ) 時, ( ) 中的部份在比對成功時會依序被記錄到 $1, $2, $3.... 這些個變數中,
             *  在 pattern 的其它部份可以分別用 \1, \2, \3... 來引用 $1, $2, $3 的內容
             *  $replacement = \\1|-n-|拉丁文|-/n-|\2
             *  \1代表匹配陣列$arr的第1個值
             *  \2代表匹配陣列$arr的第2個值
             *  以上兩個值都是$pattern中的()匹配到的值
             *  
             *  用preg_replace取代後,拉丁文部份會被|-n-|與|-/n-|符號包住
             *       |-n-|拉丁文|-/n-|
             *       
             */
            
        }
        
        /**
         * 針對兩個拉丁文中間只有空白的情況,直接把|-/n-| |-n-|的部分換成空白即可
         */
        while(preg_match("/\|-\/n-\| \|-n-\|/",$string,$arr)){
          $string = str_replace("|-/n-| |-n-|"," ",$string);
        } 
        
        /**
         * 最後一步,把 |-n-|符號換成html的斜體
         */
        $string = str_replace("|-n-|","<i>",$string);
        $string = str_replace("|-/n-|","</i>",$string);
        
        return $string;
}
    
function author_parts(){
  
        $array = array();
        $array["d'"]=true;
        $array["der"]=true;
        $array["du"]=true;
        $array["den"]=true;
        $array["le"]=true;
        $array["la"]=true;
        $array["de"]=true;
        $array["da"]=true;
        $array["del"]=true;
        $array["delle"]=true;
        $array["della"]=true;
        $array["des"]=true;
        $array["van"]=true;
        $array["von"]=true;
        $array["y"]=true;
        return $array;
}
    
function sci_parts(){
        $array = array();
        $array["unranked"]=true;
        $array["susbsp"]=true;
        $array["lus"]=true;
        $array["sf"]=true;
        $array["subv"]=true;
        $array["susp"]=true;
        $array["pseudosp"]=true;
        $array["subvariety"]=true;
        $array["variety"]=true;
        $array["subspecies"]=true;
        $array["subgroup"]=true;
        $array["group"]=true;
        $array["subfam"]=true;
        $array["spp"]=true;
        $array["convar"]=true;
        $array["forma"]=true;
        $array["fo"]=true;
        $array["form"]=true;
        $array["subforma"]=true;
        $array["subgen"]=true;
        $array["subg"]=true;
        $array["subf"]=true;
        $array["subvar"]=true;
        $array["nothovar"]=true;
        $array["nothosubsp"]=true;
        $array["variant"]=true;
        $array["var"]=true;
        $array["subsp"]=true;
        $array["sp"]=true;
        $array["ssp"]=true;
        $array["subgenus"]=true;
        $array["group"]=true;
        $array["species"]=true;
        $array["generic"]=true;
        $array["genus"]=true;
        $array["genera"]=true;
        $array["complex"]=true;
        $array["section"]=true;
        $array["genus"]=true;
        $array["morph"]=true;
        $array["mstr"]=true;
        $array["notho"]=true;
        $array["chr"]=true;
        $array["mutation"]=true;
        $array["mutatio"]=true;
        $array["biogroup"]=true;
        $array["sec"]=true;
        $array["lato"]=true;
        $array["juvenile"]=true;
        $array["variété"]=true;
        $array["holotype"]=true;
        $array["cross"]=true;
        $array["f"]=true;
        $array["x"]=true;
        $array["proles"]=true;//jims add        
        return $array;
}
    
function junk_parts(){
        $array = array();
        $array["cultiv"]=true;
        $array["enrichment"]=true;
        $array["culture"]=true;
        $array["clone"]=true;
        $array["str"]=true;
        $array["doubtful"]=true;
        $array["dubious"]=true;
        $array["emended"]=true;
        $array["com"]=true;
        $array["auth"]=true;
        $array["sens"]=true;
        $array["partim"]=true;
        $array["fi"]=true;
        $array["indicated"]=true;
        $array["lat"]=true;
        $array["id"]=true;
        $array["ab"]=true;
        $array["loc"]=true;
        $array["and"]=true;
        $array["&"]=true;
        $array["&amp;"]=true;
        $array["corrig"]=true;
        $array["pv"]=true;
        $array["mult"]=true;
        $array["cv"]=true;
        $array["inval"]=true;
        $array["aff"]=true;
        $array["ambig"]=true;
        $array["anon"]=true;
        $array["orth"]=true;
        $array["hyb"]=true;
        $array["gen"]=true;
        $array["nomen"]=true;
        $array["invalid"]=true;
        $array["prep"]=true;
        $array["dela"]=true;
        $array["press"]=true;
        $array["illeg"]=true;
        $array["ssel"]=true;
        $array["hl"]=true;
        $array["ll"]=true;
        $array["super"]=true;
        $array["pro"]=true;
        $array["hybr"]=true;
        $array["plur"]=true;
        $array["nk"]=true;
        $array["as"]=true;
        $array["to"]=true;
        $array["type"]=true;
        $array["nud"]=true;
        $array["et"]=true;
        $array["al"]=true;
        $array["accord"]=true;
        $array["according"]=true;
        $array["orthographic"]=true;
        $array["emend"]=true;
        $array["of"]=true;
        $array["authors"]=true;
        $array["nom"]=true;
        $array["comb"]=true;
        $array["nov"]=true;
        $array["ined"]=true;
        $array["cons"]=true;
        $array["sensu"]=true;
        $array["hort"]=true;
        $array["p.p"]=true;
        //$array["not"]=true;
        $array["strain"]=true;
        $array["cf"]=true;
        $array["status"]=true;
        $array["unclear"]=true;
        $array["fide"]=true;
        $array["see"]=true;
        $array["comment"]=true;
        $array["bis"]=true;
        $array["specified"]=true;
        $array["be"]=true;
        $array["filled"]=true;
        $array["fil"]=true;
        $array["questionable"]=true;
        $array["the"]=true;
        $array["arid"]=true;
        $array["acc"]=true;
        $array["region"]=true;
        $array["eul"]=true;
        $array["ms"]=true;
        $array["beauv"]=true;
        $array["prop"]=true;
        $array["nm"]=true;
        $array["fort"]=true;
        $array["mut"]=true;
        $array["stat"]=true;
        $array["plants"]=true;
        $array["nec"]=true;
        $array["given"]=true;
        $array["cited"]=true;
        $array["typ"]=true;
        $array["ign"]=true;
        $array["often"]=true;
        $array["referred"]=true;
        $array["superfl"]=true;
        $array["parte"]=true;
        $array["plants"]=true;
        $array["pl"]=true;
        $array["fig"]=true;
        $array["no"]=true;
        $array["prelo"]=true;
        $array["maly"]=true;
        $array["schneider"]=true;
        $array["apud"]=true;
        $array["sine"]=true;
        $array["typo"]=true;
        $array["abbreviation"]=true;
        $array["recorded"]=true;
        $array["label"]=true;
        $array["on"]=true;
        $array["hybridized"]=true;
        $array["with"]=true;
        $array["unspecified"]=true;
        $array["rke"]=true;
        $array["illegible"]=true;
        $array["biotype"]=true;
        $array["race"]=true;
        $array["biotype"]=true;
        $array["vag"]=true;
        $array["tax"]=true;
        $array["x"]=true;
        $array["west"]=true;
        $array["auctor"]=true;
        $array["toni"]=true;
        //$array["assigned"]=true;
        $array["sect"]=true;
        $array["subsect"]=true;
        $array["series"]=true;
        $array["ser"]=true;
        $array["typus"]=true;
        $array["dos"]=true;
        $array["rn"]=true;
        $array["editor"]=true;
        $array["di"]=true;
        $array["list"]=true;
        $array["pl"]=true;
        $array["applicable"]=true;
        $array["undet"]=true;
        $array["species"]=true;
        $array["col"]=true;
        $array["area"]=true;
        $array["op"]=true;
        $array["cit"]=true;
        $array["ey"]=true;
        $array["zu"]=true;
        $array["und"]=true;
        $array["name"]=true;
        $array["only"]=true;
        $array["excl"]=true;
        $array["syn"]=true;
        $array["or"]=true;
        $array["also"]=true;
        $array["by"]=true;
        $array["ex"]=true;
        $array["in"]=true;
        $array["auct"]=true;
        $array["non"]=true;
        $array["date"]=true;
        $array["inter"]=true;
        $array["before"]=true;
        $array["vel"]=true;
        $array["sep"]=true;
        $array["nat"]=true;
        $array["bekannt"]=true;
        $array["ter"]=true;
        $array["É"]=true;
        $array["nr"]=true;
        $array["aberr"]=true;
        $array["nr"]=true;
        $array["between"]=true;
        $array["rus"]=true;
        $array["ent"]=true;
        $array["synanamorph"]=true;
        $array["anamorph"]=true;
        $array["zur"]=true;
        $array["ul"]=true;
        $array["lu"]=true;
        $array["circa"]=true;
        $array["pls"]=true;
        $array["ante"]=true;
        $array["testa"]=true;
        $array["prior"]=true;
        $array["generic"]=true;
        $array["post"]=true;
        $array["etc"]=true;
        $array["binom"]=true;
        //$array["do"]=true;
        $array["nex"]=true;
        $array["auctt"]=true;
        $array["stricto"]=true;
        $array["das"]=true;
        $array["dates"]=true;
        $array["from"]=true;
        $array["doubtful"]=true;
        $array["dubious"]=true;
        $array["emended"]=true;
        $array["com"]=true;
        $array["partim"]=true;
        $array["fi"]=true;
        $array["indicated"]=true;
        $array["lat"]=true;
        $array["ii"]=true;
        $array["ry"]=true;
        $array["ndez"]=true;
        $array["lez"]=true;
        $array["lc"]=true;
        $array["rskov"]=true;
        $array["nudum"]=true;
        $array["sbsp"]=true;
        $array["morpha"]=true;
        $array["esp"]=true;
        $array["mph"]=true;
        $array["s-sp"]=true;
        $array["subs"]=true;
        $array["variété"]=true;
        $array["forme"]=true;
        $array["subspec"]=true;
        $array["sous-type"]=true;
        $array["inte"]=true;
        $array["subspp"]=true;
        $array["indet"]=true;
        $array["corrected"]=true;
        $array["none"]=true;
        $array["iber"]=true;
        $array["eur"]=true;
        $array["balcan"]=true;
        $array["nonn"]=true;
        $array["fl"]=true;
        $array["cauc"]=true;
        $array["armen"]=true;
        $array["inc"]=true;
        $array["orient"]=true;
        $array["ross"]=true;
        $array["med"]=true;
        $array["germ"]=true;
        $array["boreal"]=true;
        $array["boruss"]=true;
        $array["amer"]=true;
        $array["prol"]=true;
        $array["ca"]=true;
        $array["but"]=true;
        $array["misapplied"]=true;
        $array["subst"]=true;
        $array["for"]=true;
        $array["int"]=true;
        $array["several"]=true;
        $array["error"]=true;
        $array["pers"]=true;
        $array["comm"]=true;
        $array["nudum"]=true;
        $array["errore"]=true;
        $array["incertae"]=true;
        $array["sedis"]=true;
        $array["sic"]=true;
        $array["substit"]=true;
        $array["web"]=true;
        $array["site"]=true;
        $array["viii"]=true;
        $array["oblit"]=true;
        $array["new"]=true;
        $array["xxxx"]=true;
        $array["an"]=true;
        $array["objective"]=true;
        $array["synonym"]=true;
        $array["now"]=true;
        $array["bottom"]=true;
        $array["both"]=true;
        $array["pictures"]=true;
        $array["synonymy"]=true;
        $array["uncertain"]=true;
        $array["substit"]=true;
        $array["under"]=true;
        $array["inc"]=true;
        $array["sed"]=true;
        $array["spelling"]=true;
        $array["brit"]=true;
        $array["irj"]=true;
        $array["mf"]=true;
        $array["subfo"]=true;
        $array["sport"]=true;
        $array["tribe"]=true;
        $array["subtribe"]=true;
        $array["subser"]=true;
        $array["subtrib"]=true;
        $array["trib"]=true;
        $array["sebsp"]=true;
        $array["lusus"]=true;
        $array["sub"]=true;
        $array["gr"]=true;
        $array["oblvar"]=true;
        $array["nra"]=true;
        $array["fam"]=true;
        $array["en"]=true;
        $array["mey"]=true;
        $array["susbp"]=true;
        $array["sre"]=true;
        $array["subtr"]=true;
        $array["subdiv"]=true;
        $array["pars"]=true;
        $array["quad"]=true;
        $array["typum"]=true;
        $array["set"]=true;
        $array["rouy"]=true;
        $array["opiz"]=true;
        $array["agsp"]=true;
        $array["ourk"]=true;
        //$array["proles"]=true;
        $array["liu"]=true;
        $array["ecad"]=true;
        $array["substirps"]=true;
        $array["groupa"]=true;
        $array["groupe"]=true;
        $array["divis"]=true;
        $array["nothosect"]=true;
        $array["nothomorph"]=true;
        $array["em"]=true;
        $array["nsubsp"]=true;
        $array["monstr"]=true;
        $array["rev"]=true;
        $array["basionym"]=true;
        $array["quoad"]=true;
        $array["ven"]=true;
        $array["order"]=true;
        $array["mon"]=true;
        $array["superord"]=true;
        $array["ord"]=true;
        $array["subvars"]=true;
        $array["cm"]=true;
        $array["supertrib"]=true;
        $array["mnstr"]=true;
        $array["ren"]=true;
        $array["subset"]=true;
        $array["subtribus"]=true;
        $array["agg"]=true;
        $array["jr"]=true;
        $array["nothof"]=true;
        $array["nothogen"]=true;
        $array["nothosubgen"]=true;
        $array["individual"]=true;
        $array["index"]=true;
        $array["supsp"]=true;
        $array["attr"]=true;
        $array["incorrectly"]=true;
        $array["ined;cf"]=true;
        $array["el"]=true;
        $array["various"]=true;
        $array["cultivars"]=true;
        $array["af"]=true;
        $array["valide"]=true;
        $array["publ"]=true;
        $array["class"]=true;
        $array["sufam"]=true;
        $array["xx"]=true;
        $array["xxx"]=true;
        $array["xxxx"]=true;
        $array["quib"]=true;
        $array["ap"]=true;
        $array["subap"]=true;
        $array["grupo"]=true;
        $array["gruppe"]=true;
        $array["oec"]=true;
        $array["prole"]=true;
        $array["nothsect"]=true;
        $array["nssp"]=true;
        $array["nopthosubsp"]=true;
        $array["jun"]=true;
        $array["rx"]=true;
        $array["like"]=true;
        $array["ascribed"]=true;
        $array["included"]=true;
        $array["rejected"]=true;
        $array["segregates"]=true;
        $array["ngstr"]=true;
        $array["nothosubg"]=true;
        $array["subclassis"]=true;
        $array["eds"]=true;
        $array["spec"]=true;
        $array["ty"]=true;
        $array["ed"]=true;
        return $array;
}
