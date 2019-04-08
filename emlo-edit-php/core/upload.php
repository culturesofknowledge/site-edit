<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Upload files from Cultures of Knowledge offline data collection tool
# Author: Sushila Burgess
#====================================================================================

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/excel/simplexlsx-master/src/SimpleXLSX.php';
require_once __DIR__ . '/excel/simplexls-master/src/SimpleXLS.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

define( 'CSV_DIR_FOR_UPLOADS', '/srv/data/culturesofknowledge/csv/' );

define( 'IDS_CREATED_IN_TOOL_START', 1000000 );

define( 'NUM_CSV_FILES_TO_UPLOAD', 16 );
define( 'CSV_FILENAME_ROOT', 'emloexport' );
define( 'CSV_FILE_MAX_CHARS_PER_LINE', 1000000 );

define( 'CSV_UPLOAD_FIELD_SIZE', 80 );

define( 'CSV_FILE_ADDRESSEE',             1 );
define( 'CSV_FILE_AUTHOR',                2 );
define( 'CSV_FILE_INSTITUTION',           3 );
define( 'CSV_FILE_LOCATION',              4 );
define( 'CSV_FILE_MANIFESTATION',         5 );
define( 'CSV_FILE_OCCUPATION_OF_PERSON',  6 );
define( 'CSV_FILE_PERSON',                7 );
define( 'CSV_FILE_PERSON_MENTIONED',      8 );
define( 'CSV_FILE_PLACE_MENTIONED',       9 );
define( 'CSV_FILE_SUBJECT_OF_WORK',      10 );
define( 'CSV_FILE_WORK',                 11 );
define( 'CSV_FILE_LANGUAGE_OF_WORK',     12 );
define( 'CSV_FILE_WORK_RESOURCE',        13 );
define( 'CSV_FILE_PERSON_RESOURCE',      14 );
define( 'CSV_FILE_LOCATION_RESOURCE',    15 );
define( 'CSV_FILE_INSTITUTION_RESOURCE', 16 );

define( 'UPLOAD_REVIEWER_ROLE', 'reviewer' );  # Person who receives notifications where there is a new upload,
                                               # but in order to actually carry out a review, you need supervisor role.

class Upload extends Project {

  #----------------------------------------------------------------------------------

