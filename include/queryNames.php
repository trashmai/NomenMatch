<?php

require_once "./include/treat_word.inc";
require_once "./include/cleanname.inc";

/* normalize input data
if (empty($name)) {
	return;
}

$name = str_replace("　", " ", trim($name, " \t"));
while (preg_match('/[\s]{2,}/', $name)) {
	$name = preg_replace('/\s\s/', " ", $name);
}

if (!empty($name)) {
	$name = canonical_form($name);
	queryNames($name);
}
//*/

function queryNames ($name, $against, $best, $ep) {
	if (empty($ep)) return false;

	$ep .= '/select?wt=json&q=*:*';
	// $ep = 'http://localhost:8983/solr/taxa/select?wt=json&q=*:*';
	// $ep = 'http://140.109.28.72/solr4/taxa/select?wt=json&q=*:*';

	extract_results("", "", $reset=true);
	// mix2; work with latin part b2, c2, and suggestions of latin part b2, c2
	$mix2 = array();
	$sound_mix2 = array();
	$matched = array();
	$info = array();
	$suggestions = array();
	$long_suggestions = array();

	$name_cleaned = canonical_form($name, true);

	$parts = explode(" ", $name_cleaned);

	$lpa2 = $parts[0];
	$lpb2 = @$parts[1];
	$lpc2 = @$parts[2];

	$spa2 = treat_word($lpa2);
	$spb2 = treat_word($lpb2);
	$spc2 = treat_word($lpc2);

	if (!empty($parts[1])) {
		$mix2[] = $parts[1];
	}
	else {
//		return null;
		return	array('N/A' => array(
					'name' => $name,
					'name_cleaned' => $name_cleaned,
					'matched' => 'N/A',
					'matched_clean' => 'N/A',
					'accepted_namecode' => array(),
					'namecode' => array(),
					'source' => array(),
					'url_id' => array(),
					'a_url_id' => array(),
					'kingdom' => array(),
					'phylum' => array(),
					'class' => array(),
					'order' => array(),
					'family' => array(),
					'higher_than_family' => array(),
					'type' => 'N/A',
				)
			);

	}
	if (!empty($parts[2])) {
		$mix2[] = $parts[2];
	}

	if (!empty($spb2)) {
		$sound_mix2[] = $spb2;
	}
	if (!empty($spc2)) {
		$sound_mix2[] = $spc2;
	}


	// Type 1
	$query_url_1 = $ep . '&fq=canonical_name:"' . urlencode($name_cleaned) . '"';
	extract_results($query_url_1, TYPE_1, $reset=false, $against);

	// with minor spell error
	$query_url_1_err_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode(implode(" ", $mix2)) ;
	$suggestion = extract_suggestion ($query_url_1_err_suggestion, TYPE_1_E);
	if (!empty($suggestion)) {
		$query_url_1_err = $ep . '&fq=canonical_name:"' . urlencode("$lpa2 $suggestion") . '"';
		extract_results($query_url_1_err, TYPE_1_E, $reset=false, $against);
	}

	//*
	$query_url_1_err_long_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode($name_cleaned) ;
	$long_suggestion = extract_suggestion ($query_url_1_err_long_suggestion, TYPE_1_E);
	if (!empty($long_suggestion)) {
		$query_url_1_err = $ep . '&fq=latin_part_a:' . $lpa2 . '&fq=canonical_name:"' . urlencode("$long_suggestion") . '"';
		extract_results($query_url_1_err, TYPE_1_E, $reset=false, $against);
	}
	//*/
	$all_matched_tmp = extract_results();

	if (!empty($all_matched_tmp['']) || $best == 'no') {

		// Type 2
		$query_url_2 = $ep . '&fq=latin_part_a:' . urlencode($lpa2) . '&fq=latin_part_bc:(' . urlencode(implode(' OR ', $mix2)) . ")";
		extract_results($query_url_2, TYPE_2, $reset=false, $against);

		// with minor spell error
		foreach (array_unique($mix2) as $p) {
			$query_url_2_err_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode($p) ;
			$suggestion = extract_suggestion ($query_url_2_err_suggestion, TYPE_2_E);
			if (!empty($suggestion)) {
				$suggestions[] = $suggestion;
			}
			$query_url_2_err_long_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode("$lpa2 $p") ;
			$long_suggestion = extract_suggestion ($query_url_2_err_long_suggestion, TYPE_2_E);
			if (!empty($long_suggestion)) {
				$long_suggestions[] = $long_suggestion;
			}
		}
		if (!empty($suggestions)) {
			$suggestions = array_unique(array_merge($suggestions, $mix2));
			$query_url_2_err = $ep . '&fq=latin_part_a:' . urlencode($lpa2) . '&fq=latin_part_bc:(' . urlencode(implode(' OR ', $suggestions)) . ")";
			extract_results($query_url_2_err, TYPE_2_E, $reset=false, $against);
		}
		if (!empty($long_suggestions)&&(count($mix2)>1)) {
			foreach ($long_suggestions as $long_suggestion) {
                $query_url_2_err = $ep . '&fq=canonical_name:"' . urlencode($long_suggestion) . '"';
				extract_results($query_url_2_err, TYPE_2_E, $reset=false, $against);
			}
		}

		// Genus spell error???
		$query_url_2_genus_err_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode($lpa2) ;
		$suggestion = extract_suggestion ($query_url_2_genus_err_suggestion, TYPE_2_GE);

                if (is_null($suggestion)) {
			$query_url_2_genus_err_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode($name_cleaned);
			$suggestion = array_shift(explode(" ", extract_suggestion ($query_url_2_genus_err_suggestion, TYPE_2_GE)));

			if (is_null($suggestion)) {
				foreach ($mix2 as $mp) {
					$query_url_2_genus_err_suggestion = $ep . "&rows=0&spellcheck.q=" . urlencode($lpa2 + ' ' + $mp);
					$suggestion = array_shift(explode(" ", extract_suggestion ($query_url_2_genus_err_suggestion, TYPE_2_GE)));
					if (!is_null($suggestion)) {
						break;
					}
				}
			}
		}


		if (treat_word($lpa2, true) == treat_word($suggestion, true)) {
			$query_url_2_genus_err = $ep . '&fq=latin_part_a:' . urlencode($suggestion) . '&fq=latin_part_bc:(' . urlencode(implode(' OR ', $mix2)) . ")";
			extract_results($query_url_2_genus_err, TYPE_2_GS, $reset=false, $against);
		}
		elseif ((levenshtein($lpa2, $suggestion) == 1)&&(strlen($lpa2)==strlen($suggestion))) {
			$len = strlen($lpa2);
			for ($i=0; $i<$len; $i++) {
				if ($lpa2[$i] != $suggestion[$i]) {
					if (similar_char($lpa2[$i], $suggestion[$i], @$lpa2[$i+1], @$suggestion[$i+1])) {
						$query_url_2_genus_err = $ep . '&fq=latin_part_a:' . urlencode($suggestion) . '&fq=latin_part_bc:(' . urlencode(implode(' OR ', $mix2)) . ")";
						extract_results($query_url_2_genus_err, TYPE_2_GL, $reset=false, $against);
					}
				}
			}
		}
		elseif (levenshtein($lpa2, $suggestion) == 1) {
			$query_url_2_genus_err = $ep . '&fq=latin_part_a:' . urlencode($suggestion) . '&fq=latin_part_bc:(' . urlencode(implode(' OR ', $mix2)) . ")";
			extract_results($query_url_2_genus_err, TYPE_2_GL2, $reset=false, $against);
		}
		$all_matched_tmp = extract_results();
	}

	if (!empty($all_matched_tmp['']) || $best == 'no') {
		// Type 3
		$sound = treat_word($name_cleaned);
		$query_url_3 = $ep . '&fq=sound_name:"' . urlencode($sound) . '"';
		extract_results($query_url_3, TYPE_3_S, $reset=false, $against);

		// Type 3 mix
		$query_url_3 = $ep . '&fq=sound_part_a:' . urlencode($spa2) . '&fq=sound_part_bc:(' . urlencode(implode(' OR ', $sound_mix2)) . ")";
		extract_results($query_url_3, TYPE_3_S2, $reset=false, $against);

		$sound_mix2_strip_ending = array_map("treat_word", $mix2, array_fill(0, count($mix2), true));
		$query_url_3_strip_bc_ending = $ep . '&fq=sound_part_a:' . urlencode($spa2) . '&fq=sound_part_bc_strip_ending:(' . urlencode(implode(' OR ', $sound_mix2_strip_ending)) . ")";
		extract_results($query_url_3_strip_bc_ending, TYPE_3_S3, $reset=false, $against);

		$query_url_3_strip_all_ending = $ep . '&fq=sound_part_a_strip_ending:' . urlencode(treat_word($spa2, true)) . '&fq=sound_part_bc_strip_ending:(' . urlencode(implode(' OR ', $sound_mix2_strip_ending)) . ")";
		extract_results($query_url_3_strip_all_ending, TYPE_3_GUESS, $reset=false, $against);

		$all_matched_tmp = extract_results();
	}

	foreach ($all_matched_tmp as $m) {
		$all_matched[$m['matched'][0]] = array_merge(array('name' => $name, 'name_cleaned' => $name_cleaned), $m);
	}
/*
echo "<xmp>";
var_dump($all_matched);
echo "</xmp>";
//*/
	//var_dump($all_matched);
	return $all_matched;

}


