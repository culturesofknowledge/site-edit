<?php

//error_reporting(E_ALL & ~E_STRICT & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
//error_reporting(E_ALL & ~E_STRICT & ~E_DEPRECATED);
//ini_set("display_errors", 1);

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Union Catalogue Editing Interface' );
define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

require_once('includes/wts_mongodb.inc');
define( 'CONSTANT_DATABASE_TYPE', 'live' );

require_once "common_components.php";
require_once "proj_components.php";
require_once "includes/mongodb_document_structure.php";

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

#-------------------------------------------------------------------------

$connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('uploader-processed', false, false, false, false);

$callback = function ($msg) {

	$data = json_decode( $msg->body );
	// print_r( $data );

	if( !$data->error ) {
		ingest($data->foldername);
	}
	else {
		print( "Something *Failed*\n" );
		print( "Error(s):\n" . $data->error );
	}

	exit();

	//return false; // stop waiting (doesn't seem to work, file continues to hang)
};

$channel->basic_consume('uploader-processed', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
	$channel->wait();
}

$channel->close();
$connection->close();


function ingest( $Ingestname )
{
	global $database;
	global $db_connection;
	global $cofk;
	global $upload_id;
	global $uploadImpvalue;

	$db_connection = new DBQuery ('postgres');
	$cofk = new Project($db_connection);

	echo $cofk->get_datetime_now_in_words( $include_seconds = TRUE );
	echo NEWLINE;

	$cofk->db_run_query('BEGIN TRANSACTION');

	$upload_id = NULL;

	if (!$upload_id) {

		$statement = "select nextval('cofk_collect_upload_id_seq'::regclass)";
		$upload_id = $cofk->db_select_one_value($statement);
	}


	function object_to_array($object)
	{
		if (is_object($object)) {
			return array_map(__FUNCTION__, get_object_vars($object));
		} else if (is_array($object)) {
			return array_map(__FUNCTION__, $object);
		} else {
			return $object;
		}
	}

#------------------------
# Get contributor details
#------------------------
	$uploadImpvalue = $Ingestname;

	$filter = [
		'upload_id' => $Ingestname
	];
	$options = [
		'projection' => ['_id' => 0],
	];

	$query = new MongoDB\Driver\Query($filter, $options);
	$mongo_cursor = $database->executeQuery('emlo-edit.upload', $query);

	$uploads = $mongo_cursor->toArray()[0];
	$uploads = object_to_array($uploads);

	#-----------------------------------
	# Record that the upload has started
	#-----------------------------------
	$uploads['upload_id'] = $upload_id;
	$uploads['upload_description'] = $Ingestname . " " . date("j M Y G:i");    // Sat Mar 10 17:16:18 MST 2001
	// Convert timestamp
	$uploads['upload_timestamp'] = date('Y-m-d H:i:s', $uploads['upload_timestamp']['milliseconds']/1000);

	print( "Upload: ");
	print_r($uploads);

	pg_insert(
		$db_connection->connection->connection,
		'cofk_collect_upload',
		$uploads, PGSQL_DML_EXEC
	);

	#------------------------------------
	# Read and validate each file in turn
	#------------------------------------

	$both_tables = get_file_to_tables_lookup();
	$files = array(
		CSV_FILE_PERSON
	, CSV_FILE_LOCATION
	, CSV_FILE_INSTITUTION
	, CSV_FILE_WORK
	, CSV_FILE_MANIFESTATION
	, CSV_FILE_ADDRESSEE
	, CSV_FILE_AUTHOR
//		,CSV_FILE_OCCUPATION_OF_PERSON
	, CSV_FILE_PERSON_MENTIONED
//		,CSV_FILE_PLACE_MENTIONED
	, CSV_FILE_LANGUAGE_OF_WORK
//		,CSV_FILE_SUBJECT_OF_WORK
	, CSV_FILE_WORK_RESOURCE
//		,CSV_FILE_PERSON_RESOURCE
//		,CSV_FILE_LOCATION_RESOURCE
//		,CSV_FILE_INSTITUTION_RESOURCE
//	, CSV_FILE_IMAGE_OF_MANIF
	);

	foreach ($files as $file_number) {

		$postgres_table = $both_tables[$file_number]['postgres'];
		$openoffice_table = $both_tables[$file_number]['openoffice'];
		$id_field = null;
		if( $both_tables[$file_number]['id'] ) {
			$id_field = $both_tables[$file_number]['id'];
		}

		$mongoname = "collect_" . $openoffice_table;
		read_one_uploaded_file($mongoname, $openoffice_table, $postgres_table, $id_field);
		echo "\n\n";
	}

# get the total number of works loaded
	$statement = "select count(upload_id) from cofk_collect_work where upload_id = $upload_id";
	$num_works_uploaded = $cofk->db_select_one_value($statement);

	if ($num_works_uploaded == 0) {
		$statement = 'update '
			. ' cofk_collect_upload'
			. ' set upload_status = ' . CONTRIB_STATUS_REJECTED
			. " where upload_id = $upload_id";
		$cofk->db_run_query($statement);
		html::div_start('class="warning"');
		echo 'You did not upload any works in your contribution. The Works file was empty.'
			. ' This means that your contribution cannot be accepted.';
		html::new_paragraph();
		echo 'Please try again, ensuring that you are exporting from the correct data source; you may'
			. ' need to re-register your database with OpenOffice.'
			. ' Instructions for registering an OpenOffice data source are given in Step 1'
			. " of the 'Upload your contribution' form within the data collection tool.";
		html::div_end();
		html::new_paragraph();
		return;
	} else {  # write an easily-displayed summary of everything to do with the work: authors, manifestations, etc.

		//$funcname = $cofk->proj_database_function_name( 'write_work_summary', $include_collection_code = TRUE );
		$funcname = 'dbf_cofk_collect_write_work_summary';
		$statement = 'select iwork_id from '
			. ' cofk_collect_work'
			. " where upload_id = $upload_id"
			. ' order by iwork_id';
		$work_ids = $cofk->db_select_into_array($statement);
		foreach ($work_ids as $row) {
			extract($row, EXTR_OVERWRITE);
			$statement = "select $funcname ( $upload_id, $iwork_id )";
			$done = $cofk->db_select_one_value($statement);
		}


# Set the total number of works in the upload table
		$statement = 'update '
			. '	cofk_collect_upload'
			. " set total_works = $num_works_uploaded "
			. " where upload_id = $upload_id";
		$condition['upload_id'] = $upload_id;
		$update_array['total_works'] = $num_works_uploaded;
		echo "db_run_query( $statement )";
//	$cofk->db_run_query( $statement );
		pg_update($db_connection->connection->connection,
			'cofk_collect_upload',
			$update_array,
			$condition,
			PGSQL_DML_EXEC);
	}
	$cofk->db_run_query('COMMIT');

}

