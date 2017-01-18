<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'MAX_COMMENT_ROWS', 12 );

class Comment extends Project {

  #----------------------------------------------------------------------------------

  function Comment( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->rel_obj = new Relationship( $this->db_connection );
  }

  #----------------------------------------------------------------------------------

  function clear() {

    $keep_form_name = $this->form_name;

    parent::clear();

    $this->form_name = $keep_form_name;
  }
  #----------------------------------------------------------------------------------

  function set_comment( $comment_id  = NULL, $comment_type = NULL ) {

    $this->clear();
    if( ! $comment_id ) return NULL;

    $statement = 'select * from ' . $this->proj_comment_tablename() . " where comment_id = $comment_id";
    $this->db_select_into_properties( $statement );
    if( ! $this->comment_id ) die( 'Invalid comment ID.' );

    if( $comment_type ) $this->set_comment_fieldnames( $comment_type );

    return $this->comment_id;
  }
  #----------------------------------------------------------------------------------

  function display_or_edit_comments( $comment_array,   # this array is a set of rows from the relationships table
                                     $comment_type = NULL,
                                     $general_heading = NULL,
                                     $parent_form = NULL,
                                     $heading_style = 'italic' ) {
                                     
    $this->form_name = $parent_form;

    if( ! $comment_type ) $comment_type = RELTYPE_COMMENT_REFERS_TO_ENTITY;                                 

    if( $general_heading ) {
      HTML::new_paragraph();
      HTML::div_start( 'class="workfield"' );
      HTML::span_start( 'class="workfieldaligned"' );

      if( $heading_style == 'italic' ) HTML::italic_start();
      elseif( $heading_style == 'bold' ) HTML::bold_start();

      echo $general_heading;

      if( $heading_style == 'italic' ) HTML::italic_end();
      elseif( $heading_style == 'bold' ) HTML::bold_end();

      HTML::span_end( 'workfieldaligned' );
      HTML::div_end( 'workfield' );
      HTML::new_paragraph();
    }

    #------------------
    # Existing comments
    #------------------
    if( is_array( $comment_array ) && count( $comment_array ) >= 1 ) {
      foreach( $comment_array as $row ) {
        extract( $row, EXTR_OVERWRITE );
        if( $relationship_type != $comment_type ) continue;

        $comment_id = $left_id_value;  # comment (left) refers to work etc. (right)

        $this->set_comment( $comment_id );  # N.B. Clears properties before re-setting
        $this->set_comment_fieldnames( $comment_type );
        $this->label = 'Existing note';

        $this->relationship_id = $relationship_id;
        $this->relationship_type = $relationship_type;

        $this->display_or_edit_comment();
      }
    }

    #------------
    # New comment
    #------------
    $this->clear();
    $this->set_comment_fieldnames( $comment_type );
    $this->label = 'New note';

    $this->comment_entry_field();
    HTML::new_paragraph();
  }
  #----------------------------------------------------------------------------------

  function set_comment_fieldnames( $comment_type ) {

    $this->existing_comment_field = $comment_type . '_comment[]'; 
    $this->existing_comment_id_field = $comment_type . '_comment_id[]'; 
    $this->delete_comment_field = 'delete_' . $comment_type . '_comment[]';

    $this->new_comment_field = 'new_' . $comment_type . '_comment';

    #------------------------------
    # Versions to be read from POST

    $this->existing_comment_parm    = str_replace( '[]', '', $this->existing_comment_field );
    $this->existing_comment_id_parm = str_replace( '[]', '', $this->existing_comment_id_field );
    $this->delete_comment_parm      = str_replace( '[]', '', $this->delete_comment_field );
  }
  #----------------------------------------------------------------------------------

  function comment_is_editable() {  # must do 'set comment' before trying to run this method

    $this->comment_is_editable = FALSE;
    $username = $this->db_get_username();

    $has_supervisor_role = FALSE;
    $user_rolestring = $this->read_session_parm( 'user_roles' );
    $user_roles = explode( ',', $user_rolestring );
    foreach( $user_roles as $user_role ) {
      $user_role = str_replace( "'", '', $user_role );
      $user_role = trim( $user_role );
      if( $user_role == 'super' ) {
        $has_supervisor_role = TRUE;
        break;
      }
    }

    if( $has_supervisor_role || $username == $this->get_supervisor())
      $this->comment_is_editable = TRUE;

    elseif( $this->creation_user == $username )
      $this->comment_is_editable = TRUE;

    elseif( $this->proj_is_member_of_same_research_group( $this->creation_user ))
      $this->comment_is_editable = TRUE;

    else {
      $creator = $this->decode_comment_writer( $this->creation_user );
      if( $creator == PROJ_INITIAL_IMPORT ) {
        $this->set_default_comment_creator_usernames();
        if( in_array( $username, $this->default_comment_creator_usernames ))
          $this->comment_is_editable = TRUE;
      }
    }

    return $this->comment_is_editable;
  }
  #----------------------------------------------------------------------------------

