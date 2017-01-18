<?php
/*
 * PHP class to provide dropdown list of original catalogues 
 * (Lister, Lhwyd etc) within the union catalogue
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Catalogue extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Catalogue( &$db_connection ) { 

    $project = new Project( $db_connection );
    $table_name = $project->proj_catalogue_tablename();

    # Don't allow them to change any of the code values, only the descriptions.
    $fixed_codes = array();
    $statement = "select catalogue_code from $table_name where catalogue_code > '' order by catalogue_code";
    $cats = $project->db_select_into_array( $statement );
    if( count( $cats ) > 0 ) {
      foreach( $cats as $cat ) {
        extract( $cat, EXTR_OVERWRITE );
        $fixed_codes[] = $catalogue_code;
      }
    }

    $project = NULL;

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name,
                         $id_column_name    = 'catalogue_id', 
                         $desc_column_name  = 'catalogue_name',
                         $code_column_name  = 'catalogue_code',
                         $publ_column_name  = 'publish_status',
                         $auto_generated_id = TRUE,
                         $fixed_codes  ); 

    $this->code_size = 12;

    if( $this->parm_found_in_post( 'catalogue_code' )) {  # new value?
      $catalogue_code = strtoupper( trim( $this->read_post_parm( 'catalogue_code' )));
      $catalogue_id = intval( $this->read_post_parm( 'catalogue_id' ));
      if( ! $catalogue_id ) $this->fixed_codes[] = $catalogue_code;
    }
  }
  #-----------------------------------------------------

  function get_lookup_list() { # Overrride parent version from Lookup Table
                               # so that the 'original catalogue' field can more easily be suppressed
                               # for projects that don't use original catalogue, e.g. IMPAcT.

    $statement = 'select catalogue_id, catalogue_code, catalogue_name, publish_status, '
               . " catalogue_code || '. ' || catalogue_name as catalogue_name_long "
               . ' from ' . $this->proj_catalogue_tablename()
               . " where catalogue_code > ''"
               . ' order by catalogue_name';

    if( $this->debug ) {
      echo "RG get_lookup_list() <br>";
      var_dump($statement);
      echo "<br>";
    }

    $lookup_list = $this->db_select_into_array( $statement );

    return $lookup_list;
  }
  #-----------------------------------------------------

  function catg_code_dropdown( $field_name = NULL, $field_label = NULL, $selected_code = NULL ) {

    if( ! $field_name ) $field_name = 'catalogue_code';

    $db_list = $this->get_lookup_list();
    if( ! is_array( $db_list )) return;

    $blank_row = array( 'catalogue_code' => '', 'catalogue_name' => '' );

    $lookup_list = array();
    $lookup_list[] = $blank_row;
    foreach( $db_list as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $lookup_list[] = array( 'catalogue_code' => $catalogue_code, 'catalogue_name' => $catalogue_name );
    }

    HTML::dropdown_start( $field_name, $field_label, $in_table = FALSE );

    foreach( $lookup_list as $lookup_row ) {

      $display_code = $lookup_row[ 'catalogue_code' ];
      $display_desc = $lookup_row[ 'catalogue_name' ];

      HTML::dropdown_option( $display_code,
                             $display_desc,
                             $selected_code );  # pre-selected one
    }
    HTML::dropdown_end( $in_table = FALSE );
  }
  #----------------------------------------------------- 

  function proj_catalogue_tablename() {
    return $this->get_system_prefix() . '_lookup_catalogue';
  }
  #----------------------------------------------------------------------------------

  function get_all_catalogues( $order_by = 'catalogue_name' ) {

    $statement = 'select * from ' . $this->proj_catalogue_tablename() . " order by $order_by";
    $cats = $this->db_select_into_array( $statement );
    return $cats;
  }
  #----------------------------------------------------- 

  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class';
    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $uses = NULL;
    $this->referencing_class = PROJ_COLLECTION_WORK_CLASS;
    $this->referencing_method = 'edit_work';
    $this->referencing_id_column = 'iwork_id';


    $project = new Project( $this->db_connection );
    $work_table = $project->proj_work_tablename();
    $project = NULL;

    $statement = 'select iwork_id, description from ' . $work_table . ' where original_catalogue = '
               . ' (select catalogue_code from ' . $this->proj_catalogue_tablename() 
               . " where catalogue_id = $id_value) order by iwork_id"
               . ' LIMIT ' . MAX_LOOKUP_USES_DISPLAYED;
    $uses = $this->db_select_into_array( $statement );

    if( $uses ) $this->lookup_reference_column_labels = array( 'iwork_id' => 'Work ID',
                                                            'description' => 'Description' );
    return $uses;
  }
  #-----------------------------------------------------
}
?>
