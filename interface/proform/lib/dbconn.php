<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("config.php");


function dbConn($type=""){
	
	global $dbhost, $dbport, $dbuser, $dbpassword, $dbnametest, $dbnameouls;
	
	$con = "";
	
	switch ($type){
		
		case "test":
			$con = pg_connect( "host=$dbhost port=$dbport dbname=$dbnametest user=$dbuser password=$dbpassword" );	
			break;
			
		case "ouls":			
			$con = pg_connect( "host=$dbhost port=$dbport dbname=$dbnameouls user=$dbuser password=$dbpassword" );
			break;
			
		default:
			$con = pg_connect( "host=$dbhost port=$dbport dbname=$dbnametest user=$dbuser password=$dbpassword" );
			}	
		
		
			// Check connection
	if (pg_last_error()) {
			print "Failed to connect to database: " . pg_last_error ( $con );  exit();}	
	
	return $con;
}


function startTransaction($con){
	
	$sql = "START TRANSACTION;";
	if (pg_query($con, $sql)) {} else {  die('Error: ' . pg_last_error($con));}
}


function commitTransaction($con){
	
	// commit inserts if no errors occurred
	$sql = "COMMIT;";
	if (pg_query($con, $sql)) {} else {  die('Error with commit: ' . pg_last_error($con));}
}


function closeConn($con){
	// close db connection
	pg_close($con);
}


?>