<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'PUBLICATION_DETAILS_ROWS', 4 );
define( 'PUBLICATION_DETAILS_COLS', 50 );
define( 'PUBLICATION_ABBREV_MAX_LENGTH', 50 );

class Publication extends Project {

  #----------------------------------------------------------------------------------

  function Publication( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function set_publication( $publication_id = NULL ) {

    $this->clear();
    if( ! $publication_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_publication_tablename()
               . " where publication_id = $publication_id";
    $this->db_select_into_properties( $statement );

    return $this->publication_id;
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
    $this->keycol         = 'publication_id';
    $this->from_table     = $this->proj_publication_tablename();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'publication_details' );

    if( $this->proj_edit_mode_enabled() && ! $this->is_popup_publication() ) {
      $this->edit_method = 'edit_publication';
      $this->edit_tab  = '_blank';
    }
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

  function proj_get_description_from_id( $entity_id ) {  # method from Project

    return $this->get_publication_desc_from_id( $entity_id );
  }
  #----------------------------------------------------------------------------------

  function get_publication_desc_from_id( $publication_id ) {

    if( ! $publication_id ) return NULL;

    if( $using_integer_id ) {
      $where_clause = "publication_id = $publication_id";
    }
    else {
      $where_clause = "publication_id = '$publication_id'";
    }

    $statement = 'select * from ' . $this->proj_publication_tablename()
               . " where publication_id = $publication_id";
    $result = $this->db_select_into_array( $statement );
    if( count( $result ) != 1 ) return NULL;

    $this->current_row_of_data = $result[0];
    return $this->get_publication_desc_from_current_row_of_data();
  }
  #-----------------------------------------------------

  function get_publication_desc_from_current_row_of_data() {  # DB search results will set $this->current_row_of_data

    $publication_details = NULL;
    if( $this->current_row_of_data ) { 
      extract( $this->current_row_of_data, EXTR_OVERWRITE );
    }

    return $publication_details;
  }
  #-----------------------------------------------------

  function perform_delete_publication( $publication_id, $display_status_msg = FALSE ) {

    if( ! $publication_id ) return;

    if( $display_status_msg ) {
      $publication_desc = $this->get_publication_desc_from_id( $publication_id );
    }

    $statement = 'delete from ' . $this->proj_publication_tablename()
               . " where publication_id = $publication_id ";
    $this->db_run_query( $statement );

    if( $display_status_msg ) {
      HTML::div_start( 'class="warning"' );
      echo 'Deleted ' . $publication_desc;
      HTML::div_end();
      HTML::new_paragraph();
      echo 'Returning to search mode...';
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();
    }

    $this->write_post_parm( 'clear_search_button', 'Search' ); 
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo "Enter some details of the required publication (e.g. part or all of the author's name or the publication title)"
         . ' and click Search or press Return.';
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function add_publication() {
    $this->edit_publication( $new_record = TRUE );
  }
  #-----------------------------------------------------

  function edit_publication( $new_record = FALSE, $just_saved = FALSE ) {

    if( ! $new_record ) {
      $publication_id = $this->read_post_parm( 'publication_id' );
      $opening_method = $this->read_post_parm( 'opening_method' );

      if( ! $publication_id ) {
        $publication_id = $this->read_get_parm( 'publication_id' );
        $opening_method = $this->read_get_parm( 'opening_method' );
      }
      $keep_failed_validation = $this->failed_validation;

      $found = $this->set_publication( $publication_id );
      if( ! $found ) $this->die_on_error( 'Invalid publication ID' );

      $this->failed_validation = $keep_failed_validation;
    }

    if( $this->failed_validation ) {
      echo LINEBREAK . 'Your data could not be saved. Please correct invalid details and try again.' . LINEBREAK;
    }

    $this->publication_entry_stylesheets();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_publication' );

    HTML::hidden_field( 'opening_method', $opening_method );
    $focus_script = NULL;

    if( ! $new_record ) {
      HTML::italic_start();
      echo 'Publication ID ' . $this->publication_id 
           . '. Last changed ' . $this->postgres_date_to_words( $this->change_timestamp )
           . ' by ' . $this->change_user . ' ';
      HTML::italic_end();

      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $opening_method );
      HTML::new_paragraph();
      HTML::horizontal_rule();

      HTML::hidden_field( 'publication_id', $publication_id );

      HTML::h3_start();
      echo 'Change existing details:';
      HTML::h3_end();
    }

    if( $this->failed_validation ) {  # read values from POST and re-display
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_publication_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }
    }

    $this->publication_details_field();

    $this->abbreviation_field();

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::submit_button( 'save_button', 'Save' );
    HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );

    if( ! $this->is_popup_publication() )
      HTML::submit_button( 'clear_search_button', 'Search' );
    HTML::span_end();


    if( ! $new_record ) {
      HTML::new_paragraph();
      HTML::italic_start();
      echo 'Please note: changes to the details shown here will not affect the printed edition details'
           . ' of any existing works/manifestations.';
      echo LINEBREAK;
      echo "The publication details shown here will simply be used in a 'pick-list'"
           . " to help save you unnecessary typing in future.";
      HTML::italic_end();

      $this->offer_deletion();
    }

    HTML::form_end();

    if( $focus_script ) HTML::write_javascript_function( $focus_script );
  }
  #-----------------------------------------------------

