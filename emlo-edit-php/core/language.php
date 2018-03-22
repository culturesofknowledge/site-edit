<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'MAX_LENGTH_OF_LANGUAGE_NOTES', 100 );

class Language extends Project {
  #----------------------------------------------------------------------------------

  function Language( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->languages_in_use = array();

    $statement = 'select * from ' . $this->proj_favourite_language_tablename();
    $langs = $this->db_select_into_array( $statement );
    
    if( count( $langs ) > 0 ) {
      foreach( $langs as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $this->languages_in_use[] = $language_code;
      }
    }
  }

  #----------------------------------------------------------------------------------

  function clear() {

    $keep_languages_in_use = $this->languages_in_use;
    parent::clear();
    $this->languages_in_use = $keep_languages_in_use;
  }
  #----------------------------------------------------------------------------------

  function db_search( $table_or_view = NULL, $class_name = NULL, $method_name = NULL ) {

    $this->set_search_parms();
    parent::db_search( $this->from_table, $this->app_get_class( $this ), 'db_search_results' );
  }
  #-----------------------------------------------------
  
  function set_search_parms() {

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->write_post_parm( 'order_by', 'language_name' );

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->write_post_parm( 'entries_per_page', 100 );

    $this->from_table = $this->proj_language_viewname();

    $this->results_method = 'db_search_results';

    $this->set_default_simplified_search();
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    $this->search_method  = 'db_search';
    $this->results_method = 'db_search_results';
    $this->keycol         = 'language_id';
    $this->from_table     = $this->proj_language_viewname();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'language_name' );
  }
  #-----------------------------------------------------

  function set_default_simplified_search() {

    if( ! $this->parm_found_in_post( 'simplified_search' ) 
    &&  ! $this->parm_found_in_get(  'simplified_search' )) {
        $this->write_post_parm( 'simplified_search', 'Y' );  # default to simplified search
    }
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name ) {

    switch( $column_name ) {

      case 'code_639_3':
        return '3-letter language code';

      case 'code_639_1':
        return 'Alternative 2-letter code';

      case 'selected':
        return 'Favourite?';

      case 'language_id':  # this is a dummy column used purely for pagination purposes
        return '';
   
      default:
        return parent::db_get_default_column_label( $column_name );
    }
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
      extract( $row, EXTR_OVERWRITE );
      $skip_it = FALSE;

      # Set some columns as non-searchable
      switch( $column_name ) {
        case 'language_id':
          $row[ 'searchable' ] = FALSE;
          $skip_it = TRUE;
          break;

        default:
          break;
      }

      # Some columns should not appear in 'order by' dropdown list
      if( $this->getting_order_by_cols ) {  
        switch( $column_name ) {
          case 'language_id':
            $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      if( $skip_it ) continue;

      # Set search help
      switch( $column_name ) {

        case 'code_639_3':
          $search_help_text = 'E.g. eng for English';
          break;

        case 'code_639_1':
          $search_help_text = "E.g. en for English. Only the more widely-spoken languages have 2-letter codes."
                            . ' To get a list of languages with 2-letter codes, click the Advanced Search button, '
                            . " then choose 'Is not blank' from the dropdown list next to the '"
                            . $this->db_get_default_column_label( 'code_639_1' ) . "' field.";
          break;

        case 'language_name':
          $search_help_text = 'English, French, Arabic, etc. The name may be anglicised, e.g. Persian rather than Farsi.';
          break;

        case 'selected':
          $search_help_text = "<strong>The '" . $this->db_get_default_column_label( $column_name ) 
                            . " ' field contains the word 'Yes' if this language has been selected"
                            . ' for use in your project. Only selected languages will appear in data entry screens. <p>'
                            . ' </strong>For example, you might select'
                            . ' just English, French and Spanish from the thousands of potential languages listed here.'
                            . ' You will then be offered the choice of just French, English and Spanish if you need to'
                            . ' enter the language in which a work was composed. <p>'
                            . ' You can return to the list of languages'
                            . ' to make further selections whenever required, and your additional selections will then'
                            . ' appear within data entry screens elsewhere in the system.';
          break;

        default:
          break;
      }
      $row[ 'search_help_text' ] = $search_help_text;
      $row[ 'search_help_class' ] = $search_help_class;

      $columns[] = $row;
    }

    return $columns;
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'You can use this screen to search for, or browse through, all the languages defined by ISO '
         . '(the International Organization for Standardization). There are more than eight thousand of these languages.';
    HTML::new_paragraph();

    echo 'Simply click Search without entering a selection to browse through the whole list, page by page.';
    HTML::new_paragraph();

    echo 'Or enter part or all of a language name before clicking Search. For example, enter the word Greek'
         . ' to find both Ancient Greek and Modern Greek.';
    HTML::new_paragraph();

    echo  " Having found the languages you are interested in, you can mark them as 'favourites', "
         . ' so that only relevant languages appear in your data entry screens when entering details of'
         . ' the language of a work.';
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function db_get_possible_order_by_cols( $columns ) {

    $this->getting_order_by_cols = TRUE;
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of included and omitted columns
    $this->getting_order_by_cols = FALSE;
    return parent::db_get_possible_order_by_cols( $columns );
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {
  
    if( $column_name == 'language_id' ) {  # just a dummy column
      return '';
    }

    elseif( $column_name == 'selected' && $column_value && ! $this->printable_output && ! $this->csv_output ) {
      return '<strong>' . $column_value . '</strong>';
    }

    return parent::db_browse_reformat_data( $column_name, $column_value );
  }
  #-----------------------------------------------------

  function get_language_selection( $language_code ) {

    $selected = FALSE;
    if( in_array( $language_code, $this->languages_in_use )) $selected = TRUE;
    return $selected;
  }
  #-----------------------------------------------------

  function get_language_selection_desc( $language_code ) {

    $selected = $this->get_language_selection( $language_code );
    if( $selected )
      $desc = '*Selected for use*';
    else
      $desc = 'NOT selected for use';
    return $desc;
  }
  #-----------------------------------------------------

  function db_browse_plugin_2( $column_name = NULL, $column_value = NULL ) {

    if( $column_name != 'selected' ) return;
    if( $this->printable_output || $this->csv_output ) return;

    $language_code = $this->current_row_of_data[ 'code_639_3' ];
    $selected = $this->current_row_of_data[ 'selected' ];

    HTML::form_start( 'language', 'save_language' );

    if( $selected ) {
      $action = 'remove';
      $msg = 'Remove from favourites:';
    }
    else {
      $action = 'add';
      $msg = 'Add to favourites:';
    }

    HTML::hidden_field( 'code_639_3', $language_code );
    HTML::hidden_field( 'action', $action );

    HTML::italic_start();
    echo $msg;
    HTML::italic_end();
    HTML::submit_button( $action . '_' . $language_code . '_button', 'OK' );
    HTML::form_end();
  }
  #-----------------------------------------------------

  function save_language() {

    $language_code = $this->read_post_parm( 'code_639_3' );
    $action = $this->read_post_parm( 'action' );

    if( $action == 'add' )
      $statement = 'insert into ' . $this->proj_favourite_language_tablename()
                 . "(language_code) values ('$language_code')";
    else
      $statement = 'delete from ' . $this->proj_favourite_language_tablename()
                 . " where language_code = '$language_code'";
    $this->db_run_query( $statement );

    HTML::bold_start();
    echo 'Language selection has been saved.';
    HTML::bold_end();
    HTML::new_paragraph();

    $this->write_post_parm( 'text_query_op_code_639_3', 'equals' );
    $this->db_search_results();
  }
  #-----------------------------------------------------

  function language_entry_fields( $list_of_lang_codes, $langs_in_use ) {

    HTML::italic_start();
    echo 'If a language you require is not displayed below, you can add it to the list: ';
    HTML::italic_end();
    HTML::span_start( 'class="narrowspaceonleft"' );
    $this->link_to_language_search();
    HTML::span_end();
    HTML::new_paragraph();

    echo 'If the languages you want are already displayed below, tick one or more checkboxes to select'
         . ' as many as required, then click Save.';

    echo ' You can also enter very brief notes on the selected languages using the input fields below each checkbox';

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )   # IMPAcT want it as compact as possible
      echo '.';
    else {
      echo ', for example if you wanted to specify that a text is mainly in one language'
           . ' but there are just a few words in another language.';
    }

    echo LINEBREAK;
    HTML::submit_button( 'save_languages_button', 'Save' );
    HTML::new_paragraph();

    $langdata = $this->select_list_of_languages( $list_of_lang_codes );
    $langcount = count( $langdata );
    if( $langcount < 1 ) return;

    $cols_per_row = 4;
    $cells_printed = 0;
    $cells_to_print = $langcount;
    $remainder = $langcount % $cols_per_row;
    $cells_to_print -= $remainder;
    if( $remainder ) $cells_to_print += $cols_per_row;

    HTML::table_start( 'class="widelyspacepadded boxed"' );
    foreach( $langdata as $row ) {
      extract( $row, EXTR_OVERWRITE );

      if( $cells_printed % $cols_per_row == 0 ) HTML::tablerow_start();

      $selected = FALSE;
      $notes = '';
      foreach( $langs_in_use as $used ) {
        if( $used[ 'language_code' ] == $code_639_3 ) {
          $selected = TRUE;
          $notes = $used[ 'notes' ];
          break;
        }
      }

      $this->language_selection_field( $code_639_3, $language_name, $selected, $notes );

      $cells_printed++;
      if( $cells_printed % $cols_per_row == 0 ) HTML::tablerow_end();
    }

    if( $cells_printed < $cells_to_print ) {
      while( $cells_printed < $cells_to_print ) {
        HTML::tabledata();
        $cells_printed++;
      }
      HTML::tablerow_end();
    }
    HTML::table_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function language_selection_field( $language_code, $language_name, $selected, $notes ) {

    $checkbox_field = 'language_chkbox_' . $language_code;
    $notes_field    = 'language_notes_'  . $language_code;
    $cell_id        = 'language_td_'     . $language_code;

    $selected_class = 'bold highlight2';
    $unselected_class = '';
    if( $selected )
      $current_class = $selected_class;
    else
      $current_class = $unselected_class;
    $parms = 'id="' . $cell_id . '" class="' . $current_class . '"';
    $change_display_scriptname = 'change_display_of_' . $cell_id;

    HTML::tabledata_start( $parms );

    $script = "function $change_display_scriptname( chkbox ) {"               . NEWLINE
            . "  var theCell = document.getElementById( '$cell_id' );"        . NEWLINE
            . '  if( chkbox.checked ) {'                                      . NEWLINE
            . "    theCell.className = '$selected_class';"                    . NEWLINE
            . '  }'                                                           . NEWLINE 
            . '  else {'                                                      . NEWLINE
            . "    theCell.className = '$unselected_class';"                  . NEWLINE
            . '  }'                                                           . NEWLINE 
            . '}'                                                             . NEWLINE;
    HTML::write_javascript_function( $script );

    $parms = 'onclick="' . $change_display_scriptname . '( this )"';
           
    HTML::checkbox( $checkbox_field, $label = $language_name, $is_checked = $selected, 
                    $value_when_checked = $language_code, $in_table = FALSE,
                    $tabindex=1, $input_instance = NULL, $parms );

    echo LINEBREAK;

    HTML::input_field( $notes_field,  $label = '', $notes ); 
    HTML::tabledata_end();
  }
  #----------------------------------------------------------------------------------

  function select_list_of_languages( $list_of_lang_codes ) {

    $langdata = array();
    if( count( $list_of_lang_codes ) < 1 ) return $langdata;

    $i = 0;
    $langstring = '';
    foreach( $list_of_lang_codes as $lang ) {
      $i++;
      if( $i > 1 ) $langstring .= ', ';
      $langstring .= "'$lang'";
    }

    $statement = "select * from iso_639_language_codes where code_639_3 in ( $langstring ) order by language_name";
    $langdata = $this->db_select_into_array( $statement );
    return $langdata;
  }
  #-----------------------------------------------------

  function link_to_language_search() {  # allow user to add more languages to 'favourites'

    $title = 'Add further possible languages';

    $menu = new Menu( $this->db_connection, $item_id = NULL, $class_name = 'language', $method_name = 'db_search' );
    $menu_item_id = $menu->get_menu_item_id();
    if( ! $menu_item_id ) return;

    $href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $menu_item_id;

    HTML::link_start( $href, $title );
    echo $title;
    HTML::link_end();
  }
  #-----------------------------------------------------

  function get_languages_of_text( $object_type, $id_value ) {

    if((  $object_type != 'work' && $object_type != 'manifestation' ) || ! $id_value ) 
      die( 'Invalid input when getting languages.' );

    $func = 'proj_' . $object_type . '_tablename';
    $primary_table = $this->$func();
    $primary_key = $this->proj_primary_key( $primary_table );

    $func = 'proj_language_of_' . $object_type . '_tablename';
    $lang_table = $this->$func();

    $statement = "select * from $lang_table where $primary_key = '$id_value' order by language_code";
    $langs = $this->db_select_into_array( $statement );
    return $langs;
  }
  #-----------------------------------------------------

  function read_languages_of_text_from_post() {

    $language_codes = array();

    foreach( $_POST as $postkey => $postval ) {
      if( $this->string_starts_with( $postkey, 'language_chkbox_' )) {
        $lang = $this->read_post_parm( $postkey );
        $note = $this->read_post_parm( str_replace( 'chkbox', 'notes', $postkey ));
        if( strlen( $note ) > MAX_LENGTH_OF_LANGUAGE_NOTES ) 
          $note = substr( $note, 0, MAX_LENGTH_OF_LANGUAGE_NOTES );
        $language_codes[ "$lang" ] = $note;
      }
    }

    return $language_codes;
  }
  #-----------------------------------------------------

  function save_languages_of_text( $table_name, $keycol, $id_value ) {
    if( ! $table_name || ! $keycol || ! $id_value ) die( 'Invalid input when saving languages.' );

    $language_codes = $this->read_languages_of_text_from_post();
    $langstring = '';

    # Insert and update as required
    foreach( $language_codes as $lang => $note ) {
      if( $langstring > '' ) $langstring .= ', ';
      $langstring .= "'$lang'";

      $statement = "select language_code from $table_name where $keycol = '$id_value' and language_code = '$lang'";
      $exists = $this->db_select_one_value( $statement );
      if( ! $exists ) {
        $statement = "insert into $table_name( $keycol, language_code, notes ) values ( '$id_value', '$lang', "
                   . "'" . $this->escape( $note ) . "' )";
        $this->db_run_query( $statement );
      }
      else {
        $statement = "update $table_name set notes = '" . $this->escape( $note ) . "'"
                   . " where $keycol = '$id_value' and language_code = '$lang' "
                   . " and coalesce( notes, '' ) != '" . $this->escape( $note ) . "'";
        $this->db_run_query( $statement );
      }
    }

    # Delete any that have been removed from the list
    $statement = "delete from $table_name where $keycol = '$id_value'";
    if( $langstring > '' ) $statement .= " and language_code not in ( $langstring )";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------
  function desc_dropdown( $form_name, $field_name = NULL, $copy_field = NULL, $field_label = NULL,
                          $in_table=FALSE, $override_blank_row_descrip = NULL ) {

    $lookup = new Lookup_Table( $this->db_connection, 
                                $lookup_table_name = $this->proj_favourite_language_viewname(), 
                                $id_column_name = 'language_id', 
                                $desc_column_name = 'language_name'); 

    $lookup->desc_dropdown( $form_name, $field_name, $copy_field, $field_label, $in_table, $override_blank_row_descrip );
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'language_id':
        return $this->is_integer( $this->parm_value );

      case 'code_639_3':
      case 'code_639_1':
      case 'selected':
        return $this->is_alphanumeric_or_blank( $this->parm_value );

      case 'language_name':
        return $this->is_ok_free_text( $this->parm_value );
        
      case 'action':
        if( $this->parm_value == 'add' || $this->parm_value == 'remove' )
          return TRUE;
        else
          return FALSE;

      default:
        $fieldstarts = array( 'language_chkbox_', 'language_notes_' );
        foreach( $fieldstarts as $fieldstart ) {
          if( $this->string_starts_with( $parm_name, $fieldstart )) {
            $the_rest = substr( $parm_name, strlen( $fieldstart ));
            if( $this->is_alphanumeric( $the_rest )) {  # e.g. 'language_chkbox_ara' for Arabic
              if( $fieldstart == 'language_chkbox_' ) {
                if( $this->parm_value == $the_rest ) return TRUE; # e.g. the Arabic checkbox has value 'ara'
              }
              elseif( $fieldstart == 'language_notes_' ) {
                return $this->is_ok_free_text( $this->parm_value );
              }
            }
          }
        }

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
