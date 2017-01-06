<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose or add publication.
#====================================================================================

define( 'POPUP_PUBLICATION_MAX_DROPDOWN_OPTIONS', 100 );
define( 'POPUP_PUBLICATION_FIELD_POS', 110 );
define( 'POPUP_PUBLICATION_FIELD_LABEL_WIDTH', 100 );

class Popup_Publication extends publication {

  #----------------------------------------------------------------------------------

  function Popup_Publication( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent CofK methods.
    #-----------------------------------------------------
    $this->Publication( $db_connection );
  }
  #----------------------------------------------------------------------------------

  function get_primary_display_fieldname() {

    $field_to_get = 'publication_details';
    if( $this->return_abbrev ) $field_to_get = 'abbrev';
    return $field_to_get;
  }
  #----------------------------------------------------------------------------------

  function app_popup_get_search_table() {  # from application entity

    return $this->proj_publication_tablename();
  }
  #-----------------------------------------------------

  function app_popup_set_result_id() {  # from application entity

    if( $this->menu_method_name == 'save_publication' )
      $this->app_popup_result_id = $this->publication_id;  
    else
      $this->app_popup_result_id = $this->current_row_of_data[ 'publication_id' ];  
    return $this->app_popup_result_id;
  }
  #-----------------------------------------------------

  function app_popup_set_result_text() {

    if( $this->menu_method_name == 'save_publication' )
      $this->app_popup_result_text = $this->get_publication_desc_from_id( $this->publication_id );  
    else
      $this->app_popup_result_text = $this->get_publication_desc_from_current_row_of_data();
    return $this->app_popup_result_text;
  }
  #-----------------------------------------------------

  function app_popup_pass_text_back( $text_field_value = NULL ) {

    $this->app_popup_set_value_of_field_in_opener( $this->calling_field, $text_field_value );
    $this->app_popup_focus_on_field_in_opener(  $this->calling_field );
  }
  #-----------------------------------------------------

  function get_publication_desc_from_current_row_of_data() {  # DB search results will set $this->current_row_of_data

    $field_to_get = $this->get_primary_display_fieldname();

    $$field_to_get = NULL;  # clear it before extracting data from the row

    if( $this->current_row_of_data ) { 
      extract( $this->current_row_of_data, EXTR_OVERWRITE );
    }

    if( $$field_to_get )
      return $$field_to_get;
    else
      return $publication_details;
  }
  #-----------------------------------------------------

  function app_popup_from_query_field() {  # from application entity

    # Child class must check value of $this->calling_field (plus possibly form) and
    # return TRUE if calling field is part of a search form. Otherwise return FALSE.

    # In this case return TRUE because we want to return the DECODE (as if it were a search form) not the ID.
    return TRUE;
  }
  #-----------------------------------------------------

  function app_popup_get_decode_fieldname() {  # from application entity

    $fieldname = $this->calling_field;
    return $fieldname;
  }
  #-----------------------------------------------------

  function app_popup_get_focus_fieldname() {  # from application entity

    $fieldname = $this->app_popup_get_decode_fieldname();
    return $fieldname;
  }
  #-----------------------------------------------------

  function app_popup_get_order_by_col() {  # from application entity

    return $this->get_primary_display_fieldname();
  }
  #-----------------------------------------------------

  function app_popup_get_field_for_select_button() { # from application entity

    return 'publication_id';
  }
  #-----------------------------------------------------

  function app_popup_add_record() {

    $this->publication_entry_stylesheets();

    $this->form_name = html::form_start( $this->app_get_class( $this ), 'save_publication' );

    $this->app_popup_read_calling_form_and_field();
    html::hidden_field( 'calling_form', $this->calling_form );
    html::hidden_field( 'calling_field', $this->calling_field );

    $this->add_publication();

    html::form_end();
  }
  #-----------------------------------------------------
  # Add selected text without overwriting what is already there.

  function app_popup_set_value_of_field_in_opener( $field_name, $field_value ) {  # override Application Entity

    $this->script .= "var callingField = opener.document.$this->calling_form.$field_name;" . NEWLINE;
    $this->script .= "if( callingField.value > '' ) {"                                     . NEWLINE;
    $this->script .= "  callingField.value = callingField.value + ' ' + '$field_value';"   . NEWLINE;
    $this->script .= "}"                                                                   . NEWLINE;
    $this->script .= "else {"                                                              . NEWLINE;
    $this->script .= "  callingField.value = '$field_value';"                              . NEWLINE;
    $this->script .= "}"                                                                   . NEWLINE;
  }
  #-----------------------------------------------------

