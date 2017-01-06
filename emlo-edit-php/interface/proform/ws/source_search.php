<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/dbconn.php");

$con = dbConn("");

$rows = array();
$refineSearch = "";

if ((isset($_REQUEST['q'])) && ($_REQUEST['q'] != "")){
	
	// case-independent search of institution_name
	$refineSearch = " where lower(abbreviation) like lower('%" . $_REQUEST['q'] . "%')";
	$refineSearch .= " or lower(author) like lower('%" . $_REQUEST['q'] . "%')";
	$refineSearch .= " or lower(title) like lower('%" . $_REQUEST['q'] . "%')";
	$refineSearch .= " or lower(publisher) like lower('%" . $_REQUEST['q'] . "%')";
	
	
} else if ((isset($_REQUEST['id'])) && ($_REQUEST['id'] != "")){
	$refineSearch = " where id =  {$_REQUEST['id']} ";
	
}

$sql = <<<sql
select * from pro_textual_source $refineSearch order by id;
sql;

$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error $sql: " . pg_last_error($con) );}

	while ($row = pg_fetch_assoc($result)) {	
		$rows[] = $row;
	}
	
	
	print json_encode($rows);

closeConn($con);	
	
	

?>