<?php 

/*
 * FUNCTIONS
*/

function deleteActivityRecord($con){
	$messages = "";
	$activityId = $_POST["activity_id"];

	$arr_table_activity = array(
			"pro_activity" => "id",
			"pro_assertion"=> "assertion_id",
			"pro_location" => "activity_id",
			"pro_primary_person" => "activity_id",
			"pro_relationship"=> "activity_id",
			"pro_role_in_activity" => "activity_id"
	);

	foreach($arr_table_activity as $table => $activity) {
		$sql = "Delete from $table where $activity = '$activityId'";
		if (pg_query($con, $sql)) { 
			$messages .= "Activity $activityId has been deleted. ";
			
		} else {  die("Error deleting activity from $table using $sql : " . pg_last_error($con));}
	}
	
	return $messages;
}


function addActivityRecord($con){
	
	$messages = "";
	
	$activityId 	 =  addActivity($con); 
 	$messages 		.=	addPrimaryPerson($activityId, $con);
 	$messages 		.=	addTextualSources($activityId, $con); 
 	$messages 		.=	addlocation($activityId, $con); 
 	$messages 		.=	addRole($activityId, $con); 
 	$messages 		.=	addSecondaryParticipant($activityId, $con);
 	
	return $messages;
}

/*  UPDATE INDIVIDUAL TABLES */


/*
 * add activity to activity table
*/

function addActivity($con){
	$date = date('Y-m-d H:i:s');
	$sql2return = "";
	// free text form values

	// if this is an update to an activity add record with existing id 
	$activityId = (isset( $_POST["activity_id"])) ?  $_POST["activity_id"] : "";
	$idField = ($activityId != "") ? "id, " : "";
	$idValue = ($activityId != "") ? "'" . $activityId. "'," : "";
	
	
	$activityType = (isset( $_POST["activity_type"])) ?  $_POST["activity_type"] : "";
	$activityName = (isset( $_POST["activity_name"])) ?  $_POST["activity_name"] : "";
	$activityDescription = (isset( $_POST["activity_description"])) ?  $_POST["activity_description"] : "";
	$additionalNotes = (isset( $_POST["additional_notes"])) ?  $_POST["additional_notes"] : "";
	$notesUsed = (isset( $_POST["notes_used"])) ?  $_POST["notes_used"] : "";

	$dateType = (isset( $_POST["date_type"])) ?  $_POST["date_type"] : "";
	$dateFromYear = (isset( $_POST["date_from_year"])) ?  $_POST["date_from_year"] : "";
	$dateFromMonth = (isset( $_POST["date_from_month"])) ?  $_POST["date_from_month"] : "";
	$dateFromDay = (isset( $_POST["date_from_day"])) ?  $_POST["date_from_day"] : "";
	$dateFromUncertainty = (isset( $_POST["date_from_uncertainty"])) ?  $_POST["date_from_uncertainty"] : "";

	$dateToYear = (isset( $_POST["date_to_year"])) ?  $_POST["date_to_year"] : "";
	$dateToMonth = (isset( $_POST["date_to_month"])) ?  $_POST["date_to_month"] : "";
	$dateToDay = (isset( $_POST["date_to_day"])) ?  $_POST["date_to_day"] : "";
	$dateToUncertainty = (isset( $_POST["date_to_uncertainty"])) ?  $_POST["date_to_uncertainty"] : "";

	// escape free text form field values
	$activityType = pg_escape_string($con, $activityType);
	$activityName = pg_escape_string($con, $activityName);
	$activityDescription = pg_escape_string($con, $activityDescription);
	$additionalNotes  = pg_escape_string($con, $additionalNotes);
	$notesUsed = pg_escape_string($con, $notesUsed);

	
	$creationTimestamp = (isset( $_POST["creation_timestamp"])) ?  $_POST["creation_timestamp"] : $date; // see if date has been passed as variable
	$creationUser = (isset( $_POST["creation_user"])) ?  $_POST["creation_user"] : $_SESSION["username"];
	$changeTimestamp = $date;
	$changeUser = $_SESSION["username"];
	
	
	// construct sql statement
	$sql = <<<sql
INSERT INTO pro_activity ( $idField
		activity_type_id, activity_name, activity_description,
		date_type,
		date_from_year, date_from_month, date_from_day, date_from_uncertainty,
		date_to_year, date_to_month, date_to_day, date_to_uncertainty,
		notes_used, additional_notes, 
		
		creation_user, creation_timestamp,  change_user, change_timestamp
		
		
) VALUES
		( $idValue
			'$activityType', '$activityName', '$activityDescription',
		'$dateType',
		'$dateFromYear', '$dateFromMonth', '$dateFromDay', '$dateFromUncertainty',
		'$dateToYear', '$dateToMonth', '$dateToDay', '$dateToUncertainty',
		
		'$notesUsed', '$additionalNotes', '$creationUser', '$creationTimestamp', '$changeUser', '$changeTimestamp'

)
	 returning id;
sql;



	// insert record in database
	$result = pg_query($con, $sql);

	// return id for new record, to be used as foreign key in other tables
	$insertRow = pg_fetch_row($result); // get id of activity added
	$activityId = $insertRow[0];

	
	return $activityId;

}




