<?php

ini_set("memory_limit", "1024M");
set_time_limit(3600);
$stime = microtime(true);

$names = explode("|", str_replace("\n", "|", @$_REQUEST['names']));
require_once "./include/functions.php";

if (@$_REQUEST['lang']) {
	$gui_lang_file = "./conf/lang/error_codes.".$_REQUEST['lang'].".php";
	if (file_exists($gui_lang_file)) {
		require_once "./conf/lang/error_codes.".$_REQUEST['lang'].".php";
	}
	else {
		require_once "./conf/lang/error_codes.php";
	}
}
else {
	require_once "./conf/lang/error_codes.php";
}

require_once "./include/queryNames.php";

$format = (!empty($_REQUEST['format']))?$_REQUEST['format']:'';
$against = (!empty($_REQUEST['against']))?$_REQUEST['against']:'';
$best = (!empty($_REQUEST['best']))?$_REQUEST['best']:'yes';
$ep = (!empty($_REQUEST['ep']))?$_REQUEST['ep']:file_get_contents(dirname(realpath(__FILE__)).'/conf/solr_endpoint');

$ep = trim($ep, " /\r\n");

$res = array();

foreach ($names as $nidx => $name) {

/*
	for ($i=0; $i<strlen($name); $i++) {
		echo $name[$i].",".ord($name[$i]).",";
	}
//*/

	$undecide = false;
	$onlyOne = false;
	$moreThan1 = false;
	$multiAnc = false;
	$multiNc = false;

	$scores = array();
	$name_cleaned = canonical_form(trim($name, " \t\r\n.,;|"), true);
	//if (empty($name)) continue;

	$all_matched = queryNames($name, $against, $best, $ep);

	//ksort($all_matched);
	foreach ($all_matched as $matched_name => $matched) {
		//var_dump($matched);
		//$scores[$matched_name] = nameSimilarity($matched_name, $name_cleaned, $matched['type']);
		$scores[$matched_name] = nameSimilarity($matched['matched_clean'], $name_cleaned, $matched['type']);
	}
	arsort($scores);

	foreach ($scores as $matched_name => $score) {
		if ($score < 0) {
			$score = 0;
		}
		if ($best == 'yes') {

			$matched_only = array_keys($scores);
			$scores_only = array_values($scores);
			$score0 = $scores_only[0];
			if (count($scores) > 1) {
				$moreThan1 = true;
				$score1 = $scores_only[1];
				//var_dump(array($score0, $score1));
				if (round($score0/3.5,3) == round($score1/3.5,3)) {
					$undecide = true;
				}
				//var_dump($undecide);
			}
			else {
				$onlyOne = true;
			}

			if ($undecide || $onlyOne) {
				$comb = array();
				$comb_string = array();
				foreach ($matched_only as $m_idx => $mo) {
					$comb_dmin = 999;
					$comb[$m_idx] = array('[whatever]','[whatever]','[whatever]');
					$comb_string[$m_idx] = "";
					$comb_common = array();

					$parts1 = explode(" ", canonical_form($mo, true));
					$parts2 = explode(" ", $name_cleaned);
					$parts_bc_1 = array_slice($parts1, 1);
					$parts_bc_2 = array_slice($parts2, 1);
					$diff_rank = false;
					foreach ($parts_bc_1 as $idx1 => $pbc1) {
						foreach ($parts_bc_2 as $idx2 => $pbc2) {
							// var_dump(levenshtein($pbc1, $pbc2));
							if (levenshtein($pbc1, $pbc2) < $comb_dmin) {
								// $comb = $parts1[0] . " " . $pbc1;
								$comb[$m_idx][0] = $parts1[0];
								$comb_common = array('idx' => $idx1, 'name' => $pbc1);
								$comb_dmin = levenshtein($pbc1, $pbc2);
								if ($idx1 != $idx2) {
									$diff_rank = true;
								}
							}
						}
					}
					$comb[$m_idx][$comb_common['idx']+1] = $comb_common['name'];
					$comb_string[$m_idx] = implode(" ", $comb[$m_idx]);
				}
				if ($moreThan1 || $diff_rank) {
					$undecide = true;
					$all_matched[$matched_name]['score'] = 'N/A';
					$all_matched[$matched_name]['matched'] = implode("|", array_unique($comb_string));
					if ($moreThan1) {
						$all_matched[$matched_name]['namecode'] = array();
						$all_matched[$matched_name]['accepted_namecode'] = array();
						$all_matched[$matched_name]['source'] = array();
						$all_matched[$matched_name]['type'] .= "|Undecidable: Multiple cross-ranked matches";
					}
					elseif ($diff_rank) {
						$all_matched[$matched_name]['type'] .= "|Undecidable: Cross-ranked match";
					}
				}


			}

			$srcMatchedAncCnt = array();
			$srcMatchedAnc = array();
			$srcAnc = array();
			$all_matched[$matched_name]['best'] = array();
			if (!empty($all_matched[$matched_name]['accepted_namecode']) && !$undecide) {
//var_dump($all_matched[$matched_name]);
				$ncs = $all_matched[$matched_name]['namecode'];
				$ancs = $all_matched[$matched_name]['accepted_namecode'];
				$srcs = $all_matched[$matched_name]['source'];
				foreach ($srcs as $src_idx => $src) {
					if ($ncs[$src_idx] === $ancs[$src_idx]) {
						$srcMatchedAncCnt[$src] += 1;
						$srcMatchedAnc[$src][] = $ancs[$src_idx];
					}
					else {
						$srcMatchedAncCnt[$src] += 0;
					}
					$srcAnc[$src][] = $ancs[$src_idx];
				}

				$max_count = 0;
				if (count($srcMatchedAncCnt) > 0) {
					foreach ($srcMatchedAncCnt as $src => $srcMatchedAnc_cnt) {
						if ($srcMatchedAnc_cnt > 1) {
							// $all_matched[$matched_name]['score'] = 'N/A';
							$all_matched[$matched_name]['type'] .= "|Undecidable: Multiple matched, accepted names in $src";
							$undecide = true;
						}
						elseif ($srcMatchedAnc_cnt == 0) {
							if (count(array_unique($srcAnc[$src])) > 1) {
								// $all_matched[$matched_name]['score'] = 'N/A';
								$all_matched[$matched_name]['type'] .= "|Undecidable: Multiple accepted names of matched synonyms in $src";
								$undecide = true;
							}
							else {
								$all_matched[$matched_name]['best'][$src] = $srcAnc[$src][0];
							}
						}
						else {
							$all_matched[$matched_name]['best'][$src] = $srcMatchedAnc[$src][0];
						}
					}
				}

			}


		}
	
		$all_matched[$matched_name]['taxonRank'] = detRank($all_matched[$matched_name]['matched'], $all_matched[$matched_name]['matched_clean']);
		$res[$nidx][] = array_merge(array('score' => round($score/3.5,3)), $all_matched[$matched_name]);
		if ($best == 'yes') {
			break;
		}
	}
}


