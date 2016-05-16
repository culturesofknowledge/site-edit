<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Upload files from Cultures of Knowledge offline data collection tool
# Author: Sushila Burgess
#====================================================================================

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

    html::h3_start();
    echo 'Locate your data export files';
    html::h3_end();

    html::new_paragraph();
    echo "When you clicked the 'Export' button in the data collection tool, " . NUM_CSV_FILES_TO_UPLOAD . ' files'
         . ' were created in the same folder as your OpenOffice database file (EMLOcollect.odb).';
    echo LINEBREAK . 'These files were called '; 
    html::bold_start();
    echo $this->csv_filename_from_number( 1 );
    html::bold_end();
    echo ' through to ';
    html::bold_start();
    echo $this->csv_filename_from_number( NUM_CSV_FILES_TO_UPLOAD );
    html::bold_end();

    html::new_paragraph();
    html::italic_start();
    echo 'Please upload these files, using Shift-Click or Ctrl-Click to select them all at once,'
         . ' then Open.';
    html::italic_end();
    html::new_paragraph();

    html::form_start( $class_name = 'upload', 
                      $method_name = 'process_uploaded_files', 
                      $form_name = NULL,  # use default
                      $form_target = '_self',
                      $onsubmit_validation = FALSE, 
                      $form_destination = NULL, 
                      $form_method='POST',
                      $parms = 'enctype="multipart/form-data"' );

    html::multiple_file_upload_field( $fieldname = 'files_to_process[]', 
                             $label = 'Select the ' . NUM_CSV_FILES_TO_UPLOAD . " '"
                                    . CSV_FILENAME_ROOT . "' files", 
                             $value = NULL, 
                             $size = CSV_UPLOAD_FIELD_SIZE );
    html::new_paragraph();

    html::submit_button( 'upload_button', 'Upload' );
    html::form_end();
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
      html::div_start( 'class="warning"' );
      echo 'Sorry, you have not uploaded the correct number of files. ';
      html::new_paragraph();

      echo 'You need to upload ' . NUM_CSV_FILES_TO_UPLOAD . ' files, named ' 
           . CSV_FILENAME_ROOT . '01.csv through to '
           . CSV_FILENAME_ROOT . NUM_CSV_FILES_TO_UPLOAD . '.csv.';
      html::new_paragraph();

      echo ' Please try again, selecting all ' . NUM_CSV_FILES_TO_UPLOAD
           . ' files using Shift-Click or Ctrl-Click.';
      html::div_end();
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

    html::h3_start();
    echo 'Processing your contribution... ';
    html::h3_end();
    html::new_paragraph();

    html::h4_start();
    echo 'Reading uploaded files...';
    html::h4_end();
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
        html::new_paragraph();
        html::div_start( 'class="errmsg"' );
        echo "Error processing file: the '$table_desc' file contains duplicate lines.";
        html::div_end();
        html::new_paragraph();
        html::div_start( 'class="highlight2 bold"' );
        echo "This was probably caused by the 'Export' button in the data collection tool"
             . ' being pressed multiple times, as the export procedure appends data to the end'
             . ' of any existing file.';
        html::new_paragraph();
        echo 'You need to clear out existing files before you begin. Please go to the local folder'
             . ' on your personal computer from which you uploaded the files ' . CSV_FILENAME_ROOT 
             . '01.csv through to ' . CSV_FILENAME_ROOT . NUM_CSV_FILES_TO_UPLOAD . '.csv'
             . ' and delete all those ' . NUM_CSV_FILES_TO_UPLOAD . ' files.';
        html::new_paragraph();
        echo "Then return to the OpenOffice data collection tool, go into the 'Upload your"
             . " contribution' form, and click the 'Export' button again.";
        html::div_end();
        html::new_paragraph();

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
    
    html::h4_start();
    echo 'Transferring data into holding area ready for review...' . LINEBREAK;
    html::h4_end();

    $file_to_tables_lookup = $this->get_file_to_tables_lookup();

    foreach( $file_to_tables_lookup as $file_number => $tables ) {

      $this->perm_postgres_table = $tables[ 'postgres' ];
      $this->temp_postgres_table = $this->temp_tablename_from_perm( $this->perm_postgres_table );
      $this->table_desc = str_replace( '_', ' ', $tables[ 'openoffice' ] );

      echo "Processing '" . $this->table_desc . "' data..." . LINEBREAK;

      $anchor = $this->temp_postgres_table . '_anchor';
      html::anchor( $anchor );
      $script = "document.location.hash = '#$anchor';";
      html::write_javascript_function( $script );
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
      html::div_start( 'class="warning"' );
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
    html::h4_start();
    echo 'Upload complete.';
    html::h4_end();

    html::div_start( 'class="highlight2 bold"' );
    echo 'Thank you for your contribution. Your data has been successfully imported into the EMLO-edit holding area.';
    html::new_paragraph();
    echo 'The project editors have been informed.';
    html::new_paragraph();
    echo 'Your contribution will now be reviewed and, if accepted, will be transferred into the main EMLO database.'
         . ' You will shortly receive an email informing you of the results of the review.';
    html::div_end();
    html::new_paragraph();

    html::div_start( 'class="boldlink"' );
    echo 'You can also ';
    $href = $_SERVER['PHP_SELF'] . '?option=history&upload_id=' . $this->upload_id;
    html::link( $href, 'check the status and details', 'Check the status and details of your contribution' );
    echo ' of your contribution online.';

    html::button( 'check_upload_button', 'Check', $tabindex = 1,
                  'id="check_upload_button" onclick="window.location.href=' . "'$href'" . '"' );

    html::div_end();

    html::new_paragraph();

    html::anchor( 'endofmessages' );
    $script = 'document.location.hash = "endofmessages";';
    html::write_javascript_function( $script );

    $script = 'var checkButton = document.getElementById( "check_upload_button" ); checkButton.focus();';
    html::write_javascript_function( $script );
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
        html::h3_start();
        echo 'No contributions are currently awaiting review.';
        html::h3_end();
        $this->link_to_contributed_work_search( $num_uploads );
        return;
      }
      else {
        html::h3_start();
        echo 'Click Review to accept or reject the data from a particular contribution.';
        html::h3_end();
        $this->link_to_contributed_work_search( $num_uploads );
        html::new_paragraph();
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

      html::h3_start();
      echo "Uploads by $this->person_name ($this->username): $num_uploads";
      html::h3_end();

      html::italic_start();
      echo 'Click the Export button to export contribution data to a spreadsheet.';
      html::italic_end();
      html::new_paragraph();
      
      if( $num_uploads == 0 ) {
        html::new_paragraph();
        html::div_start( 'class="warning"' );
        echo 'Sorry, no history of existing contributions was found for this username.';
        html::div_end();
        html::new_paragraph();
        return;
      }
    }

    html::table_start( 'class="datatab spacepadded"' );

    html::tablerow_start();
    html::column_header( 'ID' );
    html::column_header( 'Date / time' );
    if( $this->review_underway()) html::column_header( 'Source of data' );
    if( $this->review_underway()) html::column_header( 'Contact email' );
    html::column_header( 'Works uploaded' );
    html::column_header( 'Accepted' );
    html::column_header( 'Rejected' );
    html::column_header( 'Status' );
    if( $review_underway ) html::column_header( 'Review' );
    html::column_header( 'Export' );
    html::tablerow_end();

    foreach( $uploads as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $display_parms = '';
      if( $editable ) $display_parms = 'class="highlight1"';

      html::tablerow_start();
      html::tabledata( $upload_id );

      html::tabledata_start();
      if( $review_underway ) {   # supervisor reviewing contributions ready for ingest into the main database
        echo $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
      }
      else { # ordinary user looking at their own data
        html::link_start( $href = $_SERVER['PHP_SELF'] . '?option=history&upload_id=' . $upload_id,
                          $title = 'View details of upload no. ' . $upload_id );
        echo $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
        html::link_end();
      }
      html::tabledata_end();

      if( $this->review_underway()) {
        html::tabledata( $upload_description );
        html::tabledata_start();
        html::link( $href = "mailto:$uploader_email", 
                    $displayed_text = $uploader_email, 
                    $title = 'Contact the contributor', 
                    $target = '_blank' );
        html::tabledata_end();
      }

      html::tabledata( $total_works, 'class="rightaligned"' );
      html::tabledata( $works_accepted, 'class="rightaligned"' );
      html::tabledata( $works_rejected, 'class="rightaligned"' );

      html::tabledata( $status_desc );

      if( $review_underway ) {
        html::tabledata_start( $display_parms );
        if( ! $editable ) echo 'Can be displayed in read-only mode: ';
        html::form_start( $class_name='upload', $method_name='upload_details' );
        html::hidden_field( 'upload_id', $upload_id );
        if( $editable ) {
          echo ' ';
          html::submit_button( 'review_upload_' . $upload_id . '_button', 'Review' );
        }
        else {
          html::submit_button( 'display_upload_' . $upload_id . '_button', 'Display' );
        }
        html::form_end();
        html::tabledata_end();
      }

      html::tabledata_start();
      $this->contributed_works_export_button( $upload_id, $verbose = FALSE );
      html::tabledata_end();

      html::tablerow_end();
    }


    html::table_end();
  }
  #-----------------------------------------------------

  function link_to_contributed_work_search( $num_awaiting_review ) {

    html::new_paragraph();
    html::form_start( 'contributed_work', 'db_search' );
    html::italic_start();
    if( $num_awaiting_review > 0 )
      echo 'You can also search and browse through works from earlier contributions that have already been reviewed.';
    else
      echo 'However, you can search and browse through the works from contributions that have already been reviewed.';
    html::italic_end();
    html::submit_button( 'search_button', 'Search' );
    html::form_end();
    html::new_paragraph();
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
        html::new_paragraph();
        html::div_start( 'class="warning"' );
        echo "Contribution no. $this->upload_id does not exist or was not uploaded by user '"
             . $this->username . "'.";
        html::div_end();
        html::new_paragraph();
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

    html::h3_start();
    echo "Contribution by $contributing_person_name ($contributing_username) uploaded " 
         . $this->postgres_date_to_dd_mm_yyyy( $upload_timestamp );
    html::h3_end();

    if( $review_underway ) {
      echo 'Contact details: ';
      html::link( $href = "mailto:$uploader_email", 
                  $displayed_text = $uploader_email, 
                  $title = 'Contact the contributor', 
                  $target = '_blank' );
      html::new_paragraph();
    }

    if( ! $suppress_status ) {
      html::div_start( 'class="highlight2 bold"' );
      echo 'Status: ' . $status_desc;
      echo ' | ';
      echo 'Number of works uploaded: ' . $total_works;
      echo ' | ';
      echo 'Accepted: ' . $works_accepted;
      echo ' | ';
      echo 'Rejected: ' . $works_rejected;
      html::div_end();
    }

    html::new_paragraph();
    if( $header_only ) return;

    if( $review_underway && $editable ) {
      html::div_start( 'class="buttonrow"' );

      html::form_start( $class_name = 'upload', $method_name = 'upload_list' );
      echo 'Back to list of contributions: ';
      html::submit_button( 'back_to_upload_list_button', 'Back' );
      echo SPACE;
      html::form_end();


      html::form_start( $class_name = 'review', $method_name = 'accept_all_works' );
      echo 'Accept entire contribution: ';
      html::hidden_field( 'upload_id', $this->upload_id );
      html::submit_button( 'accept_all_button', 'Accept all' );
      echo SPACE;
      html::form_end();

      html::form_start( $class_name = 'review', $method_name = 'reject_all_works' );
      echo 'Reject entire contribution: ';
      html::hidden_field( 'upload_id', $this->upload_id );
      html::submit_button( 'reject_all_button', 'Reject all' );
      html::form_end();

      html::linebreak( 'class="clearleft"' );
      html::div_end();

      html::italic_start();
      echo 'Note: confirmation will be required before Accept/Reject of entire contribution.';
      html::italic_end();
    }
    else {
      if( $review_underway ) { # supervisor, came in via EMLO-edit
        html::form_start( $class_name = 'upload', $method_name = 'upload_list' );
        echo 'Back to list of contributions: ';
        html::submit_button( 'back_to_upload_list_button', 'Back' );
        html::form_end();
      }
      else { # normal user, came in via collection tool
        html::link_start( $href = $_SERVER['PHP_SELF'] . '?option=history',
                          $title = 'View list of contributions' );
      }
    }

    $this->display_works_uploaded();
    $this->display_people_uploaded();
    $this->display_places_uploaded();
    $this->display_repos_uploaded();

    html::new_paragraph();
    html::italic_start();
    echo 'End of data uploaded ' . $this->postgres_date_to_dd_mm_yyyy( $this->upload_timestamp )
         . ' by ' . $contributing_person_name;
    html::italic_end();
    html::new_paragraph();
    html::anchor( 'end_of_upload_details_page' );
  }
  #-----------------------------------------------------

  function display_people_uploaded() {

    html::anchor( 'people_section' );
    html::h4_start();
    echo 'People and groups';
    html::h4_end();

    $statement = 'select * from ' . $this->proj_collect_person_tablename()
               . " where upload_id = $this->upload_id"
               . ' and iperson_id >= ' . IDS_CREATED_IN_TOOL_START # just show newly-created people
               . ' order by primary_name';
    $people = $this->db_select_into_array( $statement );
    $num_people = count( $people );

    html::new_paragraph();

    if( $num_people == 0 ) {
      echo 'No details of new people or groups were uploaded in this contribution.';
      return;
    }

    if( $num_people == 1 )
      echo 'Details of one new person or group were uploaded in this contribution.';
    else
      echo 'Details of ' . $num_people . ' new people or groups were uploaded in this contribution.';
    html::new_paragraph();

    $current_person = 0;
    foreach( $people as $row ) {
      extract( $row, EXTR_OVERWRITE );
      foreach( $row as $colname => $value ) {
        $this->$colname = $value;
      }
      $current_person++;

      html::new_paragraph();
      html::anchor( 'person' . $current_person );

      html::h5_start();
      echo "Person/group $current_person of $num_people";
      html::h5_end();

      html::span_start( 'class="widespaceonleft"' );
      if( $current_person > 1 ) {
        $prev_person = intval( $current_person ) - 1;
        html::link( '#person' . $prev_person, 'Previous person', 'Previous person' );
      }
      if( $current_person < $num_people && $current_person > 1 ) 
        echo ' | ';
      if( $current_person < $num_people ) {
        $next_person = intval( $current_person ) + 1;
        html::link( '#person' . $next_person, 'Next person', 'Next person' );
      }
      html::span_end();

      if( $current_person < $num_people || $current_person > 1 ) echo ' | ';
      $this->link_to_other_sections( $this_section = 'people' );
      echo LINEBREAK;

      $this->display_one_person_uploaded();
    }
  }
  #-----------------------------------------------------

  function display_one_person_uploaded() {

    html::div_start( 'class="queryresults"' );
    html::table_start( 'width="100%"' );
    html::tablerow_start();
    html::tabledata( 'Primary name', 'class="label"' );
    html::tabledata_start();
    $this->echo_safely( $this->primary_name );
    html::tabledata_end();
    html::tablerow_end();

    if( $this->alternative_names > '' ) {
      html::tablerow_start();
      html::tabledata( 'Alternative name(s)', 'class="label"' );
      html::tabledata_start();
      $this->echo_safely( $this->alternative_names );
      html::tabledata_end();
      html::tablerow_end();
    }

    if( $this->gender > '' ) {
      html::tablerow_start();
      html::tabledata( 'Gender', 'class="label"' );
      html::tabledata_start();
      $this->echo_safely( $this->gender );
      html::tabledata_end();
      html::tablerow_end();
    }

    if( $this->is_organisation == '0' ) $this->is_organisation = '';
    if( $this->is_organisation > '' ) {
      html::tablerow_start();
      html::tabledata( 'Organisation?', 'class="label"' );
      html::tabledata_start();
      echo 'Yes. ';
      if( $this->organisation_type ) {
        $statement = 'select org_type_desc from ' . $this->proj_org_type_tablename()
                   . " where org_type_id = $this->organisation_type";
        $desc = $this->db_select_one_value( $statement );
        echo 'Type of organisation: ' . $desc;
      }
      html::tabledata_end();
      html::tablerow_end();
    }

    if( $this->roles_or_titles > '' ) {
      html::tablerow_start();
      html::tabledata( 'Roles or titles', 'class="label"' );
      html::tabledata_start();
      $this->echo_safely( $this->roles_or_titles );
      html::tabledata_end();
      html::tablerow_end();
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
      html::tablerow_start();
      html::tabledata( 'Professional categories', 'class="label"' );
      html::tabledata_start();
      if( $num_occs > 1 ) html::ulist_start();
      foreach( $occs as $occ ) {
        extract( $occ, EXTR_OVERWRITE );
        if( $num_occs > 1 ) html::listitem_start();
        echo $role_category_desc;
        if( $num_occs > 1 ) html::listitem_end();
      }
      if( $num_occs > 1 ) html::ulist_end();
      html::tabledata_end();
      html::tablerow_end();
    }

    if( $this->date_of_birth_year || $this->date_of_birth2_year ) {
      html::tablerow_start();
      html::tabledata( 'Date of birth', 'class="label"' );
      html::tabledata_start();
      echo $this->date_of_birth_year;
      if( $this->date_of_birth2_year || $this->date_of_birth_is_range ) echo '&ndash;';
      echo $this->date_of_birth2_year;
      echo LINEBREAK;
      $flag_fields = array( 'date_of_birth_is_range', 'date_of_birth_inferred', 
                           'date_of_birth_uncertain', 'date_of_birth_approx' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with date of birth', TRUE );
      html::tabledata_end();
      html::tablerow_end();
    }        

    if( $this->date_of_death_year || $this->date_of_death2_year ) {
      html::tablerow_start();
      html::tabledata( 'Date of death', 'class="label"' );
      html::tabledata_start();
      echo $this->date_of_death_year;
      if( $this->date_of_death2_year || $this->date_of_death_is_range ) echo '&ndash;';
      echo $this->date_of_death2_year;
      echo LINEBREAK;
      $flag_fields = array( 'date_of_death_is_range', 'date_of_death_inferred', 
                           'date_of_death_uncertain', 'date_of_death_approx' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with date of death', TRUE );
      html::tabledata_end();
      html::tablerow_end();
    }        

    if( $this->flourished_year || $this->flourished2_year ) {
      html::tablerow_start();
      html::tabledata( 'Flourished', 'class="label"' );
      html::tabledata_start();
      echo $this->flourished_year;
      if( $this->flourished2_year || $this->flourished_is_range ) echo '&ndash;';
      echo $this->flourished2_year;
      html::tabledata_end();
      html::tablerow_end();
    }        

    if( $this->notes_on_person > '' ) {
      html::tablerow_start();
      html::tabledata( 'Publicly visible notes on person/group', 'class="label"' );
      html::tabledata_start();
      $this->echo_safely( $this->notes_on_person );
      html::tabledata_end();
      html::tablerow_end();
    }

    if( $this->editors_notes > '' ) {
      html::tablerow_start();
      html::tabledata( "Project editors' notes", 'class="label"' );
      html::tabledata_start();
      $this->echo_safely( $this->editors_notes );
      html::tabledata_end();
      html::tablerow_end();
    }

    $this->set_related_resources_for_display( $table_name = $this->proj_collect_person_resource_tablename(), 
                                              $id_column_name = 'iperson_id', 
                                              $id_value = $this->iperson_id );
    $this->display_simple_field( 'related_resources', 'Related resources' );

    html::table_end();
    html::div_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function display_places_uploaded() {

    html::anchor( 'places_section' );
    html::div_start( 'class="head_plus_nav"' );
    html::h4_start();
    echo 'Places';
    html::h4_end();

    html::span_start( 'class="widespaceonleft"' );
    $this->link_to_other_sections( $this_section = 'places' );
    html::span_end();
    html::div_end();
    html::new_paragraph();

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
    html::new_paragraph();

    $i = 0;
    foreach( $places as $row ) {
      $i++;
      html::h5_start();
      echo "Place $i of $num_places";
      html::h5_end();
      extract( $row, EXTR_OVERWRITE );
      $this->display_one_place( $row );
    }
    html::new_paragraph();
  }
  #-----------------------------------------------------
  function display_one_place( $place ) {

    if( ! $place[ 'location_id' ] ) die( 'Invalid input while displaying places uploaded.' );

    html::div_start( 'class="queryresults"' );
    html::table_start();

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

    html::table_end();
    html::div_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function display_repos_uploaded() {

    html::anchor( 'repositories_section' );
    html::div_start( 'class="head_plus_nav"' );
    html::h4_start();
    echo 'Repositories';
    html::h4_end();

    html::span_start( 'class="widespaceonleft"' );
    $this->link_to_other_sections( $this_section = 'repositories' );
    html::span_end();
    html::div_end();
    html::new_paragraph();

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
      html::new_paragraph();
      $this->display_one_repository( $repos[ 0 ] );
      html::new_paragraph();
      return;
    }

    echo 'Details of ' . $num_repos . ' new repositories were uploaded in this contribution.';
    html::new_paragraph();

    html::ulist_start();
    foreach( $repos as $row ) {
      extract( $row, EXTR_OVERWRITE );
      html::listitem_start();
      $this->echo_safely( $institution_name );
      html::listitem_end();
    }
    html::ulist_end();

    $i = 0;
    foreach( $repos as $row ) {
      $i++;
      html::h5_start();
      echo "Repository $i of $num_repos";
      html::h5_end();
      extract( $row, EXTR_OVERWRITE );
      $this->display_one_repository( $row );
    }
    html::new_paragraph();
  }
  #-----------------------------------------------------
  function display_one_repository( $repository ) {

    if( ! $repository[ 'institution_id' ] ) die( 'Invalid input while displaying repositories uploaded.' );

    html::div_start( 'class="queryresults"' );
    html::table_start();

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
    html::table_end();
    html::div_end();
    html::new_paragraph();
  }
  #-----------------------------------------------------
  function display_works_uploaded() {

    if( $this->debug ) echo "RG display_works_uploaded()<br>";

    html::anchor( 'works_section' );
    html::h4_start();
    echo 'Works';
    html::h4_end();

    $cstatement = 'select count(*) from ' . $this->proj_collect_work_tablename()
               . " where upload_id = $this->upload_id";
    if( $this->debug ) echo "RG display_works_uploaded($cstatement )<br>";

    $num_works = $this->db_select_one_value( $cstatement );
    if( $this->debug ) echo "RG display_works_uploaded(num_works = $num_works)<br>";

    if( $num_works == 0 ) {
      echo 'No details of new works were uploaded in this contribution.' . LINEBREAK;
      return;
    }

    if( $num_works == 1 )
      echo 'Details of one new work were uploaded in this contribution.';
    else
      echo 'Details of ' . $num_works . ' new works were uploaded in this contribution.';
    $this->contributed_works_export_button( $this->upload_id, $verbose = TRUE );
    html::new_paragraph();

    $current_work = 0;
    $LIMIT = 100;

    while ( $current_work < $num_works ) {
      $statement = 'select * from ' . $this->proj_collect_work_tablename()
                . " where upload_id = $this->upload_id order by iwork_id"
                . " LIMIT $LIMIT OFFSET $current_work ";
      if( $this->debug ) echo "RG display_works_uploaded($statement )<br>";

      $works = $this->db_select_into_array( $statement );
      if( $this->debug ) echo "RG display_works_uploaded(after select)<br>";

      foreach( $works as $row ) {
        extract( $row, EXTR_OVERWRITE );
        foreach( $row as $colname => $value ) {
          $this->$colname = $value;
        }
        $current_work++;

        html::new_paragraph();
        html::anchor( 'work' . $current_work );

        html::h5_start();
        echo "Work $current_work of $num_works";
        html::h5_end();

        html::span_start( 'class="widespaceonleft"' );
        if( $current_work > 1 ) {
          $prev_work = intval( $current_work ) - 1;
          html::link( '#work' . $prev_work, 'Previous work', 'Previous work' );
        }
        if( $current_work < $num_works && $current_work > 1 ) 
          echo ' | ';
        if( $current_work < $num_works ) {
          $next_work = intval( $current_work ) + 1;
          html::link( '#work' . $next_work, 'Next work', 'Next work' );
        }
        html::span_end();
        if( $current_work < $num_works || $current_work > 1 ) echo ' | ';
        $this->link_to_other_sections( $this_section = 'works' );
        echo LINEBREAK;

        $this->display_current_work();
      }
    }
  }
  #-----------------------------------------------------
  function display_current_work() {

    html::div_start( 'class="queryresults"' );
    html::table_start( 'width="100%"' );

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

    html::table_end();
    html::div_end();
  }
  #-----------------------------------------------------

  function accept_or_reject_work_buttons() {

    $statement = 'select status_desc as work_status_desc, editable as work_editable from ' 
               . $this->proj_collect_status_tablename()
               . " where status_id = $this->upload_status";
    $this->db_select_into_properties( $statement );

    html::tablerow_start();
    if( $this->review_underway && $this->editable ) 
      $tabledata_desc = 'Status and possible actions';
    else
      $tabledata_desc = 'Status';
    html::tabledata( LINEBREAK . $tabledata_desc, 'class="label"' );

    html::tabledata_start();
    html::bold_start();
    echo LINEBREAK . $this->work_status_desc; 
    html::bold_end();
    if( $this->union_iwork_id ) echo ' (ID: ' . $this->union_iwork_id . ')';
    echo LINEBREAK;

    html::div_start( 'class="buttonrow"' );

    if( $this->review_underway && $this->editable && $this->work_editable ) {

      html::form_start( $class_name = "review", $method_name = "accept_one_work" );
      html::hidden_field( 'upload_id', $this->upload_id );
      html::hidden_field( 'iwork_id', $this->iwork_id );
      html::submit_button( 'accept_work_' . $this->iwork_id, 'Accept' );
      echo SPACE;
      html::form_end();
      
      html::form_start( $class_name = "review", $method_name = "reject_one_work" );
      html::hidden_field( 'upload_id', $this->upload_id );
      html::hidden_field( 'iwork_id', $this->iwork_id );
      html::submit_button( 'reject_work_' . $this->iwork_id, 'Reject' );
      html::form_end();

      html::linebreak( 'class="clearleft"' );

      html::italic_start();
      echo 'Note: confirmation will be required before Accept/Reject of work.';
    }

    html::div_end();
    html::tabledata_end();
    html::tablerow_end();
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

    html::tablerow_start();
    html::tabledata( 'Date of work', 'class="label"' );
    html::tabledata_start();
    
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

    html::tabledata_end();
    html::tablerow_end();
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
   
	  // MATTT
		// echo "<!-- " . $statement . " -->";	

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

    html::tablerow_start();
    html::tabledata( $label, 'class="label"' );
    html::tabledata_start();

    if( $num_people > 1 ) html::ulist_start();
    foreach( $people as $person ) {
			// MATTT
      //echo "<!-- ";
      //var_dump( $person );
      //echo " --> ";
      if( $num_people > 1 ) html::listitem_start();
      extract( $person, EXTR_OVERWRITE );

      $this->echo_safely( $primary_name );
      if( $iperson_id >= IDS_CREATED_IN_TOOL_START ) {
        html::italic_start();
        echo " (new record)";
        html::italic_end();
      }
      echo LINEBREAK;

      if( $as_marked_field ) {
        if( $this->$as_marked_field ) {
          echo 'As marked: ';
          $this->echo_safely( $this->$as_marked_field );
          echo LINEBREAK;
        }
      }

      if( $num_people > 1 ) html::listitem_end();
    }
    if( $num_people > 1 ) html::ulist_end();

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

    html::tabledata_end();
    html::tablerow_end();
  }
  #-----------------------------------------------------

  function display_origin() {

    if( $this->origin_id || $this->origin_as_marked ) {
      html::tablerow_start();
      html::tabledata( 'Origin', 'class="label"' );
      html::tabledata_start();
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
        echo LINEBREAK;
      }

      if( $this->origin_as_marked ) {
        echo 'As marked: ';
        $this->echo_safely( $this->origin_as_marked );
        echo LINEBREAK;
      }

      $flag_fields = array( 'origin_inferred', 'origin_uncertain' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with origin', TRUE );

      html::tabledata_end();
      html::tablerow_end();
    }
  }
  #-----------------------------------------------------

  function display_destination() {

    if( $this->destination_id || $this->destination_as_marked ) {
      html::tablerow_start();
      html::tabledata( 'Destination', 'class="label"' );
      html::tabledata_start();
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
        echo LINEBREAK;
      }

      if( $this->destination_as_marked ) {
        echo 'As marked: ';
        $this->echo_safely( $this->destination_as_marked );
        echo LINEBREAK;
      }

      $flag_fields = array( 'destination_inferred', 'destination_uncertain' );
      $this->display_flags( $flag_fields, $flags_heading = 'Issues with destination', TRUE );

      html::tabledata_end();
      html::tablerow_end();
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

    html::tablerow_start();
    html::tabledata( 'Subject(s)', 'class="label"' );
    html::tabledata_start();

    if( $num_subjs > 1 ) html::ulist_start();
    foreach( $subjs as $subj ) {
      if( $num_subjs > 1 ) html::listitem_start();
      extract( $subj, EXTR_OVERWRITE );
      $this->echo_safely( $subject_desc );
      if( $num_subjs > 1 ) html::listitem_end();
    }
    if( $num_subjs > 1 ) html::ulist_end();
    html::tabledata_end();
    html::tablerow_end();
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

    html::tablerow_start();
    html::tabledata( 'Language(s)', 'class="label"' );
    html::tabledata_start();

    if( $num_langs > 1 ) html::ulist_start();
    foreach( $langs as $lang ) {
      if( $num_langs > 1 ) html::listitem_start();
      extract( $lang, EXTR_OVERWRITE );
      $this->echo_safely( $language_name );
      if( $num_langs > 1 ) html::listitem_end();
    }
    if( $num_langs > 1 ) html::ulist_end();

    if( $language_of_work ) {
      if( $num_langs == 1 ) echo LINEBREAK;
      $this->echo_safely( $language_of_work );
    }

    html::tabledata_end();
    html::tablerow_end();
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

    html::tablerow_start();
    html::tabledata( 'Places mentioned', 'class="label"' );
    html::tabledata_start();

    if( $num_places > 1 ) html::ulist_start();
    foreach( $places as $place ) {
      if( $num_places > 1 ) html::listitem_start();
      extract( $place, EXTR_OVERWRITE );
      $this->echo_safely( $location_name );
      if( $num_places > 1 ) html::listitem_end();
    }
    if( $num_places > 1 ) html::ulist_end();
    html::tabledata_end();
    html::tablerow_end();
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

    html::tablerow_start();
    html::tabledata( 'Repositories and versions', 'class="label"' );
    html::tabledata_start();

    if( $num_manifs > 1 ) html::ulist_start();
    foreach( $manifs as $manif ) {
      if( $num_manifs > 1 ) html::listitem_start();
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

      if( $num_manifs > 1 ) html::listitem_end();
    }
    if( $num_manifs > 1 ) html::ulist_end();
    html::tabledata_end();
    html::tablerow_end();
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
      html::link( $section_link, ucfirst($section), ucfirst($section) );
    }

    echo ' | ';
    html::link( '#aeolus_page_top_anchor', 'Top of page', 'Top of page' );
    echo ' | ';
    html::link( '#end_of_upload_details_page', 'Bottom of page', 'Bottom of page' );
  }
  #-----------------------------------------------------

  function display_simple_field( $fieldname, $label = NULL ) {

    if( ! $fieldname ) return;
    if( $this->$fieldname ) {
      html::tablerow_start();
      html::tabledata( $label, 'class="label"' );
      html::tabledata_start();
      $this->echo_safely_with_linebreaks( $this->$fieldname );
      html::tabledata_end();
      html::tablerow_end();
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

      html::new_paragraph();
      echo 'Informing project editors that a new contribution has been uploaded...';
      html::new_paragraph();

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

    html::form_start( $class_name = 'upload', $method_name = 'export_upload_to_csv',
                      $form_name = '', $form_target = '_blank' );

    html::hidden_field( 'upload_id', $upload_id );
    if( $verbose ) echo 'You can export details of the works in this contribution to a spreadsheet: ';
    html::submit_button( 'csv_button', 'Export' );
    html::form_end();
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

        html::h3_start();
        echo 'Excel upload to Collect';
        html::h3_end();

        html::new_paragraph();
        echo "For uploading Excel documents containing manifestations, people, places, repositories, works";

        html::form_start( $class_name = 'upload',
            $method_name = 'file_upload_excel_form_response',
            $form_name = NULL,  # use default
            $form_target = '_self',
            $onsubmit_validation = FALSE,
            $form_destination = NULL,
            $form_method='POST',
            $parms = 'enctype="multipart/form-data"' );

        html::file_upload_field( $fieldname = 'file_to_process',
            $label = "Upload file",
            $value = NULL,
            $size = CSV_UPLOAD_FIELD_SIZE );
        html::new_paragraph();

        html::submit_button( 'upload_button', 'Upload' );
        html::form_end();
    }

    #----------------------------------------------------------------------------------

    function file_upload_excel_form_response() {

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

        $foldername = pathinfo( $one_file['name'], PATHINFO_FILENAME) . microtime();

        $path = "/var/www/html/upload_folder/" . $foldername;
        if( !mkdir( $path ) ) {
            die( 'FAILED to create folder for upload - name ' . $foldername );
        }

        $moved = move_uploaded_file( $one_file['tmp_name'], $path . "/" . $foldername . ".xlsx" );
        if( $moved )
            $this->echo_safely( 'Thanks for uploading ' . $one_file['name'] . " to the server." );
        else
            die( 'FAILED TO MOVE file to image directory. Can you change the name and try again?' );

        flush();
        html::new_paragraph();
        system( "sleep 5;ls", $result );

        html::new_paragraph();
        if( $result == 0 ) {
            echo "Successfully uploaded";
        }
        else {
            echo "There was a problem uploading :( .";
        };
    }

}