function addPrimaryPerson($activityId, $con){

	$subjectId = $_POST["subject"];
	$sql = <<<sql
INSERT INTO pro_primary_person (activity_id, person_id) VALUES ('$activityId', '$subjectId');
sql;

	if (pg_query($con, $sql)) {
		
		return "Primary person added. ";
		
	} else {  die('Error adding primary person: ' . pg_last_error($con) .  print_r($_POST)  );}
}




/*
 * add textual sources
*
*/


function addTextualSources($activity_id, $con){
	$messages = "Textual source";
	$date = date('Y-m-d H:i:s');
	$counter = 0;
	do {

		$counter++;

		if (isset($_POST["source_id_" . $counter]  )){

			$source_id = pg_escape_string($con, $_POST["source_id_" . $counter]);
			$source_description = pg_escape_string($con, $_POST["source_details_" . $counter]);

			$sql = <<<sql
INSERT INTO pro_assertion
(assertion_type, assertion_id, source_id,  source_description, change_timestamp)
VALUES
('activity', '$activity_id', '$source_id',  '$source_description', '$date');

sql;
			// if source_id field is not null then add entry in database
			if ($_POST["source_id_" . $counter] != '') {
				if (pg_query($con, $sql)) { $messages .= "Textual source $source_id added.";} else {  die('Error adding textual source: ' . pg_last_error($con));}
				}

		} else {
		// stop the loop if the form field with counter isn't set
		if ($counter != 1) { $counter = -1; }	}
		} while ($counter != -1);
	return $messages . print_r($_POST);
}


function addLocation($activity_id, $con){
	$messages = "";
	$date = date('Y-m-d H:i:s');
	$counter = 0;
	do {
		$counter++;
		if (isset($_POST["location_id_" . $counter])){
			$location_id = pg_escape_string($con, $_POST["location_id_" . $counter]);
			$sql = <<<sql
INSERT INTO pro_location (location_id, activity_id, change_timestamp) 
VALUES ('$location_id', '$activity_id', '$date');
sql;
			if ($_POST["location_id_" . $counter] != '') {
				if (pg_query($con, $sql)) { $messages .= "Location $location_id added. "; } else {  die('Error adding location: ' . pg_last_error($con));}
			}
		} else {
			// stop the loop if the form field with counter isn't set
			if ($counter != 1){	$counter = -1;}	}

	} while ($counter != -1);
	return $messages;
}



function addRole($activity_id, $con){
	
	
	$formCounterRole = $_REQUEST['counterRole'];
	
	$messages = "";
	$date = date('Y-m-d H:i:s');
	$counterRole = 0;
	$subject_type =  "Person";

	do { // loop roles
			
		$counterRole++;
			
		if (isset($_POST["subject_role_" . $counterRole])) {
			$subject_id = (isset($_POST["subject"])) ? $_POST["subject"] : "";
			$subject_role_id = (isset($_POST["subject_role_{$counterRole}"])) ? $_POST["subject_role_{$counterRole}"] : "";

			
			$sqlRole = <<<sql
		INSERT INTO pro_role_in_activity (activity_id, entity_id, entity_type, role_id, change_timestamp)
		VALUES
		('$activity_id', '$subject_id', '$subject_type', '$subject_role_id', '$date');
sql;

			if ($subject_role_id != '') {
				if (pg_query($con, $sqlRole)) { $messages .= "Role $subject_role_id added. " ; } else {  die('Error adding subject role: ' . pg_last_error($con));}
			}
		
			addRelationship($activity_id, $subject_role_id, $subject_id, $subject_type, $counterRole, $con);

		} 
		
		
	} while ($counterRole <= $formCounterRole);  // loop roles


	
	
	
	
	
	
	
	
	
return $messages;
}




