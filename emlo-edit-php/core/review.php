<?php
/*
 * PHP class for accepting or rejecting contributions from the offline data collection tool.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'CONTRIB_STATUS_NEW',           1 ); # Awaiting review
define( 'CONTRIB_STATUS_PART_REVIEWED', 2 ); # Partly reviewed
define( 'CONTRIB_STATUS_COMPLETE',      3 ); # Review complete
define( 'CONTRIB_STATUS_ACCEPTED',      4 ); # Accepted and saved into main database
define( 'CONTRIB_STATUS_REJECTED',      5 ); # Rejected

class Review extends Project {

  #-----------------------------------------------------

  function Review( &$db_connection ) { 

    $this->Project( $db_connection );

    $this->upload_obj = new Upload( $this->db_connection );

    $work_class = PROJ_COLLECTION_WORK_CLASS;
    $this->work_obj = new $work_class( $this->db_connection );

    $this->person_obj = new Person( $this->db_connection );

    $this->manif_obj = new Manifestation( $this->db_connection );

    $this->rel_obj = new Relationship( $this->db_connection );

    $this->date_entity = new Date_Entity( $this->db_connection, 'yyyy-mm-dd' );
  }
  #-----------------------------------------------------

  function accept_all_works() {

    $upload_id = $this->read_post_parm( 'upload_id' );
    if( ! $upload_id ) die( 'Invalid input.' );
    $found = $this->upload_obj->set_upload( $upload_id );
    if( ! $found ) die( 'Invalid contribution ID.' );
    $this->upload_id = $upload_id;

    if( $this->parm_found_in_post( 'confirm_accept_all_button' )) {
      $this->proceed_with_accepting_all_works();
    }
    else {
      $this->confirm_accepting_all_works();
    }
  }
  #-----------------------------------------------------

  function reject_all_works() {

    $upload_id = $this->read_post_parm( 'upload_id' );
    if( ! $upload_id ) die( 'Invalid input.' );
    $found = $this->upload_obj->set_upload( $upload_id );
    if( ! $found ) die( 'Invalid contribution ID.' );
    $this->upload_id = $upload_id;

    if( $this->parm_found_in_post( 'confirm_reject_all_button' )) {
      $this->proceed_with_rejecting_all_works();
    }
    else {
      $this->confirm_rejecting_all_works();
    }
  }
  #-----------------------------------------------------

  function confirm_accepting_all_works() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;

    $this->upload_obj->upload_details( $header_only = TRUE );

    $num_works = $this->count_outstanding_works( $upload_id );
    if( $num_works < 1 ) {
      echo 'Review is complete for all works in this contribution.' . LINEBREAK;
      return;
    }

    $this->write_reviewform_stylesheet();
    HTML::div_start( 'class="reviewform"' );
    HTML::form_start( $class_name = 'review', $method_name = 'accept_all_works' );

    HTML::h4_start();
    echo 'Number of works still awaiting review: ' . $num_works;
    HTML::h4_end();
    echo 'Any new people, places and repositories referenced in the works will also be added to the system'
         . ' if you click Accept.';

    HTML::hidden_field( 'upload_id', $upload_id );

    $this->fields_for_all_works( $multiple_works = TRUE );

    HTML::submit_button( 'confirm_accept_all_button', 'Accept' );
    HTML::form_end();
    HTML::div_end();

    HTML::new_paragraph();

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::form_end();

    echo LINEBREAK;
  }
  #-----------------------------------------------------

  function confirm_rejecting_all_works() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;

    $this->upload_obj->upload_details( $header_only = TRUE );

    $num_works = $this->count_outstanding_works( $upload_id );
    if( $num_works < 1 ) {
      echo 'Review is complete for all works in this contribution.' . LINEBREAK;
      return;
    }

    $this->write_buttonrow_stylesheet();
    HTML::div_start( 'class="buttonrow"' );

    HTML::h4_start();
    echo 'Reject all ' . $num_works . ' works from this contribution';
    HTML::h4_end();
    HTML::italic_start();
    echo 'Please note that, if you click Reject, this will be a permanent rejection.'
          . ' There is currently no mechanism for reversing it.';
    HTML::italic_end();
    HTML::new_paragraph();

    HTML::form_start( $class_name = 'review', $method_name = 'reject_all_works' );
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::submit_button( 'confirm_reject_all_button', 'Reject' );
    HTML::form_end();

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::form_end();

    echo LINEBREAK;
    HTML::div_end();
  }
  #-----------------------------------------------------

  function proceed_with_accepting_all_works() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;

    $this->upload_obj->upload_details( $header_only = TRUE, $suppress_status = TRUE );

    $statement = 'select * from ' . $this->proj_collect_work_tablename() 
               . " where upload_id = $upload_id and work_id is null"
               . ' and upload_status = ' . CONTRIB_STATUS_NEW;
    $works = $this->db_select_into_array( $statement );
    $num_works = count( $works );
    $curr_work = 0;
    echo "Processing $num_works works plus any related people, places or repositories..." . LINEBREAK;
    flush();

    foreach( $works as $work ) {
      $curr_work++;
      HTML::bold_start();
      echo LINEBREAK . "Processing work $curr_work of $num_works ..." . LINEBREAK;
      HTML::bold_end();

      $this->save_one_work( $upload_id, $work );
    }

    $this->contributor_contact_link( $upload_id );

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
    echo 'Back to list of contributions: ';
    HTML::submit_button( 'back_to_upload_list_button', 'Back' );
    echo SPACE;
    HTML::form_end();
  }
  #-----------------------------------------------------

  function save_one_work( $upload_id, $work ) {

    $this->clear();

    #-----------------------------
    # Select details of the upload
    #-----------------------------
    $statement = 'select * from ' . $this->proj_collect_upload_tablename() . " where upload_id = $upload_id";
    $this->db_select_into_properties( $statement );
    if( ! $this->upload_id ) die( 'Invalid input.' );

    #---------------------------------------------------
    # Prepare values ready to be saved in the work table
    #---------------------------------------------------
    if( ! is_array( $work )) die( 'Invalid input' );  # row from the 'collect work' table
    extract( $work, EXTR_OVERWRITE ); # copy into variables
    foreach( $work as $colname => $value ) {

      # Tidy up some of the values
      if( $colname == 'original_calendar' && $value == 'J' )
        $value = 'JJ';  # Julian, year starting January

      elseif( $colname == 'accession_code' ) {
        $value = '';
        if( $this->parm_found_in_post( 'accession_code' )) {
          $value = $this->read_post_parm( 'accession_code' );
          $value = trim( $value );
        }

        if( ! $value )
          $value = $this->upload_description;

        if( strlen( $value ) > MAX_SIZE_ACCESSION_CODE )
          $value = substr( $value, 0, MAX_SIZE_ACCESSION_CODE );
      }

      if( $colname == 'iwork_id' ) {
        $openoffice_iwork_id = $iwork_id;
        continue; # get a new ID value instead of using this one
      }

      $this->$colname = $value; # copy into properties
      $this->write_post_parm( $colname, $value ); # to be picked up by Editable Work when saving
    }

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    #----------------------------------------------
    # Get a new work ID (integer and text versions)
    #----------------------------------------------

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_work_tablename()) . "'::regclass )";
    $union_iwork_id = $this->db_select_one_value( $statement );

    $function_name = $this->get_system_prefix() . '_common_make_text_id';
    $statement = "select $function_name( '" . $this->proj_work_tablename() . "', "
                                            . "'iwork_id', "
                                            . "$union_iwork_id )";
    $work_id = $this->db_select_one_value( $statement ); 

    #------------------------------------
    # Insert a very basic skeleton record
    #------------------------------------
    $statement = 'insert into ' . $this->proj_work_tablename() . '(work_id, iwork_id, accession_code) values ('
               . "'$work_id', $union_iwork_id, '" . $this->escape( $this->accession_code ) . "' )";
    $this->db_run_query( $statement );

    #----------------------------------------
    # Save the new IDs in the 'collect' table
    #----------------------------------------
    $statement = 'update ' . $this->proj_collect_work_tablename()
               . " set union_iwork_id = $union_iwork_id, " 
               . " work_id = '$work_id' "
               . " where iwork_id = $openoffice_iwork_id"
               . " and upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    # Initialise the Work object ready to save the rest of the data
    $this->work_obj->set_work( $union_iwork_id );
    $this->write_post_parm( 'iwork_id', $union_iwork_id ); # to be picked up by the Editable Work object
    $this->write_post_parm( 'work_id', $work_id ); # to be picked up by the Editable Work object
    
    #-------------------------------------------------------------------------------------------
    # Now save the core data as if we were doing it via the editing interface, one tab at a time
    #-------------------------------------------------------------------------------------------
    $tabs = $this->work_obj->list_tabs( $get_all_possible = TRUE );
    foreach( $tabs as $tab => $tab_title ) {
 echo 'tab: '. $tab .  LINEBREAK;			//RG_DEBUG
      if( $tab == 'manifestations_tab' ) continue;  # need to handle separately
      if( $tab == 'overview_tab' ) continue;  # no editable fields on overview tab

      $this->work_obj->set_fields_and_functions( $tab );
      $statement = $this->work_obj->get_core_work_update_statement();
      $this->db_run_query( $statement );
    }

    #----------------------------------------------------
    # A few more tweaks to dates e.g. Julian to Gregorian
    #----------------------------------------------------
    $this->process_work_dates( $work_id );

    echo 'Saved core details of work...' . LINEBREAK;
    //echo 'RG reached here 0...' . LINEBREAK;    

    #----------------------------------------------------------------------
    # Now add relationships and any other complicated bits and bobs.
    # See if we need to create any people, places or repositories for this.
    #----------------------------------------------------------------------

    # See if we need to create any people
    $person_tables = array( $this->proj_collect_addressee_of_work_tablename(),
                            $this->proj_collect_author_of_work_tablename(),
                            $this->proj_collect_person_mentioned_in_work_tablename() );
    $saved_count = 0;
    foreach( $person_tables as $tab ) {
      $statement = "select iperson_id from $tab where upload_id = $this->upload_id"
                 . " and iwork_id = $openoffice_iwork_id";
      $people = $this->db_select_into_array( $statement );
      $num_people = count( $people );
      if( $num_people == 0 ) continue;

      foreach( $people as $person ) {
        $iperson_id = $person[ 'iperson_id' ];

        # Although originally created in the offline tool, it is possible that this person has
        # already been saved into the main database, so check this before inserting them.
        $statement = 'select union_iperson_id from ' . $this->proj_collect_person_tablename()
                   . " where upload_id = $this->upload_id and iperson_id = $iperson_id";
        $union_iperson_id = NULL;
        $union_iperson_id = $this->db_select_one_value( $statement );

        if( ! $union_iperson_id ) {
          $this->save_one_person( $iperson_id );
          $saved_count++;
          if( $saved_count == 1 ) echo LINEBREAK;
          echo "Saved $this->foaf_name ... " . LINEBREAK;
        }
      }
    }
    if( $saved_count ) {
      echo "Saved $saved_count new people/groups." . LINEBREAK;
    }
    //echo 'RG reached here 1...' . LINEBREAK;    

    # See if we need to create any places
    $saved_count = 0;
    $statement = 'select location_id from ' . $this->proj_collect_place_mentioned_in_work_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id"
               . ' union '
               . ' select origin_id as location_id from ' . $this->proj_collect_work_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id"
               . ' union '
               . ' select destination_id as location_id from ' . $this->proj_collect_work_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id";
    $places = $this->db_select_into_array( $statement );
    $num_places = count( $places );
    if( $num_places > 0 ) {
      foreach( $places as $place ) {
        $location_id = $place[ 'location_id' ];
        if( ! $location_id ) continue;  # blank origin or destination ID

        $statement = 'select union_location_id from ' . $this->proj_collect_location_tablename()
                   . " where upload_id = $this->upload_id and location_id = $location_id";
        $union_location_id = NULL;
        $union_location_id = $this->db_select_one_value( $statement );

        if( ! $union_location_id ) {
          $this->save_one_location( $location_id );
          $saved_count++;
          if( $saved_count == 1 ) echo LINEBREAK;
          echo "Saved $this->location_name ... " . LINEBREAK;
        }
      }
      if( $saved_count ) {
        echo "Saved $saved_count new places." . LINEBREAK;
      }
    }

    //echo 'RG reached here 2...' . LINEBREAK;    
    # See if we need to create any repositories
    $saved_count = 0;
    $statement = 'select repository_id from ' . $this->proj_collect_manifestation_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id";
// echo 'statement: '. $statement .  LINEBREAK;			//RG_DEBUG
    $repositories = $this->db_select_into_array( $statement );
    $num_repos = count( $repositories );
// echo 'num_repos: '. $num_repos .  LINEBREAK;			//RG_DEBUG
    if( $num_repos > 0 ) {
// echo 'num_repos: '. $num_repos .  LINEBREAK;			//RG_DEBUG
// print_r($repositories); //RG_DEBUG
      foreach( $repositories as $repos ) {
        $repository_id = $repos[ 'repository_id' ];
// echo 'repository_id: '. $repository_id .  LINEBREAK;			//RG_DEBUG
        if( ! $repository_id ) continue;

        $statement = 'select union_institution_id from ' . $this->proj_collect_institution_tablename()
                   . " where upload_id = $this->upload_id and institution_id = $repository_id";
// echo 'statement: '. $statement .  LINEBREAK;			//RG_DEBUG
        $union_institution_id = NULL;
        $union_institution_id = $this->db_select_one_value( $statement );
// echo 'union_institution_id: '. $union_institution_id .  LINEBREAK;			//RG_DEBUG

        if( ! $union_institution_id ) {
          $this->save_one_institution( $repository_id );
          $saved_count++;
          if( $saved_count == 1 ) echo LINEBREAK;
          echo "Saved $this->institution_name ... " . LINEBREAK;
        }
      }
      if( $saved_count ) {
        echo "Saved $saved_count new repositories." . LINEBREAK;
      }
    }

    //echo 'RG reached here 3...' . LINEBREAK;    
    echo LINEBREAK;

    # See if we need to create any manifestations
    $statement = 'select manifestation_id from ' . $this->proj_collect_manifestation_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id";
    $manifs = $this->db_select_into_array( $statement );
    $num_manifs = count( $manifs );
    echo "RG reached here 3a...$num_manifs" . LINEBREAK;    
	print_r($manifs);
    if( $num_manifs > 0 ) {
      foreach( $manifs as $manif ) {
        $imanifestation_id = $manif[ 'manifestation_id' ];
    echo "RG reached here 3b...$imanifestation_id" . LINEBREAK;    
        $this->save_one_manifestation( $imanifestation_id, $work_id, $union_iwork_id );
      }
    }

    # Save languages as individual entries in the 'language of work' table
    $this->save_language_of_work( $work_id, $openoffice_iwork_id );

    //echo 'RG reached here 4...' . LINEBREAK;    
    # Save authors, addressees etc.
    $this->save_work_relationships( $work_id, $union_iwork_id, $openoffice_iwork_id );

    //echo 'RG reached here 5...' . LINEBREAK;    
    # Update the work status to 'Accepted'
    $statement = 'update ' . $this->proj_collect_work_tablename() . ' set upload_status = ' . CONTRIB_STATUS_ACCEPTED
               . " where upload_id = $this->upload_id and iwork_id = $openoffice_iwork_id";
    $this->db_run_query( $statement );

    # Upload the status of the whole upload as required
    $new_status = $this->get_new_status_of_upload();

    $statement = 'update ' . $this->proj_collect_upload_tablename() . " set upload_status = $new_status, "
               . ' works_accepted = works_accepted + 1 '
               . " where upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    $statement = 'select description from ' . $this->proj_work_tablename()
               . " where iwork_id = $union_iwork_id";
    $description = $this->db_select_one_value( $statement );
    $this->echo_safely( "Saved work ID $union_iwork_id : $description" );
    echo LINEBREAK;

    $statement = 'select status_desc from ' . $this->proj_collect_status_tablename()
               . " where status_id = $new_status";
    $new_status_desc = $this->db_select_one_value( $statement );
    HTML::italic_start();
    echo 'Status of contribution is now: ' . $new_status_desc . LINEBREAK;
    HTML::italic_end();

    $anchor_name = 'saved_' . $union_iwork_id . '_anchor';
    HTML::anchor( $anchor_name );
    $script = "document.location.hash = '#$anchor_name'";
    HTML::write_javascript_function( $script );
    flush();
  }
  #-----------------------------------------------------
  function save_one_person( $iperson_id ) {

    if( ! $iperson_id ) die( 'Invalid input.' );

    $statement = 'select * from ' . $this->proj_collect_person_tablename()
               . " where upload_id = $this->upload_id and iperson_id = $iperson_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) die( 'Invalid person ID.' );
    $person = $result[0];
    foreach( $person as $colname => $value ) {

      if( $colname == 'iperson_id' ) {
        $openoffice_iperson_id = $iperson_id;
        continue; # get a new ID value instead of using this one
      }
      elseif( $colname == 'primary_name' )
        $colname = 'foaf_name';
      elseif( $colname == 'alternative_names' )
        $colname = 'skos_altlabel';
      elseif( $colname == 'roles_or_titles' )
        $colname = 'person_aliases';
      elseif( $colname == 'is_organisation' ) {
        if( $value == '1' )
          $value = 'Y';
        else
          $value = '';
      }

      $this->$colname = $value; # copy into properties
    }
    
    #-------------------------------------------------
    # Generate complete dates from year, month and day
    #-------------------------------------------------
    if( ! $this->date_entity ) 
      $this->date_entity = new Date_Entity( $this->db_connection, 'yyyy-mm-dd' );

    $this->date_of_birth = NULL;
    $this->date_of_death = NULL;
    $this->flourished    = NULL;

    $is_julian = FALSE;
    if( $this->original_calendar == 'JM' || $this->original_calendar == 'JJ' )
      $is_julian = TRUE;

    if( $this->date_of_birth2_year > 0 )
      $this->date_of_birth = $this->date_entity->make_complete_date( $this->date_of_birth2_year,
                                                                     $this->date_of_birth2_month,
                                                                     $this->date_of_birth2_day,
                                                                     $is_julian );
    elseif( $this->date_of_birth_year > 0 )
      $this->date_of_birth = $this->date_entity->make_complete_date( $this->date_of_birth_year,
                                                                     $this->date_of_birth_month,
                                                                     $this->date_of_birth_day,
                                                                     $is_julian );

    if( $this->date_of_death2_year > 0 )
      $this->date_of_death = $this->date_entity->make_complete_date( $this->date_of_death2_year,
                                                                     $this->date_of_death2_month,
                                                                     $this->date_of_death2_day,
                                                                     $is_julian );
    elseif( $this->date_of_death_year > 0 )
      $this->date_of_death = $this->date_entity->make_complete_date( $this->date_of_death_year,
                                                                     $this->date_of_death_month,
                                                                     $this->date_of_death_day,
                                                                     $is_julian );

    if( $this->flourished2_year > 0 )
      $this->flourished = $this->date_entity->make_complete_date( $this->flourished2_year,
                                                                  $this->flourished2_month,
                                                                  $this->flourished2_day,
                                                                  $is_julian );
    elseif( $this->flourished_year > 0 )
      $this->flourished = $this->date_entity->make_complete_date( $this->flourished_year,
                                                                  $this->flourished_month,
                                                                  $this->flourished_day,
                                                                  $is_julian );

    if( $this->date_of_birth2_year ) $this->date_of_birth_is_range = 1;
    if( $this->date_of_death2_year ) $this->date_of_death_is_range = 1;
    if( $this->flourished2_year ) $this->flourished_is_range = 1;

    #----------------------------------------------
    # Get a new person ID (integer and text versions)
    #----------------------------------------------

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_person_tablename()) . "'::regclass )";
    $union_iperson_id = $this->db_select_one_value( $statement );

    $function_name = $this->get_system_prefix() . '_common_make_text_id';
    $statement = "select $function_name( '" . $this->proj_person_tablename() . "', "
                                            . "'iperson_id', "
                                            . "$union_iperson_id )";
    $person_id = $this->db_select_one_value( $statement ); 
    $this->person_id = $person_id;

    #-------------------------------------------
    # Insert a very basic skeleton person record
    #-------------------------------------------
    $statement = 'insert into ' . $this->proj_person_tablename() . '(person_id, iperson_id, foaf_name) values ('
               . "'$person_id', $union_iperson_id, '" . $this->escape( $this->foaf_name ) . "' )";
    $this->db_run_query( $statement );

    # Initialise the person object ready to save the rest of the data
    $this->person_obj->set_person( $union_iperson_id );

    #-------------------------------------
    # Add the rest of the core person data
    #-------------------------------------
    $columns = $this->db_list_columns( $this->proj_person_tablename());

    # Some columns are auto-generated so no need to save them here.
    $columns_to_skip = array( 'person_id', 'iperson_id', 
                              'change_timestamp', 'change_user',
                              'creation_timestamp', 'creation_user', 
                              'other_details_summary', 'other_details_summary_searchable', 'uuid' );
    $i = 0;
    $statement = 'update ' . $this->proj_person_tablename() . ' set ';

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;
      if( in_array( $column_name, $columns_to_skip ) ) $skip_it = TRUE;
      if( $skip_it ) continue;

      $i++;
      if( $i > 1 ) $statement .= ', ';

      $column_value = $this->$column_name;

      $statement .= $column_name . ' = ';
      $column_value = $this->person_obj->prepare_value_for_save( $column_name, $column_value, 
                                                                 $is_numeric, $is_date );
      $statement .= $column_value;
    }

    $statement .= " where person_id = '$person_id'";
    $this->db_run_query( $statement );

    #-------------------------------------------
    # Now save the new ID in the 'collect' table
    #-------------------------------------------
    $statement = 'update ' . $this->proj_collect_person_tablename()
               . " set union_iperson_id = $union_iperson_id, " 
               . " person_id = '$person_id' "
               . " where iperson_id = $openoffice_iperson_id"
               . " and upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    #-------------------------------
    # Add any comments on the person
    #-------------------------------
    if( $this->notes_on_person )
      $this->save_comments_on_table( $this->proj_person_tablename(), $person_id, $this->notes_on_person );

    #------------------
    # Add occupation(s)
    #------------------
    $this->save_person_occupations( $person_id, $union_iperson_id );

    #----------------------
    # Add related resources
    #----------------------
    $this->save_person_resources( $person_id, $openoffice_iperson_id );
  }
  #-----------------------------------------------------
  function save_one_location( $location_id ) {

    if( ! $location_id ) die( 'Invalid input.' );

    $statement = 'select * from ' . $this->proj_collect_location_tablename()
               . " where upload_id = $this->upload_id and location_id = $location_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) die( 'Invalid location ID.' );
    $location = $result[0];
    foreach( $location as $colname => $value ) {

      if( $this->debug ) echo "RG save_one_location() colname: $colname = $value ";

      if( $colname == 'location_id' ) {
        $openoffice_location_id = $location_id;
        continue; # get a new ID value instead of using this one
      }
      $this->$colname = $value; # copy into properties
    }

    #----------------------
    # Get a new location ID
    #----------------------

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_location_tablename()) 
               . "'::regclass )";
    $union_location_id = $this->db_select_one_value( $statement );

    #-----------------------
    # Insert location record
    #-----------------------
    $statement = 'insert into ' . $this->proj_location_tablename() 
               . ' (location_id, location_name, element_1_eg_room, element_2_eg_building,'
               . ' element_3_eg_parish, element_4_eg_city, element_5_eg_county, element_6_eg_country,'
               . ' element_7_eg_empire,latitude,longitude,location_synonyms,editors_notes) values ('
               . " $union_location_id, '" . $this->escape( $this->location_name ) . "', "
               . "'" . $this->escape( $this->element_1_eg_room ) . "', "
               . "'" . $this->escape( $this->element_2_eg_building ) . "', "
               . "'" . $this->escape( $this->element_3_eg_parish ) . "', "
               . "'" . $this->escape( $this->element_4_eg_city )   . "', "
               . "'" . $this->escape( $this->element_5_eg_county ) . "', "
               . "'" . $this->escape( $this->element_6_eg_country ). "', "
               . "'" . $this->escape( $this->element_7_eg_empire ) . "', "
               . "'" . $this->escape( $this->latitude )            . "', "
               . "'" . $this->escape( $this->longitude )           . "', "
               . "'" . $this->escape( $this->location_synonyms )   . "', "
               . "'" . $this->escape( $this->editors_notes )       . "' )";
    $this->db_run_query( $statement );

    #-------------------------------------------
    # Now save the new ID in the 'collect' table
    #-------------------------------------------
    $statement = 'update ' . $this->proj_collect_location_tablename()
               . " set union_location_id = $union_location_id "
               . " where location_id = $openoffice_location_id"
               . " and upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    #----------------------
    # Add related resources
    #----------------------
    $this->save_location_resources( $union_location_id, $openoffice_location_id );
  }
  #-----------------------------------------------------
  function save_one_institution( $institution_id ) {

    if( ! $institution_id ) die( 'Invalid input.' );

    $statement = 'select * from ' . $this->proj_collect_institution_tablename()
               . " where upload_id = $this->upload_id and institution_id = $institution_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) die( 'Invalid institution ID.' );
    $institution = $result[0];
    foreach( $institution as $colname => $value ) {

      if( $colname == 'institution_id' ) {
        $openoffice_institution_id = $institution_id;
        continue; # get a new ID value instead of using this one
      }
      $this->$colname = $value; # copy into properties
    }

    #-------------------------
    # Get a new institution ID
    #-------------------------

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_institution_tablename()) 
               . "'::regclass )";
    $union_institution_id = $this->db_select_one_value( $statement );

    #--------------------------
    # Insert institution record
    #--------------------------
    $statement = 'insert into ' . $this->proj_institution_tablename() 
               . ' (institution_id, institution_name, institution_city, institution_country)'
               . ' values ('
               . " $union_institution_id, '" . $this->escape( $this->institution_name ) . "', "
               . "'" . $this->escape( $this->institution_city ) . "', "
               . "'" . $this->escape( $this->institution_country ) . "')";
    $this->db_run_query( $statement );

    #-------------------------------------------
    # Now save the new ID in the 'collect' table
    #-------------------------------------------
    $statement = 'update ' . $this->proj_collect_institution_tablename()
               . " set union_institution_id = $union_institution_id "
               . " where institution_id = $openoffice_institution_id"
               . " and upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    #----------------------
    # Add related resources
    #----------------------
    $this->save_institution_resources( $union_institution_id, $openoffice_institution_id );
  }
  #-----------------------------------------------------
  function save_one_manifestation( $imanifestation_id, $work_id, $union_iwork_id ) {

    //echo "RG reached here man0a ...$imanifestation_id" . LINEBREAK;    
    if( ! $imanifestation_id ) die( 'Invalid input.manifestation ' );

    //echo 'RG reached here man1...' . LINEBREAK;    
    $statement = 'select * from ' . $this->proj_collect_manifestation_tablename()
               . " where upload_id = $this->upload_id and manifestation_id = $imanifestation_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) die( 'Invalid manifestation ID.' );
    $manifestation = $result[0];
    foreach( $manifestation as $colname => $value ) {

      if( $colname == 'manifestation_id' )
        continue; # get a new ID value instead of using this one

      elseif( $colname == 'iwork_id' )
        $value = $union_iwork_id;  # use Union work ID, not the one from the data collection tool

      $this->$colname = $value; # copy into properties
      $this->write_post_parm( $colname, $value ); # to be picked up by manifestation object
    }

    #------------------------------------------
    # Get a new manifestation ID (text version)
    #------------------------------------------
    $this->manif_obj->clear();
    $this->manif_obj->work_id = $work_id;

    $union_manifestation_id = $this->manif_obj->get_new_manifestation_id(); # do this before inserting
                                                                            # so value is correct
    $insert_statement = $this->manif_obj->get_manifestation_insert_statement();
    $this->db_run_query( $insert_statement );
    $this->echo_safely ( 'Saved manifestation: shelfmark or printed editon details:'
                         . " $this->id_number_or_shelfmark $this->printed_edition_details" );
    echo LINEBREAK;

    #---------------------------------------------------------
    # Create an 'is manifestation of' relationship to the work
    #---------------------------------------------------------
    $this->rel_obj->insert_relationship( $left_table_name = $this->proj_manifestation_tablename(), 
                                         $left_id_value = $union_manifestation_id,
                                         $relationship_type = RELTYPE_MANIFESTATION_IS_OF_WORK,
                                         $right_table_name = $this->proj_work_tablename(), 
                                         $right_id_value = $work_id );

    #------------------------------------------------
    # Create a relationship to the repository, if any
    #------------------------------------------------
    if( $this->repository_id ) {
      $statement = 'select union_institution_id from ' . $this->proj_collect_institution_tablename()
                 . " where upload_id = $this->upload_id and institution_id = $this->repository_id";
      $union_repository_id = $this->db_select_one_value( $statement );

      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_manifestation_tablename(), 
                                           $left_id_value = $union_manifestation_id,
                                           $relationship_type = RELTYPE_MANIF_STORED_IN_REPOS,
                                           $right_table_name = $this->proj_institution_tablename(), 
                                           $right_id_value = $union_repository_id );
    }

    #---------------------
    # Add comments, if any
    #---------------------
    if( $this->manifestation_notes )
      $this->save_comments_on_table( $this->proj_manifestation_tablename(), $union_manifestation_id, 
                                     $this->manifestation_notes, 'manifestation' );

    #--------------------------------------------
    # I'll put image filenames into notes for now
    #--------------------------------------------
    if( $this->image_filenames )
      $this->save_comments_on_table( $this->proj_manifestation_tablename(), $union_manifestation_id, 
                                     'Image filenames or URLs: ' . $this->image_filenames, 'manifestation' );

    #-------------------------------------------
    # Now save the new ID in the 'collect' table
    #-------------------------------------------
    $statement = 'update ' . $this->proj_collect_manifestation_tablename()
               . " set union_manifestation_id = '$union_manifestation_id'"
               . " where manifestation_id = $imanifestation_id"
               . " and upload_id = $this->upload_id";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function save_work_relationships( $work_id, $union_iwork_id, $openoffice_iwork_id ) {

    $this->work_id = $work_id; 
    $this->union_iwork_id = $union_iwork_id;
echo "save_work_relationships( $work_id, $union_iwork_id, $openoffice_iwork_id )"  .  LINEBREAK;			//RG_DEBUG

    $this->save_person_to_work_relationships( $this->proj_collect_author_of_work_tablename(),
                                              RELTYPE_PERSON_CREATOR_OF_WORK,
                                              $work_side = 'right', $desc = 'author' );
echo "save_work_relationships( addressee )"  .  LINEBREAK;			//RG_DEBUG

    $this->save_person_to_work_relationships( $this->proj_collect_addressee_of_work_tablename(),
                                              RELTYPE_WORK_ADDRESSED_TO_PERSON,
                                              $work_side = 'left', $desc = 'addressee' );
echo "save_work_relationships( person mentioned )"  .  LINEBREAK;			//RG_DEBUG

    $this->save_person_to_work_relationships( $this->proj_collect_person_mentioned_in_work_tablename(),
                                              RELTYPE_WORK_MENTIONS_PERSON,
                                              $work_side = 'left', $desc = 'person mentioned' );
echo "save_work_to_place_relationships( )"  .  LINEBREAK;			//RG_DEBUG

    $this->save_work_to_place_relationships();
echo "save_work_to_subject_relationships( )"  .  LINEBREAK;			//RG_DEBUG

    $this->save_work_to_subject_relationships();

echo "save_comments_on_work( )"  .  LINEBREAK;			//RG_DEBUG
    $this->save_comments_on_work( 'notes_on_letter', RELTYPE_COMMENT_REFERS_TO_ENTITY );
    $this->save_comments_on_work( 'notes_on_date_of_work', RELTYPE_COMMENT_REFERS_TO_DATE, 'date of work' );
    $this->save_comments_on_work( 'notes_on_authors', RELTYPE_COMMENT_REFERS_TO_AUTHOR, 'author' );
    $this->save_comments_on_work( 'notes_on_addressees', RELTYPE_COMMENT_REFERS_TO_ADDRESSEE, 'addressee' );
    $this->save_comments_on_work( 'notes_on_people_mentioned', RELTYPE_COMMENT_REFERS_TO_PEOPLE_MENTIONED_IN_WORK, 
                                  'people mentioned' );

echo "save_work_resources( )"  .  LINEBREAK;			//RG_DEBUG
    $this->save_work_resources( $work_id, $openoffice_iwork_id );
  }
  #-----------------------------------------------------

  function save_person_to_work_relationships( $tab, $reltype, $work_side, $desc = NULL ) {
echo "save_person_to_work_relationships( $tab, $reltype, $work_side, $desc )"  .  LINEBREAK;			//RG_DEBUG

    $statement = "select p.person_id, p.primary_name from $tab x, "
               . $this->proj_collect_person_tablename() . ' p, '
               . $this->proj_collect_work_tablename() . ' w '
               . " where x.upload_id = $this->upload_id"
               . " and x.upload_id = p.upload_id"
               . " and x.upload_id = w.upload_id"
               . ' and x.iperson_id = p.iperson_id'
               . ' and x.iwork_id = w.iwork_id'
               . " and w.work_id = '$this->work_id' and w.union_iwork_id = $this->union_iwork_id" ;
// echo 'statement: '. $statement .  LINEBREAK;			//RG_DEBUG
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) == 0 ) return;

// echo 'results: '. $results .  LINEBREAK;			//RG_DEBUG
print_r($results); //RG_DEBUG
echo LINEBREAK; //RG_DEBUG
    foreach( $results as $result ) {
      extract( $result, EXTR_OVERWRITE );

      if( $work_side == 'right' ) {
        $this->rel_obj->insert_relationship( $left_table_name = $this->proj_person_tablename(), 
                                             $left_id_value = $person_id,
                                             $relationship_type = $reltype,
                                             $right_table_name = $this->proj_work_tablename(), 
                                             $right_id_value = $this->work_id );
      }
      else {
        $this->rel_obj->insert_relationship( $left_table_name = $this->proj_work_tablename(), 
                                             $left_id_value = $this->work_id,
                                             $relationship_type = $reltype,
                                             $right_table_name = $this->proj_person_tablename(), 
                                             $right_id_value = $person_id );
      }
      echo "Saved $desc: ";
      $this->echo_safely( $primary_name );
      echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function save_work_to_place_relationships() {

    #-------
    # Origin
    #-------
    $statement = 'select l.union_location_id, l.location_name from ' 
               . $this->proj_collect_location_tablename() . ' l, '
               . $this->proj_collect_work_tablename() . ' w '
               . " where l.upload_id = $this->upload_id"
               . " and w.upload_id = l.upload_id"
               . ' and w.origin_id = l.location_id'
               . " and w.work_id = '$this->work_id' and w.union_iwork_id = $this->union_iwork_id"
               . ' and w.origin_id > 0' ;
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) > 0 ) {
      $result = $results[ 0 ];
      extract( $result, EXTR_OVERWRITE );
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_work_tablename(), 
                                           $left_id_value = $this->work_id,
                                           $relationship_type = RELTYPE_WORK_SENT_FROM_PLACE,
                                           $right_table_name = $this->proj_location_tablename(), 
                                           $right_id_value = $union_location_id );
      echo 'Saved place of origin: ';
      $this->echo_safely( $location_name );
      echo LINEBREAK;
    }

    #------------
    # Destination
    #------------
    $statement = 'select l.union_location_id, l.location_name from ' 
               . $this->proj_collect_location_tablename() . ' l, '
               . $this->proj_collect_work_tablename() . ' w '
               . " where l.upload_id = $this->upload_id"
               . " and w.upload_id = l.upload_id"
               . ' and w.destination_id = l.location_id'
               . " and w.work_id = '$this->work_id' and w.union_iwork_id = $this->union_iwork_id"
               . ' and w.destination_id > 0' ;
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) > 0 ) {
      $result = $results[ 0 ];
      extract( $result, EXTR_OVERWRITE );
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_work_tablename(), 
                                           $left_id_value = $this->work_id,
                                           $relationship_type = RELTYPE_WORK_SENT_TO_PLACE,
                                           $right_table_name = $this->proj_location_tablename(), 
                                           $right_id_value = $union_location_id );
      echo 'Saved destination: ';
      $this->echo_safely( $location_name );
      echo LINEBREAK;
    }

    #-----------------
    # Places mentioned 
    #-----------------
    $statement = 'select l.union_location_id, l.location_name from ' 
               . $this->proj_collect_place_mentioned_in_work_tablename() . ' pm, ' 
               . $this->proj_collect_location_tablename() . ' l, '
               . $this->proj_collect_work_tablename() . ' w '
               . " where pm.upload_id = $this->upload_id"
               . " and pm.upload_id = l.upload_id"
               . " and pm.upload_id = w.upload_id"
               . ' and pm.location_id = l.location_id'
               . ' and pm.iwork_id = w.iwork_id'
               . " and w.work_id = '$this->work_id' and w.union_iwork_id = $this->union_iwork_id" ;
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) == 0 ) return;

    foreach( $results as $result ) {
      extract( $result, EXTR_OVERWRITE );
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_work_tablename(), 
                                           $left_id_value = $this->work_id,
                                           $relationship_type = RELTYPE_WORK_MENTIONS_PLACE,
                                           $right_table_name = $this->proj_location_tablename(), 
                                           $right_id_value = $union_location_id );
      echo 'Saved place mentioned: ';
      $this->echo_safely( $location_name );
      echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function save_work_to_subject_relationships() {

    $statement = 'select s.subject_id, s.subject_desc from ' 
               . $this->proj_subject_tablename() . ' s, '
               . $this->proj_collect_subject_of_work_tablename() . ' sw, ' 
               . $this->proj_collect_work_tablename() . ' w '
               . " where sw.upload_id = $this->upload_id"
               . " and sw.upload_id = w.upload_id"
               . ' and sw.iwork_id = w.iwork_id'
               . ' and sw.subject_id = s.subject_id'
               . " and w.work_id = '$this->work_id' and w.union_iwork_id = $this->union_iwork_id" ;
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) == 0 ) return;

    foreach( $results as $result ) {
      extract( $result, EXTR_OVERWRITE );
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_work_tablename(), 
                                           $left_id_value = $this->work_id,
                                           $relationship_type = RELTYPE_WORK_DEALS_WITH_SUBJECT,
                                           $right_table_name = $this->proj_subject_tablename(), 
                                           $right_id_value = $subject_id );
      $this->echo_safely( "Saved subject '$subject_desc'" );
      echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function save_comments_on_work( $fieldname, $reltype, $comment_on = 'work' ) {

    $comment = trim( $this->$fieldname );
    if( strlen( $comment ) == 0 ) return;

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_comment_tablename()) . "'::regclass )";
    $comment_id = $this->db_select_one_value( $statement );

    $statement = 'insert into ' . $this->proj_comment_tablename() . ' (comment_id, comment) values ('
              . "$comment_id, '" . $this->escape( $comment ) . "')";
    $this->db_run_query( $statement );

    $this->rel_obj->insert_relationship( $left_table_name = $this->proj_comment_tablename(), 
                                         $left_id_value = $comment_id,
                                         $relationship_type = $reltype,
                                         $right_table_name = $this->proj_work_tablename(), 
                                         $right_id_value = $this->work_id );

    echo "Saved comment on $comment_on: ";
    $this->echo_safely( $comment );
    echo LINEBREAK;
  }
  #-----------------------------------------------------

  function save_comments_on_table( $tablename, $id_value, $comment = NULL, $comment_on = NULL ) {

    if( ! $tablename ) die( 'Invalid input.' );
    if( ! $id_value ) die( 'Invalid input.' );
    $comment = trim( $comment );
    if( strlen( $comment ) == 0 ) return;

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_comment_tablename()) . "'::regclass )";
    $comment_id = $this->db_select_one_value( $statement );
    $statement = 'insert into ' . $this->proj_comment_tablename() . ' (comment_id, comment) values ('
              . "$comment_id, '" . $this->escape( $comment ) . "')";
    $this->db_run_query( $statement );

    $this->rel_obj->insert_relationship( $left_table_name = $this->proj_comment_tablename(), 
                                         $left_id_value = $comment_id,
                                         $relationship_type = RELTYPE_COMMENT_REFERS_TO_ENTITY,
                                         $right_table_name = $tablename, 
                                         $right_id_value = $id_value );
    if( $comment_on ) {
      echo "Saved comment: ";
      $this->echo_safely( $comment );
      echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function save_language_of_work( $work_id, $openoffice_iwork_id ) {

    # We can now get languages of work from two sources: table with work ID and language code AND/OR a free-text string
    $statement = 'select * from ' . $this->proj_collect_language_of_work_tablename()
               . " where upload_id = $this->upload_id and iwork_id = $openoffice_iwork_id";
    $langs = $this->db_select_into_array( $statement );
    $num_langs = count( $langs );
    if( $num_langs > 0 ) {
      foreach( $langs as $lang ) {
        extract( $lang, EXTR_OVERWRITE );
        $statement = 'insert into ' . $this->proj_language_of_work_tablename()
                  . '(work_id, language_code) values (' . "'$work_id', '$language_code')";
        $this->db_run_query( $statement );

        $statement = "select language_name from iso_639_language_codes where code_639_3 = '"
                   . $this->escape( $language_code ) . "' ";
        $language_name = $this->db_select_one_value( $statement );

        echo "Saved language of work: '$language_name'" . LINEBREAK;
      }
    }

    $language_of_work = trim( $this->language_of_work ); # single string, with languages separated by semi-colons
    if( ! $language_of_work ) return;

    echo 'Saving further language(s) of work...' . LINEBREAK;
    $langs = explode( ';', $language_of_work );
    $found_all = TRUE;
    foreach( $langs as $lang ) {
      $lang = trim( $lang );
      $statement = "select code_639_3 from iso_639_language_codes where lower(language_name) = lower('"
                 . $this->escape( $lang ) . "')";
      $code = NULL;
      $code = $this->db_select_one_value( $statement );
      if( ! $code ) {
        echo "Unknown language name: '";
        $this->echo_safely( $lang );
        echo "'. Will save this language name in a comment." . LINEBREAK;
        $found_all = FALSE;
        continue;
      }
      else { # check we are not inserting a duplicate
        $existing_lang = NULL;
        $statement = 'select language_code from ' . $this->proj_language_of_work_tablename()
                   . " where work_id = '$work_id' and language_code = '$code'";
        $existing_lang = $this->db_select_one_value( $statement );
        if( $existing_lang ) continue;
      }

      $statement = 'insert into ' . $this->proj_language_of_work_tablename()
                . '(work_id, language_code) values (' . "'$work_id', '$code')";
      $this->db_run_query( $statement );
      echo "Saved language of work: '$lang'" . LINEBREAK;
    }

    if( ! $found_all ) {
      $this->work_id = $work_id;
      $this->language_of_work = 'Language(s) of work: ' . $this->language_of_work;
      $this->save_comments_on_work( 'language_of_work', RELTYPE_COMMENT_REFERS_TO_ENTITY );
    }
  }
  #-----------------------------------------------------
  function process_work_dates( $work_id ) {

    $statement = 'select * from ' . $this->proj_work_tablename() . " where work_id = '$work_id'";
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) != 1 ) die( 'Invalid input.' );
    $result = $results[0];
    extract( $result, EXTR_OVERWRITE );

    $is_julian = FALSE;
    if( $original_calendar == 'JM' || $original_calendar == 'JJ' )
      $is_julian = TRUE;

    $date_of_work_std = $this->date_entity->make_complete_date( $date_of_work_std_year,
                                                                $date_of_work_std_month,
                                                                $date_of_work_std_day,
                                                                $is_julian );

    $date_of_work2_std = $this->date_entity->make_complete_date( $date_of_work2_std_year,
                                                                $date_of_work2_std_month,
                                                                $date_of_work2_std_day,
                                                                $is_julian );

    $int_date = str_replace( '-', '', $date_of_work_std );  # e.g. '1605-08-03' becomes the integer 16050803
    if( $date_of_work2_std != '9999-12-31' ) { # not an unknown date
      $int_date2 = str_replace( '-', '', $date_of_work2_std );
      if( $int_date2 > $int_date ) {
        $int_date = $int_date2;
        $date_of_work_std = $date_of_work2_std;  # use the later of the two dates as your 'date for ordering'
      }
      $date_of_work_std_is_range = 1;  # make sure 'range' flag is set if end of date range has been entered
    }

    $date_of_work_std_gregorian = $date_of_work_std; 
    if( $is_julian && intval( $int_date ) < 99990000 )  { # not an unknown date

      $date_parts = explode( '-', $date_of_work_std );
      $year_val  = intval( $date_parts[0] );
      $month_val = intval( $date_parts[1] );
      $day_val   = intval( $date_parts[2] );

      $diffdays = 10;

      if( $year_val > 1700 ) 
        $diffdays = 11;
      else if( $year_val == 1700 && $month_val > 2 ) 
        $diffdays = 11;
      else if( $year_val==1700 && $month_val==2 && $day_val==29) 
        $diffdays = 11;

      $statement = "select '$date_of_work_std'::date + $diffdays";
      $date_of_work_std_gregorian = $this->db_select_one_value( $statement );
    }

    $statement = 'update ' . $this->proj_work_tablename() . " set date_of_work_std = '$date_of_work_std'::date,"
               . " date_of_work_std_gregorian = '$date_of_work_std_gregorian'::date, "
               . " date_of_work_std_is_range = $date_of_work_std_is_range"
               . " where work_id = '$work_id'";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function save_person_occupations( $person_id, $union_iperson_id ) {

    $statement = 'select rc.role_category_id, rc.role_category_desc from ' 
               . $this->proj_role_category_tablename() . ' rc, '
               . $this->proj_collect_occupation_of_person_tablename() . ' op, ' 
               . $this->proj_collect_person_tablename() . ' p '
               . " where op.upload_id = $this->upload_id"
               . " and op.upload_id = p.upload_id"
               . ' and op.iperson_id = p.iperson_id'
               . ' and op.occupation_id = rc.role_category_id'
               . " and p.person_id = '$person_id' and p.union_iperson_id = $union_iperson_id" ;
    $results = $this->db_select_into_array( $statement );
    if( count( $results ) == 0 ) return;

    foreach( $results as $result ) {
      extract( $result, EXTR_OVERWRITE );
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_person_tablename(), 
                                           $left_id_value = $person_id,
                                           $relationship_type = RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY,
                                           $right_table_name = $this->proj_role_category_tablename(), 
                                           $right_id_value = $role_category_id );
      #$this->echo_safely( "Saved role '$role_category_desc'" );
      #echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function proceed_with_rejecting_all_works() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;

    $this->upload_obj->upload_details( $header_only = TRUE, $suppress_status = TRUE );

    $statement = 'select iwork_id from ' . $this->proj_collect_work_tablename() 
               . " where upload_id = $upload_id and work_id is null"
               . ' and upload_status = ' . CONTRIB_STATUS_NEW;
    $works = $this->db_select_into_array( $statement );
    $num_works = count( $works );
    $curr_work = 0;
    echo "Rejecting $num_works works..." . LINEBREAK;
    flush();

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    foreach( $works as $work ) {
      extract( $work, EXTR_OVERWRITE );
      $curr_work++;
      echo "Rejecting work $curr_work of $num_works ..." . LINEBREAK;

      $statement = 'update ' . $this->proj_collect_work_tablename() 
                 . ' set upload_status = ' . CONTRIB_STATUS_REJECTED
                 . " where upload_id = $upload_id and iwork_id = $iwork_id";
      $this->db_run_query( $statement );
    }

    $statement = 'update ' . $this->proj_collect_upload_tablename() 
               . ' set upload_status = ' . CONTRIB_STATUS_COMPLETE . ', '
               . " works_rejected = works_rejected + $num_works"
               . " where upload_id = $upload_id";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    HTML::new_paragraph();
    echo 'Review of this contribution is now complete.' . LINEBREAK;
    HTML::new_paragraph();

    $this->contributor_contact_link( $upload_id );

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_list' );
    echo 'Back to list of contributions: ';
    HTML::submit_button( 'back_to_upload_list_button', 'Back' );
    HTML::form_end();
  }
  #-----------------------------------------------------
  function write_buttonrow_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' .buttonrow form { '     . NEWLINE;
    echo '  float: right;'         . NEWLINE; # override the default 'float left' behaviour of buttonrow class
    echo '  margin-right: 15px;'   . NEWLINE;
    echo '} '                      . NEWLINE;
    echo '.buttonrow br { '        . NEWLINE;
    echo '  clear: right;'         . NEWLINE;
    echo '} '                      . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------
  function write_reviewform_stylesheet() {

    echo '<style type="text/css">'                                      . NEWLINE;

    echo ' .reviewform form { '                                         . NEWLINE;
    echo '  margins: 15px;'                                             . NEWLINE;
    echo '  padding: 15px;'                                             . NEWLINE;
    echo '  border-style: solid;'                                       . NEWLINE;
    echo '  border-width: 1px;'                                         . NEWLINE;
    echo '  border-color: ' . HTML::get_contrast2_colour() . ' ;'       . NEWLINE;
    echo ' }'                                                           . NEWLINE;

    echo ' .reviewform label  {'                                        . NEWLINE;
    echo '   position: absolute; '                                      . NEWLINE;
    echo '   text-align:right; '                                        . NEWLINE;
    echo '   width: ' . DEFAULT_COL1_FIELD_LABEL_WIDTH_PX . 'px;'       . NEWLINE;
    echo ' }'                                                           . NEWLINE;

    echo ' .reviewform input, .reviewform select {'                     . NEWLINE;
    echo '   margin-left: ' . DEFAULT_COL1_FIELD_VALUE_POS_PX . 'px;'   . NEWLINE; 
    echo ' }'                                                           . NEWLINE;

    echo ' span.inputfieldaligned  {'                                   . NEWLINE;
    echo '   margin-left: ' . DEFAULT_COL1_FIELD_VALUE_POS_PX . 'px;'                    . NEWLINE; 
    echo ' }'                                                           . NEWLINE;

    echo '</style>'                                                     . NEWLINE;
  }
  #-----------------------------------------------------
  function count_outstanding_works( $upload_id ) {

    $statement = 'select count(*) from ' . $this->proj_collect_work_tablename() 
               . " where upload_id = $upload_id and work_id is null"
               . ' and upload_status = ' . CONTRIB_STATUS_NEW;
    $num_works = $this->db_select_one_value( $statement );
    return $num_works;
  }
  #-----------------------------------------------------

  function accept_one_work() {

    $upload_id = $this->read_post_parm( 'upload_id' );
    if( ! $upload_id ) die( 'Invalid input.' );
    $found = $this->upload_obj->set_upload( $upload_id );
    if( ! $found ) die( 'Invalid contribution ID.' );
    $this->upload_id = $upload_id;

    $iwork_id = $this->read_post_parm( 'iwork_id' );
    if( ! $iwork_id ) die( 'Invalid input.' );
    $found = $this->work_obj->set_work( $iwork_id );
    if( ! $found ) die( 'Invalid work ID.' );
    $this->iwork_id = $iwork_id;

    if( $this->parm_found_in_post( 'confirm_accept_work_button' )) {
      $this->proceed_with_accepting_one_work();
    }
    else {
      $this->confirm_accepting_one_work();
    }
  }
  #-----------------------------------------------------

  function confirm_accepting_one_work() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;
    if( ! $this->iwork_id ) die( 'Invalid input.' );
    $iwork_id = $this->iwork_id;

    $this->upload_obj->upload_details( $header_only = TRUE );

    $works = $this->get_outstanding_work( $upload_id, $iwork_id );
    $num_works = count( $works );
    if( $num_works != 1 ) {
      echo 'Review is complete for this work.' . LINEBREAK;
      return;
    }

    $this->write_reviewform_stylesheet();
    HTML::div_start( 'class="reviewform"' );

    HTML::form_start( $class_name = 'review', $method_name = 'accept_one_work' );
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );

    HTML::h4_start();
    echo 'Accept the following work and any new people, places or repositories referenced by it?';
    HTML::h4_end();

    $this->fields_for_all_works( $multiple_works = FALSE );

    HTML::submit_button( 'confirm_accept_work_button', 'Accept' );
    HTML::form_end();
    HTML::div_end();

    HTML::new_paragraph();

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_details' );
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::form_end();

    HTML::new_paragraph();
    HTML::italic_start();
    echo 'Details of work awaiting review:';
    HTML::italic_end();

    $this->upload_obj->set_upload( $upload_id, $iwork_id );
    $this->upload_obj->display_current_work();
  }
  #-----------------------------------------------------

  function proceed_with_accepting_one_work() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;
    if( ! $this->iwork_id ) die( 'Invalid input.' );
    $iwork_id = $this->iwork_id;

    $this->upload_obj->upload_details( $header_only = TRUE, $suppress_status = TRUE );

    $works = $this->get_outstanding_work( $upload_id, $iwork_id );
    $num_works = count( $works );
    if( $num_works != 1 ) {
      echo 'Review is complete for this work.' . LINEBREAK;
      return;
    }
    $work = $works[0];
    echo "Processing work..." . LINEBREAK;
    flush();

    $this->save_one_work( $upload_id, $work );

    $this->contributor_contact_link( $upload_id );

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_details' );
    echo 'Back to contribution: ';
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );
    HTML::submit_button( 'back_to_upload_details_button', 'Back' );
    echo SPACE;
    HTML::form_end();
  }
  #-----------------------------------------------------

  function reject_one_work() {

    $upload_id = $this->read_post_parm( 'upload_id' );
    if( ! $upload_id ) die( 'Invalid input.' );
    $found = $this->upload_obj->set_upload( $upload_id );
    if( ! $found ) die( 'Invalid contribution ID.' );
    $this->upload_id = $upload_id;

    $iwork_id = $this->read_post_parm( 'iwork_id' );
    if( ! $iwork_id ) die( 'Invalid input.' );
    $found = $this->work_obj->set_work( $iwork_id );
    if( ! $found ) die( 'Invalid work ID.' );
    $this->iwork_id = $iwork_id;

    if( $this->parm_found_in_post( 'confirm_reject_work_button' )) {
      $this->proceed_with_rejecting_one_work();
    }
    else {
      $this->confirm_rejecting_one_work();
    }
  }
  #-----------------------------------------------------

  function confirm_rejecting_one_work() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;
    if( ! $this->iwork_id ) die( 'Invalid input.' );
    $iwork_id = $this->iwork_id;

    $this->upload_obj->upload_details( $header_only = TRUE );

    $works = $this->get_outstanding_work( $upload_id, $iwork_id );
    $num_works = count( $works );
    if( $num_works != 1 ) {
      echo 'Review is complete for this work.' . LINEBREAK;
      return;
    }

    $this->write_buttonrow_stylesheet();
    HTML::div_start( 'class="buttonrow"' );

    HTML::h4_start();
    echo 'Reject the following work?';
    HTML::h4_end();
    HTML::italic_start();
    echo 'Please note that, if you click Reject, this will be a permanent rejection.'
          . ' There is currently no mechanism for reversing it.';
    HTML::italic_end();

    HTML::form_start( $class_name = 'review', $method_name = 'reject_one_work' );
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );
    HTML::submit_button( 'confirm_reject_work_button', 'Reject' );
    HTML::form_end();

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_details' );
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::form_end();

    echo LINEBREAK;
    HTML::div_end();

    $this->upload_obj->set_upload( $upload_id, $iwork_id );
    $this->upload_obj->display_current_work();
  }
  #-----------------------------------------------------

  function proceed_with_rejecting_one_work() {

    if( ! $this->upload_id ) die( 'Invalid input.' );
    $upload_id = $this->upload_id;
    if( ! $this->iwork_id ) die( 'Invalid input.' );
    $iwork_id = $this->iwork_id;

    $this->upload_obj->upload_details( $header_only = TRUE, $suppress_status = TRUE );

    $works = $this->get_outstanding_work( $upload_id, $iwork_id );
    $num_works = count( $works );
    if( $num_works != 1 ) {
      echo 'Review is complete for this work.' . LINEBREAK;
      return;
    }

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    $statement = 'update ' . $this->proj_collect_work_tablename() 
               . ' set upload_status = ' . CONTRIB_STATUS_REJECTED
               . " where upload_id = $upload_id and iwork_id = $iwork_id";
    $this->db_run_query( $statement );

    $new_status = $this->get_new_status_of_upload();

    $statement = 'update ' . $this->proj_collect_upload_tablename() . " set upload_status = $new_status, "
               . ' works_rejected = works_rejected + 1'
               . " where upload_id = $this->upload_id";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    HTML::new_paragraph();
    echo 'The selected work has been rejected.';
    HTML::new_paragraph();

    $statement = 'select status_desc from ' . $this->proj_collect_status_tablename()
               . " where status_id = $new_status";
    $new_status_desc = $this->db_select_one_value( $statement );

    HTML::div_start( 'class="highlight1 bold"' );
    echo 'The status of the contribution as a whole is now: ' . $new_status_desc;
    HTML::div_end();

    HTML::new_paragraph();
    $this->contributor_contact_link( $upload_id );

    HTML::form_start( $class_name = 'upload', $method_name = 'upload_details' );
    echo 'Back to contribution: ';
    HTML::hidden_field( 'upload_id', $upload_id );
    HTML::hidden_field( 'iwork_id', $iwork_id );
    HTML::submit_button( 'back_to_upload_details_button', 'Back' );
    HTML::form_end();
  }
  #-----------------------------------------------------

  function get_outstanding_work( $upload_id, $iwork_id ) {

    $statement = 'select * from ' . $this->proj_collect_work_tablename() 
               . " where upload_id = $upload_id and iwork_id = $iwork_id and work_id is null"
               . ' and upload_status = ' . CONTRIB_STATUS_NEW;
    $works = $this->db_select_into_array( $statement );
    return $works;
  }
  #-----------------------------------------------------

  function get_new_status_of_upload() {

    $statement = 'select count(*) from ' . $this->proj_collect_work_tablename() 
               . " where upload_id = $this->upload_id"
               . ' and upload_status = ' . CONTRIB_STATUS_NEW;
    $num_to_do = $this->db_select_one_value( $statement );
    if( $num_to_do > 0 ) 
      $new_status = CONTRIB_STATUS_PART_REVIEWED;
    else
      $new_status = CONTRIB_STATUS_COMPLETE;
    return $new_status;
  }
  #-----------------------------------------------------
  function contributor_contact_link( $upload_id ) {

    if( ! $upload_id ) die( 'Invalid upload details.' );

    $statement = 'select trim( uploader_email ) from ' . $this->proj_collect_upload_tablename()
               . " where upload_id = $upload_id";
    $uploader_email = $this->db_select_one_value( $statement );
    if( ! $uploader_email ) return;

    HTML::new_paragraph();
    HTML::h4_start();
    echo 'Contributor contact details: ';

    HTML::link( $href = "mailto:$uploader_email", 
                $displayed_text = $uploader_email, 
                $title = 'Contact the contributor', 
                $target = '_blank' );
    HTML::h4_end();

    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function save_work_resources( $work_id, $openoffice_iwork_id ) {

    $statement = 'select * from ' . $this->proj_collect_work_resource_tablename()
               . " where upload_id = $this->upload_id"
               . " and iwork_id = $openoffice_iwork_id"
               . ' order by resource_id';
    $resources = $this->db_select_into_array( $statement );

    $this->save_resources( $this->proj_work_tablename(), $work_id, $resources );
  }
  #-----------------------------------------------------

  function save_person_resources( $person_id, $openoffice_iperson_id ) {

    $statement = 'select * from ' . $this->proj_collect_person_resource_tablename()
               . " where upload_id = $this->upload_id"
               . " and iperson_id = $openoffice_iperson_id"
               . ' order by resource_id';
    $resources = $this->db_select_into_array( $statement );

    $this->save_resources( $this->proj_person_tablename(), $person_id, $resources );
  }
  #-----------------------------------------------------

  function save_location_resources( $location_id, $openoffice_location_id ) {

    $statement = 'select * from ' . $this->proj_collect_location_resource_tablename()
               . " where upload_id = $this->upload_id"
               . " and location_id = $openoffice_location_id"
               . ' order by resource_id';
    $resources = $this->db_select_into_array( $statement );

    $this->save_resources( $this->proj_location_tablename(), $location_id, $resources );
  }
  #-----------------------------------------------------

  function save_institution_resources( $institution_id, $openoffice_institution_id ) {

    $statement = 'select * from ' . $this->proj_collect_institution_resource_tablename()
               . " where upload_id = $this->upload_id"
               . " and institution_id = $openoffice_institution_id"
               . ' order by resource_id';
    $resources = $this->db_select_into_array( $statement );

    $this->save_resources( $this->proj_institution_tablename(), $institution_id, $resources );
  }
  #-----------------------------------------------------

  function save_resources( $tablename, $id_value, $resources ) {

    if( count( $resources ) == 0 ) return;

    foreach( $resources as $resource ) {
      extract( $resource, EXTR_OVERWRITE );

      if( trim( $resource_name ) == '' ) {  # this should never happen, but just in case...
        if( $resource_url > '' ) 
          $resource_name = $resource_url;
        else
          continue;  # Skip this one: no name and no URL, so it's not much of a resource!
      }

      $resource_id = NULL;  # need to get a new resource ID rather than using the non-unique one from the tool

      $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_resource_tablename()) . "'::regclass )";
      $resource_id = $this->db_select_one_value( $statement );

      $statement = 'insert into ' . $this->proj_resource_tablename() 
                 . ' ( resource_id, resource_name, resource_url, resource_details ) values ( '
                 . " $resource_id, " 
                 . "'" . $this->escape( $resource_name )    . "', "
                 . "'" . $this->escape( $resource_url )     . "', "
                 . "'" . $this->escape( $resource_details ) . "' )";
      $this->db_run_query( $statement );


      $this->rel_obj->insert_relationship( $left_table_name = $tablename, 
                                           $left_id_value = $id_value,
                                           $relationship_type = RELTYPE_ENTITY_HAS_RESOURCE,
                                           $right_table_name = $this->proj_resource_tablename(), 
                                           $right_id_value = $resource_id );
      echo LINEBREAK . 'Saved related resource: ';
      $this->echo_safely( $resource_name );
      echo LINEBREAK;
    }
  }
  #-----------------------------------------------------

  function fields_for_all_works( $multiple_works = FALSE ) {

    HTML::new_paragraph();


    $statement = 'select upload_description from ' . $this->proj_collect_upload_tablename()
               . " where upload_id = $this->upload_id";
    $upload_description = $this->db_select_one_value( $statement );
    
    HTML::input_field( 'accession_code',  $label = 'Source of data', $upload_description, 
                       FALSE, FLD_SIZE_ACCESSION_CODE );
    echo ' (max. ' . MAX_SIZE_ACCESSION_CODE . ' characters)';
		HTML::new_paragraph();

    $catg_obj = new Catalogue( $this->db_connection );

    $catg_obj->catg_code_dropdown( $field_name = 'original_catalogue', 
                                   $field_label = 'Original catalogue',
                                   $selected_code = NULL );

		HTML::new_paragraph();

    HTML::span_start( 'class="inputfieldaligned"' );
    HTML::italic_start();
    echo "You can optionally set the 'Source of data' and 'Original catalogue' fields";
    if( $multiple_works )
      echo ' for every work still outstanding.';
    else
      echo ' for this work.';
    HTML::span_end();
    echo LINEBREAK;

    HTML::span_start( 'class="inputfieldaligned"' );
    echo " Change the default value for 'Source of data', and/or choose a catalogue from the drop-down list,"
         . ' then click Accept.';
    HTML::italic_end();
    HTML::span_end();
    HTML::new_paragraph();

  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {

    switch( $parm_name ) {

      case 'upload_id':
      case 'iwork_id':
        return $this->is_integer( $this->parm_value );

      case 'accession_code':
        return Work::validate_parm( $parm_name );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
