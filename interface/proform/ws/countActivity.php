<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/config.php");


$con=pg_connect("host=$dbhost port=$dbport dbname=$dbnametest user=$dbuser password=$dbpassword");

// Check connection
if (pg_last_error()) {
	echo "Failed to connect to database: " . pg_last_error();
	exit();}

$queryActivityId 	= isset($_GET['id']) ? $_GET['id'] : ""; // activity id as GET parameter
$queryActivityType 	= isset($_GET['filterActivity']) ? $_GET['filterActivity'] : "";
$queryLocationId 	= isset($_GET['filterLocation']) ? $_GET['filterLocation'] : "";
$queryPersonId 		= isset($_GET['filterPerson']) ? $_GET['filterPerson'] : "";
$queryDateType 		= isset($_GET['filterDateType']) ? $_GET['filterDateType'] : "";
$queryYearFrom 		= isset($_GET['filterYearFrom']) ? $_GET['filterYearFrom'] : "";
$queryYearTo 		= isset($_GET['filterYearTo']) ? $_GET['filterYearTo'] : "";
$queryEditor 		= isset($_GET['filterEditor']) ? $_GET['filterEditor'] : "";

$qualifyQuery 		 = "";
$qualifyQuery 		.= ($queryActivityType != "") ? "  and activity_type_id = '$queryActivityType'  ": "";
$qualifyQuery 		.= ($queryPersonId != "") ? " and a.id = b.activity_id and b.person_id = '$queryPersonId' " : "";
$qualifyQuery 		.= ($queryLocationId != "") ? " and a.id = c.activity_id and c.location_id = '$queryLocationId' " : "";

$qualifyQuery 		.= ($queryEditor != "") ? " and a.change_user = '$queryEditor' " : "";


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
* 			search for records where date_from_year >= queryDateFromYear and date_to_year <= queryDateToYear
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

/*
 * 
 * 
 * $qualifyQuery 		.= ($queryDateType != "") ? " and a.date_type = '$queryDateType' " : "";
switch ($queryDateType){

	case "After":
		$qualifyQuery .= ($queryYearFrom != "") ? "  and a.date_from_year != ''  and CAST(a.date_from_year as integer) >= $queryYearFrom " : "";
		break;

	case "Before":
		$qualifyQuery .= ($queryYearFrom != "") ? " and a.date_from_year != ''  and CAST(a.date_from_year as integer) <= '$queryYearFrom' " : "";
		break;
			
	case "Duration":
		$qualifyQuery .= ($queryYearFrom != "") ? "  and a.date_from_year != ''  and CAST(a.date_from_year as integer) = '$queryYearFrom' " : "";
		$qualifyQuery .= ($queryYearTo != "") ? "  and a.date_to_year != '' and CAST(a.date_to_year as integer) = '$queryYearTo' " : "";
		break;

	case "Between":
		$qualifyQuery .= ($queryYearFrom != "") ? "  and a.date_from_year != '' and CAST(a.date_from_year as integer) >= '$queryYearFrom' " : "";
		$qualifyQuery .= ($queryYearTo != "") ? " and a.date_to_year != ''  and CAST(a.date_to_year as integer) <= '$queryYearTo' " : "";
		break;
			
	default:
}
*/


if ($queryActivityId != "") { $qualifyQuery = " and a.id = $queryActivityId  ";}

$tables = "";
$tables .= ($queryPersonId != "") ? "  pro_primary_person as b, " : " ";
$tables .= ($queryLocationId != "") ? " pro_location as c, " : "";
$tables .= " pro_activity as a ";

$sql = <<<sql
select count(a.id) from $tables  where 1=1 $qualifyQuery ;
sql;

// ACTIVITY QUERY
$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error: " . pg_last_error($con));}

$rows = array();


// ITERATE THROUGH ACTIVITY RESULTS
while ($row = pg_fetch_assoc($result)) {
	$rows[] = $row;
}


print json_encode($rows);

?>