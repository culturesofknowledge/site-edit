<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/config.php");


$con=pg_connect("host=$dbhost port=$dbport dbname=$dbnametest user=$dbuser password=$dbpassword");

// Check connection
if (pg_last_error()) {
	echo "Failed to connect to database: " . pg_last_error();
	exit();
}


$limit				= isset($_GET['limit']) ? $_GET['limit'] : "10"; // number of records to return
$offset				= isset($_GET['offset']) ? $_GET['offset'] : "0"; // number of records to return
$queryActivityId 	= isset($_GET['id']) ? $_GET['id'] : ""; // activity id as GET parameter
$queryActivityType 	= isset($_GET['filterActivity']) ? $_GET['filterActivity'] : ""; 
$queryLocationId 	= isset($_GET['filterLocation']) ? $_GET['filterLocation'] : "";
$queryPersonId 		= isset($_GET['filterPerson']) ? $_GET['filterPerson'] : "";
$queryDateType 		= isset($_GET['filterDateType']) ? $_GET['filterDateType'] : "";
$queryYearFrom 		= isset($_GET['filterYearFrom']) ? $_GET['filterYearFrom'] : "";
$queryYearTo 		= isset($_GET['filterYearTo']) ? $_GET['filterYearTo'] : "";

$queryEditor 		= isset($_GET['filterEditor']) ? $_GET['filterEditor'] : "";



$qualifyQuery  = "";
$qualifyQuery .= ($queryActivityType != "") ? "  and activity_type_id = '$queryActivityType'  ": "";	
$qualifyQuery .= ($queryPersonId != "") ? " and a.id = b.activity_id and b.person_id = '$queryPersonId' " : "";
$qualifyQuery .= ($queryLocationId != "") ? " and a.id = c.activity_id and c.location_id = '$queryLocationId' " : "";

$qualifyQuery .= ($queryEditor != "") ? " and a.change_user = '$queryEditor' " : "";




/*
 * The records can include a date type value = after, before, duration or between
 * 
 * The search function includes a date type filter with the options of after, before, duration and between.
 * 
 * The search function will not simply search the date type field for a matching value
 * 
 * if filter = after then
 * 			search for records where date_from_year >= queryDateFromYear
 * 
 * if filter = before then
 * 			search for records where date_from_year <= queryDateFromYear
 * 
 * if filter = duration then
 * 			search for records where date_from_year = queryDateFromYear and date_to_year = queryDateToYear and date_type = 'Duration'
 * 
 * if filter = between then
 * 			search for records where date_from_year >= queryDateFromYear and date_to_year <= queryDateToYear or date_to_year = ''
 * 
 */

switch ($queryDateType){
	
	case "After":		
		$qualifyQuery .= ($queryYearFrom != "") ? "  and a.date_from_year != ''  and CAST(a.date_from_year as integer) >= $queryYearFrom " : "";
		break;
		
	case "Before":
		$qualifyQuery .= ($queryYearFrom != "") ? " and a.date_from_year != ''  and CAST(a.date_from_year as integer) <= '$queryYearFrom' " : "";
		break;
			
	case "Duration":
		$qualifyQuery .= " and a.date_type = 'Duration' ";
		$qualifyQuery .= ($queryYearFrom != "") ? " and a.date_from_year != ''  and CAST(a.date_from_year as integer) = '$queryYearFrom' ": "";
		$qualifyQuery .= ($queryYearTo != "") ?  "  and a.date_to_year != '' and CAST(a.date_to_year as integer) = '$queryYearTo' " : "";
		break;
				
	case "Between":
		$qualifyQuery .= ($queryYearFrom != "") ? "  and a.date_from_year != '' and CAST(a.date_from_year as integer) >= '$queryYearFrom' ": "";
		$qualifyQuery .= ($queryYearTo != "") ? " and a.date_to_year != ''  and CAST(a.date_to_year as integer) <= '$queryYearTo' ": "";
		break;
			
	default:	
		$qualifyQuery .= ($queryYearFrom != "") ?"  and a.date_from_year != '' and CAST(a.date_from_year as integer) >= '$queryYearFrom' ": "";
		$qualifyQuery .= ($queryYearTo != "") ? " and ((a.date_to_year != ''  and CAST(a.date_to_year as integer) <= '$queryYearTo') or (a.date_to_year = '')) " : "";
		
}




if ($queryActivityId != "") { $qualifyQuery = " and a.id = $queryActivityId  ";}

