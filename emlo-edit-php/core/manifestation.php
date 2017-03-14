<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'MANIF_FULL_IMAGE_WIDTH_PX', 640 );

define( 'FLD_SIZE_SHELFMARK', 60 );

define( 'FLD_SIZE_MANIF_TYPE', 60 );

define( 'FLD_SIZE_PRINTED_ED_ROWS', 3 );
define( 'FLD_SIZE_PRINTED_ED_COLS', 60 );

define( 'FLD_SIZE_NON_LETTER_ENCLOSURE_ROWS', 5 );
define( 'FLD_SIZE_NON_LETTER_ENCLOSURE_COLS', 60 );

define( 'FLD_SIZE_PAPER_SIZE', 60 );

define( 'FLD_SIZE_PAPER_TYPE_OR_WATERMARK', 60 );

define( 'FLD_SIZE_NUM_PAGES_DOC', 5 );
define( 'FLD_SIZE_NUM_PAGES_TEXT', 5 );

define( 'FLD_SIZE_SEAL_ROWS', 3 );
define( 'FLD_SIZE_SEAL_COLS', 60 );

define( 'FLD_SIZE_POSTAGE_MARKS', 60 );

define( 'FLD_SIZE_ADDRESS_ROWS', 5 );
define( 'FLD_SIZE_ADDRESS_COLS', 60 );


define( 'FLD_SIZE_ENDORSE_ROWS', 3 );
define( 'FLD_SIZE_ENDORSE_COLS', 60 );

define( 'FLD_SIZE_MANIF_LANG', 60 );

define( 'FLD_SIZE_MANIF_INCIPIT_ROWS', 5 );
define( 'FLD_SIZE_MANIF_INCIPIT_COLS', 60 );

define( 'FLD_SIZE_MANIF_EXCIPIT_ROWS', 5 );
define( 'FLD_SIZE_MANIF_EXCIPIT_COLS', 60 );

define( 'FLD_SIZE_MANIF_NOTES_ROWS', 5 );
define( 'FLD_SIZE_MANIF_NOTES_COLS', 60 );

define( 'ENCLOSURE_TD_WIDTH', 525 );

class Manifestation extends Project {

  #----------------------------------------------------------------------------------

  function Manifestation( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->person_obj = new Person( $db_connection );
    $this->repository_obj = new Repository( $this->db_connection );
    $this->rel_obj = new Relationship( $this->db_connection );
    $this->date_entity = new Date_Entity( $this->db_connection );
    
    $this->get_document_types();
    $this->extra_anchors = array();
  }

  #----------------------------------------------------------------------------------

  function set_manifestation( $manifestation_id = NULL ) {

    $this->clear();
    if( ! $manifestation_id ) return NULL;

    $statement = 'select * from ' . $this->proj_manifestation_tablename()
               . " where manifestation_id = '" 
               . $this->escape( $manifestation_id ) . "'";
    $this->db_select_into_properties( $statement );
    if( ! $this->manifestation_id ) {
      $this->die_on_error( 'Invalid manifestation ID.' );
    }

    $this->this_on_right = $this->get_lefthand_values( $manifestation_id );
    $this->this_on_left = $this->get_righthand_values( $manifestation_id );

    $this->set_manifestation_images();

    $this->work_id = $this->get_work_from_manifestation( $manifestation_id );
    $this->set_this_work_details();

    $this->set_repository_id();
  }
  #----------------------------------------------------------------------------------

  function clear() {

    parent::clear();
    $this->repository_obj = new Repository( $this->db_connection );
    $this->get_document_types();
    $this->extra_anchors = array();
  }
  #----------------------------------------------------------------------------------

  function get_document_types() {

    $this->document_types = array();

    $statement = 'select * from ' . $this->proj_document_type_tablename() . ' order by document_type_code';

    $doc_types = $this->db_select_into_array( $statement );

    foreach( $doc_types as $row ) {
      extract( $row, EXTR_OVERWRITE );
      $this->document_types[ "$document_type_code" ] = $document_type_desc;
    }
  }
  #----------------------------------------------------------------------------------

  function get_repository_id() {

    $repository_id = NULL;

    foreach( $this->this_on_left as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $relationship_type == RELTYPE_MANIF_STORED_IN_REPOS ) {
        $repository_id = $right_id_value;
        break;
      }
    }

