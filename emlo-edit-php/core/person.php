<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

if( Application_Entity::get_system_prefix() == IMPACT_SYS_PREFIX )
  define( 'PERSON_PRIMARY_NAME_FLD_SIZE', 80 );
else
  define( 'PERSON_PRIMARY_NAME_FLD_SIZE', 50 );
define( 'PERSON_PRIMARY_NAME_MAX_FLD_SIZE', 200 );
define( 'PERSON_ALTERNATIVE_NAME_ROWS', 6 );
define( 'PERS_ALT_NAME_SEPARATOR_INTERNAL', '|' );
define( 'PERS_ALT_NAME_SEPARATOR_DISPLAYED', ';' );

define( 'PERSON_EDITORS_NOTES_ROWS', 3 );
define( 'PERSON_EDITORS_NOTES_COLS', 50 );

define( 'FURTHER_READING_ROWS', 5 );
define( 'FURTHER_READING_COLS', 70 );

class Person extends Project {

  #----------------------------------------------------------------------------------

  function Person( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $db_connection );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      $this->date_entity = new Islamic_Date_Entity( $this->db_connection );
    else
      $this->date_entity = new Date_Entity( $this->db_connection );

    $this->extra_anchors = array();
  }

  #----------------------------------------------------------------------------------

  function set_person( $iperson_id = NULL ) {

    $this->clear();
    if( ! $iperson_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_person_tablename()
               . " where iperson_id = '$iperson_id'";
    $this->db_select_into_properties( $statement );

    $this->skos_altlabel = str_replace( PERS_ALT_NAME_SEPARATOR_INTERNAL, PERS_ALT_NAME_SEPARATOR_DISPLAYED . ' ', 
                                        $this->skos_altlabel );
    $this->person_aliases = str_replace( PERS_ALT_NAME_SEPARATOR_INTERNAL, PERS_ALT_NAME_SEPARATOR_DISPLAYED . ' ', 
                                         $this->person_aliases );

    $this->this_on_left = $this->proj_get_righthand_side_of_rels( $left_table_name = $this->proj_person_tablename(), 
                                                                  $left_id_value = $this->person_id );

    $this->this_on_right = $this->proj_get_lefthand_side_of_rels( $right_table_name = $this->proj_person_tablename(), 
                                                                  $right_id_value = $this->person_id );

    return $this->iperson_id;
  }
  #----------------------------------------------------------------------------------

  function get_rels_for_general_notes() {

    $this->general_notes = $this->proj_get_relationships_of_type( RELTYPE_COMMENT_REFERS_TO_ENTITY );
    return $this->general_notes;
  }
  #----------------------------------------------------------------------------------

  function get_rels_for_resources() {

    $this->resources = $this->proj_get_relationships_of_type( RELTYPE_ENTITY_HAS_RESOURCE );
    return $this->resources;
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_orgs_of_which_member() {
    return 'orgs_of_which_member';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_members_of_org() {
    return 'members_of_org';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_person_locations() {
    return 'person_locations';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_place_of_birth() {
    return 'place_of_birth';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_place_of_death() {
    return 'place_of_death';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_person_to_person_equal() {
    return 'person_to_person_equal';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_parents() {
    return 'parent';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_children() {
    return 'child';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_employers() {
    return 'employer';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_employees() {
    return 'employee';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_teachers() {
    return 'teacher';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_students() {
    return 'student';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_patrons() {
    return 'patron_of_person';
  }
  #----------------------------------------------------------------------------------

  function fieldset_name_for_protegees() {
    return 'protegee_of_person';
  }
  #----------------------------------------------------------------------------------

  function set_default_simplified_search() {

    if( ! $this->parm_found_in_post( 'simplified_search' ) 
    &&  ! $this->parm_found_in_get(  'simplified_search' )) {
        $this->write_post_parm( 'simplified_search', 'Y' );  # default to simplified search
    }
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    $this->search_method  = 'db_search';
    $this->results_method = 'db_search_results';
    $this->keycol         = 'iperson_id';
    $this->from_table     = $this->proj_person_viewname();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'names_and_titles' );

    #$this->edit_method= 'edit_person';  -- We can't use the normal method for editing via POST form
    #$this->edit_tab  = '_blank';        -- because the 'Edit' form would be nested within the 'Merge' form
  }
  #-----------------------------------------------------

  function db_search( $table_or_view = NULL, $class_name = NULL, $method_name = NULL ) {

    $this->db_remember_presentation_style(); # If a saved presentation style is found, it is written to POST.
                                             # (On very first login ever, defaults will be used.)

    $this->db_set_search_result_parms();

    $this->entering_selection_criteria = TRUE;
    $this->reading_selection_criteria = FALSE;

    $this->set_default_simplified_search();

    parent::db_search( $this->from_table, $this->app_get_class( $this ), $this->results_method );
  }
  #-----------------------------------------------------

  function db_search_results() {

    $this->entering_selection_criteria = FALSE;
    $this->reading_selection_criteria = TRUE;

    parent::db_search_results();
  }
  #-----------------------------------------------------

  function db_list_columns(  $table_or_view = NULL ) {  # overrides parent class

    $rawcols = parent::db_list_columns( $table_or_view );
    if( ! is_array( $rawcols )) return NULL;

    $columns = array();
    foreach( $rawcols as $row ) {
      $column_label = NULL;
      $search_help_text = NULL;
      $search_help_class = NULL;
      $skip_it = FALSE;
      extract( $row, EXTR_OVERWRITE );

      #------------------------
      # Include or omit columns
      #------------------------
      if( $column_name == 'skos_hiddenlabel' ) $skip_it = TRUE;
      if( $column_name == 'creation_timestamp' ) $skip_it = TRUE;
      if( $column_name == 'creation_user' ) $skip_it = TRUE;

      # Although we seem to be skipping the following columns, in fact we will use the values in the search results.
      if( $column_name == 'date_of_birth_from' ) $skip_it = TRUE;
      if( $column_name == 'date_of_birth_to' ) $skip_it = TRUE;
      if( $column_name == 'date_of_death_from' ) $skip_it = TRUE;
      if( $column_name == 'date_of_death_to' ) $skip_it = TRUE;
      if( $column_name == 'flourished_from' ) $skip_it = TRUE;
      if( $column_name == 'flourished_to' ) $skip_it = TRUE;

      if( $column_name == 'date_of_birth_ce_from' ) $skip_it = TRUE;
      if( $column_name == 'date_of_birth_ce_to' ) $skip_it = TRUE;
      if( $column_name == 'date_of_death_ce_from' ) $skip_it = TRUE;
      if( $column_name == 'date_of_death_ce_to' ) $skip_it = TRUE;
      if( $column_name == 'flourished_ce_from' ) $skip_it = TRUE;
      if( $column_name == 'flourished_ce_to' ) $skip_it = TRUE;

      if( $column_name == 'date_of_birth_estimated_range' ) $skip_it = TRUE;
      if( $column_name == 'date_of_death_estimated_range' ) $skip_it = TRUE;
      if( $column_name == 'flourished_estimated_range' ) $skip_it = TRUE;

      if( $column_name == 'person_id' ) {

        $enable_merge = FALSE;
        if( ! $this->csv_output && ! $this->printable_output && ! $this->getting_order_by_cols ) {
          # Check that user has edit role. But don't offer merge in a popup window, it's too messy. 
          if( $this->proj_edit_mode_enabled() && ! $this->menu_called_as_popup )
            $enable_merge = TRUE;
        }

        if( $enable_merge )
          $row[ 'searchable' ] = FALSE;  # we will use this column as a place to put the 'Merge' checkbox
        elseif( $this->menu_method_name != 'save_person' )
          $skip_it = TRUE;
      }

      elseif( $column_name == 'other_details_summary' ) {  # this version of the column is displayed but not searchable
        $row[ 'searchable' ] = FALSE;
      }


      # Some columns are queryable but not displayed
      if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {

          case 'change_user':
          case 'other_details_summary_searchable':
            $skip_it = TRUE;
            break;

          case 'date_of_death_in_orig_calendar': # bundle all the 'original calendar' dates together into one column
          case 'flourished_in_orig_calendar':
            if( $table_or_view != $this->proj_person_tablename()) $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      if( $skip_it ) continue;

      #------------------
      # Set column labels
      #------------------
      $column_label = $this->db_get_default_column_label( $column_name ); # could be overwritten later

      switch( $column_name ) {

        case 'iperson_id':
          $search_help_text = 'The unique ID for the record within this database.';
          break;

        case 'person_id':
          $column_label = 'Merge';
          break;

        case 'foaf_name':
          $search_help_text = "Normally in 'surname, forename' format";
          break;

        case 'names_and_titles':
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
            $search_help_text = 'Primary or alternative names.'
                              . ' Use % to search for more than one name, e.g. <strong>Uljaytu%Rashid</strong>;'
                              . ' switch order of terms if no results are returned.';
          else {
            $search_help_text = "Primary name normally in 'surname, forename' format, followed by alternative names"
                              . ' and titles or roles/professions.' 
                              . ' Roles and professions may have been entered as free text and/or as a list'
                              . ' of standard categories (see below):' . LINEBREAK;
            $search_help_class = 'role_category';
          }
          break;

        case 'professions_or_titles':
          $search_help_text = 'E.g. <strong>qassab, khwaja</strong>.';
          break;

        case 'skos_altlabel':
          $search_help_text = "Normally in 'surname, forename' format";
          break;

        case 'date_of_birth':
        case 'date_of_death':

          if( $column_name == 'date_of_birth' ) 
            $column_label = 'Born';
          else 
            $column_label = 'Died';

          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = 'Enter in yyy or yyyy format.';
          else
            $search_help_text = 'Can be entered in YYYY format.';

          if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
            $search_help_text .= ' (In the case of organisations, this field may hold the date of ';
            if( $column_name == 'date_of_birth' ) 
              $search_help_text .= 'formation.)';
            else 
              $search_help_text .= 'dissolution.)';
          }

          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $column_label .= ' (Hijri)';
          break;

        case 'date_of_birth_ce':
        case 'date_of_death_ce':
        case 'flourished_ce':

          if( $column_name == 'date_of_birth_ce' ) 
            $column_label = 'Born (CE)';
          elseif( $column_name == 'date_of_death_ce' ) 
            $column_label = 'Died (CE)';
          elseif( ! $this->entering_selection_criteria )
            $column_label = 'Fl. (CE)'; 
          $search_help_text = 'Enter in yyyy format.';
          break;

        case 'flourished':
          if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria 
          &&  $this->is_search_results_method( $this->menu_method_name ))
            $column_label = 'Fl.';
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $column_label .= ' (Hijri)';
           
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = 'Enter in yyy or yyyy format.';
          else
            $search_help_text = 'Can be entered in YYYY format.';
          break;

        case 'sent':
          $search_help_text = 'Number of letters from this author/sender.';

          if( ! $this->menu_called_as_popup ) # advanced search is not enabled in popup windows
            $search_help_text .= " You can search on these 'number' fields using 'Advanced Search',"
                              .  " e.g. you could enter something like 'Sent greater than 100'"
                              .  ' to identify the more prolific authors.';
          break;

        case 'recd':
          $column_label = "Rec'd";
          $search_help_text = 'Number of letters to this addressee.';
          break;

        case 'mentioned':
          $search_help_text = 'Number of letters in which this person/organisation was mentioned.';
          break;

        case 'all_works':
          $column_label = "Sent or Rec'd";
          $search_help_text = 'Total of letters to and from this person/organisation.';
          break;

        case 'author_of':
          $search_help_text = 'Exact numbers only';
          if( ! $this->menu_called_as_popup ) # advanced search is not enabled in popup windows
            $search_help_text .= '; to find authors of more than x number of works, click'
                              . " 'Advanced Search' above, choose 'greater than' from the dropdown menu and enter x.";
          break;

        case 'nisba_and_nationality':
          $search_help_text = 'Options for <em>nisbas</em> are given below:';
          $search_help_class = 'nisba';
          break;

        case 'affiliations':
          $search_help_text = 'Includes confessional subgroup, <em>madhhab</em>, theological school, Sufi grouping,'
                            . ' movement, or other grouping or network. An overview of affiliations appears first, '
                            . ' followed by more specific details if known. Use % to search for more than one term,'
                            . " e.g. <strong>Shafi'i%Ikhwan</strong>; switch order of terms if no results are returned.";
          $search_help_class = 'org_subtype';
          break;

        case 'gender':
          if( $this->is_search_results_method( $this->menu_method_name )
              && ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) # make it more compact
            $column_label = 'Sex';
          else
            $column_label = $this->db_get_default_column_label( $column_name );
          $search_help_text = 'Could be M, F, or blank if unknown or not applicable (e.g. if referring to a group). ';
          if( ! $this->menu_called_as_popup )
            $search_help_text .= " You can use 'Advanced Search' to search for blank genders.";
          break;

        case 'is_organisation':
          if( $this->is_search_results_method( $this->menu_method_name )
              && ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) # make it more compact
            $column_label = 'Org?';
          else
            $column_label = 'Person or group?';
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = "Blank for individual people; 'Org' if the record refers to an organisation or group"
                              . ' such as a confessional subgroup or theological school.';
          else
            $search_help_text = "Blank for individual people; 'Org' if correspondent is an organisation or group.";
          break;

        case 'editors_notes':
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = '';
          else
            $search_help_text = 'Notes for internal use, intended to hold temporary queries etc.';
          break;

        case 'further_reading':
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = 'Further bibliographical information.';
          else
            $search_help_text = 'Bibliographical information.';
          break;

        case 'other_details_summary':
        case 'other_details_summary_searchable':
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
            $search_help_text = 'Summary of any other information about the person or group, including personal'
                              . " relationships, known geographical locations and related resources.";
          else
            $search_help_text = 'Summary of any other information about the person or group, including membership of'
                              . " organisations, known geographical locations, researchers' notes and related resources.";
          break;

        case 'works_written':
          $search_help_text = 'Use % to search for more than one primary source type, e.g.'
                            . " <strong>Commentary%Versification</strong>; switch order of terms if no results"
                            . ' are returned.';
          break;

        case 'associated_works':
          $search_help_text = 'Primary sources endowed by a person, dedicated to a person, etc.';
          break;

        case 'associated_manifestations':
          $search_help_text = 'Manifestations copied by a person, studied by a person, etc.';
          break;

        case 'org_type':
          $search_help_class = 'org_type';
          break;

        case 'date_of_birth_in_orig_calendar':
          if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
            $column_label = 'Dates in original calendar'; # bundle the other 'original calendar' dates in with this one
          }
          else
            $column_label = 'Born (in original calendar)';
          $search_help_text = 'Date of birth in, e.g., Islamic Solar or Alexandrian format,'
                            . ' or expressed in words rather than numbers.';
          break;

        case 'date_of_death_in_orig_calendar':
          $column_label = 'Died (in original calendar)';
          $search_help_text = 'Date of death in, e.g., Islamic Solar or Alexandrian format,'
                            . ' or expressed in words rather than numbers.';
          break;

        case 'flourished_in_orig_calendar':
          $column_label = 'Flourished (in original calendar)';
          $search_help_text = 'Dates when flourished in, e.g., Islamic Solar or Alexandrian format,'
                            . ' or expressed in words rather than numbers.';
          break;

        default:
          break;
      }
      $row[ 'column_label' ] = $column_label;

      #----------------
      # Set search help
      #----------------
      $row[ 'search_help_text' ] = $search_help_text;
      $row[ 'search_help_class' ] = $search_help_class;


      #------------------------------------------------------------
      # Transfer reformatted data into the array that will be used.
      #------------------------------------------------------------
      # Cultures of Knowledge likes person ID at end of list, IMPAcT likes it at start.
      if( $this->entering_selection_criteria && $column_name == 'iperson_id' 
          && $this->get_system_prefix() != IMPACT_SYS_PREFIX )
        $id_row = $row;
      else
        $columns[] = $row;
    }

    # For Cultures of Knowledge, put ID column at end of list
    if( $this->entering_selection_criteria && $this->get_system_prefix() != IMPACT_SYS_PREFIX )
      $columns[] = $id_row;

    return $columns;
  }
  #-----------------------------------------------------

  function db_get_possible_order_by_cols( $columns ) {

    $this->getting_order_by_cols = TRUE;
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of included and omitted columns
    $this->getting_order_by_cols = FALSE;

    return parent::db_get_possible_order_by_cols( $columns );
  }
  #-----------------------------------------------------

  function db_browse_across_page( $details, $columns ) {
    $this->db_browse_with_merge( $details, $columns, $browse_function = 'db_browse_across_page' );
  }
  #-----------------------------------------------------

  function db_browse_down_page( $details, $columns ) {
    $this->db_browse_with_merge( $details, $columns, $browse_function = 'db_browse_down_page' );
  }
  #-----------------------------------------------------

  function db_browse_with_merge( $details, $columns, $browse_function = 'db_browse_across_page' ) {

    $enable_merge = FALSE;
    if( ! $this->csv_output && ! $this->printable_output ) {
      # Check that user has edit role. But don't offer merge in a popup window, it's too messy. 
      if( $this->proj_edit_mode_enabled() && ! $this->menu_called_as_popup )
        $enable_merge = TRUE;
    }

    if( $enable_merge ) {

      $this->merge_form = HTML::form_start( $this->app_get_class( $this ), 'start_merge' );

      $select_all_script_name = $this->write_script_to_set_merge_checkboxes( $details, $tick = TRUE );
      $clear_all_script_name = $this->write_script_to_set_merge_checkboxes( $details, $tick = FALSE );

      HTML::new_paragraph();
      HTML::italic_start();
      echo 'To start the process of merging records, tick the checkbox beside the relevant names '
           . ' and click the Merge button:';
      HTML::italic_end();
      HTML::new_paragraph();

      HTML::submit_button( 'merge_button', 'Merge' );
      HTML::button( 'select_all_button', 'Select all', 1, 'onclick="' . $select_all_script_name . '()"' );
      HTML::button( 'clear_all_button', 'Clear all', 1, 'onclick="' . $clear_all_script_name . '()"' );
      HTML::new_paragraph();

      $this->db_write_pagination_fields( $this->db_page_required );  # also writes calling form/field if present
    }

    parent::$browse_function( $details, $columns );

    if( $enable_merge ) {
      HTML::new_paragraph();
      HTML::submit_button( 'merge_button2', 'Merge' );
      HTML::button( 'select_all_button2', 'Select all', 1, 'onclick="' . $select_all_script_name . '()"' );
      HTML::button( 'clear_all_button2', 'Clear all', 1, 'onclick="' . $clear_all_script_name . '()"' );
      HTML::new_paragraph();
      HTML::form_end();
    }
  }
  #-----------------------------------------------------

  function write_script_to_set_merge_checkboxes( $search_results, $tick = TRUE ) {

    $keycol = $this->keycol;  # should have been set by search result parms

    $script_name = 'checkAllMergeBoxes';
    $checked = 'true';

    if( ! $tick ) {
      $script_name = 'clearAllMergeBoxes';
      $checked = 'false';
    }

    $script = ' function ' . $script_name . '() {' . NEWLINE;

    foreach( $search_results as $row ) {
      $id = $row[ "$keycol" ];
      $script .= '  document.' . $this->merge_form . '.merge' . $id . ".checked=$checked;" . NEWLINE;
    }

    $script .= ' }' . NEWLINE;
    HTML::write_javascript_function( $script );

    return $script_name;
  }
  #-----------------------------------------------------

  function start_merge() {

    $to_merge = $this->read_post_parm( 'merge' );

    if( count( $to_merge ) < 2 ) {
      HTML::new_paragraph();
      HTML::div_start( 'class="warning"' );
      echo 'You need to check two or more checkboxes. Cancelling merge...';
      HTML::div_end();
      HTML::new_paragraph();

      $this->db_set_search_result_parms();
      $results_method = $this->results_method;
      $this->$results_method();
      return;
    }

    HTML::form_start( $this->app_get_class( $this ), 'confirm_merge' );

    $this->app_popup_read_calling_form_and_field();  # if called via a popup window, find out where to pass values to
    $this->app_popup_write_calling_form_and_field();

    $this->retain_options_during_merge(); # retain 'Advanced Search', rows per page, etc.

    $i = 0;
    foreach( $to_merge as $id_to_merge ) {
      $i++;
      HTML::hidden_field( 'merge[]', $id_to_merge, $i );
    }


    HTML::new_paragraph();
    HTML::submit_button( 'ok_button', 'OK' );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::h3_start();
    echo 'Select the master record';
    HTML::h3_end();

    HTML::italic_start();
    echo "Please select the record into which all the others should be merged, then click 'Save': ";
    HTML::italic_end();

    HTML::new_paragraph();
    HTML::table_start( 'class="datatab spacepadded"' );
    HTML::tablerow_start();
    HTML::column_header( 'Name' );
    HTML::column_header( 'Works sent or received' );
    HTML::tablerow_end();

    $i = 0;
    foreach( $to_merge as $id_to_merge ) {
      HTML::tablerow_start();
      $i++;

      #----

      HTML::tabledata_start();

      $person_name = $this->proj_get_description_from_id( $id_to_merge );

      HTML::radio_button( $fieldname = 'selected_merge_id', 
                          $person_name, 
                          $value_when_checked = $id_to_merge, 
                          $current_value = '', 
                          $tabindex=1, 
                          $button_instance=$i );

      HTML::tabledata_end();

      #----

      HTML::tabledata_start();

      $sent_rels = $this->get_righthand_values( $id_to_merge, RELTYPE_PERSON_CREATOR_OF_WORK, 
                                                $this->proj_work_tablename());
      $sent = count($sent_rels);

      #----

      $recd_rels = $this->get_lefthand_values( $id_to_merge, RELTYPE_WORK_ADDRESSED_TO_PERSON, 
                                               $this->proj_work_tablename()) ;
      $received = count($recd_rels);

      #----

      $mentioned_rels = $this->get_lefthand_values( $id_to_merge, RELTYPE_WORK_MENTIONS_PERSON, 
                                                    $this->proj_work_tablename()) ;
      $mentioned = count($mentioned_rels);

      #----

      echo 'Author/sender of ' . $sent . ' works, addressee of ' . $received . ', mentioned in ' . $mentioned . '.';
      if( $sent + $received + $mentioned > 0 ) HTML::new_paragraph();

      $this->show_desc_of_related_works( $sent_rels, 'Author/sender of:' );
      $this->show_desc_of_related_works( $recd_rels, 'Addressee of:' );
      $this->show_desc_of_related_works( $mentioned_rels, 'Mentioned in:' );
      HTML::tabledata_end();

      #----

      HTML::tablerow_end();
    }
    HTML::table_end();

    HTML::new_paragraph();
    HTML::submit_button( 'ok_button', 'OK' );
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::new_paragraph();

    HTML::form_end();
  }
  #-----------------------------------------------------

  function show_desc_of_related_works( $rels, $heading ) {

    if( is_array( $rels ) && count( $rels ) > 0 ) {

      HTML::italic_start();
      echo $heading;
      HTML::italic_end();
      HTML::new_paragraph();

      foreach( $rels as $row ) {
        $work_id = $row[ 'other_id_value' ];
        if( ! $work_obj ) $work_obj = new Work( $this->db_connection );
        echo $work_obj->get_work_desc( $work_id );
        HTML::new_paragraph();
      }
    }
  }
  #-----------------------------------------------------

  function confirm_merge() {

    if( $this->cancel_merge_at_user_request()) return;

    $this->app_popup_read_calling_form_and_field();  # if called via a popup window, find out where to pass values to
    
    $to_merge = $this->read_post_parm( 'merge' );
    $selected_merge_id = $this->read_post_parm( 'selected_merge_id' );

    if( ! $selected_merge_id ) {
      HTML::new_paragraph();
      HTML::div_start( 'class="warning"' );
      echo 'You did not select the master record. Cannot proceed with merge.';
      HTML::div_end();
      HTML::new_paragraph();

      $this->start_merge();
      return;
    }

    # Display values to be merged, and wait for user to click OK

    HTML::form_start( $this->app_get_class( $this ), 'save_merge' );

    $this->app_popup_read_calling_form_and_field();  # if called via a popup window, find out where to pass values to
    $this->app_popup_write_calling_form_and_field();

    $this->retain_options_during_merge(); # retain 'Advanced Search', rows per page, etc.

    HTML::hidden_field( 'selected_merge_id', $selected_merge_id );

    HTML::h3_start();
    echo $this->get_person_desc_from_id( $selected_merge_id );
    HTML::h3_end();

    HTML::italic_start();
    echo 'Will replace the following: ';
    HTML::italic_end();
    HTML::new_paragraph();

    $i = 0;
    foreach( $to_merge as $id_to_merge ) {
      if( $id_to_merge == $selected_merge_id ) continue;

      $i++;
      HTML::hidden_field( 'merge[]', $id_to_merge, $i );
      echo " $i. " . $this->get_person_desc_from_id( $id_to_merge );
      HTML::new_paragraph();
    }

    $sent_or_received_works = $this->read_post_parm( 'sent_or_received_works' );
    if( is_array( $sent_or_received_works )) {
      foreach( $sent_or_received_works as $work ) {
        HTML::hidden_field( 'sent_or_received_works[]', $work );
      }
    }

    HTML::submit_button( 'save_button', 'Save' );
    HTML::submit_button( 'cancel_button', 'Cancel' );

    HTML::form_end();
  }
  #-----------------------------------------------------
    
  function cancel_merge_at_user_request() {

    if( $this->parm_found_in_post( 'cancel_button' )) {
      HTML::new_paragraph();
      HTML::div_start( 'class="warning"' );
      echo 'Merge cancelled at user request.';
      HTML::div_end();
      HTML::new_paragraph();

      $this->db_set_search_result_parms();
      $search_method = $this->search_method;
      $this->$search_method();
      return TRUE;
    }
    else
      return FALSE; # user did not cancel merge
  }
  #-----------------------------------------------------

  function save_merge() {

    if( $this->cancel_merge_at_user_request()) return;

    $this->app_popup_read_calling_form_and_field();  # if called via a popup window, find out where to pass values to
    
    $to_merge = $this->read_post_parm( 'merge' );
    $selected_merge_id = $this->read_post_parm( 'selected_merge_id' );

    if( ! $selected_merge_id ) {
      HTML::new_paragraph();
      HTML::div_start( 'class="warning"' );
      echo 'You did not select the master record. Cannot proceed with merge.';
      HTML::div_end();
      HTML::new_paragraph();

      $this->start_merge();
      return;
    }

    #-------------------------------------------------------------------------------------
    # Go through relationships table, replacing IDs from 'to merge' list with selected ID.
    # Update description of works and 'queryable works' where sender/recipient has been
    # replaced. (If 'people mentioned' is added to queryable summary, update those too.)
    # Finally, delete the person ID from the person table.
    #-------------------------------------------------------------------------------------
    $merged_desc = $this->perform_merge( $selected_merge_id, $to_merge );

    echo 'The following ' . count( $merged_desc ) . ' records have been replaced:';
    echo LINEBREAK;
    $i = 0;
    foreach( $merged_desc as $desc ) {
      $i++;
      echo " $i. " . $desc . LINEBREAK;
    }

    HTML::new_paragraph();
    echo 'Replaced by: ';
    echo $this->get_person_desc_from_id( $selected_merge_id );
    HTML::new_paragraph();

    if( $this->calling_form && $this->calling_field ) {
      $this->app_popup_pass_value_back();
      HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );
    }
    else {
      HTML::horizontal_rule();
      HTML::h3_start();
      echo 'Search again:';
      HTML::h3_end();

      $this->db_set_search_result_parms();
      $search_method = $this->search_method;
      $this->$search_method();

      $script = "document.$this->form_name.names_and_titles.focus()";
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------
  function perform_merge( $selected_merge_id, $to_merge ) {

    $table_name = $this->proj_person_tablename();

    $merged_desc = array();
    $deletions = count( $to_merge );
    $i = 0;

    foreach( $to_merge as $id_to_merge ) {
      #--------------------------------------------------
      # No need to update the master record at this point
      #--------------------------------------------------
      if( $id_to_merge == $selected_merge_id ) continue;

      $i++;
      echo "Deletion $i of $deletions..." . LINEBREAK;

      #------------------------------------------------------------------------------
      # Remember original name and works sent/received by person to be merged/deleted
      #------------------------------------------------------------------------------
      $merged_desc[] = $this->get_person_desc_from_id( $id_to_merge );

      #-------------------------------------------------------------------------------------
      # Change all references to original person in 'relationships' to the new master record
      #-------------------------------------------------------------------------------------
      $this->rel_obj->change_id_value( $this->proj_person_tablename(), $id_to_merge, $selected_merge_id,
                                       $display_status_msg = TRUE );

      #---------------------------
      # Delete the original person
      #---------------------------
      $this->perform_delete_person( $id_to_merge, $display_status_msg = TRUE );
    }

    return $merged_desc;
  }
  #-----------------------------------------------------

  function get_righthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->person_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_person_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'left' );
  }
  #----------------------------------------------------------------------------------

  function get_lefthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->person_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_person_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'right' );
  }
  #----------------------------------------------------------------------------------

  function get_both_sides( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->person_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_person_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'both' );
  }
  #----------------------------------------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    switch( $column_name ) {

      case 'foaf_name':
      case 'skos_altlabel':
      case 'person_aliases':
      case 'names_and_titles':
        return str_replace( PERS_ALT_NAME_SEPARATOR_INTERNAL, 
                            PERS_ALT_NAME_SEPARATOR_DISPLAYED . ' ', 
                            $column_value );

      case 'person_id':
        if( ! $this->printable_output && ! $this->csv_output ) {
          HTML::checkbox( $fieldname = 'merge[]',
                          $label = NULL,
                          $is_checked = FALSE,
                          $value_when_checked = $column_value,
                          $in_table = FALSE,
                          $tabindex = 1,
                          $input_instance = $this->current_row_of_data[ 'iperson_id' ] );
        }
        return '';

      case 'sent':
      case 'recd':
      case 'mentioned':
      case 'all_works':
      case 'author_of':
        if( ! $this->printable_output && ! $this->csv_output ) {
          if( intval( $column_value ) > 0 ) {

            $person_id = $this->current_row_of_data[ 'person_id' ];
            $iperson_id = $this->current_row_of_data[ 'iperson_id' ];

            $title = $column_name;
            if( $title == 'recd' )
              $title = 'addressed to';
            elseif( $title == 'sent' )
              $title = 'from';
            elseif( $title == 'mentioned' )
              $title = 'mentioning';
            elseif( $title == 'author_of' )
              $title = 'written by';
            else
              $title = 'to or from';
            if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
              $title = 'Display primary sources ' . $title . ' this person';
            else
              $title = 'Display letters ' . $title . ' this person';

            $method = 'person_works_search_results';
            if( $column_name == 'mentioned' ) 
              $method = 'agent_mentioned_in_works_search_results';

            $view_type = $column_name;
            if( $view_type == 'author_of' )
              $view_type = 'author';

            $href = $_SERVER[ 'PHP_SELF' ]
                  . '?class_name=' . PROJ_COLLECTION_WORK_CLASS
                  . "&method_name=$method"
                  . '&person_id=' . rawurlencode( $person_id )
                  . '&person_works_view_type=' . $view_type;

            HTML::link_start( $href, $title, '_blank' );
            echo $column_value;
            HTML::link_end();
          }
          return '';
        }
        return $column_value;

      case 'date_of_birth':
      case 'date_of_birth_ce':
      case 'date_of_death':
      case 'date_of_death_ce':
      case 'flourished':
      case 'flourished_ce':
        if( strlen( $column_value ) == strlen( 'yyyy-mm-dd' )) {
          $column_value = substr( $column_value, 0, strlen( 'yyyy' ));
          if( $column_value == '9999' ) $column_value = '';
          while( $this->string_starts_with( $column_value , '0' )) {  # strip off any leading zeroes
            $column_value = substr( $column_value, 1 );
          }

          # See if a date range was entered
          $range_fieldname = $column_name;
          if( $this->string_ends_with( $range_fieldname, '_ce' )) 
            $range_fieldname = substr( $range_fieldname, 0, -3 );
          $range_fieldname .= '_estimated_range'; # this is a column name from the person VIEW not table
          if( $this->current_row_of_data[ "$range_fieldname" ] == 1 ) {
            $from_fieldname = $column_name . '_from';
            $to_fieldname   = $column_name . '_to';
            $year_from = substr( $this->current_row_of_data[ "$from_fieldname" ], 0, strlen( 'yyyy' )) ;
            $year_to   = substr( $this->current_row_of_data[ "$to_fieldname" ],   0, strlen( 'yyyy' )) ;
            if( $year_from && $year_to ) {
              if( $year_from != $year_to ) {
                $column_value = "$year_from to $year_to";
              }
            }
            elseif( $year_from )
              $column_value .= ' or after';
            else
              $column_value .= ' or before';
          }
        }
        return $column_value;

      case 'date_of_birth_in_orig_calendar':
        if( $this->current_row_of_data ) { 

          $date_of_birth_in_orig_calendar = $this->current_row_of_data[ 'date_of_birth_in_orig_calendar' ];
          $date_of_death_in_orig_calendar = $this->current_row_of_data[ 'date_of_death_in_orig_calendar' ];
          $flourished_in_orig_calendar = $this->current_row_of_data[ 'flourished_in_orig_calendar' ];

          $column_value = '';
          if( $date_of_birth_in_orig_calendar ) 
            $column_value  = 'Born: '       . $date_of_birth_in_orig_calendar . NEWLINE;
          if( $date_of_death_in_orig_calendar ) 
            $column_value .= 'Died: '       . $date_of_death_in_orig_calendar . NEWLINE;
          if( $flourished_in_orig_calendar )    
            $column_value .= 'Fl. ' . $flourished_in_orig_calendar;
        }
        return $column_value;

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function proj_get_description_from_id( $entity_id, $using_integer_id = FALSE ) {  # method from CofK Entity

    return $this->get_person_desc_from_id( $entity_id, $using_integer_id );
  }
  #----------------------------------------------------------------------------------

  function get_person_desc_from_id( $person_id, $using_integer_id = FALSE ) {

    if( ! $person_id ) return NULL;

    if( $using_integer_id ) {
      $where_clause = "iperson_id = $person_id";
    }
    else {
      $where_clause = "person_id = '$person_id'";
    }

    $funcname = $this->proj_database_function_name( 'decode_person', $include_collection_code = TRUE );

    $statement = "select $funcname ( person_id ) from " . $this->proj_person_tablename()
               . " where $where_clause";
    $result = $this->db_select_one_value( $statement );
    return $result;
  }
  #-----------------------------------------------------

  function get_person_desc_from_current_row_of_data() {  # DB search results will set $this->current_row_of_data

    if( $this->current_row_of_data ) { 
      $person_id = $this->current_row_of_data[ 'person_id' ];
      $person_desc = $this->get_person_desc_from_id( $person_id, $using_integer_id = FALSE );
    }
    else
      $person_desc = NULL;

    return $person_desc;
  }
  #-----------------------------------------------------

  function get_integer_id_from_text_id( $text_id ) {

    if( $this->person_id == $text_id ) {  # already have the data, no need to re-select
      if( $this->iperson_id ) { 
        return $this->iperson_id;
      }
    }

    $statement = 'select iperson_id from ' . $this->proj_person_tablename()
               . " where person_id = '$text_id'";
    $iid = $this->db_select_one_value( $statement );
    return $iid;
  }
  #-----------------------------------------------------

  function get_text_id_from_integer_id( $integer_id ) {

    if( $this->iperson_id == $integer_id ) {  # already have the data, no need to re-select
      if( $this->person_id ) { 
        return $this->person_id;
      }
    }

    $statement = 'select person_id from ' . $this->proj_person_tablename()
               . " where iperson_id = $integer_id";
    $text_id = $this->db_select_one_value( $statement );
    return $text_id;
  }
  #-----------------------------------------------------

  function perform_delete_person( $person_id, $display_status_msg = FALSE ) {

    if( ! $person_id ) return;

    if( $display_status_msg ) {
      $person_desc = $this->get_person_desc_from_id( $person_id );
    }

    $statement = 'delete from ' . $this->proj_person_tablename()
               . " where person_id = '$person_id' ";
    $this->db_run_query( $statement );

    if( $display_status_msg ) {
      echo 'Deleted ' . $person_desc;
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      $anchor_name = 'deleted_person_id_' . $person_id;
      HTML::anchor( $anchor_name );
      $script = 'window.location.href = "' . $_SERVER['PHP_SELF'] . '" + "#' . $anchor_name . '"';
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX && $this->app_get_class( $this ) != 'popup_organisation' ) {
      echo "This screen searches and allows editing of all <em>agents</em>, i.e. all entities capable of acting."
           . ' An agent may be either an individual person or a group/organisation such as a theological school. ';
    }

    if( $this->app_get_class( $this ) == 'popup_organisation' ) 
      echo 'Enter some details of the required group or organisation (e.g. part or all of its name)'
           . ' and click Search or press Return.';
    else
      echo 'Enter some details of the required person or organisation (e.g. part or all of their name)'
           . ' and click Search or press Return.';
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function one_person_search_results() {  # Really just a dummy option to allow Edit Person to be called via GET.
    if( $this->proj_edit_mode_enabled() )
      $this->edit_person();
    else {

      HTML::italic_start();
      echo 'Sorry, you do not have edit privileges in this database. Read-only details are now being displayed.';
      HTML::italic_end();
      HTML::new_paragraph();

      $this->write_post_parm( 'iperson_id', $this->read_get_parm( 'iperson_id' ));
      $this->write_post_parm( 'date_or_numeric_query_op_iperson_id', 'equals' );
      $this->write_post_parm( 'record_layout', 'down_page' );

      $this->db_search_results();
    }
  }
  #-----------------------------------------------------

  function add_person() {
    $this->edit_person( $new_record = TRUE );
  }
  #-----------------------------------------------------

  function db_browse_plugin_2( $column_name = NULL, $column_value = NULL ) {

    if( $column_name != 'iperson_id' ) return;
    if( ! $column_value ) return;
    if( $this->printable_output || $this->csv_output ) return;
    if( ! $this->proj_edit_mode_enabled() ) return;
    echo LINEBREAK;

    $title = 'Edit person details';

    $href = $_SERVER[ 'PHP_SELF' ] . '?class_name=' . $this->app_get_class( $this ) . '&method_name=one_person_search_results';
 
    $href .= '&' . $column_name . '=' . $column_value;
 
    $href .= '&opening_method=' . $this->menu_method_name;

    HTML::link_start( $href, $title, $target = '_blank' );
    echo 'Edit';
    HTML::link_end();
  }
  #-----------------------------------------------------

  function edit_person( $new_record = FALSE, $just_saved = FALSE ) {

    if( ! $new_record ) {
      $iperson_id = $this->read_post_parm( 'iperson_id' );
      $opening_method = $this->read_post_parm( 'opening_method' );

      if( ! $iperson_id ) {
        $iperson_id = $this->read_get_parm( 'iperson_id' );
        $opening_method = $this->read_get_parm( 'opening_method' );
      }
      $keep_failed_validation = $this->failed_validation;

      $found = $this->set_person( $iperson_id );
      if( ! $found ) $this->die_on_error( 'Invalid person/org ID' );

      $this->failed_validation = $keep_failed_validation;
    }

    if( $this->failed_validation ) {
      echo LINEBREAK . 'Your data could not be saved. Please correct invalid details and try again.' . LINEBREAK;
    }

    $this->person_entry_stylesheets();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_person' );

    HTML::hidden_field( 'opening_method', $opening_method );
    $focus_script = NULL;

    if( ! $new_record ) {
#--  Added to enable link for prosopography editing
      $href = '/proform/activity_view.php'
            . '?person_id=' . rawurlencode( $iperson_id );

      HTML::link_start( $href, 'Edit Prosopography', '_blank' );
      echo 'Edit Prosopography';
      HTML::link_end();

## -- Couldn't get button invocation working below RG 25-sep-14 so setup link as above
#      HTML::button( 'editpro_button2', 
#            'Edit Prosopography', 
#            $tabindex=1, 
#            $other_parms='onclick="window.location.href="'.$href.'"' );

      HTML::new_paragraph();
      HTML::italic_start();
      echo 'Person or Organisation ID ' . $this->iperson_id 
           . '. Last changed ' . $this->postgres_date_to_words( $this->change_timestamp )
           . ' by ' . $this->change_user . ' ';
      HTML::italic_end();

      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $opening_method );
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      HTML::hidden_field( 'iperson_id', $iperson_id );
      HTML::hidden_field( 'person_id', $this->person_id );
    }

    if( $this->failed_validation ) {  # read values from POST and re-display
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_person_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }
    }

    $this->person_entry_fields( $new_record );

    HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );
    HTML::submit_button( 'clear_search_button', 'Search' );
    $this->save_button( $prefix = 'page_bottom', $start_new_div = FALSE );

    HTML::form_end();

    if( ! $new_record ) {
      $this->offer_person_deletion_form();
    }

    # If the user clicked a 'Save and continue' button, return them to the section of the form where they were working
    # rather than to the 'Refresh' button at the top of the form.

    $anchor_script = $this->proj_get_anchor_script_after_save();

    if( $anchor_script )
      HTML::write_javascript_function( $anchor_script );
    elseif( $focus_script ) 
      HTML::write_javascript_function( $focus_script );
  }
  #-----------------------------------------------------

  function person_entry_stylesheets() {

    $this->write_work_entry_stylesheet();  # method from Project
    $this->date_entity->write_date_entry_stylesheet();  # method from Date Entity
  }
  #-----------------------------------------------------

  function person_entry_fields( $new_record ) {

    if( ! $this->iperson_id ) $new_record = TRUE;

    $this->suppress_relationship_sections = FALSE;
    if( $new_record ) $this->suppress_relationship_sections = TRUE;

    $this->proj_form_section_links( 'core_fields' );
    $this->primary_name_field();

    $this->gender_field();

    $this->org_field();

    $this->org_type_field();

    $this->editors_notes_field();

    $this->save_button( $prefix = 'core_fields', $start_new_div = TRUE );

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    #------

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {

      $this->proj_form_section_links( 'nisbas' );
      $this->person_nisbas_field();
      $this->save_button( 'nisbas', TRUE );
      HTML::horizontal_rule();

      $this->proj_form_section_links( 'professions' );
      $this->impact_roles_and_titles_field();
      $this->save_button( 'professions', TRUE );
      HTML::horizontal_rule();
    }
    #------

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      $this->proj_form_section_links( 'synonyms' );
      $this->synonyms_field();
    }

    #------

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      $this->proj_form_section_links( 'aliases' );
      $this->aliases_field();
    }

    #------

    $this->proj_form_section_links( 'person_dates' );

    $this->date_of_birth_field();
    $this->save_button( 'person_dates', TRUE );

    #------

    # Extra anchor for use with the 'Save and Continue' button
    HTML::anchor( 'date_of_death_anchor' );
    $this->extra_anchors[] = 'date_of_death';

    $this->date_of_death_field();
    $this->save_button( 'date_of_death', TRUE );

    #------
    HTML::anchor( 'flourished_anchor' );
    $this->extra_anchors[] = 'flourished';

    $this->flourished_field();
    $this->save_button( 'flourished', TRUE );

    HTML::horizontal_rule();

    #------

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->proj_form_section_links( 'affiliation_types' );
      $this->org_subtype_field();
      $this->save_button( 'affiliation_types', TRUE );
      HTML::horizontal_rule();

      if( ! $new_record ) {  # cannot create relationships until the core record has been created
        $this->proj_form_section_links( 'belongs_to_orgs' );
        $this->orgs_of_which_member_entry_field();

        HTML::new_paragraph();
        $this->save_button( 'belongs_to_orgs', TRUE );
        HTML::horizontal_rule();
        HTML::new_paragraph();
      }
    }
    #------

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->proj_form_section_links( 'nationalities' );
      $this->person_nationalities_field();

      HTML::new_paragraph();
      $this->save_button( 'nationalities', TRUE );
      HTML::horizontal_rule();
      HTML::new_paragraph();
    }

    #------

    if( ! $new_record ) {  # cannot create relationships until the core record has been created

      $this->write_display_change_script();

      $this->proj_form_section_links( 'person_locations' );
      $this->person_locations_entry_field();

      HTML::new_paragraph();

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
        $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_LOCATIONS_OF_PERSON,
                                'Bibliographic references and other notes on known locations:' );
        HTML::new_paragraph();
      }

      $this->save_button( 'person_locations', TRUE );
      HTML::horizontal_rule();
      HTML::new_paragraph();

      #------

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        $this->proj_form_section_links( 'belongs_to_orgs' );
        $this->orgs_of_which_member_entry_field();

        HTML::new_paragraph();
        $this->save_button( 'belongs_to_orgs', TRUE );
        HTML::horizontal_rule();
        HTML::new_paragraph();
      }

      #------

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        $this->proj_form_section_links( 'has_members' );

        if( $this->is_organisation ) {
          $entry_fields_display_style = 'block';
          $msg_display_style = 'none';
        }
        else {
          $entry_fields_display_style = 'none';
          $msg_display_style = 'block';
        }

        HTML::div_start( 'id="members_of_org_msg_div" style="display: ' . $msg_display_style . '"' );
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          echo 'This record refers to a person not a group. Affiliates can only be entered for groups.';
        else
          echo 'This record refers to a person not an organisation. Members can only be entered for organisations.';
        HTML::div_end();

        HTML::div_start( 'id="members_of_org_entry_field_div" style="display: ' . $entry_fields_display_style . '"' );

        $this->members_of_org_entry_field();

        HTML::new_paragraph();
        $this->save_button( 'has_members', TRUE );
        HTML::div_end();

        HTML::horizontal_rule();
        HTML::new_paragraph();
      }

      #------

      $this->proj_form_section_links( 'person_to_person' );
      $this->person_to_person_entry_field();
      HTML::horizontal_rule();

      #------

      $this->proj_form_section_links( 'notes_on_person' );
      $this->notes_on_person_field();

      HTML::new_paragraph();
      $this->save_button( 'notes_on_person', TRUE );
      HTML::horizontal_rule();

      #------

      $this->proj_form_section_links( 'related_resources' );
      $this->related_resources_field();
      HTML::new_paragraph();

      HTML::horizontal_rule();
      HTML::new_paragraph();

      #------
    }

    $this->proj_form_section_links( 'further_reading' );
    $this->further_reading_field();
    HTML::new_paragraph();
    $this->save_button( 'further_reading', TRUE );
    HTML::horizontal_rule();

    #------

    if( ! $new_record   # cannot create relationships until the core record has been created
    &&  $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {

      $this->proj_form_section_links( 'imgs' );
      if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );

      $this->write_post_parm( 'person_id', $this->person_id ); # so it can be picked up by the image object

      $this->image_obj->image_entry_for_entity( $this->person_id, $entity_type = 'person' );
      HTML::new_paragraph();
    }
  }
  #-----------------------------------------------------

  function proj_list_form_sections() { # some of these sections will not be available when adding new record 

    if( $this->in_person_to_person_section ) {
      $form_subsections = array( 
        'has_members'                       => 'Previous section of form',
        'parents_subsection'                => 'Parents',
        'children_subsection'               => 'Children',
        'employers_subsection'              => 'Employers',
        'employees_subsection'              => 'Employees',
        'teachers_subsection'               => 'Teachers',
        'students_subsection'               => 'Students',
        'patrons_subsection'                => 'Patrons',
        'protegees_subsection'              => 'Prot&eacuteg&eacute;s',
        'other_person_to_person_subsection' => 'Other relationships',
        'notes_on_person'                   => 'Next section of form'
      );
      return $form_subsections;
    }

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $possible_form_sections = array( 
        'core_fields'      => "Core fields",
        'nisbas'           => "Nisbas",
        'professions'      => "Professions/titles",
        'person_dates'     => "Dates",
        'affiliation_types'=> 'Affiliations overview',
        'belongs_to_orgs'  => 'Affiliation dates and details',
        'nationalities'    => "Nationality",
        'person_locations' => "Known geographical locations",
        'person_to_person' => 'Relationships with others',
        'notes_on_person'  => "Researchers' notes for front-end display",
        'related_resources'=> 'Related resources',
        'further_reading'  => 'Further reading' 
      );
    }
    else {
      $possible_form_sections = array( 
        'core_fields'      => "Core fields and editors' notes",
        'synonyms'         => 'Synonyms',
        'aliases'          => 'Roles/titles',
        'person_dates'     => "Dates",
        'person_locations' => "Known geographical locations",
        'belongs_to_orgs'  => 'Organisations to which belonged',
        'has_members'      => 'Members',
        'person_to_person' => 'Relationships with others',
        'notes_on_person'  => "Researchers' notes for front-end display",
        'related_resources'=> 'Related resources',
        'further_reading'  => 'Further reading',
        'imgs'             => 'Images' 
      );
    }

    if( ! $this->suppress_relationship_sections ) 
      return $possible_form_sections; # all possible sections of the form are valid for existing person

    $actual_form_sections = array();

    foreach( $possible_form_sections as $code => $desc ) {
      $display_it = TRUE;
      switch( $code ) {
        case 'notes_on_person':
        case 'related_resources':
        case 'belongs_to_orgs':
        case 'has_members':
        case 'person_locations':
        case 'person_to_person':
        case 'imgs':
          $display_it = FALSE;
          break;
      }
      if( $display_it )
        $actual_form_sections[ "$code" ] = $desc;
    }

    return $actual_form_sections;
  }
  #-----------------------------------------------------

  function save_button( $prefix = NULL, $start_new_div = FALSE, 
                        $save_and_continue_parms = NULL, $save_and_end_parms = NULL ) {

    if( $start_new_div ) {
      HTML::div_start( 'class="workfield"' );
      HTML::new_paragraph();
    }

    $this_class = strtolower( $this->app_get_class( $this ));

    if( $this_class == 'popup_person' || $this_class == 'popup_organisation' ) { #no 'Save and Continue' in popup screens
      $button_name = 'save_button';
      if( $prefix ) $prefix . '_' . $button_name;
      HTML::submit_button( $button_name, 'Save' );
    }
    else  # offer a choice between 'Save and Continue' and 'Save and End'
      $this->proj_extra_save_button( $prefix, $new_paragraph = FALSE, $save_and_continue_parms, $save_and_end_parms );

    if( $start_new_div ) {
      HTML::new_paragraph();
      HTML::div_end();
    }
  }
  #-----------------------------------------------------

  function save_person() {

    # Cancel/close buttons normally close the tab, but sometimes browser may prevent this, so treat as new search.
    if( $this->parm_found_in_post( 'cancel_button' ) ||  $this->parm_found_in_post( 'close_self_button' )) 
      $this->write_post_parm( 'clear_search_button', 'Search' );

    if( $this->parm_found_in_post( 'clear_search_button' )) {
      HTML::h4_start();
      echo 'Edit cancelled.';
      HTML::h4_end();
      HTML::new_paragraph();
      $this->db_search();
      return;
    }

    $this->db_run_query( 'BEGIN TRANSACTION' );

    $iperson_id = $this->read_post_parm( 'iperson_id' );

    if( $iperson_id ) {

      if( $this->parm_found_in_post( 'upload_images_button' )) { # upload images rather than saving
        $found = $this->set_person( $iperson_id );
        if( ! $found ) $this->die_on_error( 'Invalid person/org ID' );
        if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
        $this->image_obj->image_upload_form( $entity_type = 'person' );
        return;
      }

      $this->save_existing_person( $iperson_id );
    }
    else {
      $this->save_new_person();
    }

    $new_record = FALSE; 
    $just_saved = TRUE;

    if( $this->failed_validation ) {
      $this->db_run_query( 'ROLLBACK' );
      $just_saved = FALSE;
      if( ! $iperson_id ) $new_record = TRUE;
    }
    else {
      $this->db_run_query( 'COMMIT' );
    }

    $this->edit_person( $new_record, $just_saved );
  }
  #-----------------------------------------------------

  function save_existing_person( $iperson_id ) {

    if( ! $iperson_id ) $iperson_id = $this->read_post_parm( 'iperson_id' );
    $found = $this->set_person( $iperson_id );
    if( ! $found ) $this->die_on_error( 'Invalid person/org ID' );

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_person_tablename());
    $i = 0;
    $statement = 'update ' . $this->proj_person_tablename() . ' set ';

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'person_id':
        case 'iperson_id':
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
        case 'other_details_summary': # generate this later
        case 'other_details_summary_searchable': # it's generated by Dbf Cascade 02 Rel Changes
        case 'uuid' :
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $i++;
      if( $i > 1 ) $statement .= ', ';

      $column_value = $this->read_post_parm( $column_name );

      if( $column_name == 'foaf_name' ) $this->validate_primary_name( $column_value );

      $statement .= $column_name . ' = ';
      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $statement .= $column_value;
    }

    $statement .= " where person_id = '$this->person_id'";

    if( ! $this->failed_validation ) {
      echo 'Please wait: saving your changes...' . LINEBREAK;
      flush();
      $this->db_run_query( $statement );

      $this->save_birthplace();
      $this->save_place_of_death();
      $this->save_person_locations();
      $this->save_orgs_of_which_member();
      $this->save_members_of_org();
      $this->save_parents();
      $this->save_children();
      $this->save_employers();
      $this->save_employees();
      $this->save_teachers();
      $this->save_students();
      $this->save_patrons();
      $this->save_protegees();
      $this->save_person_to_person_equal();
      $this->save_comments();
      $this->save_resources();

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
        $this->save_nisbas();
        $this->save_org_subtypes();
      }
      $this->save_nationalities();
      $this->save_role_categories();

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        $this->save_images();
      }
      
      HTML::h4_start();
      echo 'Any changes have been saved.';
      HTML::h4_end();
    }
  }
  #-----------------------------------------------------

  function save_new_person() {

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_person_tablename()) . "'::regclass )";
    $iperson_id = $this->db_select_one_value( $statement );

    $function_name = $this->get_system_prefix() . '_common_make_text_id';
    $statement = "select $function_name ( '" . $this->proj_person_tablename() . "', "
                                             . "'iperson_id', "
                                             . "$iperson_id )";
    $person_id = $this->db_select_one_value( $statement );

    $this->write_post_parm( 'person_id', $person_id );
    $this->write_post_parm( 'iperson_id', $iperson_id );

    $statement = '';
    $col_statement = '';
    $val_statement = '';

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_person_tablename());
    $i = 0;
    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
        case 'uuid' :
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $i++;
      if( $i > 1 ) {
        $col_statement .= ', ';
        $val_statement .= ', ';
      }

      $col_statement .= $column_name;

      $column_value = $this->read_post_parm( $column_name );

      if( $column_name == 'foaf_name' ) $this->validate_primary_name( $column_value );

      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $val_statement .= $column_value;
    }

    if( $this->failed_validation ) {
      $this->write_post_parm( 'person_id', '' );
      $this->write_post_parm( 'iperson_id', '' );
      return;
    }

    $statement = 'insert into ' . $this->proj_person_tablename() . " ( $col_statement ) values ( $val_statement )";
    $this->db_run_query( $statement );

    $this->save_role_categories( $person_id );  # transaction handling is in outer, calling function
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->save_nisbas( $person_id );
      $this->save_org_subtypes( $person_id );
    }

    HTML::h4_start();
    echo 'New person or organisation has been saved.';
    HTML::h4_end();

    if( $this->app_get_class( $this ) == 'popup_person' || $this->app_get_class( $this ) == 'popup_organisation' ) 
      return $iperson_id;

    HTML::div_start( 'class="buttonrow"' );

    HTML::form_start( $this->app_get_class( $this ), 'add_person' );
    echo 'Add another new person? ';
    HTML::submit_button( 'add_another_new_button', 'New' );
    HTML::form_end();

    echo ' ';

    HTML::form_start( $this->app_get_class( $this ), 'db_search' );
    echo 'Return to search? ';
    HTML::submit_button( 'return_to_search_button', 'Search' );
    HTML::form_end();

    echo LINEBREAK;
    HTML::div_end();
  }
  #-----------------------------------------------------

  function save_comments() {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $this->comment_obj->save_comments( $this->proj_person_tablename(), 
                                       $this->person_id, 
                                       RELTYPE_COMMENT_REFERS_TO_ENTITY );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {

      $this->comment_obj->save_comments( $this->proj_person_tablename(), 
                                         $this->person_id, 
                                         RELTYPE_COMMENT_REFERS_TO_RELATIONSHIPS_OF_PERSON );

      $this->comment_obj->save_comments( $this->proj_person_tablename(), 
                                         $this->person_id, 
                                         RELTYPE_COMMENT_REFERS_TO_LOCATIONS_OF_PERSON );

      $this->comment_obj->save_comments( $this->proj_person_tablename(), 
                                         $this->person_id, 
                                         RELTYPE_COMMENT_REFERS_TO_AFFILIATIONS_OF_PERSON );

      $this->comment_obj->save_comments( $this->proj_person_tablename(), 
                                         $this->person_id, 
                                         RELTYPE_COMMENT_REFERS_TO_MEMBERS_OF_ORG );
    }
  }
  #-----------------------------------------------------

  function save_resources() {

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else 
      $this->resource_obj->clear();

    $this->resource_obj->save_resources( $this->proj_person_tablename(), 
                                         $this->person_id );
  }
  #-----------------------------------------------------

  function save_orgs_of_which_member() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_orgs_of_which_member(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_members_of_org() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_members_of_org(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_person_locations() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_person_locations(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_birthplace() {

    $this->rel_obj->save_single_rel_for_field_type( $field_type = $this->fieldset_name_for_place_of_birth(), 
                                                    $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_place_of_death() {

    $this->rel_obj->save_single_rel_for_field_type( $field_type = $this->fieldset_name_for_place_of_death(), 
                                                    $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_parents() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_parents(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_children() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_children(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_employers() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_employers(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_employees() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_employees(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_patrons() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_patrons(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_protegees() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_protegees(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_teachers() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_teachers(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_students() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_students(), 
                                              $known_id_value = $this->person_id ); 
  }
  #-----------------------------------------------------

  function save_person_to_person_equal() {

    $this->rel_obj->save_rels_for_field_type( $field_type = $this->fieldset_name_for_person_to_person_equal(), 
                                              $known_id_value = $this->person_id );
                                                
  }
  #----------------------------------------------------------------------------------

  function save_images() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->image_obj->save_image_details( $entity_id = $this->person_id, $entity_type = 'person' );
  }
  #-----------------------------------------------------

  function prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date ) {

    switch( $column_name ) {

      case 'date_of_birth_year':
      case 'date_of_birth_month':
      case 'date_of_birth_day':
      case 'date_of_birth2_year':
      case 'date_of_birth2_month':
      case 'date_of_birth2_day':

      case 'date_of_death_year':
      case 'date_of_death_month':
      case 'date_of_death_day':
      case 'date_of_death2_year':
      case 'date_of_death2_month':
      case 'date_of_death2_day':

      case 'flourished_year':
      case 'flourished_month':
      case 'flourished_day':
      case 'flourished2_year':
      case 'flourished2_month':
      case 'flourished2_day':

        if( $column_value == 0 ) $column_value = 'null';
        break;

      case 'date_of_birth_is_range':
      case 'date_of_birth_approx':
      case 'date_of_birth_uncertain':
      case 'date_of_birth_inferred':
      case 'date_of_death_is_range':
      case 'date_of_death_approx':
      case 'date_of_death_uncertain':
      case 'date_of_death_inferred':
        case 'flourished_inferred':
        case 'flourished_uncertain':
        case 'flourished_approx':
      case 'flourished_is_range':
        if( $column_value == '' ) $column_value = '0';
        break;

      case 'skos_altlabel':
      case 'person_aliases':
        $column_value = $this->process_alternative_names( $column_value );
        break;

      default:
        break;
    }

    if( $is_numeric ) {
      if( strlen( trim( $column_value )) == 0 ) $column_value = 'null';
    }

    elseif( $is_date ) {
      if( strlen( trim( $column_value )) == 0 ) 
        $column_value = 'null';
      elseif( $column_value == '9999-12-31' ) 
        $column_value = 'null';
      else
        $column_value = "'" . $column_value . "'::date";
    }

    else {
      $column_value = "'" . $this->escape( $column_value ) . "'";
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function validate_primary_name( $value ) {

    $value = str_replace( NEWLINE, '', $value );
    $value = str_replace( CARRIAGE_RETURN, '', $value );
    $value = trim( $value );

    if( ! $value ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( $parm_name = 'Primary name', $errmsg = 'cannot be blank' );
      return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------


  function primary_name_field() {

    echo LINEBREAK;

    HTML::div_start( 'class="workfield boldlabel"' );

    HTML::input_field( 'foaf_name', 'Primary name*', $this->foaf_name, FALSE, PERSON_PRIMARY_NAME_FLD_SIZE );

    if( $this->app_get_class( $this ) == 'popup_person' || $this->app_get_class( $this ) == 'popup_organisation' ) 
      HTML::new_paragraph();

    HTML::italic_start();
    echo ' (No more than ' . PERSON_PRIMARY_NAME_MAX_FLD_SIZE . ' characters.';

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      if( $this->app_get_class( $this ) != 'popup_organisation' ) echo ' Preferably Surname, Forename';
    }
    echo ')';
    HTML::italic_end();
    HTML::div_end();

    HTML::new_paragraph();

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      HTML::div_start( 'class="workfield"' );

      $this->impact_synonyms_field();
      HTML::new_paragraph();
      HTML::div_end();
    }
  }
  #-----------------------------------------------------


  function gender_field() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( $label_text = 'Gender:', $label_id='gender_label' ); #free-standing label
    HTML::span_end();

    HTML::span_start( 'class="workfieldaligned"' );

    HTML::radio_button( $fieldname = 'gender', $label = 'Male', $value_when_checked = 'M', 
                        $current_value = $this->gender, $tabindex=1, $button_instance=1, $script=NULL );

    HTML::radio_button( $fieldname = 'gender', $label = 'Female', $value_when_checked = 'F', 
                        $current_value = $this->gender, $tabindex=1, $button_instance=2, $script=NULL );

    HTML::radio_button( $fieldname = 'gender', $label = 'Unknown or not applicable', $value_when_checked = '', 
                        $current_value = $this->gender, $tabindex=1, $button_instance=3, $script=NULL );

    HTML::span_end();

    HTML::new_paragraph();
  }
  #-----------------------------------------------------


  function org_field() {
                               
    $script  = 'function enable_or_disable_members_of_org_entry( isOrg ) {'                          . NEWLINE;
    $script .= '  var orgEntryDiv = document.getElementById( "members_of_org_entry_field_div" );'    . NEWLINE;
    $script .= '  var orgMsgDiv = document.getElementById( "members_of_org_msg_div" );'              . NEWLINE;
    $script .= '  if( isOrg.checked == true ) {'                                                     . NEWLINE;
    $script .= "    orgEntryDiv.style.display='block';"                                              . NEWLINE;
    $script .= "    orgMsgDiv.style.display='none';"                                                 . NEWLINE;
    $script .= '  }'                                                                                 . NEWLINE;
    $script .= '  else {'                                                                            . NEWLINE;
    $script .= "    orgEntryDiv.style.display='none';"                                               . NEWLINE;
    $script .= "    orgMsgDiv.style.display='block';"                                                . NEWLINE;
    $script .= '  }'                                                                                 . NEWLINE;
    $script .= '}'                                                                                   . NEWLINE;

    HTML::write_javascript_function( $script );

    #-----

    HTML::div_start( 'class="workfield"' );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $onclick_action = 'enable_or_disable_org_type( this )'; 
      $label = 'Is this a group / organisation?';
    }
    else {
      $label = 'Organisation?';
      if( ! $this->person_id ) # 'Add New' doesn't have 'members' section
        $onclick_action = '';
      else
        $onclick_action = 'enable_or_disable_members_of_org_entry( this );';

      $onclick_action .= 'enable_or_disable_org_type( this )'; 
    }

    HTML::checkbox_with_label_on_left( $fieldname = 'is_organisation', 
                                       $label, 
                                       $is_checked = $this->is_organisation, 
                                       $value_when_checked = 'Y',
                                       $in_table = FALSE,
                                       $tabindex = 1,
                                       $input_instance = NULL,
                                       $parms = 'onclick="' . $onclick_action . '"' ); 

    if( $this->app_get_class( $this ) == 'popup_person' ) HTML::new_paragraph();
    HTML::italic_start();

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      echo 'Leave this checkbox blank when entering details of an individual person.';
      echo LINEBREAK;
      if( $this->app_get_class( $this ) != 'popup_person' ) HTML::span_start( 'class="workfieldaligned"' );
      echo 'Tick if entering details of a group or organisation such as a madhhab or theological school.';
      if( $this->app_get_class( $this ) != 'popup_person' ) HTML::span_end();
    }
    else {
      echo ' (Leave this checkbox blank if the correspondent was an individual person;'
           . ' tick if the correspondent was a group or organisation.)';
    }
    HTML::italic_end();
    HTML::div_end();

    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function org_type_field() {

    #-- Enable or disable the 'organisation type' field
                               
    $script  = 'function enable_or_disable_org_type( isOrg ) {'                            . NEWLINE;
    $script .= '  var orgTypeDiv = document.getElementById( "org_type_entry_field_div" );' . NEWLINE;
    $script .= '  if( isOrg.checked == true ) {'                                           . NEWLINE;
    $script .= "    orgTypeDiv.style.display='block';"                                     . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '  else {'                                                                  . NEWLINE;
    $script .= '    var orgTypeFld = document.getElementById( "organisation_type" );'      . NEWLINE;
    $script .= '    orgTypeFld.selectedIndex = 0;'                                         . NEWLINE;
    $script .= "    orgTypeDiv.style.display='none';"                                      . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;

    # When the 'is organisation' flag changes, any previous organisation type will be blanked out,
    # so re-enable the full range of organisation sub-types.
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      $script .= '  enable_or_disable_org_subtypes( null );'                               . NEWLINE;

    $script .= '}'                                                                         . NEWLINE;

    HTML::write_javascript_function( $script );

    $org_type_obj = new Org_Type( $this->db_connection );
    $org_type_obj->set_data_entry_mode();

    HTML::div_start( 'id="org_type_entry_field_div" class="workfield"' );

    # If the table is empty, create a dummy dropdown list so that your Javascript will still work
    $statement = 'select count(*) from ' . $this->proj_org_type_tablename();
    $org_type_count = $this->db_select_one_value( $statement );
    $dropdown_label = $this->db_get_default_column_label( 'organisation_type' );

    if( $org_type_count > 0 ) {
      $org_type_obj->lookup_table_dropdown( $field_name = 'organisation_type', 
                                            $field_label = $dropdown_label,
                                            $selected_id = $this->organisation_type );
    }
    else { # no options in table
      HTML::dropdown_start( $fieldname = 'organisation_type', $label = $dropdown_label );
      HTML::dropdown_option( $internal_value = 'null', $displayed_value = '(no options found)', $selection = 'null' );
      HTML::dropdown_end();
    }

    if( $this->is_organisation ) {
      echo LINEBREAK;
      HTML::span_start( 'class="workfieldaligned"' );
      HTML::italic_start();
      echo "Further details of a group's purpose or role can be given in the professions/titles field.";
      HTML::italic_end();
      HTML::span_end();
    }
    HTML::new_paragraph();
    HTML::div_end();

    # Hide the field if necessary
    if( ! $this->is_organisation ) {
      $script  = 'var isOrg = document.getElementById( "is_organisation" );'      . NEWLINE;
      $script .= 'enable_or_disable_org_type( isOrg );'                           . NEWLINE;
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------

  function org_subtype_field() {

    # Allow entry of organisation sub-categories and affiliation types
    HTML::div_start( 'id="org_subtype_entry_field_div"' );

    if( $this->person_id && ! $this->is_organisation ) {
      echo 'This section allows entry of a';

      HTML::italic_start();
      echo ' summary';
      HTML::italic_end();

      echo ' of affiliations. It is also possible to enter';

      HTML::italic_start();
      HTML::link( '#belongs_to_orgs_anchor', "dates and more specific details" );
      HTML::italic_end();

      echo ' of affiliations to a group or organisation.';
    }

    $org_subtype_obj = new Org_Subtype( $this->db_connection );
    $org_subtype_obj->org_subtype_entry_fields( $person_id = $this->person_id,
                                                $required_org_type = $this->organisation_type );
    HTML::div_end();  # end subtypes entry div

    HTML::new_paragraph();
    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_AFFILIATIONS_OF_PERSON, 
                             'Bibliographic references and other notes on affiliations:' );
  }
  #-----------------------------------------------------

  function editors_notes_field() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {  # IMPAcT want to use this field for bibliographic refs.
      $this->impact_bibliographic_refs_field();
      return;
    }

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->proj_textarea( 'editors_notes', PERSON_EDITORS_NOTES_ROWS, PERSON_EDITORS_NOTES_COLS,
                          $value = $this->editors_notes, $label = "Editors' notes" );
    echo LINEBREAK;

    HTML::italic_start();

    HTML::span_start( 'class="workfieldaligned"' );
    echo "These notes are for internal use; we do not plan to display them on the 'front end' website.";
    echo LINEBREAK;
    HTML::span_end();

    HTML::span_start( 'class="workfieldaligned"' );
    echo " To enter a publicly available note, please use the researchers' notes field instead.";
    HTML::span_end();

    HTML::italic_end();

    HTML::new_paragraph();
    HTML::div_end();

  }
  #-----------------------------------------------------

  function impact_bibliographic_refs_field() {  # also contains call to roles/titles functions

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    HTML::input_field( 'editors_notes', 'Bibliographic references and other notes', $this->editors_notes, 
                        FALSE, PERSON_PRIMARY_NAME_FLD_SIZE );
    HTML::div_end();

    $this->proj_publication_popups( $calling_field = 'editors_notes' );
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function further_reading_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->proj_textarea( 'further_reading', FURTHER_READING_ROWS, FURTHER_READING_COLS,
                          $value = $this->further_reading, $label = "Bibliographical information" );
    HTML::div_end();

    $this->proj_publication_popups( $calling_field = 'further_reading' );
    HTML::new_paragraph();

  }
  #-----------------------------------------------------

  function synonyms_field() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) { # as ever, IMPAcT just have to be different
      return;
    }
    
    $this->alternatives_field( $fieldname = 'skos_altlabel', 
                               $label_singular = 'synonym', 
                               $label_plural = 'Alternative names and spellings', 
                               $msg  = 'Please use this field for different spellings of the primary name,'
                                     . ' e.g. Lyster for Lister.', 
                               $msg2 = ' You can also use it for alternative names'
                                     . " such as a woman's maiden/married name." );
  }
  #-----------------------------------------------------

  function impact_synonyms_field() {

    HTML::div_start( 'class="workfield"' );

    HTML::input_field( 'skos_altlabel', 'Alternative names', 
                       $this->skos_altlabel, FALSE, PERSON_PRIMARY_NAME_FLD_SIZE );


    if( $this->app_get_class( $this ) != 'popup_person' && $this->app_get_class( $this ) != 'popup_organisation' ) {
      echo LINEBREAK;
      HTML::span_start( 'class="workfieldaligned"' );
    }
    else
      HTML::new_paragraph();

    HTML::italic_start();
    echo 'Please use this field for variations, e.g. Kamalpashazada vs. Ibn Kamal Basha.';
    HTML::italic_end();
    if( $this->app_get_class( $this ) != 'popup_person' && $this->app_get_class( $this ) != 'popup_organisation' ) HTML::span_end();

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function aliases_field() {

    $this->person_role_categories_field();
    HTML::new_paragraph();

    $msg = 'Please use this field for titles and career-related details such as '
         . " '5th Earl of Aylesbury' or 'Bishop of Chester 1650-1680'.";

    if( $this->app_get_class( $this ) == 'popup_person' )
      $msg = "Please use this field for career-related details such as 'Bishop of Chester 1650-1680'.";
    elseif( $this->app_get_class( $this ) == 'popup_organisation' )
      $msg = '';

    $this->alternatives_field( $fieldname = 'person_aliases', 
                               $label_singular = 'role/title', 
                               $label_plural = 'Further details of roles / titles', 
                               $msg,
                               $msg2 = '' );

  }
  #-----------------------------------------------------

  function impact_roles_and_titles_field() {

    HTML::new_paragraph();

    $this->person_role_categories_field();

    HTML::new_paragraph();
    HTML::div_start( 'class="workfield"' );

    HTML::input_field( 'person_aliases', 'Further details of professions / titles', 
                        $this->person_aliases, 
                        FALSE, PERSON_PRIMARY_NAME_FLD_SIZE );

    if( ! $this->is_organisation ) {
      echo LINEBREAK;
      HTML::italic_start();

      HTML::span_start( 'class="workfieldaligned"' );
      echo "E.g. 'Made chair of Madrasa al-'Aziziyya by al-Malik al-Mu'azzaa, 617/1220-1.'";
      HTML::span_end();

      HTML::italic_end();
    }
    echo LINEBREAK . LINEBREAK;
    HTML::div_end();

  }
  #-----------------------------------------------------

  function alternatives_field( $fieldname, $label_singular, $label_plural, $msg, $msg2 = NULL ) {

    # Replace names separated by vertical bar or semi-colon with one name per line
    $this->$fieldname = str_replace( PERS_ALT_NAME_SEPARATOR_INTERNAL,  NEWLINE, $this->$fieldname );
    $this->$fieldname = str_replace( PERS_ALT_NAME_SEPARATOR_DISPLAYED, NEWLINE, $this->$fieldname );

    $workarray = explode( NEWLINE, $this->$fieldname );
    $this->$fieldname = '';
    foreach( $workarray as $line ) {
      $line = trim( $line );
      if( ! $line ) continue;
      if( $this->$fieldname > '' ) $this->$fieldname .= NEWLINE;
      $this->$fieldname .= $line;
    }

    HTML::div_start( 'class="workfield"' );

    HTML::textarea( $fieldname, 
                    $rows = PERSON_ALTERNATIVE_NAME_ROWS, 
                    $cols = PERSON_PRIMARY_NAME_FLD_SIZE, 
                    $value = $this->$fieldname, 
                    $label = $label_plural );

    HTML::new_paragraph();
    HTML::div_end();
    
    HTML::italic_start();

    HTML::span_start( 'class="workfieldaligned"' );
    echo $msg;
    HTML::span_end();
    echo LINEBREAK;

    if( $msg2 ) {
      HTML::span_start( 'class="workfieldaligned"' );
      echo $msg2;
      HTML::span_end();
      echo LINEBREAK;
    }

    HTML::span_start( 'class="workfieldaligned"' );
    echo "Please put each $label_singular on a separate line.";
    HTML::new_paragraph();
    HTML::span_end();
    HTML::italic_end();

    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    HTML::submit_button( 'save_button_' . $fieldname, 'Save' );
    HTML::div_end();

    HTML::horizontal_rule();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function date_of_birth_field() {

    $legend = 'Date of birth';
    if( $this->is_organisation ) $legend = 'Date formed';

    $properties = array(
      'date_of_birth'           => $this->date_of_birth,
      'date_of_birth_year'      => $this->date_of_birth_year,
      'date_of_birth_month'     => $this->date_of_birth_month,
      'date_of_birth_day'       => $this->date_of_birth_day,
      'date_of_birth2_year'     => $this->date_of_birth2_year,
      'date_of_birth2_month'    => $this->date_of_birth2_month,
      'date_of_birth2_day'      => $this->date_of_birth2_day,
      'date_of_birth_is_range'  => $this->date_of_birth_is_range,
      'date_of_birth_inferred'  => $this->date_of_birth_inferred,
      'date_of_birth_uncertain' => $this->date_of_birth_uncertain,
      'date_of_birth_approx'    => $this->date_of_birth_approx,
      'date_of_birth_calendar'  => $this->date_of_birth_calendar
    );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $properties[ 'convert_start_and_end_dates_to_ce' ] = TRUE;
      $properties[ 'write_conversion_script' ] = TRUE; # script only needs writing once, on first field (date of birth)
      $properties[ 'date_of_birth_start_ce' ] = $this->date_of_birth_start_ce;
      $properties[ 'date_of_birth_end_ce'   ] = $this->date_of_birth_end_ce;
      $properties[ 'date_of_birth_in_orig_calendar' ] = $this->date_of_birth_in_orig_calendar;
    }

    $this->date_entity->set_properties( $properties );

    $this->date_entity->date_entry_fieldset( $fields = array( 'date_of_birth', 'date_of_birth2' ),
                                             $calendar_field = 'date_of_birth_calendar',
                                             $legend, 
                                             $extra_msg = '',
                                             $hide_sortable_dates = TRUE,
                                             $include_uncertainty_flags = TRUE,
                                             $date_range_help = array( '(Tick this box if the date'
                                             . ' of birth cannot be narrowed down to a single year.)' ),
                                             $display_calendars_in_main_fieldset = TRUE );
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function date_of_death_field() {

    $legend = 'Date of death';
    if( $this->is_organisation ) $legend = 'Date disbanded';

    $properties = array(
      'date_of_death'           => $this->date_of_death,
      'date_of_death_year'      => $this->date_of_death_year,
      'date_of_death_month'     => $this->date_of_death_month,
      'date_of_death_day'       => $this->date_of_death_day,
      'date_of_death2_year'     => $this->date_of_death2_year,
      'date_of_death2_month'    => $this->date_of_death2_month,
      'date_of_death2_day'      => $this->date_of_death2_day,
      'date_of_death_is_range'  => $this->date_of_death_is_range,
      'date_of_death_inferred'  => $this->date_of_death_inferred,
      'date_of_death_uncertain' => $this->date_of_death_uncertain,
      'date_of_death_approx'    => $this->date_of_death_approx,
      'date_of_death_calendar'  => $this->date_of_death_calendar
    );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $properties[ 'convert_start_and_end_dates_to_ce' ] = TRUE;
      $properties[ 'date_of_death_start_ce' ] = $this->date_of_death_start_ce;
      $properties[ 'date_of_death_end_ce'   ] = $this->date_of_death_end_ce;
      $properties[ 'date_of_death_in_orig_calendar' ] = $this->date_of_death_in_orig_calendar;
    }

    $this->date_entity->set_properties( $properties );

    $this->date_entity->date_entry_fieldset( $fields = array( 'date_of_death', 'date_of_death2' ),
                                             $calendar_field = 'date_of_death_calendar',
                                             $legend, 
                                             $extra_msg = '',
                                             $hide_sortable_dates = TRUE,
                                             $include_uncertainty_flags = TRUE, 
                                             $date_range_help = array( '(Tick this box if the date'
                                             . ' of death cannot be narrowed down to a single year.)' ),
                                             $display_calendars_in_main_fieldset = TRUE );
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function flourished_field() {

    $legend = 'Dates when flourished';

    $properties = array(
      'flourished'           => $this->flourished,
      'flourished_year'      => $this->flourished_year,
      'flourished_month'     => $this->flourished_month,
      'flourished_day'       => $this->flourished_day,
      'flourished2_year'     => $this->flourished2_year,
      'flourished2_month'    => $this->flourished2_month,
      'flourished2_day'      => $this->flourished2_day,
      'flourished_is_range'  => $this->flourished_is_range,
      'flourished_inferred'  => $this->flourished_inferred,
      'flourished_uncertain' => $this->flourished_uncertain,
      'flourished_approx'    => $this->flourished_approx,
      'flourished_calendar'  => $this->flourished_calendar
    );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $properties[ 'convert_start_and_end_dates_to_ce' ] = TRUE;
      $properties[ 'flourished_start_ce' ] = $this->flourished_start_ce;
      $properties[ 'flourished_end_ce'   ] = $this->flourished_end_ce;
      $properties[ 'flourished_in_orig_calendar' ] = $this->flourished_in_orig_calendar;
    }

    $this->date_entity->set_properties( $properties );

    $this->date_entity->date_entry_fieldset( $fields = array( 'flourished', 'flourished2' ),
                                             $calendar_field = 'flourished_calendar',
                                             $legend, 
                                             $extra_msg = '',
                                             $hide_sortable_dates = TRUE,
                                             $include_uncertainty_flags = TRUE,
                                             $date_range_help = array( '(Tick this box if the person'
                                             . ' or organisation is known to have been active'
                                             . ' for more than one year.)' ),
                                             $display_calendars_in_main_fieldset = TRUE );
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function notes_on_person_field() {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $general_notes = $this->get_rels_for_general_notes();

    $this->comment_obj->display_or_edit_comments( $general_notes, RELTYPE_COMMENT_REFERS_TO_ENTITY,
                                                  NULL, $this->form_name );
  }
  #-----------------------------------------------------

  function related_resources_field() {

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else
      $this->resource_obj->clear();

    $resources = $this->get_rels_for_resources();

    $this->resource_obj->edit_resources( $resources, $this->proj_person_tablename() );
  }
  #-----------------------------------------------------

  function process_alternative_names( $value ) {

    $value = trim( $value );
    if( $value == '' ) return $value;

    $names = explode( NEWLINE, $value );
    $value = '';

    foreach( $names as $name ) {
      $name = trim( $name );
      if( $name > '' ) {
        if( $value > '' ) $value .= PERS_ALT_NAME_SEPARATOR_DISPLAYED . ' ';
        $value .= $name;
      }
    }

    return $value;
  }
  #-----------------------------------------------------

  function retain_options_during_merge() {

    $simplified_search = $this->read_post_parm( 'simplified_search' );
    $order_by = $this->read_post_parm( 'order_by' );
    $sort_descending = $this->read_post_parm( 'sort_descending' );
    $entries_per_page = $this->read_post_parm( 'entries_per_page' );
    $record_layout = $this->read_post_parm( 'record_layout' );

    HTML::hidden_field( 'simplified_search', $simplified_search );
    HTML::hidden_field( 'order_by', $order_by );
    HTML::hidden_field( 'sort_descending', $sort_descending );
    HTML::hidden_field( 'entries_per_page', $entries_per_page );
    HTML::hidden_field( 'record_layout', $record_layout );

    $myclass = $this->app_get_class( $this );  # use different object to repeat query, so don't overwrite info needed for merge
    $parmwriter = new $myclass( $this->db_connection );
    $parmwriter->db_set_search_result_parms();  # set 'from table' property
    $parmwriter->db_write_hidden_selection_criteria();
  }
  #-----------------------------------------------------

  function orgs_of_which_member_entry_field() {

    $this->proj_edit_area_calling_popups( $fieldset_name = $this->fieldset_name_for_orgs_of_which_member(), 
                                          $section_heading = NULL, 
                                          $decode_display = 'organisation',
                                          $separate_section = FALSE,
                                          $extra_notes = NULL,
                                          $popup_object_name = 'popup_organisation',
                                          $popup_object_class = 'popup_organisation',
                                          $include_date_fields = TRUE );
  }
  #-----------------------------------------------------

  function members_of_org_entry_field() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
      $decode_display = 'affiliate';
    else
      $decode_display = 'member';

    $this->proj_edit_area_calling_popups( $fieldset_name = $this->fieldset_name_for_members_of_org(), 
                                          $section_heading = NULL, 
                                          $decode_display,
                                          $separate_section = FALSE,
                                          $extra_notes = NULL,
                                          $popup_object_name = 'popup_person',
                                          $popup_object_class = 'popup_person',
                                          $include_date_fields = TRUE );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      HTML::new_paragraph();
      $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_MEMBERS_OF_ORG, 'Further details of affiliates:' );
    }
  }
  #-----------------------------------------------------

  function person_locations_entry_field() {
                               
    $this->proj_single_place_entry_field( $fieldset_name=$this->fieldset_name_for_place_of_birth(), $core_desc='birthplace', 
                                          $decode_field_label = 'Birthplace (or place of formation for organisations)');
    HTML::new_paragraph();

    $this->proj_single_place_entry_field( $fieldset_name=$this->fieldset_name_for_place_of_death(), $core_desc='place of death',
                                          $decode_field_label = 'Place of death (or of cessation if an organisation)'); 
    HTML::new_paragraph();

    
    HTML::bold_start();
    echo 'Other locations:';
    HTML::bold_end();
    HTML::new_paragraph();


    $this->proj_edit_area_calling_popups( $fieldset_name = $this->fieldset_name_for_person_locations(), 
                                          $section_heading = NULL, 
                                          $decode_display = 'location',
                                          $separate_section = FALSE,
                                          $extra_notes = NULL,
                                          $popup_object_name = 'popup_location',
                                          $popup_object_class = 'popup_location',
                                          $include_date_fields = TRUE );
  }
  #-----------------------------------------------------

  function person_to_person_entry_field() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_RELATIONSHIPS_OF_PERSON,
                              'Bibliographic references and other notes on relationships:' );
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();
    }

    $this->in_person_to_person_section = TRUE;

    $this->parents_entry_field();

    $this->children_entry_field();

    $this->employers_entry_field();

    $this->employees_entry_field();

    $this->teachers_entry_field();

    $this->students_entry_field();

    $this->patrons_entry_field();

    $this->protegees_entry_field();

    $this->person_to_person_equal_entry_field();

    $this->in_person_to_person_section = FALSE;
  }
  #-----------------------------------------------------

  function person_to_person_subsection ( $fieldset_name, 
                                         $section_heading, $decode_display, $subsection_name, 
                                         $last_subsection = FALSE,
                                         $include_date_fields = TRUE ) {

    # Extra anchor for use with the 'Save and Continue' button
    $extra_anchor = $fieldset_name;
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    # Navigation links
    $this->proj_form_section_links( $subsection_name, $heading_level = 0 );

    if( $section_heading ) {
      HTML::h4_start();
      echo $section_heading;
      HTML::h4_end();
    }

    $this->proj_edit_area_calling_popups( $fieldset_name,
                                          $section_heading = NULL, $decode_display, 

                                          $separate_section = FALSE, $extra_notes = NULL,
                                          $popup_object_name = 'popup_person', 
                                          $popup_object_class = 'popup_person',
                                          $include_date_fields );
    HTML::new_paragraph();

    $this->save_button( $prefix = $fieldset_name, $start_new_div = TRUE, 
                        $save_and_continue_parms = 'class="workfield_save_button"' );

    if( ! $last_subsection ) {
      HTML::horizontal_rule();
    }
  }
  #-----------------------------------------------------
                               
  function person_to_person_equal_entry_field() {

    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_person_to_person_equal(), 
                                          $section_heading = 'Other relationships:', 
                                          $decode_display = 'related person',
                                          $subsection_name = 'other_person_to_person_subsection',
                                          $last_subsection = TRUE );
  }
  #-----------------------------------------------------

  function parents_entry_field() {

    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_parents(), 
                                          $section_heading = 'Parents:', 
                                          $decode_display = 'parent',
                                          $subsection_name = 'parents_subsection',
                                          $last_subsection = FALSE,
                                          $include_date_fields = FALSE );
  }
  #-----------------------------------------------------

  function children_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_children(), 
                                          $section_heading = 'Children:', 
                                          $decode_display = 'child',
                                          $subsection_name = 'children_subsection',
                                          $last_subsection = FALSE,
                                          $include_date_fields = FALSE );
  }
  #-----------------------------------------------------

  function employers_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_employers(), 
                                          $section_heading = 'Employers:', 
                                          $decode_display = 'employer',
                                          $subsection_name = 'employers_subsection' );
  }
  #-----------------------------------------------------

  function employees_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_employees(), 
                                          $section_heading = 'Employees:', 
                                          $decode_display = 'employee',
                                          $subsection_name = 'employees_subsection' );
  }
  #-----------------------------------------------------

  function patrons_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_patrons(), 
                                          $section_heading = 'Patrons:', 
                                          $decode_display = 'patron',
                                          $subsection_name = 'patrons_subsection' );
  }
  #-----------------------------------------------------

  function protegees_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_protegees(), 
                                          $section_heading = 'Prot&eacuteg&eacute;s:', 
                                          $decode_display = 'protege',
                                          $subsection_name = 'protegees_subsection' );
  }
  #-----------------------------------------------------

  function teachers_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_teachers(), 
                                          $section_heading = 'Teachers:', 
                                          $decode_display = 'teacher',
                                          $subsection_name = 'teachers_subsection' );
  }
  #-----------------------------------------------------

  function students_entry_field() {
                               
    $this->person_to_person_subsection(   $fieldset_name = $this->fieldset_name_for_students(), 
                                          $section_heading = 'Students:', 
                                          $decode_display = 'student',
                                          $subsection_name = 'students_subsection' );
  }
  #-----------------------------------------------------

  function offer_person_deletion_form() {

    if( ! $this->person_id ) return;
    if( ! $this->iperson_id ) return;
    if( $this->menu_called_as_popup ) return;  # don't offer deletion in a popup window, just gets too messy

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::form_start( 'person', 'confirm_person_deletion' );
    HTML::hidden_field( 'person_id', $this->person_id );
    HTML::hidden_field( 'iperson_id', $this->iperson_id );

    echo 'Delete this person/organisation? ';
    HTML::submit_button( 'delete_button', 'Delete' );

    HTML::italic_start();
    echo ' (Confirmation will be requested before the deletion takes place.)';
    HTML::italic_end();
    HTML::form_end();
  }
  #-----------------------------------------------------

  function confirm_person_deletion() {

    $invalid_input_msg = 'Invalid input to Confirm Person Deletion.';

    $iperson_id = $this->read_post_parm( 'iperson_id' );
    if( ! $iperson_id ) $this->die_on_error( $invalid_input_msg );

    $this->set_person( $iperson_id );
    if( ! $this->iperson_id ) $this->die_on_error( $invalid_input_msg );

    if( $this->person_id != $this->read_post_parm( 'person_id' )) $this->die_on_error( $invalid_input_msg );

    HTML::form_start( 'person', 'delete_person' );
    HTML::hidden_field( 'person_id', $this->person_id );
    HTML::hidden_field( 'iperson_id', $this->iperson_id );

    HTML::h3_start();
    echo "Person or organisation to be deleted:";
    HTML::h3_end();
    echo $this->foaf_name;

    if( $this->skos_altlabel ) {
      echo LINEBREAK;
      $this->echo_safely_with_linebreaks( $this->skos_altlabel );
    }

    if( $this->person_aliases ) {
      echo LINEBREAK;
      $this->echo_safely_with_linebreaks( $this->person_aliases );
    }

    HTML::new_paragraph();

    HTML::div_start( 'class="warning"' );
    HTML::h3_start();
    echo "To delete, first tick the 'Confirm Deletion' checkbox then click the 'Delete' button.";
    HTML::h3_end();
    HTML::new_paragraph();

    HTML::checkbox( $fieldname = 'confirm_deletion',
                    $label = 'Tick to confirm deletion of this person/organisation',
                    $is_checked = FALSE,
                    $value_when_checked = 1 );
    HTML::new_paragraph();
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::submit_button( 'delete_button', 'Delete' );

    HTML::div_end();
    HTML::form_end();
  }
  #-----------------------------------------------------

  function delete_person() {

    $invalid_input_msg = 'Invalid input to Delete Person.';

    $iperson_id = $this->read_post_parm( 'iperson_id' );
    if( ! $iperson_id ) $this->die_on_error( $invalid_input_msg );

    $this->set_person( $iperson_id );
    if( ! $this->iperson_id ) $this->die_on_error( $invalid_input_msg );

    if( $this->person_id != $this->read_post_parm( 'person_id' )) $this->die_on_error( $invalid_input_msg );

    $proceed_with_deletion = TRUE;
    if( $this->parm_found_in_post( 'cancel_button' )) {
       $proceed_with_deletion = FALSE;
       $msg = 'Deletion cancelled at user request.';
    }
    elseif( ! $this->parm_found_in_post( 'confirm_deletion' )) {
       $proceed_with_deletion = FALSE;
       $msg = "Deletion confirmation checkbox was not ticked.";
    }

    if( ! $proceed_with_deletion ) {
      HTML::new_paragraph();
      HTML::div_start( 'class = "warning"' );
      $this->echo_safely( "Person/organisation '$this->foaf_name' (ID $this->iperson_id) was NOT deleted." );
      echo LINEBREAK . $msg . LINEBREAK;
      HTML::div_end();
      HTML::new_paragraph();
      $this->edit_person();
      return;
    }

    #------------------------------
    # Actually do the deletion here
    #------------------------------
    $this->echo_safely( "Deleting person/organisation '$this->foaf_name' (ID $this->iperson_id) ..." );

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    $reltable = $this->proj_relationship_tablename();
    $person_table = $this->proj_person_tablename();

    $statement = 'select relationship_id, left_table_name as other_table, left_id_value as other_id '
               . " from $reltable"
               . " where right_table_name = '$person_table' and right_id_value = '$this->person_id'"
               . ' union '
               . 'select relationship_id, right_table_name as other_table, right_id_value as other_id '
               . " from $reltable"
               . " where left_table_name = '$person_table' and left_id_value = '$this->person_id'"
               . ' order by relationship_id';

    $rels = $this->db_select_into_array( $statement );

    foreach( $rels as $row ) {
      extract( $row, EXTR_OVERWRITE );

      # Delete relationship
      $statement = "delete from $reltable where relationship_id = $relationship_id";
      $this->db_run_query( $statement );
      
      # Delete comments and related resources so as not to leave 'orphaned' data
      switch( $other_table ) {
        case $this->proj_comment_tablename():
        case $this->proj_resource_tablename():
          $id_column = $this->proj_primary_key( $other_table );
          $statement = "delete from $other_table where $id_column = $other_id";
          $this->db_run_query( $statement );
          break;

        default:
          break;
      }
    }

    # Now delete the actual person
    $statement = "delete from $person_table where person_id = '$this->person_id'";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    HTML::div_start( 'class="warning"' );
    $this->echo_safely( "Person '$this->foaf_name' (ID $this->iperson_id) has been deleted." );
    HTML::div_end();
    HTML::new_paragraph();
    echo 'Returning to search...';
    HTML::new_paragraph();

    $this->write_post_parm( 'person_id', NULL );
    $this->write_post_parm( 'iperson_id', NULL ); # so that these do not appear in the search form

    $this->db_search();
  }
  #-----------------------------------------------------

  function db_query_on_date_val( &$criteria_desc,  # by reference
                                 $column_name,
                                 $column_value,
                                 $column_name2,
                                 $column_value2,
                                 $column_label,
                                 $op ) {

    $range_query = TRUE;

    if( $op == 'is_blank' || $op == 'is_not_blank' || $column_value . $column_value2 == '' )
      $range_query = FALSE;

    if( $range_query ) {
      switch( $column_name ) {

        case 'date_of_birth':
        case 'date_of_birth2':
        case 'date_of_death':
        case 'date_of_death2':
        case 'flourished':
        case 'flourished2':

        case 'date_of_birth_ce':
        case 'date_of_birth_ce2':
        case 'date_of_death_ce':
        case 'date_of_death_ce2':
        case 'flourished_ce':
        case 'flourished_ce2':
          break;

        default:
          $range_query = FALSE;
      }
    }

    if( ! $range_query ) {
      return parent::db_query_on_date_val( $criteria_desc, $column_name, $column_value, $column_name2, $column_value2,
                                           $column_label, $op );
    }

    $range_start_column = $column_name . '_from';
    $range_end_column = $column_name . '_to';

    $where1 = '';
    $where2 = '';


    #-------------------------------------------------------------------------------------------
    # E.g. we want people born 1715-1718 (I'll call it the "query range")
    # Sample person was born 1713-1716 (I'll call it the "birth range")
    # START of birth range must be less than or equal to END of query range i.e. 1713 <= 1718
    # END of birth range must be greater than or equal to START of query range i.e. 1716 >= 1715
    #-------------------------------------------------------------------------------------------
    if( $column_value ) {
      $where2 = parent::db_query_on_date_val( $criteria_desc, 
                                              'coalesce( ' . $range_end_column . ', ' . $range_start_column . ' )', 
                                              $column_value, 
                                              NULL, 
                                              NULL,
                                              $column_label, 
                                              $op );
    }

    if( $column_value2 ) {
      $where1 = parent::db_query_on_date_val( $criteria_desc, 
                                              'coalesce( ' . $range_start_column . ', ' . $range_end_column . ' )', 
                                              NULL, 
                                              NULL, 
                                              $column_value2,
                                              $column_label, 
                                              $op );
    }

    $where_clause_section = trim( $where1 . ' ' . $where2 );

    return $where_clause_section;
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name = NULL ) {

    switch( $column_name ) {

      case 'iperson_id':
        return 'Person or Group ID';

      case 'foaf_name':
        return 'Primary name';

      case 'org_type':
      case 'organisation_type':
        return 'Type of group';

      case 'person_aliases':
      case 'professions_or_titles':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Professions / titles';
        else
          return 'Titles/roles';
 
      case 'skos_altlabel':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Alternative names';
        else
          return 'Synonyms';

      case 'editors_notes':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Bibliographic references and other notes';
        else
          return "Editors' notes";

      case 'author_of':
        return 'No. of works written';

      case 'works_written':
        return 'Details of works written';

      case 'names_and_titles':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Names';
        else {
          if( $this->csv_output )
            return 'Names/titles/roles';
          else
            return 'Names and titles/roles';
        }

      case 'other_details_summary':
      case 'other_details_summary_searchable':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Associated people, places and resources';
        else
          return 'Other details';

      case 'date_of_birth_ce':
      case 'date_of_death_ce':
      case 'flourished_ce':
        $label = parent::db_get_default_column_label( $column_name );
        return str_replace( ' ce', ' (CE)', $label );

      case 'date_of_birth_in_orig_calendar':
      case 'date_of_death_in_orig_calendar':
      case 'flourished_in_orig_calendar':
        $label = parent::db_get_default_column_label( $column_name );
        $label = str_replace( ' orig ', ' original ', $label );
        if( $column_name == 'flourished_in_orig_calendar' ) {
          $label = str_replace( 'Flourished', 'Dates when flourished,', $label );
        }
        return $label;

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function person_nisbas_field() {

    $nisba_obj = new Nisba( $this->db_connection );
    $nisba_obj->nisba_entry_fields( $this->person_id );
  }
  #-----------------------------------------------------

  function person_nationalities_field() {

    $nationality_obj = new Nationality( $this->db_connection );
    $nationality_obj->nationality_entry_fields( $this->person_id );
  }
  #-----------------------------------------------------

  function person_role_categories_field() {

    $role_category_obj = new Role_Category( $this->db_connection );
    $role_category_obj->role_category_entry_fields( $this->person_id );
  }
  #-----------------------------------------------------

  function save_nisbas( $person_id = NULL ) {

    if( ! $person_id ) $person_id = $this->person_id;

    $nisba_obj = new Nisba( $this->db_connection );
    $nisba_obj->save_nisbas( $person_id );
  }
  #-----------------------------------------------------

  function save_nationalities( $person_id = NULL ) {

    if( ! $person_id ) $person_id = $this->person_id;

    $nationality_obj = new nationality( $this->db_connection );
    $nationality_obj->save_nationalities( $person_id );
  }
  #-----------------------------------------------------

  function save_role_categories( $person_id = NULL ) {

    if( ! $person_id ) $person_id = $this->person_id;

    $role_category_obj = new Role_Category( $this->db_connection );
    $role_category_obj->save_role_categories( $person_id );
  }
  #-----------------------------------------------------

  function save_org_subtypes( $person_id = NULL ) {

    if( ! $person_id ) $person_id = $this->person_id;

    $org_subtype_obj = new Org_Subtype( $this->db_connection );
    $org_subtype_obj->save_org_subtypes( $person_id );
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'iperson_id':

      case 'date_of_birth_year':
      case 'date_of_birth_month':
      case 'date_of_birth_day':
      case 'date_of_birth2_year':
      case 'date_of_birth2_month':
      case 'date_of_birth2_day':

      case 'date_of_death_year':
      case 'date_of_death_month':
      case 'date_of_death_day':
      case 'date_of_death2_year':
      case 'date_of_death2_month':
      case 'date_of_death2_day':

      case 'flourished_year':
      case 'flourished_month':
      case 'flourished_day':
      case 'flourished2_year':
      case 'flourished2_month':
      case 'flourished2_day':

      case 'sent':
      case 'recd':
      case 'mentioned':
      case 'all_works':
      case 'author_of':

      case 'organisation_type':

        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'foaf_name':
      case 'skos_altlabel':
      case 'person_aliases':
      case 'names_and_titles':
      case 'date_of_birth_as_marked':
      case 'date_of_death_as_marked':
      case 'editors_notes':
      case 'further_reading':
      case 'other_details_summary':
      case 'other_details_summary_searchable':
      case 'affiliations':
      case 'professions_or_titles':
      case 'works_written':
      case 'associated_works':
      case 'associated_manifestations':
      case 'org_type':
      case 'date_of_birth_in_orig_calendar':
      case 'date_of_death_in_orig_calendar':
      case 'flourished_in_orig_calendar':
      case 'nisba_and_nationality':
      case 'images':

        return $this->is_ok_free_text( $this->parm_value );


      case 'date_of_birth': 
      case 'date_of_birth2':
      case 'date_of_birth_ce': 
      case 'date_of_birth_ce2':
      case 'date_of_death':
      case 'date_of_death2':
      case 'date_of_death_ce':
      case 'date_of_death_ce2':
      case 'flourished':
      case 'flourished2':
      case 'flourished_ce':
      case 'flourished_ce2':

      case 'date_of_birth_start_ce': 
      case 'date_of_birth_end_ce':
      case 'date_of_death_start_ce':
      case 'date_of_death_end_ce':
      case 'flourished_start_ce':
      case 'flourished_end_ce':

        if( $this->menu_method_name == 'save_person' )
          return $this->date_entity->is_postgres_timestamp( $this->parm_value );

        $this->parm_value = $this->date_entity->yyyy_to_dd_mm_yyyy( $parm_name, $this->parm_value );
        return $this->is_dd_mm_yyyy( $this->parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE );


      case 'merge':
      case 'sent_or_received_works':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_html_id( $this->parm_value );

      case 'person_id':
      case 'selected_merge_id':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      case 'date_of_birth_approx':
      case 'date_of_birth_uncertain':
      case 'date_of_birth_inferred':
      case 'date_of_death_approx':
      case 'date_of_death_uncertain':
      case 'date_of_death_inferred':

        case 'flourished_inferred':
        case 'flourished_uncertain':
        case 'flourished_approx':

      case 'date_of_birth_is_range':
      case 'date_of_death_is_range':
      case 'flourished_is_range':
        return $this->is_on_off_switch( $this->parm_value );

      case 'is_organisation':
        return $this->is_alphanumeric_or_blank( $this->parm_value );

      case 'gender':
      case 'date_of_birth_calendar':
      case 'date_of_death_calendar':
      case 'flourished_calendar':
        return $this->is_alphabetic_or_blank( $this->parm_value );

      case $this->proj_new_id_fieldname_from_fieldset_name( $this->fieldset_name_for_place_of_birth() ):
      case $this->proj_new_id_fieldname_from_fieldset_name( $this->fieldset_name_for_place_of_death() ):
        return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