  function set_comment_source_desc() {

    $this->comment_source_desc = '';
    if( ! $this->comment_id ) return;

    $creator = $this->decode_comment_writer( $this->creation_user );
    $changer = $this->decode_comment_writer( $this->change_user );

    if( $creator == PROJ_INITIAL_IMPORT ) $creator = strtolower( $creator );
    if( $changer == PROJ_INITIAL_IMPORT ) $changer = strtolower( $changer );

    $this->comment_source_desc  = ' (Note created ' . $this->postgres_date_to_words( $this->creation_timestamp );
    $this->comment_source_desc .= ' by ' . $creator . '.';

    if( $this->change_timestamp > $this->creation_timestamp ) {
      $this->comment_source_desc .= ' Last changed ' . $this->postgres_date_to_words( $this->change_timestamp );
      $this->comment_source_desc .= ' by ' . $changer . '.';
    }

    $this->comment_source_desc .= ')';
  }
  #----------------------------------------------------------------------------------

  function echo_comment_source_desc() {

    if( $this->comment_source_desc ) {
      HTML::div_start( 'class="workfield"' );
      HTML::span_start( 'class="workfieldaligned"' );
      HTML::italic_start();

      $this->echo_safely( $this->comment_source_desc );

      HTML::italic_end();
      HTML::span_end();
      HTML::div_end( 'workfield' );

      echo LINEBREAK;
    }
  }
  #----------------------------------------------------------------------------------

  function display_or_edit_comment() {

    $this->set_comment_source_desc();

    if( ! $this->comment_is_editable() ) {
      $this->comment_display();
    }
    else {  # comment is editable
      $this->comment_entry_field();
      $this->comment_deletion_field();
    }

    $this->echo_comment_source_desc();
    HTML::new_paragraph();

  }
  #----------------------------------------------------------------------------------

  function comment_display() {

    $fieldname = 'readonly_comment_' . $this->comment_id;
 
    HTML::div_start( 'class="workfield"' );

    if( strlen( $this->comment ) <= $this->get_comment_field_cols() 
    && ! $this->string_contains_substring( $this->comment, NEWLINE ) ) {

      HTML::input_field( $fieldname, 
                         $label = $this->label, 
                         $value = $this->comment, 
                         $in_table = FALSE, 
                         $size = $this->get_comment_field_cols(), 
                         $tabindex = 1, NULL, NULL, 
                         $data_parms = 'READONLY class="highlighted"' ); 
    }
    else {

      $this->proj_textarea( $fieldname, 
                            $rows = $this->get_comment_field_rows(), 
                            $cols = $this->get_comment_field_cols(), 
                            $this->comment, 
                            $this->label,
                            'READONLY class="highlighted"' );
    }
    HTML::div_end( 'workfield' );
  }
  #----------------------------------------------------------------------------------

  function get_comment_field_cols( $cols = NULL ) {

    if( ! $cols ) $cols = FLD_SIZE_NOTES_ON_WORK_COLS;

    return $cols;
  }
  #----------------------------------------------------------------------------------

  function get_comment_field_rows( $cols = NULL ) {

    $comment_length = strlen( $this->comment );

    if( ! $cols ) $cols = $this->get_comment_field_cols();

    if( $comment_length && $cols ) {
      $rows = $comment_length / $cols;
      $rows = ceil( $rows );
      $rows++;

      $newline_count = substr_count( $this->comment, NEWLINE );
      $newline_count++;
      if( $newline_count > $rows ) $rows = $newline_count;

      if( $rows > MAX_COMMENT_ROWS ) $rows = MAX_COMMENT_ROWS;
    }
    else
      $rows = FLD_SIZE_NOTES_ON_WORK_ROWS;

    return $rows;
  }
  #----------------------------------------------------------------------------------

