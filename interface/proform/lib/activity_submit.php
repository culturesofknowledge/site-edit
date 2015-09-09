<?php 
include("user.php");
include("activity.php");


$con = dbConn();
startTransaction($con);

$result = "";
$action = (isset($_POST["activity_action"])) ? $_POST["activity_action"] : "";


switch ($action){

	case "add":

		$messages = addActivityRecord($con);
		$result	= "The activity has been added. " . $messages;
		break;

	case "edit":

		$messages  = deleteActivityRecord($con); // delete existing record
		$messages .= addActivityRecord($con);
		$result	= "The activity has been updated. " . $messages;

		break;

	case "delete":

		$messages = deleteActivityRecord($con);
		$result = "The activity has been deleted." . $messages;

		break;

	default:
		
		$result = "An action is required";
		

}

commitTransaction($con);
closeConn($con);

echo  $result;




?>