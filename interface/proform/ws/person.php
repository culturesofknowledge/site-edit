<?php 
header("Access-Control-Allow-Origin: *");

include("../lib/config.php");




$con=pg_connect("host=$dbhost port=$dbport dbname=$dbnameouls user=$dbuser password=$dbpassword");

// Check connection
if (pg_last_error()) { 	echo "Failed to connect to database: " . pg_last_error();	exit();}

$rows = array();


$refineSearch = "where is_organisation = '' ";

if ((isset($_REQUEST['id'])) && ($_REQUEST['id'] != "")){
	$refineSearch .= " and iperson_id =  {$_REQUEST['id']} ";
}


if ((isset($_REQUEST['q'])) && ($_REQUEST['q'] != "")){
	$refineSearch .= " and foaf_name ~* '.*" . $_REQUEST['q'] . ".*' ";

}


$sql = <<<sql
select iperson_id, foaf_name, date_of_birth_year, date_of_death_year, flourished_year from  cofk_union_person $refineSearch;
sql;

$result = pg_query($con, $sql);

if(!$result) { 	die("SQL Error $sql   " . pg_last_error($con) );}




	while ($row = pg_fetch_assoc($result)) {
		
		$rw = array();
		
		$rw['name'] = $row['foaf_name'];
		$rw['date'] = " (b: " . $row['date_of_birth_year'] . " d: " . $row['date_of_death_year'] . " fl: " . $row['flourished_year'] . " )"; 
		$rw['emloid'] = $row['iperson_id'];
		

		$rows[] = $rw;
	}
	
	
	
	print json_encode($rows);


?>