$etime = microtime(true);

render($res, $format, $etime - $stime);

function color_class ($idx) {
/*	$colors = array(
		'row_red',
		'row_orange',
		'row_yellow',
		'row_green',
		'row_blue',
		'row_purple',
	);
 */

	$colors = array(
		'row_yellow',
		'row_green',
	);
	return $colors[$idx % count($colors)];
}



function render_table ($data, $time, $hardcsv=false) {

	header("Content-type: text/html; charset=utf-8");
	$src_conf = read_src_conf();

	global $against, $best;

	$not_show = array(
		'name_cleaned',
		'url_id',
		'a_url_id',
		'kingdom',
		'phylum',
		'class',
		'order',
	);

	echo "<head>";
    echo "<link href='http://fonts.googleapis.com/css?family=Roboto|Slabo+27px&subset=latin,latin-ext' rel='stylesheet' type='text/css'>";
    echo "<link href='https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/spacelab/bootstrap.min.css' rel='stylesheet' integrity='sha384-zF4BRsG/fLiTGfR9QL82DrilZxrwgY/+du4p/c7J72zZj+FLYq4zY00RylP9ZjiT' crossorigin='anonymous'>";
    echo "<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js' integrity='sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa' crossorigin='anonymous'></script>";
	echo "<script src='https://code.jquery.com/jquery-2.1.4.min.js'></script>";
	echo "<script src='./js/diff.js'></script>";
	echo "</head>";

	$prev_name = "";
	$row_class = "even";

	$serial_no = 0;

	echo "<style>";
	echo "table, tr, td { border:solid 1px black;}\n";
	echo ".row_red { background:#FF9999;}\n";
	echo ".row_orange { background:#FFB547;}\n";
	echo ".row_yellow { background:#FFFF70;}\n";
	echo ".row_green { background:#8DE28D;}\n";
	echo ".row_blue { background:#91DAFF;}\n";
	echo ".row_purple { background:#E9A9FF;}\n";
	echo "</style>";

    echo "<body>";
    echo "<div class='container'>";
    echo "<h1 class='navbar-brand m-b-0'>Matching results</h1>";
    echo "<br/>";
    echo "<p>";
	echo "query time: " . round($time, 3) . " s<br/>";
	echo "memory usage: " . round(memory_get_usage(true) / (1024 * 1024), 1) . " MB<br/>";
	echo "Legend: <span style='color:red;'>removed</span> <span style='color:blue;'>added</span> <span style='color:grey;'>common</span>";
    echo "</p>";
    
	echo "<table class='table table-striped table-bordered'>";

	$tmp_data0 = $data[0][0];
	foreach ($not_show as $ns) {
		unset($tmp_data0[$ns]);
	}

	$columns = array_keys($tmp_data0);

	echo "<tr><td>no.</td><td>" . implode("</td><td>", $columns) . "</td></tr>\n";
	$prev_score = -100;
	foreach ($data as $nidx => $name_d) {
		foreach ($name_d as $d) {
			$d['name'] = htmlentities($d['name']);
			/*
			if ($d['name'] != $prev_name) {
				$prev_name = $d['name'];
				$serial_no++;
				$row_class = color_class($serial_no - 1);
			}
			elseif ($d['score'] > $prev_score) {
				$serial_no++;
				$row_class = color_class($serial_no - 1);
			}
			$prev_score = $d['score'];
			//*/

			$serial_no = $nidx + 1;
			$row_class = color_class($nidx);

            echo "<tr class='row_result' id='row_".$serial_no."'><td>$serial_no</td><td>";

			$ncs = $d['namecode'];
			$ancs = $d['accepted_namecode'];
			$sources = $d['source'];
			$url_ids = $d['url_id'];
			$aurl_ids = $d['a_url_id'];

			$html_ncs = array();
			$html_ancs = array();
			$html_sources = array();

			$url_anc_srcs = array();

			foreach ($sources as $src_idx => $src) {

				// TODO: this part must be implemented dynamicaly reading configurations from some file

				if (!empty($src_conf[$src]['url_base'])) {
					$url_base = $src_conf[$src]['url_base'];
				}
				else {
					$url_base = "http://example.org/species/id/";
				}

				$url = $url_base . $url_ids[$src_idx];
				$aurl = $url_base . $aurl_ids[$src_idx];

				$html_ncs[$src_idx] = "<a target='_blank' href='$url'>" . $ncs[$src_idx] . "</a>";
				$html_ancs[$src_idx] = "<a target='_blank' href='$aurl'>" . $ancs[$src_idx] ."</a>";

				$url_anc_srcs[$src][$ancs[$src_idx]] = "<a target='_blank' href='$aurl'>" . $ancs[$src_idx] ."</a>";



				if ($ncs[$src_idx] == $ancs[$src_idx]) {
					$html_sources[$src_idx] = "<font color='#ff0000'>$src</font>";
				}
				else {
					$html_sources[$src_idx] = $src;
				}
			}
			$d['source'] = implode("<br/>", $html_sources);
			$d['namecode'] = implode("<br/>", $html_ncs);
			$d['accepted_namecode'] = implode("<br/>", $html_ancs);

			$d['higher_than_family'] = implode("<br/>", $d['higher_than_family']);
			$d['family'] = implode("<br/>", $d['family']);

			$bests = $d['best'];
			if (!empty($bests)) {
				foreach ($bests as $src => $best_anc) {
					$d['best'][$src] = $src . ":" . $url_anc_srcs[$src][$best_anc];
				}
				$d['best'] = implode("<br/>", $d['best']);
			}

			if (empty($d['best'])&&($best=='yes')&&($d['score']==='N/A')) {
				$d['best'] = "<a target='_blank' href='api.php?names=".urlencode($d['name'])."&format=table&best=no&against=".$against."'>" . 'undecidable</a>';
			}
			elseif (empty($d['best'])) {
				$d['best'] = '';
			}
			if ($best !== 'yes') {
				unset($d['best']);
			}

			$d['matched'] = str_replace("|", "<br/>", $d['matched']);
			$d['type'] = str_replace("|", "<br/>", $d['type']);

			$d['name'] = "<span name_cleaned='" . $d['name_cleaned'] . "'>" . $d['name'] . "</span>";

			foreach ($not_show as $ns) {
				unset($d[$ns]);
			}

			if ($hardcsv) {
				echo str_replace("<br/>", "|", implode("</td><td>", $d));
			}
			else {
				echo implode("</td><td>", $d);
			}
			echo "</td></tr>\n";
		}
	}
	echo "</table>\n";
	echo "<script src='./js/diffName.js'></script>";
}