  function publication_entry_stylesheets() {

    $this->write_work_entry_stylesheet();  # method from Project
  }
  #-----------------------------------------------------

  function offer_deletion() {

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::h3_start();
    echo 'Or delete publication details:';
    HTML::h3_end();

    echo 'These publication details can be deleted if they are not needed in future pick-lists. This will ';
    HTML::bold_start();
    echo 'not';
    HTML::bold_end();
    echo ' have any effect on the details of existing works/manifestations.';
    HTML::new_paragraph();

    echo "To delete these publication details, you need both to tick the 'Confirm deletion' checkbox ";
    HTML::italic_start();
    echo 'and';
    HTML::italic_end();
    echo " click the 'Delete' button.";
    HTML::new_paragraph();

    HTML::span_start( 'class="bold"' );
    HTML::checkbox( $fieldname = 'confirm_deletion', $label = 'Confirm deletion', $is_checked = FALSE );
    HTML::span_end();
    HTML::new_paragraph();

    HTML::submit_button( 'delete_button', 'Delete' );
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function save_publication() {

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

    elseif( $this->parm_found_in_post( 'delete_button' )) {
      if( ! $this->parm_found_in_post( 'confirm_deletion' )) {
        HTML::div_start( 'class="errmsg"' );
        echo "You did not tick the 'Confirm Deletion' checkbox, so the record was not deleted.";
        HTML::div_end();
        HTML::new_paragraph();
        $this->edit_publication( $new_record = FALSE, $just_saved = FALSE );
      }
      else {
        $publication_id = $this->read_post_parm( 'publication_id' );
        $this->perform_delete_publication( $publication_id, $display_status_msg = TRUE );
        HTML::new_paragraph();
        $this->db_search();
      }
      return;
    }

    $this->db_run_query( 'BEGIN TRANSACTION' );

    $publication_id = $this->read_post_parm( 'publication_id' );

    if( $publication_id ) {
      $this->save_existing_publication( $publication_id );
    }
    else {
      $this->save_new_publication();
    }

    $new_record = FALSE; 
    $just_saved = TRUE;

    if( $this->failed_validation ) {
      $this->db_run_query( 'ROLLBACK' );
      $just_saved = FALSE;
      if( ! $publication_id ) $new_record = TRUE;
    }
    else {
      $this->db_run_query( 'COMMIT' );
    }

    $this->edit_publication( $new_record, $just_saved );
  }
  #-----------------------------------------------------

  function save_existing_publication( $publication_id ) {

    if( ! $publication_id ) $publication_id = $this->read_post_parm( 'publication_id' );
    $found = $this->set_publication( $publication_id );
    if( ! $found ) $this->die_on_error( 'Invalid publication/org ID' );

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_publication_tablename());
    $i = 0;
    $statement = 'update ' . $this->proj_publication_tablename() . ' set ';

    foreach( $columns as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'publication_id':
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

      if( $column_name == 'publication_details' ) $this->validate_publication_details( $column_value );
      if( $column_name == 'abbrev' ) $column_value = $this->trunc_abbrev( $column_value );

      $statement .= $column_name . ' = ';
      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $statement .= $column_value;
    }

    $statement .= " where publication_id = '$this->publication_id'";

    if( ! $this->failed_validation ) {
      echo 'Please wait: saving your changes...' . LINEBREAK;
      flush();
      $this->db_run_query( $statement );

      HTML::h4_start();
      echo 'Any changes have been saved.';
      HTML::h4_end();
    }
  }
  #-----------------------------------------------------

  function save_new_publication() {

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_publication_tablename()) . "'::regclass )";
    $publication_id = $this->db_select_one_value( $statement );

    $this->write_post_parm( 'publication_id', $publication_id );

    $statement = '';
    $col_statement = '';
    $val_statement = '';

    $this->continue_on_read_parm_err = TRUE;
    $columns = $this->db_list_columns( $this->proj_publication_tablename());
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

