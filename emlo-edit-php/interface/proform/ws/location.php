<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/dbconn.php");

$con = dbConn("ouls");

$rows = array();

$refineSearch = "";

if ((isset($_REQUEST['id'])) && ($_REQUEST['id'] != "")){
	$refineSearch = " where location_id =  {$_REQUEST['id']} ";

}


if ((isset($_REQUEST['q'])) && ($_REQUEST['q'] != "")){
	$refineSearch = " where location_name ~* '.*" . $_REQUEST['q'] . ".*'";

}



$sql = <<<sql
select location_id, location_name from cofk_union_location $refineSearch order by location_name;
sql;

$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error $sql: " . pg_last_error($con) );}




	while ($row = pg_fetch_assoc($result)) {
		
		$rw = array();
		
		$rw['emloid'] = $row['location_id'];
		$rw['label'] = $row['location_name'];
		

		$rows[] = $rw;
	}
	
	
	
	print json_encode($rows);


?>