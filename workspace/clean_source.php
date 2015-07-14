<?php

require_once "../conf/solr_endpoint.php";
require_once "../include/functions.php";

$ep = trim(file_get_contents("../conf/solr_endpoint"), " /\r\n");

$query_url = $ep . "/select?q=*:*&rows=0&wt=json&facet=true&facet.field=source";
$f_jo = json_decode(file_get_contents($query_url));

$source = @$argv[1];
if ((!empty($source)) && preg_match('/[a-zA-Z\_]+/', $source) && ($source !== 'all')) {
	echo $ep . "/update?stream.body=" . urlencode('<delete><query>source:'.$source.'</query></delete>') . "&commit=true";
	exec("curl " . $ep . "/update?commit=true -H 'Content-Type: text/xml' --data-binary '<delete><query>source:$source</query></delete>'");
}
elseif ($source==='all') {
	echo ("curl " . $ep . "/update?commit=true -H 'Content-Type: text/xml' --data-binary '<delete><query>source:*</query></delete>'");
	exec("curl " . $ep . "/update?commit=true -H 'Content-Type: text/xml' --data-binary '<delete><query>source:*</query></delete>'");
}
else {
	echo "
Usage:
php clean_source.php {source_id|all}

Source Name(s):\n";

	foreach ($f_jo->facet_counts->facet_fields->source as $idx => $fieldCount) {
		if ($idx&1) { // odd
			echo $current_field . "\trow counts:" . $fieldCount . "\n";
			$current_field = '';
		}
		else { // even
			$current_field = $fieldCount;
		}
	}
	echo "\n";

}
?>