// Functions
function extract_suggestion ($query_url="", $msg="") {
//	echo $msg . "\n";
//	echo "extract suggestion, " . $query_url . "\n";
	$jo = @json_decode(@file_get_contents($query_url));
	if (!empty($jo->spellcheck->suggestions)) {
		$vals = array_values($jo->spellcheck->suggestions);
		//echo "<xmp>";
		//var_dump($vals);
		//echo "</xmp>";
		$idx = array_search("collation", $vals);
		return trim($vals[0][$idx+1], "()");
	}
}


function extract_results ($query_url="", $msg="", $reset=false, $against="") {

	static $all_matched = array();
	static $query_urls = array();
//	echo "<xmp>";
//	echo $msg . "\n";
//	echo "extract results, " . $query_url . "\n";
//	echo "</xmp>";
	if ($reset) {
		$all_matched = array();
		$query_urls = array();
	}
	if (empty($query_url)&&!$reset) {
		return $all_matched;
	}
	if (!empty($query_url)) {

		if (!empty($against)) {
			$query_url .= "&fq=source:$against";
		}

		if (@$query_urls[$query_url]) {
			return;
		}
		$query_urls[$query_url] = true;
		$jo = @json_decode(@file_get_contents($query_url));
		echo $query_url;
		echo "<pre>" . var_export($jo, true) . "</pre>";
	}
	if (!empty($jo) && $jo->response->numFound > 0) {
		foreach ($jo->response->docs as $doc) {
			$doc->is_accepted = ($doc->namecode === $doc->accepted_namecode)?1:0;
			$matched[] = $doc;
			if (empty($all_matched[$doc->canonical_name])) {
				unset($all_matched['']);
				$all_matched[$doc->canonical_name] = array(
					'matched' => $doc->original_name,
					'matched_clean' => $doc->canonical_name,
					'accepted_namecode' => array(@$doc->accepted_namecode),
					'namecode' => array(@$doc->namecode),
					'source' => array(array_shift(explode("-", $doc->id))),
					'url_id' => array(@$doc->url_id),
					'a_url_id' => array(@$doc->a_url_id),
					'kingdom' => array(@$doc->kingdom),
					'phylum' => array(@$doc->phylum),
					'class' => array(@$doc->class),
					'order' => array(@$doc->order),
					'family' => array(@$doc->family),
					'higher_than_family' => array(@$doc->order."-".@$doc->class."-".@$doc->phylum."-".@$doc->kingdom),
					'type' => $msg,
				);
			}
			else {
				if (!in_array(@$doc->namecode, $all_matched[$doc->canonical_name]['namecode'])) {
					$all_matched[$doc->canonical_name]['namecode'][] = @$doc->namecode;
					$all_matched[$doc->canonical_name]['source'][] = array_shift(explode("-", $doc->id));
					$all_matched[$doc->canonical_name]['accepted_namecode'][] = @$doc->accepted_namecode;
					$all_matched[$doc->canonical_name]['url_id'][] = @$doc->url_id;
					$all_matched[$doc->canonical_name]['a_url_id'][] = @$doc->a_url_id;
					$all_matched[$doc->canonical_name]['kingdom'][] = @$doc->kingdom;
					$all_matched[$doc->canonical_name]['phylum'][] = @$doc->phylum;
					$all_matched[$doc->canonical_name]['class'][] = @$doc->class;
					$all_matched[$doc->canonical_name]['order'][] = @$doc->order;
					$all_matched[$doc->canonical_name]['family'][] = @$doc->family;
					$all_matched[$doc->canonical_name]['higher_than_family'][] = @$doc->order."-".@$doc->class."-".@$doc->phylum."-".@$doc->kingdom;
				}

			}
		}
//		echo "<xmp>";
//		var_dump($matched);
//		echo "</xmp>";
	}
	elseif (empty($all_matched)) {
		$all_matched[''] = array(
			'matched' => '',
			'matched_clean' => '',
			'accepted_namecode' => array(),
			'namecode' => array(),
			'source' => array(),
			'url_id' => array(),
			'a_url_id' => array(),
			'kingdom' => array(),
			'phylum' => array(),
			'class' => array(),
			'order' => array(),
			'family' => array(),
			'higher_than_family' => array(),
			'type' => 'no match'
		);
	}
}

// confusing OCR results or hand writings
function similar_char($a, $b, $aplus1='', $bplus1='') {

	if (empty($aplus1)) $aplus1 = '';
	if (empty($bplus1)) $bplus1 = '';

	$similar_sets = array(
		array('r','m', 'n'),
		array('a', 'd'),
		array('a', 'u'),
		array('c', 'o'),
		array('c', 'r'),
		array('c', 'e'),
		array('e', 'o'),
                array('t', 'r'),
                array('A', 'E'),
	);
	$similar_2chrs = array(
		array('in' ,'m'),
		array('ni' ,'m'),
		array('rn' ,'m'),
		array('ri' ,'n'),
	);
	$similar = false;

	foreach ($similar_sets as $set) {
		if (in_array($a, $set) && in_array($b, $set)) {
			$similar = true;
		}
	}

	foreach ($similar_2chrs as $chars) {
		if ((in_array($a, $chars) && in_array($b.$bplus1, $set)) || (in_array($a.$aplus1, $chars) && in_array($b, $set))){
			$similar = true;
		}
	}

	return $similar;
}


?>