$tables = "";
$tables .= ($queryPersonId != "") ? "  pro_primary_person as b, " : " ";
$tables .= ($queryLocationId != "") ? " pro_location as c, " : "";
$tables .= " pro_activity as a ";

// , pro_primary_person as b, pro_location as c where 1=1 $qualifyQuery
$sql = <<<sql
select a.* from $tables  where 1=1 $qualifyQuery order by a.activity_type_id, a.activity_name,  a.change_timestamp, a.creation_timestamp  limit $limit offset $offset ;
sql;

// get value of event_label that is equal to the combined_spreadsheet_row value in pro_activity_relation
// use this to retrieve a list of identifiers for related activities that share the same filename and spreadsheet_row value
if ($queryActivityId != "") {
	$sqlRelatedActivity = <<<sql

SELECT d.id as related_activity_id, d.activity_type_id, d.activity_name, d.date_from_year, d.date_from_month, d.date_from_day FROM  pro_activity_relation b

INNER JOIN pro_activity a 
ON CAST(a.event_label AS INTEGER) = b.combined_spreadsheet_row AND a.id = $queryActivityId 

INNER JOIN pro_activity_relation c
ON b.filename = c.filename
AND b.spreadsheet_row = c.spreadsheet_row

JOIN pro_activity d 
ON c.combined_spreadsheet_row = CAST(d.event_label AS INTEGER)
WHERE d.id != $queryActivityId 
sql;
} else {
	$sqlRelatedActivity = "";	
}


//select * from pro_role_in_activity where activity_id = '%s' ;
$sqlRole = <<<sql
select a.iperson_id, a.foaf_name as entity_name, b.* 
from 
cofk_union_person as a, pro_role_in_activity as b
where 
b.activity_id = '%s' and 
a.iperson_id = CAST(b.entity_id as integer);
sql;

// select * from pro_assertion where assertion_type = 'activity' and assertion_id = '%s';
$sqlAssertion = <<<sql

select  a.*,  b.* from 
pro_assertion as a, pro_textual_source as b 
where 
a.assertion_type = 'activity' 
and cast(a.source_id as integer) = b.id
and a.assertion_id = '%s'

sql;

$sqlRelationship = <<<sql
select * from pro_relationship where activity_id = '%s';
sql;


//select * from pro_location where activity_id = '%s';
$sqlLocation = <<<sql
select a.location_id, a.location_name 
		from cofk_union_location as a, pro_location as b
		where b.activity_id = '%s' and a.location_id = CAST(b.location_id as integer);
sql;

// select * from pro_primary_person where activity_id = '%s';
$sqlPrimaryPerson = <<<sql
		select a.iperson_id, b.person_id, a.foaf_name 
		from cofk_union_person as a, pro_primary_person as b
		where b.activity_id = '%s' and a.iperson_id = CAST(b.person_id as integer);
sql;

$term = (isset($_REQUEST["term"])) ? $_REQUEST["term"] : "";
$person = (isset($_REQUEST["subject"])) ? $_REQUEST["subject"] : "";

// ACTIVITY QUERY
$result = pg_query($con, $sql);

if(!$result) { die("SQL Error: " . pg_last_error($con));}

$rows = array();

// ITERATE THROUGH ACTIVITY RESULTS
while ($row = pg_fetch_assoc($result)) {
	$activity_id = $row["id"];
	$activity_label = "{$row['activity_type_id']}  {$row['activity_name']}  {$row['activity_description']}";
	$row['text'] = $activity_label;
	$row['role_in_activity'] = getRows($activity_id, $con, $sqlRole);
	$row['relationship'] = getRows($activity_id, $con, $sqlRelationship);
	$row['location'] =  getRows($activity_id, $con, $sqlLocation);
	$row['assertion'] = getRows($activity_id, $con, $sqlAssertion);
	$row['primary_person'] = getRows($activity_id, $con, $sqlPrimaryPerson);
	$row['related_activity'] = ($sqlRelatedActivity != '') ? getRows("", $con, $sqlRelatedActivity) : "";
	$rows[] = $row;
		
}

print json_encode($rows);




function getRows($activity_id, $con, $sqlvar){
	
	$rows = array();
	
	$sql = ($activity_id != '') ? sprintf($sqlvar, $activity_id): $sqlvar;

	
	$result = pg_query($con, $sql);
	
	if(!$result) {
		die("SQL Error $sqlvar: " . pg_last_error($con));
	}
	
	while ($row = pg_fetch_assoc($result)) {
		
		$rows[] = $row;
	}
	
	return $rows;
}




?>