    return $repository_id;
  }
  #----------------------------------------------------------------------------------

  function set_repository_id() {

    $repository_id = $this->get_repository_id();
    if( is_null( $repository_id )) $repository_id = 0;
    $this->repository_id = $repository_id;
  }
  #----------------------------------------------------------------------------------

  function get_work_from_manifestation( $manifestation_id ) {

    if( ! $manifestation_id ) return NULL;

    $statement = 'select right_id_value from ' . $this->proj_relationship_tablename()
               . " where left_id_value = '" . $this->escape( $manifestation_id ) . "' "
               . " and left_table_name = '" . $this->proj_manifestation_tablename() . "' "
               . " and relationship_type = '" . RELTYPE_MANIFESTATION_IS_OF_WORK . "' "
               . " and right_table_name = '" . $this->proj_work_tablename() . "'";

    $work_id = $this->db_select_one_value( $statement );
    return $work_id;
  }
  #----------------------------------------------------------------------------------

  function set_this_work_details() {

    $this->work_desc = NULL;
    $this->original_catalogue = NULL;

    if( $this->work_id ) {
      $work_obj = new Work( $this->db_connection );
      $work_obj->set_work_by_text_id( $this->work_id );

      $this->work_desc = $work_obj->description;
      $this->original_catalogue = $work_obj->original_catalogue;
    }
  }
  #----------------------------------------------------------------------------------

  function get_manifestations_of_work( $work_id = NULL ) {

    $this->clear();
    if( ! $work_id ) return NULL;
    $this->work_id = $work_id;
    $this->set_this_work_details( $work_id );  # we need to know original catalogue of work so that images
                                               # display correctly for Comenius (non-default thumbnail path)

    $this->manifestation_ids = array();
    $this->manifestations = array();
    $this->manifestation_id_string = '';

    $ids = $this->select_manifestations_of_work( $work_id );
    if( ! is_array( $ids )) return;

    foreach( $ids as $row ) {
      $this->manifestation_ids[] = $row[ 'id_value' ];
    }

    $first = TRUE;
    foreach( $this->manifestation_ids as $manifestation_id ) {

      $this->manifestations[ "$manifestation_id" ] = array();

      if( $first ) $this->manifestation_id_string .= ', ';
      $this->manifestation_id_string .= "'" . $this->escape( $manifestation_id ) . "'";

      $statement = 'select * from ' . $this->proj_manifestation_tablename() 
                 . " where manifestation_id = '" 
                 . $this->escape( $manifestation_id ) . "'";

      $coredata = $this->db_select_into_array( $statement );

      $this->manifestations[ "$manifestation_id" ]['core'] = $coredata[0];

      $this->manifestations[ "$manifestation_id" ]['this_on_left'] = $this->get_righthand_values( $manifestation_id );
      $this->manifestations[ "$manifestation_id" ]['this_on_right'] = $this->get_lefthand_values( $manifestation_id );
      $first = FALSE;
    }
  }
  #----------------------------------------------------------------------------------

  function select_manifestations_of_work( $work_id = NULL ) {

    if( ! $work_id ) return NULL;

    $statement = 'select left_id_value as id_value ' 
               . ' from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '" . $this->proj_manifestation_tablename() . "' "
               . " and relationship_type = '" . RELTYPE_MANIFESTATION_IS_OF_WORK . "' "
               . " and right_table_name = '" . $this->proj_work_tablename() . "' "
               . " and right_id_value = '$work_id' "
               . ' order by relationship_id';

    $ids = $this->db_select_into_array( $statement );
    return $ids;
  }
  #----------------------------------------------------------------------------------

  function get_righthand_values( $manifestation_id = NULL ) {

    if( ! $manifestation_id ) return NULL;

    $order_by='relationship_type, relationship_valid_from, relationship_valid_till, relationship_id';

    $manifestation_on_left = $this->proj_get_righthand_side_of_rels( $this->proj_manifestation_tablename(), 
                                                                     $manifestation_id,
                                                                     $order_by );
    if( is_null( $manifestation_on_left )) $manifestation_on_left = array();
    return $manifestation_on_left;
  }
  #----------------------------------------------------------------------------------

  function get_lefthand_values( $manifestation_id = NULL ) {

    if( ! $manifestation_id ) return NULL;

    $order_by='relationship_type, relationship_valid_from, relationship_valid_till, relationship_id';

    $manifestation_on_right = $this->proj_get_lefthand_side_of_rels( $this->proj_manifestation_tablename(), 
                                                                     $manifestation_id,
                                                                     $order_by );
    if( is_null( $manifestation_on_right )) $manifestation_on_right = array();
    return $manifestation_on_right;
  }
  #----------------------------------------------------------------------------------

  function proj_get_description_from_id( $entity_id ) {  # method from Project

    return $this->get_manifestation_desc( $entity_id );
  }
  #----------------------------------------------------------------------------------

  function get_manifestation_desc( $manifestation_id ) {

    $statement = 'select id_number_or_shelfmark, manifestation_type, printed_edition_details '
               . ' from ' . $this->proj_manifestation_tablename()
               . " where manifestation_id = '" . $this->escape( $manifestation_id ) . "'";
    $mdesc = $this->db_select_into_array( $statement );

    if( count( $mdesc ) != 1 ) return NULL;
    $row = $mdesc[ 0 ];
    extract( $row, EXTR_OVERWRITE );

    $desc = $this->decode_manifestation_type( $manifestation_type );
    if( $id_number_or_shelfmark )
      $desc .= ': ' . $id_number_or_shelfmark;
    if( $printed_edition_details )
      $desc .= ': ' . $printed_edition_details;


    # If this manifestation does not belong to the current work, add work details
    $same_work = FALSE;
    foreach( $this->manifestation_ids as $id ) {
      if( $manifestation_id == $id ) {
        $same_work = TRUE;
        break;
      }
    }

    if( ! $same_work ) {
      $work_id = $this->get_work_from_manifestation( $manifestation_id ) ;
      $work_obj = new Work( $this->db_connection );
      $iwork_id = $work_obj->set_work_by_text_id( $work_id );
      $work_desc = $work_obj->get_work_desc( $work_id );
      if( $work_desc ) $desc .= ' -- Work ID ' . $iwork_id . '. ' . $work_desc;
      $funcname = $this->proj_database_function_name( 'link_to_edit_app', 
                                                      $include_collection_code = TRUE );
      $statement = "select $funcname ( '" . $this->escape( $desc )  . "', "
                 . "'?iwork_id=$iwork_id' )";
      $desc = $this->db_select_one_value( $statement );
    }

    return $desc;
  }
  #----------------------------------------------------------------------------------

  function manifestation_entry( $work_id = NULL, $form_name = NULL  ) {

    if( ! $work_id )   die( 'Missing work ID' );
    if( ! $form_name ) die( 'Missing form name' );

    HTML::hidden_field( 'work_id', $work_id );

    $editing_existing = FALSE;
    if( $this->parm_found_in_post( 'edit_manifestation_button' )) {
      $editing_existing = TRUE;
      $manifestation_id = $this->read_post_parm( 'manifestation_id' );
      HTML::hidden_field( 'manifestation_id', $manifestation_id );
    }
    else
      HTML::hidden_field( 'manifestation_id', NULL ); # value of field to be set by 'Edit' button

    # If you are not already editing an existing manifestation, put up a 'New' button enabling the 'Add' form.
    $this->add_new_manif_button( $editing_existing );

    $editing_existing_manif = $this->display_summary_of_manifestations( $work_id, 
                                                                        $editable = TRUE, 
                                                                        $manifestation_id, 
                                                                        $form_name );

    HTML::new_paragraph();

    HTML::anchor( 'data_entry_form_start' );

    if( $editing_existing ) {
      $this->edit_manifestation( $manifestation_id, $work_id, $form_name ); # Set form property AFTER selecting data
                                                                            # as selection method clears properties
    }
    else {
      $existing_manifestation_ids = $this->manifestation_ids;

      $this->clear();

      $this->work_id = $work_id;
      $this->form_name = $form_name;
      $this->existing_manifestation_ids = $existing_manifestation_ids;

      $this->add_manifestation();
    }

    if( $editing_existing_manif ) {
      $go_to = 'window.location.hash="#data_entry_form_start"';
      HTML::write_javascript_function( $go_to );
    }
  }
  #----------------------------------------------------------------------------------

  function manif_deletion_warning_script() {

    $scriptname = 'manif_deletion_warning';

    $script = "function $scriptname( chkbox ) {"                                   . NEWLINE
            . '  if( chkbox.checked ) {'                                           . NEWLINE
            . '    alert( "Manifestation will be deleted when you click Save!" );' . NEWLINE
            . '  }'                                                                . NEWLINE 
            . '  else {'                                                           . NEWLINE
            . '    alert( "Manifestation will not now be deleted." );'             . NEWLINE
            . '  }'                                                                . NEWLINE 
            . '}'                                                                  . NEWLINE;
    HTML::write_javascript_function( $script );

    return $scriptname;
  }
  #----------------------------------------------------------------------------------


  function display_summary_of_manifestations( $work_id, $editable = FALSE, $manifestation_to_edit = NULL,
                                              $form_name = NULL ) {

    $currently_editing = FALSE;
    $this->get_manifestations_of_work( $work_id );

    if( ! count( $this->manifestations )) {
      HTML::new_paragraph();
      echo 'No manifestation details found.';
      HTML::new_paragraph();
      return $currently_editing;
    }

    $warning_script = $this->manif_deletion_warning_script();
    $chkbox_parms = 'onclick="' . $warning_script . '( this )"';

    $edit_button_cell_parms = 'class="highlight2"';
    $edit_row_parms = ''; 

    if( $editable && $manifestation_to_edit ) {
      $currently_editing = TRUE;
      $edit_button_cell_parms = '';  # highlight edited row rather than column with Edit button
      $edit_row_parms = 'class="editing_manif"';
    }

    HTML::table_start( 'class="datatab spacepadded"' );
    HTML::table_caption( 'Existing manifestations' );

    HTML::tablerow_start();
    if( $editable ) {
      HTML::column_header( 'Edit', $edit_button_cell_parms );
      HTML::column_header( 'Del' );
    }
    HTML::column_header( 'Repository' );
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      HTML::column_header( 'ID, title, shelfmark, folios, bibliographic refs.' );
    else
      HTML::column_header( 'ID / shelfmark / printed edition' );
    HTML::column_header( 'Document type' );
    HTML::column_header( 'Image' );
    HTML::column_header( 'Further details' );
    HTML::tablerow_end();

    $mrow = 0;
    foreach( $this->manifestations as $one_manifestation ) {
      $mrow++;

      $this->core = $one_manifestation[ 'core' ];
      $this->this_on_left = $one_manifestation[ 'this_on_left' ];
      $this->this_on_right = $one_manifestation[ 'this_on_right' ];
      $this->set_manifestation_images();

      $this->manifestation_id = $this->core[ 'manifestation_id' ];

      $editing_this_row = FALSE;

      HTML::tablerow_start();

      #---------------------
      # 'Edit' button column
      #---------------------
      if( $editable ) {
        if( ! $form_name ) {
          die( 'Missing form name in manifestation summary.' );
        }

        $td_parms = '';
        if( $currently_editing && $this->manifestation_id == $manifestation_to_edit ) {
          $editing_this_row = TRUE;
          $td_parms = $edit_row_parms;
        }
        elseif( ! $currently_editing ) {
          $td_parms = $edit_button_cell_parms;
        }

        HTML::tabledata_start( $td_parms );

        if( $editing_this_row )
            echo 'Editing>';

        elseif( ! $currently_editing ) {
          $set_manifestation_id_script = 'onclick="document.' . $form_name . '.manifestation_id.value='
                                       . "'" . $this->escape( $this->manifestation_id ) . "'" . '"';

          HTML::submit_button( 'edit_manifestation_button', 'Edit', $tabindex=1, $set_manifestation_id_script );
        }

        HTML::tabledata_end();
      }

      $td_parms = '';
      if( $editing_this_row ) $td_parms = $edit_row_parms;

      #-------------------------
      # 'Delete' checkbox column
      #-------------------------
      if( $editable ) {
        if( $currently_editing ) {
          HTML::tabledata( ' ', $td_parms );
        }
        else {
          HTML::tabledata_start();
          
          HTML::checkbox( $fieldname = 'delete_manifestation[]', 
                          $label = NULL, 
                          $is_checked = FALSE, 
                          $value_when_checked = $this->manifestation_id, 
                          $in_table = FALSE,
                          $tabindex=1, 
                          $input_instance = $mrow, 
                          $parms = $chkbox_parms . ' ' . $td_parms );

          HTML::tabledata_end();
        }
      }

      #--------------------
      # Normal data columns
      #--------------------
      HTML::tabledata_start( $td_parms );
      $this->echo_repository();
      $this->echo_former_owners();
      HTML::tabledata_end();

      HTML::tabledata_start( $td_parms );
      $this->echo_shelfmark_or_printed_edition();
      HTML::tabledata_end();

      HTML::tabledata_start( $td_parms );
      $this->echo_manifestation_type();
      HTML::tabledata_end();

      HTML::tabledata_start( $td_parms );
      $this->echo_image_links();
      HTML::tabledata_end();

      HTML::tabledata_start( $td_parms );
      $this->echo_further_details();
      HTML::tabledata_end();

      HTML::tablerow_end();
    }
    HTML::table_end();

    return $currently_editing;
  }
  #----------------------------------------------------------------------------------

  function echo_repository() {

    $repository_id = $this->get_repository_id(); # check whether it is worth selecting repository name

    if( $repository_id ) {
      $repository_name = $this->repository_obj->get_lookup_desc( $repository_id );
    }

    $this->echo_safely( $repository_name );
  }
  #----------------------------------------------------------------------------------

  function set_manifestation_images() {

    $this->images = array();

    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $relationship_type == RELTYPE_IMAGE_IS_OF_MANIF ) {
        $image_id = $left_id_value;
        $this->images[ $image_id ] = NULL;
      }
    }

    if( count( $this->images ) > 0 ) {
      foreach( $this->images as $image_id => $f ) {
        $statement = 'select image_filename from ' . $this->proj_image_tablename()
                   . " where image_id=$image_id";
        $image_filename = $this->db_select_one_value( $statement );
        $this->images[ $image_id ] = $image_filename;
      }
    }
  }
  #----------------------------------------------------------------------------------

  function display_fullsize_images() {

    if( count( $this->images ) > 0 ) {
      foreach( $this->images as $image_id => $image_filename ) {
        echo $this->proj_make_image_link( $image_filename, $width_px = MANIF_FULL_IMAGE_WIDTH_PX );
      }
    }
  }
  #----------------------------------------------------------------------------------

  function echo_image_links() {

    if( count( $this->images ) > 0 ) {
      $i = 0;
      foreach( $this->images as $image_id => $image_filename ) {
        $i++;
        if( $i > 1 ) {
          $extension = $this->proj_get_file_extension( $image_filename );
          $displayable = $this->proj_is_displayable_image_file( $extension );

          if( $displayable ) # image links don't need separating by a vertical bar, text links do.
            echo ' ';
          else
            echo ' | ';
        }
        echo $this->proj_make_image_link( $image_filename );
      }
    }
  }
  #----------------------------------------------------------------------------------

  function decode_manifestation_type( $doc_type_code ) {

    $doc_type_desc = $this->document_types[ "$doc_type_code" ];
    return $doc_type_desc;
  }
  #----------------------------------------------------------------------------------

  function echo_manifestation_type() {

    $doc_type_code = $this->core[ 'manifestation_type' ];
    $doc_type_desc = $this->decode_manifestation_type( $doc_type_code );

    $this->echo_safely( $doc_type_desc );

    $this->echo_enclosure_details();
  }
  #----------------------------------------------------------------------------------

  function echo_shelfmark_or_printed_edition() {

    #echo $this->manifestation_id . LINEBREAK;

    if( $this->core[ 'id_number_or_shelfmark' ] ) {
      $this->echo_safely( $this->core[ 'id_number_or_shelfmark' ] );
    }

    # People are supposed to create separate manifestations for MSS and printed eds. 
    # But sometimes they don't, so let's show both rather than either/or.
    echo ' ';

    if( $this->core[ 'id_number_or_shelfmark' ] && $this->core[ 'printed_edition_details' ] ) {
      echo LINEBREAK . LINEBREAK;  # IMPAcT in fact uses 'printed edition details' for 'bibliographic references'
                                   # so often will have both printed edition and shelfmark.
    }

    if( $this->core[ 'printed_edition_details' ] ) {
      $this->echo_safely( $this->core[ 'printed_edition_details' ] );
    }
  }
  #----------------------------------------------------------------------------------

  function echo_enclosure_details() {

    $enclosed_ids = array();
    $enclosing_ids = array();

    # See if this manifestation had enclosures
    # These may have been manifestations of a work, OR something like money or 'a case of rifles'.
    # If money, case of rifles etc, we'll use the 'non_letter_enclosures' text field.
    # If the enclosure was a letter (manifestation of work), use relationships table.

    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $relationship_type == RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF ) {
        if( $left_table_name == $this->proj_manifestation_tablename()) {
          $enclosed_id = $left_id_value;
          $enclosed_ids[] = $enclosed_id;
        }
      }
    }

    # See if this manifestation was itself an enclosure
    foreach( $this->this_on_left as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $relationship_type == RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF ) {
        $enclosing_id = $right_id_value;
        $enclosing_ids[] = $enclosing_id;
      }
    }

    if( count( $enclosing_ids )) {
      foreach( $enclosing_ids as $enclosing ) {
        HTML::new_paragraph();
        echo $this->proj_get_field_label( 'enclosed_in' ) . ': ';
        $this->echo_safely( $this->get_manifestation_desc( $enclosing ));
      }
    }

    if( count( $enclosed_ids )) {
      foreach( $enclosed_ids as $enclosed ) {
        HTML::new_paragraph();
        echo $this->proj_get_field_label( 'enclosure' ) . ': ';
        $this->echo_safely( $this->get_manifestation_desc( $enclosed ));
      }
    }
  }
  #----------------------------------------------------------------------------------

  function echo_former_owners() {

    $former_owner_ids = array();
    $former_owner_dates = array();

    # See if this manifestation had former owners
    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );

      if( $relationship_type == RELTYPE_PERSON_OWNED_MANIF ) {
        $former_owner_id = $left_id_value;
        $former_owner_ids[] = $former_owner_id;
        $former_owner_dates[ "$former_owner_id" ] = array( 'from' => $relationship_valid_from,
                                                           'till' => $relationship_valid_till );
      }
    }

    if( count( $former_owner_ids )) {
      foreach( $former_owner_ids as $owner ) {
        HTML::new_paragraph();
        echo 'Formerly owned by: ';
        $this->echo_safely( $this->person_obj->get_person_desc_from_id( $owner, $using_integer_id = FALSE  ));
        $from = $former_owner_dates[ "$owner" ][ 'from' ];
        $till = $former_owner_dates[ "$owner" ][ 'till' ];
        if( $from ) echo ' from ' . substr( $from, 0 , 4 );  # year only
        if( $till ) echo ' to ' . substr( $till, 0, 4 );  # year only
      }
    }
  }
  #----------------------------------------------------------------------------------

	function echo_further_details() {

		$routing_mark_stamp = null;
		$routing_mark_ms_field = null;

		$handling_instructions_field = null;
		$stored_folded = null;

		$postage_marks = null;
		$postage_costs_as_marked = null;

		$non_delivery_reason = null;
		$date_of_receipt_as_marked = null;
		$paper_size = null;
		$paper_type_or_watermark = null;


		$number_of_pages_of_document = null;
		$number_of_pages_of_text = null;
		$postage_costs = null;
		$seal = null;
		$address = null;
		$routing_mark_ms = null;
		$handling_instructions = null;
		$endorsements = null;


		$language_of_manifestation = null;
		$manifestation_is_translation = null;
		$manifestation_incipit = null;
		$manifestation_excipit = null;
		$manifestation_ps = null;

		extract( $this->core, EXTR_OVERWRITE );

		if( $paper_size ) {
			echo 'Paper size: ';
			$this->echo_safely( $paper_size );
			HTML::new_paragraph();
    	}

		if( $paper_type_or_watermark ) {
			echo 'Paper type, watermark: ';
			$this->echo_safely( $paper_type_or_watermark );
			HTML::new_paragraph();
		}

		if( $stored_folded ) {
			echo $this->db_get_default_column_label( 'stored_folded' ) . ': ';
			$this->echo_safely( $stored_folded );
			HTML::new_paragraph();
		}

		if ($number_of_pages_of_document) {
			echo $this->db_get_default_column_label('number_of_pages_of_document') . ': ';
			$this->echo_safely($number_of_pages_of_document);
			HTML::new_paragraph();
		}

		if ($number_of_pages_of_text) {
			echo $this->db_get_default_column_label('number_of_pages_of_text') . ': ';
			$this->echo_safely($number_of_pages_of_text);
			HTML::new_paragraph();
		}

		if ($postage_marks) {
			echo 'Postage marks: ';
			$this->echo_safely($postage_marks);
			HTML::new_paragraph();
		}

		if ($postage_costs_as_marked) {
			echo $this->db_get_default_column_label('postage_costs_as_marked') . ': ';
			$this->echo_safely($postage_costs_as_marked);
			HTML::new_paragraph();
		}

		if ($postage_costs) {
			echo $this->db_get_default_column_label('postage_costs') . ': ';
			$this->echo_safely($postage_costs);
			HTML::new_paragraph();
		}

		if ($seal) {
			echo 'Seal: ';
			$this->echo_safely($seal);
			HTML::new_paragraph();
		}

		if ($address) {
			echo $this->db_get_default_column_label('address') . ': ';
			$this->echo_safely($address);
			HTML::new_paragraph();
		}
		if ($routing_mark_stamp) {
			echo $this->db_get_default_column_label('routing_mark_stamp') . ': ';
			$this->echo_safely($address);
			HTML::new_paragraph();
		}

		if ($routing_mark_ms) {
			echo $this->db_get_default_column_label('routing_mark_ms') . ': ';
			$this->echo_safely($address);
			HTML::new_paragraph();
		}

		if ($handling_instructions) {
			echo $this->db_get_default_column_label('handling_instructions') . ': ';
			$this->echo_safely($address);
			HTML::new_paragraph();
		}

		if ($endorsements) {
			echo $this->db_get_default_column_label('endorsements') . ': ';
			$this->echo_safely($endorsements);
			HTML::new_paragraph();
		}

		if ($non_delivery_reason) {
			echo $this->db_get_default_column_label('non_delivery_reason') . ': ';
			$this->echo_safely($non_delivery_reason);
			HTML::new_paragraph();
		}

		if ($date_of_receipt_as_marked) {
			echo $this->db_get_default_column_label('date_of_receipt_as_marked') . ': ';
			$this->echo_safely($date_of_receipt_as_marked);
			HTML::new_paragraph();
		}

		if ($language_of_manifestation) {
			echo 'Language of this manifestation: ';
			$this->echo_safely($language_of_manifestation);
			HTML::new_paragraph();
		}

		if ($manifestation_is_translation) {
			echo 'Manifestation is translation.';
			HTML::new_paragraph();
		}

		if ($manifestation_incipit) {
			echo 'Manifestation incipit: ';
			$this->echo_safely($manifestation_incipit);
			HTML::new_paragraph();
		}

		if ($manifestation_excipit) {
			echo $this->proj_get_field_label('manifestation_excipit') . ': ';
			$this->echo_safely($manifestation_excipit);
			HTML::new_paragraph();
		}

		if ($manifestation_ps) {
			echo $this->proj_get_field_label('manifestation_ps') . ': ';
			$this->echo_safely($manifestation_ps);
			HTML::new_paragraph();
		}
	}

	#----------------------------------------------------------------------------------

  function add_new_manif_button( $editing_existing ) {

    echo '<style type="text/css">' . NEWLINE;
    echo ' div.hidden_div {'       . NEWLINE; 
    echo '   display: none; '      . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo ' div.displayed_div {'    . NEWLINE; 
    echo '   display: block; '     . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo '</style>' . NEWLINE;

    $add_button_div_class = 'displayed_div';
    if( $editing_existing ) $add_button_div_class = 'hidden_div';

    HTML::div_start( 'id="new_button_div" class="' . $add_button_div_class . '"' );

    echo 'Add new manifestation: ';
    HTML::button( 'new_manif_button', 'New', 1, 'onclick="show_new_manif_form()"' );

    HTML::div_end();
  }
  #----------------------------------------------------------------------------------

  function add_manifestation() {

    if( ! $this->work_id )   die( 'Missing work ID' );
    if( ! $this->form_name ) die( 'Missing form name' );

    $script = '  function show_new_manif_form() {' . NEWLINE
            . '    var button_div = document.getElementById( "new_button_div" );' . NEWLINE
            . '    var form_div = document.getElementById( "new_manif_div" );'    . NEWLINE
            . '    button_div.className = "hidden_div";'                          . NEWLINE
            . '    form_div.className = "displayed_div";'                         . NEWLINE
            . '    window.location.hash = "#data_entry_form_start";'              . NEWLINE
            . '  }'                                                               . NEWLINE;
    HTML::write_javascript_function( $script );

    HTML::new_paragraph();

    HTML::div_start( 'id="new_manif_div" class="hidden_div"' );

    HTML::h4_start();
    echo 'Add new manifestation:';
    HTML::h4_end();

    $this->manifestation_entry_form();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function edit_manifestation( $manifestation_id = NULL, $work_id = NULL, $form_name = NULL ) {

    if( ! $manifestation_id ) die( 'Missing manifestation ID' );
    if( ! $work_id )    die( 'Missing work ID' );
    if( ! $form_name )  die( 'Missing form name' );

    $this->set_manifestation( $manifestation_id );

    $this->work_id = $work_id;
    $this->form_name = $form_name;

    HTML::new_paragraph();

    HTML::h4_start();
    echo 'Edit manifestation:';
    HTML::h4_end();

    HTML::new_paragraph();

    $this->manifestation_entry_form();

  }
  #-----------------------------------------------------

  function manifestation_entry_form() {

    $this->manif_basic_details_fieldgroup();

    $this->manif_date_fieldgroup();

    $this->manif_former_owners_fieldgroup();

    $this->manif_enclosed_fieldgroup();

    $this->manif_enclosing_fieldgroup();

    $this->manif_paper_and_markings_fieldgroup();

	  $this->receipt_date_fieldgroup();

    $this->manif_language_fieldgroup();

    $this->manif_incipit_and_excipit_fieldgroup();

    $this->manif_notes_fieldgroup();

    $this->manif_scribe_fieldgroup();

    $this->place_of_copying_section(); # in this class is just a stub; must be overridden in child class to activate

    $this->manif_image_fieldgroup();
  }
  #-----------------------------------------------------

  function manif_basic_details_fieldgroup() {

    $this->proj_form_section_links( 'basic_details', $heading_level = 'bold' );
    HTML::div_start( 'class="workfield"' );

    $this->manifestation_title_field(); # For CofK will currently be just a stub. 
                                        # Overridden in IMPAcT child class.

    $this->manifestation_type_field();
    HTML::new_paragraph();

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) { # IMPAcT treats printed editions a bit differently
      HTML::span_start( 'class="workfieldaligned"' );
      HTML::italic_start();
      echo 'Please create separate manifestation records for manuscripts and printed editions.';
      HTML::italic_end();
      HTML::span_end();
      HTML::new_paragraph();
    }

    $this->repository_field();
    HTML::new_paragraph();

    $this->shelfmark_field();
    HTML::new_paragraph();

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      HTML::label( 'Or:' );
      echo LINEBREAK . LINEBREAK;
    }
    HTML::div_end();  # end div workfield

    $this->printed_edition_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'basic_details' );

    HTML::horizontal_rule();
    HTML::div_end();  # end div workfield
  }
  #-----------------------------------------------------

  function manif_date_fieldgroup() {

    $this->date_entity->write_date_entry_stylesheet();

    $this->proj_form_section_links( 'manif_dates', $heading_level = 'bold' );

    $this->date_fields();
  }
  #-----------------------------------------------------

	function receipt_date_fieldgroup() {

		$this->date_entity->write_date_entry_stylesheet();

		$this->proj_form_section_links( 'receipt_date', $heading_level = 'bold' );

		$this->date_receipt_fields();
	}
	#-----------------------------------------------------

  function manif_former_owners_fieldgroup() {

    $this->proj_form_section_links( 'former_owners', $heading_level = 'bold' );
    $this->former_owners_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'former_owners' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_study_fieldgroup() {

    $this->proj_form_section_links( 'manif_study', $heading_level = 'bold' );

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::bold_start();
    echo 'Teachers:';
    HTML::bold_end();
    HTML::new_paragraph();

    $this->taught_manif_field() ;

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'manif_study' );
    HTML::div_end();  # end div workfield
    HTML::horizontal_rule( 'class="pale"' );

    $extra_anchor = 'studied_manif';
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::bold_start();
    echo 'Students:';
    HTML::bold_end();
    HTML::new_paragraph();

    $this->studied_manif_field() ;

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'studied_manif' );
    HTML::div_end();  # end div workfield
    HTML::horizontal_rule( 'class="pale"' );

    $extra_anchor = 'where_manif_was_studied';
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::bold_start();
    echo 'Place where studied:';
    HTML::bold_end();
    HTML::new_paragraph();

    $this->where_manif_was_studied_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'where_manif_was_studied' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_annotator_fieldgroup() {

    $this->proj_form_section_links( 'manif_annotator', $heading_level = 'bold' );

    HTML::div_start( 'class="workfield"' );
    $this->endorsements_field();
    HTML::div_end();  # end div workfield
    HTML::new_paragraph();

    $this->annotated_by_field() ;

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'manif_annotator' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function patrons_of_manif_fieldgroup() {

    $this->proj_form_section_links( 'patrons_of_manif', $heading_level = 'bold' );

    $this->patrons_of_manif_field() ;

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'patrons_of_manif' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();

  }
  #-----------------------------------------------------

  function dedicatees_of_manif_fieldgroup() {

    $this->proj_form_section_links( 'dedicatees_of_manif', $heading_level = 'bold' );

    $this->dedicatees_of_manif_field() ;

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'dedicatees_of_manif' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();

  }
  #-----------------------------------------------------

  function endower_of_manif_fieldgroup() {

    $this->proj_form_section_links( 'endower_of_manif', $heading_level = 'bold' );

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::bold_start();
    echo 'Endower:';
    HTML::bold_end();
    HTML::span_end();
    HTML::new_paragraph();

    $this->endower_of_manif_field() ;

    HTML::new_paragraph();

    $this->notes_on_endowers_field();
    HTML::new_paragraph();
    HTML::horizontal_rule( 'class="pale"');
  }
  #-----------------------------------------------------

  function endowee_of_manif_fieldgroup() {

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::bold_start();
    echo 'Endowee (person or organisation):';
    HTML::bold_end();
    HTML::span_end();
    HTML::new_paragraph();

    $this->endowee_of_manif_field() ;

    HTML::new_paragraph();

    $this->notes_on_endowees_field();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'endower_of_manif' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();

  }
  #-----------------------------------------------------

  function manif_enclosed_fieldgroup() {

    $this->proj_form_section_links( 'enclosures', $heading_level = 0 );
    $this->enclosures_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'enclosures' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_enclosing_fieldgroup() {

    $this->proj_form_section_links( 'enclosed_in', $heading_level = 'bold' );
    $this->enclosing_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'enclosed_in' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_paper_and_markings_fieldgroup() {

    $this->proj_form_section_links( 'paper_and_markings', $heading_level = 'bold' );

    HTML::div_start( 'class="workfield"' );

	  $this->manifestation_opened_field();
	  HTML::new_paragraph();

    $this->paper_size_field();
    HTML::new_paragraph();

	  $this->stored_folded_field();
	  HTML::new_paragraph();

    $this->paper_type_or_watermark_field();
    HTML::new_paragraph();

    $this->number_of_pages_of_document_field();
    HTML::new_paragraph();

    $this->seal_field();
    HTML::new_paragraph();

    $this->postage_marks_field();
    HTML::new_paragraph();

	  $this->postage_costs_as_marked_field();
	  HTML::new_paragraph();
	  $this->postage_costs_field();
	  HTML::new_paragraph();

    $this->address_field();
    HTML::new_paragraph();


	  $this->routing_mark_stamp_field();
	  HTML::new_paragraph();

	  $this->routing_mark_ms_field();
	  HTML::new_paragraph();

	  $this->handling_instructions_field();
	  HTML::new_paragraph();

    $this->endorsements_field();
	  HTML::new_paragraph();

	  $this->non_delivery_reason_field();
	  HTML::new_paragraph();

    $this->extra_save_button( 'paper_and_markings' );

    HTML::div_end();  # end div workfield
    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_language_fieldgroup() {

    $this->proj_form_section_links( 'manif_lang', $heading_level = 'bold' );
    HTML::new_paragraph();

    $this->manifestation_is_translation_field();
    HTML::new_paragraph();

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      HTML::italic_start();
      echo '(It is not necessary to enter language, incipit and explicit of manifestation if they are the same'
           . ' as those of the original work.)';
      HTML::italic_end();
      echo LINEBREAK;
    }

    $this->language_of_manifestation_field();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function manif_incipit_and_excipit_fieldgroup() {

    $this->proj_form_section_links( 'incipit_and_excipit', $heading_level = 'bold' );
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    $this->manifestation_incipit_field();
    HTML::new_paragraph();

    $this->manifestation_excipit_field();

    $this->extra_save_button( 'incipit_and_excipit' );

    HTML::horizontal_rule();
    HTML::div_end();  # end div workfield
  }
  #-----------------------------------------------------

  function manif_notes_fieldgroup() {

    $this->proj_form_section_links( 'manifestation_notes', $heading_level = 'bold' );

    $this->manifestation_notes_field();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'manifestation_notes' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_scribe_fieldgroup() {

    $this->proj_form_section_links( 'scribe_hand', $heading_level = 'bold' );
    $this->scribes_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'scribe_hand' );
    HTML::div_end();  # end div workfield

    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function manif_image_fieldgroup() {

    $this->proj_form_section_links( 'imgs', $heading_level = 'bold' );
    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->image_obj->image_entry_for_manif( $this->manifestation_id );
  }
  #-----------------------------------------------------

  function manifestation_title_field() {  # Override in child class if manifestation title required.
  }
  #-----------------------------------------------------

  function repository_field() {

    if( ! $this->repository_id ) $this->repository_id = 0;

    $this->repository_obj->lookup_table_dropdown( $field_name = 'repository', $field_label = 'Repository', 
                                                  $selected_id = $this->repository_id );
  }
  #-----------------------------------------------------

  function shelfmark_field() {

    HTML::input_field( 'id_number_or_shelfmark', 
                       $this->proj_get_field_label( 'id_number_or_shelfmark' ), 
                       $this->id_number_or_shelfmark,
                       FALSE, FLD_SIZE_SHELFMARK );
  }
  #-----------------------------------------------------

  function date_fields() {

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      echo '(Can be left blank if same as date of work.)';
      HTML::new_paragraph();
    }

    $this->date_as_marked_field() ;
    HTML::new_paragraph();

    if( $this->manifestation_creation_calendar == 'U' ) # Unknown
      $this->manifestation_creation_calendar = '';
    $this->original_calendar_field() ;
    HTML::new_paragraph();

    $this->manifestation_date_field();
    HTML::new_paragraph();

    $this->date_flags();
    HTML::new_paragraph();

    $this->date_notes_field();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'manif_dates' );
    HTML::div_end( 'workfield' );

    HTML::new_paragraph();
    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function date_as_marked_field() {

    HTML::span_start( 'class="workfield"' );

    HTML::input_field( 'manifestation_creation_date_as_marked',  $label = 'Date of manifestation as marked', 
                       $this->manifestation_creation_date_as_marked, 
                       FALSE, FLD_SIZE_DATE_AS_MARKED );
    
    HTML::span_end( 'workfield' );
  }
  #-----------------------------------------------------

  function original_calendar_field() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Calendar used: ' );
    HTML::span_end( 'workfield' );

    $this->date_entity->calendar_selection_field( $fieldname='manifestation_creation_calendar', 
                                                  $selected_calendar = $this->manifestation_creation_calendar ); 
  }
  #-----------------------------------------------------

  function manifestation_date_field() {

    $date_range_help = array();
    $date_range_help[] = 'The manifestation is known to have been produced over a period of two or more days';
    $date_range_help[] = 'The manifestation cannot be precisely dated even to a single year';

    $this->date_entry_fieldset( $fields = array( 'manifestation_creation_date', 
                                                 'manifestation_creation_date2' ),
                                $legend = $this->proj_get_field_label( 'manif_dates' ), 
                                $extra_msg = NULL,
                                $calendar_fieldname = 'manifestation_creation_calendar',
                                $date_range_help );
  }
  #-----------------------------------------------------

  function date_flags() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Issues with date of manifestation: ' );
    HTML::span_end( 'workfield' );

    HTML::ulist_start( 'class="dateflags"' );

    HTML::listitem_start();
    $this->flag_inferred_date_field() ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_uncertain_date_field() ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_approx_date_field() ;
    HTML::listitem_end();

    HTML::ulist_end();
  }
  #-----------------------------------------------------

  function flag_inferred_date_field() {

    $this->basic_checkbox( 'manifestation_creation_date_inferred', 'Date is inferred', 
                           $this->manifestation_creation_date_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_date_field() {

    $this->basic_checkbox( 'manifestation_creation_date_uncertain', 'Date is uncertain', 
                           $this->manifestation_creation_date_uncertain );
  }
  #-----------------------------------------------------

  function flag_approx_date_field() {

    $this->basic_checkbox( 'manifestation_creation_date_approx', 'Date is approximate', 
                           $this->manifestation_creation_date_approx );
  }
  #-----------------------------------------------------





	function date_receipt_fields() {

		$this->date_of_receipt_as_marked_field();
		HTML::new_paragraph();

		if( $this->manifestation_receipt_calendar == 'U' ) # Unknown
			$this->manifestation_receipt_calendar = '';
		$this->receipt_original_calendar_field() ;
		HTML::new_paragraph();

		$this->manifestation_receipt_date_field();
		HTML::new_paragraph();

		$this->receipt_date_flags();
		HTML::new_paragraph();

		HTML::div_start( 'class="workfield"' );
		$this->extra_save_button( 'receipt_date' );
		HTML::div_end( 'workfield' );

		HTML::new_paragraph();
		HTML::horizontal_rule();
	}

	#-----------------------------------------------------

	function receipt_original_calendar_field() {

		HTML::span_start( 'class="workfield"' );
		HTML::label( 'Calendar used: ', NULL, NULL );
		HTML::span_end( 'workfield' );

		$this->date_entity->calendar_selection_field( $fieldname='manifestation_receipt_calendar',
			$selected_calendar = $this->manifestation_receipt_calendar );
	}
	#-----------------------------------------------------

	function manifestation_receipt_date_field() {

		$date_range_help = array();
		$date_range_help[] = 'The receipt cannot be precisely dated';

		$this->date_entry_fieldset( $fields = array( 'manifestation_receipt_date',
			'manifestation_receipt_date2' ),
			$legend = $this->proj_get_field_label( 'receipt_date' ),
			$extra_msg = NULL,
			$calendar_fieldname = 'manifestation_receipt_calendar',
			$date_range_help );
	}
	#-----------------------------------------------------

	function receipt_date_flags() {

		HTML::span_start( 'class="workfield"' );
		HTML::label( 'Issues with receipt date: ' );
		HTML::span_end( 'workfield' );

		HTML::ulist_start( 'class="dateflags"' );

		HTML::listitem_start();
		$this->flag_inferred_receipt_date_field() ;
		HTML::listitem_end();

		HTML::listitem_start();
		$this->flag_uncertain_receipt_date_field() ;
		HTML::listitem_end();

		HTML::listitem_start();
		$this->flag_approx_receipt_date_field() ;
		HTML::listitem_end();

		HTML::ulist_end();
	}
	#-----------------------------------------------------

	function flag_inferred_receipt_date_field() {

		$this->basic_checkbox( 'manifestation_receipt_date_inferred', 'Date is inferred',
			$this->manifestation_receipt_date_inferred );
	}
	#-----------------------------------------------------

	function flag_uncertain_receipt_date_field() {

		$this->basic_checkbox( 'manifestation_receipt_date_uncertain', 'Date is uncertain',
			$this->manifestation_receipt_date_uncertain );
	}
	#-----------------------------------------------------

	function flag_approx_receipt_date_field() {

		$this->basic_checkbox( 'manifestation_receipt_date_approx', 'Date is approximate',
			$this->manifestation_receipt_date_approx );
	}
	#-----------------------------------------------------






  function date_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_DATE,
                             'Bibliographic references and any other notes on date:' );
  }
  #-----------------------------------------------------

  function copyist_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_COPYIST_OF_MANIF,
                             'Bibliographic references and any other notes on copyist:' ); 
  }
  #-----------------------------------------------------

  function place_of_copying_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COPYING,
                             'Bibliographic references and any other notes on place of copying:' );
  }
  #-----------------------------------------------------

  function incipit_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_INCIPIT_OF_MANIF,
                             'Bibliographic references and any other notes on incipit of manifestation:' );
  }
  #-----------------------------------------------------

  function excipit_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_EXCIPIT_OF_MANIF,
                             'Bibliographic references and other notes on explicit or colophon of manifestation:' );
  }
  #-----------------------------------------------------

  function notes_on_patron_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_MANIF,
                             'Bibliographic references and any other notes on patron:' );
  }
  #-----------------------------------------------------

  function notes_on_dedicatee_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_MANIF,
                             'Bibliographic references and any other notes on dedicatee:' ); 
  }
  #-----------------------------------------------------

  function notes_on_former_owners_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_FORMER_OWNERS_OF_MANIF,
                             'Bibliographic references and any other notes on former owners:' ); 
  }
  #-----------------------------------------------------

  function notes_on_endowers_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ENDOWERS_OF_MANIF,
                             'Bibliographic references and any other notes on endowers:' ); 
  }
  #-----------------------------------------------------

  function notes_on_endowees_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ENDOWEES_OF_MANIF,
                             'Bibliographic references and any other notes on endowees:' ); 
  }
  #-----------------------------------------------------

  function former_owners_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name               = 'former_owner',
                                          $section_heading             = NULL,
                                          $decode_display              = 'former owner',
                                          $separate_section            = FALSE,
                                          $extra_notes                 = NULL,
                                          $popup_object_name           = 'popup_person',
                                          $popup_object_class          = 'popup_person',
                                          $include_date_fields         = TRUE );
  }
  #-----------------------------------------------------

  function taught_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'taught_manif',
                                          $section_heading     = '',
                                          $decode_display      = 'teacher of manifestation',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = TRUE );

    $this->taught_manif_notes_field();
  }
  #-----------------------------------------------------

  function taught_manif_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_TEACHER_OF_MANIF,
                             'Bibliographic references and any other notes on teachers:' );

  }
  #-----------------------------------------------------

  function studied_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'studied_manif',
                                          $section_heading     = '',
                                          $decode_display      = 'student of manifestation',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = TRUE );
    $this->studied_manif_notes_field();
  }
  #-----------------------------------------------------

  function studied_manif_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_STUDENT_OF_MANIF,
                             'Bibliographic references and any other notes on students:' );

  }
  #-----------------------------------------------------

  function where_manif_was_studied_field() {

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'where_manif_was_studied',
                                          $section_heading     = '',
                                          $decode_display      = 'place where manifestation was studied',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_location',
                                          $popup_object_class  = 'popup_location',
                                          $include_date_fields = TRUE );
    $this->where_manif_was_studied_notes_field();
  }
  #-----------------------------------------------------

  function where_manif_was_studied_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PLACE_STUDIED_OF_MANIF,
                             'Bibliographic references and other notes on places where studied:' );

  }
  #-----------------------------------------------------

  function patrons_of_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'patrons_of_manif',
                                          $section_heading     = NULL,
                                          $decode_display      = 'patron',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = FALSE );
    $this->notes_on_patron_field();
  }
  #-----------------------------------------------------

  function dedicatees_of_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'dedicatees_of_manif',
                                          $section_heading     = NULL,
                                          $decode_display      = 'dedicatee',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = FALSE );
    $this->notes_on_dedicatee_field();
  }
  #-----------------------------------------------------

  function endower_of_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'endower_of_manif',
                                          $section_heading     = NULL,
                                          $decode_display      = 'endower',
                                          $separate_section    = FALSE,
                                          $extra_notes         = '',
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = FALSE );
  }
  #-----------------------------------------------------

  function endowee_of_manif_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'endowee_of_manif',
                                          $section_heading     = '',
                                          $decode_display      = 'endowee',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = FALSE );
  }
  #-----------------------------------------------------

  function annotated_by_field() {

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name       = 'annotated_by',
                                          $section_heading     = 'Annotator(s):',
                                          $decode_display      = 'annotator',
                                          $separate_section    = FALSE,
                                          $extra_notes         = NULL,
                                          $popup_object_name   = 'popup_person',
                                          $popup_object_class  = 'popup_person',
                                          $include_date_fields = FALSE );

    $this->annotator_notes_field();
  }
  #-----------------------------------------------------

  function annotator_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ANNOTATOR_OF_MANIF,
                             'Notes on annotations, bibliographic references and annotators:' );

  }
  #-----------------------------------------------------

  function codex_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_CODEX_OF_MANIF,
                             'Bibliographic references and other notes on codex/composite work:' ); 

  }
  #-----------------------------------------------------

  function contents_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_CONTENTS_OF_MANIF,
                             'Bibliographic references and other notes on items comprised in codex/composite work:' ); 

  }
  #-----------------------------------------------------

  function place_of_copying_section() { # must be overridden in child class to activate
  }
  #-----------------------------------------------------

  function place_of_copying_field() {

    $this->proj_edit_area_calling_popups( $fieldset_name               = 'place_of_copying',
                                          $section_heading             = NULL,
                                          $decode_display              = 'place of copying',
                                          $separate_section            = FALSE,
                                          $extra_notes                 = NULL,
                                          $popup_object_name           = 'popup_location',
                                          $popup_object_class          = 'popup_location' );
  }
  #-----------------------------------------------------

  function manifestation_type_field() {

    $document_type_obj = new document_type( $this->db_connection );
    
    $document_type_obj->lookup_table_dropdown( $field_name = 'manifestation_type', 
                                               $field_label = 'Document type', 
                                               $selected_id = $this->manifestation_type );
  }

	function manifestation_opened_field() {

		HTML::dropdown_start( "opened", "Letter opened", $in_table=FALSE );

		$opened_selected = $this->opened;
		echo $opened_selected;

		HTML::dropdown_option( "o", "Opened", $opened_selected );
		HTML::dropdown_option( "p", "Partially opened", $opened_selected );
		HTML::dropdown_option( "u", "Unopened", $opened_selected );

		HTML::dropdown_end( FALSE );
	}
  #-----------------------------------------------------

  function printed_edition_field() {

    HTML::div_start( 'class="workfield"' );

    HTML::textarea( 'printed_edition_details', FLD_SIZE_PRINTED_ED_ROWS, FLD_SIZE_PRINTED_ED_COLS, 
                    $value = $this->printed_edition_details, 
                    $label = $this->proj_get_field_label( 'printed_edition_details' ) );

    HTML::div_end();

    $this->proj_publication_popups( $calling_field = 'printed_edition_details' );
  }
  #-----------------------------------------------------

  function paper_size_field() {

    HTML::input_field( 'paper_size', 'Paper size', $this->paper_size, FALSE, FLD_SIZE_PAPER_SIZE, $tabindex=1,
                       NULL, NULL, NULL, 0, ' (up to 500 characters)' );
  }
  #-----------------------------------------------------

  function paper_type_or_watermark_field() {

    HTML::input_field( 'paper_type_or_watermark', 'Paper type, watermark', $this->paper_type_or_watermark, FALSE, 
                       FLD_SIZE_PAPER_TYPE_OR_WATERMARK, $tabindex=1, NULL, NULL, NULL, 0, ' (up to 500 characters)' );
  }
  #-----------------------------------------------------

	function stored_folded_field() {

		HTML::input_field( 'stored_folded', 'Stored folded', $this->stored_folded, FALSE,
			10, $tabindex=1, NULL, NULL, NULL, 0, ' ' );
	}
	#-----------------------------------------------------
	function postage_costs_as_marked_field() {

		HTML::input_field( 'postage_costs_as_marked', 'Postage costs as marked', $this->postage_costs_as_marked, FALSE,
			60, $tabindex=1, NULL, NULL, NULL, 0, NULL );
	}
	#-----------------------------------------------------
	function postage_costs_field() {

		HTML::input_field( 'postage_costs', 'Postage cost(s)', $this->postage_costs, FALSE,
			60, $tabindex=1, NULL, NULL, NULL, 0, NULL );
	}
	#-----------------------------------------------------
	function non_delivery_reason_field() {

		HTML::input_field( 'non_delivery_reason', 'Reason for non-delivery', $this->non_delivery_reason, FALSE,
			60, $tabindex=1, NULL, NULL, NULL, 0, ' (up to 500 characters)' );
	}
	#-----------------------------------------------------
	function date_of_receipt_as_marked_field() {

		HTML::input_field( 'date_of_receipt_as_marked', 'Date of receipt as marked', $this->date_of_receipt_as_marked, FALSE,
			60, $tabindex=1, NULL, NULL, NULL, 0, NULL );
	}
	#-----------------------------------------------------



  function number_of_pages_of_document_field() {

    HTML::input_field( 'number_of_pages_of_document', 
                       $this->proj_get_field_label( 'number_of_pages_of_document' ), 
                       $this->number_of_pages_of_document, 
                       FALSE, FLD_SIZE_NUM_PAGES_DOC, 1, NULL, NULL, 
                       $input_parms = 'onchange="js_check_value_is_numeric( this )"',
                       $input_instance = 0, $trailing_text = ' (whole numbers only)' );
  }
  #-----------------------------------------------------

  function seal_field() {

    HTML::textarea( 'seal', FLD_SIZE_SEAL_ROWS, FLD_SIZE_SEAL_COLS, $value = $this->seal, $label = 'Seal' );
  }
  #-----------------------------------------------------

  function postage_marks_field() {

    HTML::input_field( 'postage_marks', 'Postage marks', $this->postage_marks, FALSE, FLD_SIZE_POSTAGE_MARKS );
  }
  #-----------------------------------------------------

  function address_field() {

    HTML::textarea( 'address', FLD_SIZE_ADDRESS_ROWS, FLD_SIZE_ADDRESS_COLS, $value = $this->address,
                    $label = 'Address' );
  }
  #-----------------------------------------------------

	function routing_mark_stamp_field() {

		HTML::textarea( 'routing_mark_stamp', 3, 60, $value = $this->routing_mark_stamp,
			$label = 'Routing Mark (stamp)' );
	}
	#-----------------------------------------------------

	function routing_mark_ms_field() {

		HTML::textarea( 'routing_mark_ms', 3, 60, $value = $this->routing_mark_ms,
			$label = 'Routing Mark (MS)' );
	}
	#-----------------------------------------------------

	function handling_instructions_field() {

		HTML::textarea( 'handling_instructions', 3, 60, $value = $this->handling_instructions,
			$label = 'Handling Instructions' );
	}
	#-----------------------------------------------------


	function endorsements_field() {

   $label = $this->db_get_default_column_label( 'endorsements' );
   
   if( $label == 'Endorsements' ) $label = 'Other endorsements';

    HTML::textarea( 'endorsements', FLD_SIZE_ENDORSE_ROWS, FLD_SIZE_ENDORSE_COLS, $value = $this->endorsements, 
                    $label );
  }
  #-----------------------------------------------------

  function language_of_manifestation_field() {

    $possible_langs = $this->proj_get_possible_languages();
    $actual_langs = $this->proj_get_languages_used( $object_type = 'manifestation', $id_value = $this->manifestation_id );
    if( ! $this->language_obj ) $this->language_obj = new Language( $this->db_connection );
    $this->language_obj->language_entry_fields( $possible_langs, $actual_langs );
  }
  #-----------------------------------------------------

  function manifestation_is_translation_field() {

    $this->basic_checkbox( 'manifestation_is_translation', 
                           'Manifestation is translation', 
                           $this->manifestation_is_translation );
  }
  #-----------------------------------------------------

  function manifestation_incipit_field() {

    HTML::textarea( 'manifestation_incipit', FLD_SIZE_MANIF_INCIPIT_ROWS, FLD_SIZE_MANIF_INCIPIT_COLS, 
                    $value = $this->manifestation_incipit, $label = 'Manifestation incipit' );
  }
  #-----------------------------------------------------

  function manifestation_excipit_field() {

    HTML::textarea( 'manifestation_excipit', FLD_SIZE_MANIF_EXCIPIT_ROWS, FLD_SIZE_MANIF_EXCIPIT_COLS, 
                    $value = $this->manifestation_excipit, 
                    $label = $this->proj_get_field_label( 'manifestation_excipit' ));
  }
  #-----------------------------------------------------

  function manifestation_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ENTITY, NULL );

  }
  #-----------------------------------------------------

  function scribes_field() {

    $decode_display = strtolower( $this->proj_get_field_label( 'scribe' ));

    $this->set_ids_in_popup_person();

    $this->proj_edit_area_calling_popups( $fieldset_name               = 'scribe_hand',
                                          $section_heading             = NULL,
                                          $decode_display,
                                          $separate_section            = FALSE,
                                          $extra_notes                 = NULL,
                                          $popup_object_name           = 'popup_person',
                                          $popup_object_class          = 'popup_person' );
  }
  #-----------------------------------------------------

  function proj_enable_popup_add_method() {  # overrides method from Project

    return FALSE;  # don't enable 'Add' popup for manifestations, only 'Select' popup
  }
  #----------------------------------------------------------------------------------

  function enclosures_field() {

    HTML::bold_start();
    echo 'Enclosures (letters and other types of enclosure):';
    HTML::bold_end();
    HTML::new_paragraph();

    echo 'Note: when entering enclosure details, '
         . ' please treat any work/manifestation on the Cultures of Knowledge system as a letter.';
    echo LINEBREAK . LINEBREAK . LINEBREAK;

    $this->proj_edit_area_calling_popups( $fieldset_name               = 'enclosure_to_this',
                                          $section_heading             = 'LETTERS enclosed in this manifestation:',
                                          $decode_display              = 'enclosed letter',
                                          $separate_section            = FALSE,
                                          $extra_notes                 = NULL,
                                          $popup_object_name           = 'popup_manifestation',
                                          $popup_object_class          = 'popup_manifestation' );

    echo LINEBREAK . LINEBREAK;

    HTML::span_start( 'class="workfieldaligned"' );
    HTML::italic_start();
    echo 'OTHER TYPES of enclosure, e.g. money, books, samples of minerals:';
    HTML::italic_end();
    HTML::span_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );

    HTML::textarea( 'non_letter_enclosures', FLD_SIZE_NON_LETTER_ENCLOSURE_ROWS, FLD_SIZE_NON_LETTER_ENCLOSURE_COLS, 
                    $value = $this->non_letter_enclosures, $label = 'Details of enclosures other than letters' );

    HTML::div_end();  # end div workfield
  }
  #----------------------------------------------------------------------------------

  function enclosing_field() {

    $fieldset_name = 'enclosing_this';

    $this->proj_edit_area_calling_popups( $fieldset_name,
                                          $section_heading             = NULL,
                                          $decode_display = $this->proj_get_field_label( $fieldset_name ),
                                          $separate_section            = FALSE,
                                          $extra_notes                 = NULL,
                                          $popup_object_name           = 'popup_manifestation',
                                          $popup_object_class          = 'popup_manifestation' );
  }
  #----------------------------------------------------------------------------------

  function proj_get_entitydesc_td_width( $fieldset_name ) {  # overrides parent version

    switch( $fieldset_name ) {

      case 'enclosure_to_this':
      case 'enclosing_this':
        return ENCLOSURE_TD_WIDTH;

      default:
        return parent::proj_get_entitydesc_td_width( $fieldset_name );
    }
  }
  #-----------------------------------------------------

  function save_manifestation() {

    $this->work_id = $this->read_post_parm( 'work_id' );

    $new_record = FALSE;
    $this->manifestation_id = $this->read_post_parm( 'manifestation_id' );
    if( $this->manifestation_id ) 
      $this->set_manifestation( $this->manifestation_id );
    else
      $new_record = TRUE;

    $this->reading_parms_for_update = TRUE;  # Used in 'validate parm'. N.B. Do AFTER 'set manifestation'.

    #----------------------------------------------------------------------------------------------
    # You can't edit a manifestation AND delete it at the same time. However, it would be possible
    # to ADD a manifestation and delete others in one click of the 'Save' button.
    # We need to check whether any new manifestation details have actually been entered.
    #----------------------------------------------------------------------------------------------
    if( $new_record ) {
      if( $this->parm_found_in_post( 'delete_manifestation' )) {
        echo LINEBREAK . 'Deleting manifestation details...' . LINEBREAK;
        $this->delete_manifestations();
      }

      if( ! $this->new_manifestation_entered()) {
        if( ! $this->parm_found_in_post( 'delete_manifestation' )) {
          HTML::div_start( 'class="warning"' );
          echo LINEBREAK . 'No new details to save.' . LINEBREAK;
          echo 'Save cancelled.';
          echo LINEBREAK . LINEBREAK;
          HTML::div_end();
        }
        return;
      }
    }

    if( $new_record )
      $statement = $this->get_manifestation_insert_statement();
    else
      $statement = $this->get_manifestation_update_statement();

    $this->db_run_query( $statement );

    if( $new_record ) {
      $this->rel_obj->insert_relationship( $left_table_name = $this->proj_manifestation_tablename(),
                                           $left_id_value = $this->manifestation_id,
                                           $relationship_type = RELTYPE_MANIFESTATION_IS_OF_WORK,
                                           $right_table_name = $this->proj_work_tablename(),
                                           $right_id_value = $this->work_id );
    }

    $this->save_repository();
    $this->save_former_owners();
    $this->save_enclosures();
    $this->save_enclosing();
    $this->save_comments();
    $this->save_scribe_hand();
    $this->save_languages();
    $this->save_images();
    $this->save_place_of_copying();
    $this->save_manif_teachers();
    $this->save_manif_students();
    $this->save_manif_place_of_study();
    $this->save_manif_annotator();
    $this->save_patrons_of_manif();
    $this->save_dedicatees_of_manif();
    $this->save_endower_of_manif();
    $this->save_endowee_of_manif();

    # If the user clicked a 'Save and continue' button, return them to the section of the form
    # where they were working, rather than to the list of manifestations at the top of the page.
    # In order to do this, you need to make it seem as if they clicked the 'Edit' button.

    $save_indicator = 'save_button';
    $post_keys = array_keys( $_POST );
    foreach( $post_keys as $post_key ) {

      if( $this->string_ends_with( $post_key, $save_indicator ) ) {

        $this->write_post_parm( 'edit_manifestation_button', 'Edit' );
        $this->write_post_parm( 'manifestation_id', $this->manifestation_id );

        # If this is just the ordinary 'Save' button rather than a 'Save and continue' button, 
        # then write a fake 'Save and Continue' button so that we go to the start of the editing form.
        if( $post_key == $save_indicator ) {
          $this->unset_post_parm( $save_indicator );
          $this->write_post_parm( 'data_entry_form_start_' . $save_indicator, 'Save' );
        }
        break;
      }
    }
  }
  #-----------------------------------------------------

  function get_new_manifestation_id() {

    # E.g. for work with integer ID 123, first manifestation is w123a and 26th is w123z.
    # If there are more than 26, we'll continue with w123za, w123zb etc.

    $existing = $this->select_manifestations_of_work( $this->work_id );
    $max_existing = '';
    $final_chars = '';

    if( is_array( $existing )) {
      foreach( $existing as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $reversed = strrev( $id_value );
        $final_chars = '';
        for( $char_pos = 0; $char_pos < strlen( $reversed ); $char_pos++ ) {
          $one_char = substr( $reversed, $char_pos, 1 );
          if( $one_char >= 'a' && $one_char <= 'z' )
            $final_chars .= $one_char;
          else
            break;
        }
        $final_chars = strrev( $final_chars ); # restore original order
        if( $final_chars > $max_existing ) $max_existing = $final_chars;
      }
    }

    $final_chars = $max_existing;
    if( $final_chars ) {
      $last_char = substr( $final_chars, -1 );
      $final_chars = substr( $final_chars, 0, -1 );
      if( $last_char < 'z' ) # after 'z' comes 'aa' but I think 'za' would be better for ordering
        $last_char++;
      else
        $last_char .= 'a';
      $final_chars .= $last_char;
    }
    else
      $final_chars = 'a';

    $this->iwork_id = $this->read_post_parm( 'iwork_id' );
    $manifestation_id = 'W' . $this->iwork_id . '-' . $final_chars;
    return $manifestation_id;
  }
  #-----------------------------------------------------

  function get_manifestation_insert_statement() {

    $this->manifestation_id = $this->get_new_manifestation_id();

    $column_clause = '';
    $values_clause = '';

    $column_list = DBEntity::db_list_columns( $this->proj_manifestation_tablename());
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );

      $skip_it = FALSE;
      switch( $column_name ) {
        case 'creation_timestamp':
        case 'creation_user':
        case 'change_timestamp':
        case 'change_user':
        case 'uuid':


          $skip_it = TRUE;
          break;

        default:
          break;
      }
      if( $skip_it ) continue;

      $value_entered = FALSE;

      if( $column_name == 'manifestation_id' )
        $value_entered = TRUE;  # we will automatically fill in a value for the ID

      elseif( $this->parm_found_in_post( $column_name ))  {
        $this->$column_name = $this->read_post_parm( $column_name );
        if( strlen( $this->$column_name ) > 0 ) $value_entered = TRUE;
      }
      if( ! $value_entered ) continue;


      if( ! $first_column ) {
        $column_clause .= ', ';
        $values_clause .= ', ';
      }
      $column_clause .= $column_name;

      $value = $this->$column_name;

      if( $is_numeric ) {
        $values_clause .= $value;
      }

      elseif( $is_date ) {
        $values_clause .= "'$value'::date";
      }

      else
        $values_clause .= "'" . $this->escape( $value ) . "'";

      $first_column = FALSE;
    }

    $statement = 'insert into ' . $this->proj_manifestation_tablename() 
               . " ( $column_clause ) values ( $values_clause )";

    return $statement;
  }
  #-----------------------------------------------------
  function get_manifestation_update_statement() {

    $detect_changes = '';

    $statement = 'update ' . $this->proj_manifestation_tablename() . ' set ';
    $column_list = DBEntity::db_list_columns( $this->proj_manifestation_tablename());
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );

      $skip_it = FALSE;
      switch( $column_name ) {
        case 'manifestation_id':
        case 'creation_timestamp':
        case 'creation_user':
        case 'change_timestamp':
        case 'change_user':

          $skip_it = TRUE;
          break;

        default:
          break;
      }
      if( $skip_it ) continue;

      $blank_field_out = FALSE;   # Normally, don't blank out values not found in Post. The exceptions are checkboxes
                                  # on the form, AND those fields that disappear when 'date range' is unchecked.

      if( $this->parm_found_in_post( $column_name ))  {
        $this->$column_name = $this->read_post_parm( $column_name );

        switch( $column_name ) {

          case 'manifestation_creation_date_year':
          case 'manifestation_creation_date_month':
          case 'manifestation_creation_date_day':
          case 'manifestation_creation_date2_year':
          case 'manifestation_creation_date2_month':
          case 'manifestation_creation_date2_day':
            if( $this->$column_name == 0 ) $this->$column_name = '';
            break;

          default:
            break;
        }
      }

      else { # The field did not appear on the calling form.
        if( $this->is_integer_checkbox_field( $column_name )) $blank_field_out = TRUE;
        if( ! $blank_field_out ) continue;
      }

      if( ! $first_column ) $statement .= ', ';

      $value = $this->$column_name;

      if( $is_numeric ) {
        if( $value == '' || $blank_field_out ) {
          if( $this->is_integer_checkbox_field( $column_name )) 
            $value = '0';
          else
            $value = 'null';
        }
        $statement .= " $column_name = $value";
      }

      elseif( $is_date ) {
        if( $value == '' ) {
          $value = 'null';
          $statement .= " $column_name = $value";
        }
        else
          $statement .= " $column_name = '$value'::date";
      }

      else
        $statement .= " $column_name = '" . $this->escape( $value ) . "'";

      # Avoid performing updates unnecessarily so we don't have to work through loads of triggers.
      if( $detect_changes > '' ) $detect_changes .= ' or ';

      if( $value == 'null' ) 
        $detect_changes .= "$column_name is not null";
      else {
        if( $is_numeric )
          $detect_changes .= "coalesce( $column_name, -9999 ) != $value";
        elseif( $is_date ) 
          $detect_changes .= "coalesce( $column_name, '1000-01-01'::date ) != '$value'::date";
        else
          $detect_changes .= "coalesce( $column_name, '' ) != '" . $this->escape( $value ) . "'";
      }

      $first_column = FALSE;
    }

    $statement .= " where ( $detect_changes )";
    $statement .= " and manifestation_id = '$this->manifestation_id'";

    return $statement;
  }
  #-----------------------------------------------------

  function new_manifestation_entered() {

    $column_list = DBEntity::db_list_columns( $this->proj_manifestation_tablename());

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $value = NULL;

      $skip_it = FALSE;
      switch( $column_name ) {
        case 'manifestation_id':

        case 'creation_timestamp':
        case 'creation_user':
        case 'change_timestamp':
        case 'change_user':

          $skip_it = TRUE;
          break;

        default:
          break;
      }
      if( $skip_it ) continue;

      if( $this->parm_found_in_post( $column_name ))  {
        $value = $this->read_post_parm( $column_name );
      }

      if( $this->is_empty_or_default_value( $column_name, $value )) {
        $value = '';  # value may be a default value but non-blank
        continue;
      }

      break;
    }

    if( ! $value ) $value = $this->read_post_parm( 'repository' );
    if( ! $value ) $value = $this->read_comments_from_post();
    if( ! $value ) $value = $this->check_if_languages_entered();

    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'former_owner' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'enclosure_to_this' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'enclosing_this' ); 
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'scribe_hand' ); 
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'place_of_copying' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'taught_manif' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'studied_manif' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'where_manif_was_studied' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'annotated_by' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'patrons_of_manif' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'dedicatees_of_manif' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'endower_of_manif' );
    if( ! $value ) $value = $this->rel_obj->read_rels_for_field_type( 'endowee_of_manif' );

    if( $value )
      $details_entered = TRUE;
    else
      $details_entered = FALSE;

    return $details_entered;
  }
  #-----------------------------------------------------

  function is_empty_or_default_value( $column_name, $value ) {

    $value = trim( $value );
    if( strlen( $value ) == 0 ) return TRUE;

    if( $value == 0 || $value == '0' ) {

      if( $this->is_integer_checkbox_field( $column_name )) return TRUE;

      switch( $column_name ) {
        case 'manifestation_creation_date_month':
        case 'manifestation_creation_date_day':
        case 'manifestation_creation_date_month':
        case 'manifestation_creation_date_day':
          return TRUE;
      }
    }

    if( $column_name == 'manifestation_type' ) {
      foreach( $this->document_types as $code => $desc ) {
        $default_document_type = $code;
        break; # just get the first one
      }

      if( $value == $default_document_type ) {
        return TRUE;
      }
    }

    return FALSE;
  }
  #-----------------------------------------------------

  function delete_manifestations() {

    $to_delete = $this->read_post_parm( 'delete_manifestation' );

    foreach( $to_delete as $manifestation_id ) {
      $this->delete_one_manifestation( $manifestation_id );
    }
  }
  #-----------------------------------------------------

  function delete_one_manifestation( $manifestation_id ) {

    $this->rel_obj->delete_all_rels_for_id( $table_name = $this->proj_manifestation_tablename(), 
                                            $id_value   = $manifestation_id );

    $statement = 'delete from ' . $this->proj_manifestation_tablename()
               . " where manifestation_id = '$manifestation_id'";
    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function is_integer_checkbox_field( $column_name ) {  # If the field was not found in Post, it was unchecked,
                                                        # so we may need to update the record accordingly.
    switch( $column_name ) {
      case 'manifestation_creation_date_inferred':
      case 'manifestation_creation_date_uncertain':
      case 'manifestation_creation_date_approx':
      case 'manifestation_creation_date_is_range':
      case 'manifestation_is_translation':
        return TRUE;

      default:
        return FALSE;
    }
  }
  #-----------------------------------------------------

  function save_former_owners() {

    $this->rel_obj->save_rels_for_field_type( 'former_owner', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_place_of_copying() {

    $this->rel_obj->save_rels_for_field_type( 'place_of_copying', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_enclosures() {

    $this->rel_obj->save_rels_for_field_type( 'enclosure_to_this', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_enclosing() {

    $this->rel_obj->save_rels_for_field_type( 'enclosing_this', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_scribe_hand() {

    $this->rel_obj->save_rels_for_field_type( 'scribe_hand', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_manif_teachers() {

    $this->rel_obj->save_rels_for_field_type( 'taught_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_manif_students() {

    $this->rel_obj->save_rels_for_field_type( 'studied_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_manif_place_of_study() {

    $this->rel_obj->save_rels_for_field_type( 'where_manif_was_studied', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_manif_annotator() {

    $this->rel_obj->save_rels_for_field_type( 'annotated_by', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_patrons_of_manif() {

    $this->rel_obj->save_rels_for_field_type( 'patrons_of_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_dedicatees_of_manif() {

    $this->rel_obj->save_rels_for_field_type( 'dedicatees_of_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_endower_of_manif() {

    $this->rel_obj->save_rels_for_field_type( 'endower_of_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_endowee_of_manif() {

    $this->rel_obj->save_rels_for_field_type( 'endowee_of_manif', $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_repository() {

    $old_repository_id = 0;
    if( $this->manifestation_id ) {
      $old_repository_id = $this->repository_id;
    }

    $new_repository_id = $this->read_post_parm( 'repository' );

    if( $new_repository_id != $old_repository_id ) {

      if( $old_repository_id ) {
        $this->rel_obj->delete_relationship( $left_table_name = $this->proj_manifestation_tablename(),
                                             $left_id_value = $this->manifestation_id,
                                             $relationship_type = RELTYPE_MANIF_STORED_IN_REPOS,
                                             $right_table_name = $this->proj_institution_tablename(),
                                             $right_id_value = $old_repository_id );
      }

      if( $new_repository_id ) {
        $this->rel_obj->insert_relationship( $left_table_name = $this->proj_manifestation_tablename(),
                                             $left_id_value = $this->manifestation_id,
                                             $relationship_type = RELTYPE_MANIF_STORED_IN_REPOS,
                                             $right_table_name = $this->proj_institution_tablename(),
                                             $right_id_value = $new_repository_id );
      }
    }
  }
  #-----------------------------------------------------

  function check_if_languages_entered() {

    if( ! $this->language_obj ) $this->language_obj = new Language( $this->db_connection );
    $langs = $this->language_obj->read_languages_of_text_from_post();

    if( is_array( $langs ) && count( $langs ) > 0 )
      return $langs;
    else
      return NULL;
  }
  #-----------------------------------------------------

  function save_languages() {
    $this->proj_save_languages( $object_type = 'manifestation', $id_value = $this->manifestation_id );
  }
  #-----------------------------------------------------

  function save_images() {

    if( ! $this->image_obj ) $this->image_obj = new Image( $this->db_connection );
    $this->image_obj->save_image_details( $entity_id = $this->manifestation_id, $entity_type = 'manifestation' );
  }
  #-----------------------------------------------------

  function extra_save_button( $prefix = NULL, $new_paragraph = TRUE, $parms='onclick="js_enable_form_submission()"') {

    $this->proj_extra_save_button( $prefix, $new_paragraph, $parms ); 
  }
  #-----------------------------------------------------

  function get_manifestation_overview( $manifestation_id, $current_manif = NULL, $manif_count = NULL ) {

    $overview = array();
    if( ! $manifestation_id ) return $overview;

    $this->set_manifestation( $manifestation_id );
    if( ! $this->manifestation_id ) return $overview;

    $this->select_extra_details_for_overview();

    $this->running_total = '';
    if( strlen( $current_manif ) > 0 && strlen( $manif_count ) > 0 ) 
      $this->running_total = 'Manifestation ' . $current_manif . ' of ' . $manif_count;

    $cols = $this->get_columns_for_overview();
    foreach( $cols as $colname ) {
      $colvalue = $this->$colname;
      $label = $this->db_get_default_column_label( $colname );

      switch( $colname ) {

        case 'manifestation_type':
          $colvalue = $this->decode_manifestation_type( $colvalue );
          break;

        case 'manifestation_creation_calendar':
          $colvalue = $this->date_entity->decode_calendar( $colvalue );
          if( $colvalue == 'Unknown' ) $colvalue = '';
          break;

        case 'manifestation_is_translation':  # checkbox fields
        case 'manifestation_creation_date_inferred':
        case 'manifestation_creation_date_uncertain':
        case 'manifestation_creation_date_approx':
          if( $colvalue )
            $colvalue = '*** Yes ***';
          else
            $colvalue = '';
          break;
      }

      if( strlen( $colvalue ) > 0 ) {
        $overview[] = array( 'Field' => $label, 'Value' => $colvalue );
      }
    }

    return $overview;
  }
  #-----------------------------------------------------

  function get_columns_for_overview() {

    $cols = array( 

      'manifestation_type',
      'repository',                       # relationship to institution
      'id_number_or_shelfmark',
      'former_owners',                    # relationship to people
      'printed_edition_details',

      'enclosures',                       # relationship to other work/manifestations
      'non_letter_enclosures',
      'enclosed_in',                      # relationship to other work/manifestations
      'paper_size',
      'paper_type_or_watermark',
		'stored_folded',
      'number_of_pages_of_document',
      'number_of_pages_of_text',
      'seal',
      'postage_marks',
		'postage_costs_as_marked',
		'postage_costs',
      'address',
		'routing_mark_stamp',
		'routing_mark_ms',
		'handling_instructions',
      'endorsements',
		'non_delivery_reason',
		'date_of_receipt_as_marked',

      'manifestation_is_translation',
      'language_of_manifestation',
      'manifestation_incipit',
      'manifestation_excipit',
      'manifestation_ps',

      'manifestation_creation_calendar',
      'manifestation_creation_date',
      'manifestation_creation_date_gregorian',
      'manifestation_creation_date_year',
      'manifestation_creation_date_month',
      'manifestation_creation_date_day',
      'manifestation_creation_date_inferred',
      'manifestation_creation_date_uncertain',
      'manifestation_creation_date_approx',

		'manifestation_receipt_calendar',
		'manifestation_receipt_date',
		'manifestation_receipt_date_gregorian',
		'manifestation_receipt_date_year',
		'manifestation_receipt_date_month',
		'manifestation_receipt_date_day',
		'manifestation_receipt_date_inferred',
		'manifestation_receipt_date_uncertain',
		'manifestation_receipt_date_approx',

      'manifestation_notes',              # relationship to comments
      'manifestation_images',             # relationship to images
      'scribe'                            # relationship to person
    );
    return $cols;
  }
  #-----------------------------------------------------

  function select_extra_details_for_overview() {

    $this->repository  = $this->get_repository_decoded();

    $this->former_owners = $this->get_former_owners_decoded();

    $this->enclosures = $this->get_enclosures_decoded();

    $this->enclosed_in = $this->get_enclosing_decoded();

    $this->scribe = $this->get_scribe_hand_decoded();

    $this->manifestation_notes = $this->get_comments_decoded();

    $this->manifestation_images = $this->get_images_decoded();
  }
  #-----------------------------------------------------

  function get_repository_decoded() {

    if( $this->repository_id )
      $repository = $this->repository_obj->get_lookup_desc( $this->repository_id );
    return $repository;
  }
  #-----------------------------------------------------
  function get_former_owners_decoded() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_PERSON_OWNED_MANIF, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'right' ); # Person owned manif. This is manif.
    return $result;
  }
  #-----------------------------------------------------
  function get_enclosures_decoded() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF, 
                                            $required_table = $this->proj_manifestation_tablename(), 
                                            $this_side = 'right' ); # Inner in outer. This is outer.
    return $result;
  }
  #-----------------------------------------------------
  function get_enclosing_decoded() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF, 
                                            $required_table = $this->proj_manifestation_tablename(), 
                                            $this_side = 'left' ); # Inner in outer. This is outer.
    return $result;
  }
  #-----------------------------------------------------
  function get_scribe_hand_decoded() {

    $wholly = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_PERSON_HANDWROTE_MANIF, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'right' ); # Person wrote letter. This is letter.

    $partly = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_PERSON_PARTLY_HANDWROTE_MANIF, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'right' ); # Person wrote letter. This is letter.

    if( $partly ) $partly = 'Partly in hand of: ' . $partly;

    if( $wholly && $partly ) # shouldn't happen, but maybe it will!
      $wholly .= LINEBREAK;

    $result = $wholly . $partly;
    return $result;
  }
  #-----------------------------------------------------
  function get_comments_decoded() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_COMMENT_REFERS_TO_ENTITY, 
                                            $required_table = $this->proj_comment_tablename(), 
                                            $this_side = 'right' ); # Comment refers to manif.
    return $result;
  }
  #-----------------------------------------------------
  function get_images_decoded() {

    $function_name = $this->proj_database_function_name( 'list_manifestation_images', $include_collection_code = TRUE );
    $statement = "select $function_name( '$this->manifestation_id' )";
    $result = $this->db_select_one_value( $statement );
    return $result;
  }
  #-----------------------------------------------------

  function get_manifestation_comment_types() {

    $reltypes = array( RELTYPE_COMMENT_REFERS_TO_ENTITY,
                       RELTYPE_COMMENT_REFERS_TO_DATE,
                       RELTYPE_COMMENT_REFERS_TO_CODEX_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_CONTENTS_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_INCIPIT_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_EXCIPIT_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_FORMER_OWNERS_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_ENDOWERS_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_ENDOWEES_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_COPYIST_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COPYING,
                       RELTYPE_COMMENT_REFERS_TO_TEACHER_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_STUDENT_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_ANNOTATOR_OF_MANIF,
                       RELTYPE_COMMENT_REFERS_TO_PLACE_STUDIED_OF_MANIF );
    return $reltypes;
  }
  #-----------------------------------------------------

  function read_comments_from_post() {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $reltypes = $this->get_manifestation_comment_types();

    foreach( $reltypes as $reltype ) {
      $comment = $this->comment_obj->read_new_comment_from_post( $reltype );
      if( $comment ) return $comment;
    }

    return $comment;
  }
  #-----------------------------------------------------

  function save_comments() {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $reltypes = $this->get_manifestation_comment_types();

    foreach( $reltypes as $reltype ) {
      $this->comment_obj->save_comments( $this->proj_manifestation_tablename(), 
                                         $this->manifestation_id, 
                                         $reltype );
    }
  }
  #-----------------------------------------------------

  function refresh_work_for_list_of_manifs( $manif_list ) { # refresh the queryable work summary for these manifestations
                                                            # called by Repository object if repositories are merged
    $flush_frequency = 20;

    $manif_count = count( $manif_list );
    if( $manif_count > 0 ) {
      $i = 0;
      foreach( $manif_list as $manif ) {  # a simple array of manifestation IDs
        $i++;
        $this->set_manifestation( $manif );
        $desc = $this->id_number_or_shelfmark . ' ' . $this->printed_edition_details;
        $desc = trim( $desc );
        if( ! $desc ) $desc = '[no shelfmark or printed edition details entered]';
        $this->echo_safely( "Refreshing work details for $desc" ); 
        echo LINEBREAK;

        $statement = 'update ' . $this->proj_work_tablename() . " set change_timestamp = 'now'::timestamp "
                   . " where work_id = '$this->work_id'";  # this should trigger refresh of queryable work
        $this->db_run_query( $statement );

        if( $i % $flush_frequency == 0 ) {

          $anchor_name = 'refreshed_manif_' . str_replace( '-', '_', str_replace( ':', '_', $manif ));
          HTML::anchor( $anchor_name );
          $script = 'window.location.hash = "#' . $anchor_name . '"';
          HTML::write_javascript_function( $script );
          flush();
        }
      }
    }
    echo LINEBREAK;
  }
  #-----------------------------------------------------------------------------
  # Provide search help for the Manifestations field in the Search Works screen.
  # We want dropdown lists for both document type and institution.

  function desc_dropdown( $form_name, $field_name = NULL, $copy_field = NULL, $field_label = NULL,
                          $in_table=FALSE, $override_blank_row_descrip = NULL ) {

    $this->search_help_text();

    $document_type_obj = new Lookup_Table( $this->db_connection, 
                                           $lookup_table_name = $this->proj_document_type_tablename(), 
                                           $id_column_name    = 'document_type_id', 
                                           $desc_column_name  = 'document_type_desc',
                                           $code_column_name  = 'document_type_code'  ); 

    $document_type_obj->desc_dropdown( $form_name, $field_name . '_doc_type', $copy_field, $field_label = 'Document type',
                                       $in_table, $override_blank_row_descrip );

    HTML::new_paragraph();


    $this->repository_obj->desc_dropdown( $form_name, $field_name . '_repos', $copy_field, $field_label = 'Repository',
                                          $in_table, $override_blank_row_descrip = 'Possible values' );
    echo LINEBREAK;
  }
  #-----------------------------------------------------

  function search_help_text() {

    HTML::new_paragraph();

    echo 'The Manifestations field contains a very brief summary of all the manifestations of a work. This summary'
         . ' includes document type plus either repository and shelfmark or printed edition details.';
    HTML::italic_start();

    echo ' You can search on both document type and repository at once if you wish, but please remember, document type'
         . ' comes first in the summary, then repository, so you need to enter your search terms in that same order.'
         . ' Also, if entering multiple search terms, you need to separate them using the wildcard % (percent-sign).';

    HTML::italic_end();
    HTML::new_paragraph();

    echo 'E.g. to find all drafts in the Bodleian you would need to enter ';
    HTML::bold_start();
    echo 'Draft%Bodleian';
    HTML::bold_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function proj_list_form_sections() {

    $form_sections = array( 'basic_details'      =>  'Basic details',
                            'manif_dates'        =>  'Manifestation date',
                            'former_owners'      =>  'Former owners',
                            'enclosures'         =>  $this->proj_get_field_label( 'enclosures' ),
                            'enclosed_in'        =>  $this->proj_get_field_label( 'enclosing_section' ),
                            'paper_and_markings' =>  'Paper and markings',
							'receipt_date'       =>  'Receipt date',
                            'manif_lang'         =>  'Language of manifestation',
                            'incipit_and_excipit'=>  'Incipit and explicit',
                            'manifestation_notes'=>  'Notes on manifestation',
                            'scribe_hand'        =>  $this->proj_get_field_label( 'scribe_hand' ),
                            'imgs'               =>  'Images' );

    return $form_sections;
  }
  #-----------------------------------------------------

  function is_postgres_timestamp( $parm_value, $allow_blank = TRUE ) {

    return $this->date_entity->is_postgres_timestamp( $parm_value, $allow_blank );
  }
  #-----------------------------------------------------
  # This function can be used to label fields for which there is no corresponding column in the database.

  function proj_get_field_label( $fieldname = NULL ) {

    switch( $fieldname ) {
      case 'scribe_hand';
        return 'Scribe/hand';

      case 'enclosing_section':
        return 'Letters in which this one was enclosed';

      case 'enclosing_this':
        return 'enclosing letter';

      case 'printed_edition_details':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
          return 'Bibliographic references';
        else
          return parent::proj_get_field_label( $fieldname );

      case 'number_of_pages_of_document':
        return 'Number of pages';

      case 'id_number_or_shelfmark':
        return 'ID number or shelfmark';

      case 'manifestation_excipit':
        return 'Manifestation explicit';

      case 'manif_dates':
        return 'Date of manifestation in standard format';

		case 'receipt_date' :
			return 'Date of receipt';

      default:
        return parent::proj_get_field_label( $fieldname );
    }
  }
  #-----------------------------------------------------

  function set_ids_in_popup_person() {

    if( ! $this->popup_person ) $this->popup_person = new Popup_Person( $this->db_connection );
    $this->popup_person->set_work_id( $this->work_id );

    $manifs = $this->rel_obj->get_other_side_for_this_on_right( $this_table = $this->proj_work_tablename(), 
                                                                $this_id = $this->work_id, 
                                                                $reltype = RELTYPE_MANIFESTATION_IS_OF_WORK, 
                                                                $other_table = $this->proj_manifestation_tablename() );
    $manif_string = '';
    if( count( $manifs ) > 0 ) {
      foreach( $manifs as $row ) {
        $manif_id = $row[ 'other_id_value' ];
        if( $manif_string > '' ) $manif_string .= ',';
        $manif_string .= $manif_id;
      }
    }

    $this->popup_person->set_manifestation_ids( $manif_string );

  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'work_id':
      case 'manifestation_id':
        if( $this->parm_value == '' )
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      case 'delete_manifestation':
        return $this->is_array_of_html_id( $this->parm_value );
 
      case 'manifestation_title':
      case 'manifestation_alternative_titles':
      case 'description':  # may want to query on Work description in manifestation selection popup
      case 'id_number_or_shelfmark':
      case 'printed_edition_details':
      case 'paper_size':
      case 'paper_type_or_watermark':
      case 'seal':
      case 'postage_marks':
      case 'endorsements':
      case 'non_letter_enclosures':
      case 'language_of_manifestation':
      case 'address':
      case 'manifestation_incipit':
      case 'manifestation_excipit':
      case 'manifestation_ps':
      case 'manifestation_creation_date_as_marked':
      case 'script':
      case 'text_block_size':
      case 'bindings':
      case 'illustrations':
      case 'illuminations':
		case 'routing_mark_stamp':
		case 'routing_mark_ms':
		case 'handling_instructions':
		case 'stored_folded':
		case 'postage_costs_as_marked':
		case 'postage_costs':
		case 'non_delivery_reason':
		case 'date_of_receipt_as_marked':
        	return $this->is_ok_free_text( $this->parm_value );

      case 'iwork_id':
      case 'relationship_id':
      case 'repository':
      case 'number_of_pages_of_document':
      case 'number_of_pages_of_text':
      case 'lines_per_page':
		case 'manifestation_creation_date_year':
		case 'manifestation_creation_date_month':
		case 'manifestation_creation_date_day':
		case 'manifestation_creation_date2_year':
		case 'manifestation_creation_date2_month':
		case 'manifestation_creation_date2_day':

		case 'manifestation_receipt_date_year':
		case 'manifestation_receipt_date_month':
		case 'manifestation_receipt_date_day':
		case 'manifestation_receipt_date2_year':
		case 'manifestation_receipt_date2_month':
		case 'manifestation_receipt_date2_day':
        return $this->is_integer( $this->parm_value );

      case 'manifestation_creation_date_inferred':
      case 'manifestation_creation_date_uncertain':
      case 'manifestation_creation_date_approx':
      case 'manifestation_creation_date_is_range':
      case 'manifestation_is_translation':
		case 'manifestation_receipt_date_inferred':
		case 'manifestation_receipt_date_uncertain':
		case 'manifestation_receipt_date_approx':
		case 'manifestation_receipt_date_is_range':
        if( $this->reading_parms_for_update )
          return $this->is_integer( $this->parm_value );
        else
          return $this->is_alphabetic_or_blank( $this->parm_value );

      case 'manifestation_creation_date':
      case 'manifestation_creation_date2':
		case 'manifestation_creation_date_gregorian':
		case 'manifestation_receipt_date_gregorian':
		case 'manifestation_receipt_date':
		case 'manifestation_receipt_date2':
        return $this->is_postgres_timestamp( $this->parm_value );

      case 'manifestation_type':
		case 'manifestation_creation_calendar':
		case 'manifestation_receipt_calendar':
		case 'opened':
        return $this->is_alphanumeric_or_blank( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
