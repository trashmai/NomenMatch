<?php

function read_src_conf ($file = "./source-data/sources.csv") {
	$lines = file($file);
	$ret = array();
	foreach ($lines as $line) {
		$line = trim($line, " \r\n,");
		$parts = explode("\t", $line);
		$ret[$parts[0]]['id'] = trim($parts[0], " \r\n,");
		$ret[$parts[0]]['name'] = trim($parts[1], " \r\n,");
		$ret[$parts[0]]['url_base'] = trim($parts[2], " \r\n,");
		$ret[$parts[0]]['citation'] = trim($parts[3], " \r\n,");
		$ret[$parts[0]]['url'] = trim($parts[4], " \r\n,");
		$ret[$parts[0]]['version'] = trim($parts[5], " \r\n,");
	}
	return $ret;
}


?>
