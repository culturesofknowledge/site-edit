<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'FLD_SIZE_LOCATION_NAME', 60 );
define( 'LOCATION_SYNONYM_ROWS', 6 );
define( 'PLACE_EDITORS_NOTES_ROWS', 3 );
define( 'PLACE_EDITORS_NOTES_COLS', 60 );

define( 'ROOM_ELEMENT_OF_PLACENAME',     1 );
define( 'BUILDING_ELEMENT_OF_PLACENAME', 2 );
define( 'PARISH_ELEMENT_OF_PLACENAME',   3 );
define( 'CITY_ELEMENT_OF_PLACENAME',     4 );
define( 'COUNTY_ELEMENT_OF_PLACENAME',   5 );
define( 'COUNTRY_ELEMENT_OF_PLACENAME',  6 );
define( 'EMPIRE_ELEMENT_OF_PLACENAME',   7 );

define( 'SMALLEST_ELEMENT_OF_PLACENAME', ROOM_ELEMENT_OF_PLACENAME );
define( 'LARGEST_ELEMENT_OF_PLACENAME',  EMPIRE_ELEMENT_OF_PLACENAME );

define( 'PRIMARY_ELEMENT_OF_PLACENAME',  CITY_ELEMENT_OF_PLACENAME );

class Location extends Project {

  #----------------------------------------------------------------------------------

