<?php
/*
 * PHP class to provide standard options for describing a related resource.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Speed_Entry_Text extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Speed_Entry_Text( &$db_connection ) { 

    $project = new Project( $db_connection );
    $table_name = $project->proj_speed_entry_text_tablename();
    $project = NULL;

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'speed_entry_text_id', 
                         $desc_column_name  = 'speed_entry_text' );
  }
  #-----------------------------------------------------

  function get_lookup_list() {

    $statement = 'select speed_entry_text_id, speed_entry_text, object_type';
    $statement = $statement . " from $this->lookup_table_name ";
    $statement = $statement . ' order by speed_entry_text, object_type';

    $lookup_list = $this->db_select_into_array( $statement );

    if( ! is_array( $lookup_list )) echo 'No options found.';

    return $lookup_list;
  }
  #-----------------------------------------------------

  function get_extra_insert_cols() { # overrides parent class

    return ' object_type, ';
  }
  #----------------------------------------------------- 

  function get_extra_insert_vals() { # overrides parent class

    $object_type = $this->read_post_parm( 'object_type' );
    return " '$object_type', ";
  }
  #----------------------------------------------------- 

  function get_extra_update_cols_and_vals() { # overrides parent class

    $object_type = $this->read_post_parm( 'object_type' );
    return " object_type = '$object_type', ";
  }
  #----------------------------------------------------- 


  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class
    return NULL;
  }
  #-----------------------------------------------------

  function write_extra_fields2_new() {  # overrides parent class

    $this->write_object_type_dropdown();
  }
  #-----------------------------------------------------

  function write_extra_fields2_existing( $id_value ) {  # overrides parent class

    $statement = "select object_type from $this->lookup_table_name where $this->id_column_name = $id_value";
    $object_type = $this->db_select_one_value( $statement );

    $this->write_object_type_dropdown( $object_type );
  }
  #-----------------------------------------------------

  function write_object_type_dropdown( $selected_object_type = 'All' ) {

    html::tabledata(); # empty cell

    html::tabledata_start();

    html::dropdown_start( $fieldname = 'object_type', $label = 'Relevant to' );

    html::dropdown_option( $internal_value = 'All', $displayed_value = 'All', 
                           $selection = $selected_object_type );

    html::dropdown_option( $internal_value = 'People', $displayed_value = 'People', 
                           $selection = $selected_object_type );

    html::dropdown_option( $internal_value = 'Places', $displayed_value = 'Places', 
                           $selection = $selected_object_type );

    html::dropdown_option( $internal_value = 'Repositories', $displayed_value = 'Repositories', 
                           $selection = $selected_object_type );

    html::dropdown_option( $internal_value = 'Works', $displayed_value = 'Works', 
                           $selection = $selected_object_type );
    html::dropdown_end();
    html::tabledata_end();
    html::new_tablerow();
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'speed_entry_text_id':
        return $this->is_integer( $this->parm_value );

      case 'speed_entry_text':
        return $this->is_ok_free_text( $this->parm_value );
        
      case 'object_type':
        return $this->is_alphabetic( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------

}
?>