#-----------------------------------------------------
// mixed pg_delete ( resource $connection , string $table_name , array $assoc_array [, int $options = PGSQL_DML_EXEC ] )
// mixed pg_insert ( resource $connection , string $table_name , array $assoc_array [, int $options = PGSQL_DML_EXEC ] )
// mixed pg_select ( resource $connection , string $table_name , array $assoc_array [, int $options = PGSQL_DML_EXEC ] )
// mixed pg_update ( resource $connection , string $table_name , array $data , array $condition [, int $options = PGSQL_DML_EXEC ] )
#-----------------------------------------------------
function read_one_uploaded_file( $mongo_table, $openoffice_table, $postgres_table, $id_field ) {
	global $cofk;
	global $database;
	global $upload_id;
	global $db_connection;
	global $uploadImpvalue;

	$table_desc = str_replace( '_', ' ', $openoffice_table );

	echo "Reading '" . $table_desc . "' file..." . NEWLINE;
	echo NEWLINE;
	flush();

	$filter = [ 'upload_id'=>$uploadImpvalue ];
	$options = [ 'projection' => ['_id' => 0] ];

	$query = new MongoDB\Driver\Query($filter, $options);
	$mongo_cursor = $database->executeQuery( 'emlo-edit.' . $mongo_table, $query);

	//echo "Number found: " . $mongo_cursor->count();

	$it = new \IteratorIterator($mongo_cursor);
	$it->rewind(); // Very important

	while( $mongo_row = $it->current() ) {

		$mongo_row = object_to_array( $mongo_row );

		$mongo_row['upload_id'] = $upload_id ;

		if (!empty($mongo_row['union_iperson_id'])) {
			// Check people exist

			$select_cols = 'foaf_name,person_id';
			$where_col = 'iperson_id';
			$from_table = $cofk->proj_person_tablename();
			$value = $mongo_row['union_iperson_id'];
			$check = "select $select_cols from $from_table where $where_col = $value";

			//$mongo_row['person_id'] = $cofk->db_select_one_value( $check );
			$results = $cofk->db_select_into_array( $check );

			if( $results ) {
				$result = $results[0];
				$mongo_row['person_id'] = $result['person_id'];
				if( ! $mongo_row['person_id'] )
					$mongo_row['union_iperson_id'] = NULL;
				else
					$mongo_row['primary_name'] = $result['foaf_name'];
			}
		}

		if( $mongo_row['institution_id'] ) {
			// Lets check if this institution already exists.

			$institution_id = $mongo_row['institution_id'];
			$from_table = $cofk->proj_institution_tablename();

			$check = "select institution_id from $from_table where institution_id = $institution_id";

			$results = $cofk->db_select_into_array( $check );
			if( $results ) {
				$mongo_row['union_institution_id'] = $institution_id;
				echo 'Found existing institution with id=' . $institution_id . "\n";
			}
			else {
				echo 'New institution: ' . $mongo_row['institution_name'] . "\n";
			}
		}

		$res = pg_insert (
			$db_connection->connection->connection,
			$postgres_table ,
			$mongo_row ,
			PGSQL_DML_EXEC  );

		if ($res) {
			if( $id_field ) {
				echo "Successfully inserted " . $openoffice_table . " id( " . $mongo_row[$id_field] . " ) into $postgres_table\n";
			}
			else {
				echo "Successfully inserted into $postgres_table\n";
			}
		} else {
				echo "\nErrored trying to insert", print_r($mongo_row, True ), "into " . $postgres_table . "\n";

				$mdb_structure = new MongoDB_Document_Structure(); # get a list of the columns in the MongoDB document
				echo "Expected format : " . print_r($mdb_structure->$openoffice_table(),True);

				echo pg_last_error( $db_connection->connection->connection );
				echo "\nError detected in inputs\n";
				exit(1);
		}

		$it->next();
	}
}
#-----------------------------------------------------
function get_file_to_tables_lookup() {

$file_to_tables_lookup = array(

		CSV_FILE_PERSON               => array( 'openoffice' => 'person',
				'postgres'   => 'cofk_collect_person',
				'id' => 'iperson_id'
		),

		CSV_FILE_LOCATION             => array( 'openoffice' => 'location',
				'postgres'   => 'cofk_collect_location',
			'id' => 'location_id'
		),

		CSV_FILE_INSTITUTION          => array( 'openoffice' => 'institution',
				'postgres'   => 'cofk_collect_institution',
			'id' => 'institution_id'
		),

		CSV_FILE_WORK                 => array( 'openoffice' => 'work',
				'postgres'   => 'cofk_collect_work',
			'id' => 'iwork_id'
		),

		CSV_FILE_MANIFESTATION        => array( 'openoffice' => 'manifestation',
				'postgres'   => 'cofk_collect_manifestation',
			'id' => 'manifestation_id'
		),

		CSV_FILE_ADDRESSEE            => array( 'openoffice' => 'addressee',
				'postgres'   => 'cofk_collect_addressee_of_work'),

		CSV_FILE_AUTHOR               => array( 'openoffice' => 'author',
				'postgres'   => 'cofk_collect_author_of_work'),

		CSV_FILE_OCCUPATION_OF_PERSON => array( 'openoffice' => 'occupation_of_person',
				'postgres'   => 'cofk_collect_occupation_of_person'),

		CSV_FILE_PERSON_MENTIONED     => array( 'openoffice' => 'person_mentioned',
				'postgres'   => 'cofk_collect_person_mentioned_in_work'),

		CSV_FILE_PLACE_MENTIONED      => array( 'openoffice' => 'place_mentioned',
				'postgres'   => 'cofk_collect_place_mentioned_in_work'),

		CSV_FILE_LANGUAGE_OF_WORK      => array( 'openoffice' => 'language_of_work',
				'postgres'   => 'cofk_collect_language_of_work'),

		CSV_FILE_SUBJECT_OF_WORK      => array( 'openoffice' => 'subject_of_work',
				'postgres'   => 'cofk_collect_subject_of_work'),

		CSV_FILE_WORK_RESOURCE        => array( 'openoffice' => 'work_resource',
				'postgres'   => 'cofk_collect_work_resource'),

		CSV_FILE_PERSON_RESOURCE      => array( 'openoffice' => 'person_resource',
				'postgres'   => 'cofk_collect_person_resource'),

		CSV_FILE_LOCATION_RESOURCE    => array( 'openoffice' => 'location_resource',
				'postgres'   => 'cofk_collect_location_resource'),

		CSV_FILE_INSTITUTION_RESOURCE => array( 'openoffice' => 'institution_resource',
				'postgres'   => 'cofk_collect_institution_resource'),

		//CSV_FILE_IMAGE_OF_MANIF => array( 'openoffice' => 'image_of_manif',
		//		'postgres'   => 'cofk_collect_image_of_manif')
);
return $file_to_tables_lookup;
}
#-----------------------------------------------------
?>

