<?php 
header("Access-Control-Allow-Origin: *");


include("../lib/dbconn.php");

$con = dbConn();

$rows = array();

$refineSearch = "";

if ((isset($_REQUEST['q'])) &&($_REQUEST['q'] != "")){
	
	// case-independent search of publication_details
	$refineSearch = " where lower(publication_details) like lower('%" . $_REQUEST['q'] . "%')";
}


else if ((isset($_REQUEST['id'])) &&($_REQUEST['id'] != "")){
	// search by id
	$refineSearch = " where publication_id = '" . $_REQUEST['id'] . "'";
}



$sql = <<<sql
select publication_id, publication_details from cofk_union_publication $refineSearch order by publication_details;
sql;


$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error $sql: " . pg_last_error($con) );}




	while ($row = pg_fetch_assoc($result)) {
		
		$rw = array();
		
		$rw['emloid'] = $row['publication_id'];
		$rw['label'] = $row['publication_details'];
		

		$rows[] = $rw;
	}
	
	
	
	print json_encode($rows);


closeConn($con);
?>