  function comment_entry_field() {

    HTML::div_start( 'class="workfield"' );

    if( $this->comment_id ) HTML::hidden_field( $this->existing_comment_id_field, $this->comment_id,
                                                $input_instance = $this->comment_id );

    if( $this->comment_id )
      $fieldname = $this->existing_comment_field;
    else
      $fieldname = $this->new_comment_field;
   
    if( strlen( $this->comment ) <= $this->get_comment_field_cols()
    && ! $this->string_contains_substring( $this->comment, NEWLINE ) ) {
      HTML::input_field( $fieldname, 
                         $label = $this->label, 
                         $value = $this->comment, 
                         $in_table = FALSE, 
                         $size = $this->get_comment_field_cols(), 
                         $tabindex = 1, NULL, NULL, NULL, 
                         $input_instance = $this->comment_id );
    }
    else {
      $this->proj_textarea( $fieldname, 
                            $rows = $this->get_comment_field_rows(), 
                            $cols = $this->get_comment_field_cols(), 
                            $this->comment, 
                            $this->label,
                            NULL, NULL,
                            $input_instance = $this->comment_id );
    }

    HTML::div_end( 'workfield' );

    # If this is a new note (so we don't have to worry about whether it's read-only or not),
    # and if we know the name of the current form, allow selection of bibliographical references from pick-list.
    if( $this->form_name && $fieldname == $this->new_comment_field ) {  
      $this->proj_publication_popups( $calling_field = $this->new_comment_field );
    }
  }
  #----------------------------------------------------------------------------------

  function comment_deletion_field() {

    if( $this->comment_id ) {  # existing comment
      #echo LINEBREAK;

      $fieldname = $this->delete_comment_field;
      $span_id = 'comment_deletion_span_' . $this->comment_id;
      $normal_class = 'workfieldaligned';
      $warning_class = $normal_class . ' ' . 'warning';
      $change_display_scriptname = 'change_display_of_' . $span_id;

      $script = "function $change_display_scriptname( chkbox ) {"           . NEWLINE
              . "  var theSpan = document.getElementById( '$span_id' );"    . NEWLINE
              . '  if( chkbox.checked ) {'                                  . NEWLINE
              . '    alert( "Note will be deleted when you click Save!" );' . NEWLINE
              . "    theSpan.className = '$warning_class';"           . NEWLINE
              . '  }'                                                       . NEWLINE 
              . '  else {'                                                  . NEWLINE
              . '    alert( "Note will not now be deleted." );'             . NEWLINE
              . "    theSpan.className = '$normal_class';"            . NEWLINE
              . '  }'                                                       . NEWLINE 
              . '}'                                                         . NEWLINE;
      HTML::write_javascript_function( $script );

      HTML::span_start( 'class="' . $normal_class . '" id="' . $span_id . '"' );
      $parms = 'onclick="' . $change_display_scriptname . '( this )"';
             
      HTML::checkbox( $fieldname, $label = 'Delete the above note', $is_checked = NULL, 
                      $value_when_checked = $this->comment_id, $in_table = FALSE,
                      $tabindex=1, 
                      $input_instance = $this->comment_id, 
                      $parms );

      HTML::span_end();
      echo LINEBREAK;
    }
  }
  #----------------------------------------------------------------------------------

