<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose or add person.
#====================================================================================

define( 'POPUP_PERSON_MAX_DROPDOWN_OPTIONS', 100 );
define( 'POPUP_PERSON_FIELD_POS', 110 );
define( 'POPUP_PERSON_FIELD_LABEL_WIDTH', 100 );

class Popup_Person extends Person {

  #----------------------------------------------------------------------------------

  function Popup_Person( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent CofK methods.
    #-----------------------------------------------------
    $this->Person( $db_connection );
  }
  #----------------------------------------------------------------------------------

  function app_popup_get_search_table() {  # from application entity

    if( $this->menu_method_name == 'select_by_first_letter_of_name' )
      return $this->proj_person_tablename();
    else
      return $this->proj_person_viewname();
  }
  #-----------------------------------------------------

  function app_popup_set_result_id() {  # from application entity

    if( $this->menu_method_name == 'save_person' )
      $this->app_popup_result_id = $this->person_id;  
    else
      $this->app_popup_result_id = $this->current_row_of_data[ 'person_id' ];  
    return $this->app_popup_result_id;
  }
  #-----------------------------------------------------

  function app_popup_set_result_text() {

    if( $this->menu_method_name == 'save_person' )
      $this->app_popup_result_text = $this->get_person_desc_from_id( $this->iperson_id, $using_integer_id = TRUE );  
    else
      $this->app_popup_result_text = $this->get_person_desc_from_current_row_of_data();

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->app_popup_result_text = $this->put_al_after_surname( $this->app_popup_result_text );
    }
    return $this->app_popup_result_text;
  }
  #-----------------------------------------------------

  function app_popup_from_query_field() {  # from application entity

    # Child class must check value of $this->calling_field (plus possibly form) and
    # return TRUE if calling field is part of a search form. Otherwise return FALSE.

    return FALSE;
  }
  #-----------------------------------------------------

  function app_popup_get_decode_fieldname() {  # from application entity

    $fieldname = $this->proj_decode_fieldname_from_id_fieldname( $this->calling_field );
    return $fieldname;
  }
  #-----------------------------------------------------

  function app_popup_get_focus_fieldname() {  # from application entity

    $fieldname = $this->app_popup_get_decode_fieldname();
    return $fieldname;
  }
  #-----------------------------------------------------

  function app_popup_get_order_by_col() {  # from application entity


    if( $this->menu_method_name == 'select_by_first_letter_of_name' )
      return 'foaf_name';
    else
      return 'names_and_titles';
    return ;
  }
  #-----------------------------------------------------

  function app_popup_get_field_for_select_button() { # from application entity

    return 'iperson_id';
  }
  #-----------------------------------------------------

  function app_popup_add_record() {

    $this->person_entry_stylesheets();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_person' );

    $this->app_popup_read_calling_form_and_field();
    HTML::hidden_field( 'calling_form', $this->calling_form );
    HTML::hidden_field( 'calling_field', $this->calling_field );

    if( ! $this->failed_validation )
      $this->gender = 'M';  # for CofK default to male

    $this->person_entry_fields();

    HTML::submit_button( 'cancel_button', 'Cancel', $tabindex=1, $other_parms='onclick="self.close()"' );
    HTML::submit_button( 'save_button2', 'Save' );

    HTML::form_end();
  }
  #-----------------------------------------------------

  function save_person() {

    $iperson_id = $this->save_new_person();

    if( ! $iperson_id || $this->failed_validation ) {
      $this->continue_on_read_parm_err = TRUE;
      $this->suppress_read_parm_errmsgs = TRUE; # we have already displayed the messages once

      $columns = $this->db_list_columns( $this->proj_person_tablename());
      foreach( $columns as $crow ) {
        extract( $crow, EXTR_OVERWRITE );
        $this->$column_name = $this->read_post_parm( $column_name );
      }

      $this->app_popup_add_record();
      return;
    }

    $this->set_person( $iperson_id );

    echo $this->get_person_desc_from_id( $this->iperson_id, $using_integer_id = TRUE );  
    HTML::new_paragraph();
    HTML::italic_start();
    echo 'Click OK to pass the details back into the main data entry screen.';
    HTML::italic_end();
    HTML::new_paragraph();

    $this->calling_form = $this->read_post_parm( 'calling_form' );
    $this->calling_field = $this->read_post_parm( 'calling_field' );

    $this->app_popup_pass_value_back(); 
    HTML::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
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

        case 'iperson_id':
          if( $this->menu_method_name == 'app_popup_search_results' ) {
            $row[ 'column_label' ] = 'Select';
          }
          break;

        case 'person_id':
          $row[ 'searchable' ] = FALSE;  # we want to select ID and pass it back but never display it
          break;

        case 'mentioned':
          $row[ 'column_label' ] = 'Mention'; # just slightly more compact
          break;

        default:
          break;
      }

      $columns[] = $row;
    }
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

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'select_by_first_letter_of_name' );

    $this->app_popup_read_calling_form_and_field();
    HTML::hidden_field( 'calling_form', $this->calling_form );
    HTML::hidden_field( 'calling_field', $this->calling_field );

    $people_found = 0;

    #-----------------------------------------------------
    # Write out one button for each letter of the alphabet
    #-----------------------------------------------------
    $first_letter_of_name = $this->read_post_parm( 'first_letter_of_name' );
    $first_letter_of_name = $this->strip_all_slashes( $first_letter_of_name );

    HTML::hidden_field( 'first_letter_of_name', $first_letter_of_name );

    HTML::hidden_field( 'block_of_names_start', '' );  # Just a dummy field in most cases. 
                                                       # Only used if you need to sub-divide a longer list

    $script  = '  function chooseNewLetter( letterButton ) { '                                   . NEWLINE;
    $script .= "    document.$this->form_name.first_letter_of_name.value = letterButton.value; " . NEWLINE;
    $script .= "    document.$this->form_name.block_of_names_start.value = '';"                  . NEWLINE;
    $script .= '  } '                                                                            . NEWLINE;

    HTML::write_javascript_function( $script );

    HTML::new_paragraph();
    echo 'Click a letter to retrieve a list of people or groups with names beginning with that letter:';
    HTML::new_paragraph();

    $first_possible_letter = 'A';
    $letter_button_count = 26;

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $first_possible_letter = "'";   # the Arabic letter/phoneme 'AYN'.
      $letter_button_count++;
    }

    $letter = $first_possible_letter;

    for( $i = 0; $i < $letter_button_count; $i++ ) {
      if( $i == 13 ) HTML::new_paragraph();

      $character_desc = $letter;
      if( $character_desc == "'" ) $character_desc = 'ayn'; # Arabic phoneme represented in English by single quote
      $button_name = 'first_letter_of_name_button' . $character_desc;
      if( $letter == $first_possible_letter ) $focus_field = $button_name;

      $parms = 'class="all_letters';
      if( $letter == $first_letter_of_name ) $parms .= ' selected_letter';
      $parms .= '" ';

      $script = ' onclick="chooseNewLetter( this )" ';
      $parms .= $script;

      HTML::submit_button( $button_name, $letter, $tabindex = 2, $parms );
      echo ' ';

      if( $letter >= 'A' && $letter <= 'Z' )
        $letter++;
      else
        $letter = 'A';
    }


    #---------------------------------------
    # If a letter was chosen, select people 
    # with names beginning with that letter.
    #---------------------------------------
    if( $first_letter_of_name ) {
      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      $letter_value = $first_letter_of_name;
      if( $letter_value == "'" ) # Arabic phoneme represented in English by single quote
        $letter_value = "''";    # double up for use in an SQL statement
      

      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
        $statement  = 'select p.*, ';

        $statement .= "case when lower( foaf_name ) like 'al-%' then substr(foaf_name, 4) ";
        $statement .= "else foaf_name end as minus_al";

        $statement .= ' from ' . $this->from_table . ' p ';

        $statement .= " where (upper(foaf_name) like '" . $letter_value . "%' and upper(foaf_name) not like 'AL-%' )"
                   . " or (upper(foaf_name) like 'AL-" . $letter_value . "%')";

        $statement .= ' order by minus_al';
      }
      else {
        $statement  = 'select * from ' . $this->from_table;
        $statement .= " where upper( foaf_name ) like '" . $letter_value . "%'";
        $statement .= ' order by foaf_name';
      }


      $people = $this->db_select_into_array( $statement );
      $people_found = count( $people );
    }

    #-------------------------------------------
    # Display a dropdown list of selected people
    # (or a subset if list is too long
    #-------------------------------------------
    if( $first_letter_of_name && ! $people_found ) {
      echo "No people or groups found with names starting with '$first_letter_of_name'.";
      HTML::new_paragraph();
      HTML::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
      $focus_field = 'cancel_button';
    }

    elseif( ! $first_letter_of_name ) {
      HTML::new_paragraph();
      HTML::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
    }

    elseif( $people_found ) {

      $focus_field = 'person_sel';

      if( $people_found > POPUP_PERSON_MAX_DROPDOWN_OPTIONS ) {
        $people = $this->subdivide_within_first_letter_of_name( $people );
      }

      $this->write_passback_script();

      HTML::dropdown_start( 'person_sel', $label = NULL );
      $first = TRUE;
      foreach( $people as $row ) {
        $this->current_row_of_data = $row;
        extract( $row, EXTR_OVERWRITE );
        if( $first ) $selection = $person_id; # select first option initially

        HTML::dropdown_option( $person_id, $this->app_popup_set_result_text(), $selection );

        $selection = NULL;
        $first = FALSE;
      }
      HTML::dropdown_end();

      echo ' ';

      HTML::button( 'select_button', 'Select', $tabindex = 1, 'onClick="selectPerson()"' );
      HTML::button( 'cancel_button', 'Cancel', $tabindex=1, 'onclick="self.close()"' );
    }

    HTML::form_end();

    #----------------------------------------------------
    # Focus on letter 'A', dropdown list or Cancel button
    #----------------------------------------------------
    $script = "document.$this->form_name.$focus_field.focus();"  . NEWLINE;
    HTML::write_javascript_function( $script );

    #---------------------------------------
    # Provide normal 'Search' option as well
    #---------------------------------------
    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::form_start( $this->app_get_class( $this ), $this->app_popup_get_search_method() );

    HTML::hidden_field( 'calling_form', $this->calling_form );
    HTML::hidden_field( 'calling_field', $this->calling_field );

    echo 'Alternatively you can search using a more flexible set of criteria: ';
    HTML::submit_button( 'search_button', 'Search' );

    HTML::form_end();
  }
  #-----------------------------------------------------

  function write_passback_script() {

    $thisform = $this->form_name;

    $calling_form = $this->calling_form;
    $calling_field = $this->calling_field;

    $decode_field = $this->app_popup_get_decode_fieldname(); 
    $focus_field = $this->app_popup_get_focus_fieldname();

    $script  = '  function selectPerson() { '                                                      . NEWLINE;
    $script .= "    var selectedOptNo = document.$thisform.person_sel.selectedIndex;"              . NEWLINE;
    $script .= "    var selectedId = document.$thisform.person_sel.options[selectedOptNo].value;"  . NEWLINE;
    $script .= "    var selectedText = document.$thisform.person_sel.options[selectedOptNo].text;" . NEWLINE;
    $script .= "    opener.document.$calling_form.$calling_field.value = selectedId;"              . NEWLINE;
    $script .= "    opener.document.$calling_form.$decode_field.value = selectedText;"             . NEWLINE;
    $script .= "    opener.document.$calling_form.$decode_field.className = 'highlight2';"         . NEWLINE;
    $script .= "    var focusField = opener.document.$calling_form.$focus_field;"                  . NEWLINE;
    $script .= '    if( focusField != null ) { '                                                   . NEWLINE;
    $script .= '      focusField.focus();'                                                         . NEWLINE;
    $script .= '    } '                                                                            . NEWLINE;
    $script .= '    self.close();'                                                                 . NEWLINE;
    $script .= '  } '                                                                              . NEWLINE;

    HTML::write_javascript_function( $script );
  }
  #-----------------------------------------------------

  function subdivide_within_first_letter_of_name( $all_people ) {

    HTML::new_paragraph();

    $some_people = array();
    $all_people_count = count( $all_people );
    $last_record_in_all_people = $all_people_count - 1;

    $block_of_names_start = $this->read_post_parm( 'block_of_names_start' );

    if( ! $block_of_names_start ) $block_of_names_start = 0;

    $i = 0;
    $button_index = 0;
    $buttons = array();
    $first_name = '';
    $last_name = '';

    while( $i < $all_people_count ) {
      if( $i % POPUP_PERSON_MAX_DROPDOWN_OPTIONS == 0 ) {  # start of new block

        if( $i > 0 ) {  # finish off previous block before you start new one
          $row = $all_people[ $i - 1 ];
          extract( $row, EXTR_OVERWRITE );
          $last_name = $foaf_name;
          $buttons[ $button_index ]['last_name'] = $last_name;
        }

        $button_index = $i;
        $first_name = '';
        $last_name = '';

        $row = $all_people[ $i ];
        extract( $row, EXTR_OVERWRITE );
        $first_name = $foaf_name;
        $buttons[ $button_index ] = array( 'first_name' => $first_name,
                                           'last_name'  => $last_name );
      }

      $i += POPUP_PERSON_MAX_DROPDOWN_OPTIONS;
    }

    $row = $all_people[ $all_people_count - 1 ];
    extract( $row, EXTR_OVERWRITE );
    $last_name = $foaf_name;
    $buttons[ $button_index ]['last_name'] = $last_name;


    $script  = '  function chooseNewBlockOfNames( blockButton ) { '                             . NEWLINE;
    $script .= "    document.$this->form_name.block_of_names_start.value = blockButton.value;"  . NEWLINE;
    $script .= "    document.$this->form_name.submit();"                                        . NEWLINE;
    $script .= '  } '                                                                           . NEWLINE;

    HTML::write_javascript_function( $script );


    foreach( $buttons as $button_number => $names ) {

      $button_label = $names['first_name'] . ' --- ' . $names['last_name'];

      HTML::radio_button( 'block_of_names_start_selecter', $button_label, $value_when_checked = $button_number, 
                          $current_value = $block_of_names_start, $tabindex=2, $button_instance=$button_number, 
                          $script='onclick="chooseNewBlockOfNames( this )"' );
      echo LINEBREAK;
    }

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    for( $i = $block_of_names_start; $i < $block_of_names_start + POPUP_PERSON_MAX_DROPDOWN_OPTIONS; $i++ ) {
      $some_people[] = $all_people[ $i ];
      if( $i == $last_record_in_all_people ) break;
    }

    return $some_people;
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    switch( $column_name ) {

      case 'iperson_id':
        return '';

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function write_letter_selection_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' input.all_letters {'                                               . NEWLINE;
    echo '   font-color: ' . HTML::get_contrast1_colour() . ';'               . NEWLINE;
    echo '   background-color: ' .  HTML::header_background_colour() . ';'    . NEWLINE;
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
    echo '   margin-left: ' . POPUP_PERSON_FIELD_POS . 'px;'  . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' span.popupinputfield label {'                      . NEWLINE;
    echo '   width: ' . POPUP_PERSON_FIELD_LABEL_WIDTH . 'px;'. NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield label  {'                               . NEWLINE;
    echo '   width: ' . POPUP_PERSON_FIELD_LABEL_WIDTH . 'px;'. NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield input, .workfield textarea, .workfield select, span.workfieldaligned  {'           
                                                              . NEWLINE;
    echo '   margin-left: ' . POPUP_PERSON_FIELD_POS . 'px;'  . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' ul.calendartypes, ul.dateflags {'                  . NEWLINE;
    echo '   left: '. POPUP_PERSON_FIELD_POS . 'px; '         . NEWLINE;
    echo ' }';

    echo '</style>' . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function proj_extra_popup_buttons() {  

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) return;

    HTML::new_paragraph();
    HTML::span_start( 'class="workfieldaligned"' );

    $this->unknown_person_button();
    $this->multiple_people_buttons();
    $this->repeat_person_button();

    HTML::span_end();
  }
  #-----------------------------------------------------

  function unknown_person_button() {

    $statement = 'select person_id, foaf_name from ' . $this->proj_person_tablename()
               . " where foaf_name like 'Unknown%' order by foaf_name";

    $this->person_quick_selection_button( $statement, $button_label = 'Unknown' );
  }
  #----------------------------------------------------------------------------------

  function multiple_people_buttons() {

    $statement = 'select person_id, foaf_name from ' . $this->proj_person_tablename()
               . " where foaf_name like 'Multiple%' order by foaf_name";

    $this->person_quick_selection_button( $statement, $button_label = 'Multiple' );
  }
  #----------------------------------------------------------------------------------

  function person_quick_selection_button( $statement, $button_label = 'Quick select' ) {

    if( ! $statement ) return;

    $results = $this->db_select_into_array( $statement );
    if( count( $results ) < 1 ) return;

    $row = $results[0];
    extract( $row, EXTR_OVERWRITE );

    $calling_form = $this->calling_form;
    $calling_field = $this->calling_field;

    $decode_field = $this->app_popup_get_decode_fieldname(); 
    $focus_field = $this->app_popup_get_focus_fieldname();

    $funcname = 'quickSelect' . str_replace( ' ', '', $button_label ) . ucfirst( $calling_field );
    $button_name = $funcname . 'Button';

    $script  = "  function $funcname() { "                                                  . NEWLINE;
    $script .= "    var selectedId = '$person_id';"                                         . NEWLINE;
    $script .= "    var selectedText = '" . $this->escape( $foaf_name ) . "' ;"             . NEWLINE;
    $script .= "    document.$calling_form.$calling_field.value = selectedId;"              . NEWLINE;
    $script .= "    document.$calling_form.$decode_field.value = selectedText;"             . NEWLINE;
    $script .= "    document.$calling_form.$decode_field.className = 'highlight2';"         . NEWLINE;
    $script .= "  } "                                                                       . NEWLINE;

    HTML::write_javascript_function( $script );

    HTML::button( $button_name, $button_label, $tabindex = 1, 'onclick="' . $funcname . '()"' );
  }
  #----------------------------------------------------------------------------------

  function repeat_person_button() {

    if( ! $this->work_id ) return;  # should have been set by class 'Editable Work' in method 'person_entry_field()'

    $people = $this->get_existing_people_for_work( $this->work_id, $this->manifestation_ids );
    if( count( $people ) == 0 ) return;

    $href = $_SERVER[ 'PHP_SELF' ] 
            . '?class_name=popup_person'
            . '&method_name=existing_people_for_work_search_results'
            . '&calling_form=' . $this->calling_form
            . '&calling_field=' . $this->calling_field
            . '&work_id=' . $this->work_id
            . '&manifestation_ids=' . $this->manifestation_ids;
 
    HTML::button( $button_name = $this->calling_field . 'RepeatButton', 
                  $button_label = 'Repeat', 
                  $tabindex = 1, 
                  'onclick="searchWindow( ' . "'" . $href . "', 'repeatPersonWindow' )" . '"' );
  }
  #----------------------------------------------------------------------------------

  function get_existing_people_for_work( $work_id = NULL, $manif_string = NULL ) {

    if( ! $work_id ) $work_id = $this->read_post_parm( 'work_id' );
    if( ! $work_id ) return NULL;

    if( ! $manif_string ) $manif_string = $this->read_post_parm( 'manifestation_ids' );


    $workpeople = $this->rel_obj->get_other_side_for_this_on_both_sides( 
                                    $this_table = $this->proj_work_tablename(), 
                                    $this_id = $work_id, 
                                    $reltype = NULL, 
                                    $other_table = $this->proj_person_tablename());

    if( $manif_string ) {
      $manifs = explode( ',', $manif_string );
      foreach( $manifs as $manif ) {
        $manpeople = $this->rel_obj->get_other_side_for_this_on_both_sides( 
                                       $this_table = $this->proj_manifestation_tablename(), 
                                       $this_id = $manif, 
                                       $reltype = NULL, 
                                       $other_table = $this->proj_person_tablename());
        while( count( $manpeople ) > 0 ) {
          $row = array_shift( $manpeople );
          $workpeople[] = $row;
        }
      }
    }

    return $workpeople;
  }
  #----------------------------------------------------------------------------------

  function existing_people_for_work_search_results() {

    $this->calling_form = $this->read_get_parm( 'calling_form' );
    $this->calling_field = $this->read_get_parm( 'calling_field' );

    $work_id = $this->read_get_parm( 'work_id' );
    $manifestation_ids = $this->read_get_parm( 'manifestation_ids' );

    $results = $this->get_existing_people_for_work( $work_id, $manifestation_ids );
    if( count( $results ) == 0 ) return;

    echo 'You can use this screen to make a quick selection from any people or organisations already associated'
         . ' with the current work or any of its manifestations.';
    HTML::new_paragraph();
    HTML::button( 'cancel_repeat_selection_button', 'Cancel', 1, 'onclick="self.close()"' );
    HTML::new_paragraph();

    $person_ids = array();

    foreach( $results as $row ) {
      $person_id = $row[ 'other_id_value' ];
      if( ! in_array( $person_id, $person_ids ))
        $person_ids[] = $person_id;
    }

    $id_string = '';
    foreach( $person_ids as $person_id ) {
      if( $id_string > '' ) $id_string .= ', ';
      $id_string .= "'$person_id'";
    }
    $statement = 'select person_id, foaf_name from ' . $this->proj_person_tablename()
               . " where person_id in ( $id_string )"
               . ' order by foaf_name';
    $people = $this->db_select_into_array( $statement );

    HTML::table_start( 'class="datatab spacepadded"' );
    HTML::tablerow_start();
    HTML::column_header( 'Name' );
    HTML::column_header( 'Select' );
    HTML::tablerow_end();

    foreach( $people as $row ) {
      extract( $row, EXTR_OVERWRITE );
      HTML::tablerow_start();

      HTML::tabledata( $foaf_name );

      HTML::tabledata_start();
      $funcname = $this->write_passback_script_for_repeat_button( $person_id, $foaf_name );
      HTML::button( $button_name = $funcname . 'Button', 
                    $button_label = 'OK', 
                    $tabindex = 1, 
                    'onclick="' . $funcname . '()"' );
      HTML::tabledata_end();

      HTML::tablerow_end();
    }
    HTML::table_end();

    HTML::new_paragraph();
    HTML::button( 'cancel_repeat_selection_button2', 'Cancel', 1, 'onclick="self.close()"' );
  }
  #----------------------------------------------------------------------------------

  function write_passback_script_for_repeat_button( $person_id, $person_name ) {

    if( ! $person_id || ! $person_name ) return;

    $calling_form = $this->calling_form;
    $calling_field = $this->calling_field;

    $decode_field = $this->app_popup_get_decode_fieldname(); 
    $focus_field = $this->app_popup_get_focus_fieldname();

    $funcname = 'selectPersonID' . str_replace( '.', '_', $person_id );
    $funcname = str_replace( ':', '_', $funcname );
    $funcname = str_replace( '-', '_', $funcname );

    $script  = "  function $funcname() { "                                                         . NEWLINE;
    $script .= "    var selectedId = '$person_id';"                                                . NEWLINE;
    $script .= "    var selectedText = '" . $this->escape( $person_name ) . "';"                   . NEWLINE;
    $script .= "    opener.document.$calling_form.$calling_field.value = selectedId;"              . NEWLINE;
    $script .= "    opener.document.$calling_form.$decode_field.value = selectedText;"             . NEWLINE;
    $script .= "    opener.document.$calling_form.$decode_field.className = 'highlight2';"         . NEWLINE;
    $script .= "    var focusField = opener.document.$calling_form.$focus_field;"                  . NEWLINE;
    $script .= '    if( focusField != null ) {'                                                    . NEWLINE;
    $script .= '      focusField.focus();'                                                         . NEWLINE;
    $script .= '    }'                                                                             . NEWLINE;
    $script .= '    self.close();'                                                                 . NEWLINE;
    $script .= "  } "                                                                              . NEWLINE;

    HTML::write_javascript_function( $script );
    return $funcname;
  }
  #-----------------------------------------------------

  function set_work_id( $work_id = NULL  ) {
    $this->work_id = $work_id;
  }
  #----------------------------------------------------------------------------------

  function set_manifestation_ids( $manifestation_ids = NULL  ) {
    $this->manifestation_ids = $manifestation_ids;
  }
  #----------------------------------------------------------------------------------

  function put_al_after_surname( $name  ) {

    if( ! $this->string_starts_with( strtolower( $name ), 'al-' ))
      return $name;

    $parts = explode( ' ', $name );

    $first_part = $parts[ 0 ];
    $first_part = substr( $first_part, 3 );
    if( $this->string_ends_with( $first_part, ',' ))
      $first_part .= ' al-,';
    else
      $first_part .= ', al-';
    $parts[ 0 ] = $first_part;

    $name = implode( ' ', $parts );
    return $name;
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'first_letter_of_name':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
          if( $this->strip_all_slashes( $this->parm_value ) == "'" ) return TRUE;   # the Arabic letter/phoneme 'AYN'.
        }
        return $this->is_alphabetic_or_blank( $this->parm_value );

      case 'block_of_names_start':
        return $this->is_integer( $this->parm_value );

      case 'work_id':
        if( $this->parm_value == '' )
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      case 'manifestation_ids':
        if( $this->parm_value == '' )
          return TRUE;
        else
          return $this->is_comma_separated_html_id( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
