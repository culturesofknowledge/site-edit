<?php 

$config = "emlo"; // local or emlo

$dbhost = "";
$dbport = "";
$dbuser = "";
$dbpassword = "";
$dbnametest = "";
$dbnameouls = "";

switch($config){
	case "local":
		$dbhost = "localhost";
		$dbport = "5432";
		$dbuser = "postgres";
		$dbpassword = "password";
		$dbnametest = "ouls";
		$dbnameouls = "ouls";
		
		break;
		
	case "emlo":
		$dbhost = "localhost";
		$dbport = "5432";
		$dbuser = "postgres";
		$dbpassword = "";
		$dbnametest = "ouls";
		$dbnameouls = "ouls";
		
		break;
			
}

$urlEMLO = "https://emlo-edit.bodleian.ox.ac.uk/interface/union.php";

?>