  function decode_comment_writer( $username ) {

    if( $username == PROJ_INITIAL_IMPORT ) return $username;

    if( ! $this->is_alphanumeric( $username )) die( 'Invalid input.' );

    if( $username == 'postgres' ) return PROJ_INITIAL_IMPORT;

    $supervisor_connection = new DBQuery( $this->get_supervisor());
    $decoder = new Comment( $supervisor_connection );

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

  function set_default_comment_creator_usernames() {

    $this->default_comment_creator_usernames = array();
    $core_catalogue = $this->proj_get_core_catalogue();

    switch( $core_catalogue ) {

      case 'wallis':
        $this->default_comment_creator_usernames[] = 'cofkphilip';
        # fall-through

      case 'cardindex':
        $this->default_comment_creator_usernames[] = 'cofkkim';
        $this->default_comment_creator_usernames[] = 'cofkmiranda';
        break;

      case 'lister':
        $this->default_comment_creator_usernames[] = 'cofkannamarie';
        break;

      case 'comenius':
      case 'comenius2':
        $this->default_comment_creator_usernames[] = 'cofkkaterina';
        $this->default_comment_creator_usernames[] = 'cofkiva';
        $this->default_comment_creator_usernames[] = 'cofkvladimir';
        break;

      case 'hartlib':
        $this->default_comment_creator_usernames[] = 'cofkleigh';
        $this->default_comment_creator_usernames[] = 'cofkhoward';
        break;

      case 'lhwyd':
        $this->default_comment_creator_usernames[] = 'cofkhelen';
        $this->default_comment_creator_usernames[] = 'cofkrichard';
        break;

      case 'aubrey':
        $this->default_comment_creator_usernames[] = 'cofkkelsey';
        break;
    }
  }
  #----------------------------------------------------------------------------------

  function save_comments( $other_table, $other_id, $comment_type ) {

    if( ! $other_table || ! $other_id || ! $comment_type ) 
      die( 'Invalid input when saving comments.' );

    $this->set_comment_fieldnames( $comment_type );

    $new_comment = $this->read_post_parm( $this->new_comment_field );

    $existing_comments = $this->read_post_parm( $this->existing_comment_parm );
    $existing_comment_ids = $this->read_post_parm( $this->existing_comment_id_parm );
    $deletion_checkboxes = $this->read_post_parm( $this->delete_comment_parm );

    #------------------
    # Existing comments
    #------------------
    $index = -1;
    if( is_array( $existing_comment_ids )) {
      foreach( $existing_comment_ids as $comment_id ) {
        $index++;
        $delete_this_one = FALSE;
        $comment = '';

        if( is_array( $deletion_checkboxes )) {
          if( in_array( $comment_id, $deletion_checkboxes )) {
            $delete_this_one = TRUE;
          }
        }

        if( ! $delete_this_one ) {
          $comment = $existing_comments[ $index ];

          # Delete blank comments
          $stripped_comment = $this->strip_comment( $comment );
          if( $stripped_comment == '' ) $delete_this_one = TRUE;
        }

        if( $delete_this_one ) {

          # Delete the relationship between the comment and the other table
          $this->rel_obj->delete_relationship( $left_table_name = $this->proj_comment_tablename(), 
                                               $left_id_value = $comment_id, 
                                               $relationship_type = $comment_type,
                                               $right_table_name = $other_table,
                                               $right_id_value = $other_id ); 

          $comment_still_in_use = $this->rel_obj->value_exists_on_either_side( $this->proj_comment_tablename(), 
                                                                               $comment_id );

          if( ! $comment_still_in_use ) {
            $statement = 'delete from ' . $this->proj_comment_tablename() . " where comment_id = $comment_id";
            $this->db_run_query( $statement );
          }
        }

        else {  # update existing comment if it has changed
          $statement = 'update ' . $this->proj_comment_tablename() 
                     . " set comment = '" . $this->escape( $comment ) . "' "
                     . " where comment != '" . $this->escape( $comment ) . "' "
                     . " and comment_id = $comment_id";
          $this->db_run_query( $statement );
        }
      }
    }

    #------------
    # New comment
    #------------
    $stripped_comment = $this->strip_comment( $new_comment );
    if( $stripped_comment > '' ) {  # save new comment

      $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_comment_tablename()) . "'::regclass )";
      $comment_id = $this->db_select_one_value( $statement );

      $statement = 'insert into ' . $this->proj_comment_tablename() . ' ( comment_id, comment ) values ( '
                 . " $comment_id, '" . $this->escape( $new_comment ) . "' )";
      $this->db_run_query( $statement );

      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_comment_tablename(),
                                           $left_id_value = $comment_id, 
                                           $relationship_type = $comment_type,
                                           $right_table_name = $other_table,
                                           $right_id_value = $other_id ); 
    }
  }
  #----------------------------------------------------------------------------------

  function strip_comment( $comment ) {

    $stripped_comment = str_replace( NEWLINE, '', $comment );
    $stripped_comment = str_replace( CARRIAGE_RETURN, '', $stripped_comment );
    $stripped_comment = trim( $stripped_comment );
    return $stripped_comment;

  }
  #----------------------------------------------------------------------------------

  function read_new_comment_from_post( $comment_type ) {

    $this->set_comment_fieldnames( $comment_type );

    return $this->read_post_parm( $this->new_comment_field );
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'comment_id':
      case 'relationship_id':
        return $this->is_integer( $this->parm_value );

      case 'comment':
      case "$this->new_comment_field":
        return $this->is_ok_free_text( $this->parm_value );

      case "$this->existing_comment_parm":
        if( $this->parm_value == NULL ) return TRUE;
        return $this->is_array_of_ok_free_text( $this->parm_value );

      case "$this->existing_comment_id_parm":
      case "$this->delete_comment_parm":
        if( $this->parm_value == NULL ) return TRUE;
        return $this->is_array_of_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
