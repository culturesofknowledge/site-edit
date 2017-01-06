<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


class Audit_Trail extends Project {

  #----------------------------------------------------------------------------------

  function Audit_Trail( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function get_search_table() {

    return $this->proj_audit_trail_viewname();
  }
  #-----------------------------------------------------

  function get_results_method() {

    return 'audit_trail_search_results';
  }
  #-----------------------------------------------------

  function get_search_method() {

    return 'db_search';
  }
  #-----------------------------------------------------

  function set_default_simplified_search() {

    if( ! $this->parm_found_in_post( 'simplified_search' ) 
    &&  ! $this->parm_found_in_get(  'simplified_search' )) {

      #if( ! $this->is_expanded_view())
        $this->write_post_parm( 'simplified_search', 'Y' );  # default to simplified search
    }
  }
  #-----------------------------------------------------
  
  function set_audit_trail_search_parms() {  # Only make this database available for 'logged in' search 

    $this->entering_selection_criteria = TRUE;
    $this->reading_selection_criteria = FALSE;

    $this->db_remember_presentation_style(); # If a saved presentation style is found, it is written to POST.
                                             # (On very first login ever, defaults will be used.)

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) {
      $this->write_post_parm( 'order_by', 'audit_trail_entry' );
      $this->write_post_parm( 'sort_descending', '1' );
    }

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->write_post_parm( 'entries_per_page', 100 );

    $this->from_table = $this->get_search_table();

    $this->results_method = $this->get_results_method();

    $this->set_default_simplified_search();
  }
  #-----------------------------------------------------

  function get_default_order_by_col() {
    return 'audit_trail_entry';
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() {  # Overrides parent method from DBEntity.

    $this->search_method  = $this->get_search_method();
    $this->results_method = $this->get_results_method();
    $this->keycol         = 'audit_trail_entry';

    $this->from_table = $this->get_search_table();

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) {
      $this->write_post_parm( 'order_by', 'audit_trail_entry' );
      $this->write_post_parm( 'sort_descending', '1' );
    }

    $this->force_printing_across_page = TRUE;

    $this->edit_method = NULL;
    $this->edit_tab    = NULL;
  }
  #-----------------------------------------------------
  function db_choose_asc_desc() {
    html::span_start( 'class="widespaceonleft"' );
    parent::db_choose_asc_desc();
    html::span_end( 'widespaceonleft' );
  }
  #-----------------------------------------------------

  function db_search( $table_or_view, $class_name = NULL, $method_name = NULL ) {

    $this->set_audit_trail_search_parms();
    parent::db_search( $this->from_table, $this->app_get_class( $this ), $this->results_method );
  }
  #-----------------------------------------------------

  function audit_trail_search_results() {

    $this->entering_selection_criteria = FALSE;
    $this->reading_selection_criteria = TRUE;

    $this->db_search_results();
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

    echo 'Enter selection in one or more fields and click the Search button or press the Return key.';
    html::new_paragraph();

    echo "If you want to find changes made on a particular day, enter that day in the 'Change date/time: From' field, "
         . " and the following day in the 'Change date/time: To' field. For example, to find changes made on 21st June"
         . " 2010, enter 'From 21/06/2010 to 22/06/2010'. This is because the date '22/06/2010' would be equivalent"
         . " to '22/06/2010 00:00' i.e. midnight at the start of that date.";
    html::new_paragraph();

    echo "If you want to find today's changes, you can enter the word 'today' in the 'Change date/time: From' field.";
    html::new_paragraph();
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

      #---------------------------------------------
      # Some columns are queryable but not displayed
      #---------------------------------------------
      if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {

          case 'change_user': # bundled in with change timestamp
            $skip_it = TRUE;
            break;

          case 'changed_record_id': # bundled in with changed record description
          case 'table_name':
            $skip_it = TRUE;
            break;

          case 'changed_record_desc': # not needed if we are already focusing on a single record
            if( $this->menu_method_name=='display_audit_trail' || $this->menu_method_name=='one_work_search_results' ) 
              $skip_it = TRUE;
            break;

          case 'left_table_name':  # Relationship details: bundle them all together in 'decode new' column
          case 'left_id_value_old':
          case 'left_id_decode_old':
          case 'left_id_value_new':
          case 'relationship_type':  # just display 'relationship decode left to right'
          case 'relationship_decode_right_to_left':
          case 'right_table_name':  # Relationship details: bundle them all together in 'decode new' column
          case 'right_id_value_old':
          case 'right_id_decode_old':
          case 'right_id_value_new':
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

        case 'audit_id':
          $column_label = 'Audit trail entry';
          break;

        case 'left_id_decode_new':
          $column_label = 'Left-hand side';
          break;

        case 'right_id_decode_new':
          $column_label = 'Right-hand side';
          break;

        case 'relationship_decode_left_to_right':
          $column_label = 'Relationship';
          break;

        case 'changed_record_id':
          $column_label = 'Record ID';
          break;

        case 'changed_record_desc':
          if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) 
            $column_label = 'Changed record';
          else
            $column_label = 'Record Desc';
          break;

        case 'change_type':
          if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
            $column_label = 'Type';
          }
          break;

