<?php

require_once "../include/cleanname.inc";
require_once "../include/treat_word.inc";

$ep = trim(file_get_contents("../conf/solr_endpoint"), " /\r\n");

if (empty($argv[1])) {
	echo "
Usage:
php importChecklistToSolr.php {/path/to/source_data.csv} [source_id]
if [source_id] is empty, \"source_data\" will be used as the source_id  

Example(s):
";
	exec("ls -h ./data/*.csv", $o);
	echo implode("\n", $o) . "\n\n";
	return;
}




function submitJson($dat, $tmp_path="./tmp/save_storage.json") {
	global $ep;
	static $total = 0;
	if (!empty($dat)) {
		$numString = ($total + 1) . "-" . ($total + count($dat));
		echo "Submitting $numString ...\n";
		$json = json_encode($dat);

		if (!empty($tmp_path)) {
			$json_path = $tmp_path;
		}
		else {
			$json_path = "./tmp/json/test_solr_".$numString.".json";
		}

		file_put_contents($json_path, $json);
		$jf = "curl '" . $ep . "/update/json?commit=true' --data-binary @".$json_path." -H 'Content-type:application/json'";
		$response = exec($jf, $out);
//		var_dump($out);
		$total+=count($dat);
	}
}


$counter = 0;
$ret = array();

$file = (empty($argv[1]))?"":$argv[1];
$fp = fopen($file, "r");

$source = (empty($argv[2]))?basename($file, '.csv'):$argv[2];

while ($vals = fgetcsv($fp, 0, "\t" )) {
	$vals = array_map("trim", $vals, array_fill(0, count($vals), "\r\n\t ,.'\""));

	if (empty($vals[0]) && empty($vals[1])) {
		$vals[0] = "sci_hash_" . md5($vals[2]);
		$vals[1] = $vals[0];
	}
	else if (empty($vals[0])) {
		$vals[0] = $vals[1] . '-s5-v' . md5($vals[2]);
	}


	$rec = array();
	$rec['id'] = $source . '-' . $vals[0];
	$rec['source'] = $source;

	$rec['url_id'] = $vals[3];
	if (!empty($vals[4])) {
		$rec['a_url_id'] = $vals[4];
	}
	else {
		$rec['a_url_id'] = $vals[3];
	}

	$rec['family'] = array_shift(explode(" ", $vals[5]));
	$rec['order'] = array_shift(explode(" ", $vals[6]));
	$rec['class'] = array_shift(explode(" ", $vals[7]));
	$rec['phylum'] = array_shift(explode(" ", $vals[8]));
	$rec['kingdom'] = array_shift(explode(" ", $vals[9]));

	$rec['sound_family'] = treat_word($rec['family']);
	$rec['sound_order'] = treat_word($rec['order']);
	$rec['sound_class'] = treat_word($rec['class']);
	$rec['sound_phylum'] = treat_word($rec['phylum']);
	$rec['sound_kingdom'] = treat_word($rec['kingdom']);


	$rec['namecode'] = $vals[0];
//	$rec['taibnet_url'] = "http://taibnet.sinica.edu.tw/chi/taibnet_species_detail.php?name_code=" . $vals[0];
	if (!empty($vals[1])) {
		$rec['accepted_namecode'] = $vals[1];
	}
	else {
		$rec['accepted_namecode'] = $vals[0];
	}
	$rec['original_name'] = $vals[2];
	$rec['canonical_name'] = canonical_form($vals[2], true);
	//if ($rec['canonical_name'] == 'Bombyx pernyi') {
		//var_dump($rec);
	//}
	$rec['sound_name'] = treat_word($rec['canonical_name']);
	$frags = explode(" ", $rec['canonical_name']);
	$rec['latin_part_a'] = $frags[0];
	$rec['genus'] = $frags[0];
	$rec['sound_part_a'] = treat_word($frags[0]);
	$rec['sound_genus'] = $frags[0];
	$rec['sound_part_a_strip_ending'] = treat_word($frags[0], true);

	$rec['nameSpell'][] = $frags[0];

	if (!empty($frags[1])) {
		$rec['latin_part_bc'][] = $frags[1];
		$rec['nameSpell'][] = $frags[1];
		$rec['nameSpell'][] = $frags[0] . " " . $frags[1];
		$rec['sound_part_bc'][] = treat_word($frags[1]);
		$rec['sound_part_bc_strip_ending'][] = treat_word($frags[1], true);
	}
	else {
		continue;
	}

	if (!empty($frags[2])) {
		$rec['latin_part_bc'][] = $frags[2];
		$rec['nameSpell'][] = $frags[2];
		$rec['nameSpell'][] = $frags[1] . " " . $frags[2];
		$rec['nameSpell'][] = $frags[0] . " " . $frags[2];
		$rec['nameSpell'][] = $frags[0] . " " . $frags[1] . " " . $frags[2];
		$rec['sound_part_bc'][] = treat_word($frags[2]);
		$rec['sound_part_bc_strip_ending'][] = treat_word($frags[2], true);
	}
	
	$ret[] = $rec;

	if ($counter % 1000 == 999) {
		submitJson($ret);
		$ret = array();
	}
	$counter++;
}

if (!empty($ret)) {
	submitJson($ret);
}


?>
