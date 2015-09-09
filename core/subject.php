<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

class Subject extends Lookup_Table {
  #----------------------------------------------------------------------------------

  function Subject( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->project = new Project( $db_connection );
    $table_name = $this->project->proj_subject_tablename();

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'subject_id', 
                         $desc_column_name  = 'subject_desc' );
    $this->get_all_possible_subjects();

    $this->rel_obj = new Relationship( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function clear() {

    parent::clear();
    $this->get_all_possible_subjects();
  }
  #----------------------------------------------------------------------------------

  function get_all_possible_subjects() {

    $this->all_possible_subjects = array();

    $statement = 'select * from ' . $this->project->proj_subject_tablename() . ' order by subject_desc';
    $subjs = $this->db_select_into_array( $statement );
    
    if( count( $subjs ) > 0 ) {
      foreach( $subjs as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $this->all_possible_subjects[ $subject_id ] = $subject_desc;
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_subjects_of_work( $work_id = NULL ) {

    $subjs_of_work = array();

    if( ! $work_id ) # could be a new record
      return $subjs_of_work;

    $statement = 'select right_id_value as subject_id '
               . ' from ' . $this->project->proj_relationship_tablename()
               . " where left_table_name = '" . $this->project->proj_work_tablename() . "' "
               . " and left_id_value = '$work_id' "
               . " and relationship_type = '" . RELTYPE_WORK_DEALS_WITH_SUBJECT . "' "
               . " and right_table_name = '" . $this->project->proj_subject_tablename() . "'"
               . ' order by subject_id';

    $subj_rows = $this->db_select_into_array( $statement );

    if( count( $subj_rows ) > 0 ) {
      foreach( $subj_rows as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $subjs_of_work[] = $subject_id;
      }
    }

    return $subjs_of_work;
  }
  #----------------------------------------------------------------------------------


  function subject_entry_fields( $work_id = NULL ) {

    $subjs_in_use = $this->get_subjects_of_work( $work_id );

    $this->project->proj_multiple_compact_checkboxes( 
                      $all_possible_ids_and_descs = $this->all_possible_subjects,
                      $selected_ids = $subjs_in_use,
                      $checkbox_fieldname = 'subject_chkbox',
                      $list_label = 'Subject(s):' );
  }
  #-----------------------------------------------------

  function save_subjects( $work_id ) {

    if( ! $work_id ) die( 'Invalid input while saving subject of work.' );

    $subjs_of_work = $this->get_subjects_of_work( $work_id );

    $statement = 'select max(subject_id) from ' . $this->project->proj_subject_tablename();
    $max_subject_id = $this->db_select_one_value( $statement );

    $subject_id_string = '';

    for( $i = 1; $i <= $max_subject_id; $i++ ) {
      $subject_id = $i;
      $existing_subject = FALSE;
      if( in_array( $subject_id, $subjs_of_work )) $existing_subject = TRUE;

      $fieldname = 'subject_chkbox' . $subject_id;
      if( $this->parm_found_in_post( $fieldname )) {
        if( ! $existing_subject ) {

          $this->rel_obj->insert_relationship( $left_table_name = $this->project->proj_work_tablename(),
                                               $left_id_value = $work_id,
                                               $relationship_type = RELTYPE_WORK_DEALS_WITH_SUBJECT,
                                               $right_table_name = $this->project->proj_subject_tablename(),
                                               $right_id_value = $subject_id );
        }
      }
      else { # checkbox was not ticked for this subject
        if( $existing_subject ) {

          $this->rel_obj->delete_relationship( $left_table_name = $this->project->proj_work_tablename(),
                                               $left_id_value = $work_id,
                                               $relationship_type = RELTYPE_WORK_DEALS_WITH_SUBJECT,
                                               $right_table_name = $this->project->proj_subject_tablename(),
                                               $right_id_value = $subject_id );
        }
      }
    }
  }
  #-----------------------------------------------------

  function edit_lookup_table2() {  # overrides parent class

    parent::edit_lookup_table2(); # Actually do the addition, update or deletion

    # Now see if it was an update. If so, update manifestation summary in queryable work.
    $updated = TRUE;

    $non_update_buttons = array( 'add_button',
                                 'check_deletion_button',
                                 'delete_button',
                                 'cancel_deletion_button' );

    foreach( $non_update_buttons as $button ) {
      if( $this->parm_found_in_post( $button )) {
        $updated = FALSE;
        break;
      }
    }

    if( $updated ) { # We need to manually refresh the 'queryable work' table.

      $id_value = $this->read_post_parm( $this->id_column_name );

      $work_table = $this->project->proj_work_tablename();
      $queryable_work_table = $this->project->proj_queryable_work_tablename();
      $refresh_func = $this->project->proj_database_function_name( 'get_work_desc', 
                                                                   $include_collection_code = TRUE );
      $works = $this->get_work_ids( $id_value );
      if( is_array( $works )) {
        foreach( $works as $work_id ) {
          $statement = "update $work_table set description = $refresh_func ( work_id ) "
                     . " where work_id = '$work_id'";
          $this->db_run_query( $statement );

          $statement = "update $queryable_work_table set description = $refresh_func ( work_id ) "
                     . " where work_id = '$work_id'";
          $this->db_run_query( $statement );
        }
      }
    }
  }
  #----------------------------------------------------- 

  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class';

    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $uses = NULL;
    $this->referencing_class = PROJ_COLLECTION_WORK_CLASS;
    $this->referencing_method = 'edit_work';
    $this->referencing_id_column = 'iwork_id';

    $works = $this->get_work_ids( $id_value );
    if( count( $works ) > 0 ) {

      $statement = 'select iwork_id, description '
                 . ' from ' . $this->project->proj_work_tablename() 
                 . ' where work_id in (';

      for( $i = 0; $i < count( $works ); $i++ ) {
        if( $i > 0 ) $statement .= ', ';
        $work_id = $works[ $i ];
        $statement .= "'$work_id'";
      }

      $statement = $statement . ') order by iwork_id';

      $uses = $this->db_select_into_array( $statement );
    }

    if( $uses ) $this->lookup_reference_column_labels = array( 'iwork_id' => 'Work ID',
                                                               'description' => 'Description' );
    return $uses;
  }
  #-----------------------------------------------------

  function get_work_ids( $id_value = NULL ) {

    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $work_table = $this->project->proj_work_tablename();
    $subj_table = $this->project->proj_subject_tablename();
    $rel_table = $this->project->proj_relationship_tablename();

    $works = array();

    $manifestation_id = $mrow[ 'manifestation_id' ];
    $statement = "select left_id_value as work_id from $rel_table"
               . " where left_table_name = '$work_table'"
               . " and relationship_type = '" . RELTYPE_WORK_DEALS_WITH_SUBJECT . "'"
               . " and right_table_name = '$subj_table'"
               . " and right_id_value = '$id_value'";
    $sworks = $this->db_select_into_array( $statement );
    if( is_array( $sworks )) {
      foreach( $sworks as $wrow ) {
        $work_id = $wrow[ 'work_id' ];
        if( ! in_array( $work_id, $works )) {
          $works[] = $work_id;
        }
      }
    }
    return $works;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'subject_id':
        return $this->is_integer( $this->parm_value );

      case 'subject_desc':
        return $this->is_ok_free_text( $this->parm_value );
        
      default:
        $fieldstart = 'subject_chkbox';
        if( $this->string_starts_with( $parm_name, $fieldstart )) {
          $the_rest = substr( $parm_name, strlen( $fieldstart ));
          if( $this->is_integer( $the_rest ))   # e.g. if fieldname is 'subject_chkbox1', value should be '1'
            if( intval( $the_rest ) == intval( $this->parm_value )) return TRUE;
        }

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
