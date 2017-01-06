<?php
/*
 * PHP class to provide dropdown list of organisation/group types
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Nationality extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Nationality( &$db_connection ) { 

    $this->project = new Project( $db_connection );
    $table_name = $this->project->proj_nationality_tablename();

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'nationality_id', 
                         $desc_column_name  = 'nationality_desc' );

    $this->get_all_possible_nationalities();
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
               . ' from ' . $proj->proj_person_tablename() . ' p, ' 
                          . $proj->proj_relationship_tablename() . ' rel'
               . " where rel.left_table_name = '" . $proj->proj_person_tablename() . "' "
               . ' and p.person_id = rel.left_id_value'
               . " and rel.relationship_type = '" . RELTYPE_PERSON_MEMBER_OF_NATIONALITY . "' "
               . " and rel.right_table_name = '" . $proj->proj_nationality_tablename() . "' "
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
    $this->get_all_possible_nationalities();
  }
  #----------------------------------------------------------------------------------

  function get_all_possible_nationalities() {

    $this->all_possible_nationalities = array();

    $statement = 'select * from ' . $this->project->proj_nationality_tablename() . ' order by nationality_desc';
    $nationalities = $this->db_select_into_array( $statement );
    
    if( count( $nationalities ) > 0 ) {
      foreach( $nationalities as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $this->all_possible_nationalities[ $nationality_id ] = $nationality_desc;
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_nationalities_of_person( $person_id = NULL ) {

    $nationalities_of_person = array();
    if( ! $person_id ) # could be a new record
      return $nationalities_of_person;

    $statement = 'select right_id_value as nationality_id '
               . ' from ' . $this->project->proj_relationship_tablename()
               . " where left_table_name = '" . $this->project->proj_person_tablename() . "' "
               . " and left_id_value = '$person_id' "
               . " and relationship_type = '" . RELTYPE_PERSON_MEMBER_OF_NATIONALITY . "' "
               . " and right_table_name = '" . $this->project->proj_nationality_tablename() . "'"
               . ' order by nationality_id';

    $nationality_rows = $this->db_select_into_array( $statement );

    if( count( $nationality_rows ) > 0 ) {
      foreach( $nationality_rows as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $nationalities_of_person[] = $nationality_id;
      }
    }

    return $nationalities_of_person;
  }
  #----------------------------------------------------------------------------------


  function nationality_entry_fields( $person_id = NULL, $include_label = FALSE ) {

    $nationalities_in_use = $this->get_nationalities_of_person( $person_id );

    $this->project->proj_multiple_compact_checkboxes( 
                      $all_possible_ids_and_descs = $this->all_possible_nationalities,
                      $selected_ids = $nationalities_in_use,
                      $checkbox_fieldname = 'nationality_chkbox',
                      $list_label = '' );
  }
  #-----------------------------------------------------

  function save_nationalities( $person_id ) {

    if( ! $person_id ) die( 'Invalid input while saving nationality of person.' );

    $nationalities_of_person = $this->get_nationalities_of_person( $person_id );

    $statement = 'select max(nationality_id) from ' . $this->project->proj_nationality_tablename();
    $max_nationality_id = $this->db_select_one_value( $statement );

    $nationality_id_string = '';

    $this->rel_obj = new Relationship( $this->db_connection );

    for( $i = 1; $i <= $max_nationality_id; $i++ ) {
      $nationality_id = $i;
      $existing_nationality = FALSE;
      if( in_array( $nationality_id, $nationalities_of_person )) $existing_nationality = TRUE;

      $fieldname = 'nationality_chkbox' . $nationality_id;
      if( $this->parm_found_in_post( $fieldname )) {
        if( ! $existing_nationality ) {

          $this->rel_obj->insert_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_MEMBER_OF_NATIONALITY,
                                               $right_table_name = $this->project->proj_nationality_tablename(),
                                               $right_id_value = $nationality_id );
        }
      }
      else { # checkbox was not ticked for this nationality
        if( $existing_nationality ) {

          $this->rel_obj->delete_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_MEMBER_OF_NATIONALITY,
                                               $right_table_name = $this->project->proj_nationality_tablename(),
                                               $right_id_value = $nationality_id );
        }
      }
    }
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'nationality_id':
        return $this->is_integer( $this->parm_value );

      case 'nationality_desc':
        return $this->is_ok_free_text( $this->parm_value );
        
      default:
        $fieldstart = 'nationality_chkbox';
        if( $this->string_starts_with( $parm_name, $fieldstart )) {
          $the_rest = substr( $parm_name, strlen( $fieldstart ));
          if( $this->is_integer( $the_rest ))   # e.g. if fieldname is 'nationality_chkbox1', value should be '1'
            if( intval( $the_rest ) == intval( $this->parm_value )) return TRUE;
        }

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
