<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Collection-specific methods for the Bodleian Card Catalogue (also known as the
# Selden End card index) which forms a special subset of the Union Catalogue.
#====================================================================================

define( 'SELDEN_RIGHTHAND_SAVE_KEY_POS', 690 );
define( 'SELDEN_ENLARGED_IMAGE_WIDTH_PX', 500 );

class Selden_Work extends Editable_Work {

  #----------------------------------------------------------------------------------

  function Selden_Work( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Editable_Work( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function set_work( $iwork_id = NULL ) {

    parent::set_work( $iwork_id );

    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $relationship_type == RELTYPE_MANIFESTATION_IS_OF_WORK ) {
        $this->manifestation_id = $left_id_value;
        $this->manifest_obj->set_manifestation( $this->manifestation_id );
        break;  # Selden End data only has one manifestation per work
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_search_table() {

    if( $this->is_expanded_view())
      $tab = $this->proj_work_viewname();
    else
      $tab = $this->proj_compact_work_viewname();
    $tab = str_replace( 'union', 'cardindex', $tab ); # if in the Card Index editing interface, get cards only
    return $tab;
  }
  #-----------------------------------------------------

  function list_form_sections() {

    $form_sections = array( 'editors_notes'        => "Editor's notes",
                            'card_image'           => 'Index card image',
                            'document_details'     => 'Document details',
                            'authors'              => 'Authors/senders',
                            'recipients'           => 'Addressees',
                            'date_of_work'         => 'Date of work', 
                            'origin'               => 'Places',
                            'language'             => 'Language',
                            'content'              => 'Content',
                            'notes_on_work'        => "Card compiler's notes" );
    return $form_sections;
  }
  #-----------------------------------------------------

  function form_section_links( $curr_section = NULL, $start_with_linebreak = TRUE ) {

    if( $start_with_linebreak ) echo LINEBREAK;

    $this->form_section_anchor( $curr_section );

    $form_sections = $this->list_form_sections();

    $first = TRUE;
    foreach( $form_sections as $section => $display ) {
      if( ! $first ) echo ' | ';
      HTML::link( '#' . $section . '_anchor', $display );
      $first = FALSE;
    }
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function form_section_anchor( $curr_section ) {

    $form_sections = $this->list_form_sections();

    if( $curr_section ) {
      if( key_exists( $curr_section, $form_sections ))
        HTML::anchor( $curr_section . '_anchor' );
      else
        echo 'Did not find anchor ' . $curr_section;
    }
  }
  #-----------------------------------------------------

  function write_work_entry_form( $new_record, $just_saved ) {

    $focus_script = '';

    $this->write_work_entry_stylesheet();
    $this->write_tabform_stylesheet();
    $this->date_entity->write_date_entry_stylesheet();
    $this->write_display_change_script();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_work', 
                                         NULL, NULL, $onsubmit_validation = TRUE );

    if( ! $new_record ) {
      HTML::hidden_field( 'iwork_id', $this->iwork_id );
      HTML::hidden_field( 'opening_method', $this->opening_method );

      HTML::h3_start();
      echo $this->get_work_desc( $this->work_id );
      HTML::h3_end();

      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $this->opening_method );
    }

    $this->selden_end_fields();

    HTML::form_end();
    HTML::new_paragraph();

    if( $focus_script ) HTML::write_javascript_function( $focus_script );
  }
  #-----------------------------------------------------

  function button_area() {

    parent::button_area( $start_with_horizontal_rule = FALSE );

    $statement = 'select max(iwork_id) from ' . $this->get_collection_setting( 'work' );
    $max_iwork_id = $this->db_select_one_value( $statement );

    if( $this->iwork_id > 1 ) {
      echo ' ';
      $this->write_prev_iwork_id_field();
      HTML::submit_button( 'prev_button', 'Prev' );
    }

    if( $this->iwork_id < $max_iwork_id ) {
      echo ' ';
      $this->write_next_iwork_id_field();
      HTML::submit_button( 'next_button', 'Next' );
    }

    HTML::italic_start();
    echo ' (Next/Prev in order of card no.)';
    HTML::italic_end();

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function exclude_index_cards_clause() {
    return " date_of_work_std != '1900-01-01' ";
  }
  #-----------------------------------------------------
  function write_next_iwork_id_field() {

    $statement = 'select min(iwork_id) from ' . $this->get_collection_setting( 'work' )
               . " where iwork_id > $this->iwork_id";
    $statement .= ' and ' . $this->exclude_index_cards_clause();
    $next_iwork_id = $this->db_select_one_value( $statement );
    HTML::hidden_field( 'next_iwork_id', $next_iwork_id );
  }
  #-----------------------------------------------------
  function write_prev_iwork_id_field() {

    $statement = 'select max(iwork_id) from ' . $this->get_collection_setting( 'work' )
               . " where iwork_id < $this->iwork_id";
    $statement .= ' and ' . $this->exclude_index_cards_clause();
    $prev_iwork_id = $this->db_select_one_value( $statement );
    HTML::hidden_field( 'prev_iwork_id', $prev_iwork_id );
  }
  #-----------------------------------------------------

  function selden_end_fields() {  # We only have one tab for Selden End

    if( $this->iwork_id ) {
      HTML::new_paragraph();
      HTML::italic_start();
      echo 'Card no. ' . $this->iwork_id;
      echo ' -- Last changed: ' . $this->postgres_date_to_words( $this->change_timestamp );
      echo ' by ' . strtolower( $this->change_user );
      HTML::italic_end();
    }

    $this->form_section_links( 'editors_notes' );
    HTML::new_paragraph();

    $this->editors_notes_field();

    $this->button_area();

    $this->display_image();

    HTML::new_paragraph();
    $this->form_section_links( 'document_details' );

    HTML::bold_start();
    echo 'Document details:';
    HTML::bold_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();
    $this->manifest_obj->manifestation_type_field();

    HTML::new_paragraph();
    $this->manifest_obj->shelfmark_field();
    HTML::new_paragraph();
    $this->extra_save_button( 'manifestation' );
    HTML::div_end( 'workfield' );

    HTML::new_paragraph();
    $this->form_section_links( 'authors' );
    $this->author_sender_field();
    HTML::new_paragraph();
    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'author_sender' );
    HTML::div_end( 'workfield' );

    $this->form_section_links( 'recipients' );
    $this->addressee_field();
    HTML::new_paragraph();
    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'addressee' );
    HTML::div_end( 'workfield' );

    HTML::new_paragraph();
    $this->form_section_links( 'date_of_work' );
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::bold_start();
    echo 'Date of work:';
    HTML::new_paragraph();
    HTML::bold_end();

    $this->date_fields();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'dates', $new_paragraph = FALSE );
    HTML::div_end( 'workfield' );

    HTML::new_paragraph();
    $this->form_section_links( 'origin' );
    HTML::horizontal_rule();
    HTML::new_paragraph();
    $this->places_tab();

    HTML::new_paragraph();
    $this->form_section_links( 'language' );
    HTML::horizontal_rule();
    HTML::new_paragraph();

    HTML::bold_start();
    echo 'Language:';
    HTML::bold_end();
    HTML::new_paragraph();

    $this->languages_field();

    $this->form_section_links( 'content' );
    HTML::new_paragraph();

    HTML::bold_start();
    echo 'Content:';
    HTML::bold_end();
    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->abstract_field();
    HTML::div_end( 'workfield' );

    $this->extra_save_button( 'abstract', $new_paragraph = FALSE , 
                              $parms='style="position: relative; left: ' . SELDEN_RIGHTHAND_SAVE_KEY_POS . 'px"'  );
    HTML::new_paragraph();

    $this->people_mentioned_field();
    HTML::new_paragraph();
    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'people_mentioned' );
    HTML::div_end( 'workfield' );
    HTML::new_paragraph();

    $this->form_section_links( 'notes_on_work' );
    HTML::horizontal_rule();
    HTML::new_paragraph();

    $this->notes_on_work_field();
    HTML::new_paragraph();
    $this->extra_save_button( 'card_compilers_notes' );

    #HTML::write_javascript_function( 'document.' . $this->form_name . '.editors_notes.focus()' );
  }
  #-----------------------------------------------------

  function display_image() {

    HTML::new_paragraph();
    $this->form_section_links( 'card_image', $start_with_linebreak = FALSE );

    $this->manifest_obj->display_fullsize_images();

    HTML::new_paragraph();
  }
  #-----------------------------------------------------
  function date_notes_field() {  # We won't have separate date notes for Selden End
  }
  #-----------------------------------------------------

  function notes_on_authors_field() {
  }
  #-----------------------------------------------------

  function notes_on_addressees_field() {
  }
  #-----------------------------------------------------

  function assess_submit_key() {

    $action = 'edit';

    if( $this->parm_found_in_post( 'next_button' )) {
      $next_iwork_id = $this->read_post_parm( 'next_iwork_id' );
      $this->write_post_parm( 'iwork_id', $next_iwork_id );
      return $action;
    }

    elseif( $this->parm_found_in_post( 'prev_button' )) {
      $prev_iwork_id = $this->read_post_parm( 'prev_iwork_id' );
      $this->write_post_parm( 'iwork_id', $prev_iwork_id );
      return $action;
    }

    return parent::assess_submit_key();
  }
  #-----------------------------------------------------

  function get_default_order_by_col() {
    return 'iwork_id';
  }
  #-----------------------------------------------------

  function db_choose_order_by_col( $possible_order_by_cols = NULL, $default_order_by_col = '1' ) {

    parent::db_choose_order_by_col( $possible_order_by_cols, $default_order_by_col );

    $order_by = $this->read_post_parm( 'order_by' );
    if( $order_by && $order_by != 'iwork_id' ) echo ' (then by card no.) ';
  }
  #-----------------------------------------------------

  function db_list_columns( $table_or_view ) {

    $columns = parent::db_list_columns( $table_or_view );

    $i = 0;
    foreach( $columns as $crow ) {
      $column_name = $crow[ 'column_name' ];

      switch( $column_name ) {

        case 'iwork_id':
          $columns[ $i ]['column_label'] = 'Card no.';
          $columns[ $i ]['search_help_text'] = 'Order within the Selden End card index EAD.';
          break;

        case 'manifestations_for_display':
        case 'manifestations_searchable':
          $columns[ $i ][ 'column_label' ] = 'Shelfmark';
          break;

        default:
          break;
      }

      $i++;
    }
    return $columns;
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name = NULL ) {

    switch( $column_name ) {

      case 'iwork_id':
        return 'Card no.';

      case 'original_notes':
        return "Card compiler's notes";

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    $column_value = parent::db_browse_reformat_data( $column_name, $column_value );

    if( $column_name == 'images' ) {
      if( ! $this->printable_output ) {
        $drawer = $this->current_row_of_data[ 'drawer' ];
        $column_value = $drawer . NEWLINE . $column_value;
      }
    }

    elseif( $column_name == 'abstract' ) {  # add info about document type  etc in same cell as abstract

      $manifestation_type = $this->current_row_of_data[ 'manifestation_type' ];
      if( $manifestation_type ) $column_value = $manifestation_type . '. ' . $column_value;

      $original_notes = $this->current_row_of_data['original_notes'];
      $original_notes = $this->proj_reduce_no_of_blank_lines( $original_notes );

      if( $original_notes ) {
        if( $column_value ) $column_value .= NEWLINE;
        $column_value .= 'Notes by card compiler: ' . $original_notes;
      }
    }

    elseif( $column_name == 'date_of_work_std' ) {  
      if( ! $this->csv_output ) {
        #------------------------------------------------------
        # Add info about relevance to CofK in same cell as date
        #------------------------------------------------------
        $relevant_to_cofk = $this->current_row_of_data['relevant_to_cofk'];

        $button_name = 'relevant_to_cofk_' . $this->current_row_of_data['iwork_id'];
        $image_file = '';
        $msg = '';

        if( $relevant_to_cofk == 'Yes' ) {
          $image_file = PROJ_TINY_LOGO_FILE;
          $msg = 'This record is relevant to Cultures of Knowledge';
        }
        elseif( $relevant_to_cofk == '?' ) {
          $image_file = PROJ_RELEVANCE_UNKNOWN_FILE;
          $msg = 'Not known whether this record is relevant to Cultures of Knowledge';
        }
        else {
          HTML::italic_start();
          echo 'Not relevant' . LINEBREAK;
          HTML::italic_end();
        }

        if( $image_file && $msg ) {
          $this->proj_image_msg_button( $image_file, $msg, $button_name );
          $column_value = NEWLINE . $column_value;
        }

        elseif( $msg )
          $column_value .= NEWLINE . NEWLINE . $msg;
      }
    }

    elseif( $column_name == 'enlarged_images' ) {
      if( ! $this->printable_output && ! $this->csv_output ) {
        $filenames = $this->proj_extract_image_filenames( $column_value );
        if( is_array( $filenames )) {
          foreach( $filenames as $filename ) {
            $work_desc = $this->current_row_of_data[ 'description' ];
            $work_desc = str_replace( '"', "'", $work_desc );
            $work_desc = str_replace( NEWLINE, ' ', $work_desc );
            $work_desc = str_replace( CARRIAGE_RETURN, ' ', $work_desc );

            $img .= '<img src="' . $filename . '" ';
            $img .= ' id="' . $this->proj_convert_to_alphanumeric( $filename ) . '" ';
            $img .= ' width="' . SELDEN_ENLARGED_IMAGE_WIDTH_PX . 'px" ';
            $img .= ' alt="Image of: ' . $this->escape( $work_desc ) . '" ';
            $img .= ' />';
 
            echo $img;

            $column_value = '';
          }
        }
      }
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function editors_notes_field() {

    $this->relevant_to_cofk_field();
    HTML::new_paragraph();

    HTML::bold_start();
    echo "Editor's notes:";
    HTML::bold_end();

    HTML::new_paragraph();

    $this->proj_textarea( 'editors_notes', FLD_SIZE_NOTES_ON_WORK_ROWS, FLD_SIZE_NOTES_ON_WORK_COLS, 
                          $value = $this->editors_notes, $label = NULL );
    HTML::new_paragraph();

  }
  #-----------------------------------------------------

  function notes_on_work_field() {  

    HTML::bold_start();
    echo 'Notes by card compiler:';
    HTML::bold_end();
    HTML::new_paragraph();
    parent::notes_on_work_field();
  }
  #-----------------------------------------------------

  function save_work_fields() {


    $statement = 'BEGIN TRANSACTION';
    $this->db_run_query( $statement );

    #-----------------------------------------
    # Update fields from the core 'work' table
    #-----------------------------------------
    $statement = $this->get_core_work_update_statement();  # reads values for all 'work' columns from Post
    $this->db_run_query( $statement );

    #---------------------------------------------------------------
    # Update shelfmark. (Selden End cards only have 1 manifestation)
    #---------------------------------------------------------------
    $manifestation_id = $this->get_selden_manifestation_id( $this->work_id );
    $id_number_or_shelfmark = $this->read_post_parm( 'id_number_or_shelfmark' );

    $statement = 'update ' . $this->proj_manifestation_tablename() 
               . " set id_number_or_shelfmark = '" . $this->escape( $id_number_or_shelfmark ) . "' "
               . " where manifestation_id = '$manifestation_id' "
               . " and coalesce( id_number_or_shelfmark, '' ) != '" . $this->escape( $id_number_or_shelfmark ) . "'";
    $this->db_run_query( $statement );

    #---------------------
    # Update document type
    #---------------------
    $manifestation_type = $this->read_post_parm( 'manifestation_type' );

    $statement = 'update ' . $this->proj_manifestation_tablename() 
               . " set manifestation_type = '" . $this->escape( $manifestation_type ) . "' "
               . " where manifestation_id = '$manifestation_id' "
               . " and coalesce( manifestation_type, '' ) != '" . $this->escape( $manifestation_type ) . "'";
    $this->db_run_query( $statement );

    #-------------------
    # Save relationships
    #-------------------
    $this->save_author_sender();
    $this->save_addressee();
    $this->save_people_mentioned();
    $this->save_origin();
    $this->save_destination();
    $this->save_comments();

    #-----------------------------------------------------------
    # Check whether related data like manifestations has changed
    # (will have cascaded into 'queryable' table by now).
    # If so, update timestamp/user of work table.
    #-----------------------------------------------------------
    $this->set_wider_timestamp( $this->work_id );
    
    $statement = 'COMMIT';
    $this->db_run_query( $statement );

    echo $this->get_datetime_now_in_words() . " -- Any changes have been saved.";
  }
  #-----------------------------------------------------

  function save_comments( $selected_tab = NULL ) {  # overwrites parent method from Editable Work

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );
     
    $this->comment_obj->save_comments( $this->proj_work_tablename(), 
                                       $this->work_id, 
                                       RELTYPE_COMMENT_REFERS_TO_ENTITY );
  }
  #-----------------------------------------------------

  function field_is_on_current_tab( $fieldname ) {  # The main editing interface has separate tabs
    return TRUE;                                    # but Selden End interface is one single screen.
  }
  #-----------------------------------------------------

  function get_selden_manifestation_id( $work_id ) {
    #------------------------------------------------------
    # Get manifestation ID (Selden End cards only have one)
    #------------------------------------------------------
    $function_name = $this->proj_database_function_name( 'list_rel_ids', $include_collection_code = TRUE );
    $statement = "select id_value from $function_name( "
               . " '" . $this->proj_manifestation_tablename() . "', "  #required_table 
               . " 'is_manifestation_of', "       #required_reltype
               . " '" . $this->proj_work_tablename() . "', "  #known_table
               . " '$work_id' )";                 #known_id
    $manifestation_id = $this->db_select_one_value( $statement );
    return $manifestation_id;
  }
  #-----------------------------------------------------
  function db_write_custom_page_button() {

    parent::db_write_custom_page_button();  # allow swap between main view and alternative view

    $image_enlargement_ids = '';

    # Find out which image files are being linked to
    $first = TRUE;
    foreach( $this->db_one_page_of_results as $row ) {
      if( ! $first ) $image_enlargement_ids .= ', ';
      $iwork_id = $row[ 'iwork_id' ];
      $image_enlargement_ids .= $iwork_id;
      $first = FALSE;
    }

    HTML::form_start( $this->app_get_class( $this ), 'display_images', NULL, $form_target = '_blank' );

    HTML::hidden_field( 'image_enlargement_ids', $image_enlargement_ids );
    HTML::hidden_field( 'order_by', $this->order_by );

    HTML::submit_button( 'display_images_button', 'Display images', $tabindex=1, 'class="pagelist"' );
    HTML::form_end();
  }
  #-----------------------------------------------------

  function display_images() {

    $this->db_set_search_result_parms(); # Read 'order by' value and enable Edit button etc.
    $image_view = $this->get_collection_setting( 'work_image_view' ); # will use this for column list later

    $image_enlargement_ids = $this->read_post_parm( 'image_enlargement_ids' );

    $statement = 'select description, images as enlarged_images, iwork_id from ' . $this->from_table
               . ' where iwork_id in (';
    $statement .= $image_enlargement_ids;
    $statement .= ') order by ' . $this->order_by;

    $results = $this->db_select_into_array( $statement );

    $this->from_table = $image_view;
    $cols = $this->db_list_columns( $this->from_table );
    $this->db_browse_across_page( $results, $cols );
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'next_iwork_id':
      case 'prev_iwork_id':
        return $this->is_integer( $this->parm_value );

      case 'image_enlargement_ids':  # will be a string of integer IDs separated by spaces and commas
        return $this->is_comma_separated_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