  function app_popup_get_view_button_label() {
    return 'Copy';
  }
  #-----------------------------------------------------

  function app_popup_get_create_button_label() {
    return 'Add';
  }
  #-----------------------------------------------------

  function save_publication() {

    $publication_id = $this->save_new_publication();

    if( ! $publication_id || $this->failed_validation ) {
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_publication_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }

      $this->app_popup_add_record();
      return;
    }

    $this->set_publication( $publication_id );

    echo $this->get_publication_desc_from_id( $this->publication_id );  
    html::new_paragraph();
    html::italic_start();
    echo 'Click OK to pass the details back into the main data entry screen.';
    html::italic_end();
    html::new_paragraph();

    $this->calling_form = $this->read_post_parm( 'calling_form' );
    $this->calling_field = $this->read_post_parm( 'calling_field' );

    $this->app_popup_pass_value_back(); 
    html::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    parent::db_set_search_result_parms();

    # Override some of the parameters
    $this->from_table     = $this->app_popup_get_search_table();
    $this->search_method  = $this->app_popup_get_search_method();
    $this->results_method = $this->app_popup_get_search_results_method();

    $this->write_post_parm( 'order_by', $this->app_popup_get_order_by_col() );
  }
  #-----------------------------------------------------

  function db_list_columns(  $table_or_view = NULL ) {  # overrides parent class

    $rawcols = parent::db_list_columns( $table_or_view );
    if( ! is_array( $rawcols )) return NULL;

    $columns = array();
    foreach( $rawcols as $row ) {
      extract( $row, EXTR_OVERWRITE );

      #------------------
      # Set column labels
      #------------------
      switch( $column_name ) {

        case 'publication_id':
          if( $this->menu_method_name == 'app_popup_search_results' ) {
            $row[ 'column_label' ] = 'Select';
          }
          break;

        default:
          break;
      }

      if( $column_name == 'publication_id' ) 
        $id_row = $row;
      else
        $columns[] = $row;
    }

    $columns[] = $id_row;
    return $columns;
  }
  #-----------------------------------------------------
  # Override method from Project class, so you can select by first letter of name.

  function proj_initial_popup_selection_method() {  # can be overridden by child class

    return 'select_by_first_letter_of_name';
  }
  #----------------------------------------------------------------------------------

  function select_by_first_letter_of_name() {

    $this->db_set_search_result_parms();

    $this->write_letter_selection_stylesheet();

    $this->form_name = html::form_start( $this->app_get_class( $this ), 'select_by_first_letter_of_name' );

    $this->app_popup_read_calling_form_and_field();
    html::hidden_field( 'calling_form', $this->calling_form );
    html::hidden_field( 'calling_field', $this->calling_field );

    $publications_found = 0;

    #-----------------------------------------------------
    # Write out one button for each letter of the alphabet
    #-----------------------------------------------------
    $first_letter_of_name = $this->read_post_parm( 'first_letter_of_name' );

    html::hidden_field( 'first_letter_of_name', $first_letter_of_name );

    html::hidden_field( 'block_of_names_start', '' );  # Just a dummy field in most cases. 
                                                       # Only used if you need to sub-divide a longer list

    $script  = 'function chooseNewLetter( letterButton ) {'                                    . NEWLINE;
    $script .= "  document.$this->form_name.first_letter_of_name.value = letterButton.value; " . NEWLINE;
    $script .= "  document.$this->form_name.block_of_names_start.value = '';"                  . NEWLINE;
    $script .= '}'                                                                             . NEWLINE;

    html::write_javascript_function( $script );

    html::new_paragraph();
    echo 'Click a letter to retrieve a list of publications with details beginning with that letter:';
    html::new_paragraph();

    $letter = 'A';
    for( $i = 0; $i < 26; $i++ ) {
      if( $i == 13 ) html::new_paragraph();

      $button_name = 'first_letter_of_name_button' . $letter;
      if( $letter == 'A' ) $focus_field = $button_name;

      $parms = 'class="all_letters';
      if( $letter == $first_letter_of_name ) $parms .= ' selected_letter';
      $parms .= '" ';

      $script = ' onclick="chooseNewLetter( this )" ';
      $parms .= $script;

      html::submit_button( $button_name, $letter, $tabindex = 2, $parms );
      echo ' ';

      $letter++;
    }


    #--------------------------------------------
    # If a letter was chosen, select publications 
    # with names beginning with that letter.
    #--------------------------------------------
    if( $first_letter_of_name ) {
      html::new_paragraph();
      html::horizontal_rule();
      html::new_paragraph();

      $field_to_get = $this->get_primary_display_fieldname();
      
      $statement = 'select * from ' . $this->from_table . ' where upper( ' . $field_to_get . ' ) like '
                 . "'${first_letter_of_name}%'"
                 . ' order by ' . $field_to_get;
      $publications = $this->db_select_into_array( $statement );
      $publications_found = count( $publications );
    }

    #-------------------------------------------
    # Display a dropdown list of selected publications
    # (or a subset if list is too long
    #-------------------------------------------
    if( $first_letter_of_name && ! $publications_found ) {
      echo "No publications found with details starting with '$first_letter_of_name'.";
      html::new_paragraph();
      html::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
      $focus_field = 'cancel_button';
    }

    elseif( ! $first_letter_of_name ) {
      html::new_paragraph();
      html::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
    }

    elseif( $publications_found ) {

      $focus_field = 'publication_sel';

      if( $publications_found > POPUP_PUBLICATION_MAX_DROPDOWN_OPTIONS ) {
        $publications = $this->subdivide_within_first_letter_of_name( $publications );
      }

      $this->write_passback_script();

      html::dropdown_start( 'publication_sel', $label = NULL );
      $first = TRUE;
      foreach( $publications as $row ) {
        $this->current_row_of_data = $row;
        extract( $row, EXTR_OVERWRITE );
        if( $first ) $selection = $publication_id; # select first option initially

        html::dropdown_option( $publication_id, $this->app_popup_set_result_text(), $selection );

        $selection = NULL;
        $first = FALSE;
      }
      html::dropdown_end();

      echo ' ';

      html::button( 'select_button', 'Select', $tabindex = 1, 'onClick="selectpublication()"' );
      html::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
    }

    html::form_end();

    #----------------------------------------------------
    # Focus on letter 'A', dropdown list or Cancel button
    #----------------------------------------------------
    $script = "document.$this->form_name.$focus_field.focus();"  . NEWLINE;
    html::write_javascript_function( $script );

    #---------------------------------------
    # Provide normal 'Search' option as well
    #---------------------------------------
    html::new_paragraph();
    html::horizontal_rule();
    html::new_paragraph();

    html::form_start( $this->app_get_class( $this ), $this->app_popup_get_search_method() );

    html::hidden_field( 'calling_form', $this->calling_form );
    html::hidden_field( 'calling_field', $this->calling_field );

    echo 'Alternatively you can search using a more flexible set of criteria: ';
    html::submit_button( 'search_button', 'Search' );

    html::form_end();
  }
  #-----------------------------------------------------

  function write_passback_script() {

    $thisform = $this->form_name;

    $calling_form = $this->calling_form;
    $calling_field = $this->calling_field;

    $decode_field = $this->app_popup_get_decode_fieldname(); 
    $focus_field = $this->app_popup_get_focus_fieldname();

    $script  = 'function selectpublication() {'                                                       . NEWLINE;
    $script .= "  var selectedOptNo = document.$thisform.publication_sel.selectedIndex;"              . NEWLINE;
    $script .= "  var selectedText = document.$thisform.publication_sel.options[selectedOptNo].text;" . NEWLINE;
    $script .= "  var callingField = opener.document.$calling_form.$calling_field;"                   . NEWLINE;
    $script .= "  if( callingField.value > '' ) {"                                                    . NEWLINE;
    $script .= "    callingField.value =  callingField.value+ ' ' + selectedText;"                    . NEWLINE;
    $script .= "  }"                                                                                  . NEWLINE;
    $script .= "  else {"                                                                             . NEWLINE;
    $script .= "    callingField.value = selectedText;"                                               . NEWLINE;
    $script .= "  }"                                                                                  . NEWLINE;
    $script .= "  var focusField = opener.document.$calling_form.$focus_field;"                       . NEWLINE;
    $script .= '  if( focusField != null ) {;'                                                        . NEWLINE;
    $script .= '    focusField.focus();'                                                              . NEWLINE;
    $script .= '  }'                                                                                  . NEWLINE;
    $script .= '  self.close();'                                                                      . NEWLINE;
    $script .= '}'                                                                                    . NEWLINE;

    html::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function subdivide_within_first_letter_of_name( $all_publications ) {

    html::new_paragraph();

    $field_to_get = $this->get_primary_display_fieldname();

    $some_publications = array();
    $all_publications_count = count( $all_publications );
    $last_record_in_all_publications = $all_publications_count - 1;

    $block_of_names_start = $this->read_post_parm( 'block_of_names_start' );

    if( ! $block_of_names_start ) $block_of_names_start = 0;

    $i = 0;
    $button_index = 0;
    $buttons = array();
    $first_name = '';
    $last_name = '';

    while( $i < $all_publications_count ) {
      if( $i % POPUP_PUBLICATION_MAX_DROPDOWN_OPTIONS == 0 ) {  # start of new block

        if( $i > 0 ) {  # finish off previous block before you start new one
          $row = $all_publications[ $i - 1 ];
          extract( $row, EXTR_OVERWRITE );
          $last_name = $$field_to_get;
          $buttons[ $button_index ]['last_name'] = $last_name;
        }

        $button_index = $i;
        $first_name = '';
        $last_name = '';

        $row = $all_publications[ $i ];
        extract( $row, EXTR_OVERWRITE );
        $first_name = $$field_to_get;
        $buttons[ $button_index ] = array( 'first_name' => $first_name,
                                           'last_name'  => $last_name );
      }

      $i += POPUP_PUBLICATION_MAX_DROPDOWN_OPTIONS;
    }

    $row = $all_publications[ $all_publications_count - 1 ];
    extract( $row, EXTR_OVERWRITE );
    $last_name = $$field_to_get;
    $buttons[ $button_index ]['last_name'] = $last_name;


    $script  = 'function chooseNewBlockOfNames( blockButton ) {'                              . NEWLINE;
    $script .= "  document.$this->form_name.block_of_names_start.value = blockButton.value;"  . NEWLINE;
    $script .= "  document.$this->form_name.submit();"                                        . NEWLINE;
    $script .= '}'                                                                            . NEWLINE;

    html::write_javascript_function( $script );


    foreach( $buttons as $button_number => $names ) {

      $button_label = $names['first_name'] . ' --- ' . $names['last_name'];

      html::radio_button( 'block_of_names_start_selecter', $button_label, $value_when_checked = $button_number, 
                          $current_value = $block_of_names_start, $tabindex=2, $button_instance=$button_number, 
                          $script='onclick="chooseNewBlockOfNames( this )"' );
      echo LINEBREAK;
    }

    html::new_paragraph();
    html::horizontal_rule();
    html::new_paragraph();

    for( $i = $block_of_names_start; $i < $block_of_names_start + POPUP_PUBLICATION_MAX_DROPDOWN_OPTIONS; $i++ ) {
      $some_publications[] = $all_publications[ $i ];
      if( $i == $last_record_in_all_publications ) break;
    }

    return $some_publications;
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    switch( $column_name ) {

      case 'publication_id':
        return '';

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function write_letter_selection_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' input.all_letters {'                                               . NEWLINE;
    echo '   font-color: ' . html::get_contrast1_colour() . ';'               . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    . NEWLINE;
    echo '   padding: 2px;'                                                   . NEWLINE;
    echo '   margin: 5px;'                                                    . NEWLINE;
    echo ' }'                                                                 . NEWLINE;


    echo ' input.selected_letter {'                                           . NEWLINE;
    echo '   font-weight: bold;'                                              . NEWLINE;
    echo '   font-size: 12pt;'                                                . NEWLINE;
    echo '   padding: 5px;'                                                   . NEWLINE;
    echo ' }'                                                                 . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------

  function write_work_entry_stylesheet() {  # override method from CofK Entity

    parent::write_work_entry_stylesheet();

    echo '<style type="text/css">' . NEWLINE;

    echo ' span.popupinputfield input, label.popupnoentry {'  . NEWLINE;
    echo '   margin-left: ' . POPUP_PUBLICATION_FIELD_POS . 'px;'  . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' span.popupinputfield label {'                      . NEWLINE;
    echo '   width: ' . POPUP_PUBLICATION_FIELD_LABEL_WIDTH . 'px;'. NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield label  {'                               . NEWLINE;
    echo '   width: ' . POPUP_PUBLICATION_FIELD_LABEL_WIDTH . 'px;'. NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield input, .workfield textarea, .workfield select, span.workfieldaligned  {'           
                                                              . NEWLINE;
    echo '   margin-left: ' . POPUP_PUBLICATION_FIELD_POS . 'px;'  . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' ul.calendartypes, ul.dateflags {'                  . NEWLINE;
    echo '   left: '. POPUP_PUBLICATION_FIELD_POS . 'px; '         . NEWLINE;
    echo ' }';

    echo '</style>' . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'first_letter_of_name':
        return $this->is_alphabetic_or_blank( $this->parm_value );

      case 'block_of_names_start':
        return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