      if( $column_name == 'publication_details' ) $this->validate_publication_details( $column_value );
      if( $column_name == 'abbrev' ) $column_value = $this->trunc_abbrev( $column_value );

      $column_value = $this->prepare_value_for_save( $column_name, $column_value, $is_numeric, $is_date );
      $val_statement .= $column_value;
    }

    if( $this->failed_validation ) {
      $this->write_post_parm( 'publication_id', '' );
      return;
    }

    $statement = 'insert into ' . $this->proj_publication_tablename() . " ( $col_statement ) values ( $val_statement )";
    $this->db_run_query( $statement );

    HTML::h4_start();
    echo 'New publication has been saved.';
    HTML::h4_end();

    if( $this->is_popup_publication() )
      return $publication_id;

    HTML::div_start( 'class="buttonrow"' );

    HTML::form_start( $this->app_get_class( $this ), 'add_publication' );
    echo 'Add another new publication? ';
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

  function validate_publication_details( $value ) {

    $value = str_replace( NEWLINE, '', $value );
    $value = str_replace( CARRIAGE_RETURN, '', $value );
    $value = trim( $value );

    if( ! $value ) {
      $this->failed_validation = TRUE;
      $this->display_errmsg( $parm_name = 'Publication details', $errmsg = 'cannot be blank' );
      return FALSE;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function trunc_abbrev( $abbrev ) {
    if( strlen( $abbrev ) > PUBLICATION_ABBREV_MAX_LENGTH )
      $abbrev = substr( $abbrev, 0, PUBLICATION_ABBREV_MAX_LENGTH );
    return $abbrev;
  }
  #-----------------------------------------------------


  function publication_details_field() {

    HTML::div_start( 'class="workfield boldlabel"' );
    echo LINEBREAK;

    if( ! $this->publication_id ) {
      HTML::italic_start();
      echo "Any details entered here will be made available on 'pick-lists' to save you typing" 
           . ' when entering the printed edition details of works/manifestations.';
      HTML::italic_end();
      HTML::new_paragraph();
    }

    $this->proj_textarea( 'publication_details', PUBLICATION_DETAILS_ROWS, PUBLICATION_DETAILS_COLS,
                          $value = $this->publication_details, $label = 'Publication details*' );
    echo LINEBREAK;

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::italic_start();
    echo '(e.g. author(s)/editor(s), title, place and year of publication)';
    HTML::italic_end();
    HTML::span_end();

    HTML::new_paragraph();
    HTML::div_end();
  }
  #-----------------------------------------------------


  function abbreviation_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    HTML::input_field( 'abbrev', 'Abbreviation', $this->abbrev, 
                       $in_table = FALSE, $size = NULL, $tabindex = 1, $label_parms = NULL, $data_parms = NULL, 
                       $input_parms = 'MAXLENGTH="' . PUBLICATION_ABBREV_MAX_LENGTH . '"' );

    HTML::span_start( 'class="narrowspaceonleft"' );
    HTML::italic_start();
    echo ' (optional, max. 50 characters)';
    HTML::italic_end();
    HTML::span_end();

    HTML::new_paragraph();
    HTML::div_end();
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
      if( $column_name == 'creation_timestamp' ) $skip_it = TRUE;
      if( $column_name == 'creation_user' ) $skip_it = TRUE;

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

        case 'publication_id':
          $column_label = 'Publication ID';
          $search_help_text = 'The unique ID for the record within this database.';
          break;

        case 'abbrev':
          $column_label = 'Abbreviation';
          $search_help_text = 'Optional short form of the full publication details.';
          break;

        case 'publication_details':
          $search_help_text = 'E.g. author(s)/editor(s), title, place and year of publication.';
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
      if( $this->entering_selection_criteria && $column_name == 'publication_id' ) # put at end of list
        $id_row = $row;
      elseif( $column_name == 'change_timestamp' )
        $timestamp_row = $row;
      elseif( $column_name == 'change_user' )
        $user_row = $row;
      else
        $columns[] = $row;
    }

    # Put "change timestamp/user" columns after publication full details and abbreviation
    if( $this->entering_selection_criteria || $this->reading_selection_criteria ) $columns[] = $user_row;
    $columns[] = $timestamp_row;

    if( $this->entering_selection_criteria ) # put ID column at end of list
      $columns[] = $id_row;

    return $columns;
  }
  #-----------------------------------------------------

  function is_popup_publication() {

    $is_popup = FALSE;
    $this_class = strtolower( $this->app_get_class( $this ));
    if( $this_class == 'popup_publication' || $this_class == 'popup_publication_abbrev' )
      $is_popup = TRUE;
    return $is_popup;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'publication_id':
        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'publication_details':
      case 'abbrev':
        return $this->is_ok_free_text( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
