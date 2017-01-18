<?php
/*
 * PHP class to provide dropdown list of document types
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'DEFAULT_DOCUMENT_TYPE', 'ALS' ); # autograph letter sent

require_once 'lookup_table.php';

class Document_Type extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Document_Type( &$db_connection ) { 

    $project = new Project( $db_connection );
    $table_name = $project->proj_document_type_tablename();
    $project = NULL;

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'document_type_id', 
                         $desc_column_name  = 'document_type_desc',
                         $code_column_name  = 'document_type_code'  ); 
  }
  #-----------------------------------------------------
  # Override parent method so can pass back code instead of numeric ID.

  function lookup_table_dropdown( $field_name = NULL, 
                                  $field_label = NULL, 
                                  $selected_id = NULL ) {

    if( ! $field_name ) $field_name = 'manifestation_type';

    #--------------------------------------------------------
    # Write out a dropdown list of values from selected table
    #--------------------------------------------------------
    $this->lookup_list = $this->get_lookup_list();
    if( ! is_array( $this->lookup_list )) return;

    # Take value from first row as default if nothing has been pre-selected
    # rather than hard-coding a default document type which may not be suitable for every project.
    if( ! $selected_id ) $selected_id = $this->lookup_list[ 0 ][ 'document_type_code' ];

    HTML::dropdown_start( $field_name, $field_label, $in_table=FALSE );

    foreach( $this->lookup_list as $lookup_row ) {

      extract( $lookup_row, EXTR_OVERWRITE );

      HTML::dropdown_option( $document_type_code,
                             $document_type_desc,
                             $selected_id );  # selected one
    }
    HTML::dropdown_end( $in_table);
  }
  #----------------------------------------------------- 

  function get_composite_document_type() { # this method must ONLY be used by projects like IMPAcT
                                           # which actually have an 'is composite document' column
    $statement = "select document_type_code from $this->lookup_table_name"
               . ' where is_composite_document = 1';
    $composite = $this->db_select_one_value( $statement );
    return $composite;
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

    if( $updated ) { # Any change to document type code will have cascaded to the 'manifestations' table via foreign key.
                     # But we need to manually refresh manifestation summary in the 'queryable work' table.

      $code_value = $this->read_post_parm( $this->code_column_name );

      $proj = new Project( $this->db_connection );
      $refresh_func = $proj->proj_database_function_name( 'refresh_queryable_work', 
                                                          $include_collection_code = TRUE );
      $proj = NULL;

      $works = $this->get_work_ids( $code_value );
      if( is_array( $works )) {
        foreach( $works as $work_id ) {
          $statement = "select $refresh_func ( '$work_id' )";
          $retval = $this->db_select_one_value( $statement );
        }
      }
    }
  }
  #----------------------------------------------------- 

  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class

    $code_value = $this->get_lookup_code( $id_value );
    if( ! $code_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $uses = NULL;
    $this->referencing_class = PROJ_COLLECTION_WORK_CLASS;
    $this->referencing_method = 'edit_work';
    $this->referencing_id_column = 'iwork_id';

    $works = $this->get_work_ids( $code_value );
    if( count( $works ) > 0 ) {

      $proj = new Project( $this->db_connection );

      $statement = 'select iwork_id, description '
                 . ' from ' . $proj->proj_work_tablename() 
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

  function get_work_ids( $code_value = NULL ) {

    if( ! $code_value ) $code_value = $this->read_post_parm( $this->code_column_name );
    if( ! $code_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $proj = new Project( $this->db_connection );

    $work_table = $proj->proj_work_tablename();
    $manif_table = $proj->proj_manifestation_tablename();
    $rel_table = $proj->proj_relationship_tablename();

    $proj = NULL;
    $works = array();

    $statement = "select manifestation_id from $manif_table where manifestation_type = '$code_value'";
    $manifs = $this->db_select_into_array( $statement );

    if( is_array( $manifs )) {
      foreach( $manifs as $mrow ) {
        $manifestation_id = $mrow[ 'manifestation_id' ];
        $statement = "select right_id_value as work_id from $rel_table"
                   . " where left_table_name = '$manif_table'"
                   . " and left_id_value = '$manifestation_id'"
                   . " and relationship_type = '" . RELTYPE_MANIFESTATION_IS_OF_WORK . "'"
                   . " and right_table_name = '$work_table'";
        $mworks = $this->db_select_into_array( $statement );
        if( is_array( $mworks )) {
          foreach( $mworks as $wrow ) {
            $work_id = $wrow[ 'work_id' ];
            if( ! in_array( $work_id, $works )) {
              $works[] = $work_id;
            }
          }
        }
      }
    }
    return $works;
  }
  #-----------------------------------------------------
}
?>
