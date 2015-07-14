<?php

require_once "./include/functions.php";

$src_conf = read_src_conf();
$ep = (!empty($_REQUEST['ep']))?$_REQUEST['ep']:'http://localhost:8983/solr/taxa/select';


$query_url = $ep . "?q=*:*&rows=0&wt=json&facet=true&facet.field=source";
$f_jo = json_decode(file_get_contents($query_url));
$fieldCounts = array();
foreach ($f_jo->facet_counts->facet_fields->source as $idx => $fieldCount) {
	if ($idx&1) { // odd
		$fieldCounts[] = array(
			'source' => $current_field,
			'count' => $fieldCount,
			'name' => $src_conf[$current_field]['name'],
			'citation' => $src_conf[$current_field]['citation'],
			'url' => $src_conf[$current_field]['url'],
			'version' => $src_conf[$current_field]['version'],
		);
		$current_field = '';
	}
	else { // even
		$current_field = $fieldCount;
	}
}

echo json_encode($fieldCounts);

?>
