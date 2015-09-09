<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'DEFAULT_RESOURCE_DESC_ROWS', 2 );
define( 'MAX_RESOURCE_DESC_ROWS', 12 );
define( 'FLD_SIZE_RESOURCE_NAME', 90 );
define( 'FLD_SIZE_RESOURCE_URL', 90 );

class Resource extends Project {

  #----------------------------------------------------------------------------------

  function Resource( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $this->db_connection );

    $this->extra_anchors = array();
  }

  #----------------------------------------------------------------------------------

  function set_resource( $resource_id  = NULL ) {

    $this->clear();
    if( ! $resource_id ) return NULL;

    $statement = 'select * from ' . $this->proj_resource_tablename() . " where resource_id = $resource_id";
    $this->db_select_into_properties( $statement );
    if( ! $this->resource_id ) die( 'Invalid resource ID.' );

    return $this->resource_id;
  }
  #----------------------------------------------------------------------------------
  # Produce a set of navigation links to help you jump through the sections of a long form

  function make_list_of_resource_links( $resource_array ) {

    $links = array();
    $resource_no = 0;

    if( is_array( $resource_array ) && count( $resource_array ) >= 1 ) {

      $anchor = 'add_new_resource';
      $display = 'Add new related resource';
      $links[ "$anchor" ] = $display;

      foreach( $resource_array as $row ) {
        extract( $row, EXTR_OVERWRITE );
        if( $relationship_type != RELTYPE_ENTITY_HAS_RESOURCE ) continue;
        $resource_no++;

        $resource_id = $right_id_value;  # work etc. (left) has resource (right)
        $this->set_resource( $resource_id );  # N.B. Clears properties before re-setting

        $anchor = 'existing_resource_no_' . $resource_no;
        $display = 'Resource no. ' . $resource_no . '. ' . $this->resource_name;

        $links[ "$anchor" ] = $display;
      }
    }

    return $links;
  }
  #----------------------------------------------------------------------------------

  function proj_list_form_sections() { # overrides parent class

    $form_sections = $this->form_section_links;
    return $form_sections;
  }
  #-----------------------------------------------------

  function edit_resources( $resource_array, # this array is a set of rows from the relationships table
                           $related_table = NULL ) {
                                     
    #------------
    # New resource
    #------------
    $this->clear();

    $links = $this->make_list_of_resource_links( $resource_array ); # enable easier navigation round the form
    $this->form_section_links = $links;
    $this->proj_form_section_links( $curr_section = 'add_new_resource', $heading_level = NULL );
    $this->clear();

    html::h4_start();
    echo 'Add a new resource:';
    html::h4_end();

    $this->related_table = $related_table; # used for setting which standard text options to offer
    $this->resource_entry_fields();

    $this->extra_save_button( $prefix = 'new_resource' );
    html::new_paragraph();

    #------------------
    # Existing resources
    #------------------
    if( is_array( $resource_array ) && count( $resource_array ) >= 1 ) {
      $total_resources = count( $resource_array );
      $this_resource = 0;
      html::horizontal_rule();

      html::h4_start();
      echo 'Edit existing resource(s):';
      html::h4_end();
      foreach( $resource_array as $row ) {
        $this_resource++;
        extract( $row, EXTR_OVERWRITE );
        if( $relationship_type != RELTYPE_ENTITY_HAS_RESOURCE ) continue;

        $resource_id = $right_id_value;  # work etc. (left) has resource (right)

        $this->set_resource( $resource_id );  # N.B. Clears properties before re-setting
        $this->related_table = $related_table;

        $this->relationship_id = $relationship_id;
        $this->relationship_type = $relationship_type;

        # Extra anchor for use with the 'Save and Continue' button
        $extra_anchor = 'resource' . $this->resource_id;
        html::anchor( $extra_anchor . '_anchor' );
        $this->extra_anchors[] = $extra_anchor;

        # Navigation links
        $this->form_section_links = $links; # will have been cleared by 'set resource'
        $this->proj_form_section_links( $curr_section = 'existing_resource_no_' . $this_resource, $heading_level = 'bold' );
        echo LINEBREAK;

        $this->edit_resource();

        if( $this_resource < $total_resources ) html::horizontal_rule();
      }
    }
  }
  #----------------------------------------------------------------------------------

  function set_resource_source_desc() {

    $this->resource_source_desc = '';
    if( ! $this->resource_id ) return;

    $creator = $this->decode_resource_writer( $this->creation_user );
    $changer = $this->decode_resource_writer( $this->change_user );

    if( $creator == PROJ_INITIAL_IMPORT ) $creator = strtolower( $creator );
    if( $changer == PROJ_INITIAL_IMPORT ) $changer = strtolower( $changer );

    $this->resource_source_desc  = ' (Entry created ' . $this->postgres_date_to_words( $this->creation_timestamp );
    $this->resource_source_desc .= ' by ' . $creator . '.';

    if( $this->change_timestamp > $this->creation_timestamp ) {
      $this->resource_source_desc .= ' Last changed ' . $this->postgres_date_to_words( $this->change_timestamp );
      $this->resource_source_desc .= ' by ' . $changer . '.';
    }

    $this->resource_source_desc .= ')';
  }
  #----------------------------------------------------------------------------------

  function echo_resource_source_desc() {

    if( $this->resource_source_desc ) {
      html::div_start( 'class="workfield"' );
      html::span_start( 'class="workfieldaligned"' );
      html::italic_start();

      $this->echo_safely( $this->resource_source_desc );

      html::italic_end();
      html::span_end();
      html::div_end( 'workfield' );

      echo LINEBREAK;
    }
  }
  #----------------------------------------------------------------------------------

  function edit_resource() {

    $this->set_resource_source_desc();

    $this->resource_entry_fields();
    $this->resource_deletion_field();

    $this->echo_resource_source_desc();
    html::new_paragraph();

    $this->extra_save_button( $prefix = 'resource' . $this->resource_id );
  }
  #----------------------------------------------------------------------------------

  function get_resource_details_field_cols( $cols = NULL ) {

    if( ! $cols ) $cols = FLD_SIZE_NOTES_ON_WORK_COLS;

    return $cols;
  }
  #----------------------------------------------------------------------------------

  function get_resource_details_field_rows( $cols = NULL ) {

    $resource_length = strlen( $this->resource_details );

    if( ! $cols ) $cols = $this->get_resource_details_field_cols();

    if( $resource_length && $cols ) {
      $rows = $resource_length / $cols;
      $rows = ceil( $rows );
      $rows++;

      $newline_count = substr_count( $this->resource_details, NEWLINE );
      $newline_count++;
      if( $newline_count > $rows ) $rows = $newline_count;

      if( $rows > MAX_RESOURCE_DESC_ROWS ) $rows = MAX_RESOURCE_DESC_ROWS;
    }
    else
      $rows = DEFAULT_RESOURCE_DESC_ROWS;

    return $rows;
  }
  #----------------------------------------------------------------------------------

  function resource_entry_fields() {

    html::div_start( 'class="workfield"' );

    if( $this->resource_id ) html::hidden_field( 'existing_resource_id[]', $this->resource_id, 
                                                 $input_instance = $this->resource_id );

    #----------- Name ----------------
    if( $this->resource_id )
      $fieldname = 'existing_resource_name[]';
    else
      $fieldname = 'new_resource_name';
    $name_field_id = html::field_id_from_fieldname( $fieldname, $this->resource_id );

    html::input_field( $fieldname, 'Title or brief description', $this->resource_name,
                       FALSE, FLD_SIZE_RESOURCE_NAME, $tabindex = 1,  NULL, NULL, NULL, 
                       $input_instance = $this->resource_id );

    html::new_paragraph();
    html::div_end( 'workfield' );

    $this->standard_text_options( $name_field_id );

    html::div_start( 'class="workfield"' );


    #----------- URL ----------------
    if( $this->resource_id )
      $fieldname = 'existing_resource_url[]';
    else
      $fieldname = 'new_resource_url';

    $url_field_id = html::field_id_from_fieldname( $fieldname, $this->resource_id );
    $test_link    = 'test_' . $url_field_id . '_link';
    $test_msg     = 'test_' . $url_field_id . '_msg';
    $script_name  = 'test_' . $url_field_id . '_function';

    $script  = "function $script_name( url_value ) {"                       . NEWLINE;
    $script .= "  var the_link = document.getElementById( '$test_link' ); " . NEWLINE;
    $script .= "  var the_msg  = document.getElementById( '$test_msg' ); "  . NEWLINE;
    $script .= '  the_link.href = url_value;'                               . NEWLINE;
    $script .= '  if( url_value == "" ) {'                                  . NEWLINE;
    $script .= '    the_link.style.display = "none";'                       . NEWLINE;
    $script .= '    the_msg.style.display = "inline";'                      . NEWLINE;
    $script .= '  }'                                                        . NEWLINE;
    $script .= '  else {'                                                   . NEWLINE;
    $script .= '    the_link.style.display = "inline";'                     . NEWLINE;
    $script .= '    the_msg.style.display = "none";'                        . NEWLINE;
    $script .= '  }'                                                        . NEWLINE;
    $script .= '}'                                                          . NEWLINE;

    html::write_javascript_function( $script );
   
    html::input_field( $fieldname, 'URL', $this->resource_url,
                       FALSE, FLD_SIZE_RESOURCE_URL, $tabindex = 1,  NULL, NULL, 
                       $input_parms = 'onchange="' . $script_name . '( this.value )"',
                       $input_instance = $this->resource_id  );

    html::new_paragraph();

    #---------- Test link -------------
    html::span_start( 'class="workfieldaligned"');

    if( $this->resource_url ) {
      $link_display = 'inline';
      $msg_display = 'none';
    }
    else {
      $link_display = 'none';
      $msg_display = 'inline';
    }

    html::link( $href = $this->resource_url, $displayed_text = 'Check URL', $title = 'Check the URL is working', 
                $target = '_blank', $accesskey = '', $tabindex = 1,
                $extra_parms = 'id="' . $test_link . '" style="display: ' . $link_display . '"' );
    html::span_end();

    html::span_start( 'id="' . $test_msg . '" style="display: ' . $msg_display . '"' );
    html::italic_start();
    echo "After entering URL, press Tab or click elsewhere on the form, and a 'Check URL' link will appear.";
    html::italic_end();
    html::span_end();

    html::new_paragraph();

    #----------- Details ----------------
    if( $this->resource_id )
      $fieldname = 'existing_resource_details[]';
    else
      $fieldname = 'new_resource_details';
   

    $this->proj_textarea( $fieldname, 
                          $rows = $this->get_resource_details_field_rows(), 
                          $cols = $this->get_resource_details_field_cols(), 
                          $this->resource_details, 
                          'Further details of resource',
                          NULL, NULL,
                          $input_instance = $this->resource_id );

    html::div_end( 'workfield' );

    $details_field_id = html::field_id_from_fieldname( $fieldname, $this->resource_id );
    $this->standard_text_options( $details_field_id );
  }
  #----------------------------------------------------------------------------------

  function resource_deletion_field() {

    if( $this->resource_id ) {  # existing resource
      #echo LINEBREAK;

      $fieldname = 'delete_existing_resource[]';
      $span_id = 'resource_deletion_span_' . $this->resource_id;
      $normal_class = 'workfieldaligned';
      $warning_class = $normal_class . ' ' . 'warning';
      $change_display_scriptname = 'change_display_of_' . $span_id;

      $script = "function $change_display_scriptname( chkbox ) {"               . NEWLINE
              . "  var theSpan = document.getElementById( '$span_id' );"        . NEWLINE
              . '  if( chkbox.checked ) {'                                      . NEWLINE
              . '    alert( "Resource will be deleted when you click Save!" );' . NEWLINE
              . "    theSpan.className = '$warning_class';"                     . NEWLINE
              . '  }'                                                           . NEWLINE 
              . '  else {'                                                      . NEWLINE
              . '    alert( "Resource will not now be deleted." );'             . NEWLINE
              . "    theSpan.className = '$normal_class';"                      . NEWLINE
              . '  }'                                                           . NEWLINE 
              . '}'                                                             . NEWLINE;
      html::write_javascript_function( $script );

      html::span_start( 'class="' . $normal_class . '" id="' . $span_id . '"' );
      $parms = 'onclick="' . $change_display_scriptname . '( this )"';
             
      html::checkbox( $fieldname, $label = 'Delete the above resource', $is_checked = NULL, 
                      $value_when_checked = $this->resource_id, $in_table = FALSE,
                      $tabindex=1, 
                      $input_instance = $this->resource_id, 
                      $parms );

      html::span_end();
      echo LINEBREAK;
    }
  }
  #----------------------------------------------------------------------------------

  function decode_resource_writer( $username ) {

    if( ! $this->is_alphanumeric( $username )) die( 'Invalid input.' );

    if( $username == 'postgres' ) return PROJ_INITIAL_IMPORT;

    $supervisor_connection = new DBQuery( $this->get_supervisor());
    $decoder = new resource( $supervisor_connection );

    $statement = 'select distinct surname, forename from ' . $this->proj_users_and_roles_viewname()
               . " where username = '$username'";
    $user_details = $decoder->db_select_into_array( $statement );

    $decoder = NULL;
    $supervisor_connection = NULL;

    if( count( $user_details ) == 1 ) {
      $surname = $user_details[0]['surname'];
      $forename = $user_details[0]['forename'];
    }

    $decode = $forename . ' ' . $surname;
    $decode = trim( $decode );
    if( ! $decode ) { # perhaps a user that now has been deleted?
      $decode = ucfirst( str_replace( $this->get_system_prefix(), '', $username ));
    }

    return $decode;
  }
  #----------------------------------------------------------------------------------

  function save_resources( $other_table, $other_id ) {

    if( ! $other_table || ! $other_id ) 
      die( 'Invalid input when saving resources.' );

    $new_resource_name    = $this->read_post_parm( 'new_resource_name' );
    $new_resource_url     = $this->read_post_parm( 'new_resource_url' );
    $new_resource_details = $this->read_post_parm( 'new_resource_details' );

    $existing_resource_ids     = $this->read_post_parm( 'existing_resource_id' );
    $existing_resource_names   = $this->read_post_parm( 'existing_resource_name' );
    $existing_resource_urls    = $this->read_post_parm( 'existing_resource_url' );
    $existing_resource_details = $this->read_post_parm( 'existing_resource_details' );

    $deletion_checkboxes = $this->read_post_parm( 'delete_existing_resource' );

    #------------------
    # Existing resources
    #------------------
    $index = -1;
    if( is_array( $existing_resource_ids )) {
      foreach( $existing_resource_ids as $resource_id ) {
        $index++;
        $delete_this_one = FALSE;
        $resource = '';

        if( is_array( $deletion_checkboxes )) {
          if( in_array( $resource_id, $deletion_checkboxes )) {
            $delete_this_one = TRUE;
          }
        }

        if( ! $delete_this_one ) {
          $resource_name    = $existing_resource_names[ $index ];
          $resource_url     = $existing_resource_urls[ $index ];
          $resource_details = $existing_resource_details[ $index ];

          # Delete blank resources
          $stripped_resource = $this->strip_resource_element( $resource_name )
                             . $this->strip_resource_element( $resource_url )
                             . $this->strip_resource_element( $resource_details );
          if( $stripped_resource == '' ) $delete_this_one = TRUE;
        }

        if( $delete_this_one ) {

          # Delete the relationship between the resource and the other table
          $this->rel_obj->delete_relationship( $left_table_name   = $other_table,
                                               $left_id_value     = $other_id, 
                                               $relationship_type = RELTYPE_ENTITY_HAS_RESOURCE,
                                               $right_table_name  = $this->proj_resource_tablename(),
                                               $right_id_value     = $resource_id ); 
                                               

          $resource_still_in_use = $this->rel_obj->value_exists_on_either_side( $this->proj_resource_tablename(), 
                                                                               $resource_id );

          if( ! $resource_still_in_use ) {
            $statement = 'delete from ' . $this->proj_resource_tablename() . " where resource_id = $resource_id";
            $this->db_run_query( $statement );
          }
        }

        else {  # update existing resource if it has changed

          if( $this->strip_resource_element( $resource_name ) == '' ) #don't allow blank resource name
            $resource_name = $this->get_default_resource_name( $resource_url, $resource_details );

          $statement = 'update ' . $this->proj_resource_tablename() 
                     . " set resource_name = '" . $this->escape( $resource_name ) . "', "
                     . " resource_url = '" . $this->escape( $resource_url ) . "', "
                     . " resource_details = '" . $this->escape( $resource_details ) . "' "
                     . " where ( resource_name != '" . $this->escape( $resource_name ) . "' "
                     . " or resource_url != '" . $this->escape( $resource_url ) . "' "
                     . " or resource_details != '" . $this->escape( $resource_details ) . "') "
                     . " and resource_id = $resource_id";
          $this->db_run_query( $statement );
        }
      }
    }

    #------------
    # New resource
    #------------
    $stripped_resource = $this->strip_resource_element( $new_resource_name )
                       . $this->strip_resource_element( $new_resource_url )
                       . $this->strip_resource_element( $new_resource_details );
    if( $stripped_resource > '' ) {  # save new resource

      if( $this->strip_resource_element( $new_resource_name ) == '' ) #don't allow blank resource name
        $new_resource_name = $this->get_default_resource_name( $new_resource_url, 
                                                               $new_resource_details );

      $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_resource_tablename()) . "'::regclass )";
      $resource_id = $this->db_select_one_value( $statement );

      $statement = 'insert into ' . $this->proj_resource_tablename() 
                 . ' ( resource_id, resource_name, resource_url, resource_details ) values ( '
                 . " $resource_id, " 
                 . "'" . $this->escape( $new_resource_name )    . "', "
                 . "'" . $this->escape( $new_resource_url )     . "', "
                 . "'" . $this->escape( $new_resource_details ) . "' )";
      $this->db_run_query( $statement );

      $this->rel_obj->insert_relationship( $left_table_name = $other_table,  
                                           $left_id_value = $other_id, 
                                           $relationship_type = RELTYPE_ENTITY_HAS_RESOURCE,
                                           $right_table_name = $this->proj_resource_tablename(),
                                           $right_id_value = $resource_id ); 

      # See if they pressed the 'Save and Continue' button, in which case put them onto 
      # the details of the resource that they just entered.
      if( $this->parm_found_in_post( 'new_resource_save_button' )) {
        $this->unset_post_parm( 'new_resource_save_button' );
        $this->write_post_parm( 'resource' . $resource_id . '_save_button' );
      }
    }
  }
  #----------------------------------------------------------------------------------

  function strip_resource_element( $resource_element ) {

    $stripped_resource = str_replace( NEWLINE, '', $resource_element );
    $stripped_resource = str_replace( CARRIAGE_RETURN, '', $stripped_resource );
    $stripped_resource = trim( $stripped_resource );
    return $stripped_resource;

  }
  #----------------------------------------------------------------------------------

  function extra_save_button( $prefix = NULL, $new_paragraph = TRUE, 
                              $parms='onclick="js_enable_form_submission()" class ="workfield_save_button"') {

    $this->proj_extra_save_button( $prefix, $new_paragraph, $parms );
  }
  #-----------------------------------------------------

  function standard_text_options( $field_id ) {

    $options = array();

    switch( $this->related_table ) {

      case $this->proj_location_tablename():
      case $this->proj_location_viewname():
        $object_type = 'Places';
        break;

      case $this->proj_person_tablename():
      case $this->proj_person_viewname():
        $object_type = 'People';
        break;

      case $this->proj_work_tablename():
        $object_type = 'Works';
        break;

      case $this->proj_institution_tablename():
        $object_type = 'Repositories';
        break;

      default:
        $object_type = 'All';
    }

    $statement = 'select speed_entry_text from ' . $this->proj_speed_entry_text_tablename()
               . " where object_type = '$object_type' or object_type = 'All'"
               . ' order by speed_entry_text';
    $rows = $this->db_select_into_array( $statement );
    if( count( $rows ) == 0 ) return;

    foreach( $rows as $row ) {
      $options[] = $row[ 'speed_entry_text' ];
    }

    html::span_start( 'class="workfield"' );
    html::label( 'Add standard text:' );
    html::span_end();

    $script_name = 'add_to_resource_field_' . $field_id;

    $script  = "function $script_name( boxIsChecked, valueToAdd ) {"           . NEWLINE;
    $script .= "  var the_title = document.getElementById( '$field_id' ); "    . NEWLINE;
    $script .= "  if( boxIsChecked ) { "                                       . NEWLINE;
    $script .= '    if( the_title.value != "" ) { '                            . NEWLINE;
    $script .= '      the_title.value = the_title.value + " ";'                . NEWLINE;
    $script .= '    }'                                                         . NEWLINE;
    $script .= '    the_title.value = the_title.value + valueToAdd;'           . NEWLINE;
    $script .= '  }'                                                           . NEWLINE;
    $script .= '}'                                                             . NEWLINE;

    html::write_javascript_function( $script );

    foreach( $options as $opt ) {
      html::span_start( 'class="workfieldaligned highlight1"' );

      $opt_id = $field_id . '_';
      for( $i = 0; $i < strlen( $opt ); $i++ ) {
        $one_char = substr( strtolower( $opt ), $i, 1 );
        if( $one_char >= 'a' && $one_char <= 'z' )
          $opt_id .= $one_char;
        elseif( $one_char >= '0' && $one_char <= '9' )
          $opt_id .= $one_char;
        elseif( $one_char == ' ' )
          $opt_id .= '_';
      }
      
      html::checkbox( $fieldname = $opt_id, $label = $opt, $is_checked = FALSE, 
                      $value_when_checked = "$opt", $in_table = FALSE, $tabindex=1, $input_instance = NULL, 
                      $parms = 'onclick="' . $script_name . '( this.checked, this.value )"' );
      html::span_end();
      echo LINEBREAK;
    }

    html::new_paragraph();
    echo LINEBREAK;
  }
  #-----------------------------------------------------
  function get_default_resource_name( $resource_url, $resource_details ) {

    if( $resource_url > '' )
      $resource_name = $resource_url;

    elseif( $resource_details > '' ) {
      $name_size = 50; # just an arbitrary number

      if( strlen( $resource_details ) <= $name_size )
        $resource_name = $resource_details;
      else
        $resource_name = substr( $resource_details, 0, $name_size ) . '...';
    }

    else
      $resource_name = '[Related resource]';

    $resource_name = str_replace( NEWLINE, ' ', $resource_name );
    $resource_name = str_replace( CARRIAGE_RETURN, ' ', $resource_name );
    return $resource_name;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'resource_id':
      case 'relationship_id':
        return $this->is_integer( $this->parm_value );

      case 'new_resource_name':
      case 'new_resource_url':
      case 'new_resource_details':
        return $this->is_ok_free_text( $this->parm_value );

      case 'existing_resource_name':
      case 'existing_resource_url':
      case 'existing_resource_details':
        if( $this->parm_value == NULL ) return TRUE;
        return $this->is_array_of_ok_free_text( $this->parm_value );

      case 'existing_resource_id':
      case 'delete_existing_resource':
        if( $this->parm_value == NULL ) return TRUE;
        return $this->is_array_of_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
