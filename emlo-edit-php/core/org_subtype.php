<?php
/*
 * PHP class to provide dropdown list, checkbox list etc, of organisation/group sub-types
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Org_Subtype extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Org_Subtype( &$db_connection ) { 

    $this->project = new Project( $db_connection );
    $table_name = $this->project->proj_org_subtype_tablename();

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name, 
                         $id_column_name    = 'org_subtype_id', 
                         $desc_column_name  = 'org_subtype_desc' );

    $this->get_all_possible_org_subtypes();

    $this->org_type_obj = new Org_Type( $this->db_connection );
  }
  #-----------------------------------------------------

  function write_extra_fields1_new() {

    HTML::tabledata_start( 'colspan="2"' );

    $this->org_type_obj->lookup_table_dropdown( $field_name = 'org_type_id', 
                                                $field_label = 'Type of group or organisation' );
    HTML::tabledata_end();

    HTML::new_tablerow();
  }
  #-----------------------------------------------------

  function write_extra_fields1_existing( $id_value = NULL ) {

    if( ! $id_value ) return;

    HTML::tabledata_start( 'colspan="2"' );

    $statement = 'select org_type_id from ' . $this->project->proj_org_subtype_tablename()
               . " where org_subtype_id = $id_value";
    $org_type_id = $this->db_select_one_value( $statement );

    $statement = 'select org_type_desc from ' . $this->project->proj_org_type_tablename()
               . " where org_type_id = $org_type_id";
    $org_type_desc = $this->db_select_one_value( $statement );

    HTML::hidden_field( 'org_type_id', $org_type_id );
    echo 'Type of group or organisation: ';
    HTML::bold_start();
    echo $org_type_desc;
    HTML::bold_end();

    HTML::tabledata_end();
    HTML::new_tablerow();
  }
  #----------------------------------------------------- 

  function get_lookup_list( $called_by = NULL ) { # overrides parent class

    $statement = "select s.org_subtype_id, s.org_subtype_desc, s.org_type_id, t.org_type_desc, "
               . " t.org_type_desc || ': ' || s.org_subtype_desc as long_desc"
               . " from " . $this->project->proj_org_subtype_tablename() . " s, "
               . $this->project->proj_org_type_tablename() . " t "
               . " where s.org_type_id = t.org_type_id"
               . " order by org_type_desc, org_subtype_desc";

    $lookup_list = $this->db_select_into_array( $statement );

    if( ! is_array( $lookup_list )) echo 'No options found.';

    if( $called_by == 'desc_dropdown' ) {
      for( $i = 0; $i < count( $lookup_list ); $i++ ) {
        $tdesc = $lookup_list[ $i ][ 'org_type_desc' ];
        $sdesc = $lookup_list[ $i ][ 'org_subtype_desc' ];

        $lookup_list[ $i ][ 'org_subtype_desc' ] = $tdesc . '%' . $sdesc; # automatically add wildcard for query
      }
    }

    return $lookup_list;
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
               . " and rel.relationship_type = '" . RELTYPE_PERSON_HAD_AFFILIATION_OF_TYPE . "' "
               . " and rel.right_table_name = '" . $this->project->proj_org_subtype_tablename() . "' "
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
    $this->get_all_possible_org_subtypes();
  }
  #----------------------------------------------------------------------------------

  function get_all_possible_org_subtypes( $use_long_desc = TRUE ) {

    $this->all_possible_org_subtypes = array();

    $org_subtypes = $this->get_lookup_list();
    
    if( count( $org_subtypes ) > 0 ) {
      foreach( $org_subtypes as $row ) {
        extract( $row, EXTR_OVERWRITE );

        if( $use_long_desc )
          $desc = $long_desc;
        else
          $desc = $org_subtype_desc;

        $this->all_possible_org_subtypes[ $org_subtype_id ] = $desc; # e.g. "Peripatetic" 
                                                                     # vs. "Philosophical school: peritapetic
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_subtypes_for_one_type( $required_type = NULL ) {

    $org_subtypes = $this->get_lookup_list();
    $subtypes_for_one_type = array();
    
    if( count( $org_subtypes ) > 0 ) {
      foreach( $org_subtypes as $row ) {
        extract( $row, EXTR_OVERWRITE );
        if( $org_type_id != $required_type ) continue;
        $subtypes_for_one_type[ $org_subtype_id ] = $org_subtype_desc;
      }
    }

    return $subtypes_for_one_type;
  }
  #----------------------------------------------------------------------------------

  function get_org_subtypes_of_person( $person_id = NULL ) {

    $org_subtypes_of_person = array();
    if( ! $person_id ) # could be a new record
      return $org_subtypes_of_person;

    $statement = 'select right_id_value as org_subtype_id '
               . ' from ' . $this->project->proj_relationship_tablename()
               . " where left_table_name = '" . $this->project->proj_person_tablename() . "' "
               . " and left_id_value = '$person_id' "
               . " and relationship_type = '" . RELTYPE_PERSON_HAD_AFFILIATION_OF_TYPE . "' "
               . " and right_table_name = '" . $this->project->proj_org_subtype_tablename() . "'"
               . ' order by org_subtype_id';

    $org_subtype_rows = $this->db_select_into_array( $statement );

    if( count( $org_subtype_rows ) > 0 ) {
      foreach( $org_subtype_rows as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $org_subtypes_of_person[] = $org_subtype_id;
      }
    }

    return $org_subtypes_of_person;
  }
  #----------------------------------------------------------------------------------
  function make_checkbox_name( $org_type_id = 0 ) {

    return 'orgtype_' . $org_type_id . '_subtype_chkbox_';  # then add sub-type ID to end of string
  }
  #----------------------------------------------------------------------------------

  function value_from_checkbox_name( $checkbox_name ) {

    $parts = explode( '_', $checkbox_name );
    if( count( $parts ) != 5 ) return FALSE;

    $strvar1 = $parts[0];
    $intvar1 = $parts[1];
    $strvar2 = $parts[2];
    $strvar3 = $parts[3];
    $intvar2 = $parts[4];

    if( $strvar1 != 'orgtype' ) 
      return FALSE;

    if( ! $this->is_integer( $intvar1 )) 
      return FALSE;  # primary organisation type

    if( $strvar2 != 'subtype' ) 
      return FALSE;

    if( $strvar3 != 'chkbox' ) 
      return FALSE;

    if( ! $this->is_integer( $intvar2 )) 
      return FALSE;  # organisation sub-type

    return $intvar2; # return organisation sub-type
  }
  #----------------------------------------------------------------------------------


  function org_subtype_entry_fields( $person_id = NULL,
                                     $required_org_type = NULL ) {

    if( $required_org_type == 'null' ) $required_org_type = NULL;

    $org_subtypes_in_use = $this->get_org_subtypes_of_person( $person_id );

    $statement = 'select org_type_id, org_type_desc from ' . $this->project->proj_org_type_tablename()
               . ' order by org_type_desc';
    $org_types = $this->db_select_into_array( $statement );

    foreach( $org_types as $row ) {
      extract( $row, EXTR_OVERWRITE );

      HTML::div_start( 'id="' . $this->get_org_type_div_id( $org_type_id ) . '"' );

      $ids_and_descs = $this->get_subtypes_for_one_type( $required_type = $org_type_id );

      if( count( $ids_and_descs ) > 0 ) {
        $this->project->proj_multiple_compact_checkboxes( 
                        $all_possible_ids_and_descs = $ids_and_descs,
                        $selected_ids = $org_subtypes_in_use,
                        $checkbox_fieldname = $this->make_checkbox_name( $org_type_id ),
                        $list_label = $org_type_desc );
      }
      HTML::div_end();
    }

    # Prepare to suppress display of irrelevant checkboxes
    $funcname = 'enable_or_disable_org_subtypes';

    $script  = '  function ' . $funcname . '( required_org_type_id ) { ' . NEWLINE;
    $script .= '    if( required_org_type_id == "null" ) { '             . NEWLINE;
    $script .= '      required_org_type_id = null;'                      . NEWLINE;
    $script .= '    } '                                                  . NEWLINE; 

    foreach( $org_types as $row ) {
      extract( $row, EXTR_OVERWRITE );

      $div_id = $this->get_org_type_div_id( $org_type_id );
      $label_id = $this->make_checkbox_name( $org_type_id ) . 'list_label';

      $script .= '    div_id = "' . $div_id . '";'                                        . NEWLINE;
      $script .= '    the_div = document.getElementById( div_id );'                       . NEWLINE;

      $script .= '    label_id = "' . $label_id . '";'                                    . NEWLINE;
      $script .= '    the_label = document.getElementById( label_id );'                   . NEWLINE;

      $script .= '    if(( the_div ) && ( the_label )) { '                                . NEWLINE;
      $script .= '      if( ! required_org_type_id ) { '                                  . NEWLINE; 
      $script .= '        the_div.style.display = "block";'                               . NEWLINE;
      $script .= '        the_label.innerHTML = "' . $org_type_desc . '";'                . NEWLINE;
      $script .= '      } '                                                               . NEWLINE; 
      $script .= "      else if( parseInt( required_org_type_id ) == $org_type_id ) { "   . NEWLINE; 
      $script .= '        the_div.style.display = "block";'                               . NEWLINE;
      $script .= '        the_label.innerHTML = "";'                                      . NEWLINE;
      $script .= "      } "                                                               . NEWLINE; 
      $script .= "      else  { "                                                         . NEWLINE; 

      $ids_and_descs = $this->get_subtypes_for_one_type( $org_type_id );
      $fieldname_start = $this->make_checkbox_name( $org_type_id );

      foreach( $ids_and_descs as $id => $desc ) {
        $script .= '        checkbox_id = "' . $fieldname_start . $id . '";'              . NEWLINE;
        $script .= '        the_checkbox = document.getElementById( checkbox_id );'       . NEWLINE;
        $script .= '        if( the_checkbox.checked == true ) { '                        . NEWLINE;
        $script .= '          the_checkbox.click(); // uncheck it and fire onclick event' . NEWLINE;
        $script .= '        } '                                                           . NEWLINE; 
      }

      $script .= '        the_div.style.display = "none";'                                . NEWLINE;

      $script .= "      } "                                                               . NEWLINE; 
      $script .= '    } '                                                                 . NEWLINE; 
    }

    $script .= '  } '                                                                     . NEWLINE; 
    HTML::write_javascript_function( $script );

    if( $required_org_type ) {
      $script = "$funcname( $required_org_type )" . NEWLINE;
      HTML::write_javascript_function( $script );
    }
  }
  #-----------------------------------------------------
  function get_org_type_div_id( $org_type_id = 0 ) {
    return 'org_type_' . $org_type_id . '_div';
  }
  #-----------------------------------------------------

  function save_org_subtypes( $person_id ) {

    if( ! $person_id ) die( 'Invalid input while saving org_subtype of person.' );

    $org_subtypes_of_person = $this->get_org_subtypes_of_person( $person_id );

    $statement = 'select * from ' . $this->project->proj_org_subtype_tablename();
    $all_subtypes = $this->db_select_into_array( $statement );

    $this->rel_obj = new Relationship( $this->db_connection );

    foreach( $all_subtypes as $row ) {
      extract( $row, EXTR_OVERWRITE );

      $existing_org_subtype = FALSE;
      if( in_array( $org_subtype_id, $org_subtypes_of_person )) $existing_org_subtype = TRUE;

      $fieldname = $this->make_checkbox_name( $org_type_id ) . $org_subtype_id;
      if( $this->parm_found_in_post( $fieldname )) {
        $org_subtype_id = $this->read_post_parm( $fieldname ); # not totally necessary, but does a full validation

        if( ! $existing_org_subtype ) {

          $this->rel_obj->insert_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_HAD_AFFILIATION_OF_TYPE,
                                               $right_table_name = $this->project->proj_org_subtype_tablename(),
                                               $right_id_value = $org_subtype_id );
        }
      }
      else { # checkbox was not ticked for this org_subtype
        if( $existing_org_subtype ) {

          $this->rel_obj->delete_relationship( $left_table_name = $this->project->proj_person_tablename(),
                                               $left_id_value = $person_id,
                                               $relationship_type = RELTYPE_PERSON_HAD_AFFILIATION_OF_TYPE,
                                               $right_table_name = $this->project->proj_org_subtype_tablename(),
                                               $right_id_value = $org_subtype_id );
        }
      }
    }
  }
  #-----------------------------------------------------

  function get_label_for_desc_field() { # overrides parent method from 'lookup table'
    return 'Sub-category';
  }
  #----------------------------------------------------- 

  function get_extra_insert_cols() { # override if required
    return 'org_type_id, ';
  }
  #----------------------------------------------------- 

  function get_extra_insert_vals() { # override if required

    $org_type_id = $this->read_post_parm( 'org_type_id' );
    return $org_type_id . ', ';
  }
  #----------------------------------------------------- 

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'org_subtype_id':
      case 'org_type_id':
        return $this->is_integer( $this->parm_value );

      case 'org_subtype_desc':
        return $this->is_ok_free_text( $this->parm_value );
        
      default:
        #........................................................................................................
        # Check for 'org subtype' entry for a person
        # The checkbox name will be of format 'orgtype_X_subtype_chkbox_X' (X being an integer in both cases)
        # and the final X of the checkbox NAME must match the VALUE of checkbox.
        $subtype_id_from_checkbox_name = $this->value_from_checkbox_name( $parm_name ); # returns FALSE if wrong format
        if( $subtype_id_from_checkbox_name ) {
          if( $this->is_integer( $this->parm_value )) {
            if( $this->parm_value == $subtype_id_from_checkbox_name ) return TRUE;
          }
        }
        #........................................................................................................

        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
