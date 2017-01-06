<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


class Contributor extends Project {
  #----------------------------------------------------------------------------------

  function Contributor( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function db_search( $table_or_view, $class_name = NULL, $method_name = NULL ) {

    $this->set_search_parms();
    parent::db_search( $this->from_table, $this->app_get_class( $this ), 'db_search_results' );
  }
  #-----------------------------------------------------
  
  function set_search_parms() {

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->write_post_parm( 'order_by', 'tool_user_email' );

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->write_post_parm( 'entries_per_page', 100 );

    $this->from_table = $this->proj_collect_tool_user_tablename();

    $this->results_method = 'db_search_results';

    $this->set_default_simplified_search();
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    $this->search_method  = 'db_search';
    $this->results_method = 'db_search_results';
    $this->keycol         = 'tool_user_id';
    $this->from_table     = $this->proj_collect_tool_user_tablename();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'tool_user_email' );
  }
  #-----------------------------------------------------

  function set_default_simplified_search() {

    if( ! $this->parm_found_in_post( 'simplified_search' ) 
    &&  ! $this->parm_found_in_get(  'simplified_search' )) {
        $this->write_post_parm( 'simplified_search', 'Y' );  # default to simplified search
    }
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {
  
    if( $column_name == 'tool_user_email' && $column_value && ! $this->printable_output && ! $this->csv_output ) {
      html::link( $href = "mailto:$column_value", 
                  $displayed_text = $column_value, 
                  $title = 'Contact the contributor', 
                  $target = '_blank' );
      return '';
    }
    return parent::db_browse_reformat_data( $column_name, $column_value );
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
        case 'tool_user_pw':
        case 'tool_user_id':
          $row[ 'searchable' ] = FALSE;
          $skip_it = TRUE;  # 
          break;

        default:
          break;
      }

      if( $skip_it ) continue;

      # Set search help
      switch( $column_name ) {

        case 'tool_user_email':
          $search_help_text = 'Email address of contributor'
                            . ' (this is also their username within the data collection tool).';
          break;

        case 'tool_user_forename':
          $search_help_text = "Contributor's forename.";

        case 'tool_user_surname':
          $search_help_text = "Contributor's surname.";

      }
      $row[ 'search_help_text' ] = $search_help_text;
      $row[ 'search_help_class' ] = $search_help_class;

      $columns[] = $row;
    }

    return $columns;
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'You can use this screen to search for, or browse through, all the contributors who have registered'
         . ' to use the offline data collection tool but who do not have a full login to EMLOedit.';
    html::new_paragraph();

    echo 'Simply click Search without entering a selection to browse through the whole list, page by page.';
    html::new_paragraph();
  }
  #-----------------------------------------------------

  function db_get_possible_order_by_cols( $columns ) {

    $this->getting_order_by_cols = TRUE;
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of included and omitted columns
    $this->getting_order_by_cols = FALSE;
    return parent::db_get_possible_order_by_cols( $columns );
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name ) {

    switch( $column_name ) {

      case 'contributor_id':
        return 'Contributor ID';

      case 'tool_user_email':
        return 'Email address';

      case 'tool_user_surname':
        return 'Surname';

      case 'tool_user_forename':
        return 'Forename';

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'tool_user_id':
        return $this->is_integer( $this->parm_value );

      case 'tool_user_email':
        return $this->is_email_address( $this->parm_value );

      case 'tool_user_surname':
      case 'tool_user_forename':
        return $this->is_ok_free_text( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
