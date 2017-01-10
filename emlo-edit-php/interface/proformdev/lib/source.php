<?php 



function addTextualSource($con){

	$date = date('Y-m-d H:i:s');
	$messages = "";


	$arrFields = array(
			"id", "author", "title", "chapterArticleTitle",
			"volumeSeriesNumber","issueNumber", "pageNumber",
			"editor",  "placePublication",
			"datePublication","urlResource",
			"abbreviation","fullBibliographicDetails", "edition", "reprintFacsimile", "repository",
			"creation_timestamp", "creation_user", "change_timestamp", "change_user"

	);

	$values = "";
	$fields = "";
	$separator = ", ";

	foreach($arrFields as $key => $field){

		switch ($field){
				
			case "id":
				$f = "";
				$value = "";
				break;

			case "creation_timestamp":
				$value = (isset( $_REQUEST["creation_timestamp"])) ?  $_REQUEST["creation_timestamp"] : $date;
				$value = ($value == "") ? $date : $value;
				
				
				$value = "'$value'$separator";
				$f = '"' . $field . '"' . $separator;
				break;

			case "creation_user":
				$value = ((isset( $_REQUEST["creation_user"])) && ($_REQUEST["creation_user"] != "")) ?  $_REQUEST["creation_user"] : $_SESSION["username"];
				$value = "'$value'$separator";
				$f = '"' . $field . '"' . $separator;
				break;
					
			case "change_timestamp":
				$value = $date;
				$value = "'$value'$separator";
				$f = '"' . $field . '"' . $separator;
				break;

			case "change_user":
				$value = $_SESSION["username"];
				$separator = "";
				$value = "'$value'";
				$f = '"' . $field . '"';
				break;

			default:
				$value = $_POST[$field];
				$value = "'$value'$separator";
				$f = '"' . $field . '"' . $separator;
		}

		$fields .= $f;
		$values .=  $value;

	}

	$sql = "INSERT INTO pro_textual_source ($fields) values ( $values);";


	if (pg_query($con, $sql)) {
		
		$messages .= "The textual source has been added. ";
		
	} else {  
		
		$messages .= "Error adding textual source using ' $sql ' : " . pg_last_error($con);
	}
	return $messages;
}



function deleteTextualSource($con){
	$messages = "";
	$id = $_REQUEST["source_id"];

	$sql = "Delete from pro_textual_source where id = '$id'";
	if (pg_query($con, $sql)) {
		$messages .= "The textual source has been deleted. ";

	} else {  $messages .="Error deleting textual source using ' $sql ' : " . pg_last_error($con);}

	return $messages;

}



?>