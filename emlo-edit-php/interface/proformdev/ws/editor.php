<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/dbconn.php");


$con = dbConn();

$rows = array();

$refineSearch = "";



$sql = <<<sql
select distinct change_user from pro_activity order by change_user;
sql;

$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error $sql: " . pg_last_error($con) );}

	while ($row = pg_fetch_assoc($result)) {
		
		$rw = array();
		
		$rw['id'] = $row['change_user'];
		
		

		$rows[] = $rw;
	}
	
	print json_encode($rows);

closeConn($con);

?>