function render_plain ($data, $time) {
	header("Content-type: text/plain; charset=utf-8");
	echo "query time: " . $time . "s\n";
	echo implode("\t", array_keys($data[0][0])) . "\n";
	foreach ($data as $d) {
		foreach ($d as $col) {
			foreach ($col as $idx => $val) {
				if (is_array($val)) {
					$new_val = implode("|", $val);
				}
				else {
					$new_val = implode("|", explode("|", trim($val, "\r\n ")));
				}
				$col[$idx] = $new_val;
			}
			echo implode("\t", $col) . "\n";
		}
	}
}


function render_csv ($data, $time) {
	header("Content-type: text/csv; charset=utf-8");
	header("Content-Disposition: attachment; filename=results.csv");
	header("Pragma: no-cache");
	header("Expires: 0");
	$utf8_bom = "\xEF\xBB\xBF";
	echo $utf8_bom;
	echo "sep=\t\n";
	echo implode("\t", array_keys($data[0][0])) . "\n";
	foreach ($data as $d) {
		foreach ($d as $col) {
			foreach ($col as $idx => $val) {
				if (is_array($val)) {
					$new_val = implode("|", $val);
				}
				else {
					$new_val = implode("|", explode("|", trim($val, "\r\n ")));
				}
				$col[$idx] = $new_val;
			}
			echo implode("\t", $col) . "\n";
		}
	}

}