  function Location( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function set_location( $location_id = NULL ) {

    $this->clear();
    if( ! $location_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_location_tablename()
               . " where location_id = $location_id";
    $this->db_select_into_properties( $statement );

    $this->this_on_left = $this->proj_get_righthand_side_of_rels( $left_table_name = $this->proj_location_tablename(), 
                                                                  $left_id_value = $this->location_id );

    $this->this_on_right = $this->proj_get_lefthand_side_of_rels( $right_table_name = $this->proj_location_tablename(), 
                                                                  $right_id_value = $this->location_id );
    return $this->location_id;
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
    $this->keycol         = 'location_id';
    $this->from_table     = $this->proj_location_viewname();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'location_name' );

    #$this->edit_method= 'edit_location';  -- We can't use the normal method for editing via POST form
    #$this->edit_tab  = '_blank';        -- because the 'Edit' form would be nested within the 'Merge' form
  }
  #-----------------------------------------------------

  function db_search( $table_or_view, $class_name = NULL, $method_name = NULL ) {

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

    $copyrow = $rawcols[ 0 ];
    $enable_merge = FALSE;
    if( ! $this->csv_output && ! $this->printable_output && ! $this->getting_order_by_cols ) {
      # Check that user has edit role. But don't offer merge in a popup window, it's too messy. 
      if( $this->proj_edit_mode_enabled() && ! $this->menu_called_as_popup )
        $enable_merge = TRUE;
    }

    if( $enable_merge ) {
      $copyrow[ 'column_label' ] = 'Merge';  # we will use this column as a place to put the 'Merge' checkbox
      $copyrow[ 'column_name' ] = 'extra';
      $copyrow[ 'searchable' ] = FALSE;
      $copyrow[ 'search_help_text' ] = NULL;
      $copyrow[ 'search_help_class' ] = NULL;
      $copyrow[ 'is_numeric' ] = FALSE;
      $copyrow[ 'is_date' ] = FALSE;

      $columns[] = $copyrow;
    }

    foreach( $rawcols as $row ) {
      $column_label = NULL;
      $search_help_text = NULL;
      $search_help_class = NULL;
      $skip_it = FALSE;
      extract( $row, EXTR_OVERWRITE );

      #------------------------
      # Include or omit columns
      #------------------------
      if( $column_name == 'creation_timestamp' ) $skip_it = TRUE;
      if( $column_name == 'creation_user' ) $skip_it = TRUE;

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX && $column_name == 'element_5_eg_county' ) 
        $skip_it = TRUE;  # they don't want to use county etc in IMPAcT

      # Some columns are queryable but not displayed
      if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {
          case 'change_user':
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
      switch( $column_name ) {

        case 'location_id':
          $column_label = 'Location ID';
          $search_help_text = 'The unique ID for the record within this database.';
          break;

        case 'location_name':
          $column_label = 'Location name(s)';
          $search_help_text = 'This field contains the primary name and any alternative names for a location.';
          break;

        case 'editors_notes':
          $column_label = "Editors' notes";
          $search_help_text = 'Notes for internal use. Not intended for front-end display.';
          break;

        case 'researchers_notes':
          $column_label = "Researchers' notes";
          $search_help_text = 'Comments destined for front-end display.';
          break;

        case 'sent':
          $search_help_text = 'Number of letters sent from this place of origin.';

          if( ! $this->menu_called_as_popup ) # advanced search is not enabled in popup windows
            $search_help_text .= " You can search on these 'number' fields using 'Advanced Search',"
                              .  " e.g. you could enter something like 'Sent greater than 100'"
                              .  ' to identify a place from which many letters were sent, but please note that these will'
                              .  ' be <strong>slower</strong> searches than those on place name or latitude/longitude.';
          break;

        case 'recd':
          $column_label = "Rec'd";
          $search_help_text = 'Number of letters sent to this destination.';
          break;

        case 'works_composed_at_place':
          $column_label = 'Works composed';
          $search_help_text = 'Number of works composed at this location.';
 
          if( ! $this->menu_called_as_popup ) # advanced search is not enabled in popup windows
            $search_help_text .= 'Exact numbers only; to find places where more than x number of works were composed,'
                            . " click 'Advanced Search' above, choose 'greater than' from the dropdown menu"
                            . ' and enter x.';
          break;

        case 'all_works':
          $column_label = "Sent or Rec'd";
          $search_help_text = 'Total number of letters sent to and from this place.';
          break;

        case 'element_1_eg_room':
        case 'element_2_eg_building':
        case 'element_3_eg_parish':
        case 'element_4_eg_city':
        case 'element_5_eg_county':
        case 'element_6_eg_country':
        case 'element_7_eg_empire':
          $column_label = $this->location_element_label( $column_name );
          $element_no = $this->get_element_number_from_name( $column_name );
          $search_help_text = $this->location_element_example( $element_no, $brief = FALSE );
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
      if( $column_name == 'location_id' && $this->get_system_prefix() != IMPACT_SYS_PREFIX ) # put second
        $id_row = $row;
      elseif( $column_name == 'change_timestamp' ) # put at end of list
        $timestamp_row = $row;
      else
        $columns[] = $row;

      if( $id_row && $column_name == 'location_name' && $this->get_system_prefix() != IMPACT_SYS_PREFIX ) # put ID 
                                                                                               # column after name
        $columns[] = $id_row;

    }

    if( $timestamp_row ) # put timestamp column at the very end of the list
      $columns[] = $timestamp_row;

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
      # Will eventually need to check that user has edit role here
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
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
      HTML::column_header( 'Works composed here' );
    else
      HTML::column_header( 'Works sent or received' );
    HTML::tablerow_end();

    $i = 0;
    foreach( $to_merge as $id_to_merge ) {
      HTML::tablerow_start();
      $i++;

      #----

      HTML::tabledata_start();

      $location_name = $this->proj_get_description_from_id( $id_to_merge );

      HTML::radio_button( $fieldname = 'selected_merge_id', 
                          $location_name, 
                          $value_when_checked = $id_to_merge, 
                          $current_value = '', 
                          $tabindex=1, 
                          $button_instance=$i );

      HTML::tabledata_end();

      #----

      HTML::tabledata_start();

      $sent_rels = $this->get_lefthand_values( $id_to_merge, RELTYPE_WORK_SENT_FROM_PLACE, 
                                                $this->proj_work_tablename());
      $sent = count($sent_rels);

      #----

      $recd_rels = $this->get_lefthand_values( $id_to_merge, RELTYPE_WORK_SENT_TO_PLACE, 
                                               $this->proj_work_tablename()) ;
      $received = count($recd_rels);

      #----

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
        echo 'Place of composition of ' . $sent . ' works.';
      else
        echo 'Origin of ' . $sent . ' works, destination of ' . $received . '.';
      if( $sent + $received > 0 ) HTML::new_paragraph();

      $this->show_desc_of_sent_or_received_works( $sent_rels );
      $this->show_desc_of_sent_or_received_works( $recd_rels );
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

  function show_desc_of_sent_or_received_works( $rels ) {

    if( is_array( $rels )) {
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
    echo $this->get_location_desc_from_id( $selected_merge_id );
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
      echo " $i. " . $this->get_location_desc_from_id( $id_to_merge );
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
    # Finally, delete the location ID from the location table.
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
    echo $this->get_location_desc_from_id( $selected_merge_id );
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

      $script = "document.$this->form_name.location_name.focus()";
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------
  function perform_merge( $selected_merge_id, $to_merge ) {

    $table_name = $this->proj_location_tablename();

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
      # Remember original name and works sent/received by location to be merged/deleted
      #------------------------------------------------------------------------------
      $merged_desc[] = $this->get_location_desc_from_id( $id_to_merge );

      #-------------------------------------------------------------------------------------
      # Change all references to original location in 'relationships' to the new master record
      #-------------------------------------------------------------------------------------
      $this->rel_obj->change_id_value( $this->proj_location_tablename(), $id_to_merge, $selected_merge_id,
                                       $display_status_msg = TRUE );

      #---------------------------
      # Delete the original location
      #---------------------------
      $this->perform_delete_location( $id_to_merge, $display_status_msg = TRUE );
    }

    return $merged_desc;
  }
  #-----------------------------------------------------

  function get_righthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->location_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_location_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'left' );
  }
  #----------------------------------------------------------------------------------

  function get_lefthand_values( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->location_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_location_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'right' );
  }
  #----------------------------------------------------------------------------------

  function get_both_sides( $this_id = NULL, $reltype = NULL, $other_table = NULL, $order_col = NULL ) {

    if( ! $this_id ) $this_id = $this->location_id;

    return $this->rel_obj->get_other_side_for_this( $this->proj_location_tablename(), $this_id, $reltype, $other_table,
                                                    $order_col, $this_side = 'both' );
  }
  #----------------------------------------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    $column_value = str_replace( ' ~ ', NEWLINE . '~ ', $column_value );

    switch( $column_name ) {

      case 'extra':
        if( ! $this->printable_output && ! $this->csv_output ) {
          $place = $this->current_row_of_data[ 'location_id' ];
          HTML::checkbox( $fieldname = 'merge[]',
                          $label = NULL,
                          $is_checked = FALSE,
                          $value_when_checked = $place,
                          $in_table = FALSE,
                          $tabindex = 1,
                          $input_instance = $place );
        }
        return '';

      case 'sent':
      case 'recd':
      case 'all_works':
      case 'works_composed_at_place':
        if( ! $this->printable_output && ! $this->csv_output ) {
          if( intval( $column_value ) > 0 ) {

            $location_id = $this->current_row_of_data[ 'location_id' ];
            $view_type = $column_name;

            if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
              $view_type = 'sent'; # awful cludge, but we are using 'sent from' instead of 'composed at'
              $title = 'Display primary sources composed at this location';
            }
            else {
              $title = $column_name;
              if( $title == 'recd' )
                $title = 'addressed to';
              elseif( $title == 'sent' )
                $title = 'from';
              else
                $title = 'to or from';
              $title = 'Display letters ' . $title . ' this location';
            }

            $href = $_SERVER[ 'PHP_SELF' ]
                  . '?class_name=' . PROJ_COLLECTION_WORK_CLASS
                  . '&method_name=location_works_search_results'
                  . '&location_id=' . rawurlencode( $location_id )
                  . '&location_works_view_type=' . $view_type;

            HTML::link_start( $href, $title, '_blank' );
            echo $column_value;
            HTML::link_end();
          }
          return '';
        }
        return $column_value;

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function proj_get_description_from_id( $entity_id ) {  # method from Project

    return $this->get_location_desc_from_id( $entity_id );
  }
  #----------------------------------------------------------------------------------

  function get_location_desc_from_id( $location_id ) {

    if( ! $location_id ) return NULL;

    $where_clause = "location_id = $location_id";

    $statement = 'select * from ' . $this->proj_location_tablename()
               . " where $where_clause";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) return NULL;

    $this->current_row_of_data = $result[0];
    return $this->get_location_desc_from_current_row_of_data();
  }
  #-----------------------------------------------------

  function get_location_desc_from_current_row_of_data() {  # DB search results will set $this->current_row_of_data

    if( $this->current_row_of_data ) { 
      extract( $this->current_row_of_data, EXTR_OVERWRITE );
      $location_desc = trim( $location_name );
    }
    return $location_desc;
  }
  #-----------------------------------------------------

  function perform_delete_location( $location_id, $display_status_msg = FALSE ) {

    if( ! $location_id ) return;

    if( $display_status_msg ) {
      $location_desc = $this->get_location_desc_from_id( $location_id );
    }

    $statement = 'delete from ' . $this->proj_location_tablename()
               . " where location_id = $location_id ";
    $this->db_run_query( $statement );

    if( $display_status_msg ) {
      echo 'Deleted ' . $location_desc;
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      $anchor_name = 'deleted_location_id_' . $location_id;
      HTML::anchor( $anchor_name );
      $script = 'window.location.href = "' . $_SERVER['PHP_SELF'] . '" + "#' . $anchor_name . '"';
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'Enter some details of the required location and click Search or press Return.';
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function one_location_search_results() {  # Really just a dummy option to allow Edit location to be called via GET.

    if( $this->proj_edit_mode_enabled() )
      $this->edit_location();

    else {
      HTML::italic_start();
      echo 'Sorry, you do not have edit privileges in this database. Read-only details are now being displayed.';
      HTML::italic_end();
      HTML::new_paragraph();

      $this->write_post_parm( 'location_id', $this->read_get_parm( 'location_id' ));
      $this->write_post_parm( 'date_or_numeric_query_op_location_id', 'equals' );
      $this->write_post_parm( 'record_layout', 'down_page' );

      $this->db_search_results();
    }
  }
  #-----------------------------------------------------

  function add_location() {
    $this->edit_location( $new_record = TRUE );
  }
  #-----------------------------------------------------

  function db_browse_plugin_2( $column_name = NULL, $column_value = NULL ) {

    if( $column_name != 'location_id' ) return;
    if( ! $column_value ) return;
    if( $this->printable_output || $this->csv_output ) return;
    if( ! $this->proj_edit_mode_enabled() ) return;
    echo LINEBREAK;

    $title = 'Edit location details';

    $href = $_SERVER[ 'PHP_SELF' ] . '?class_name=' . $this->app_get_class( $this ) . '&method_name=one_location_search_results';
 
    $href .= '&' . $column_name . '=' . $column_value;
 
    $href .= '&opening_method=' . $this->menu_method_name;

    HTML::link_start( $href, $title, $target = '_blank' );
    echo 'Edit';
    HTML::link_end();
  }
  #-----------------------------------------------------

  function edit_location( $new_record = FALSE, $just_saved = FALSE ) {

    if( ! $new_record ) {
      $location_id = $this->read_post_parm( 'location_id' );
      $opening_method = $this->read_post_parm( 'opening_method' );

      if( ! $location_id ) {
        $location_id = $this->read_get_parm( 'location_id' );
        $opening_method = $this->read_get_parm( 'opening_method' );
      }
      $keep_failed_validation = $this->failed_validation;

      $found = $this->set_location( $location_id );
      if( ! $found ) $this->die_on_error( 'Invalid location/org ID' );

      $this->failed_validation = $keep_failed_validation;
    }

    if( $this->failed_validation ) {
      echo LINEBREAK . 'Your data could not be saved. Please correct invalid details and try again.' . LINEBREAK;
    }

    $this->write_work_entry_stylesheet();  # method from Project

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_location' );

    HTML::hidden_field( 'opening_method', $opening_method );
    $focus_script = NULL;

    if( ! $new_record ) {
      HTML::italic_start();
      echo 'Location ID ' . $this->location_id 
           . '. Last changed ' . $this->postgres_date_to_words( $this->change_timestamp )
           . ' by ' . $this->change_user . ' ';
      HTML::italic_end();

      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $opening_method );
      HTML::new_paragraph();
      HTML::horizontal_rule();

      HTML::hidden_field( 'location_id', $location_id );
    }

    if( $this->failed_validation ) {  # read values from POST and re-display
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_location_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }
    }

    $this->location_entry_fields();

    HTML::form_end();

    if( ! $new_record ) {
      $this->offer_location_deletion_form();
    }

    if( $focus_script ) HTML::write_javascript_function( $focus_script );

    # The above focus script will put you on the 'city' field.
    # However, may want to go to a different section of the form if they pressed 'Save and Continue'.

    if( $this->app_get_class( $this ) != 'popup_location' ) {  # no 'Save and Continue' in popup screen
      $anchor_script = $this->proj_get_anchor_script_after_save();
      if( $anchor_script )
        HTML::write_javascript_function( $anchor_script );
    }
  }
  #-----------------------------------------------------

  function location_entry_fields() {

    if( $this->location_id ) # for a new record you've only got the one section of core fields
      $this->proj_form_section_links( 'core_fields' );

    $this->editors_notes_field();

    $this->location_element_fields();

    $this->location_synonyms_field();

    $this->latitude_field();

    $this->longitude_field();

    if( $this->app_get_class( $this ) != 'popup_location' ) {  # popup screen has its own 'Save' buttons
      $this->proj_extra_save_button( $prefix = 'core_fields', $new_paragraph = FALSE, 'class ="workfield_save_button"' );

      if( $this->location_id ) { #Edit Existing is in new tab, but Add New is in main window which we don't want to close
        HTML::span_start( 'class="widespaceonleft"' );
        HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );
        HTML::span_end();
      }

      HTML::span_start( 'class="widespaceonleft"' );
      HTML::submit_button( 'clear_search_button', 'Search' );
      HTML::span_end();
    }

    HTML::new_paragraph();

    if( $this->location_id ) { # can't enter relationships until the core record exists
      HTML::horizontal_rule();

      $this->proj_form_section_links( 'researchers_notes' );
      $this->researchers_notes_field();

      $this->proj_form_section_links( 'related_resources' );
      $this->related_resources_field();

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        HTML::horizontal_rule();
        HTML::new_paragraph();
        $this->proj_form_section_links( 'imgs' );
        $this->images_field();
      }
    }
  }
  #-----------------------------------------------------

  function save_location() {

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

    $location_id = $this->read_post_parm( 'location_id' );

    if( $location_id && $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      if( $this->parm_found_in_post( 'upload_images_button' )) { # upload images rather than saving
        $found = $this->set_location( $location_id );
        if( ! $found ) $this->die_on_error( 'Invalid location ID' );
        if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
        $this->image_obj->image_upload_form( $entity_type = 'location' );
        return;
      }
    }

    $this->db_run_query( 'BEGIN TRANSACTION' );

    if( $location_id ) {
      $this->save_existing_location( $location_id );
    }
    else {
      $this->save_new_location();
    }

    $this->db_run_query( 'COMMIT' );

    $new_record = FALSE; 
    $just_saved = TRUE;

    if( $this->failed_validation ) {
      $just_saved = FALSE;
      if( ! $location_id ) $new_record = TRUE;
    }

    $this->edit_location( $new_record, $just_saved );
  }
  #-----------------------------------------------------

  function save_existing_location( $location_id ) {

    if( ! $location_id ) $location_id = $this->read_post_parm( 'location_id' );
    $found = $this->set_location( $location_id );
    if( ! $found ) $this->die_on_error( 'Invalid location/org ID' );

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_location_tablename());
    $i = 0;
    $statement = 'update ' . $this->proj_location_tablename() . ' set ';

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'location_id':
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
        case 'extra':
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $i++;
      if( $i > 1 ) $statement .= ', ';

      $column_value = $this->read_post_parm( $column_name );

      if( $column_name == 'location_name' ) $this->validate_location_name( $column_value );

      $statement .= $column_name . ' = ';
      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $statement .= $column_value;
    }

    $statement .= " where location_id = $this->location_id";

    if( ! $this->failed_validation ) {
      echo 'Please wait: saving your changes...' . LINEBREAK;
      flush();
      $this->db_run_query( $statement );

      $this->save_comments();
      $this->save_resources();

      if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
        $this->save_images();
      }

      HTML::h4_start();
      echo 'Any changes have been saved.';
      HTML::h4_end();
    }
  }
  #-----------------------------------------------------

  function save_new_location() {

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_location_tablename()) . "'::regclass )";
    $location_id = $this->db_select_one_value( $statement );

    $this->write_post_parm( 'location_id', $location_id );

    $statement = '';
    $col_statement = '';
    $val_statement = '';

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_location_tablename());
    $i = 0;
    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'change_timestamp':
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
        case 'extra':
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

      if( $column_name == 'location_name' ) $this->validate_location_name( $column_value );

      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $val_statement .= $column_value;
    }

    if( $this->failed_validation ) {
      $this->write_post_parm( 'location_id', NULL );
      return;
    }

    $statement = 'insert into ' . $this->proj_location_tablename() . " ( $col_statement ) values ( $val_statement )";
    $this->db_run_query( $statement );

    HTML::h4_start();
    echo 'New location has been saved.';
    HTML::h4_end();

    if( $this->app_get_class( $this ) == 'popup_location' ) return $location_id;

    HTML::div_start( 'class="buttonrow"' );

    HTML::form_start( $this->app_get_class( $this ), 'add_location' );
    echo 'Add another new location? ';
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

    $this->comment_obj->save_comments( $this->proj_location_tablename(), 
                                       $this->location_id, 
                                       RELTYPE_COMMENT_REFERS_TO_ENTITY );
  }
  #-----------------------------------------------------

  function save_resources() {

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else 
      $this->resource_obj->clear();

    $this->resource_obj->save_resources( $this->proj_location_tablename(), 
                                         $this->location_id );
  }
  #-----------------------------------------------------

  function save_images() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->image_obj->save_image_details( $entity_id = $this->location_id, $entity_type = 'location' );
  }
  #-----------------------------------------------------

  function prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date ) {

    # We don't have any numeric or date values in locations at the moment.
    $column_value = "'" . $this->escape( $column_value ) . "'";
    return $column_value;
  }
  #-----------------------------------------------------

  function validate_location_name( $value ) {

    $value = str_replace( NEWLINE, '', $value );
    $value = str_replace( CARRIAGE_RETURN, '', $value );
    $value = trim( $value );

    if( ! $value ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( $parm_name = 'Location name', $errmsg = 'cannot be blank' );
      return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------


  function location_name_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::input_field( 'location_name', 'Full name of location', $this->location_name, FALSE, FLD_SIZE_LOCATION_NAME,
                       $tabindex=1,  NULL, NULL, ' READONLY class="highlight2"' );

    echo LINEBREAK;

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::italic_start();
    echo 'The full name of the location will be automatically generated from its individual elements.';
    HTML::italic_end();
    HTML::span_end();

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function location_element_fields() {

    echo '<style type="text/css">' . NEWLINE;
    echo 'div#location_elements_div {' . NEWLINE;
    echo '  width: ' . floor( intval( FLD_SIZE_LOCATION_NAME ) * 1.5 ) . 'em;' . NEWLINE;
    echo '}' . NEWLINE;
    echo '</style>' . NEWLINE;

    HTML::div_start( 'class="highlight2"' );
    HTML::h4_start();
    echo 'Elements of location name:';
    HTML::h4_end();

    HTML::div_start( 'class="workfield" id="location_elements_div"' );

    # Write script to generate full name of location
    $scriptname = 'makeFullLocationName';

    $script  = "function ${scriptname}() {" . NEWLINE;
    $script .= '  var fullname = "";' . NEWLINE;
    $script .= '  var partname = "";' . NEWLINE;

    for( $i = SMALLEST_ELEMENT_OF_PLACENAME; $i <= LARGEST_ELEMENT_OF_PLACENAME; $i++ ) {

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX && $i == COUNTY_ELEMENT_OF_PLACENAME ) 
        continue; # IMPAcT don't want to use counties etc.

      $fieldname = $this->location_element_name( $i );
      $script .= NEWLINE;
      $script .= "  partname = document.$this->form_name.$fieldname.value;" . NEWLINE;
      $script .= '  if( partname > "" ) {' . NEWLINE;
      $script .= '    if( fullname > "" ) {' . NEWLINE;
      $script .= '      fullname = fullname + ", ";' . NEWLINE;
      $script .= '    }' . NEWLINE;
      $script .= '    fullname = fullname + partname;' . NEWLINE;
      $script .= '  }' . NEWLINE;
    }

    $script .= "  document.$this->form_name.location_name.value = fullname;" . NEWLINE;
    $script .= '}' . NEWLINE;
    HTML::write_javascript_function( $script );
   
    for( $i = SMALLEST_ELEMENT_OF_PLACENAME; $i <= LARGEST_ELEMENT_OF_PLACENAME; $i++ ) {
      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX && $i == COUNTY_ELEMENT_OF_PLACENAME ) 
        continue; # IMPAcT don't want to use counties etc.

      $this->location_element_field( $i, $scriptname );
      HTML::new_paragraph();
    }

    HTML::div_end();

    $this->location_name_field();
    HTML::div_end();

    $focus_field = $this->location_element_name( PRIMARY_ELEMENT_OF_PLACENAME );
    $focus_script = "document.$this->form_name.$focus_field.focus()";
    HTML::write_javascript_function( $focus_script );
  }
  #-----------------------------------------------------

  function location_element_field( $element_no, $scriptname ) {

    $fieldname = $this->location_element_name( $element_no );
    if( ! $fieldname ) die( 'Invalid element number.' );
    if( ! $scriptname ) die( 'Invalid script name for field element.' );

    $parms = 'onchange="' . $scriptname . '()"';

    $label = $this->location_element_label( $fieldname, $include_the_word_element=FALSE );

    if( $element_no == PRIMARY_ELEMENT_OF_PLACENAME ) HTML::span_start( 'class="bold"' );

    HTML::input_field( $fieldname, $label, $this->$fieldname, FALSE, FLD_SIZE_LOCATION_NAME,
                       $tabindex=1, NULL, NULL, $input_parms = $parms );

    HTML::span_start( 'class="narrowspaceonleft"' );
    $example = $this->location_element_example( $element_no, $brief = FALSE );
    echo ' ' . $example;
    HTML::span_end();
    if( $element_no == PRIMARY_ELEMENT_OF_PLACENAME ) HTML::span_end();
  }
  #-----------------------------------------------------

  function location_element_name( $element_no ) {

    $element_name = '';

    $example = $this->location_element_decode( $element_no );
    if( $example ) {
      $element_name = 'element_' . $element_no . '_eg_' . $example;
    }

    return $element_name;
  }
  #-----------------------------------------------------

  function get_element_number_from_name( $column_name ) {

    $element_no = $column_name;
    $element_no = str_replace( 'element_', '', $element_no );
    $element_no = substr( $element_no, 0, 1 );
    return $element_no;
  }
  #-----------------------------------------------------

  function location_element_label( $column_name, $include_the_word_element = FALSE ) {

    $element_no = $this->get_element_number_from_name( $column_name );
    $example = $this->location_element_example( $element_no, $brief = TRUE );

    if( $include_the_word_element ) {
      $example = strtolower( $example );
      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
        $column_label = 'Element of location: ' . $example;
      else {
        $column_label = 'Element ' . $element_no . ' e.g. ' . $example;
      }
    }
    else {  # don't include the word 'Element'
      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) 
        $column_label = $example;
      else {
        $example = strtolower( $example );
        $column_label = $element_no . '. E.g. ' . $example;
      }
    }

    return $column_label;
  }
  #-----------------------------------------------------

  function location_element_decode( $element_no ) {

    switch( $element_no ) {
      case ROOM_ELEMENT_OF_PLACENAME:     return 'room';
      case BUILDING_ELEMENT_OF_PLACENAME: return 'building';
      case PARISH_ELEMENT_OF_PLACENAME:   return 'parish';
      case CITY_ELEMENT_OF_PLACENAME:     return 'city';
      case COUNTY_ELEMENT_OF_PLACENAME:   return 'county';
      case COUNTRY_ELEMENT_OF_PLACENAME:  return 'country';
      case EMPIRE_ELEMENT_OF_PLACENAME:   return 'empire';
      default: break;
    }

    return NULL;
  }
  #-----------------------------------------------------

  function location_element_example( $element_no, $brief = TRUE ) {

    if( $brief ) { # generating field labels

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {  # customised field labels for IMPAcT
        switch( $element_no ) {
          case ROOM_ELEMENT_OF_PLACENAME:     return 'Room or part of building';
          case BUILDING_ELEMENT_OF_PLACENAME: return 'Building / institution';
          case PARISH_ELEMENT_OF_PLACENAME:   return 'District, quarter, or part of city';
          case CITY_ELEMENT_OF_PLACENAME:     return 'City or town';
          case COUNTY_ELEMENT_OF_PLACENAME:   return 'n/a';
          case COUNTRY_ELEMENT_OF_PLACENAME:  return 'Region';
          case EMPIRE_ELEMENT_OF_PLACENAME:   return 'Kingdom, empire';
          default:                            return NULL;
        }
      }

      else {  # default field labels
        $decode = $this->location_element_decode( $element_no );
        if( $decode == 'parish' ) $decode = 'district of city';
        return $decode;
      }
    }

    else { # generating search help

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {  # Islamic examples for IMPAcT
        switch( $element_no ) {
          case ROOM_ELEMENT_OF_PLACENAME:     return "e.g. masjid in Dolmabahce Palace";
          case BUILDING_ELEMENT_OF_PLACENAME: return "e.g. khanqah, zawiya, ribat, madrasa, maktaba, kulliya, imara";
          case PARISH_ELEMENT_OF_PLACENAME:   return "e.g. Suq al-Baqqalin; al-mahalla al-Arminiyya ";
          case CITY_ELEMENT_OF_PLACENAME:     return "e.g. Manisa";
          case COUNTY_ELEMENT_OF_PLACENAME:   return "n/a";
          case COUNTRY_ELEMENT_OF_PLACENAME:  return "e.g. Khurasan; Diyar-i 'Ajam";
          case EMPIRE_ELEMENT_OF_PLACENAME:   return "e.g. Mamluk Sultanate";
          default: break;
        }
      }
      else {  # default search help for Cultures of Knowledge
        switch( $element_no ) {
          case ROOM_ELEMENT_OF_PLACENAME:     return "<em>'Sub-place'</em>, e.g. Porter's Lodge";
          case BUILDING_ELEMENT_OF_PLACENAME: return "<em>'Place'</em>, e.g. St Anne's College";
          case PARISH_ELEMENT_OF_PLACENAME:   return "<em>'Civil parish/township'</em>, e.g. University of Oxford";
          case CITY_ELEMENT_OF_PLACENAME:     return "<em>'Local administrative unit'</em> (city or town), e.g. Oxford";
          case COUNTY_ELEMENT_OF_PLACENAME:   return "<em>'Wider administrative unit'</em>, e.g. Oxfordshire";
          case COUNTRY_ELEMENT_OF_PLACENAME:  return "<em>'Country'</em>, e.g. England";
          case EMPIRE_ELEMENT_OF_PLACENAME:   return "<em>'Nation'</em>, e.g. United Kingdom";
          default: break;
        }
      }
    }

    return NULL;
  }
  #-----------------------------------------------------

  function impt_location_element_example( $element_no ) {


    return NULL;
  }
  #-----------------------------------------------------

  function location_synonyms_field() {

    $workarray = explode( NEWLINE, $this->location_synonyms );
    $this->location_synonyms = '';
    foreach( $workarray as $line ) {
      $line = trim( $line );
      if( ! $line ) continue;
      if( $this->location_synonyms > '' ) $this->location_synonyms .= NEWLINE;
      $this->location_synonyms .= $line;
    }

    HTML::div_start( 'class="workfield"' );

    HTML::textarea( 'location_synonyms', 
                    $rows = LOCATION_SYNONYM_ROWS, 
                    $cols = FLD_SIZE_LOCATION_NAME, 
                    $value = $this->location_synonyms, 
                    $label = 'Alternative names for location' );

    HTML::div_end();

    HTML::italic_start();

    HTML::span_start( 'class="workfieldaligned"' );
    echo "Please put each synonym on a separate line.";
    HTML::new_paragraph();
    HTML::span_end();
    HTML::italic_end();

  }
  #-----------------------------------------------------

  function latitude_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::input_field( 'latitude', 'Latitude', $this->latitude );
    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function longitude_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::input_field( 'longitude', 'Longitude', $this->longitude );
    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function editors_notes_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->proj_textarea( 'editors_notes', PLACE_EDITORS_NOTES_ROWS, PLACE_EDITORS_NOTES_COLS,
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

  function researchers_notes_field() {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $general_notes = $this->get_rels_for_general_notes();

    $this->comment_obj->display_or_edit_comments( $general_notes, RELTYPE_COMMENT_REFERS_TO_ENTITY,
                                                  NULL, $this->form_name );

    HTML::new_paragraph();

    $this->proj_extra_save_button( $prefix = 'researchers_notes', $new_paragraph = FALSE, 
                                   'class ="workfield_save_button"' );

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function related_resources_field() {

    if( ! $this->resource_obj ) 
      $this->resource_obj = new Resource( $this->db_connection );
    else
      $this->resource_obj->clear();

    $resources = $this->get_rels_for_resources();

    $this->resource_obj->edit_resources( $resources, $related_table = $this->proj_location_tablename());
  }
  #-----------------------------------------------------

  function images_field() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->write_post_parm( 'location_id', $this->location_id ); # so it can be picked up by the image object
    $this->image_obj->image_entry_for_entity( $this->location_id, $entity_type = 'location' );
  }
  #-----------------------------------------------------

  function get_rels_for_general_notes() {

    $this->general_notes = array();

    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $left_table_name == $this->proj_comment_tablename() 
      && $relationship_type == RELTYPE_COMMENT_REFERS_TO_ENTITY ) {

        $this->general_notes[] = $row;
      }
    }

    return $this->general_notes;
  }
  #----------------------------------------------------------------------------------

  function get_rels_for_resources() {

    $this->resources = array();

    foreach( $this->this_on_left as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $right_table_name == $this->proj_resource_tablename() 
      && $relationship_type == RELTYPE_ENTITY_HAS_RESOURCE ) {

        $this->resources[] = $row;
      }
    }

    return $this->resources;
  }
  #----------------------------------------------------------------------------------

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

  function proj_list_form_sections() { # some of these sections will not be available when adding new record 

    $form_sections = array( 
      'core_fields'      => "Core fields and editors' notes",
      'researchers_notes'=> "Researchers' notes for front-end display",
      'related_resources'=> 'Related resources',
      'imgs'             => 'Images'
    );

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $discard = array_pop( $form_sections ); # Impact doesn't have images for locations
    }

    return $form_sections;
  }
  #-----------------------------------------------------

  function offer_location_deletion_form() {

    if( ! $this->location_id ) return;
    if( $this->menu_called_as_popup ) return;  # don't offer deletion in a popup window, just gets too messy

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::form_start( 'location', 'confirm_location_deletion' );
    HTML::hidden_field( 'location_id', $this->location_id );

    echo 'Delete this location? ';
    HTML::submit_button( 'delete_button', 'Delete' );

    HTML::italic_start();
    echo ' (Confirmation will be requested before the deletion takes place.)';
    HTML::italic_end();
    HTML::form_end();
  }
  #-----------------------------------------------------

  function confirm_location_deletion() {

    $invalid_input_msg = 'Invalid input to Confirm Location Deletion.';

    $location_id = $this->read_post_parm( 'location_id' );
    if( ! $location_id ) $this->die_on_error( $invalid_input_msg );

    $this->set_location( $location_id );
    if( ! $this->location_id ) $this->die_on_error( $invalid_input_msg );

    HTML::form_start( 'location', 'delete_location' );
    HTML::hidden_field( 'location_id', $this->location_id );

    HTML::h3_start();
    echo "Location to be deleted:";
    HTML::h3_end();
    echo $this->location_name;

    if( $this->location_synonyms ) {
      echo LINEBREAK;
      $this->echo_safely_with_linebreaks( $this->location_synonyms );
    }

    if( $this->latitude ) {
      echo LINEBREAK;
      $this->echo_safely_with_linebreaks( $this->latitude );
    }

    if( $this->longitude ) {
      echo LINEBREAK;
      $this->echo_safely_with_linebreaks( $this->longitude );
    }

    HTML::new_paragraph();

    HTML::div_start( 'class="warning"' );
    HTML::h3_start();
    echo "To delete, first tick the 'Confirm Deletion' checkbox then click the 'Delete' button.";
    HTML::h3_end();
    HTML::new_paragraph();

    HTML::checkbox( $fieldname = 'confirm_deletion',
                    $label = 'Tick to confirm deletion of this location',
                    $is_checked = FALSE,
                    $value_when_checked = 1 );
    HTML::new_paragraph();
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::submit_button( 'delete_button', 'Delete' );

    HTML::div_end();
    HTML::form_end();
  }
  #-----------------------------------------------------

  function delete_location() {

    $invalid_input_msg = 'Invalid input to Delete location.';

    $location_id = $this->read_post_parm( 'location_id' );
    if( ! $location_id ) $this->die_on_error( $invalid_input_msg );

    $this->set_location( $location_id );
    if( ! $this->location_id ) $this->die_on_error( $invalid_input_msg );

    if( $this->location_id != $this->read_post_parm( 'location_id' )) $this->die_on_error( $invalid_input_msg );

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
      $this->echo_safely( "Location '$this->location_name' (ID $this->location_id) was NOT deleted." );
      echo LINEBREAK . $msg . LINEBREAK;
      HTML::div_end();
      HTML::new_paragraph();
      $this->edit_location();
      return;
    }

    #------------------------------
    # Actually do the deletion here
    #------------------------------
    $this->echo_safely( "Deleting location '$this->location_name' (ID $this->location_id) ..." );

    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    $reltable = $this->proj_relationship_tablename();
    $location_table = $this->proj_location_tablename();

    $statement = 'select relationship_id, left_table_name as other_table, left_id_value as other_id '
               . " from $reltable"
               . " where right_table_name = '$location_table' and right_id_value = '$this->location_id'"
               . ' union '
               . 'select relationship_id, right_table_name as other_table, right_id_value as other_id '
               . " from $reltable"
               . " where left_table_name = '$location_table' and left_id_value = '$this->location_id'"
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

    # Now delete the actual location
    $statement = "delete from $location_table where location_id = $this->location_id";
    $this->db_run_query( $statement );

    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    HTML::div_start( 'class="warning"' );
    $this->echo_safely( "Location '$this->location_name' (ID $this->location_id) has been deleted." );
    HTML::div_end();
    HTML::new_paragraph();
    echo 'Returning to search...';
    HTML::new_paragraph();

    $this->write_post_parm( 'location_id', NULL );

    $this->db_search();
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'location_id':
      case 'sent':
      case 'recd':
      case 'all_works':
      case 'works_composed_at_place':
      case 'selected_merge_id':
        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'location_name':
      case 'location_synonyms':
      case 'latitude':
      case 'longitude':
      case 'editors_notes':
      case 'related_resources':
      case 'researchers_notes':
      case 'element_1_eg_room':
      case 'element_2_eg_building':
      case 'element_3_eg_parish':
      case 'element_4_eg_city':
      case 'element_5_eg_county':
      case 'element_6_eg_country':
      case 'element_7_eg_empire':
      case 'images':

        return $this->is_ok_free_text( $this->parm_value );


      case 'merge':
      case 'sent_or_received_works':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
