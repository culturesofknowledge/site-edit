<?php 
session_start();


include("dbconn.php");

global $urlEMLO, $config;


$sessionId = (isset($_SESSION["session_token"])) ? $_SESSION["session_token"] : "";

// need to identify if the user is logged in - if not redirect to login page at 
// https://emlo-edit.bodleian.ox.ac.uk/interface/union.php

$con = dbConn("ouls");

$sql = "Select session_id from cofk_sessions where session_code = '" . $sessionId . "' order by session_id desc limit 1";

$result = pg_query($con, $sql);
if(!$result) { 	die("SQL Error $sql: " . pg_last_error($con) );}

$countRows = pg_num_rows($result);

switch ($countRows) {

	case "0" :
		// user not logged in so redirect
		
		header("Location:" . $urlEMLO);
		exit;
	
		break;
	
	default:
	// user logged in
		
		break;
}




?>