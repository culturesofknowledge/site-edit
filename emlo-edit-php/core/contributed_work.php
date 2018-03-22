<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


class Contributed_Work extends Project {
  #----------------------------------------------------------------------------------

  function Contributed_Work( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function db_search( $table_or_view = NULL, $class_name = NULL, $method_name = NULL ) {

    $this->set_search_parms();
    parent::db_search( $this->from_table, $this->app_get_class( $this ), 'db_search_results' );
  }
  #-----------------------------------------------------
  
  function set_search_parms() {

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->write_post_parm( 'order_by', 'union_iwork_id' );

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->write_post_parm( 'entries_per_page', 100 );

    $this->from_table = $this->proj_collect_work_summary_viewname();

    $this->results_method = 'db_search_results';

    $this->set_default_simplified_search();
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() { 

    $this->search_method  = 'db_search';
    $this->results_method = 'db_search_results';
    $this->keycol         = 'contributed_work_id';
    $this->from_table     = $this->proj_collect_work_summary_viewname();

    if( ! $this->parm_found_in_post( 'order_by' )) 
      $this->write_post_parm( 'order_by', 'contributed_work_id' );
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
  
    if( $column_name == 'uploader_email' && $column_value && ! $this->printable_output && ! $this->csv_output ) {
      HTML::link( $href = "mailto:$column_value", 
                  $displayed_text = 'Contact', 
                  $title = 'Contact the contributor', 
                  $target = '_blank' );
      return '';
    }
    return parent::db_browse_reformat_data( $column_name, $column_value );
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name ) {

    switch( $column_name ) {

      case 'union_iwork_id':
        return 'ID in main database';

      case 'work_id_in_tool':
        return 'Work ID in tool';

      case 'upload_id':
        return 'Upload ID';

      case 'contributed_work_id':
        return 'Upload no. / ID in tool';
   
      case 'status_desc':
        return 'Status of work';

      case 'uploader_email':
        return 'Contact';

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
        case 'contributed_work_id':
          $row[ 'searchable' ] = FALSE;
          $skip_it = TRUE;  # 
          break;

        default:
          break;
      }

      if( $skip_it ) continue;

      # Set search help
      switch( $column_name ) {

        case 'uploader_email':
          $search_help_text = 'Email address of contributor.';
          break;

        case 'source_of_data':
          $search_help_text = 'The name of the researcher who contributed the data, followed by the date and time'
                            . ' when the contribution was uploaded.';
          break;

        case 'status_desc':
          $search_help_text = "'Awaiting review', 'Accepted and saved into main database' or 'Rejected'.";
          break;

        case 'union_iwork_id':
          $search_help_text = 'Unique work ID as saved in EMLO-edit. Blank for works which have not been accepted.';
          break;

        case 'date_of_work':
          $search_help_text = 'A text field, normally in YYYY-MM-DD format, but with unknown parts of the date'
                            . " replaced by question marks, e.g. '1659-09-??'. Occasionally this field will hold"
                            . " a date range, e.g. '1659-09-15 to 1659-09-16'.";
          break;

        case 'original_calendar':
          $search_help_text = "'Julian', 'Gregorian' or 'Unknown'";
          break;

        case 'authors':
        case 'addressees':
        case 'people_mentioned':
          $search_help_text = "Names normally in 'surname, forename' format. The ID numbers of pre-existing people"
                            . " from the main database are given in square brackets after the name, e.g."
                            . "'Postlethwayt, John, 1650-1713 [ID 21175]'.";
          break;

        case 'origin':
        case 'destination':
        case 'places_mentioned':
          $search_help_text = "Placenames in standard modern spellings. The ID numbers of pre-existing places"
                            . " from the main database are given in square brackets after the name, e.g."
                            . "'Oxford, Oxfordshire, England [ID 400014]'.";
          break;

        case 'manifestations':
          $search_help_text = 'Document type, repository name and shelfmark or printed edition details.';
          break;

        case 'issues':
          $search_help_text = "This field flags up issues such as 'Author uncertain' or 'Addressee inferred'.";
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

    echo 'You can use this screen to search for, or browse through, all the works uploaded from the offline data'
         . ' collection tool, whether accepted, rejected or still awaiting review.';
    HTML::new_paragraph();

    echo 'Simply click Search without entering a selection to browse through the whole list, page by page.';
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

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'contributed_work_id':
      case 'upload_id':
      case 'work_id_in_tool':
      case 'union_iwork_id':
        return $this->is_integer( $this->parm_value );

      case 'source_of_data':
      case 'notes_on_letter':
      case 'date_of_work':
      case 'date_of_work_as_marked':
      case 'original_calendar':
      case 'notes_on_date_of_work':
      case 'authors':
      case 'authors_as_marked':
      case 'notes_on_authors':
      case 'addressees':
      case 'addressees_as_marked':
      case 'notes_on_addressees':
      case 'destination':
      case 'destination_as_marked':
      case 'origin':
      case 'origin_as_marked':
      case 'abstract':
      case 'keywords':
      case 'languages_of_work':
      case 'subjects_of_work':
      case 'incipit':
      case 'excipit':
      case 'people_mentioned':
      case 'notes_on_people_mentioned':
      case 'places_mentioned':
      case 'manifestations':
      case 'status_desc':
      case 'issues':
      case 'editors_notes':
      case 'related_resources':
        return $this->is_ok_free_text( $this->parm_value );

      case 'uploader_email':
        return $this->is_email_address( $this->parm_value );


      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
