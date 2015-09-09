<?php
/*
 * PHP class to provide dropdown list of organisation/group types
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Org_Type extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Org_Type( &$db_connection ) { 

    $project = new Project( $db_connection );
    $table_name = $project->proj_org_type_tablename();
    $project = NULL;

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'org_type_id', 
                         $desc_column_name  = 'org_type_desc' );
  }
  #-----------------------------------------------------

  function set_data_entry_mode( $data_entry_mode = 'dropdown' ) {
    $this->data_entry_mode = $data_entry_mode;
  }
  #-----------------------------------------------------

  function clear_data_entry_mode() {
    $this->data_entry_mode = NULL;
  }
  #-----------------------------------------------------

  function get_lookup_list() {

    $lookup_list = parent::get_lookup_list();

    if( $this->data_entry_mode == 'dropdown' ) {
      $blank_row = array( 'org_type_id' => 'null', 'org_type_desc' => ' ' );
      array_unshift( $lookup_list, $blank_row );
    }

    return $lookup_list;
  }
  #-----------------------------------------------------

  function lookup_table_dropdown( $field_name = NULL, $field_label = NULL, $selected_id = 0, $in_table = FALSE,
                                  $script = NULL ) {

    parent::lookup_table_dropdown( $field_name, $field_label, $selected_id, $in_table,
                                  $script = 'onchange="enable_or_disable_org_subtypes( this.value )"' );
  }
  #----------------------------------------------------- 

  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class

    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $uses = NULL;
    $this->referencing_class = 'person';
    $this->referencing_method = 'one_person_search_results';
    $this->referencing_id_column = 'iperson_id';

    $proj = new Project( $this->db_connection );

    $statement = 'select p.iperson_id, p.foaf_name '
               . ' from ' . $proj->proj_person_tablename() . ' p, ' . $proj->proj_org_type_tablename() . ' lookup '
               . ' where lookup.org_type_id = p.organisation_type '
               . ' and lookup.org_type_id = ';

    $statement = $statement . $id_value;
    $statement = $statement . ' order by iperson_id';

    $uses = $this->db_select_into_array( $statement );

    if( $uses ) $this->lookup_reference_column_labels = array( 'iperson_id' => 'Organisation ID',
                                                               'description' => 'Name of organisation' );
    return $uses;
  }
  #-----------------------------------------------------

}
?>
