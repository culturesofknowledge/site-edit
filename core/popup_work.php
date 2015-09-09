<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose work.
#====================================================================================

class Popup_Work extends Editable_Work {

  #----------------------------------------------------------------------------------

  function Popup_Work( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent CofK methods.
    #-----------------------------------------------------
    $this->Editable_Work( $db_connection );

    $this->is_impact = FALSE;
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->is_impact = TRUE;
      $work_class = PROJ_COLLECTION_WORK_CLASS;
      $this->impact_work_obj = new $work_class ( $this->db_connection );
    }
  }
  #----------------------------------------------------------------------------------

  function clear() {
    parent::clear();

    $this->is_impact = FALSE;
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      $this->is_impact = TRUE;
  }
  #----------------------------------------------------------------------------------
  # Override method from Project so you go into search screen not results screen

  function proj_initial_popup_selection_method() {

    return $this->app_popup_get_search_method();
  }
  #----------------------------------------------------------------------------------

  function app_popup_get_search_table() {  # from application entity

    return $this->get_collection_setting( 'work_selection_view' );
  }
  #-----------------------------------------------------

  function app_popup_set_result_id() {  # from application entity

    $this->app_popup_result_id = $this->current_row_of_data[ 'work_id' ];  
    return $this->app_popup_result_id;
  }
  #-----------------------------------------------------

  function app_popup_set_result_text() {

    $this->app_popup_result_text = $this->current_row_of_data[ 'description' ];
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

    return 'date_of_work_std';
  }
  #-----------------------------------------------------

  function app_popup_get_field_for_select_button() { # from application entity

    return 'iwork_id';
  }
  #-----------------------------------------------------

  function app_popup_add_record() {

    html::new_paragraph();
    echo "'Add work' menu option still under construction.";
    html::new_paragraph();
    $this->close_self_button();
  }
  #-----------------------------------------------------

  function get_search_table() { # override parent method from 'Work'
    return $this->app_popup_get_search_table();
  }
  #-----------------------------------------------------

  function get_results_method() { # override parent method from 'Work'
    return $this->app_popup_get_search_results_method();
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    parent::db_set_search_result_parms();

    # Override some of the parameters
    $this->from_table     = $this->app_popup_get_search_table();
    $this->keycol         = 'iwork_id';  # using iwork id here because we need a unique integer value for pagaination
    $this->search_method  = $this->app_popup_get_search_method();
    $this->results_method = $this->app_popup_get_search_results_method();
    $this->edit_method    = NULL;

    $this->write_post_parm( 'order_by', $this->app_popup_get_order_by_col() );
  }
  #-----------------------------------------------------

  function db_list_columns(  $table_or_view = NULL ) {  # overrides parent class

    if( $this->is_impact ) {
      $this->impact_work_obj->entering_selection_criteria = $this->entering_selection_criteria;
      $this->impact_work_obj->reading_selection_criteria = $this->reading_selection_criteria;
      $rawcols = $this->impact_work_obj->db_list_columns( $table_or_view );
    }
    else
      $rawcols = parent::db_list_columns( $table_or_view );
    if( ! is_array( $rawcols )) return NULL;

    $columns = array();
    foreach( $rawcols as $row ) {

      extract( $row, EXTR_OVERWRITE );
      $skip_it = FALSE;

      #------------------
      # Set column labels
      #------------------
      switch( $column_name ) {

        case 'work_id':
          $row[ 'searchable' ] = FALSE;
          $row[ 'column_label' ] = 'Select';
          if( $this->is_impact ) $skip_it = TRUE;
          break;

        case 'description':
          $row[ 'column_label' ] = 'Brief details of work';
          $row[ 'search_help_text' ] = $this->get_search_help_text( $column_name );
          if( $this->is_impact ) $skip_it = TRUE;
          break;

        case 'manifestations_searchable':
          $row[ 'section_heading' ] = 'Manifestations';

        default:
          break;
      }
      if( $skip_it ) continue;

      $columns[] = $row;
    }

    return $columns;
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    if( $this->is_impact ) {
      echo 'Enter selection in one or more fields and click the Search button or press the Return key.';
      html::new_paragraph();
      return;
    }

    echo 'Enter some details of the required work (e.g. date of work or name of correspondent)'
         . ' and click Search or press Return.';
    html::new_paragraph();

    echo "Please note that you can use the wildcard '%' (percent sign) to stand for any number of characters. ";
    #html::new_paragraph();

    echo 'For example, you could enter ';
    html::bold_start();
    echo ' 1697%Deer%Lhwyd ';
    html::bold_end();
    echo " to retrieve '7 Apr 1697: Deer, Richard to Lhwyd, Edward'. "; 
    #html::new_paragraph();

    echo 'Or you could enter ';
    html::bold_start();
    echo ' 15 Apr ';
    html::bold_end();
    echo " to find all letters dated 15th April of any year. "; 
    #html::new_paragraph();

    echo 'Please be aware that only the ';
    html::italic_start();
    echo 'primary';
    html::italic_end();
    echo ' name of a correspondent is given here in the brief details of a work. For example, Lhwyd not Lhuyd, '
         . ' Lister not Lyster.';
    html::new_paragraph();

    echo 'The list will be sorted by date of work, with undated works at the end.';
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function db_browse_across_page( $details, $columns ) {

    $displaycols = array();

    foreach( $columns as $crow ) {
      $column_name = $crow[ 'column_name' ];
      switch( $column_name ) {
        case 'work_id':
          $skip_it = TRUE;  # We want to select ID and pass it back, but not display it.
          break;
        default:
          $skip_it = FALSE;
      }
      if( $skip_it ) continue;
      $displaycols[] = $crow;
    }

    DBEntity::db_browse_across_page( $details, $displaycols );
  }
  #-----------------------------------------------------
  function db_browse_reformat_data( $column_name, $column_value ) {

    switch( $column_name ) {

      case 'date_of_work_std':
        $column_value = $this->postgres_date_to_dd_mm_yyyy( $the_datetime = $column_value );
        break;

      default:
        $column_value = parent::db_browse_reformat_data( $column_name, $column_value );
        break;
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name = NULL ) {

    if( $this->is_impact ) 
      return $this->impact_work_obj->db_get_default_column_label( $column_name );
    else
      return parent::db_get_default_column_label( $column_name );
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'date_of_work_std':
      case 'date_of_work_std2':
      case 'christian_date':
      case 'christian_date2':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {  # allow 3-figure years
          if( strlen( $this->parm_value ) == strlen( 'yyy' ) && $this->is_integer( $this->parm_value )) {
            $this->parm_value = '0' . $this->parm_value;
            $this->write_post_parm( $parm_name, $this->parm_value );
          }
        }
        return parent::validate_parm( $parm_name );

      case 'description':
      case 'people_and_places_associated_with_manifestations':
        return $this->is_ok_free_text( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
