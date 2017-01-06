<?php
/*
 * PHP class to provide dropdown list of categories of role/profession
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Role_Category extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Role_Category( &$db_connection ) { 

    $this->project = new Project( $db_connection );
    $table_name = $this->project->proj_role_category_tablename();

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'role_category_id', 
                         $desc_column_name  = 'role_category_desc' );

    $this->get_all_possible_role_categories();
  }
  #-----------------------------------------------------

  function find_uses_of_this_id( $id_value = NULL ) {  # overrides parent class

    if( ! $id_value ) $id_value = $this->read_post_parm( $this->id_column_name );
    if( ! $id_value ) $this->die_on_error( 'No ID value passed to method "Find uses of this ID".' );

    $uses = NULL;
    $this->referencing_class = 'person';
    $this->referencing_method = 'one_person_search_results';
    $this->referencing_id_column = 'iperson_id';

    $statement = 'select p.iperson_id, p.foaf_name '
               . ' from ' . $this->project->proj_person_tablename() . ' p, ' 
                          . $this->project->proj_relationship_tablename() . ' rel'
               . " where rel.left_table_name = '" . $this->project->proj_person_tablename() . "' "
               . ' and p.person_id = rel.left_id_value'
               . " and rel.relationship_type = '" . RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY . "' "
               . " and rel.right_table_name = '" . $this->project->proj_role_category_tablename() . "' "
               . " and rel.right_id_value = '$id_value'";

    $statement = $statement . ' order by iperson_id';

    $uses = $this->db_select_into_array( $statement );

    if( $uses ) $this->lookup_reference_column_labels = array( 'iperson_id' => 'Person ID',
                                                               'description' => 'Name of person' );
    return $uses;
  }
  #-----------------------------------------------------

  function clear() {

    parent::clear();
    $this->get_all_possible_role_categories();
  }
  #----------------------------------------------------------------------------------

  function get_all_possible_role_categories() {

    $this->all_possible_role_categories = array();

    $statement = 'select * from ' . $this->project->proj_role_category_tablename() . ' order by role_category_desc';
    $role_categories = $this->db_select_into_array( $statement );
    
    if( count( $role_categories ) > 0 ) {
      foreach( $role_categories as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $this->all_possible_role_categories[ $role_category_id ] = $role_category_desc;
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_role_categories_of_person( $person_id = NULL ) {

    $role_categories_of_person = array();
    if( ! $person_id ) # could be a new record
      return $role_categories_of_person;

    $statement = 'select right_id_value as role_category_id '
               . ' from ' . $this->project->proj_relationship_tablename()
               . " where left_table_name = '" . $this->project->proj_person_tablename() . "' "
               . " and left_id_value = '$person_id' "
               . " and relationship_type = '" . RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY . "' "
               . " and right_table_name = '" . $this->project->proj_role_category_tablename() . "'"
               . ' order by role_category_id';

    $role_category_rows = $this->db_select_into_array( $statement );

    if( count( $role_category_rows ) > 0 ) {
      foreach( $role_category_rows as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $role_categories_of_person[] = $role_category_id;
      }
    }

    return $role_categories_of_person;
  }
  #----------------------------------------------------------------------------------


  function role_category_entry_fields( $person_id = NULL ) {

    $role_categories_in_use = $this->get_role_categories_of_person( $person_id );

    $this->project->proj_multiple_compact_checkboxes( 
                      $all_possible_ids_and_descs = $this->all_possible_role_categories,
                      $selected_ids = $role_categories_in_use,
                      $checkbox_fieldname = 'role_category_chkbox',
                      $list_label = 'Professional categories:' );
  }
  #-----------------------------------------------------

  function save_role_categories( $person_id ) {

    if( ! $person_id ) die( 'Invalid input while saving role category of person.' );

    $role_categories_of_person = $this->get_role_categories_of_person( $person_id );

    $statement = 'select max(role_category_id) from ' . $this->project->proj_role_category_tablename();
    $max_role_category_id = $this->db_select_one_value( $statement );

    $role_category_id_string = '';

    $this->rel_obj = new Relationship( $this->db_connection );

    for( $i = 1; $i <= $max_role_category_id; $i++ ) {
      $role_category_id = $i;
      $existing_role_category = FALSE;
      if( in_array( $role_category_id, $role_categories_of_person )) $existing_role_category = TRUE;

      $fieldname = 'role_category_chkbox' . $role_category_id;
      if( $this->parm_found_in_post( $fieldname )) {
        if( ! $existing_role_category ) {

          $this->rel_obj->insert_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY,
                                               $right_table_name = $this->project->proj_role_category_tablename(),
                                               $right_id_value = $role_category_id );
        }
      }
      else { # checkbox was not ticked for this role_category
        if( $existing_role_category ) {

          $this->rel_obj->delete_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY,
                                               $right_table_name = $this->project->proj_role_category_tablename(),
                                               $right_id_value = $role_category_id );
        }
      }
    }
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'role_category_id':
        return $this->is_integer( $this->parm_value );

      case 'role_category_desc':
        return $this->is_ok_free_text( $this->parm_value );
        
      default:
        $fieldstart = 'role_category_chkbox';
        if( $this->string_starts_with( $parm_name, $fieldstart )) {
          $the_rest = substr( $parm_name, strlen( $fieldstart ));
          if( $this->is_integer( $the_rest ))   # e.g. if fieldname is 'role_category_chkbox1', value should be '1'
            if( intval( $the_rest ) == intval( $this->parm_value )) return TRUE;
        }

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------

}
?>
