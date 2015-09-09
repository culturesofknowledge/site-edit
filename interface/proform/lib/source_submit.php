<?php 
include("user.php");
include("source.php");


$con = dbConn();
startTransaction($con);

$result = "";
$messages = "";
$action = (isset($_REQUEST["source_action"])) ? $_REQUEST["source_action"] : "";


switch ($action){
	
	case "add":
		
		$messages = addTextualSource($con);
		$result	=  $messages;		
		break;
		
	case "edit":
		
		$messages  = deleteTextualSource($con); // delete existing record
		$messages .= addTextualSource($con);
		$result	=  $messages;
		
		break;
		
	case "delete":
		
		$messages = deleteTextualSource($con);
		$result = $messages;
		
		break;
		
	default:			
	
}


commitTransaction($con);
closeConn($con);

print $result;



?>