<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose or add person.
#====================================================================================

class Popup_Manifestation extends Manifestation {

  #----------------------------------------------------------------------------------

  function Popup_Manifestation( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent CofK methods.
    #-----------------------------------------------------
    $this->Manifestation( $db_connection );

    $this->is_impact = FALSE;
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->is_impact = TRUE;
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

    #-----------------------------------------------------------------------------
    # In IMPAcT, you may need to check for manifestations of type 'composite'
    # if we are talking about several MSS getting bound together into an anthology
    #-----------------------------------------------------------------------------
    if( $this->am_looking_for_anthology())
      return $this->get_collection_setting( 'composite_manif_selection_view' );

    return $this->get_collection_setting( 'manifestation_selection_view' );
  }
  #-----------------------------------------------------

  function app_popup_set_result_id() {  # from application entity

    $this->app_popup_result_id = $this->current_row_of_data[ 'manifestation_id' ];  
    return $this->app_popup_result_id;
  }
  #-----------------------------------------------------

  function app_popup_set_result_text() {

    if( $this->is_impact ) {
      $manifestation_id = $this->current_row_of_data[ 'manifestation_id' ];  
      $func = $this->proj_database_function_name( 'decode_manifestation', TRUE );
      $this->app_popup_result_text = $this->db_select_one_value( "select $func ( '$manifestation_id', 1 )" ); 
    }
    else {
      $this->app_popup_result_text = $this->current_row_of_data[ 'description' ]  . ' -- '
                                   . $this->current_row_of_data[ 'manifestation_type' ] . ' --  ' 
                                   . $this->current_row_of_data[ 'id_number_or_shelfmark' ];
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

    return 'date_of_work_std';
  }
  #-----------------------------------------------------

  function app_popup_get_field_for_select_button() { # from application entity

    return 'relationship_id';
  }
  #-----------------------------------------------------

  function app_popup_add_record() {

    html::new_paragraph();
    echo "'Add work' menu option still under construction.";
    html::new_paragraph();
    $this->close_self_button();
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    parent::db_set_search_result_parms();

    # Override some of the parameters
    $this->from_table     = $this->app_popup_get_search_table();
    $this->keycol         = 'relationship_id';  # not actually a 'manifestation' key column, but using it here 
                                                # because we need a unique integer value for pagaination
    $this->search_method  = $this->app_popup_get_search_method();
    $this->results_method = $this->app_popup_get_search_results_method();

    $this->write_post_parm( 'order_by', $this->app_popup_get_order_by_col() );
  }
  #-----------------------------------------------------

  function db_list_columns(  $table_or_view = NULL ) {  # overrides parent class

    if( $this->is_impact ) {
      if( ! $this->impt_manif_obj ) $this->impt_manif_obj = new Impt_Manifestation( $this->db_connection );
      $this->impt_manif_obj->entering_selection_criteria = $this->entering_selection_criteria;
      $this->impt_manif_obj->reading_selection_criteria = $this->reading_selection_criteria;
      $rawcols = $this->impt_manif_obj->db_list_columns( $table_or_view );
    }
    else
      $rawcols = parent::db_list_columns( $table_or_view );

    if( ! is_array( $rawcols )) return NULL;

    $columns = array();
    foreach( $rawcols as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $skip_it = FALSE;

      if( ! $this->is_impact ) {
        $row[ 'search_help_text' ] = NULL;
        $row[ 'search_help_class' ] = NULL;
      }

      #------------------
      # Set column labels
      #------------------
      switch( $column_name ) {

        case 'relationship_id':
         $row[ 'searchable' ] = FALSE;
          if( $this->menu_method_name == 'app_popup_search_results' ) {
            $row[ 'column_label' ] = 'Select';
          }
          break;

        case 'work_id':
          $row[ 'searchable' ] = FALSE;
          break;

        case 'manifestation_id':
          if( ! $this->is_impact )
            $skip_it = TRUE;
          break;

        case 'description':
          $row[ 'column_label' ] = 'Brief details of work';
          if( $this->is_impact ) {
            $row[ 'search_help_text' ] = 'This field contains title, type and author of work if known.';
            break;
          }
          
          $row[ 'search_help_text' ] = "This is in the style 'DD Mon YYYY: Author/Sender to Addressee',"
                                     . ' e.g. 8 Mar 1693: Bulkeley, Sir Richard to Lister, Martin.';
          break;

        case 'id_number_or_shelfmark':
          if( ! $this->is_impact )
            $row[ 'column_label' ] = 'ID number or shelfmark';
          break;

        case 'manifestation_type':
          $row[ 'column_label' ] = 'Document type';
          $row[ 'search_help_class' ] = 'document_type';
          break;

        case 'iwork_id':
          $row[ 'column_label' ] = 'Work ID';
          if( $this->is_impact ) {
            if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) 
              $skip_it = TRUE;  # no need to waste space on work ID because we have manifestation ID displayed.
          }
          break;


        default:
          break;
      }
      if( $skip_it ) continue;

      $columns[] = $row;
    }
    return $columns;
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name ) {

    if( $this->is_impact ) {
      if( ! $this->impt_manif_obj ) $this->impt_manif_obj = new Impt_Manifestation( $this->db_connection );
      return $this->impt_manif_obj->db_get_default_column_label( $column_name );
    }

    switch( $column_name ) {
      case 'work_id':
      case 'iwork_id':
        return 'Work ID';

      case 'manifestation_id':
        return 'ID';

      case 'date_of_work_std': 
        return 'Date of work';

      case 'id_number_or_shelfmark':
        return 'Shelfmark';

      case 'manifestation_type':
        return 'Document type';

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'Enter some details of the required work/manifestation (e.g. name of author)'
         . ' and click Search or press Return.';
    html::new_paragraph();

    echo "Please note that you can use the wildcard '%' (percent sign) to stand for any number of characters. ";
    html::new_paragraph();

    if( $this->is_impact ) {
      if( $this->am_looking_for_anthology()) {
        html::bold_start();
        echo "As you are looking for a codex or anthology, only manifestations of type 'Composite'"
             . ' will be retrieved.';
        html::bold_end();
        html::new_paragraph();
      }
      return;
    }

    echo 'For example, you could enter ';
    html::bold_start();
    echo ' 1697%Deer%Lhwyd ';
    html::bold_end();
    echo " to retrieve '7 Apr 1697: Deer, Richard to Lhwyd, Edward'."; 
    html::new_paragraph();

    echo 'Or you could enter ';
    html::bold_start();
    echo ' 15 Apr ';
    html::bold_end();
    echo " to find all letters dated 15th April of any year."; 
    html::new_paragraph();

    echo 'Please be aware that only the ';
    html::italic_start();
    echo 'primary';
    html::italic_end();
    echo ' name of a correspondent is given here in the brief details of a work. For example, Lhwyd not Lhuyd, '
         . ' Lister not Lyster';
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
        case 'manifestation_id':
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

      case 'relationship_id':
        if( $this->is_impact ) 
          return $this->current_row_of_data[ 'manifestation_id' ];
        else
          return '';

      default:
        return parent::db_browse_reformat_data( $column_name, $column_value );
    }
  }
  #-----------------------------------------------------