function render_json ($data, $time) {
	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array(
		'query_time' => $time,
		'results' => $data,
		));
//	echo json_encode($data);
}


function render ($data, $format='table', $time) {

	$func_name = "render_" . $format;
	if (function_exists($func_name)) {
		call_user_func($func_name, $data, $time);
	}
	else {
		render_table($data, $time);
	}
}


function nameSimilarity ($matched, $name, $type=null) {

	if ($matched == 'N/A') return 0;

	//$matched_cleaned = canonical_form($matched, true);
	$matched_cleaned = $matched;

	if (empty($matched_cleaned)) return 0;

	$score = 3;

	$parts1 = explode(" ", $matched_cleaned);
	$parts2 = explode(" ", $name);

	if (count($parts1) === count($parts2)) {
		if ((levenshtein($matched_cleaned, $name) <= 3)||(preg_match('/full/i', $type))) {
			$score += 0.5;
		}
		for ($pidx=0; $pidx<count($parts1); $pidx++) {
			if ($parts1[$pidx][0] != $parts2[$pidx][0]) {
				$score -= 1.5;
			}
		}
	}

	if (preg_match('/sound|look/i', $type)) {
		// $score -= 0.05;
		$score -= levenshtein($name, $matched_cleaned) / 20;
	}


	$penalty = 0;
	if (count($parts1) == count($parts2)) {
		$penalty = 0;
	}
	elseif (count(array_unique($parts1)) != count(array_unique($parts2))) {
		$penalty = 0.01;
	}

	if (count(array_unique($parts1)) > count(array_unique($parts2))) {
		$penalty += 0.5;
//		$penalty = $penalty / (3 - count(array_unique(array_slice($parts1, 1))));
	}
	elseif (count(array_unique($parts1)) < count(array_unique($parts2))) {
		if (count(array_unique($parts1)) < count($parts1)) {
			$penalty -= 0.0;
		}
		else {
			$penalty -= 0.2;
		}
//		$penalty = ($penalty < 0)?0:$penalty;
	}



	$score -= (2 * levenshtein($parts1[0] /* Genus */, $parts2[0]) / strlen($parts1[0]));

	$sub_parts1 = array_slice($parts1, 1);
	$sub_parts2 = array_slice($parts2, 1);

	$total_err = 0;
	foreach ($sub_parts2 as $sp2_idx => $sp2) {
//		$min_err = 999.0;
//		$min_errs[$sp1] = 999.0;
		if (is_null(@$min_errs[$sp2])) {
			$min_errs[$sp2] = 999.0;
		}
		foreach ($sub_parts1 as $sp1_idx => $sp1) {
			if ((levenshtein($sp1, $sp2) <= 3)&&(treat_word($sp1[0])==treat_word($sp2[0]))) {
//				echo "<xmp>$sp1 $sp2 ". levenshtein($sp1, $sp2) . "</xmp>";
				$tmp_err = (float) levenshtein($sp1, $sp2) / (float) strlen($sp1);
//				$min_err = min($min_err, $tmp_err);
				$min_errs[$sp2] = min($min_errs[$sp2], $tmp_err);
//				echo "$sp1, $sp2, $min_err, $total_err, $matched<br/>";
			}
			else {
//				$min_err = 0.5;

				if (count($sub_parts1) != count($sub_parts2)) {
					$factor = count($sub_parts1) + count($sub_parts2) - ($sp1_idx + $sp2_idx + 1);
				}
				else {
					$factor = 1;
				}
/*
echo "<xmp>";
var_dump(array($sp2, $sp1, $factor, min($min_errs[$sp2], 1 / $factor)));
echo "</xmp>";
//*/
				$min_errs[$sp2] = min($min_errs[$sp2], 1 / $factor);
/*
echo "<xmp>";
var_dump(array($sub_parts1, $sub_parts2));
echo "</xmp>";
//*/
			}

		}
	}
		/*
		echo "<xmp>";
		var_dump($min_errs);
		echo "</xmp>";
		//*/
	foreach (array_unique($sub_parts2) as $sp2) {
		$total_err += $min_errs[$sp2];
	}

//	$score -= (($total_err>1.5)?1.5:$total_err);
	$score -= $total_err;
		/*
		echo "<xmp>";
		var_dump(array($matched, $penalty, $score, $score - $penalty));
		echo "</xmp>";
		//*/
	return $score - $penalty;
}

function detRank ($sciname, $sciname_clean) {
	$numParts = count(explode(" ", $sciname_clean));
	switch ($numParts) {
		case 2:
			return 'species';
			break;
		case 3:
			if (preg_match('/ var\.? /', $sciname)) {
				return 'variety';
			}
			else {
				return 'subspecies';
			}
			break;
		default:
			return 'unknown';
	}
}
echo "</div>";
echo "</body>";

?>