function addSecondaryParticipant($activity_id, $con){
	
	// add secondary participants
	$messages = "";
	$date = date('Y-m-d H:i:s');
	$formCounter = $_REQUEST['counterSP'];
	$counter = 0;
	do { // loop roles	
		$counter++;
			
		if (isset($_POST["sp_entity_type_" . $counter])) {
			$entity_type = (isset($_POST["sp_entity_type_" . $counter])) ? $_POST["sp_entity_type_" . $counter] : "";
			$entity_id = (isset($_POST["sp_entity_id_" . $counter])) ? $_POST["sp_entity_id_" . $counter] : "";
			$entity_role = (isset($_POST["sp_role_" . $counter])) ? $_POST["sp_role_" . $counter] : "";
	
				
			$sql = <<<sql
		INSERT INTO pro_role_in_activity (activity_id, entity_id, entity_type, role_id, change_timestamp)
		VALUES
		('$activity_id', '$entity_id', '$entity_type', '$entity_role', '$date');
sql;
	
			if ($entity_type != '') {
				if (pg_query($con, $sql)) { $messages .= "Secondary participant $entity_role added. " ; } else {  die('Error adding secondary participant: ' . pg_last_error($con));}
			}
		}
	} while ($counter <= $formCounter);  // loop roles
}



function addRelationship($activity_id, $subject_role_id, $subject_id, $subject_type, $counterRole, $con){
	$messages = "";
	$date = date('Y-m-d H:i:s');
	$counterRel = 0; // reset relationship counter

	do { 
		$counterRel++;
		// loop relationships
		$relationship = (isset($_POST["relationship_{$counterRole}_{$counterRel}"])) ? $_POST["relationship_{$counterRole}_{$counterRel}"] : "";

		if ($relationship != ""){
			$object_id = (isset($_POST["object_{$counterRole}_{$counterRel}"])) ? pg_escape_string($con, $_POST["object_{$counterRole}_{$counterRel}"]) : "" ;
			$object_type =  (isset($_POST["object_type_{$counterRole}_{$counterRel}"])) ? $_POST["object_type_{$counterRole}_{$counterRel}"] : "";
			$object_role = (isset($_POST["object_role_{$counterRole}_{$counterRel}"])) ? $_POST["object_role_{$counterRole}_{$counterRel}"] : "";
		
			// sql relationship
			$sqlRel = <<<sql
			INSERT INTO pro_relationship
			(activity_id, subject_id, subject_type, subject_role_id, relationship_id, object_id, object_type, object_role_id, change_timestamp)
			VALUES
			('$activity_id', '$subject_id', '$subject_type', '$subject_role_id','$relationship', '$object_id', '$object_type', '$object_role', '$date');
sql;


			if (pg_query($con, $sqlRel)) { $messages .= "Relationship $relationship added. "; } else {  die('Error adding relationship: ' . pg_last_error($con));}
		
			// add records to pro_role_in_activity table for the objects in the relationship that have a role
			if ($object_role != ""){
				addRoleObject($object_role, $object_type, $object_id, $activity_id, $con, $date);
				}
		} else {
			if ($counterRel != 1){	$counterRel = -1;}} // end loop relationships		
	} while ($counterRel != -1);  // loop relationships
return $messages;
}



function addRoleObject($object_role, $object_type, $object_id, $activity_id, $con, $date){

$messages = "";
			$sql = <<<sql

INSERT INTO pro_role_in_activity
(entity_type, entity_id,  role_id, change_timestamp, activity_id)
VALUES
('$object_type', '$object_id', '$object_role', '$date', '$activity_id');
sql;

	if (pg_query($con, $sql)) { $messages .= "Role $object_role added."; } else {  die('Error adding object role: ' . pg_last_error($con));}

	return $messages;
}













?>