  function Upload( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function set_upload( $upload_id = NULL, $iwork_id = NULL ) {

    $this->clear();
    if( ! $upload_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_collect_upload_tablename()
               . " where upload_id = $upload_id";
    $this->db_select_into_properties( $statement );

    $statement = 'select * from ' . $this->proj_collect_status_tablename()
               . " where status_id = $this->upload_status";
    $this->db_select_into_properties( $statement );

    if( $iwork_id && $this->upload_id ) {
      $statement = 'select * from ' . $this->proj_collect_work_tablename()
                 . " where upload_id = $this->upload_id and iwork_id = $iwork_id";
      $this->db_select_into_properties( $statement );
    }

    return $this->upload_id;
  }
  #----------------------------------------------------------------------------------
  function review_underway() {

    if( PROJ_COLLECTION_CODE == DATA_COLLECTION_TOOL_SUBSYSTEM ) # this is just a normal user viewing their own data
      return FALSE;
    else { # review must be done by someone with 'supervisor' role
      if( ! $this->user_is_supervisor()) die( 'Invalid user.' );
      return TRUE;
    }
  }
  #----------------------------------------------------------------------------------

  function proj_main_tablename( $core_name ) {  # used in overriding parent tablename() methods, 
                                                # so as not to include 'collect' in the name

    $tablename = $this->get_system_prefix() . '_';
    if( $this->get_system_prefix() == CULTURES_OF_KNOWLEDGE_SYS_PREFIX )
      $tablename .= 'union_';
    $tablename .= $core_name;
    return $tablename;
  }
  #----------------------------------------------------------------------------------

  function proj_work_tablename() {  # overrides parent method, so as not to include 'collect' in the name
    return $this->proj_main_tablename( 'work' );
  }
  #----------------------------------------------------------------------------------

  function proj_person_tablename() {  # overrides parent method, so as not to include 'collect' in the name
    return $this->proj_main_tablename( 'person' );
  }
  #----------------------------------------------------------------------------------

  function proj_location_tablename() {  # overrides parent method, so as not to include 'collect' in the name
    return $this->proj_main_tablename( 'location' );
  }
  #----------------------------------------------------------------------------------

  function proj_institution_tablename() {  # overrides parent method, so as not to include 'collect' in the name
    return $this->proj_main_tablename( 'institution' );
  }
  #----------------------------------------------------------------------------------

  function proj_subject_tablename() {  # overrides parent method, so as not to include 'collect' in the name
    return $this->proj_main_tablename( 'subject' );
  }
  #----------------------------------------------------------------------------------

  function proj_role_category_tablename() {
    return $this->proj_main_tablename( 'role_category' );
  }
  #----------------------------------------------------------------------------------

  function proj_org_type_tablename() {
    return $this->proj_main_tablename( 'org_type' );
  }
  #----------------------------------------------------------------------------------

  function proj_resource_tablename() {
    return $this->proj_main_tablename( 'resource' );
  }
  #----------------------------------------------------------------------------------

  function file_upload_form() {

    HTML::h3_start();
    echo 'Locate your data export files';
    HTML::h3_end();

    HTML::new_paragraph();
    echo "When you clicked the 'Export' button in the data collection tool, " . NUM_CSV_FILES_TO_UPLOAD . ' files'
         . ' were created in the same folder as your OpenOffice database file (EMLOcollect.odb).';
    echo LINEBREAK . 'These files were called '; 
    HTML::bold_start();
    echo $this->csv_filename_from_number( 1 );
    HTML::bold_end();
    echo ' through to ';
    HTML::bold_start();
    echo $this->csv_filename_from_number( NUM_CSV_FILES_TO_UPLOAD );
    HTML::bold_end();

    HTML::new_paragraph();
    HTML::italic_start();
    echo 'Please upload these files, using Shift-Click or Ctrl-Click to select them all at once,'
         . ' then Open.';
    HTML::italic_end();
    HTML::new_paragraph();

    HTML::form_start( $class_name = 'upload', 
                      $method_name = 'process_uploaded_files', 
                      $form_name = NULL,  # use default
                      $form_target = '_self',
                      $onsubmit_validation = FALSE, 
                      $form_destination = NULL, 
                      $form_method='POST',
                      $parms = 'enctype="multipart/form-data"' );

    HTML::multiple_file_upload_field( $fieldname = 'files_to_process[]', 
                             $label = 'Select the ' . NUM_CSV_FILES_TO_UPLOAD . " '"
                                    . CSV_FILENAME_ROOT . "' files", 
                             $value = NULL, 
                             $size = CSV_UPLOAD_FIELD_SIZE );
    HTML::new_paragraph();

    HTML::submit_button( 'upload_button', 'Upload' );
    HTML::form_end();
  }
  #----------------------------------------------------------------------------------

  function process_uploaded_files() {

    $files_to_process = $_FILES[ 'files_to_process' ];
    if( ! $this->is_array_of_ok_free_text( $files_to_process )) {
      die( 'Invalid input.' );
    }

    extract( $files_to_process, EXTR_OVERWRITE );
    $invalid = FALSE;

    if( count( $name )     != NUM_CSV_FILES_TO_UPLOAD ) $invalid = TRUE;
    if( count( $tmp_name ) != NUM_CSV_FILES_TO_UPLOAD ) $invalid = TRUE;
    if( count( $type )     != NUM_CSV_FILES_TO_UPLOAD ) $invalid = TRUE;
    if( count( $error )    != NUM_CSV_FILES_TO_UPLOAD ) $invalid = TRUE;
    if( count( $size )     != NUM_CSV_FILES_TO_UPLOAD ) $invalid = TRUE;

    if( $invalid ) {
      echo LINEBREAK;
      HTML::div_start( 'class="warning"' );
      echo 'Sorry, you have not uploaded the correct number of files. ';
      HTML::new_paragraph();

      echo 'You need to upload ' . NUM_CSV_FILES_TO_UPLOAD . ' files, named ' 
           . CSV_FILENAME_ROOT . '01.csv through to '
           . CSV_FILENAME_ROOT . NUM_CSV_FILES_TO_UPLOAD . '.csv.';
      HTML::new_paragraph();

      echo ' Please try again, selecting all ' . NUM_CSV_FILES_TO_UPLOAD
           . ' files using Shift-Click or Ctrl-Click.';
      HTML::div_end();
      echo LINEBREAK;

      $this->file_upload_form();
      return;
    }

    if( ! $this->is_array_of_ok_free_text( $name ))     $invalid = TRUE;
    if( ! $this->is_array_of_ok_free_text( $tmp_name )) $invalid = TRUE;
    if( ! $this->is_array_of_ok_free_text( $type ))     $invalid = TRUE;
    if( ! $this->is_array_of_integers( $error ))        $invalid = TRUE;
    if( ! $this->is_array_of_integers( $size ))         $invalid = TRUE;

    foreach( $tmp_name as $one_tmp_name ) {
      if( ! is_uploaded_file( $one_tmp_name )) $invalid = TRUE;
    }

    foreach( $name as $one_name ) {
      if( strlen( $one_name ) != strlen( CSV_FILENAME_ROOT . 'NN.csv' )) {
        $invalid = TRUE;
        break;
      }
      $part1 = strtolower( substr( $one_name, 0, strlen( CSV_FILENAME_ROOT )));
      $part2 = strtolower( substr( $one_name, strlen( CSV_FILENAME_ROOT ), 2 ));
      $part3 = strtolower( substr( $one_name, strlen( CSV_FILENAME_ROOT ) + 2 ));

      if( $part1 != CSV_FILENAME_ROOT || ! $this->is_integer( $part2 ) || $part3 != '.csv' ) {
        $invalid = TRUE;
        break;
      }
    }

    if( $invalid ) die( 'Invalid file details.' );
    $upload_err_msg = 'An error occurred while uploading the files.';

    $new_path = $this->get_csv_upload_dir();
    $ret = mkdir( $new_path );
    if( ! $ret ) die( $upload_err_msg  );

    #---------------------------------------------
    # Move the file to its semi-permanent position
    #---------------------------------------------
    for( $i = 0; $i < NUM_CSV_FILES_TO_UPLOAD; $i++ ) {

      $one_name     = $name[ $i ];
      $one_tmp_name = $tmp_name[ $i ];
      $one_type     = $type[ $i ];
      $one_error    = $error[ $i ];
      $one_size     = $size[ $i ];

      if( $one_error != 0 ) {
        die( $upload_err_msg  );
      }

      $new_filename = $new_path . $one_name;
      $moved = move_uploaded_file( $one_tmp_name, $new_filename );
      if( ! $moved ) die( $upload_err_msg );

      $readable = chmod( $new_filename, 0744 );
      if( ! $readable ) die( $upload_err_msg );
    }

    #------------------------
    # Get contributor details
    #------------------------
    $this->username    = $this->read_session_parm( 'username' );
    $this->user_email  = $this->read_session_parm( 'user_email' );
    $this->person_name = $this->read_session_parm( 'person_name' );

    #-----------------------------------
    # Record that the upload has started
    #-----------------------------------
    $statement = 'insert into ' . $this->proj_collect_upload_tablename() 
               . '(upload_id, upload_username, uploader_email, upload_description) values ('
               . $this->upload_id . ", '" . $this->escape( $this->username ) . "', "
               . "'" . $this->escape( $this->user_email ) . "', "
               . "'" . $this->escape( $this->person_name ) . "' || ' '"
               . " || to_char( 'now'::timestamp, 'dd Mon yyyy hh24:mi' ))";
    $this->db_run_query( $statement ); 

    #------------------------------------
    # Read and validate each file in turn
    #------------------------------------
    $this->read_uploaded_files( $this->upload_id );

    #------------------------------------------------------------
    # Transfer the data from temporary tables into permanent ones
    #------------------------------------------------------------
    if( ! $this->failed_validation ) {
      $this->transfer_temp_tables();
    }

    #-------------------------------------------
    # Drop temporary tables and delete CSV files
    #-------------------------------------------
    $this->cleanup();
  }
  #----------------------------------------------------------------------------------

  function get_csv_upload_dir( $upload_id = NULL ) {

    if( ! $upload_id ) {
      $upload_table = $this->proj_collect_upload_tablename();
      $upload_seq = $this->proj_id_seq_name( $upload_table );

      $statement = "select nextval('" . $upload_seq . "'::regclass)";
      $upload_id = $this->db_select_one_value( $statement );
    }

    $this->upload_id = $upload_id;

    $path = CSV_DIR_FOR_UPLOADS . CONSTANT_DATABASE_TYPE . 'upload' . $upload_id . '/';
    return $path;
  }
  #-----------------------------------------------------

  function csv_filename_from_number( $file_number ) {

    $filename = CSV_FILENAME_ROOT . str_pad( strval( $file_number ), 2, '0', STR_PAD_LEFT ) . '.csv';
    return $filename;
  }
  #-----------------------------------------------------

  function get_file_to_tables_lookup() {

    $file_to_tables_lookup = array(

      CSV_FILE_PERSON               => array( 'openoffice' => 'person',
                                              'postgres'   => $this->proj_collect_person_tablename()),

      CSV_FILE_LOCATION             => array( 'openoffice' => 'location',
                                              'postgres'   => $this->proj_collect_location_tablename()),

      CSV_FILE_INSTITUTION          => array( 'openoffice' => 'institution',
                                              'postgres'   => $this->proj_collect_institution_tablename()),

      CSV_FILE_WORK                 => array( 'openoffice' => 'work',
                                              'postgres'   => $this->proj_collect_work_tablename()),

      CSV_FILE_MANIFESTATION        => array( 'openoffice' => 'manifestation',
                                              'postgres'   => $this->proj_collect_manifestation_tablename()),

      CSV_FILE_ADDRESSEE            => array( 'openoffice' => 'addressee',
                                              'postgres'   => $this->proj_collect_addressee_of_work_tablename()),

      CSV_FILE_AUTHOR               => array( 'openoffice' => 'author',
                                              'postgres'   => $this->proj_collect_author_of_work_tablename()),

      CSV_FILE_OCCUPATION_OF_PERSON => array( 'openoffice' => 'occupation_of_person',
                                              'postgres'   => $this->proj_collect_occupation_of_person_tablename()),

      CSV_FILE_PERSON_MENTIONED     => array( 'openoffice' => 'person_mentioned',
                                              'postgres'   => $this->proj_collect_person_mentioned_in_work_tablename()),

      CSV_FILE_PLACE_MENTIONED      => array( 'openoffice' => 'place_mentioned',
                                              'postgres'   => $this->proj_collect_place_mentioned_in_work_tablename()),

      CSV_FILE_LANGUAGE_OF_WORK      => array( 'openoffice' => 'language_of_work',
                                               'postgres'   => $this->proj_collect_language_of_work_tablename()),

      CSV_FILE_SUBJECT_OF_WORK      => array( 'openoffice' => 'subject_of_work',
                                              'postgres'   => $this->proj_collect_subject_of_work_tablename()),

      CSV_FILE_WORK_RESOURCE        => array( 'openoffice' => 'work_resource',
                                              'postgres'   => $this->proj_collect_work_resource_tablename()),

      CSV_FILE_PERSON_RESOURCE      => array( 'openoffice' => 'person_resource',
                                              'postgres'   => $this->proj_collect_person_resource_tablename()),

      CSV_FILE_LOCATION_RESOURCE    => array( 'openoffice' => 'location_resource',
                                              'postgres'   => $this->proj_collect_location_resource_tablename()),

      CSV_FILE_INSTITUTION_RESOURCE => array( 'openoffice' => 'institution_resource',
                                              'postgres'   => $this->proj_collect_institution_resource_tablename())
    );
    return $file_to_tables_lookup;
  }
  #-----------------------------------------------------

  function get_file_to_openoffice_table_lookup() {

    $file_to_table_lookup = array();
    $both_tables = $this->get_file_to_tables_lookup();

    foreach( $both_tables as $file_number => $tables ) {
      $file_to_table_lookup[ $file_number ] = $tables[ 'openoffice' ];
    }
    return $file_to_table_lookup;
  }
  #-----------------------------------------------------

  function get_file_to_postgres_table_lookup() {

    $file_to_table_lookup = array();
    $both_tables = $this->get_file_to_tables_lookup();

    foreach( $both_tables as $file_number => $tables ) {
      $file_to_table_lookup[ $file_number ] = $tables[ 'postgres' ];
    }
    return $file_to_table_lookup;
  }
  #-----------------------------------------------------

  function read_uploaded_files( $upload_id ) {

    if( ! $upload_id ) die( 'Error reading uploaded files.' );
    $this->upload_id = $this->set_upload( $upload_id );
    $this->failed_validation = FALSE;

    HTML::h3_start();
    echo 'Processing your contribution... ';
    HTML::h3_end();
    HTML::new_paragraph();

    HTML::h4_start();
    echo 'Reading uploaded files...';
    HTML::h4_end();
    flush();

    $file_to_openoffice_table_lookup = $this->get_file_to_openoffice_table_lookup() ;
    $file_to_postgres_table_lookup = $this->get_file_to_postgres_table_lookup() ;

    $this->temporary_tables_created = array();

    foreach( $file_to_openoffice_table_lookup as $file_number => $openoffice_table ) {
      $postgres_table = $file_to_postgres_table_lookup[ $file_number ];
      $filename = $this->csv_filename_from_number( $file_number );
      $this->read_one_uploaded_file( $filename, $openoffice_table, $postgres_table );
      if( $this->failed_validation ) return;
    }
  }
  #-----------------------------------------------------

  function read_one_uploaded_file( $filename, $openoffice_table, $postgres_table ) {

    echo LINEBREAK;
    echo $this->get_datetime_now_in_words( $include_seconds = TRUE );
    echo LINEBREAK;

    $table_desc = str_replace( '_', ' ', $openoffice_table );
    echo "Reading '" . $table_desc . "' file..." . LINEBREAK;

    echo LINEBREAK;
    flush();

    $oo_structure = new OpenOffice_Table_Structure();
    $cols = $oo_structure->$openoffice_table();  # get a list of the columns in the OpenOffice table

    # Create a temporary table to hold the data until it goes into the main database
    $postgres_table = $this->temp_tablename_from_perm( $postgres_table );
    $statement = "create table $postgres_table (";
    $i = 0;
    foreach( $cols as $colname => $datatype ) {
      $i++;
      if( $i > 1 ) $statement .= ', ';
      $statement .= "$colname $datatype";
    }
    $statement .= ')';
    $this->db_run_query( $statement );
    $this->temporary_tables_created[] = $postgres_table;

    # Identify the unique key column
    $keycol = '';
    foreach( $cols as $colname => $datatype ) {
      $keycol = $colname; # first column in the table will always be the key column
      break;
    }

    $path = $this->get_csv_upload_dir( $this->upload_id );
    $fullpath = $path . $filename; # path ends in slash

    $handle = fopen( $fullpath, 'r' );
    $line_number = 0;
    while( $line = fgetcsv( $handle, CSV_FILE_MAX_CHARS_PER_LINE )) {
      # Check for blank lines, which may be left in OpenOffice text table after deletion of old data
      if( count( $line ) == 1 ) {
        $onlycol = $line[ 0 ];
        if( strlen( trim( $onlycol )) == 0 ) continue;  # skip blank line
      }

      $line_number++;
      if( count( $line ) != count( $cols )) {
        $errmsg = "ERROR: wrong number of columns in file '" . $filename . "' at line $line_number.";
        echo $errmsg . LINEBREAK;
        $this->failed_validation = TRUE;
        break;
      }

      # Check that there are the right number of columns of the right datatypes
      $i = -1;
      foreach( $cols as $colname => $datatype ) { #massively simplified datatypes: 'integer' or 'text'
        $i++;
        $column_number = $i + 1;
        $$colname = NULL; # clear value from previous line
        $value = $line[ $i ];
        $errmsg = "ERROR: invalid entry in file '" . $filename . "' at line $line_number, column $column_number.";
        if( $datatype == 'integer' ) {
          if( ! $this->is_integer( $value, $allow_negative = FALSE, $allow_null = TRUE )) {
            echo $errmsg . LINEBREAK;
            $this->failed_validation = TRUE;
            break; # if failed validation, stop reading the line
          }
        }
        else {
          if( ! $this->is_ok_free_text( $value )) {
            echo $errmsg . LINEBREAK;
            $this->failed_validation = TRUE;
            break; # if failed validation, stop reading the line
          }
        }
        # Copy the value into a named variable/property
        $$colname = $value;
        $this->$colname = $value;
      }
      if( $this->failed_validation ) break;  # if failed validation, stop reading the file

      # Check whether we have already imported a line with the relevant key value. This could happen
      # because when OpenOffice exports to a CSV file, it appends to an existing file instead of
      # overwriting. I think we have to reject any batch of files that seem to contain duplicates.

      $duplicate = FALSE; 
      if( intval( $$keycol ) == 0 && $line_number == 1 ) # dummy row indicating an empty table
        continue;
      elseif( intval( $$keycol ) == 0 && $line_number > 1 ) # two dummy rows = a duplicate
        $duplicate = TRUE;
      else {
        $statement = "select count(*) from $postgres_table where $keycol = " . $$keycol;
        $duplicate = $this->db_select_one_value( $statement );
      }

      if( $duplicate ) {
        $this->failed_validation = TRUE;
        HTML::new_paragraph();
        HTML::div_start( 'class="errmsg"' );
        echo "Error processing file: the '$table_desc' file contains duplicate lines.";
        HTML::div_end();
        HTML::new_paragraph();
        HTML::div_start( 'class="highlight2 bold"' );
        echo "This was probably caused by the 'Export' button in the data collection tool"
             . ' being pressed multiple times, as the export procedure appends data to the end'
             . ' of any existing file.';
        HTML::new_paragraph();
        echo 'You need to clear out existing files before you begin. Please go to the local folder'
             . ' on your personal computer from which you uploaded the files ' . CSV_FILENAME_ROOT 
             . '01.csv through to ' . CSV_FILENAME_ROOT . NUM_CSV_FILES_TO_UPLOAD . '.csv'
             . ' and delete all those ' . NUM_CSV_FILES_TO_UPLOAD . ' files.';
        HTML::new_paragraph();
        echo "Then return to the OpenOffice data collection tool, go into the 'Upload your"
             . " contribution' form, and click the 'Export' button again.";
        HTML::div_end();
        HTML::new_paragraph();

        break; # stop reading the file
      }

      $statement = "insert into $postgres_table (";
      $i = 0;
      foreach( $cols as $colname => $datatype ) {
        $value = $$colname;
        if( strlen( $value ) == 0 ) continue;
        if( $i > 0 ) $statement .= ', ';
        $statement .= $colname;
        $i++;
      }
      $statement .= ' ) values ( ';
      $i = 0;
      foreach( $cols as $colname => $datatype ) {
        $value = $$colname;
        if( strlen( $value ) == 0 ) continue;
        if( $i > 0 ) $statement .= ', ';
        $i++;

        # See if people and works already exist in the main database
        if( $colname == 'person_id' or $colname == 'work_id' ) {
          if( $colname == 'person_id' ) {
            $select_col = 'iperson_id'; 
            $where_col = 'person_id';
            $from_table = $this->proj_person_tablename();
          }
          else {
            $select_col = 'iwork_id'; 
            $where_col = 'work_id';
            $from_table = $this->proj_work_tablename();
          }

          $check = "select $select_col from $from_table where $where_col = '$value'";
          $union_iid = $this->db_select_one_value( $check );
          if( $union_iid ) 
            $statement .= "'" . $this->escape( $value ) . "'";
          else
            $statement .= 'null';
        }
        elseif( $datatype == 'integer' ) 
          $statement .= $value;
        else
          $statement .= "'" . $this->escape( $value ) . "'";
      }
      $statement .= ')';
      $this->db_run_query( $statement );
    }

    fclose( $handle );
  }
  #-----------------------------------------------------
  function temp_tablename_from_perm( $perm_tablename ) {
    return 'upload' . $this->upload_id . '_' . $perm_tablename;
  }
  #-----------------------------------------------------
  function cleanup() {

    # Drop temporary tables
    foreach( $this->temporary_tables_created as $temptable ) {
      $statement = 'drop table ' . $temptable;
      $this->db_run_query( $statement );
    }

    # Delete CSV files and the directory containing them
    $path = $this->get_csv_upload_dir( $this->upload_id );
    $files_and_tables = $this->get_file_to_openoffice_table_lookup();
    foreach( $files_and_tables as $file_number => $table_name ) {
      $filename = $this->csv_filename_from_number( $file_number );
      $fullpath = $path . $filename; # path ends in slash
      unlink( $fullpath );
    }
    rmdir( $path );
  }
  #-----------------------------------------------------

  function person_used_in_contribution( $iperson_id ) {

    $tables_to_check = array();
    $tables_to_check[] = $this->temp_tablename_from_perm( $this->proj_collect_addressee_of_work_tablename());
    $tables_to_check[] = $this->temp_tablename_from_perm( $this->proj_collect_author_of_work_tablename());
    $tables_to_check[] = $this->temp_tablename_from_perm( $this->proj_collect_person_mentioned_in_work_tablename());
    
    foreach( $tables_to_check as $table ) {
      $statement = "select count(*) from $table where iperson_id = $iperson_id";
      $uses = $this->db_select_one_value( $statement );
      if( $uses > 0 ) return TRUE;
    }
    return FALSE;
  }
  #-----------------------------------------------------

  function place_used_in_contribution( $location_id ) {

    $table = $this->temp_tablename_from_perm( $this->proj_collect_work_tablename());
    $statement = "select count(*) from $table where origin_id = $location_id or destination_id = $location_id";
    $uses = $this->db_select_one_value( $statement );
    if( $uses > 0 ) return TRUE;

    $table = $this->temp_tablename_from_perm( $this->proj_collect_place_mentioned_in_work_tablename());
    $statement = "select count(*) from $table where location_id = $location_id";
    $uses = $this->db_select_one_value( $statement );
    if( $uses > 0 ) return TRUE;

    return FALSE;
  }
  #-----------------------------------------------------

  function repos_used_in_contribution( $institution_id ) {

    $table = $this->temp_tablename_from_perm( $this->proj_collect_manifestation_tablename());
    $statement = "select count(*) from $table where repository_id = $institution_id";
    $uses = $this->db_select_one_value( $statement );
    if( $uses > 0 ) return TRUE;

    return FALSE;
  }
  #-----------------------------------------------------
  function transfer_temp_tables() {  # transfer from temporary tables into the permanent ones with prefix 'collect'

    echo LINEBREAK;
    
    HTML::h4_start();
    echo 'Transferring data into holding area ready for review...' . LINEBREAK;
    HTML::h4_end();

    $file_to_tables_lookup = $this->get_file_to_tables_lookup();

    foreach( $file_to_tables_lookup as $file_number => $tables ) {

      $this->perm_postgres_table = $tables[ 'postgres' ];
      $this->temp_postgres_table = $this->temp_tablename_from_perm( $this->perm_postgres_table );
      $this->table_desc = str_replace( '_', ' ', $tables[ 'openoffice' ] );

      echo "Processing '" . $this->table_desc . "' data..." . LINEBREAK;

      $anchor = $this->temp_postgres_table . '_anchor';
      HTML::anchor( $anchor );
      $script = "document.location.hash = '#$anchor';";
      HTML::write_javascript_function( $script );
      flush();

      $this->uploaded_data = array();
      $statement = 'select * from ' . $this->temp_postgres_table;


      if( $file_number == CSV_FILE_PERSON ) { #restrict selection or will die under mass of data
        $where_clause = $this->only_get_people_used();
        $statement .= ' ' . $where_clause;
      }
      elseif( $file_number == CSV_FILE_LOCATION ) {
        $where_clause = $this->only_get_places_used();
        $statement .= ' ' . $where_clause;
      }
      elseif( $file_number == CSV_FILE_INSTITUTION ) {
        $where_clause = $this->only_get_repos_used();
        $statement .= ' ' . $where_clause;
      }

      $this->uploaded_data = $this->db_select_into_array( $statement );

      $funcname = 'transfer_'. $tables[ 'openoffice' ];
      $this->$funcname();

      if( $this->failed_validation ) {
        echo 'Import procedure had to be aborted due to errors.' . LINEBREAK;
        return;
      }
    }

    # Check that there are actually some works in the contribution
    # If not, automatically reject it.
    $statement = 'select count(*) from ' . $this->proj_collect_work_tablename()
               . " where upload_id = $this->upload_id";
    $num_works_uploaded = $this->db_select_one_value( $statement );
    if( $num_works_uploaded == 0 ) {
      $statement = 'update ' . $this->proj_collect_upload_tablename()
                 . ' set upload_status = ' . CONTRIB_STATUS_REJECTED
                 . " where upload_id = $this->upload_id";
      $this->db_run_query( $statement );
      HTML::div_start( 'class="warning"' );
      echo 'You did not upload any works in your contribution. The Works file was empty.'
           . ' This means that your contribution cannot be accepted.';
      HTML::new_paragraph();
      echo 'Please try again, ensuring that you are exporting from the correct data source; you may'
           . ' need to re-register your database with OpenOffice.'
           . ' Instructions for registering an OpenOffice data source are given in Step 1'
           . " of the 'Upload your contribution' form within the data collection tool."; 
      HTML::div_end();
      HTML::new_paragraph();
      return;
    }
    else {  # write an easily-displayed summary of everything to do with the work: authors, manifestations, etc.

      $funcname = $this->proj_database_function_name( 'write_work_summary', $include_collection_code = TRUE );
      $statement = 'select iwork_id from ' . $this->proj_collect_work_tablename()
                 . " where upload_id = $this->upload_id"
                 . ' order by iwork_id';
      $work_ids = $this->db_select_into_array( $statement );
      foreach( $work_ids as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $statement = "select $funcname ( $this->upload_id, $iwork_id )";
        $done = $this->db_select_one_value( $statement );
      }

      # Set the total number of works in the upload table
      $statement = 'update ' . $this->proj_collect_upload_tablename()
                 . " set total_works = $num_works_uploaded "
                 . " where upload_id = $this->upload_id";
      $this->db_run_query( $statement );
    }

    echo LINEBREAK;
    echo $this->get_datetime_now_in_words( $include_seconds = TRUE );
    echo LINEBREAK;

    # Inform anyone with the 'reviewer' role
    $this->inform_reviewer_of_upload();

    # If they did manage to upload some valid data, tell them it is awaiting review.
    HTML::h4_start();
    echo 'Upload complete.';
    HTML::h4_end();

    HTML::div_start( 'class="highlight2 bold"' );
    echo 'Thank you for your contribution. Your data has been successfully imported into the EMLO-edit holding area.';
    HTML::new_paragraph();
    echo 'The project editors have been informed.';
    HTML::new_paragraph();
    echo 'Your contribution will now be reviewed and, if accepted, will be transferred into the main EMLO database.'
         . ' You will shortly receive an email informing you of the results of the review.';
    HTML::div_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="boldlink"' );
    echo 'You can also ';
    $href = $_SERVER['PHP_SELF'] . '?option=history&upload_id=' . $this->upload_id;
    HTML::link( $href, 'check the status and details', 'Check the status and details of your contribution' );
    echo ' of your contribution online.';

    HTML::button( 'check_upload_button', 'Check', $tabindex = 1,
                  'id="check_upload_button" onclick="window.location.href=' . "'$href'" . '"' );

    HTML::div_end();

    HTML::new_paragraph();

    HTML::anchor( 'endofmessages' );
    $script = 'document.location.hash = "endofmessages";';
    HTML::write_javascript_function( $script );

    $script = 'var checkButton = document.getElementById( "check_upload_button" ); checkButton.focus();';
    HTML::write_javascript_function( $script );
  }
  #-----------------------------------------------------
  function transfer_person() {  # transfer into the 'collect person' table 

    $cols = $this->db_list_columns( $this->perm_postgres_table );
    $num_saved = 0;

    foreach( $this->uploaded_data as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $already_existed = FALSE;
      $insert_row = FALSE;

      # The main 'person' table will have been pre-loaded into OpenOffice to provide an authority list.
      # We don't want to copy all these details, whether new or existing, into the 'collect person' table
      # unless they are actually used in the contribution. Both new and existing details have to go into the
      # 'collect' table if they are used in the contribution, because of foreign keys linking back to the main table.

      $statement = "select person_id from " . $this->proj_person_tablename()
                   . " where iperson_id = $iperson_id";
      $person_id = $this->db_select_one_value( $statement );

      if( ! $person_id )
        $insert_row = TRUE;
      else { # see if this pre-existing person was used in the contribution
        $already_existed = TRUE;
        $insert_row = $this->person_used_in_contribution( $iperson_id );
      }

      if( $insert_row ) {
        $this->echo_safely( "Copying '" . $this->table_desc .  "' record into holding area: $primary_name" );
        echo LINEBREAK;
        $num_saved++;

        # Columns that need special handling
        $cols_statement = "insert into $this->perm_postgres_table ( upload_id, iperson_id";
        $vals_statement = "values ( $this->upload_id, $iperson_id";

        $cols_statement .= ', union_iperson_id';
        if( ! $already_existed ) $union_iperson_id = 'null';
        else $union_iperson_id = $iperson_id;
        $vals_statement .= ", $union_iperson_id";

        $cols_statement .= ', person_id';
        if( $already_existed ) $vals_statement .= ", '$person_id'";
        else $vals_statement .= ', null';

        # Remaining columns can all be done in a standard way
        foreach( $cols as $crow ) {
          extract( $crow, EXTR_OVERWRITE );

          if( $column_name == 'upload_id' )        continue;  # already done
          if( $column_name == 'iperson_id' )       continue;
          if( $column_name == 'union_iperson_id' ) continue;
          if( $column_name == 'person_id' )        continue;

          if( strlen( $$column_name ) == 0 ) continue; # no need to insert null values

          $cols_statement .= ', ' . $column_name;
          $vals_statement .= ', ';
          if( $is_numeric )
            $vals_statement .= $$column_name;
          else # we don't have any date columns in these particular tables, so will treat everything else as string
            $vals_statement .= "'" . $this->escape( $$column_name ) . "'";
        }
        $cols_statement .= ') ';
        $vals_statement .= ') ';
        $statement = $cols_statement . $vals_statement;
        $this->db_run_query( $statement );
      }
    }
    echo "Saved $num_saved '" . $this->table_desc . "' records." . LINEBREAK;
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function transfer_location() {  # transfer into the 'collect location' table 

    $cols = $this->db_list_columns( $this->perm_postgres_table );
    $num_saved = 0;

    foreach( $this->uploaded_data as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $already_existed = FALSE;
      $insert_row = FALSE;

      # The main 'location' table will have been pre-loaded into OpenOffice to provide an authority list.
      # We don't want to copy all these details, whether new or existing, into the 'collect location' table
      # unless they are actually used in the contribution. Both new and existing details have to go into the
      # 'collect' table if they are used in the contribution, because of foreign keys linking back to the main table.

      $statement = "select location_id from " . $this->proj_location_tablename()
                 . " where location_id = $location_id";
      $already_existed = $this->db_select_one_value( $statement );

      if( ! $already_existed )
        $insert_row = TRUE;
      else { # see if this pre-existing location was used in the contribution
        $insert_row = $this->place_used_in_contribution( $location_id );
      }

      if( $insert_row ) {
        $this->echo_safely( "Copying '" . $this->table_desc .  "' record into holding area: $location_name" );
        echo LINEBREAK;
        $num_saved++;

        # Columns that need special handling
        $cols_statement = "insert into $this->perm_postgres_table ( upload_id, location_id";
        $vals_statement = "values ( $this->upload_id, $location_id";

        $cols_statement .= ', union_location_id';
        if( ! $already_existed ) $union_location_id = 'null';
        else $union_location_id = $location_id;
        $vals_statement .= ", $union_location_id";

        # Remaining columns can all be done in a standard way
        foreach( $cols as $crow ) {
          extract( $crow, EXTR_OVERWRITE );

          if( $column_name == 'upload_id' )         continue;  # already done
          if( $column_name == 'location_id' )       continue;
          if( $column_name == 'union_location_id' ) continue;

          if( strlen( $$column_name ) == 0 ) continue; # no need to insert null values

          $cols_statement .= ', ' . $column_name;
          $vals_statement .= ', ';
          if( $is_numeric )
            $vals_statement .= $$column_name;
          else # we don't have any date columns in these particular tables, so will treat everything else as string
            $vals_statement .= "'" . $this->escape( $$column_name ) . "'";
        }
        $cols_statement .= ') ';
        $vals_statement .= ') ';
        $statement = $cols_statement . $vals_statement;
        $this->db_run_query( $statement );
      }
    }
    echo "Saved $num_saved '" . $this->table_desc . "' records." . LINEBREAK;
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function transfer_institution() {  # transfer into the 'collect institution' table 

    $cols = $this->db_list_columns( $this->perm_postgres_table );
    $num_saved = 0;

    foreach( $this->uploaded_data as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $already_existed = FALSE;
      $insert_row = FALSE;

      # The main 'institution' table will have been pre-loaded into OpenOffice to provide an authority list.
      # We don't want to copy all these details, whether new or existing, into the 'collect institution' table
      # unless they are actually used in the contribution. Both new and existing details have to go into the
      # 'collect' table if they are used in the contribution, because of foreign keys linking back to the main table.

      $statement = "select institution_id from " . $this->proj_institution_tablename()
                 . " where institution_id = $institution_id";
      $already_existed = $this->db_select_one_value( $statement );

      if( ! $already_existed )
        $insert_row = TRUE;
      else { # see if this pre-existing institution was used in the contribution
        $insert_row = $this->repos_used_in_contribution( $institution_id );
      }

      if( $insert_row ) {
        $this->echo_safely( "Copying '" . $this->table_desc .  "' record into holding area: $institution_name" );
        echo LINEBREAK;
        $num_saved++;

        # Columns that need special handling
        $cols_statement = "insert into $this->perm_postgres_table ( upload_id, institution_id";
        $vals_statement = "values ( $this->upload_id, $institution_id";

        $cols_statement .= ', union_institution_id';
        if( ! $already_existed ) $union_institution_id = 'null';
        else $union_institution_id = $institution_id;
        $vals_statement .= ", $union_institution_id";

        # Remaining columns can all be done in a standard way
        foreach( $cols as $crow ) {
          extract( $crow, EXTR_OVERWRITE );

          if( $column_name == 'upload_id' )            continue;  # already done
          if( $column_name == 'institution_id' )       continue;
          if( $column_name == 'union_institution_id' ) continue;

          if( strlen( $$column_name ) == 0 ) continue; # no need to insert null values

          $cols_statement .= ', ' . $column_name;
          $vals_statement .= ', ';
          if( $is_numeric )
            $vals_statement .= $$column_name;
          else # we don't have any date columns in these particular tables, so will treat everything else as string
            $vals_statement .= "'" . $this->escape( $$column_name ) . "'";
        }
        $cols_statement .= ') ';
        $vals_statement .= ') ';
        $statement = $cols_statement . $vals_statement;
        $this->db_run_query( $statement );
      }
    }
    echo "Saved $num_saved '" . $this->table_desc . "' records." . LINEBREAK;
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function transfer_temp_table_to_perm() {  # transfer into the 'collect work' table 

    $cols = $this->db_list_columns( $this->perm_postgres_table );
    $num_saved = 0;

    foreach( $this->uploaded_data as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $num_saved++;
      echo "Saving '" . $this->table_desc .  "' record no. $num_saved ..." ;
      echo LINEBREAK;

      $cols_statement = "insert into $this->perm_postgres_table ( upload_id";
      $vals_statement = "values ( $this->upload_id";

      foreach( $cols as $crow ) {
        extract( $crow, EXTR_OVERWRITE );

        if( $column_name == 'upload_id' )  continue; # already done
        if( strlen( $$column_name ) == 0 ) continue; # no need to insert null values

        $cols_statement .= ', ' . $column_name;
        $vals_statement .= ', ';
        if( $is_numeric )
          $vals_statement .= $$column_name;
        else # we don't have any date columns in these particular tables, so will treat everything else as string
          $vals_statement .= "'" . $this->escape( $$column_name ) . "'";
      }
      $cols_statement .= ') ';
      $vals_statement .= ') ';
      $statement = $cols_statement . $vals_statement;
      $this->db_run_query( $statement );
    }
    echo "Saved $num_saved '" . $this->table_desc . "' records." . LINEBREAK;
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function transfer_work() {  # transfer into the 'collect work' table 

    $this->transfer_temp_table_to_perm();

    # Add 'source of data' field
    $statement = 'update ' . $this->proj_collect_work_tablename()
               . " set accession_code = '" . $this->escape( $this->upload_description ) . "'"
               . " where upload_id = $this->upload_id";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------
  function transfer_manifestation() {  # transfer into the 'collect manifestation' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_addressee() {  # transfer into the 'collect addressee' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_author() {  # transfer into the 'collect author' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_occupation_of_person() {  # transfer into the 'collect occupation of person' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_person_mentioned() {  # transfer into the 'collect person mentioned' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_place_mentioned() {  # transfer into the 'collect place mentioned' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_subject_of_work() {  # transfer into the 'collect subject of work' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_language_of_work() {  # transfer into the 'collect language of work' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_work_resource() {  # transfer into the 'collect work resource' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_person_resource() {  # transfer into the 'collect person resource' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_location_resource() {  # transfer into the 'collect location resource' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------
  function transfer_institution_resource() {  # transfer into the 'collect institution resource' table 
    $this->transfer_temp_table_to_perm();
  }
  #-----------------------------------------------------

  function history() {
    if( $this->parm_found_in_get( 'upload_id' )) {
      $this->upload_details();
    }
    else {
      $this->upload_list();
    }
  }
  #-----------------------------------------------------

  function upload_list() {

    $review_underway = FALSE;
    if( $this->review_underway()) {  # a supervisor reviewing contributions ready for ingest into the main database
      $review_underway = TRUE;

      $statement = $this->join_upload_and_status_tables() 
                 . ' and editable = 1 '
                 . " order by upload_id desc";
      $uploads = $this->db_select_into_array( $statement );
      $num_uploads = count( $uploads );
      if( $num_uploads == 0 ) {
        HTML::h3_start();
        echo 'No contributions are currently awaiting review.';
        HTML::h3_end();
        $this->link_to_contributed_work_search( $num_uploads );
        return;
      }
      else {
        HTML::h3_start();
        echo 'Click Review to accept or reject the data from a particular contribution.';
        HTML::h3_end();
        $this->link_to_contributed_work_search( $num_uploads );
        HTML::new_paragraph();
      }
    }
    else { # a normal user viewing their own contributions
      $this->username = $this->read_session_parm( 'username' );
      $this->person_name = $this->read_session_parm( 'person_name' );

      $statement = $this->join_upload_and_status_tables()
                 . " and upload_username = '" . $this->username . "'"
                 . ' order by upload_id desc';
      $uploads = $this->db_select_into_array( $statement );
      $num_uploads = count( $uploads );

      HTML::h3_start();
      echo "Uploads by $this->person_name ($this->username): $num_uploads";
      HTML::h3_end();

      HTML::italic_start();
      echo 'Click the Export button to export contribution data to a spreadsheet.';
      HTML::italic_end();
      HTML::new_paragraph();
      
      if( $num_uploads == 0 ) {
        HTML::new_paragraph();
        HTML::div_start( 'class="warning"' );
        echo 'Sorry, no history of existing contributions was found for this username.';
        HTML::div_end();
        HTML::new_paragraph();
        return;
      }
    }

    HTML::table_start( 'class="datatab spacepadded"' );

    HTML::tablerow_start();
    HTML::column_header( 'ID' );
    HTML::column_header( 'Date / time' );
    if( $this->review_underway()) HTML::column_header( 'Source of data' );
    if( $this->review_underway()) HTML::column_header( 'Contact email' );
    HTML::column_header( 'Works uploaded' );
    HTML::column_header( 'Accepted' );
    HTML::column_header( 'Rejected' );
    HTML::column_header( 'Status' );
    if( $review_underway ) HTML::column_header( 'Review' );
    HTML::column_header( 'Export' );
    HTML::tablerow_end();

    foreach( $uploads as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $display_parms = '';
      if( $editable ) $display_parms = 'class="highlight1"';

      HTML::tablerow_start();
      HTML::tabledata( $upload_id );

      HTML::tabledata_start();
      if( $review_underway ) {   # supervisor reviewing contributions ready for ingest into the main database
        echo $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
      }
      else { # ordinary user looking at their own data
        HTML::link_start( $href = $_SERVER['PHP_SELF'] . '?option=history&upload_id=' . $upload_id,
                          $title = 'View details of upload no. ' . $upload_id );
        echo $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
        HTML::link_end();
      }
      HTML::tabledata_end();

      if( $this->review_underway()) {
        HTML::tabledata( $upload_description );
        HTML::tabledata_start();
        HTML::link( $href = "mailto:$uploader_email", 
                    $displayed_text = $uploader_email, 
                    $title = 'Contact the contributor', 
                    $target = '_blank' );
        HTML::tabledata_end();
      }

      HTML::tabledata( $total_works, 'class="rightaligned"' );
      HTML::tabledata( $works_accepted, 'class="rightaligned"' );
      HTML::tabledata( $works_rejected, 'class="rightaligned"' );

      HTML::tabledata( $status_desc );

      if( $review_underway ) {
        HTML::tabledata_start( $display_parms );
        if( ! $editable ) echo 'Can be displayed in read-only mode: ';
        HTML::form_start( $class_name='upload', $method_name='upload_details' );
        HTML::hidden_field( 'upload_id', $upload_id );
        if( $editable ) {
          echo ' ';
          HTML::submit_button( 'review_upload_' . $upload_id . '_button', 'Review' );
        }
        else {
          HTML::submit_button( 'display_upload_' . $upload_id . '_button', 'Display' );
        }
        HTML::form_end();
        HTML::tabledata_end();
      }

      HTML::tabledata_start();
      $this->contributed_works_export_button( $upload_id, $verbose = FALSE );
      HTML::tabledata_end();

      HTML::tablerow_end();
    }


    HTML::table_end();
  }
  #-----------------------------------------------------

  function link_to_contributed_work_search( $num_awaiting_review ) {

    HTML::new_paragraph();
    HTML::form_start( 'contributed_work', 'db_search' );
    HTML::italic_start();
    if( $num_awaiting_review > 0 )
      echo 'You can also search and browse through works from earlier contributions that have already been reviewed.';
    else
      echo 'However, you can search and browse through the works from contributions that have already been reviewed.';
    HTML::italic_end();
    HTML::submit_button( 'search_button', 'Search' );
    HTML::form_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function upload_details( $header_only = FALSE, $suppress_status = FALSE ) {

    $contributing_username = NULL;
    $contributing_person_name = NULL;

    $review_underway = FALSE;
    if( $this->review_underway())   # a supervisor reviewing contributions ready for ingest into the main database
      $review_underway = TRUE;
    $this->review_underway = $review_underway;

    if( $review_underway ) { # a supervisor deciding whether to accept or reject the contribution
      $this->upload_id = $this->read_post_parm( 'upload_id' );
      $statement = $this->join_upload_and_status_tables() . " and upload_id = $this->upload_id" ;
      $uploads = $this->db_select_into_array( $statement );
      $num_uploads = count( $uploads );
      if( $num_uploads == 0 ) {
        echo "Contribution no. $this->upload_id does not exist.";
        return;
      }
      $contributing_username = $uploads[ 0 ][ 'upload_username' ];
      $tool_user_obj = new Tool_User( $this->db_connection );
      $contributing_person_name = $tool_user_obj->look_up_person_name( $contributing_username );
    }

    else {  # a normal user looking at their own data
      
      $this->username = $this->read_session_parm( 'username' );
      $this->person_name = $this->read_session_parm( 'person_name' );

      $contributing_username = $this->username;
      $contributing_person_name = $this->person_name;

      $this->upload_id = $this->read_get_parm( 'upload_id' );

      $statement = $this->join_upload_and_status_tables()
                 . " and upload_username = '" . $this->username . "'"
                 . " and upload_id = $this->upload_id" ;
      $uploads = $this->db_select_into_array( $statement );
      $num_uploads = count( $uploads );
      
      if( $num_uploads == 0 ) {
        HTML::new_paragraph();
        HTML::div_start( 'class="warning"' );
        echo "Contribution no. $this->upload_id does not exist or was not uploaded by user '"
             . $this->username . "'.";
        HTML::div_end();
        HTML::new_paragraph();
        return;
      }
    }

    $row = $uploads[ 0 ];
    extract( $row, EXTR_OVERWRITE ); # copy the data into simple variables for use now
    foreach( $row as $colname => $value ) { # copy the data into properties for use in other functions
      $this->$colname = $value;
    }

    echo '<style type="text/css">' . NEWLINE;
    echo ' h5 {'                   . NEWLINE; 
    echo '   display: inline;'     . NEWLINE;
    echo '   margin-top: 15px;'    . NEWLINE;
    echo '   margin-bottom: 5px;'  . NEWLINE;
    echo '   font-size: 10pt;'     . NEWLINE;
    echo '   font-weight: bold;'   . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo ' td.label {'             . NEWLINE; 
    echo '   width: 100px;'        . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo ' div.head_plus_nav h4 {' . NEWLINE; 
    echo '   display: inline;'     . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo '</style>'                . NEWLINE;

    HTML::h3_start();
    echo "Contribution by $contributing_person_name ($contributing_username) uploaded " 
         . $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
    HTML::h3_end();

    if( $review_underway ) {
      echo 'Contact details: ';
      HTML::link( $href = "mailto:$uploader_email", 
                  $displayed_text = $uploader_email, 
                  $title = 'Contact the contributor', 
                  $target = '_blank' );
      HTML::new_paragraph();
    }

    if( ! $suppress_status ) {
      HTML::div_start( 'class="highlight2 bold"' );
      echo 'Status: ' . $status_desc;
      echo ' | ';
      echo 'Number of works uploaded: ' . $total_works;
      echo ' | ';
      echo 'Accepted: ' . $works_accepted;
      echo ' | ';
      echo 'Rejected: ' . $works_rejected;
      HTML::div_end();
    }

    HTML::new_paragraph();
    if( $header_only ) return;

    if( $review_underway && $editable ) {
      HTML::div_start( 'class="buttonrow"' );

      HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
      echo 'Back to list of contributions: ';
      HTML::submit_button( 'back_to_upload_list_button', 'Back' );
      echo SPACE;
      HTML::form_end();


      HTML::form_start( $class_name = 'review', $method_name = 'accept_all_works' );
      echo 'Accept entire contribution: ';
      HTML::hidden_field( 'upload_id', $this->upload_id );
      HTML::submit_button( 'accept_all_button', 'Accept all' );
      echo SPACE;
      HTML::form_end();

      HTML::form_start( $class_name = 'review', $method_name = 'reject_all_works' );
      echo 'Reject entire contribution: ';
      HTML::hidden_field( 'upload_id', $this->upload_id );
      HTML::submit_button( 'reject_all_button', 'Reject all' );
      HTML::form_end();

      HTML::linebreak( 'class="clearleft"' );
      HTML::div_end();

      HTML::italic_start();
      echo 'Note: confirmation will be required before Accept/Reject of entire contribution.';
      HTML::italic_end();
    }
    else {
      if( $review_underway ) { # supervisor, came in via EMLO-edit
        HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
        echo 'Back to list of contributions: ';
        HTML::submit_button( 'back_to_upload_list_button', 'Back' );
        HTML::form_end();
      }
      else { # normal user, came in via collection tool
        HTML::link_start( $href = $_SERVER['PHP_SELF'] . '?option=history',
                          $title = 'View list of contributions' );
      }
    }

    $this->display_works_uploaded();
    $this->display_people_uploaded();
    $this->display_places_uploaded();
    $this->display_repos_uploaded();

    HTML::new_paragraph();
    HTML::italic_start();
    echo 'End of data uploaded ' . $this->postgres_date_to_dd_mm_yyyy( $this->upload_timestamp )
         . ' by ' . $contributing_person_name;
    HTML::italic_end();
    HTML::new_paragraph();
    HTML::anchor( 'end_of_upload_details_page' );
  }
  #-----------------------------------------------------

  function display_people_uploaded() {

    HTML::anchor( 'people_section' );
    HTML::h4_start();
    echo 'People and groups';
    HTML::h4_end();

    $statement = 'select * from ' . $this->proj_collect_person_tablename()
               . " where upload_id = $this->upload_id"
               . ' and iperson_id >= ' . IDS_CREATED_IN_TOOL_START # just show newly-created people
               . ' order by primary_name';
    $people = $this->db_select_into_array( $statement );
    $num_people = count( $people );

    HTML::new_paragraph();

    if( $num_people == 0 ) {
      echo 'No details of new people or groups were uploaded in this contribution.';
      return;
    }

    if( $num_people == 1 )
      echo 'Details of one new person or group were uploaded in this contribution.';
    else
      echo 'Details of ' . $num_people . ' new people or groups were uploaded in this contribution.';
    HTML::new_paragraph();

    $current_person = 0;
    foreach( $people as $row ) {
      extract( $row, EXTR_OVERWRITE );
      foreach( $row as $colname => $value ) {
        $this->$colname = $value;
      }
      $current_person++;

      HTML::new_paragraph();
      HTML::anchor( 'person' . $current_person );

      HTML::h5_start();
      echo "Person/group $current_person of $num_people";
      HTML::h5_end();

      HTML::span_start( 'class="widespaceonleft"' );
      if( $current_person > 1 ) {
        $prev_person = intval( $current_person ) - 1;
        HTML::link( '#person' . $prev_person, 'Previous person', 'Previous person' );
      }
      if( $current_person < $num_people && $current_person > 1 ) 
        echo ' | ';
      if( $current_person < $num_people ) {
        $next_person = intval( $current_person ) + 1;
        HTML::link( '#person' . $next_person, 'Next person', 'Next person' );
      }
      HTML::span_end();

      if( $current_person < $num_people || $current_person > 1 ) echo ' | ';
      $this->link_to_other_sections( $this_section = 'people' );
      echo LINEBREAK;

      $this->display_one_person_uploaded();
    }
  }
  #-----------------------------------------------------

  function display_one_person_uploaded() {

    HTML::div_start( 'class="queryresults"' );
    HTML::table_start( 'width="100%"' );
    HTML::tablerow_start();
    HTML::tabledata( 'Primary name', 'class="label"' );
    HTML::tabledata_start();
    $this->echo_safely( $this->primary_name );
    HTML::tabledata_end();
    HTML::tablerow_end();

    if( $this->alternative_names > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Alternative name(s)', 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely( $this->alternative_names );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    if( $this->gender > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Gender', 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely( $this->gender );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    if( $this->is_organisation == '0' ) $this->is_organisation = '';
    if( $this->is_organisation > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Organisation?', 'class="label"' );
      HTML::tabledata_start();
      echo 'Yes. ';
      if( $this->organisation_type ) {
        $statement = 'select org_type_desc from ' . $this->proj_org_type_tablename()
                   . " where org_type_id = $this->organisation_type";
        $desc = $this->db_select_one_value( $statement );
        echo 'Type of organisation: ' . $desc;
      }
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    if( $this->roles_or_titles > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Roles or titles', 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely( $this->roles_or_titles );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    $statement = 'select r.* from ' . $this->proj_role_category_tablename() .  ' r, '
               . $this->proj_collect_occupation_of_person_tablename() . ' p'
               . ' where r.role_category_id = p.occupation_id'
               . " and p.upload_id = $this->upload_id"
               . " and p.iperson_id = $this->iperson_id"
               . ' order by role_category_desc';
    $occs = $this->db_select_into_array( $statement );
    $num_occs = count( $occs );
    if( $num_occs > 0 ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Professional categories', 'class="label"' );
      HTML::tabledata_start();
      if( $num_occs > 1 ) HTML::ulist_start();
      foreach( $occs as $occ ) {
        extract( $occ, EXTR_OVERWRITE );
        if( $num_occs > 1 ) HTML::listitem_start();
        echo $role_category_desc;
        if( $num_occs > 1 ) HTML::listitem_end();
      }
      if( $num_occs > 1 ) HTML::ulist_end();
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    if( $this->date_of_birth_year || $this->date_of_birth2_year ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Date of birth', 'class="label"' );
      HTML::tabledata_start();
      echo $this->date_of_birth_year;
      if( $this->date_of_birth2_year || $this->date_of_birth_is_range ) echo '&ndash;';
      echo $this->date_of_birth2_year;
      echo LINEBREAK;
      $flag_fields = array( 'date_of_birth_is_range', 'date_of_birth_inferred', 
                           'date_of_birth_uncertain', 'date_of_birth_approx' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with date of birth', TRUE );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }        

    if( $this->date_of_death_year || $this->date_of_death2_year ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Date of death', 'class="label"' );
      HTML::tabledata_start();
      echo $this->date_of_death_year;
      if( $this->date_of_death2_year || $this->date_of_death_is_range ) echo '&ndash;';
      echo $this->date_of_death2_year;
      echo LINEBREAK;
      $flag_fields = array( 'date_of_death_is_range', 'date_of_death_inferred', 
                           'date_of_death_uncertain', 'date_of_death_approx' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with date of death', TRUE );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }        

    if( $this->flourished_year || $this->flourished2_year ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Flourished', 'class="label"' );
      HTML::tabledata_start();
      echo $this->flourished_year;
      if( $this->flourished2_year || $this->flourished_is_range ) echo '&ndash;';
      echo $this->flourished2_year;
      HTML::tabledata_end();
      HTML::tablerow_end();
    }        

    if( $this->notes_on_person > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Publicly visible notes on person/group', 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely( $this->notes_on_person );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    if( $this->editors_notes > '' ) {
      HTML::tablerow_start();
      HTML::tabledata( "Project editors' notes", 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely( $this->editors_notes );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }

    $this->set_related_resources_for_display( $table_name = $this->proj_collect_person_resource_tablename(), 
                                              $id_column_name = 'iperson_id', 
                                              $id_value = $this->iperson_id );
    $this->display_simple_field( 'related_resources', 'Related resources' );

    HTML::table_end();
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function display_places_uploaded() {

    HTML::anchor( 'places_section' );
    HTML::div_start( 'class="head_plus_nav"' );
    HTML::h4_start();
    echo 'Places';
    HTML::h4_end();

    HTML::span_start( 'class="widespaceonleft"' );
    $this->link_to_other_sections( $this_section = 'places' );
    HTML::span_end();
    HTML::div_end();
    HTML::new_paragraph();

    $statement = 'select * from ' . $this->proj_collect_location_tablename()
               . " where upload_id = $this->upload_id"
               . ' and location_id >= ' . IDS_CREATED_IN_TOOL_START # just show newly-created places
               . ' order by location_name';
    $places = $this->db_select_into_array( $statement );
    $num_places = count( $places );

    if( $num_places == 0 ) {
      echo 'No details of new places were uploaded in this contribution. ';
      return;
    }

    elseif( $num_places == 1 ) {
      echo 'Details of one new place were uploaded in this contribution:';
      $this->display_one_place( $places[ 0 ] );
      return;
    }

    echo 'Details of ' . $num_places . ' new places were uploaded in this contribution.';
    HTML::new_paragraph();

    $i = 0;
    foreach( $places as $row ) {
      $i++;
      HTML::h5_start();
      echo "Place $i of $num_places";
      HTML::h5_end();
      extract( $row, EXTR_OVERWRITE );
      $this->display_one_place( $row );
    }
    HTML::new_paragraph();
  }
  #-----------------------------------------------------
  function display_one_place( $place ) {

    if( ! $place[ 'location_id' ] ) die( 'Invalid input while displaying places uploaded.' );

    HTML::div_start( 'class="queryresults"' );
    HTML::table_start();

    foreach( $place as $fieldname => $value ) {

      $this->$fieldname = $value;
      $display_it = FALSE;

      switch( $fieldname ) {
        case 'location_name':
        case 'notes_on_place':
        case 'editors_notes':
        case 'location_synonyms':
        case 'latitude':
        case 'longitude':
          $display_it = TRUE;
          break;
      }

      if( ! $display_it ) continue;
      $label = $this->db_get_default_column_label( $fieldname );

      $this->display_simple_field( $fieldname, $label );
    }

    $this->set_related_resources_for_display( $table_name = $this->proj_collect_location_resource_tablename(), 
                                              $id_column_name = 'location_id', 
                                              $id_value = $this->location_id );
    $this->display_simple_field( 'related_resources', 'Related resources' );

    HTML::table_end();
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function display_repos_uploaded() {

    HTML::anchor( 'repositories_section' );
    HTML::div_start( 'class="head_plus_nav"' );
    HTML::h4_start();
    echo 'Repositories';
    HTML::h4_end();

    HTML::span_start( 'class="widespaceonleft"' );
    $this->link_to_other_sections( $this_section = 'repositories' );
    HTML::span_end();
    HTML::div_end();
    HTML::new_paragraph();

    $statement = 'select * from ' . $this->proj_collect_institution_tablename()
               . " where upload_id = $this->upload_id"
               . ' and institution_id >= ' . IDS_CREATED_IN_TOOL_START # just show newly-created repositories
               . ' order by institution_name';
    $repos = $this->db_select_into_array( $statement );
    $num_repos = count( $repos );

    if( $num_repos == 0 ) {
      echo 'No details of new repositories were uploaded in this contribution. ';
      return;
    }

    elseif( $num_repos == 1 ) {
      echo 'Details of one new repository were uploaded in this contribution:';
      HTML::new_paragraph();
      $this->display_one_repository( $repos[ 0 ] );
      HTML::new_paragraph();
      return;
    }

    echo 'Details of ' . $num_repos . ' new repositories were uploaded in this contribution.';
    HTML::new_paragraph();

    HTML::ulist_start();
    foreach( $repos as $row ) {
      extract( $row, EXTR_OVERWRITE );
      HTML::listitem_start();
      $this->echo_safely( $institution_name );
      HTML::listitem_end();
    }
    HTML::ulist_end();

    $i = 0;
    foreach( $repos as $row ) {
      $i++;
      HTML::h5_start();
      echo "Repository $i of $num_repos";
      HTML::h5_end();
      extract( $row, EXTR_OVERWRITE );
      $this->display_one_repository( $row );
    }
    HTML::new_paragraph();
  }
  #-----------------------------------------------------
  function display_one_repository( $repository ) {

    if( ! $repository[ 'institution_id' ] ) die( 'Invalid input while displaying repositories uploaded.' );

    HTML::div_start( 'class="queryresults"' );
    HTML::table_start();

    foreach( $repository as $fieldname => $value ) {

      $this->$fieldname = $value;
      $display_it = FALSE;

      switch( $fieldname ) {
        case 'institution_name':
        case 'institution_city':
        case 'institution_country':
          $display_it = TRUE;
          break;
      }

      if( ! $display_it ) continue;
      $label = $this->db_get_default_column_label( $fieldname );

      $this->display_simple_field( $fieldname, $label );
    }

    $this->set_related_resources_for_display( $table_name = $this->proj_collect_institution_resource_tablename(), 
                                              $id_column_name = 'institution_id', 
                                              $id_value = $this->institution_id );
    $this->display_simple_field( 'related_resources', 'Related resources' );
    HTML::table_end();
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------
  function display_works_uploaded() {

    HTML::anchor( 'works_section' );
    HTML::h4_start();
    echo 'Works';
    HTML::h4_end();

    $cstatement = 'select count(*) from ' . $this->proj_collect_work_tablename()
               . " where upload_id = $this->upload_id";

    $num_works = $this->db_select_one_value( $cstatement );

    if( $num_works == 0 ) {
      echo 'No details of new works were uploaded in this contribution.' . LINEBREAK;
      return;
    }

    if( $num_works == 1 )
      echo 'Details of one new work were uploaded in this contribution.';
    else
      echo 'Details of ' . $num_works . ' new works were uploaded in this contribution.';
    $this->contributed_works_export_button( $this->upload_id, $verbose = TRUE );
    HTML::new_paragraph();

    $current_work = 0;
    $LIMIT = 100;

    while ( $current_work < $num_works ) {
      $statement = 'select * from ' . $this->proj_collect_work_tablename()
                . " where upload_id = $this->upload_id order by iwork_id"
                . " LIMIT $LIMIT OFFSET $current_work ";

      $works = $this->db_select_into_array( $statement );

      foreach( $works as $row ) {
        extract( $row, EXTR_OVERWRITE );
        foreach( $row as $colname => $value ) {
          $this->$colname = $value;
        }
        $current_work++;


        HTML::horizontal_rule( 'style="background-color:#303030;color:#303030;height:3px;"');
        HTML::new_paragraph();
        HTML::anchor( 'work' . $current_work );

        HTML::h4_start();
        echo "Work $current_work of $num_works";
        HTML::h4_end();

        HTML::span_start( 'class="widespaceonleft"' );
        if( $current_work > 1 ) {
          $prev_work = intval( $current_work ) - 1;
          HTML::link( '#work' . $prev_work, 'Previous work', 'Previous work' );
        }
        if( $current_work < $num_works && $current_work > 1 ) 
          echo ' | ';
        if( $current_work < $num_works ) {
          $next_work = intval( $current_work ) + 1;
          HTML::link( '#work' . $next_work, 'Next work', 'Next work' );
        }
        HTML::span_end();
        if( $current_work < $num_works || $current_work > 1 ) echo ' | ';
        $this->link_to_other_sections( $this_section = 'works' );
        echo LINEBREAK;

        $this->display_current_work();
      }
    }
  }
  #-----------------------------------------------------
  function display_current_work() {

    HTML::div_start( 'class="queryresults"' );
    HTML::table_start( 'width="100%"' );

    $this->accept_or_reject_work_buttons();
    $this->display_date_of_work();
    $this->display_authors();
    $this->display_origin();
    $this->display_addressees();
    $this->display_destination();
    $this->display_languages_of_work();
    $this->display_simple_field( 'incipit', 'Incipit' );
    $this->display_simple_field( 'excipit', 'Explicit' );
    $this->display_simple_field( 'abstract', 'Abstract' );
    $this->display_simple_field( 'keywords', 'Keywords' );
    $this->display_subjects_of_work();
    $this->display_people_mentioned();
    $this->display_places_mentioned();
    $this->display_manifestations();
    $this->display_simple_field( 'notes_on_letter', 'General notes' );
    $this->display_simple_field( 'editors_notes', "Project editors' notes" );

    $this->set_related_resources_for_display( $table_name = $this->proj_collect_work_resource_tablename(), 
                                              $id_column_name = 'iwork_id', 
                                              $id_value = $this->iwork_id );
    $this->display_simple_field( 'related_resources', 'Related resources' );

    HTML::table_end();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function accept_or_reject_work_buttons() {

    $statement = 'select status_desc as work_status_desc, editable as work_editable from ' 
               . $this->proj_collect_status_tablename()
               . " where status_id = $this->upload_status";
    $this->db_select_into_properties( $statement );

    HTML::tablerow_start();
    if( $this->review_underway && $this->editable ) 
      $tabledata_desc = 'Status and possible actions';
    else
      $tabledata_desc = 'Status';
    HTML::tabledata( LINEBREAK . $tabledata_desc, 'class="label"' );

    HTML::tabledata_start();
    HTML::bold_start();
    echo LINEBREAK . $this->work_status_desc; 
    HTML::bold_end();
    if( $this->union_iwork_id ) echo ' (ID: ' . $this->union_iwork_id . ')';
	 if( $this->iwork_id ) echo ' (Collect ID: ' . $this->iwork_id . ')';
    echo LINEBREAK;

    HTML::div_start( 'class="buttonrow"' );

    if( $this->review_underway && $this->editable && $this->work_editable ) {

      HTML::form_start( $class_name = "review", $method_name = "accept_one_work" );
      HTML::hidden_field( 'upload_id', $this->upload_id );
      HTML::hidden_field( 'iwork_id', $this->iwork_id );
      HTML::submit_button( 'accept_work_' . $this->iwork_id, 'Accept' );
      echo SPACE;
      HTML::form_end();
      
      HTML::form_start( $class_name = "review", $method_name = "reject_one_work" );
      HTML::hidden_field( 'upload_id', $this->upload_id );
      HTML::hidden_field( 'iwork_id', $this->iwork_id );
      HTML::submit_button( 'reject_work_' . $this->iwork_id, 'Reject' );
      HTML::form_end();

      HTML::linebreak( 'class="clearleft"' );

      HTML::italic_start();
      echo 'Note: confirmation will be required before Accept/Reject of work.';
    }

    HTML::div_end();
    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_date_of_work() {

    $date_entered = FALSE;

    $relevant_fields = array( 'date_of_work_std_year', 'date_of_work_std_month', 'date_of_work_std_day',
                              'date_of_work2_std_year', 'date_of_work_std2_month', 'date_of_work2_std_day',
                              'date_of_work_std_is_range', 'date_of_work_inferred', 'date_of_work_uncertain',
                              'date_of_work_approx', 'date_of_work_as_marked', 'original_calendar' );

    foreach( $relevant_fields as $field ) {
      if( $this->$field == '0' ) $this->$field = '';
      if( strlen( $this->$field ) > 0 ) {
        $date_entered = TRUE;
        break;
      }
    }

    if( ! $date_entered ) return;

    HTML::tablerow_start();
    HTML::tabledata( 'Date of work', 'class="label"' );
    HTML::tabledata_start();
    
    $start_date_entered = FALSE;
    $end_date_entered = FALSE;

    if( $this->date_of_work_std_day || $this->date_of_work_std_month || $this->date_of_work_std_year ) {
      $start_date_entered = TRUE;
      if( $this->date_of_work_std_day )
       $this->echo_safely(  $this->date_of_work_std_day );
      else
        echo '??';
      echo ' ';
      
      if( $this->date_of_work_std_month )
        echo $this->get_month_name_from_number( $this->date_of_work_std_month );
      else
        echo '???';
      echo ' ';
      
      if( $this->date_of_work_std_year )
        echo $this->date_of_work_std_year;
      else
        echo '????';
      echo ' ';
    }

    if( $this->date_of_work2_std_day || $this->date_of_work2_std_month || $this->date_of_work2_std_year ) {
      $end_date_entered = TRUE;
      if( $start_date_entered )
        echo ' to ';
      else
        echo 'To ';
      
      if( $this->date_of_work2_std_day )
        echo $this->date_of_work2_std_day;
      else
        echo '??';
      echo ' ';
      
      if( $this->date_of_work2_std_month )
        echo $this->get_month_name_from_number( $this->date_of_work2_std_month );
      else
        echo '???';
      echo ' ';
      
      if( $this->date_of_work2_std_year )
        echo $this->date_of_work2_std_year;
      else
        echo '????';
      echo ' ';
    }
    if( $start_date_entered || $end_date_entered ) echo LINEBREAK;

    if( $this->date_of_work_as_marked ) {
     $this->echo_safely( 'As marked: ' . $this->date_of_work_as_marked );
      echo LINEBREAK;
    }

    if( $this->original_calendar ) {
      echo 'Original calendar: ';
      if( $this->original_calendar == 'G' )
        echo 'Gregorian';
      elseif( $this->original_calendar == 'J' )
        echo 'Julian'; // This will switch to "JJ" after accepted, see review.php
      elseif( $this->original_calendar == 'JJ' )
        echo 'Julian (January year start)' ;
      elseif( $this->original_calendar == 'JM' )
        echo 'Julian (March year start)' ;

      echo LINEBREAK;
    }

    $checkboxes = array( 'date_of_work_std_is_range', 'date_of_work_inferred', 
                         'date_of_work_uncertain', 'date_of_work_approx' );
    $this->display_flags( $flag_fields = $checkboxes, $flags_heading = 'Issues with date of work' );

    if( $this->notes_on_date_of_work ) {
     $this->echo_safely( 'Notes on date: ' . $this->notes_on_date_of_work );
      echo LINEBREAK;
    }

    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_flags( $flag_fields = array(), $flags_heading = NULL, $estimated_range_only = FALSE ) {

    $flags_entered = FALSE;
    foreach( $flag_fields as $flag_field ) {
      if( $this->$flag_field ) {
        $flags_entered = TRUE;
        break;
      }
    }
    if( $flags_entered ) {
      $flag_string = '';
      foreach( $flag_fields as $flag_field ) {
        if( $this->$flag_field ) {
          if( $flag_string > '' ) $flag_string .= ', ';
          $last_underscore = strpos( strrev( $flag_field ), '_' );
          $label = substr( $flag_field, 0 - $last_underscore );
          if( $label == 'approx' ) $label = 'approximate';
          if( $label == 'range' ) {
            if( $estimated_range_only )
              $label = 'estimated range';
            else
              $label = 'estimated or known range';
          }
          $flag_string .= $label;
        }
      }
      if( $flags_heading ) echo $flags_heading . ': ';
      echo $flag_string . LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function display_authors() {

    $this->display_people_linked_to_work( $this->proj_collect_author_of_work_tablename(), 'Author(s)',
                                          'notes_on_authors', 'authors_as_marked', 'authors' );
  }
  #-----------------------------------------------------

  function display_addressees() {

    $this->display_people_linked_to_work( $this->proj_collect_addressee_of_work_tablename(), 'Addressee(s)',
                                          'notes_on_addressees', 'addressees_as_marked', 'addressees' );
  }
  #-----------------------------------------------------

  function display_people_mentioned() {

    $this->display_people_linked_to_work( $this->proj_collect_person_mentioned_in_work_tablename(), 'People mentioned',
                                          'notes_on_people_mentioned' );
  }
  #-----------------------------------------------------

  function display_people_linked_to_work( $link_to_table = NULL, $label = NULL,
                                           $notes_field = NULL, $as_marked_field = NULL, $flag_fields_start = NULL ) {

    if( ! $link_to_table ) return;

    $statement = 'select p.primary_name, p.iperson_id from ' . $this->proj_collect_person_tablename() . " p, $link_to_table x"
               . ' where p.iperson_id = x.iperson_id'
               . ' and p.upload_id = x.upload_id'
               . " and x.iwork_id = $this->iwork_id"
               . " and x.upload_id = $this->upload_id"
               . ' order by primary_name';
   

    $people = $this->db_select_into_array( $statement );
    $num_people = count( $people );

    $person_data_entered = FALSE;
    if( $num_people > 0 )
      $person_data_entered = TRUE;
    elseif( $as_marked_field ) {
      if( $this->$as_marked_field ) 
        $person_data_entered = TRUE;
    }
    if( ! $person_data_entered ) return;

    HTML::tablerow_start();
    HTML::tabledata( $label, 'class="label"' );
    HTML::tabledata_start();

    if( $num_people > 1 ) HTML::ulist_start();
    foreach( $people as $person ) {

      if( $num_people > 1 ) HTML::listitem_start();
      extract( $person, EXTR_OVERWRITE );

      $this->echo_safely( $primary_name );
      if( $iperson_id >= IDS_CREATED_IN_TOOL_START ) {
        HTML::italic_start();
        echo "  <span style=\"color:#222\">[new record]</span>";
        HTML::italic_end();
      }
      echo LINEBREAK;

      if( $as_marked_field ) {
        if( $this->$as_marked_field ) {
          echo 'As marked: ';
          $this->echo_safely( $this->$as_marked_field );
          echo LINEBREAK;
        }
      }

      if( $num_people > 1 ) HTML::listitem_end();
    }
    if( $num_people > 1 ) HTML::ulist_end();

    if( $flag_fields_start ) {
      $flag_fields = array ( $flag_fields_start . '_inferred', $flag_fields_start . '_uncertain' );
      $flags_heading = 'Issues with ' . strtolower( $label );
      $this->display_flags( $flag_fields, $flags_heading );
    }

    if( $notes_field ) {
      if( $this->$notes_field ) {
        echo 'Notes on ' . strtolower( $label ) . ': ';
        $this->echo_safely( $this->$notes_field );
        echo LINEBREAK;
      }
    }

    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_origin() {

    if( $this->origin_id || $this->origin_as_marked ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Origin', 'class="label"' );
      HTML::tabledata_start();
      if( $this->origin_id ) {
        if( $this->origin_id >= IDS_CREATED_IN_TOOL_START ) { # newly created
          $statement = 'select location_name from ' . $this->proj_collect_location_tablename()
                     . " where upload_id = $this->upload_id and location_id = $this->origin_id";
          $location_name = $this->db_select_one_value( $statement );
        }
        else {
          $statement = 'select location_name from ' . $this->proj_location_tablename()
                     . " where location_id = $this->origin_id";
          $location_name = $this->db_select_one_value( $statement );
        }
        $this->echo_safely( $location_name );
			if( $this->origin_id >= IDS_CREATED_IN_TOOL_START ) {
				HTML::italic_start();
				echo " <span style=\"color:#222\">[new record]</span>";
				HTML::italic_end();
			}
        echo LINEBREAK;
      }

      if( $this->origin_as_marked ) {
        echo 'As marked: ';
        $this->echo_safely( $this->origin_as_marked );
        echo LINEBREAK;
      }

      $flag_fields = array( 'origin_inferred', 'origin_uncertain' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with origin', TRUE );

      HTML::tabledata_end();
      HTML::tablerow_end();
    }
  }
  #-----------------------------------------------------

  function display_destination() {

    if( $this->destination_id || $this->destination_as_marked ) {
      HTML::tablerow_start();
      HTML::tabledata( 'Destination', 'class="label"' );
      HTML::tabledata_start();
      if( $this->destination_id ) {
        if( $this->destination_id >= IDS_CREATED_IN_TOOL_START ) { # newly created
          $statement = 'select location_name from ' . $this->proj_collect_location_tablename()
                     . " where upload_id = $this->upload_id and location_id = $this->destination_id";
          $location_name = $this->db_select_one_value( $statement );
        }
        else {
          $statement = 'select location_name from ' . $this->proj_location_tablename()
                     . " where location_id = $this->destination_id";
          $location_name = $this->db_select_one_value( $statement );
        }
        $this->echo_safely( $location_name );
			if( $this->destination_id >= IDS_CREATED_IN_TOOL_START ) {
				HTML::italic_start();
				echo ' <span style="color:#555">[new record]</span>';
				HTML::italic_end();
			}
        echo LINEBREAK;
      }

      if( $this->destination_as_marked ) {
        echo 'As marked: ';
        $this->echo_safely( $this->destination_as_marked );
        echo LINEBREAK;
      }

      $flag_fields = array( 'destination_inferred', 'destination_uncertain' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with destination', TRUE );

      HTML::tabledata_end();
      HTML::tablerow_end();
    }
  }
  #-----------------------------------------------------

  function display_subjects_of_work() {

    $statement = 'select s.subject_desc from ' . $this->proj_subject_tablename() . ' s,'
               . $this->proj_collect_subject_of_work_tablename() . ' w'
               . ' where s.subject_id = w.subject_id'
               . " and w.iwork_id = $this->iwork_id"
               . " and w.upload_id = $this->upload_id"
               . ' order by subject_desc';
    $subjs = $this->db_select_into_array( $statement );
    $num_subjs = count( $subjs );
    if( $num_subjs == 0 ) return;

    HTML::tablerow_start();
    HTML::tabledata( 'Subject(s)', 'class="label"' );
    HTML::tabledata_start();

    if( $num_subjs > 1 ) HTML::ulist_start();
    foreach( $subjs as $subj ) {
      if( $num_subjs > 1 ) HTML::listitem_start();
      extract( $subj, EXTR_OVERWRITE );
      $this->echo_safely( $subject_desc );
      if( $num_subjs > 1 ) HTML::listitem_end();
    }
    if( $num_subjs > 1 ) HTML::ulist_end();
    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_languages_of_work() {

    $statement = 'select i.language_name from iso_639_language_codes i,'
               . $this->proj_collect_language_of_work_tablename() . ' w'
               . ' where i.code_639_3 = w.language_code'
               . " and w.iwork_id = $this->iwork_id"
               . " and w.upload_id = $this->upload_id"
               . ' order by language_name';
    $langs = $this->db_select_into_array( $statement );
    $num_langs = count( $langs );
    $language_of_work  = trim( $this->language_of_work  );
    if( $num_langs == 0 && ! $language_of_work ) return;
    $this->display_simple_field( '', 'Language(s)' );

    HTML::tablerow_start();
    HTML::tabledata( 'Language(s)', 'class="label"' );
    HTML::tabledata_start();

    if( $num_langs > 1 ) HTML::ulist_start();
    foreach( $langs as $lang ) {
      if( $num_langs > 1 ) HTML::listitem_start();
      extract( $lang, EXTR_OVERWRITE );
      $this->echo_safely( $language_name );
      if( $num_langs > 1 ) HTML::listitem_end();
    }
    if( $num_langs > 1 ) HTML::ulist_end();

    if( $language_of_work ) {
      if( $num_langs == 1 ) echo LINEBREAK;
      $this->echo_safely( $language_of_work );
    }

    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_places_mentioned() {

    $statement = 'select l.location_name from ' . $this->proj_collect_location_tablename() . ' l, '
               . $this->proj_collect_place_mentioned_in_work_tablename() . ' w '
               . ' where l.location_id = w.location_id'
               . ' and l.upload_id = w.upload_id'
               . " and w.iwork_id = $this->iwork_id"
               . " and w.upload_id = $this->upload_id"
               . ' order by location_name';
    $places = $this->db_select_into_array( $statement );
    $num_places = count( $places );
    if( $num_places == 0 ) return;

    HTML::tablerow_start();
    HTML::tabledata( 'Places mentioned', 'class="label"' );
    HTML::tabledata_start();

    if( $num_places > 1 ) HTML::ulist_start();
    foreach( $places as $place ) {
      if( $num_places > 1 ) HTML::listitem_start();
      extract( $place, EXTR_OVERWRITE );
      $this->echo_safely( $location_name );
      if( $num_places > 1 ) HTML::listitem_end();
    }
    if( $num_places > 1 ) HTML::ulist_end();
    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function display_manifestations() {

    $statement = 'select m.*, d.document_type_desc from ' 
               . $this->proj_collect_manifestation_tablename() . ' m, '
               . $this->proj_document_type_tablename() . ' d '
               . ' where m.manifestation_type = d.document_type_code'
               . " and m.iwork_id = $this->iwork_id"
               . " and m.upload_id = $this->upload_id"
               . ' order by manifestation_id';
    $manifs = $this->db_select_into_array( $statement );
    $num_manifs = count( $manifs );
    if( $num_manifs == 0 ) return;

    HTML::tablerow_start();
    HTML::tabledata( 'Repositories and versions', 'class="label"' );
    HTML::tabledata_start();

    if( $num_manifs > 1 ) HTML::ulist_start();
    foreach( $manifs as $manif ) {
      if( $num_manifs > 1 ) HTML::listitem_start();
      extract( $manif, EXTR_OVERWRITE );

      echo 'Document type: ';
      $this->echo_safely( $document_type_desc );
      echo LINEBREAK;

      if( $repository_id ) {
        $statement = 'select institution_name from ' . $this->proj_collect_institution_tablename()
                   . " where upload_id = $this->upload_id and institution_id = $repository_id";
        $repos_name = $this->db_select_one_value( $statement );
        echo 'Repository: ';
        $this->echo_safely( $repos_name );
        echo LINEBREAK;
      }

      if( $id_number_or_shelfmark ) {
        echo 'Shelfmark: ';
        $this->echo_safely( $id_number_or_shelfmark );
        echo LINEBREAK;
      }

      if( $printed_edition_details ) {
        echo 'Printed edition details: ';
        $this->echo_safely( $printed_edition_details );
        echo LINEBREAK;
      }

      if( $image_filenames ) {
        echo 'Image URLs and filenames: ';
        $this->echo_safely( $image_filenames );
        echo LINEBREAK;
      }

      if( $manifestation_notes ) {
        echo 'Notes on document: ';
        $this->echo_safely( $manifestation_notes );
        echo LINEBREAK;
      }

      if( $num_manifs > 1 ) HTML::listitem_end();
    }
    if( $num_manifs > 1 ) HTML::ulist_end();
    HTML::tabledata_end();
    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function link_to_other_sections( $this_section = NULL ) {

    $sections = array( 'works', 'people', 'places', 'repositories' );
    $i = 0;
    foreach( $sections as $section ) {
      if( $section == $this_section ) continue;
      if( $i > 0 ) echo ' | ';
      $i++;
      $section_link = '#' . $section . '_section';
      HTML::link( $section_link, ucfirst($section), ucfirst($section) );
    }

    echo ' | ';
    HTML::link( '#aeolus_page_top_anchor', 'Top of page', 'Top of page' );
    echo ' | ';
    HTML::link( '#end_of_upload_details_page', 'Bottom of page', 'Bottom of page' );
  }
  #-----------------------------------------------------

  function display_simple_field( $fieldname, $label = NULL ) {

    if( ! $fieldname ) return;
    if( $this->$fieldname ) {
      HTML::tablerow_start();
      HTML::tabledata( $label, 'class="label"' );
      HTML::tabledata_start();
      $this->echo_safely_with_linebreaks( $this->$fieldname );
      HTML::tabledata_end();
      HTML::tablerow_end();
    }
  }
  #-----------------------------------------------------

  function join_upload_and_status_tables() {

    $statement = 'select u.*, s.status_desc, s.editable from ' 
               . $this->proj_collect_upload_tablename() . ' u, '
               . $this->proj_collect_status_tablename() . ' s '
               . ' where u.upload_status = s.status_id';
    return $statement;
  }
  #-----------------------------------------------------

  function inform_reviewer_of_upload() {

    # You need to be a supervisor to look up other people's roles
    $supervisor_username = $this->get_supervisor(); 
    $super_connection = new DBQuery ( $supervisor_username );
    $super_obj = new User( $super_connection );

    $statement = 'select email from ' . $this->proj_users_and_roles_viewname()
               . " where email > '' and role_code = '" . UPLOAD_REVIEWER_ROLE . "'";
    $reviewers = $super_obj->db_select_into_array( $statement );

    $super_connection = NULL;
    $super_obj = NULL;

    $num_reviewers = count( $reviewers );
    if( $num_reviewers > 0 ) {

      HTML::new_paragraph();
      echo 'Informing project editors that a new contribution has been uploaded...';
      HTML::new_paragraph();

      $statement = 'select * from ' . $this->proj_collect_work_summary_viewname()
                 . " where upload_id = $this->upload_id"
                 . ' order by work_id_in_tool';
      $get_summary = $statement;  # I think we will need to re-run the query each time as the file attachment
                                  # seems to get mangled on the second run through.
      $contribution = $this->db_select_into_array( $statement );
      $num_works_uploaded = count( $contribution );

      $msg_subject = 'Upload ' . $this->upload_id . ' from data collection tool';
      $msg_body = 'A new contribution has arrived in the EMLO-edit holding area: upload ID ' . $this->upload_id . '.';
      $msg_body .= CARRIAGE_RETURN . NEWLINE;
      $msg_body .= 'Source of data: ' . $this->upload_description;
      $msg_body .= CARRIAGE_RETURN . NEWLINE;
      $msg_body .= 'Contact email address: ' . $this->uploader_email;
      $msg_body .= CARRIAGE_RETURN . NEWLINE;
      $msg_body .= 'Number of works uploaded: ' . $num_works_uploaded;
      $msg_body .= CARRIAGE_RETURN . NEWLINE;

      $contributed_work_obj = new Contributed_Work( $this->db_connection ); # so we get the correct column headings
      $contributed_work_obj->username = $this->get_supervisor();

      foreach( $reviewers as $reviewer ) {
        extract( $reviewer, EXTR_OVERWRITE );
        $contribution = $this->db_select_into_array( $get_summary ); # get a new array of data each time
                                                                     # so that later emails don't get mangled

        $success = $contributed_work_obj->db_produce_csv_output( 
                                  $contribution,
                                  $msg_recipient = $email,
                                  $msg_body,
                                  $msg_subject,
                                  $filename_root = 'upload' . $this->upload_id,
                                  $suppress_confirmation = TRUE ); 

      }
    }
    return $num_reviewers;
  }
  #-----------------------------------------------------
  function contributed_works_export_button( $upload_id, $verbose = FALSE ) {

    if( ! $upload_id ) die( 'Invalid input.' );

    HTML::form_start( $class_name = 'upload', $method_name = 'export_upload_to_csv',
                      $form_name = '', $form_target = '_blank' );

    HTML::hidden_field( 'upload_id', $upload_id );
    if( $verbose ) echo 'You can export details of the works in this contribution to a spreadsheet: ';
    HTML::submit_button( 'csv_button', 'Export' );
    HTML::form_end();

	 /* HTML::form_start( $class_name = 'upload', $method_name = 'export_upload_to_csv_webform',
		  $form_name = '', $form_target = '_blank' );

	  HTML::hidden_field( 'upload_id', $upload_id );
	  if( $verbose ) echo 'You can export details of the works in this contribution to a spreadsheet that matches the webform: ';
	  HTML::submit_button( 'csv_button', 'Export match Webform' );
	  HTML::form_end();
	 */
  }
  #-----------------------------------------------------

	function export_upload_to_csv_webform() {

		$upload_id = $this->read_post_parm( 'upload_id' );
		if( ! $upload_id ) die( 'Invalid input.' );

		$statement = 'select * from ' . $this->proj_collect_upload_tablename()
			. " where upload_id = $upload_id";
		echo $statement;
		$this->db_select_into_properties( $statement );

		$statement = 'select status_desc from ' . $this->proj_collect_status_tablename()
			. " where status_id = $this->upload_status";

		echo $statement;
		$status_desc = $this->db_select_one_value( $statement );

		$statement = 'select * from ' . $this->proj_collect_work_tablename()
			. " where upload_id = $this->upload_id";
		echo $statement;
		$contribution = $this->db_select_into_array( $statement );

		$email = $this->read_session_parm( 'user_email' );
		$msg_subject = 'Details of contribution ID ' . $upload_id;

		$msg_body = 'Details are attached of contribution ID ' . $upload_id . ', made via the offline data collection tool.';
		$msg_body .= CARRIAGE_RETURN . NEWLINE;

		$msg_body .= "Contributed by: $this->upload_description ($this->uploader_email)" . '.';
		$msg_body .= CARRIAGE_RETURN . NEWLINE;

		$msg_body .= "Status of contribution: $status_desc" . '.';
		$msg_body .= CARRIAGE_RETURN . NEWLINE;

		$msg_body .= 'Number of works uploaded: ' . $this->total_works . '. ';
		$msg_body .= ' Number accepted: ' . $this->works_accepted . '. ';
		$msg_body .= ' Number rejected: ' . $this->works_rejected . '. ';
		$msg_body .= CARRIAGE_RETURN . NEWLINE;

		$contributed_work_obj = new Contributed_Work( $this->db_connection ); # so we get the correct column headings

		$success = $contributed_work_obj->db_produce_csv_output( $contribution,
			$msg_recipient = $email,
			$msg_body,
			$msg_subject,
			$filename_root = 'upload' . $upload_id,
			$suppress_confirmation = FALSE );
	}
	#-----------------------------------------------------

  function export_upload_to_csv() {

    $upload_id = $this->read_post_parm( 'upload_id' );
    if( ! $upload_id ) die( 'Invalid input.' );

    $statement = 'select * from ' . $this->proj_collect_upload_tablename()
               . " where upload_id = $upload_id";
    $this->db_select_into_properties( $statement );

    $statement = 'select status_desc from ' . $this->proj_collect_status_tablename()
               . " where status_id = $this->upload_status";
    $status_desc = $this->db_select_one_value( $statement );

    $statement = 'select * from ' . $this->proj_collect_work_summary_viewname()
               . " where upload_id = $this->upload_id"
               . ' order by work_id_in_tool';
    $contribution = $this->db_select_into_array( $statement );
    $num_works_uploaded = count( $contribution );

    $email = $this->read_session_parm( 'user_email' );
    $msg_subject = 'Details of contribution ID ' . $upload_id;

    $msg_body = 'Details are attached of contribution ID ' . $upload_id . ', made via the offline data collection tool.';
    $msg_body .= CARRIAGE_RETURN . NEWLINE;

    $msg_body .= "Contributed by: $this->upload_description ($this->uploader_email)" . '.';
    $msg_body .= CARRIAGE_RETURN . NEWLINE;

    $msg_body .= "Status of contribution: $status_desc" . '.';
    $msg_body .= CARRIAGE_RETURN . NEWLINE;

    $msg_body .= 'Number of works uploaded: ' . $this->total_works . '. ';
    $msg_body .= ' Number accepted: ' . $this->works_accepted . '. ';
    $msg_body .= ' Number rejected: ' . $this->works_rejected . '. ';
    $msg_body .= CARRIAGE_RETURN . NEWLINE;

    $contributed_work_obj = new Contributed_Work( $this->db_connection ); # so we get the correct column headings

    $success = $contributed_work_obj->db_produce_csv_output( $contribution,
                                                             $msg_recipient = $email,
                                                             $msg_body,
                                                             $msg_subject,
                                                             $filename_root = 'upload' . $upload_id,
                                                             $suppress_confirmation = FALSE ); 
  }
  #-----------------------------------------------------

  function get_related_resources( $table_name, $id_column_name, $id_value ) {

    if( ! $table_name || ! $id_column_name || ! $id_value ) die( 'Invalid input while getting related resources.' );

    $statement = "select * from $table_name where $id_column_name = $id_value"
               . " and upload_id = $this->upload_id";
    $resources = $this->db_select_into_array( $statement );
    return $resources;
  }
  #-----------------------------------------------------

  function set_related_resources_for_display( $table_name, $id_column_name, $id_value ) {

    $this->related_resources = '';
    $resource_string = '';
    $resources = $this->get_related_resources( $table_name, $id_column_name, $id_value );

    if( count( $resources ) > 0 ) {
      foreach( $resources as $row ) {
        extract( $row, EXTR_OVERWRITE );
        if( $resource_string > '' ) $resource_string .= NEWLINE . NEWLINE;
        $resource_string .= 'Description: ' . $resource_name;
        if( $resource_url ) {
          $resource_string .= NEWLINE . 'URL: ' . $resource_url;
        }
        if( $resource_details ) {
          $resource_string .= NEWLINE . 'Further details: ' . $resource_details;
        }
      }
    }

    $this->related_resources = $resource_string;
    return $resource_string;
  }
  #-----------------------------------------------------
  function only_get_people_used() {

    $where_clause = ' where iperson_id >= ' . IDS_CREATED_IN_TOOL_START;

    $file_to_postgres_table_lookup = $this->get_file_to_postgres_table_lookup();

    foreach( $file_to_postgres_table_lookup as $file_number => $postgres_table ) {
      $includes_person = FALSE;
      switch( $file_number ) {
        case CSV_FILE_ADDRESSEE:
        case CSV_FILE_AUTHOR:
        case CSV_FILE_OCCUPATION_OF_PERSON:
        case CSV_FILE_PERSON_MENTIONED:
        case CSV_FILE_PERSON_RESOURCE:
          $includes_person = TRUE;
          break;
      }
      if( ! $includes_person ) continue;

      $temp_table = $this->temp_tablename_from_perm( $postgres_table );

      $where_clause .= " or iperson_id in (select iperson_id from $temp_table)";
    }
    return $where_clause;
  }
  #-----------------------------------------------------
  function only_get_places_used() {

    $where_clause = ' where location_id >= ' . IDS_CREATED_IN_TOOL_START;

    $file_to_postgres_table_lookup = $this->get_file_to_postgres_table_lookup();

    foreach( $file_to_postgres_table_lookup as $file_number => $postgres_table ) {
      $includes_place = FALSE;
      switch( $file_number ) {
        case CSV_FILE_PLACE_MENTIONED:
        case CSV_FILE_WORK:
          $includes_place = TRUE;
          break;
      }
      if( ! $includes_place ) continue;

      $temp_table = $this->temp_tablename_from_perm( $postgres_table );

      if( $file_number == CSV_FILE_PLACE_MENTIONED )
        $where_clause .= " or location_id in (select location_id from $temp_table)";
      else {
        $where_clause .= " or location_id in (select origin_id from $temp_table)";
        $where_clause .= " or location_id in (select destination_id from $temp_table)";
      }
    }
    return $where_clause;
  }
  #-----------------------------------------------------
  function only_get_repos_used() {

    $where_clause = ' where institution_id >= ' . IDS_CREATED_IN_TOOL_START;

    $file_to_postgres_table_lookup = $this->get_file_to_postgres_table_lookup();
    $postgres_table = $file_to_postgres_table_lookup[ CSV_FILE_MANIFESTATION ];
    $temp_table = $this->temp_tablename_from_perm( $postgres_table );

    $where_clause .= " or institution_id in (select repository_id from $temp_table)";
    return $where_clause;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'username':
        if( $this->is_alphanumeric( $this->parm_value, $allow_underscores = TRUE, $allow_all_whitespace = FALSE ))
          return TRUE;
        elseif( $this->is_email_address( $this->parm_value ))
          return TRUE;
        else
          return FALSE;

      case 'person_name':
        return $this->is_ok_free_text( $this->parm_value );

      case 'upload_id':
        return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------

    function file_upload_excel_form() {

        HTML::h3_start();
        echo 'Excel upload to Collect';
        HTML::h3_end();

        HTML::new_paragraph();
        echo "For uploading Excel documents containing manifestations, people, places, repositories, works into collect.";

        HTML::form_start( $class_name = 'upload',
            $method_name = 'file_upload_excel_form_response',
            $form_name = NULL,  # use default
            $form_target = '_self',
            $onsubmit_validation = FALSE,
            $form_destination = NULL,
            $form_method='POST',
            $parms = 'enctype="multipart/form-data"' );

        HTML::file_upload_field( $fieldname = 'file_to_process',
            $label = "Upload file",
            $value = NULL,
            $size = CSV_UPLOAD_FIELD_SIZE );
        HTML::new_paragraph();

	echo '<p>Please only upload one excel file at a time. The webpage may appear to freeze while the processing takes place.</p>';

        HTML::submit_button( 'upload_button', 'Upload' );
        HTML::form_end();
    }

    #----------------------------------------------------------------------------------

    function file_upload_excel_form_response() {

		 $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
		 $channel = $connection->channel();

		 $channel->queue_declare('uploader', false, false, false, false);


        $filecount = count( $_FILES );
        if( ! $filecount ) {
            echo 'No files were uploaded.';
            return;
        }
        elseif( $filecount > 1 ) {
            echo LINEBREAK;
            echo 'You have tried to upload ' .  $filecount . ' files at once. Please just upload one file at a time.';
            return;
        }

        $one_file = $_FILES[ 'file_to_process' ];
        //extract( $one_file, EXTR_OVERWRITE );

        $invalid = FALSE;
        if( ! $this->is_ok_free_text( $one_file['name'] ))     $invalid = TRUE;
        if( ! $this->is_ok_free_text( $one_file['tmp_name'] )) $invalid = TRUE;
        if( ! $this->is_ok_free_text( $one_file['type'] ))     $invalid = TRUE;
        if( ! $this->is_integer( $one_file['error'] ))         $invalid = TRUE;
        if( ! $this->is_integer( $one_file['size'] ))          $invalid = TRUE;

        if( ! is_uploaded_file( $one_file['tmp_name'] ))       $invalid = TRUE;

        if( $invalid ) die( "That doesn't seem to be a valid file." );

        $filename = pathinfo( $one_file['name'], PATHINFO_FILENAME);
        $foldername = $filename . "-" . gmdate("ymd-His");

        $path = "/uploader/" . $foldername;
        if( !mkdir( $path ) ) {
            die( 'FAILED to create folder for upload - name ' . $path );
        }

        $fileLocation = $path . "/" . $filename . ".xlsx";
        $moved = move_uploaded_file( $one_file['tmp_name'], $fileLocation );
        if( $moved ) {
			$this->echo_safely('Thanks for uploading ' . $one_file['name'] . " to the server.");
		}
        else {
			die('FAILED TO MOVE file to uploader directory. Can you change the name and try again?');
		}

		$data = new stdClass;
		$data->foldername = $foldername;
		$data->filelocation = $fileLocation;

		$msg = new AMQPMessage(json_encode( $data ) );
		$channel->basic_publish($msg, '', 'uploader');

		echo "<p>CokBot has processed your file. (If something is wrong you'll have to pass on the below output to your Sys-Admin)</p>";
		echo "<p>Check it out <a href=\"/union.php?menu_item_id=131\">here</a>.</p>";

        flush();

		$output = shell_exec( "php /var/www/core/upload_import2Postgres.php " . $data->foldername );//. ' &');
		echo '<p><h3>Extended output</h3>';
		echo '<textarea id="textarea_output" rows="9" cols="120">'.$output.'</textarea></p>';
		echo '<script>var textarea = document.getElementById("textarea_output");textarea.scrollTop = textarea.scrollHeight;</script>';

		flush();
    }

	#-----------------------------------------------------

	function file_upload_excel_batch_form() {

		HTML::h3_start();
		echo 'Excel upload for Batch Process';
		HTML::h3_end();

		HTML::new_paragraph();
		echo "For uploading Excel documents containing TBA.";

		HTML::form_start( $class_name = 'upload',
			$method_name = 'file_upload_excel_batch_view',
			$form_name = NULL,  # use default
			$form_target = '_self',
			$onsubmit_validation = FALSE,
			$form_destination = NULL,
			$form_method='POST',
			$parms = 'enctype="multipart/form-data"' );

		HTML::file_upload_field( $fieldname = 'file_to_process',
			$label = "Upload file",
			$value = NULL,
			$size = CSV_UPLOAD_FIELD_SIZE );
		HTML::new_paragraph();

		echo '<p>Please only upload one excel file at a time. The webpage may appear to freeze while the processing takes place.</p>';

		HTML::submit_button( 'upload_button', 'Upload' );
		HTML::form_end();
	}

	#----------------------------------------------------------------------------------

	function file_upload_excel_batch_view() {

		$filecount = count( $_FILES );
		if( ! $filecount ) {
			echo 'No files were uploaded.';
			return;
		}
		elseif( $filecount > 1 ) {
			echo LINEBREAK;
			echo 'You have tried to upload ' .  $filecount . ' files at once. Please just upload one file at a time.';
			return;
		}

		$one_file = $_FILES[ 'file_to_process' ];

		$invalid = FALSE;
		if( ! $this->is_ok_free_text( $one_file['name'] ))     $invalid = TRUE;
		if( ! $this->is_ok_free_text( $one_file['tmp_name'] )) $invalid = TRUE;
		if( ! $this->is_ok_free_text( $one_file['type'] ))     $invalid = TRUE;
		if( ! $this->is_integer( $one_file['error'] ))         $invalid = TRUE;
		if( ! $this->is_integer( $one_file['size'] ))          $invalid = TRUE;

		if( ! is_uploaded_file( $one_file['tmp_name'] ))       $invalid = TRUE;

		if( $invalid ) die( "That doesn't seem to be a valid file." );

		$filename = pathinfo( $one_file['name'], PATHINFO_FILENAME);
		$foldername = $filename . "-" . gmdate("ymd-His");

		$path = "/tweaker/" . $foldername;
		if( !mkdir( $path ) ) {
			die( 'FAILED to create folder for batch - name ' . $path );
		}

		$fileLocation = $path . "/" . $filename . ".xlsx";
		$moved = move_uploaded_file( $one_file['tmp_name'], $fileLocation );
		if( $moved ) {
			$this->echo_safely('Thanks for uploading ' . $one_file['name'] );

			$this->analyse_batch_excel_file( $fileLocation );
		}
		else {
			die('FAILED TO MOVE file to batch directory. Can you change the name and try again?');
		}
	}

	function analyse_batch_excel_file( $filename ) {

		$xl = SimpleXLSX::parse( $filename );
		if ( !$xl ) {
			$error = SimpleXLSX::parseError();

			$xl = SimpleXLS::parse( $filename );

			if( !$xl ) {
				$error .= " | " . SimpleXLS::parseError();
			}
		}

		if ( $xl ) {

			$allowed_sheetnames = array (
				'work',
				'person',
				'location',
				'manifestation',
				'institution',
				'resource'
			);

			$allowed_work_columns = array(
				"EMLO Letter ID Number",
				"Year date",
				"Month date",
				"Day date",
				"Standard gregorian date",
				"Date is range (0=No; 1=Yes)",
				"Year 2nd date (range)",
				"Month 2nd date (range)",
				"Day 2nd date (range)",
				"Calendar of date provided to EMLO (G=Gregorian; JJ=Julian, year start 1 January; JM=Julian, year start March, U=Unknown)",
				"Date as marked on letter",
				"Date uncertain (0=No; 1=Yes)",
				"Date approximate (0=No; 1=Yes)",
				"Date inferred (0=No; 1=Yes)",
				"Notes on date",
				"Author",
				"Author EMLO ID",
				"Author as marked in body/text of letter",
				"Author inferred (0=No; 1=Yes)",
				"Author uncertain (0=No; 1=Yes)",
				"Notes on Author in relation to letter",
				"Recipient",
				"Recipient EMLO ID",
				"Recipient as marked in body/text of letter",
				"Recipient inferred (0=No; 1=Yes)",
				"Recipient uncertain (0=No; 1=Yes)",
				"Notes on Recipient in relation to letter",
				"Origin name",
				"Origin EMLO ID",
				"Origin as marked in body/text of letter",
				"Origin inferred (0=No; 1=Yes)",
				"Origin uncertain (0=No; 1=Yes)",
				"Notes on Origin in relation to letter",
				"Destination name",
				"Destination EMLO ID",
				"Destination as marked in body/text of letter",
				"Destination inferred (0=No; 1=Yes)",
				"Destination uncertain (0=No; 1=Yes)",
				"Notes on Destination in relation to letter",
				"Abstract",
				"Keywords",
				"Language(s)",
				"Incipit",
				"Explicit",
				"People mentioned",
				"EMLO IDs of people mentioned",
				"Notes on people mentioned",
				"Original Catalogue name",
				"Source",
				"Matching letter(s) in alternative EMLO catalogue(s) (self reference also)",
				"Match id number",
				"Related Resource IDs [er = number for link to EMLO letter]",
				"General notes for public display",
				"Editors' working notes",
				"UUID",
				"EMLO URL",
			);
			$allowed_person_columns = array(
				"EMLO Person ID",
				"Person primary name in EMLO",
				"Synonyms",
				"Roles/Titles",
				"Gender",
				"Is Organization (Y=yes;black=no)",
				"Birth year",
				"Death year",
				"Fl. year 1",
				"Fl. year 2",
				"Fl. year is range (0=No; 1=Yes)",
				"General notes on person",
				"Editors' working notes",
				"Related Resource IDs",
				"UUID",
				"EMLO URL"
			);
			$allowed_location_columns = array(
				"Place ID",
				"Place name",
				"Room",
				"Building",
				"Street or parish",
				"Primary place name (city, town, village)",
				"County, State, or Province",
				"Country",
				"Empire",
				"Place name synonyms",
				"Coordinates: Latitude",
				"Coordinates: Longitude",
				"Related Resource IDs",
				"General notes on place",
				"Editors' working notes",
				"UUID",
				"EMLO URL",
			);
			$allowed_manifestation_columns = array(
				"Work (Letter) ID",
				"Manifestation [Letter] ID",
				"Manifestation type",
				"Repository name",
				"Repository ID",
				"Shelfmark and pagination",
				"Printed copy details",
				"Notes on manifestation",
				"UUID",
			);
			$allowed_institution_columns = array(
				"Repository ID",
				"Repository Name",
				"Repository City",
				"Repository Country",
				"Related Resource IDs",
				"UUID",
				"EMLO URL",
			);
			$allowed_resource_columns = array(
				"Resource ID",
				"Resource Name",
				"Resource Details",
				"Resource URL",
				"UUID",
			);



			// From objects.py in exporter code.
			$work_exporter = array(
				array(  "f" => "EMLO Letter ID Number",       "d" => array(  "o" => "work", "f" => "iwork_id" ) ),

				array(  "f" => "Year date",                   "d" => array(  "o" => "work", "f" => "date_of_work_std_year") ),
				array(  "f" => "Month date",                  "d" => array(  "o" => "work", "f" => "date_of_work_std_month") ),
				array(  "f" => "Day date",                    "d" => array(  "o" => "work", "f" => "date_of_work_std_day") ),
				array(  "f" => "Standard gregorian date",     "d" => array(  "o" => "work", "f" => "date_of_work_std_gregorian") ),
				array(  "f" => "Date is range (0=No; 1=Yes)", "d" => array(  "o" => "work", "f" => "date_of_work_std_is_range") ),
				array(  "f" => "Year 2nd date (range)",       "d" => array(  "o" => "work", "f" => "date_of_work2_std_year") ),
				array(  "f" => "Month 2nd date (range)",      "d" => array(  "o" => "work", "f" => "date_of_work2_std_month") ),
				array(  "f" => "Day 2nd date (range)",        "d" => array(  "o" => "work", "f" => "date_of_work2_std_day") ),
				array(  "f" => "Calendar of date provided " .
					"to EMLO (G=Gregorian; " .
					"JJ=Julian, year start 1 " .
					"January; JM=Julian, year " .
					"start March, U=Unknown)",         "d" => array(  "o" => "work", "f" => "original_calendar") ),
				array(  "f" => "Date as marked on letter",        "d" => array(  "o" => "work", "f" => "date_of_work_as_marked") ),
				array(  "f" => "Date uncertain (0=No; 1=Yes)",    "d" => array(  "o" => "work", "f" => "date_of_work_uncertain") ),
				array(  "f" => "Date approximate (0=No; 1=Yes)",  "d" => array(  "o" => "work", "f" => "date_of_work_approx") ),
				array(  "f" => "Date inferred (0=No; 1=Yes)",     "d" => array(  "o" => "work", "f" => "date_of_work_inferred") ),
				array(  "f" => "Notes on date",                   "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_date") ),

				array(  "f" => "Author",                          "d" =>  array(  "o" => "person", "f" => "foaf_name", "r" => "created" ) ),
				array(  "f" => "Author EMLO ID",                  "d" => array(  "o" => "person", "f" => "iperson_id", "r" => "created" ) ),
				array(  "f" => "Author as marked in body/text " .
					"of letter",                       "d" => array(  "o" => "work", "f" => "authors_as_marked") ),
				array(  "f" => "Author inferred (0=No; 1=Yes)",   "d" => array(  "o" => "work", "f" => "authors_inferred") ),
				array(  "f" => "Author uncertain (0=No; 1=Yes)",  "d" => array(  "o" => "work", "f" => "authors_uncertain") ),
				array(  "f" => "Notes on Author in relation "  .
					"to letter",                       "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_author") ),
				array(  "f" => "Recipient",                       "d" => array(  "o" => "person", "f" => "foaf_name", "r" => "was_addressed_to" ) ),
				array(  "f" => "Recipient EMLO ID",               "d" => array(  "o" => "person", "f" => "iperson_id", "r" => "was_addressed_to" ) ),
				array(  "f" => "Recipient as marked in body/text " .
					"of letter",                       "d" => array(  "o" => "work", "f" => "addressees_as_marked") ),
				array(  "f" => "Recipient inferred " .
					"(0=No; 1=Yes)",                   "d" => array(  "o" => "work", "f" => "addressees_inferred") ),
				array(  "f" => "Recipient uncertain " .
						"(0=No; 1=Yes)",                   "d" => array(  "o" => "work", "f" => "addressees_uncertain") ),
				array(  "f" => "Notes on Recipient in " .
						"relation to letter",              "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_addressee") ),

				array(  "f" => "Origin name",                     "d" => array(  "o" => "location", "f" => "location_name", "r" => "was_sent_from" ) ),
				array(  "f" => "Origin EMLO ID",                  "d" => array(  "o" => "location", "f" => "location_id", "r" => "was_sent_from" ) ),
				array(  "f" => "Origin as marked in body/text " .
					"of letter",                       "d" => array(  "o" => "work", "f" => "origin_as_marked") ),
				array(  "f" => "Origin inferred (0=No; 1=Yes)",   "d" => array(  "o" => "work", "f" => "origin_inferred") ),
				array(  "f" => "Origin uncertain (0=No; 1=Yes)",  "d" => array(  "o" => "work", "f" => "origin_uncertain") ),
				array(  "f" => "Notes on Origin in relation " .
					"to letter",                       "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_origin") ),
				array(  "f" => "Destination name",                "d" => array(  "o" => "location", "f" => "location_name", "r" => "was_sent_to" ) ),
				array(  "f" => "Destination EMLO ID",             "d" => array(  "o" => "location", "f" => "location_id", "r" => "was_sent_to" ) ),
				array(  "f" => "Destination as marked in " .
					"body/text of letter",             "d" => array(  "o" => "work", "f" => "destination_as_marked") ),
				array(  "f" => "Destination inferred " .
					"(0=No; 1=Yes)",                   "d" => array(  "o" => "work", "f" => "destination_inferred") ),
				array(  "f" => "Destination uncertain " .
					"(0=No; 1=Yes)",                   "d" => array(  "o" => "work", "f" => "destination_uncertain") ),
				array(  "f" => "Notes on Destination in " .
					"relation to letter",              "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_destination") ),

				array(  "f" => "Abstract",                        "d" => array(  "o" => "work", "f" => "abstract") ),
				array(  "f" => "Keywords",                        "d" => array(  "o" => "work", "f" => "keywords") ),
				array(  "f" => "Language(s)",                     "d" => array(  "o" => "work", "f" => "language_of_work") ),
				array(  "f" => "Incipit",                         "d" => array(  "o" => "work", "f" => "incipit") ),
				array(  "f" => "Explicit",                        "d" => array(  "o" => "work", "f" => "explicit") ),

				array(  "f" => "People mentioned",                "d" => array(  "o" => "person", "f" => "foaf_name", "r" => "mentions" ) ),
				array(  "f" => "EMLO IDs of people mentioned",    "d" => array(  "o" => "person", "f" => "iperson_id", "r" => "mentions" ) ),
				array(  "f" => "Notes on people mentioned",       "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to_people_mentioned_in_work") ),

				array(  "f" => "Original Catalogue name",         "d" => array(  "o" => "work", "f" => "original_catalogue" ) ),
				array(  "f" => "Source",                          "d" => array(  "o" => "work", "f" => "accession_code" ) ),

				# Ignoring as would include other works (complicated work connections...)
				#array(  "f" => "Letter in reply to", "d" => array(  "o" => "work-rel", "f" => "iwork_id", "r" => "" ) ),
				#array(  "f" => "Letter answered by", "d" => array(  "o" => "work-rel", "f" => "iwork_id", "r" => "is_reply_to" ) ),

				array(  "f" => "Matching letter(s) in alternative EMLO catalogue(s) (self reference also)", "d" => array(  "o" => "work-rel", "f" => "iwork_id", "r" => "matches" ) ),
				array(  "f" => "Match id number", "d" => array( ) ),

				# This will be a separate table... some how...
				# array(  "f" => "Related Resource descriptor", "d" => array(  "o" => "work", "f" => "") ),
				# array(  "f" => "Related Resource URL", "d" => array(  "o" => "work", "f" => "") ),

				array(  "f" => "Related Resource IDs " .
					"array( er = number for link " .
					"to EMLO letter)",                 "d" => array(  "o" => "resource", "f" => "resource_id", "r" => "is_related_to") ),
				array(  "f" => "General notes for public " .
					"display",                         "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to") ),
				array(  "f" => "Editors' working notes",          "d" => array(  "o" => "work", "f" => "editors_notes") ),
				array(  "f" => "UUID",                            "d" => array(  "o" => "work", "f" => "uuid" ) ),
				array(  "f" => "EMLO URL",                        "d" => array( ) ),
			);
			$person_exporter = array(
				array(  "f" => "EMLO Person ID", "d" => array(  "o" => "person", "f" => "iperson_id" ) ),
				array(  "f" => "Person primary name in EMLO", "d" => array(  "o" => "person", "f" => "foaf_name" ) ),
				array(  "f" => "Synonyms", "d" => array(  "o" => "person", "f" => "skos_altlabel" ) ),
				# array(  "f" => "Synonyms Other", "d" => array(  "o" => "person", "f" => "skos_hiddenlabel" ) ),
				array(  "f" => "Roles/Titles", "d" => array(  "o" => "person", "f" => "person_aliases" ) ),
				array(  "f" => "Gender", "d" => array(  "o" => "person", "f" => "gender" ) ),
				array(  "f" => "Is Organization (Y=yes;black=no)", "d" => array(  "o" => "person", "f" => "is_organisation" ) ),
				array(  "f" => "Birth year", "d" => array(  "o" => "person", "f" => "date_of_birth_year" ) ),
				array(  "f" => "Death year", "d" => array(  "o" => "person", "f" => "date_of_death_year" ) ),
				array(  "f" => "Fl. year 1", "d" => array(  "o" => "person", "f" => "flourished_year" ) ),
				array(  "f" => "Fl. year 2", "d" => array(  "o" => "person", "f" => "flourished2_year" ) ),
				array(  "f" => "Fl. year is range (0=No; 1=Yes)", "d" => array(  "o" => "person", "f" => "flourished_is_range" ) ),
				array(  "f" => "General notes on person", "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to") ),
				array(  "f" => "Editors' working notes", "d" => array(  "o" => "person", "f" => "editors_notes" ) ),
				#array(  "f" => "Related Resource Name(s)", "d" => array(  "o" => "person", "f" => "" ) ),
				#array(  "f" => "Related Resource URL(s)", "d" => array(  "o" => "person", "f" => "" ) ),
				array(  "f" => "Related Resource IDs", "d" => array(  "o" => "resource", "f" => "resource_id", "r" => "is_related_to") ),
				array(  "f" => "UUID", "d" => array(  "o" => "person", "f" => "uuid" ) ),
				array(  "f" => "EMLO URL",                        "d" => array( ) ),
			);
			$location_exporter = array(
				array(  "f" => "Place ID", "d" => array(  "o" => "location", "f" => "location_id" ) ),
				array(  "f" => "Place name", "d" => array(  "o" => "location", "f" => "location_name" ) ),
				array(  "f" => "Room", "d" => array(  "o" => "location", "f" => "element_1_eg_room" ) ),
				array(  "f" => "Building", "d" => array(  "o" => "location", "f" => "element_2_eg_building" ) ),
				array(  "f" => "Street or parish", "d" => array(  "o" => "location", "f" => "element_3_eg_parish" ) ),
				array(  "f" => "Primary place name (city, town, village)", "d" => array(  "o" => "location", "f" => "element_4_eg_city" ) ),
				array(  "f" => "County, State, or Province", "d" => array(  "o" => "location", "f" => "element_5_eg_county" ) ),
				array(  "f" => "Country", "d" => array(  "o" => "location", "f" => "element_6_eg_country" ) ),
				array(  "f" => "Empire", "d" => array(  "o" => "location", "f" => "element_7_eg_empire" ) ),
				array(  "f" => "Place name synonyms", "d" => array(  "o" => "location", "f" => "location_synonyms" ) ),
				array(  "f" => "Coordinates=> Latitude", "d" => array(  "o" => "location", "f" => "latitude" ) ),
				array(  "f" => "Coordinates=> Longitude", "d" => array(  "o" => "location", "f" => "longitude" ) ),
				#array(  "f" => "Related resource name", "d" => array(  "o" => "location", "f" => "" ) ),
				#array(  "f" => "Related resource URL", "d" => array(  "o" => "location", "f" => "" ) ),
				array(  "f" => "Related Resource IDs", "d" => array(  "o" => "resource", "f" => "resource_id", "r" => "is_related_to") ),
				array(  "f" => "General notes on place", "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to" ) ),
				array(  "f" => "Editors' working notes", "d" => array(  "o" => "location", "f" => "editors_notes" ) ),
				array(  "f" => "UUID", "d" => array(  "o" => "location", "f" => "uuid" ) ),
				array(  "f" => "EMLO URL",                        "d" => array( ) ),

			);
			$manifestation_exporter = array(
				array(  "f" => "Work (Letter) ID", "d" => array(  "o" => "work", "f" => "iwork_id", "r" => "is_manifestation_of" ) ),
				array(  "f" => "Manifestation array( Letter) ID", "d" => array(  "o" => "manifestation", "f" => "manifestation_id" ) ),
				array(  "f" => "Manifestation type", "d" => array(  "o" => "manifestation", "f" => "manifestation_type" ) ),
				array(  "f" => "Repository name", "d" => array(  "o" => "institution", "f" => "institution_name", "r" => "stored_in" ) ),
				array(  "f" => "Repository ID", "d" => array(  "o" => "institution", "f" => "institution_id", "r" => "stored_in" ) ),
				array(  "f" => "Shelfmark and pagination", "d" => array(  "o" => "manifestation", "f" => "id_number_or_shelfmark" ) ),
				array(  "f" => "Printed copy details", "d" => array(  "o" => "manifestation", "f" => "printed_edition_details" ) ),
				array(  "f" => "Notes on manifestation", "d" => array(  "o" => "comment", "f" => "comment", "r" => "refers_to" ) ),
				array(  "f" => "UUID", "d" => array(  "o" => "manifestation", "f" => "uuid" ) ),
			);
			$institute_exporter = array(
				#array(  "f" => "Manifestation ID",   "d" => array(  "o" => "institution", "f" => "" ) ),
				#array(  "f" => "Work array( Letter) ID",   "d" => array(  "o" => "institution", "f" => "" ) ),
				array(  "f" => "Repository ID",        "d" => array(  "o" => "institution", "f" => "institution_id" ) ),
				array(  "f" => "Repository Name",      "d" => array(  "o" => "institution", "f" => "institution_name" ) ),
				array(  "f" => "Repository City",      "d" => array(  "o" => "institution", "f" => "institution_city" ) ),
				array(  "f" => "Repository Country",   "d" => array(  "o" => "institution", "f" => "institution_country" ) ),
				array(  "f" => "Related Resource IDs", "d" => array(  "o" => "resource",    "f" => "resource_id", "r" => "is_related_to") ),
				array(  "f" => "UUID",                 "d" => array(  "o" => "institution", "f" => "uuid" ) ),
				array(  "f" => "EMLO URL",                        "d" => array( ) ),
			);
			$resource_exporter = array(
				array(  "f" => "Resource ID", "d" => array(  "o" => "resource", "f" => "resource_id" ) ),
				array(  "f" => "Resource Name", "d" => array(  "o" => "resource", "f" => "resource_name" ) ),
				array(  "f" => "Resource Details", "d" => array(  "o" => "resource", "f" => "resource_details" ) ),
				array(  "f" => "Resource URL", "d" => array(  "o" => "resource", "f" => "resource_url" ) ),
				array(  "f" => "UUID", "d" => array(  "o" => "resource", "f" => "uuid" ) ),
			);

			$settings = array(
				'work' => 		array(
					'id_title' => 'iwork_id',
					'exporter' => $work_exporter,
					'columns' => $this->get_columns_from_export( $work_exporter ),
					'table' => $this->proj_work_tablename()
				),
				'person' => 	array(
					'id_title' => 'iperson_id',
					'exporter' => $person_exporter,
					'columns' => $this->get_columns_from_export( $person_exporter ),
					'table' => $this->proj_person_tablename()
				),
				'location' => 	array(
					'id_title' => 'location_id',
					'exporter' => $location_exporter,
					'columns' => $this->get_columns_from_export( $location_exporter ),
					'table' => $this->proj_location_tablename()
				),
				'manifestation' => array(
					'id_title' => 'manifestation_id',
					'exporter' => $manifestation_exporter,
					'columns' => $this->get_columns_from_export( $manifestation_exporter ),
					'table' => $this->proj_manifestation_tablename()
				),
				'institution' => array(
					'id_title' => 'institution_id',
					'exporter' => $institute_exporter,
					'columns' => $this->get_columns_from_export( $institute_exporter ),
					'table' => $this->proj_institution_tablename()
				),
				'resource' => array(
					'id_title' => 'resource_id',
					'exporter' => $resource_exporter,
					'columns' => $this->get_columns_from_export( $resource_exporter ),
					'table' => $this->proj_resource_tablename()
				)
			);

			$settings['work']['id_export_title'] = $settings['work']['columns'][0];
			$settings['person']['id_export_title'] = $settings['person']['columns'][0];
			$settings['location']['id_export_title'] = $settings['location']['columns'][0];
			$settings['manifestation']['id_export_title'] = $settings['manifestation']['columns'][1];// the second column, to match the export file...
			$settings['institution']['id_export_title'] = $settings['institution']['columns'][0];
			$settings['resource']['id_export_title'] = $settings['work']['resource'][0];

			if( $xl->sheetsCount() !== 1 ) {
				echo '<p>The batch process file should only contain one sheet</p>';
				return;
			}

			$sheetNumber = 0;
			$sheetCommandColumn = 'Command';

			$sheetName = $xl->sheetName($sheetNumber);
			if( !in_array( $sheetName, $allowed_sheetnames ) ) {
				echo '<p>The file contains an invalid sheetname, "' . $xl->sheetName($sheetNumber) . '". It must be one of: </p>';
				echo '<ul>';
				for($i = 0, $z=count($allowed_sheetnames); $i < $z; $i++) {
					echo '<li>' . $allowed_sheetnames[$i] . '</li>';
				}
				echo '</ul>';
				return;
			}

			$sets = $settings[$sheetName];

			$dim = $xl->dimension($sheetNumber);
			$cols = $dim[0];
			$rows = $dim[1];

			echo ' Rows:' . $rows . ' Cols:' . $cols;

			if( $rows <= 1 ) {
				echo '<p>Sorry, I was unable to load any rows from that file. If there are data rows then you should try resaving the file in a pre 2013 Excel format and then reuploading.</p>';
				return;
			}

			// check columns
			//
			if( $cols < 2 ) {
				echo '<p>You don\'t have enough columns. You need at least an ID and a command column.</p>';
				return;
			}

			$titleRow = $xl->rows($sheetNumber)[0];
			$titleError = False;
			$data_columns = array();
			for ($i = 0, $z=$cols; $i < $z; $i++) {
				$cellText = $titleRow[$i];

				if( $i == 0 ) {
					if( $cellText != $sets['id_export_title'] ) {
						$titleError = true;
						echo '<p>';
						echo 'The first column must be an ID row but it\'s called "' . $cellText . '".';
						echo 'It should be: "' . $sets['id_export_title'] . '"';
						echo '</p>';
					}
				}

				elseif( $i == 1 ) {

					if( $cellText !== $sheetCommandColumn ) {
						$titleError = true;
						echo '<p>The second column must be set to ' . $sheetCommandColumn . '. Each row should have the same value of either "CREATE", "UPDATE" or "DELETE"</p>';
					}
				}

				else {
					if( $cellText != '' && !in_array( $cellText, $sets['columns'] ) ) {
						$titleError = true;
						echo '<p>Unexpected column found: ' . $cellText .' </p>';
						echo '<p>It should be one of (from export file): </p>';
						echo '<ul>';
						for($i = 0, $z=count($sets['columns']); $i < $z; $i++) {
							echo '<li>' . $sets['columns'] . '</li>';
						}
						echo '</ul>';
						echo '';
					}
					else {
						array_push($data_columns, $cellText );
					}
				}
			}

			if( $titleError ) {
				return;
			}

			// Check command is just the one thing.
			$command = null;
			for ($i = 1, $z=$rows; $i < $z; $i++) {
				if( !$command ) {
					$command = $xl->rows($sheetNumber)[$i][1];
				}

				if( $command == '' || $command != $xl->rows($sheetNumber)[$i][1] ) {
					echo '<p>You can only do one type of command per file. Detected a problem at row ' . $i . '. Please ensure the cells in the command column are all the same and either set to CREATE, UPDATE or DELETE.</p>';
					return;
				}
			}

			if( $command == "DELETE" && count($data_columns) != 0 ) {
				echo '<p>No additional columns allowed for DELETE command. Only ID and Command.';
				echo ' Column count is '. count($data_columns) . ' (I may be picking up empty columns)</p>';
				for( $i = 0, $z = count($data_columns); $i <$z; $i++ ) {
					echo '/' . $data_columns[$i] . '\\';
				}
				return;
			}

			$currents = null;
			if( $command == "UPDATE" ) {

				$ids = array();
				for ($i = 1, $z = $rows; $i < $z; $i++) {
					array_push($ids, $xl->rows($sheetNumber)[$i][0]);
				}

				$field_names = array( $sets['id_title'] );
				for( $i = 0, $z = count($data_columns); $i <$z; $i++ ) {
					array_push( $field_names, $this->get_field_name_from_export( $data_columns[$i], $sets['exporter'] ) );
				}

				$statement = 'select ' . implode( ',', $field_names ) . ' from ' . $sets['table']
						. ' where ' . $sets['id_title'] . " in ('" . implode("','", $ids) . "')";

				echo $statement;
				$currents = $this->db_select_into_array($statement);
				foreach ($currents as $row) {
					echo 'found ';
					foreach( $field_names as $field  ) {
						echo $field . ':' . $row[$field];
					}
				}
			}

			echo '<h2>' . $command . " " . $xl->sheetName($sheetNumber) .  '</h2>';

			echo '<p>You are about to ' . $command . ' ' . ($rows-1) . ' ' . $sheetName . 's';
			if( $command == "UPDATE") {
				echo ' with ' . sizeof($data_columns) . ' changes each ';
			}
    		echo '. Details in table below.</p>';
			echo '<p>Do you wish to continue? <button>Do it, Bot!</button></p>' ;

			echo '<div class="queryresults"><table border=1>';

				echo '<thead><tr>';
				echo '<th>'. $sets['id_export_title'] . '</th>';
				echo '<th>Command</th>';
				for( $i = 0, $z = count($data_columns); $i <$z; $i++ ) {
					echo '<th colspan="2">'. $data_columns[$i] . '</th>';
				}
				echo '</tr>';

				if( $command == "UPDATE") {
					echo '<tr><th/><th/>';
					for ($i = 0, $z = count($data_columns); $i < $z; $i++) {
						echo '<th>from</th><th>to</th>';
					}
					echo '</tr></thead>';
				}

				for( $r = 1, $z=$rows; $r < $z; $r++) {
					$row = $xl->rows($sheetNumber)[$r];
					$id = $row[0];

					if( $command == "UPDATE") {
						$current = $this->get_current_from_id($id, $sets['id_title'], $currents);
					}

					echo '<tr>';
					echo "<td>$id</td>";
					echo "<td>$command</td>";

					for( $i = 0, $zz = count($data_columns); $i <$zz; $i++ ) {
						if( $command == "UPDATE") {
							echo '<td>' . $current[$this->get_field_name_from_export($data_columns[$i], $sets['exporter'])] . '</td>';
						}
						echo '<td>' . $row[$i+2] . '</td>';

					}
					echo '</tr>';
				}


			echo '</table></div>';
		}
	}

	function get_field_name_from_export( $col, $exporter ) {
		for($i = 0, $z = count($exporter); $i < $z; $i++ ) {
			if( $exporter[$i]['f'] == $col ) {
				return $exporter[$i]['d']['f'];
			}
		}
		return null;
	}

	function get_columns_from_export( $exporter ) {
  		$cols = array();

  		for($i = 0, $z = count($exporter); $i < $z; $i++ ) {
  			array_push( $cols, $exporter[$i]['f'] );
		}

		return $cols;
	}

	function get_current_from_id($id, $id_name, $currents) {
		for($i = 0, $z = count($currents); $i < $z; $i++ ) {
			if( $currents[$i][$id_name] == $id ) {
				return $currents[$i];
			}
		}
		return null;
	}
}