        case 'change_timestamp': # override default of 'Last edit'
          $column_label = 'Change date/time';
          break;

        case 'change_user': # override default of 'Last changed by'
          $column_label = 'Changed by user';
          break;

        default:
          if( ! $column_label ) # may have been pre-set by CofK Entity
            $column_label = $this->db_get_default_column_label( $column_name );
          break;
      }
      $row[ 'column_label' ] = $column_label;


      #----------------
      # Set search help
      #----------------
      switch( $column_name ) {

        case 'change_type':
          $search_help_text = 'Can be: New; Chg (i.e. changed); Del (i.e. deleted).';
          break;

        case 'changes_made':
          $search_help_text = 'Contains the old and/or new version of the data. E.g. for a person whose name'
                            . " was changed to 'Bloggs, Fred' from 'Bloggs, F', this field would contain"
                            . " the words 'New: Bloggs, Fred', then a blank line, then 'Old: Bloggs, F'.";
          break;

        case 'changed_record_id':
          $search_help_text = 'The ID of the record that was changed.';
          break;

        case 'changed_record_desc':
          $search_help_text = 'A brief description of the record that was changed (e.g. the current name of a'
                            . ' person or place).';
          break;

        case 'audit_trail_entry':
          $search_help_text = 'A sequential number for every detail added to the audit trail.';
          break;

        case 'changed_field':
          $search_help_class = 'audit_trail_column';
          break;

        case 'table_name':
          $search_help_class = 'audit_trail_table';
          break;

        default:
          break;
      }
      $row[ 'search_help_text' ] = $search_help_text;
      $row[ 'search_help_class' ] = $search_help_class;

      if( $column_name == 'audit_id' )
        $keycol_row = $row;
      else
        $columns[] = $row;
    }

    if( $keycol_row ) $columns[] = $keycol_row;
    return $columns;
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    $column_value = parent::db_browse_reformat_data( $column_name, $column_value );

    if( $column_name == 'changed_record_desc' ) {
      $column_value .= NEWLINE . NEWLINE . ' ['
      . $this->current_row_of_data[ 'table_name' ]
      . ' ID '
      . $this->current_row_of_data[ 'changed_record_id' ]
      . ']';
    }

    elseif( $column_name == 'left_id_decode_new' ) {
      $column_value = '';
      extract( $this->current_row_of_data, EXTR_OVERWRITE );
      $column_value = $this->get_displayable_side_of_relationship( $change_type, $left_table_name, 
                                                                   $new_id     = $left_id_value_new, 
                                                                   $new_decode = $left_id_decode_new, 
                                                                   $old_id     = $left_id_value_old, 
                                                                   $old_decode = $left_id_decode_old);
    }

    elseif( $column_name == 'right_id_decode_new' ) {
      $column_value = '';
      extract( $this->current_row_of_data, EXTR_OVERWRITE );
      $column_value = $this->get_displayable_side_of_relationship( $change_type, $right_table_name, 
                                                                   $new_id     = $right_id_value_new, 
                                                                   $new_decode = $right_id_decode_new, 
                                                                   $old_id     = $right_id_value_old, 
                                                                   $old_decode = $right_id_decode_old);
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function get_displayable_side_of_relationship( $change_type, $table_name, 
                                                 $new_id, $new_decode, $old_id, $old_decode ) {
    $curr_id_name = '';
    $alternative_id_name = '';

    if( $old_id ) # we might have a text-based work ID or person ID, but we want to display the integer version.
      $old_id = $this->get_displayable_id( $table_name, $old_id, $add_deletion_msg = TRUE );

    if( $new_id )
      $new_id = $this->get_displayable_id( $table_name, $new_id, $add_deletion_msg = TRUE );

    $function_name = $this->proj_database_function_name( 'get_table_label', $include_collection_code = FALSE );
    $statement = "select $function_name( '$table_name' )"; # as given in view e.g. Work not cofk_union_work
    $table_label = $this->db_select_one_value( $statement );

    $old_details = $old_decode . NEWLINE . NEWLINE . '[' . $table_label . ' ID ' . $old_id . ']';
    $new_details = $new_decode . NEWLINE . NEWLINE . '[' . $table_label . ' ID ' . $new_id . ']';

    if( $change_type == 'New' )
      return $new_details;

    elseif( $change_type == 'Del' )
      return $old_details;

    else  {  # Chg
      if( $new_id == $old_id ) # it's the other side of the relationship that changed
        return $new_details;
      else {  # this side of the relationship has changed
        $details = 'New value: ' . $new_details . NEWLINE . NEWLINE . 'Old value: ' . $old_details;
        return $details;
      }
    }
  }
  #-----------------------------------------------------

  function one_work_search_results() {
    $this->display_audit_trail();
  }
  #-----------------------------------------------------

  function display_audit_trail() {

    $table_name = $this->read_post_parm( 'table_name' );
    $displayed_key_value = $this->read_post_parm( 'key_value' );

    if( ! $table_name || ! $displayed_key_value ) {
      $table_name = $this->read_get_parm( 'table_name' );
      $displayed_key_value = $this->read_get_parm( 'key_value' );
    }

    $key_within_relationships = $this->get_key_within_relationships( $table_name, $displayed_key_value );
    
    if( ! $table_name || ! $displayed_key_value || ! $key_within_relationships )
      die( 'Cannot locate required audit trail entries.' );

    $desc = $this->get_decode_from_key_within_relationships( $table_name, $key_within_relationships );

    $function_name = $this->proj_database_function_name( 'get_table_label', $include_collection_code = FALSE );
    $statement = "select $function_name( '$table_name' )"; # as given in view e.g. Work not cofk_union_work
    $table_label = $this->db_select_one_value( $statement );

    html::h3_start();
    echo $table_label . ': ';
    echo $desc;
    html::h3_end();

    echo "Please note that you may need to check the 'Related Items' section at the bottom of this page"
         . ' to get a full picture of all changes relevant to this item. For example, you may need to check'
         . " a work's manifestations, authors or addressees in the 'Related Items' list.";

    #----------------------------------
    # Get changes to this record itself
    #----------------------------------
    html::h4_start();
    echo $table_label . ' general summary:';
    html::h4_end();
    echo 'Retrieving data...' . LINEBREAK;
    flush();

    $this->from_table = $this->proj_audit_trail_viewname();

    $column_list = 'change_timestamp, change_user, changed_field, change_type, changes_made, audit_trail_entry';

    $statement = "select $column_list from " . $this->from_table
               . " where table_name = '$table_label' "
               . " and changed_record_id = '" . $this->escape( $displayed_key_value ) . "' "
               . " order by audit_trail_entry desc";
    $results = $this->db_select_into_array( $statement );

    if( count( $results ) < 1 ) {
      echo 'No general history found for this ' . strtolower( $table_label ) . '.';
      html::new_paragraph();
    }
    else
      $this->db_browse_across_page( $results, $cols );

    #-----------

    html::h4_start();
    echo $table_label . ' relationships detail:';
    html::h4_end();
    echo 'Retrieving data...' . LINEBREAK;
    flush();

    $this->from_table = $this->proj_relationship_audit_trail_tablename();

    $column_list = 'change_timestamp, change_user, '
                 . 'left_table_name, ' 
                 . 'left_id_value_old, '
                 . 'left_id_decode_old, '
                 . 'left_id_value_new, '
                 . 'left_id_decode_new, '
                 . 'right_table_name, '
                 . 'right_id_value_old, '
                 . 'right_id_decode_old, '
                 . 'right_id_value_new, '
                 . 'right_id_decode_new, '
                 . 'change_type, relationship_decode_left_to_right, audit_id';

    $statement = "select $column_list from " . $this->from_table
               . " where left_table_name = '$table_name' "
               . " and left_id_value_new = '" . $this->escape( $key_within_relationships ) . "' "

               . "union select $column_list from " . $this->from_table
               . " where left_table_name = '$table_name' "
               . " and left_id_value_old = '" . $this->escape( $key_within_relationships ) . "' "

               . "union select $column_list from " . $this->from_table
               . " where right_table_name = '$table_name' "
               . " and right_id_value_new = '" . $this->escape( $key_within_relationships ) . "' "

               . "union select $column_list from " . $this->from_table
               . " where right_table_name = '$table_name' "
               . " and right_id_value_old = '" . $this->escape( $key_within_relationships ) . "' "

               . " order by audit_id desc";
    $results = $this->db_select_into_array( $statement );

    if( count( $results ) < 1 ) {
      echo 'No relationship history found for this ' . strtolower( $table_label ) . '.';
      html::new_paragraph();
    }
    else
      $this->db_browse_across_page( $results, $cols );

    #---------------------------------------------
    # Provide menu of items related to this record
    #---------------------------------------------
    html::h4_start();
    echo 'Related items:';
    html::h4_end();
    $this->related_items_menu( $table_name, $key_within_relationships, $table_label );

  }
  #-----------------------------------------------------

  function related_items_menu( $table_name, $key_within_relationships, $table_label ) {

    $db_rels = $this->rel_obj->get_other_side_for_this_on_both_sides( $this_table = $table_name, 
                                                                      $this_id = $key_within_relationships );
    $relcount = count( $db_rels );
    if( ! $relcount > 0 ) return;

    $fieldsep = '[Cofk*Field*Separator]'; # something that cannot possibly appear in the data!

    $rels = array();
    foreach( $db_rels as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $this->string_starts_with( $other_id_value, 'SeldenEndFindAid' )) continue; # unnecessarily created works
                                                                                      # as finding aid for other works
      $rels[] = $other_table_name . $fieldsep . $other_id_value;
    }

    $relcount = count( $rels );
    if( ! $relcount > 0 ) return;

    $table_label = strtolower( $table_label );
    echo "You can view the audit trail for $relcount other items to which this $table_label is currently related: ";

    sort( $rels );

    $last_rel = '';
    $last_table = '';
    $other_table = '';
    $other_id = '';
    $ids_printed = 0;

    html::table_start( 'class="datatab spacepadded"' );
    html::tablerow_start();
    html::column_header( 'Type' );
    html::column_header( 'Description/summary' );
    html::column_header( 'ID value' );
    html::column_header( '' );
    html::tablerow_end();

    foreach( $rels as $rel ) {
      if( $rel == $last_rel ) continue; # we only want one link per table/key value
      $last_rel = $rel;

      html::tablerow_start();

      $table_and_id = explode( $fieldsep, $rel );

      $other_table = $table_and_id[ 0 ];
      $other_id    = $table_and_id[ 1 ];

      if( $other_table != $last_table ) {
        $last_table = $other_table;
        $function_name = $this->proj_database_function_name( 'get_table_label', $include_collection_code = FALSE );
        $statement = "select $function_name( '$other_table' )"; # as given in view e.g. Work not cofk_union_work
        $other_table_label = $this->db_select_one_value( $statement );
      }

      $desc = $this->get_decode_from_key_within_relationships( $other_table, $other_id );

      $displayable_id = $this->get_displayable_id( $other_table, $other_id, $add_deletion_msg = FALSE );

      html::tabledata( $other_table_label );
      html::tabledata( $desc );
      html::tabledata( $displayable_id );

      html::tabledata_start( 'class="highlight2"' );
      html::form_start( 'audit_trail', 'display_audit_trail' );
      html::hidden_field( 'table_name', $other_table );
      html::hidden_field( 'key_value', $displayable_id );
      html::submit_button( 'view_button', 'View' );
      html::form_end();
      html::tabledata_end();

      html::tablerow_end();
    }

    html::table_end();
  }
  #-----------------------------------------------------

  function get_displayable_id( $table_name, $key_within_relationships, $add_deletion_msg = TRUE ) {

    $curr_id_name = '';
    $alternative_id_name = '';

    if( $table_name == $this->proj_work_tablename()) {
      $curr_id_name = 'work_id';
      $alternative_id_name = 'iwork_id';
    }
    elseif( $table_name == $this->proj_person_tablename()) {
      $curr_id_name = 'person_id';
      $alternative_id_name = 'iperson_id';
    }

    if( $alternative_id_name ) {
      $selection = "select $alternative_id_name from $table_name where $curr_id_name ";
      $displayable_id = $this->db_select_one_value( "$selection = '$key_within_relationships'");
      if( ! $displayable_id ) {
        if( $add_deletion_msg )
          $displayable_id = '(deleted). ID for internal system use was: ' . $key_within_relationships;
        else
          $displayable_id = 0;
      }
    }
    else
      $displayable_id = $key_within_relationships;

    return $displayable_id;
  }
  #-----------------------------------------------------

  function get_key_within_relationships( $table_name, $displayed_key_value ) {

    if( $table_name == $this->proj_work_tablename()) {
      $statement = "select work_id from $table_name where iwork_id = $displayed_key_value";
      $key_within_relationships = $this->db_select_one_value( $statement );
    }
    elseif( $table_name == $this->proj_person_tablename()) {
      $statement = "select person_id from $table_name where iperson_id = $displayed_key_value";
      $key_within_relationships = $this->db_select_one_value( $statement );
    }
    else
      $key_within_relationships = $displayed_key_value;
    
    return $key_within_relationships;
  }
  #-----------------------------------------------------

  function get_decode_from_key_within_relationships( $table_name, $key_within_relationships ) {

    $function_name = $this->proj_database_function_name( 'decode', $include_collection_code = TRUE );
    $statement = "select $function_name( "
               . " '$table_name', '$key_within_relationships', 1 )"; # the '1' is for 'suppress links' so that you
                                                                     # do not get the string 'xxxCofkLinkStartxxx'
                                                                     # etc appearing onscreen.
    $desc = $this->db_select_one_value( $statement );
    if( ! $desc ) $desc = '[No description found]';
    return $desc;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      # column names from table
      case 'audit_id':
      case 'key_value_integer':

      # column names from view
      case 'audit_trail_entry':
        return $this->is_integer( $this->parm_value );

      # column names from table
      case 'table_name':
      case 'key_value_text':
      case 'key_decode':
      case 'column_name':
      case 'new_column_value':
      case 'old_column_value':

      # column names from view
      case 'changes_made':
      case 'changed_record_id':
      case 'changed_record_desc':
      case 'changed_field':

        return $this->is_ok_free_text( $this->parm_value );

      case 'table_name':
        return $this->is_alphanumeric_or_blank( $this->parm_value, 
                                                $allow_underscores = TRUE, 
                                                $allow_all_whitespace = FALSE );

      case 'key_value':
        if( $this->is_integer( $this->parm_value ))
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