  function am_looking_for_anthology() {

    if( $this->is_impact ) {
      $anthology = $this->proj_new_id_fieldname_from_fieldset_name( 'enclosing_this' );
      if( $this->calling_field == $anthology ) {
        return TRUE;
      }
    }

    return FALSE;
  }
  #-----------------------------------------------------

  function get_manifestation_desc( $manifestation_id ) {

    if( $this->is_impact ) {
      $function_name = $this->proj_database_function_name( 'decode_manifestation', $include_collection_code = TRUE );
      $statement = "select $function_name ( '$manifestation_id' )";
      $decode = $this->db_select_one_value( $statement );
      return $decode;
    }
    else {
      return parent::get_manifestation_desc( $manifestation_id );
    }

  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'manifestation_type':
      case 'manif_incipit_explicit_colophon':
      case 'creators_searchable':
      case 'creators_for_display':
      case 'repository':
      case 'work_title':
        return $this->is_ok_free_text( $this->parm_value );

      case 'is_composite_document':
        return $this->is_on_off_switch( $this->parm_value );

      case 'date_of_work_std':
      case 'date_of_work_std2':
      case 'christian_date':
      case 'christian_date2':
      case 'manifestation_creation_date':
      case 'manifestation_creation_date2':
      case 'manifestation_creation_date_gregorian':
      case 'manifestation_creation_date_gregorian2':
        if( $this->is_impact ){  # allow 3-figure years
          if( strlen( $this->parm_value ) == strlen( 'yyy' ) && $this->is_integer( $this->parm_value )) {
            $this->parm_value = '0' . $this->parm_value;
            $this->write_post_parm( $parm_name, $this->parm_value );
            $date_entity = new Islamic_Date_Entity( $this->db_connection );
            $this->parm_value = $date_entity->yyyy_to_dd_mm_yyyy( $parm_name, $this->parm_value );
            return $date_entity->is_dd_mm_yyyy( $this->parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE );
          }
        }
        $this->parm_value = $this->yyyy_to_dd_mm_yyyy( $parm_name, $this->parm_value );
        return $this->is_dd_mm_yyyy( $this->parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE );

        if( $this->reading_parms_for_update ) {
          $parm_value = $this->parm_value;
          if( $this->string_starts_with( $parm_value, '9999' )) # Postgres timestamp check won't allow this year
            $parm_value = '1000' . substr( $parm_value, 4 );
          return $this->is_postgres_timestamp( $parm_value );
        }

        $this->parm_value = $this->yyyy_to_dd_mm_yyyy( $parm_name, $this->parm_value );
        return $this->is_dd_mm_yyyy( $this->parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
