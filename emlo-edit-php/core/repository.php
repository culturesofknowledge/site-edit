<?php
/*
 * PHP class for handling manuscript repositories (i.e. libraries etc)
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'FLD_SIZE_INSTITUTION_NAME', 60 );

define( 'TEXTAREA_COLS_REPOS_ALTERNATIVE', 57 );
define( 'TEXTAREA_ROWS_REPOS_ALTERNATIVE', 2 );

define( 'REPOS_ALT_NAME_SEPARATOR_INTERNAL', '|' );
define( 'REPOS_ALT_NAME_SEPARATOR_DISPLAYED',  "\n" );

require_once 'lookup_table.php';

class Repository extends Project {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Repository( &$db_connection ) { 

    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $db_connection );

    $this->init_lookup_obj();
  }
  #-----------------------------------------------------

  function init_lookup_obj( $datasource = 'view' ) {

    if( $datasource == 'table' )
      $lookup_table_name = $this->proj_institution_tablename(); 
    else
      $lookup_table_name = $this->proj_institution_viewname(); 

    $this->lookup_obj = new Lookup_Table( $this->db_connection, 
                                          $lookup_table_name, 
                                          $id_column_name    = 'institution_id', 
                                          $desc_column_name  = 'institution_name' ); 
  }
  #-----------------------------------------------------

  function clear() {
    parent::clear();
    $this->init_lookup_obj();
  }
  #-----------------------------------------------------
  function get_lookup_desc( $institution_id ) {
    $repository_name = $this->lookup_obj->get_lookup_desc( $institution_id );
    return $repository_name;
  }
  #-----------------------------------------------------
  function lookup_table_dropdown( $field_name, $field_label, $selected_id = NULL ) {
    $this->lookup_obj->lookup_table_dropdown( $field_name, $field_label, $selected_id ); 
  }
  #-----------------------------------------------------

  function desc_dropdown( $form_name, $field_name = NULL, $copy_field = NULL, $field_label = NULL,
                          $in_table=FALSE, $override_blank_row_descrip = NULL ) {

    #----------------------------------------------------------------------------------
    # Use table instead of view here, because manifestations summary in queryable work
    # does not contain the country/city name at present (08 Dec 2010).
    # If we change the summary, we can change 'table' to 'view'.
    #----------------------------------------------------------------------------------
    $this->init_lookup_obj( 'table' );

    $this->lookup_obj->desc_dropdown( $form_name, $field_name, $copy_field, $field_label,
                                      $in_table, $override_blank_row_descrip );

    $this->init_lookup_obj();  # revert to view
  }
  #-----------------------------------------------------
  #-----------------------------------------------------
  #-----------  End of lookup obj methods --------------
  #-----------------------------------------------------

  function set_repository( $institution_id = NULL ) {

    $this->clear();

    if( ! $institution_id ) $institution_id = $this->read_post_parm( 'institution_id' );
    if( ! $institution_id ) $institution_id = $this->read_get_parm( 'institution_id' );
    if( ! $institution_id ) return NULL;

    $statement = 'select * from ' . $this->proj_institution_tablename() . " where institution_id = $institution_id";
    $this->db_select_into_properties( $statement );

    return $this->institution_id;
  }
  #-----------------------------------------------------

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
    $this->keycol         = 'institution_id';
    $this->from_table     = $this->proj_institution_query_viewname();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'institution_name' );

    $this->force_printing_across_page = TRUE;

    #$this->edit_method= 'edit_repository';  -- We can't use the normal method for editing via POST form
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

    $dummy_col_for_merge = $this->get_dummy_col_for_merge(); # use this column as a place to put the Merge checkbox

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
      if( $column_name == 'creation_user' ) $skip_it = TRUE;

      if( $column_name == $dummy_col_for_merge ) {

        $enable_merge = FALSE;
        if( ! $this->csv_output && ! $this->printable_output && ! $this->getting_order_by_cols ) {
          # Will eventually need to check that user has edit role here
          if( $this->proj_edit_mode_enabled() )
            $enable_merge = TRUE;
        }

        if( $enable_merge )
          $row[ 'searchable' ] = FALSE;  # we will use this column as a place to put the 'Merge' checkbox
        else
          $skip_it = TRUE;
      }

      # Some columns are queryable but not displayed
      if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {
          case 'change_user':
          case 'uuid' :
            $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      if( $skip_it ) continue;

      #------------------
      # Set column labels
      #------------------
      $column_label = $this->db_get_default_column_label( $column_name );

      switch( $column_name ) {

        case 'institution_id':
          $search_help_text = 'The unique ID for the record within this database.';
          break;

        case 'related_resources':
          $search_help_text = 'E.g. links to online catalogues.';
          break;

        case $dummy_col_for_merge:
          $column_label = 'Merge';
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
      if( $this->entering_selection_criteria && $column_name == 'institution_id' ) # put at end of list
        $id_row = $row;
      else
        $columns[] = $row;
    }

    if( $this->entering_selection_criteria ) # put ID column at end of list
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

  function db_browse_reformat_data( $column_name, $column_value ) {

    $dummy_col_for_merge = $this->get_dummy_col_for_merge(); # use this column as a place to put the Merge checkbox

    switch( $column_name ) {

      case 'institution_synonyms':
      case 'institution_city_synonyms':
      case 'institution_country_synonyms':
         $column_value = str_replace( REPOS_ALT_NAME_SEPARATOR_INTERNAL, 
                                      REPOS_ALT_NAME_SEPARATOR_DISPLAYED, 
                                      $column_value );
         if( $this->string_contains_substring( $column_value, REPOS_ALT_NAME_SEPARATOR_DISPLAYED )) {
           $elements = explode( REPOS_ALT_NAME_SEPARATOR_DISPLAYED, $column_value );
           $i = 0;
           foreach( $elements as $element ) {
             if( trim( $element ) > '' ) $i++;
           }
           if( $i > 1 ) {
             $column_value = '<ul>';
             foreach( $elements as $element ) {
               if( trim( $element ) > '' ) {
                 $column_value .= '<li>' . $element . '</li>';
               }
             }
             $column_value .= '</ul>';
           }
         }
         return $column_value;

      case $dummy_col_for_merge:
        if( ! $this->printable_output && ! $this->csv_output ) {

          $id = $this->current_row_of_data[ 'institution_id' ];

          HTML::checkbox( $fieldname = 'merge[]',
                          $label = NULL,
                          $is_checked = FALSE,
                          $value_when_checked = $id,
                          $in_table = FALSE,
                          $tabindex = 1,
                          $input_instance = $id );
        }
        return '';

      case 'related_resources':
        return $this->proj_convert_non_html_list( $column_value );

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function db_browse_with_merge( $details, $columns, $browse_function = 'db_browse_across_page' ) {

    $enable_merge = FALSE;
    if( ! $this->csv_output && ! $this->printable_output ) {
      # Will eventually need to check that user has edit role here
      if( $this->proj_edit_mode_enabled() )
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

  function get_dummy_col_for_merge() {
    return 'creation_timestamp'; # use it as a place to put the Merge checkbox
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

    $script = 'function ' . $script_name . '() {' . NEWLINE;

    foreach( $search_results as $row ) {
      $id = $row[ "$keycol" ];
      $script .= '  document.' . $this->merge_form . '.merge' . $id . ".checked=$checked;" . NEWLINE;
    }

    $script .= '}' . NEWLINE;
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
    HTML::column_header( 'No. of manifestations' );
    HTML::tablerow_end();

    $i = 0;
    foreach( $to_merge as $id_to_merge ) {
      HTML::tablerow_start();
      $i++;

      #----

      HTML::tabledata_start();

      $institution_name = $this->proj_get_description_from_id( $id_to_merge );

      HTML::radio_button( $fieldname = 'selected_merge_id', 
                          $institution_name, 
                          $value_when_checked = $id_to_merge, 
                          $current_value = '', 
                          $tabindex=1, 
                          $button_instance=$i );

      HTML::tabledata_end();

      #----

      HTML::tabledata_start();

      $stored_manifs = $this->get_manifs_in_repos( $id_to_merge );
      $manif_count = count($stored_manifs);
      echo $manif_count;
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

  function confirm_merge() {

    if( $this->cancel_merge_at_user_request()) return;

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

    $this->retain_options_during_merge(); # retain 'Advanced Search', rows per page, etc.

    HTML::hidden_field( 'selected_merge_id', $selected_merge_id );

    HTML::h3_start();
    echo $this->get_repository_desc_from_id( $selected_merge_id );
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
      echo " $i. " . $this->get_repository_desc_from_id( $id_to_merge );
      HTML::new_paragraph();
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
    # Finally, delete the repository ID from the repository table.
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
    echo $this->get_repository_desc_from_id( $selected_merge_id );
    HTML::new_paragraph();

    HTML::horizontal_rule();
    HTML::h3_start();
    echo 'Search again:';
    HTML::h3_end();

    $this->db_set_search_result_parms();
    $search_method = $this->search_method;
    $this->$search_method();

    $script = "document.$this->form_name.institution_name.focus()";
    HTML::write_javascript_function( $script );
  }
  #-----------------------------------------------------
  function perform_merge( $selected_merge_id, $to_merge ) {

    $manif_obj = new Manifestation( $this->db_connection );

    $table_name = $this->proj_institution_tablename();

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
      # Remember original name and works sent/received by repository to be merged/deleted
      #------------------------------------------------------------------------------
      $merged_desc[] = $this->get_repository_desc_from_id( $id_to_merge );

      #------------------------------------------------------------------------------------------
      # Get a list of manifestation IDs in this repository, as the queryable work record relating
      # to these manifestations will not automatically be refreshed, you need to do it manually.
      #------------------------------------------------------------------------------------------
      $stored_manifs = $this->get_manifs_in_repos( $id_to_merge );
      $manif_count = count( $stored_manifs );
      echo $manif_count . ' manifestations will be affected by this change...' . LINEBREAK;

      #-------------------------------------------------------------------------------------
      # Change all references to original repository in 'relationships' to the new master record
      #-------------------------------------------------------------------------------------
      $this->rel_obj->change_id_value( $this->proj_institution_tablename(), $id_to_merge, $selected_merge_id,
                                       $display_status_msg = TRUE );
      #--------------------------
      # Update the queryable work
      #--------------------------
      $manif_obj->refresh_work_for_list_of_manifs( $stored_manifs );

      #---------------------------
      # Delete the original repository
      #---------------------------
      $this->perform_delete_repository( $id_to_merge, $display_status_msg = TRUE );
    }

    return $merged_desc;
  }
  #-----------------------------------------------------

  function get_manifs_in_repos( $institution_id ) {

    $stored_manifs = array();

    $manif_rels = $this->get_lefthand_values( $institution_id, RELTYPE_MANIF_STORED_IN_REPOS, 
                                              $this->proj_manifestation_tablename()) ;
    if( count( $manif_rels ) > 0 ) {
      foreach( $manif_rels as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $manif = $other_id_value;
        $stored_manifs[] = $manif;
      }
    }

    $row = NULL;
    $manif_rels = NULL;

    return $stored_manifs;
  }
  #-----------------------------------------------------

  function get_righthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->institution_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_institution_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'left' );
  }
  #----------------------------------------------------------------------------------

  function get_lefthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->institution_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_institution_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'right' );
  }
  #----------------------------------------------------------------------------------

  function get_both_sides( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->institution_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_institution_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'both' );
  }
  #----------------------------------------------------------------------------------

  function proj_get_description_from_id( $entity_id, $using_integer_id = FALSE ) {  # method from Project

    return $this->get_repository_desc_from_id( $entity_id, $using_integer_id );
  }
  #----------------------------------------------------------------------------------

  function get_repository_desc_from_id( $institution_id ) {

    if( ! $institution_id ) return NULL;

    $statement = 'select * from ' . $this->proj_institution_tablename()
               . " where institution_id = $institution_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) return NULL;

    $this->current_row_of_data = $result[0];
    return $this->get_repository_desc_from_current_row_of_data();
  }
  #-----------------------------------------------------

  function get_repository_desc_from_current_row_of_data() {  # DB search results will set $this->current_row_of_data

    if( $this->current_row_of_data ) { 
      extract( $this->current_row_of_data, EXTR_OVERWRITE );

      $repository_desc = '';

      if( $institution_country ) 
        $repository_desc .= $institution_country . ', ';

      if( $institution_city ) 
        $repository_desc .= $institution_city . ', ';

      if( $institution_name ) 
        $repository_desc .= $institution_name;
    }
    else
      $repository_desc = NULL;

    return $repository_desc;
  }
  #-----------------------------------------------------

  function get_repository_desc_for_credits( $institution_id ) {  # this has library name first instead of country

    if( ! $institution_id ) return NULL;

    $statement = 'select * from ' . $this->proj_institution_tablename()
               . " where institution_id = $institution_id";

    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) return NULL;
    extract( $result[0], EXTR_OVERWRITE );

    $repository_desc = trim( $institution_name );
    if( ! $repository_desc ) return $repository_desc;

    if( $institution_city ) {
      $repository_desc .= ', ' . trim( $institution_city );
    }

    if( $institution_country ) {
      $repository_desc .= ', ' . trim( $institution_country );
    }

    return $repository_desc;
  }
  #-----------------------------------------------------

  function perform_delete_repository( $institution_id, $display_status_msg = FALSE ) {

    if( ! $institution_id ) return;

    if( $display_status_msg ) {
      $repository_desc = $this->get_repository_desc_from_id( $institution_id );
    }

    $statement = 'delete from ' . $this->proj_institution_tablename()
               . " where institution_id = '$institution_id' ";
    $this->db_run_query( $statement );

    if( $display_status_msg ) {
      echo 'Deleted ' . $repository_desc;
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      $anchor_name = 'deleted_institution_id_' . $institution_id;
      HTML::anchor( $anchor_name );
      $script = 'window.location.href = "' . $_SERVER['PHP_SELF'] . '" + "#' . $anchor_name . '"';
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'Enter some details of the required repository (e.g. country, city or part or all of institution name)'
         . ' and click Search or press Return.';
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function one_repository_search_results() {  # Really just a dummy option to allow Edit repository to be called via GET.

    if( $this->proj_edit_mode_enabled() )
      $this->edit_repository();

    else {
      HTML::italic_start();
      echo 'Sorry, you do not have edit privileges in this database. Read-only details are now being displayed.';
      HTML::italic_end();
      HTML::new_paragraph();

      $this->write_post_parm( 'institution_id', $this->read_get_parm( 'institution_id' ));
      $this->write_post_parm( 'date_or_numeric_query_op_institution_id', 'equals' );
      $this->write_post_parm( 'record_layout', 'down_page' );

      $this->db_search_results();
    }
  }
  #-----------------------------------------------------

  function add_repository() {
    $this->edit_repository( $new_record = TRUE );
  }
  #-----------------------------------------------------

  function db_browse_plugin_2( $column_name = NULL, $column_value = NULL ) {

    if( $column_name != 'institution_id' ) return;
    if( ! $column_value ) return;
    if( $this->printable_output || $this->csv_output ) return;
    if( ! $this->proj_edit_mode_enabled() ) return;
    echo LINEBREAK;

    $title = 'Edit repository details';

    $href = $_SERVER[ 'PHP_SELF' ] . '?class_name=' . $this->app_get_class( $this ) . '&method_name=one_repository_search_results';
 
    $href .= '&' . $column_name . '=' . $column_value;
 
    $href .= '&opening_method=' . $this->menu_method_name;

    HTML::link_start( $href, $title, $target = '_blank' );
    echo 'Edit';
    HTML::link_end();
  }
  #-----------------------------------------------------

  function edit_repository( $new_record = FALSE, $just_saved = FALSE ) {

    if( ! $new_record ) {
      $institution_id = $this->read_post_parm( 'institution_id' );
      $opening_method = $this->read_post_parm( 'opening_method' );

      if( ! $institution_id ) {
        $institution_id = $this->read_get_parm( 'institution_id' );
        $opening_method = $this->read_get_parm( 'opening_method' );
      }
      $keep_failed_validation = $this->failed_validation;

      $found = $this->set_repository( $institution_id );
      if( ! $found ) $this->die_on_error( 'Invalid repository ID' );

      $this->failed_validation = $keep_failed_validation;
    }

    if( $this->failed_validation ) {
      echo LINEBREAK . 'Your data could not be saved. Please correct invalid details and try again.' . LINEBREAK;
    }

    $this->repository_entry_stylesheets();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_repository' );

    HTML::hidden_field( 'opening_method', $opening_method );
    $focus_script = NULL;

    if( ! $new_record ) {
      HTML::italic_start();
      echo 'Repository ID ' . $this->institution_id 
           . '. Last changed ' . $this->postgres_date_to_words( $this->change_timestamp )
           . ' by ' . $this->change_user . ' ';
      HTML::italic_end();

      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $opening_method );
      HTML::new_paragraph();

      HTML::submit_button( 'save_button', 'Save' );
      HTML::submit_button( 'clear_search_button', 'Search' );
      HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );

      HTML::new_paragraph();
      HTML::horizontal_rule();

      HTML::hidden_field( 'institution_id', $institution_id );
    }

    if( $this->failed_validation ) {  # read values from POST and re-display
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_institution_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }
    }

    $this->repository_entry_fields();

    HTML::form_end();

    if( $focus_script ) HTML::write_javascript_function( $focus_script );

    # The above focus script will put you on the 'Refresh' button.
    # However, may want to go to a different section of the form if they pressed 'Save and Continue'.

    $anchor_script = $this->proj_get_anchor_script_after_save();
    if( $anchor_script )
      HTML::write_javascript_function( $anchor_script );
  }
  #-----------------------------------------------------

  function repository_entry_stylesheets() {

    $this->write_work_entry_stylesheet();  # method from Project
  }
  #-----------------------------------------------------

  function related_resources_field() {

    HTML::new_paragraph();
    HTML::horizontal_rule();
    $this->proj_form_section_links( 'resources' );
    HTML::new_paragraph();

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else
      $this->resource_obj->clear();

    $resources = $this->get_rels_for_resources();

    $this->resource_obj->edit_resources( $resources, $this->proj_institution_tablename() );
  }
  #-----------------------------------------------------

  function get_rels_for_resources() {

    $statement = 'select * from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '" . $this->proj_institution_tablename() . "'"
               . " and left_id_value = '$this->institution_id' "
               . " and relationship_type = '" . RELTYPE_ENTITY_HAS_RESOURCE . "'"
               . " and right_table_name = '" . $this->proj_resource_tablename() . "'"
               . " order by relationship_id";
    
    $resources = $this->db_select_into_array( $statement );
    return $resources;
  }
  #----------------------------------------------------------------------------------

  function save_resources() {

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else 
      $this->resource_obj->clear();

    $this->resource_obj->save_resources( $this->proj_institution_tablename(), 
                                         $this->institution_id );
  }
  #-----------------------------------------------------

  function save_images() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->image_obj->save_image_details( $entity_id = $this->institution_id, $entity_type = 'institution' );
  }
  #-----------------------------------------------------

  function repository_entry_fields() {

    $this->institution_name_field();

    $this->institution_synonyms_field();
    #----

    $this->city_field();

    $this->city_synonyms_field();

    #----

    $this->country_field();

    $this->country_synonyms_field();
    HTML::new_paragraph();

    #----

    if( $this->institution_id ) $this->related_resources_field(); 

    #----

    if( $this->institution_id ) {
      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        HTML::horizontal_rule();
        $this->proj_form_section_links( 'imgs' );
        $this->images_field();
      }
    }
  }
  #-----------------------------------------------------

  function editors_notes_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->proj_textarea( 'editors_notes', TEXTAREA_ROWS_REPOS_ALTERNATIVE, TEXTAREA_COLS_REPOS_ALTERNATIVE,
                          $value = $this->editors_notes, $label = "Editors' notes" );
    echo LINEBREAK;

    HTML::italic_start();
    HTML::span_start( 'class="workfieldaligned"' );
    echo "Notes for internal use.";
    HTML::span_end();
    HTML::italic_end();

    HTML::div_end();

    $this->proj_publication_popups( $calling_field = 'editors_notes' );
    HTML::new_paragraph();

  }
  #-----------------------------------------------------

  function images_field() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->write_post_parm( 'institution_id', $this->institution_id ); # so it can be picked up by the image object
    $this->image_obj->image_entry_for_entity( $this->institution_id, $entity_type = 'institution' );
  }
  #-----------------------------------------------------

  function save_repository() {

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

    $institution_id = $this->read_post_parm( 'institution_id' );

    if( $institution_id && $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      if( $this->parm_found_in_post( 'upload_images_button' )) { # upload images rather than saving
        $found = $this->set_repository( $institution_id );
        if( ! $found ) $this->die_on_error( 'Invalid institution ID' );
        if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
        $this->image_obj->image_upload_form( $entity_type = 'institution' );
        return;
      }
    }


    if( $institution_id ) {
      $this->save_existing_repository( $institution_id );
    }
    else {
      $this->save_new_repository();
    }

    $new_record = FALSE; 
    $just_saved = TRUE;

    if( $this->failed_validation ) {
      $just_saved = FALSE;
      if( ! $institution_id ) $new_record = TRUE;
    }

    $this->edit_repository( $new_record, $just_saved );
  }
  #-----------------------------------------------------

  function save_existing_repository( $institution_id ) {

    if( ! $institution_id ) $institution_id = $this->read_post_parm( 'institution_id' );
    $found = $this->set_repository( $institution_id );
    if( ! $found ) $this->die_on_error( 'Invalid repository/org ID' );

    $old_institution_name = $this->institution_name;

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_institution_tablename());
    $i = 0;
    $statement = 'update ' . $this->proj_institution_tablename() . ' set ';

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'institution_id':
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $i++;
      if( $i > 1 ) $statement .= ', ';

      $column_value = $this->read_post_parm( $column_name );
      $$column_name = $column_value;

      if( $column_name == 'institution_name' ) $this->validate_institution_name( $column_value );

      $statement .= $column_name . ' = ';
      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $statement .= $column_value;
    }

    $statement .= " where institution_id = $this->institution_id";

    if( ! $this->failed_validation ) {
      $this->db_run_query( $statement );

      $this->save_resources();

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        $this->save_images();
      }

      $refresh_manifs = FALSE;
      if( $institution_name != $old_institution_name ) $refresh_manifs = TRUE;

      if( $refresh_manifs ) {
        #------------------------------------------------------------------------------------------
        # Get a list of manifestation IDs in this repository, as the queryable work record relating
        # to these manifestations will not automatically be refreshed, you need to do it manually.
        #------------------------------------------------------------------------------------------
        $stored_manifs = $this->get_manifs_in_repos( $id_to_merge );
        $manif_count = count( $stored_manifs );

        if( $manif_count > 0 ) {
          echo "Updating repository name in $manif_count manifestations..." . LINEBREAK;
          flush();
          $manif_obj = new Manifestation( $this->db_connection );
          $manif_obj->refresh_work_for_list_of_manifs( $stored_manifs );
        }
      }

      HTML::h4_start();
      echo 'Any changes have been saved.';
      HTML::h4_end();
    }
  }
  #-----------------------------------------------------

  function save_new_repository() {

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_institution_tablename()) . "'::regclass )";
    $institution_id = $this->db_select_one_value( $statement );

    $this->write_post_parm( 'institution_id', $institution_id );

    $statement = '';
    $col_statement = '';
    $val_statement = '';

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_institution_tablename());
    $i = 0;
    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
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

      if( $column_name == 'institution_name' ) $this->validate_institution_name( $column_value );

      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $val_statement .= $column_value;
    }

    if( $this->failed_validation ) {
      return;
    }

    $statement = 'insert into ' . $this->proj_institution_tablename() . " ( $col_statement ) values ( $val_statement )";
    $this->db_run_query( $statement );

    HTML::h4_start();
    echo 'New repository has been saved.';
    HTML::h4_end();

    HTML::div_start( 'class="buttonrow"' );

    HTML::form_start( $this->app_get_class( $this ), 'add_repository' );
    echo 'Add another new repository? ';
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

  function prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date ) {

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

  function validate_institution_name( $value ) {

    $value = str_replace( NEWLINE, '', $value );
    $value = str_replace( CARRIAGE_RETURN, '', $value );
    $value = trim( $value );

    if( ! $value ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( $parm_name = 'Repository name', $errmsg = 'cannot be blank' );
      return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function institution_name_field() {

    $fieldname = 'institution_name';

    # Write out anchor for use with 'Save and continue' button
    HTML::anchor( $fieldname . '_anchor' );
    $this->extra_anchors[] = $fieldname;

    $this->proj_form_section_links( 'institution' );
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield boldlabel"' );
    HTML::input_field( $fieldname, 'Institution name', $this->institution_name, 
                       FALSE, FLD_SIZE_INSTITUTION_NAME );

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function institution_synonyms_field() {

    $fieldname = 'institution_synonyms';
 
    $label_plural = $this->db_get_default_column_label( $fieldname );
    $label_singular = strtolower( substr( $label_plural, 0, -1 ));

    $this->alternatives_field( $fieldname, 
                               $label_singular, 
                               $label_plural, 
                               $msg  = 'Please use this field for different variations of the institution name,'
                                     . ' e.g. BL for British Library.',
                               $msg2 = 'You can also use this field to translate the name into different languages.' ); 

    # Offer a choice between 'Save and continue' or 'Save and back to top'.
    $this->proj_extra_save_button( $prefix = 'institution_name', $new_paragraph = TRUE );

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function city_field() {

    $fieldname = 'institution_city';

    # Write out anchor for use with 'Save and continue' button
    HTML::anchor( $fieldname . '_anchor' );
    $this->extra_anchors[] = $fieldname;

    $this->proj_form_section_links( 'city' );
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield boldlabel"' );

    HTML::input_field( $fieldname, 'City name', $this->institution_city, 
                       FALSE, FLD_SIZE_INSTITUTION_NAME );

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function city_synonyms_field() {

    $fieldname = 'institution_city_synonyms';
 
    $label_plural = $this->db_get_default_column_label( $fieldname );
    $label_singular = strtolower( substr( $label_plural, 0, -1 ));


    $this->alternatives_field( $fieldname, 
                               $label_singular, 
                               $label_plural, 
                               $msg = 'Please use this field to give the city name in other languages, if required.' );

    # Offer a choice between 'Save and continue' or 'Save and back to top'.
    $this->proj_extra_save_button( $prefix = 'institution_city', $new_paragraph = TRUE );

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function country_field() {

    $fieldname = 'institution_country';

    # Write out anchor for use with 'Save and continue' button
    HTML::anchor( $fieldname . '_anchor' );
    $this->extra_anchors[] = $fieldname;

    $this->proj_form_section_links( 'country' );
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield boldlabel"' );
    HTML::input_field( $fieldname, 'Country name', $this->institution_country, 
                       FALSE, FLD_SIZE_INSTITUTION_NAME );

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function country_synonyms_field() {

    $fieldname = 'institution_country_synonyms';
 
    $label_plural = $this->db_get_default_column_label( $fieldname );
    $label_singular = strtolower( substr( $label_plural, 0, -1 ));


    $this->alternatives_field( $fieldname, 
                               $label_singular, 
                               $label_plural, 
                               $msg = 'Please use this field to give the country name in other languages, if required.',
                               $msg2 = NULL );

    # Offer a choice between 'Save and continue' or 'Save and back to top'.
    $this->proj_extra_save_button( $prefix = 'institution_country', $new_paragraph = TRUE );
  }
  #-----------------------------------------------------

  function alternatives_field( $fieldname, $label_singular, $label_plural, $msg, $msg2 = NULL ) {

    # Replace names separated by vertical bar with one name per line
    $this->$fieldname = str_replace( REPOS_ALT_NAME_SEPARATOR_INTERNAL, NEWLINE, $this->$fieldname );

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
                    $rows = TEXTAREA_ROWS_REPOS_ALTERNATIVE, 
                    $cols = TEXTAREA_COLS_REPOS_ALTERNATIVE, 
                    $value = $this->$fieldname, 
                    $label = $label_plural );

    echo LINEBREAK;
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

    if( $fieldname == 'institution_synonyms' ) {
      $this->editors_notes_field();
      HTML::new_paragraph();
    }
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

  function db_get_default_column_label( $column_name = NULL ) {

    switch( $column_name ) {

      case 'institution_id':
        return 'Repository ID';

      case 'institution_synonyms':
        return 'Alternative institution names';

      case 'institution_city':
        return 'City name';

      case 'institution_city_synonyms':
        return 'Alternative city names';

      case 'institution_country':
        return 'Country name';

      case 'institution_country_synonyms':
        return 'Alternative country names';

      case 'editors_notes':
        return "Editors' notes";

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function proj_list_form_sections() { # some of these sections will not be available when adding new record 

    $form_sections = array( 
      'institution' => 'Institution',
      'city'        => 'City',
      'country'     => 'Country'
    );

    if( $this->institution_id ) {  # links that are only available for existing records
      $form_sections[ 'resources' ] = 'Related resources';
      $form_sections[ 'imgs' ] = 'Images';

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
        $discard = array_pop( $form_sections ); # Impact doesn't have images for repositories
      }
    }

    return $form_sections;
  }
  #-----------------------------------------------------


  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'institution_id':
      case 'latitude' :
      case 'longitude' :
        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'institution_name':
      case 'institution_city':
      case 'institution_country':
      case 'institution_synonyms':
      case 'institution_city_synonyms':
      case 'institution_country_synonyms':
      case 'related_resources':
      case 'editors_notes':
      case 'images':
      case 'address' :

        return $this->is_ok_free_text( $this->parm_value );

      case 'merge':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value );

      case 'institution_id':
      case 'selected_merge_id':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
