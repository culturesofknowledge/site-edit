<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

if( Application_Entity::get_system_prefix() == IMPACT_SYS_PREFIX ) {
  define( 'IMAGE_URL_START', 'http://impact.bodleian.ox.ac.uk/public/images/' );
  define( 'IMAGE_DIR_FOR_UPLOADS', '/srv/data/public/images/uploaded/' );
}
else {
  define( 'IMAGE_URL_START', 'http://emlo-edit.bodleian.ox.ac.uk/culturesofknowledge/images/' );
  define( 'IMAGE_DIR_FOR_UPLOADS', '/srv/data/culturesofknowledge/images/uploaded/' );
}

define( 'UPLOADED_IMAGE_WIDTH_PX', 2000 );

define( 'IMAGE_URL_FIELD_SIZE', 120 );
define( 'THUMBNAIL_DISPLAY_AREA', 'thumbDisplayArea' );
define( 'THUMBNAIL_DISPLAY_AREA_HEIGHT_PX', 400 );

define( 'CREDITS_FIELD_SIZE',     IMAGE_URL_FIELD_SIZE );
define( 'LICENCE_URL_FIELD_SIZE', IMAGE_URL_FIELD_SIZE );
define( 'DISPLAY_ORDER_FIELD_SIZE', 2 );

define( 'LICENCE_DETAILS_COLS', 117 );
define( 'LICENCE_DETAILS_ROWS', 4 );

define( 'IMAGE_UPLOAD_FIELD_SIZE', 60 );

define( 'CULTURES_OF_KNOWLEDGE_DEFAULT_LICENCE_URL',
        'http://cofk2.bodleian.ox.ac.uk/culturesofknowledge/licence/terms_of_use.html' );

class Image extends Project {

  #----------------------------------------------------------------------------------

  function Image( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
  }

  #----------------------------------------------------------------------------------

  function set_image( $image_id = NULL ) {

    $this->clear();
    if( ! $image_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_image_tablename()
               . " where image_id = $image_id";
    $this->db_select_into_properties( $statement );

    return $this->image_id;
  }
  #----------------------------------------------------------------------------------

  function clear( $keep_entity_type = FALSE ) {

    if( $keep_entity_type )
      $keep_entity_type = $this->entity_type;

    parent::clear();

    if( $keep_entity_type )
      $this->set_entity_type( $keep_entity_type );
  }
  #----------------------------------------------------------------------------------

  function set_entity_type( $entity_type ) {

    #----------------------------------------------------------------------------
    # Check if this is one of the entities which can have images attached to them
    #----------------------------------------------------------------------------
    $is_valid = FALSE;  

    switch( $entity_type ) {

      case 'manifestation':
      case 'person':
      case 'location':
      case 'institution':

        $is_valid = TRUE;
        break;
    }

    if( ! $is_valid ) die( 'Invalid entity type passed to image entry procedure.' );

    #-------------------------------------------------
    # Set some properties derived from the entity type
    #-------------------------------------------------
    $this->entity_type = $entity_type;

    $this->entity_id_fieldname = $entity_type . '_id';

    $get_table_func = 'proj_' . $entity_type . '_tablename';
    $this->entity_tablename = $this->$get_table_func();

    $this->entity_classname = $entity_type;
    if( $entity_type == 'institution' ) { # unfortunately the table is 'institution' but the PHP class is 'repository'
      $this->entity_classname = 'repository';
    }
  }
  #----------------------------------------------------------------------------------

  function set_manifestation_images( $manifestation_id ) {

    return $this->set_entity_images( $manifestation_id, 'manifestation' );
  }
  #----------------------------------------------------------------------------------

  function set_entity_images( $entity_id, $entity_type = 'manifestation' ) {

    $this->set_entity_type( $entity_type );
    if( ! $entity_id ) return array();

    $statement = 'select i.* from ' 
               . $this->proj_relationship_tablename() . ' r, '
               . $this->proj_image_tablename() . ' i '
               . " where r.left_table_name = '" . $this->proj_image_tablename() . "' "
               . ' and r.left_id_value = i.image_id::varchar '
               . " and r.relationship_type = '" . RELTYPE_IMAGE_IS_OF_ENTITY . "' "
               . " and r.right_table_name = '" . $this->entity_tablename . "' "
               . " and r.right_id_value = '$entity_id'"
               . ' order by display_order, image_filename';
    
    $this->entity_images = $this->db_select_into_array( $statement );
    if( ! is_array( $this->entity_images )) $this->entity_images = array();
    return $this->entity_images;
  }
  #----------------------------------------------------------------------------------

  function image_entry_for_manif( $manifestation_id ) {

    $this->image_entry_for_entity( $manifestation_id, $entity_type = 'manifestation' );
  }
  #----------------------------------------------------------------------------------

  function image_entry_for_entity( $entity_id, $entity_type = 'manifestation' ) {

    $this->set_entity_type( $entity_type );

    if( ! $entity_id ) {
      echo "It will be possible to add images once the new $entity_type has been created.";
      html::new_paragraph();
      return;
    }

    $imgs = $this->set_entity_images( $entity_id, $entity_type );
    if( count( $imgs ) > 0 ) {
      foreach( $imgs as $one_img ) {
        echo $this->proj_make_image_link( $one_img [ 'image_filename' ] );
        echo ' ';
      }
      html::new_paragraph();
    }

    $this->write_image_entry_stylesheet();
    html::new_paragraph();

    html::italic_start();
    echo 'Copyright law must be respected at all times. You must ensure that all necessary'
         . ' permissions have been obtained before uploading or attempting to display images.';
    html::italic_end();
    html::new_paragraph();

    echo "You can associate images with a $entity_type in two different ways:";
    html::ulist_start();
    html::listitem( '<em>Upload</em> them if you have a copy on your own personal computer' );
    html::listitem( '<em>Link</em> to them by typing in, or copying and pasting in, the URL of an image'
                   . " on this or another repository's server." );
    html::ulist_end();
    echo 'In both cases, credits and licence details must be supplied before the image can be displayed to the public.';
    html::new_paragraph();

    $this->offer_file_upload();

    html::italic_start();
    echo 'Or:';
    html::italic_end();
    html::new_paragraph();

    $this->edit_images_by_url( $imgs );
  }
  #----------------------------------------------------------------------------------

  function offer_file_upload() {

    html::bold_start();
    echo 'Upload image files from your own computer: ';
    html::bold_end();

    html::submit_button( 'upload_images_button', 'Upload' );
    html::new_paragraph();
  }
  #----------------------------------------------------------------------------------

  function edit_images_by_url( $imgs ) {

    html::bold_start();
    echo 'Enter or change links to images by typing in the URL:';
    html::bold_end();
    $this->image_deletion_warning_script();
    html::new_paragraph();

    echo 'After entering or changing the URL of the main image or optionally the thumbnail image,'
         . " press Tab or click elsewhere on the form, and a 'Check URL' link will appear.";

    $curr_img_no = 0;
    $total_imgs = count( $imgs );

    $this->add_image_by_url( $total_imgs );  # allow them to type the URL directly in.
    html::new_paragraph();

    foreach( $imgs as $one_img ) {
      $curr_img_no++;
      if( $curr_img_no > 1 ) {
        html::new_paragraph();
      }
      $this->edit_existing_image( $one_img, $curr_img_no, $total_imgs );
    }
  }
  #----------------------------------------------------------------------------------

  function add_image_by_url( $existing_imgs = NULL ) {
    $this->edit_image_by_url( $one_img = NULL, $curr_img_no = 0, $total_imgs = $existing_imgs );
  }
  #----------------------------------------------------------------------------------

  function edit_existing_image( $one_img = NULL, $curr_img_no = NULL, $total_imgs = NULL ) {
    $this->edit_image_by_url( $one_img, $curr_img_no, $total_imgs );
  }
  #----------------------------------------------------------------------------------

  function edit_image_by_url( $one_img = NULL, $curr_img_no = 0, $total_imgs = NULL ) {

    #--------------------------------------
    # Get any existing data into properties
    #--------------------------------------
    if( $one_img ) {
      extract( $one_img, EXTR_OVERWRITE );
      $this->clear( $keep_entity_type = TRUE );
      foreach( $one_img as $colname => $colvalue ) {
        $this->$colname = $colvalue;
      }
    }

    #----------------------------------------
    # Set fieldnames, field IDs, scripts etc.
    #----------------------------------------
    $image_fieldname     = 'image_filename[]';
    $thumbnail_fieldname = 'thumbnail[]';

    html::hidden_field( 'image_id[]', $image_id );

    $input_instance = $image_id;  # used in generating unique field ID
    if( ! $input_instance ) $input_instance = 'new';

    $image_field_id     = html::field_id_from_fieldname( $image_fieldname,     $input_instance );
    $thumbnail_field_id = html::field_id_from_fieldname( $thumbnail_fieldname, $input_instance );

    $thumbnail_display_area = THUMBNAIL_DISPLAY_AREA . $input_instance;

    $check_image_link = $this->url_checker_linkname( $image_field_id );
    $check_thumbnail_link = $this->url_checker_linkname( $thumbnail_field_id );
    
    $check_image_scriptname = $this->url_checker_script( $image_field_id, $thumbnail_display_area );
    $check_thumbnail_scriptname = $this->url_checker_script( $thumbnail_field_id, $thumbnail_display_area );

    $toggle_readonly_scriptname = $this->toggle_readonly_script( $image_field_id, $thumbnail_field_id ); 

    $current_image_anchor = 'image_no_' . $curr_img_no;
    html::anchor( $current_image_anchor );

    if( $total_imgs > 0 && $curr_img_no < $total_imgs ) {
      $next_img_no = $curr_img_no + 1;
      $next_image_anchor = 'image_no_' . $next_img_no;
    }

    if( $curr_img_no > 0 ) {
      $prev_img_no = $curr_img_no - 1;
      $prev_image_anchor = 'image_no_' . $prev_img_no;
    }

    html::new_paragraph( 'class="rightaligned"' );
    if( $prev_image_anchor ) {
      if( $curr_img_no > 1 )
        $prev_img_txt = 'Previous image (no. ' . $prev_img_no . ' of ' . $total_imgs . ')';
      else
        $prev_img_txt = 'New image entry';
      html::link( '#' . $prev_image_anchor, $prev_img_txt );
    }

    if( $prev_image_anchor && $next_image_anchor ) echo ' | ';

    if( $next_image_anchor ) {
      if( $curr_img_no )
        $next_img_txt = 'Next image (no. ' . $next_img_no . ' of ' . $total_imgs . ')';
      else
        $next_img_txt = 'Go to existing image(s)';
      html::link( '#' . $next_image_anchor, $next_img_txt );
    }
    html::new_paragraph();

    #------------------------
    # Start 'image entry' div
    #------------------------
    html::div_start( 'class="image_entry"' );

    html::italic_start();
    if( $curr_img_no )
      echo 'Existing image no. ' . $curr_img_no . ' of ' . $total_imgs;
    else {
      echo 'Enter new image details:';

      # Entity type etc should already have been set in properties
      if( ! $this->entity_id_fieldname ) die( 'Entity properties not correctly set.' );

      $this->entity_id = $this->read_post_parm( $this->entity_id_fieldname );

      $this->set_default_credits();
      $this->set_default_licence();
    }
    html::italic_end();
    html::new_paragraph();

    #----------------------------
    # Display any image thumbnail
    #----------------------------
    html::div_start( 'class="image_entry_thumb"' );
    html::div_start( 'id="' . $thumbnail_display_area . '"' );

    if( $image_filename ) {
      $broken = $this->proj_link_is_broken( $image_filename );
      if( ! $broken )
        echo $this->proj_make_image_link( $image_filename );
      else {
        html::span_start( 'class="warning"' );
        echo '********** IMAGE NOT FOUND **********';
        html::span_end();
      }
    }
    if( $thumbnail ) {
      $broken = $this->proj_link_is_broken( $thumbnail );
      if( $broken ) {
        html::span_start( 'class="warning"' );
        echo LINEBREAK . '********** THUMBNAIL NOT FOUND **********';
        html::span_end();
      }
    }
    html::new_paragraph();
    html::div_end(); # end thumbnail display area

    # Write currently INVISIBLE link to allow them to check any changes they later make to the main image URL.
    html::link( $href = $image_filename, $displayed_text = 'Check image URL', $title = 'Check the image URL is working', 
                $target = '_blank', $accesskey = '', $tabindex = 1,
                $extra_parms = 'id="' . $check_image_link . '" style="display: none"' );
    html::new_paragraph();

    # Write currently INVISIBLE link to allow them to check any changes they later make to the thumbnail URL.
    html::link( $href = $thumbnail_filename, $displayed_text = 'Check thumbnail URL', 
                $title = 'Check the thumbnail URL is working', 
                $target = '_blank', $accesskey = '', $tabindex = 1,
                $extra_parms = 'id="' . $check_thumbnail_link . '" style="display: none"' );
    html::new_paragraph();

    html::div_end(); # end "image entry THUMBNAIL" div

    #-------------------------------------------
    # Display and allow editing of the image URL 
    # and other fields such as licence details.
    #-------------------------------------------
    html::div_start( 'class="image_entry_flds"' );
    html::div_start( 'class="workfield"' );

    $display_parms = NULL;
    if( $image_id ) {  # protect against accidental changes to the URL
      $display_parms = 'READONLY class="readonly" ';
    }

    #---------------
    # Main image URL
    #---------------
    $onchange_parms = ' onchange="' . $check_image_scriptname . '( this.value )"';

    html::input_field( $fieldname = $image_fieldname, 
                       $label = 'URL for full-size image', 
                       $value = $image_filename, 
                       $in_table = FALSE, 
                       $size = IMAGE_URL_FIELD_SIZE,
                       $tabindex = 1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = $display_parms . $onchange_parms, 
                       $input_instance );

    html::new_paragraph();

    #--------------
    # Thumbnail URL
    #--------------
    $onchange_parms = ' onchange="' . $check_thumbnail_scriptname . '( this.value )"';

    html::input_field( $fieldname = $thumbnail_fieldname, 
                       $label = 'URL for thumbnail (if any)', 
                       $value = $thumbnail, 
                       $in_table = FALSE, 
                       $size = IMAGE_URL_FIELD_SIZE,
                       $tabindex = 1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = $display_parms . $onchange_parms, 
                       $input_instance );
    html::new_paragraph();
    html::div_end(); # end "workfield" div

    #----------------------------------------------
    # Toggle between readonly and writable for URLs
    #----------------------------------------------
    if( $image_id ) {
      html::span_start( 'class="workfieldaligned"' );
      $this->basic_checkbox( 'toggleReadOnly' . $image_id, 'Make URLs editable', FALSE, 1,
                             'onclick="' . $toggle_readonly_scriptname . '( this )"' );
      html::span_end();
      html::new_paragraph();
    }

    #-------------
    # Licence etc.
    #-------------
    html::div_start( 'class="workfield boldlabel"' );

    $this->credits_field( $input_instance );
    html::new_paragraph();

    $this->licence_details_field( $input_instance );
    html::new_paragraph();

    $this->licence_url_field( $input_instance );
    html::new_paragraph();

    html::div_end(); # end "workfield boldlabel" div

    $this->can_be_displayed_field( $input_instance );
    html::new_paragraph();

    html::div_start( 'class="workfield"' );
    $this->display_order_field( $input_instance );
    html::new_paragraph();
    html::div_end(); # end "workfield" div

    if( $this->image_id ) {
      $this->delete_image_checkbox( $input_instance );
      html::new_paragraph();
    }

    html::submit_button( 'save_' . $input_instance . '_img', 'Save', $tabindex = 1, 'class="workfield_save_button"' );
    html::new_paragraph();
    html::div_end(); # end "image entry FIELDS" div

    html::linebreak( 'class="clearboth"' );

    html::div_end();  # end "image entry" div
  }
  #----------------------------------------------------------------------------------

  function credits_field( $input_instance ) {

    html::input_field( $fieldname = 'credits[]', 
                       $label = "Credits for 'front end' display*", 
                       $value = $this->credits, 
                       $in_table = FALSE, 
                       $size = CREDITS_FIELD_SIZE,
                       $tabindex = 1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = NULL,
                       $input_instance );
  }
  #----------------------------------------------------------------------------------

  function licence_details_field( $input_instance ) {

    html::textarea( 'licence_details[]', LICENCE_DETAILS_ROWS, LICENCE_DETAILS_COLS, 
                    $value = $this->licence_details, $label = 'Either: full text of licence*', 
                    NULL, NULL, $input_instance );
  }
  #----------------------------------------------------------------------------------

  function licence_url_field( $input_instance ) {

    html::input_field( $fieldname = 'licence_url[]', 
                       $label = "Or: licence URL*", 
                       $value = $this->licence_url, 
                       $in_table = FALSE, 
                       $size = CREDITS_FIELD_SIZE,
                       $tabindex = 1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = NULL,
                       $input_instance );
  }
  #----------------------------------------------------------------------------------

  function can_be_displayed_field( $input_instance ) {

    $value_when_checked = $this->image_id;  # helps us work out which image the checkbox refers to
    if( ! $this->image_id ) $value_when_checked = 'Y';

    if( $this->can_be_displayed == 'Y' )
      $checked = TRUE;
    elseif( ! $this->image_id )  # new record, default to displayable
      $checked = TRUE;
    else
      $checked = FALSE;

    html::span_start( 'class="workfieldaligned"' );
    $this->basic_checkbox( 'can_be_displayed[]', 'Can be displayed to public', $checked, $value_when_checked );
    html::span_end();

    html::span_start( 'class="narrowspaceonleft"' );
    html::italic_start();
    echo "(If this box is not ticked, the image will not be displayed in the 'front end'.)";
    html::italic_end();
    html::span_end();
  }
  #----------------------------------------------------------------------------------

  function display_order_field( $input_instance ) {

    if( ! $this->display_order ) $this->display_order = 1;

    html::input_field( $fieldname = 'display_order[]', 
                       $label = "Order for display in front end", 
                       $value = $this->display_order, 
                       $in_table = FALSE, 
                       $size = DISPLAY_ORDER_FIELD_SIZE,
                       $tabindex = 1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = 'onchange="js_check_value_is_numeric( this )"',
                       $input_instance );

    html::span_start( 'class="narrowspaceonleft"' );
    html::italic_start();
    echo "(Please enter a number greater than or equal to 1."
         . ' If multiple images have the same number, they will be ordered by filename.)';
    html::italic_end();
    html::span_end();
  }
  #----------------------------------------------------------------------------------
    
  function delete_image_checkbox( $input_instance ) {

    html::span_start( 'class="workfieldaligned"' );

    html::checkbox( $fieldname = 'delete_image[]', 
                    $label = 'Delete image from ' . $this->entity_type, 
                    $is_checked = FALSE, 
                    $value_when_checked = $this->image_id, 
                    $in_table = FALSE,
                    $tabindex=1, 
                    $input_instance, 
                    $parms = 'onclick="image_deletion_warning( this )"' );

    html::span_end();

    html::span_start( 'class="narrowspaceonleft"' );
    html::italic_start();
    echo "(Tick then click Save to delete the connection between this $this->entity_type and this image.)";
    html::italic_end();
    html::span_end();
  }
  #----------------------------------------------------------------------------------

  function image_deletion_warning_script() {

    $scriptname = 'image_deletion_warning';

    $script = "function $scriptname( chkbox ) {"                                                   . NEWLINE
            . '  if( chkbox.checked ) {'                                                           . NEWLINE
            . '    alert( "Image will be deleted from this ' . $this->entity_type . ' when you click Save!" );' 
                                                                                                   . NEWLINE
            . '  }'                                                                                . NEWLINE 
            . '  else {'                                                                           . NEWLINE
            . '    alert( "Image will not now be deleted from this ' . $this->entity_type . '." );'. NEWLINE
            . '  }'                                                                                . NEWLINE 
            . '}'                                                                                  . NEWLINE;
    html::write_javascript_function( $script );

    return $scriptname;
  }
  #----------------------------------------------------------------------------------

  function url_checker_linkname( $url_field_id ) {

    $check_link = 'check_' . $url_field_id . '_link';
    return $check_link;
  }
  #----------------------------------------------------------------------------------

  function url_checker_script( $url_field_id, $thumbnail_display_area ) {

    $check_link = $this->url_checker_linkname( $url_field_id );

    $script_name  = 'check_' . $url_field_id . '_function';

    $script  = "function $script_name( url_value ) {"                       . NEWLINE;
    $script .= "  var thumbnail_area = document.getElementById( '$thumbnail_display_area' );" . NEWLINE;
    $script .= '  thumbnail_area.innerHTML = "";'                           . NEWLINE;
    $script .= "  var the_link = document.getElementById( '$check_link' );" . NEWLINE;
    $script .= '  the_link.href = url_value;'                               . NEWLINE;
    $script .= '  if( url_value == "" ) {'                                  . NEWLINE;
    $script .= '    the_link.style.display = "none";'                       . NEWLINE;
    $script .= '  }'                                                        . NEWLINE;
    $script .= '  else {'                                                   . NEWLINE;
    $script .= '    the_link.style.display = "inline";'                     . NEWLINE;
    $script .= '  }'                                                        . NEWLINE;
    $script .= '}'                                                          . NEWLINE;

    html::write_javascript_function( $script );
    return $script_name;
  }
  #----------------------------------------------------------------------------------

  function toggle_readonly_script( $image_field_id, $thumbnail_field_id ) {

    $script_name  = 'toggle_readonly_' . $image_field_id . '_function';

    $script  = "function $script_name( the_checkbox ) {"                                       . NEWLINE;

    $script .= "  var the_image_field = document.getElementById( '$image_field_id' );"         . NEWLINE;
    $script .= "  var the_thumbnail_field = document.getElementById( '$thumbnail_field_id' );" . NEWLINE;

    $script .= '  if( the_checkbox.checked == true ) {'                                        . NEWLINE;

    $script .= '    the_image_field.readOnly = false;'                                         . NEWLINE;
    $script .= '    the_image_field.className = "writable";'                                   . NEWLINE;

    $script .= '    the_thumbnail_field.readOnly = false;'                                     . NEWLINE;
    $script .= '    the_thumbnail_field.className = "writable";'                               . NEWLINE;
    $script .= '  }'                                                                           . NEWLINE;

    $script .= '  else {'                                                                      . NEWLINE;

    $script .= '    the_image_field.readOnly = true;'                                          . NEWLINE;
    $script .= '    the_image_field.className = "readonly";'                                   . NEWLINE;

    $script .= '    the_thumbnail_field.readOnly = true;'                                      . NEWLINE;
    $script .= '    the_thumbnail_field.className = "readonly";'                               . NEWLINE;
    $script .= '  }'                                                                           . NEWLINE;
    $script .= '}'                                                                             . NEWLINE;

    html::write_javascript_function( $script );
    return $script_name;
  }
  #----------------------------------------------------------------------------------

  function write_image_entry_stylesheet() {

    $borderwidth = 5;
    $thumbwidth = IMAGE_THUMBNAIL_WIDTH_PX + ($borderwidth*2);
    $fldspos = WORK_FIELD_POS + $thumbwidth + ($borderwidth*2);

    $background_colour = html::get_highlight2_colour();

    echo '<style type="text/css">'                   . NEWLINE;

    echo '  div.image_entry {'                       . NEWLINE;
    echo "    padding: ${borderwidth}px;"            . NEWLINE;
    echo "    background-color: $background_colour;" . NEWLINE;
    echo '    width: ' . ceil(IMAGE_URL_FIELD_SIZE * 1.5) . 'em;'               
                                                     . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '  div.image_entry_flds {'                  . NEWLINE;
    echo '    display: inline;'                      . NEWLINE;
    echo "    margin-left: ${borderwidth}px;"        . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '  span.image_entry_flds_aligned {'         . NEWLINE;
    echo "    margin-left: ${fldspos}px;"            . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '  div.image_entry_thumb {'                 . NEWLINE;
    echo '    float: left;'                          . NEWLINE;
    echo "    width: ${thumbwidth}px;"               . NEWLINE;
    echo '    height: ' . THUMBNAIL_DISPLAY_AREA_HEIGHT_PX . 'px;'               
                                                     . NEWLINE;
    echo '    display: inline;'                      . NEWLINE;
    echo "    margin-left: ${borderwidth}px;"        . NEWLINE;
    echo "    margin-right: ${borderwidth}px;"       . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '  .image_entry_thumb img {'                . NEWLINE;
    echo '    float: left;'                          . NEWLINE;
    echo "    margin-left: ${borderwidth}px;"        . NEWLINE;
    echo "    margin-right: ${borderwidth}px;"       . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '  br.clearboth {'                          . NEWLINE;
    echo "    clear: both;"                          . NEWLINE;
    echo '  }'                                       . NEWLINE;
    
    echo '  div.image_entry input.readonly {'        . NEWLINE;
    echo "    background-color: $background_colour;" . NEWLINE;
    echo '  }'                                       . NEWLINE;
    
    echo '  div.image_entry input.writable {'        . NEWLINE;
    echo '    background-color: white;'              . NEWLINE;
    echo '  }'                                       . NEWLINE;

    echo '</style>'                                  . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function read_image_details_from_post() {

    $relevant_parms = array();

    $cols = $this->db_list_columns( $this->proj_image_tablename() );
    foreach( $cols as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'change_timestamp': 
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $relevant_parms[] = $column_name;

      $arrayname = $this->get_post_parm_arrayname( $column_name );

      $this->$arrayname = $this->read_post_parm( $column_name );
    }

    # Also read 'deletion checkbox' from POST (even though it is not actually a column)
    $varname = 'delete_image';
    $arrayname = $this->get_post_parm_arrayname( $varname );
    $this->$arrayname = $this->read_post_parm( $varname );
    $relevant_parms[] = $varname;

    return $relevant_parms;
  }
  #----------------------------------------------------------------------------------

  function get_post_parm_arrayname( $column_name ) {
    $arrayname = $column_name . '_array';
    return $arrayname;
  }
  #----------------------------------------------------------------------------------

  function save_image_details( $entity_id, $entity_type = 'manifestation' ) {

    $this->set_entity_type( $entity_type );

    if( ! $entity_id ) die( 'Cannot save images: invalid ' . $this->entity_type . ' details.' );
    $this->entity_id = $entity_id;

    # Values are read from POST into arrays.
    $relevant_parms = $this->read_image_details_from_post();

    $total_images = count( $this->image_id_array );

    #----------------------------------------------------
    # Go through the values from POST one image at a time
    #----------------------------------------------------
    for( $i = 0; $i < $total_images; $i++ ) {

      # Make sure we know image ID before we start on anything else
      $arrayname = $this->get_post_parm_arrayname( 'image_id' );
      $arr = $this->$arrayname;
      $image_id = $arr[ $i ];
      $this->image_id = $image_id;

      foreach( $relevant_parms as $parm_name ) {
        if( $parm_name == 'image_id' ) continue;  # already extracted from POST

        $this->$parm_name = NULL;  # make sure we've cleared out all data from the previous image
        $arrayname = $this->get_post_parm_arrayname( $parm_name );
        $arr = $this->$arrayname;

        switch( $parm_name ) {
          case 'can_be_displayed':  # need to treat checkboxes differently from normal fields (only there if checked)
          case 'delete_image':
            $parm_value = 'N';
            $parm_count = count( $arr );
            if( $parm_count > 0 ) {
              foreach( $arr as $key => $val ) {
                if( $val == $image_id )
                  $parm_value = 'Y';
                elseif( $parm_name == 'can_be_displayed' && $val == 'Y' && ! $image_id ) # new image
                  $parm_value = 'Y';

                if( $parm_value == 'Y' ) break;
              }
            }
            break;

          default:  # all normal input fields etc will appear in the array whether they are empty or not
            $parm_value = $arr[ $i ];
            break;
        }

        $this->$parm_name = $parm_value;
      }

      #------------------------------------------------------------
      # We've now got all the values for one image into properties.
      # Now we'll decide whether to insert, delete or update.
      #------------------------------------------------------------
      $action = '';

      $this->image_filename = trim( $this->image_filename );

      $this->set_displayability();

      if( $this->image_filename && ! $this->image_id )  # new record
        $action = 'insert';
      elseif( $this->delete_image == 'Y' || ( $this->image_filename == '' && $this->image_id ))
        $action = 'delete';
      elseif( $this->image_id )
        $action = 'update';

      if( ! $action ) continue;
      $funcname = $action . '_image';

      #----------------------------------------------------- 
      # Perform the insert, update or deletion for one image
      #----------------------------------------------------- 
      $this->$funcname();
    }
  }
  #----------------------------------------------------------------------------------

  function set_displayability() {

    if( ! $this->image_filename ) return;

    if( $this->delete_image == 'Y' ) return;

    for( $i = 1; $i <= 2; $i++ ) {
      if( $i == 1 ) {
        $val = $this->credits;
        $desc = "'Credits' field is";
      }
      else {
        $val = $this->licence_details . $this->licence_url;
        $desc = "'Licence text/URL' fields are";
      }

      $val = str_replace( NEWLINE, '', $val );
      $val = str_replace( CARRIAGE_RETURN, '', $val );
      $val = trim( $val );

      if( $this->can_be_displayed == 'Y' ) {
        if( ! $val ) {
          $this->can_be_displayed = 'N';

          html::div_start( 'class="warning"' );
          echo "$desc blank, so the image '" . $this->image_filename . "' will NOT be displayed to the public.";
          html::div_end();
          html::new_paragraph();
        }
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_cols_for_insert_or_update() {

    $cols = array();

    $rawcols = $this->db_list_columns( $this->proj_image_tablename() );
    foreach( $rawcols as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      $skip_it = FALSE;

      switch( $column_name ) {
        case 'change_timestamp': 
        case 'change_user':
        case 'creation_timestamp':
        case 'creation_user':
          $skip_it = TRUE;
          break;
      }

      if( $skip_it ) continue;
      $cols[] = $crow;
    }

    return $cols;
  }
  #----------------------------------------------------------------------------------

  function insert_image() {

    if( ! $this->entity_id ) die( 'Cannot save changes to images: invalid ' . $this->entity_type . ' details.' );

    $sequence = $this->proj_id_seq_name( $this->proj_image_tablename() );
    $statement = "select nextval( '$sequence'::regclass )"; 
    $this->image_id = $this->db_select_one_value( $statement );

    $column_list = $this->get_cols_for_insert_or_update();
    $column_clause = '';
    $values_clause = '';
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
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

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );

    $statement = 'insert into ' . $this->proj_image_tablename() . " ( $column_clause ) values ( $values_clause )";
    $this->db_run_query( $statement );

    $this->rel_obj->insert_relationship( $left_table_name = $this->proj_image_tablename(),
                                         $left_id_value = $this->image_id, 
                                         $relationship_type = RELTYPE_IMAGE_IS_OF_ENTITY,
                                         $right_table_name = $this->entity_tablename,
                                         $right_id_value = $this->entity_id ); 
  }
  #----------------------------------------------------------------------------------

  function update_image() {

    if( ! $this->entity_id ) die( 'Cannot save changes to images: invalid ' . $this->entity_type . ' details.' );

    $detect_changes = '';

    $statement = 'update ' . $this->proj_image_tablename() . ' set ';
    $column_list = $this->get_cols_for_insert_or_update();
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );
      if( $column_name == 'image_id' ) continue;
      if( ! $first_column ) $statement .= ', ';

      $value = $this->$column_name;

      if( $column_name == 'display_order' ) { # currently the only numeric field apart from image ID.
        if( $value < 1 ) $value = 1;
        $statement .= " $column_name = $value";
      }
      else
        $statement .= " $column_name = '" . $this->escape( $value ) . "'";

      # Avoid performing updates unnecessarily so we don't have to work through loads of triggers.
      if( $detect_changes > '' ) $detect_changes .= ' or ';

      if( $is_numeric )
        $detect_changes .= "coalesce( $column_name, -9999 ) != $value";
      else
        $detect_changes .= "coalesce( $column_name, '' ) != '" . $this->escape( $value ) . "'";

      $first_column = FALSE;
    }

    $statement .= " where ( $detect_changes )";
    $statement .= " and image_id = $this->image_id";

    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function delete_image() {

    if( ! $this->entity_id ) die( 'Cannot save changes to images: invalid ' . $this->entity_type . ' details.' );

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );

    # Delete the relationship between this image and this manifestation, person, place or whatever.
    $this->rel_obj->delete_relationship( $left_table_name = $this->proj_image_tablename(),
                                         $left_id_value = $this->image_id,
                                         $relationship_type = RELTYPE_IMAGE_IS_OF_ENTITY,
                                         $right_table_name = $this->entity_tablename,
                                         $right_id_value = $this->entity_id );

    # See if any other manifestations have a relationship to this image
    $others = $this->rel_obj->value_exists_on_either_side( $table_name = $this->proj_image_tablename(), 
                                                           $id_value = $this->image_id );

    if( ! $others ) {
      $statement = 'delete from ' . $this->proj_image_tablename() . " where image_id = $this->image_id";
      $this->db_run_query( $statement );
    }
  }
  #----------------------------------------------------------------------------------

  function read_and_display_work_and_manif() {

    $this->manifestation_id = $this->read_post_parm( 'manifestation_id' );
    if( ! $this->manifestation_id ) die( 'Invalid manifestation details.' );
    $this->entity_id = $this->manifestation_id;

    $this->work_id = $this->read_post_parm( 'work_id' );
    if( ! $this->work_id ) die( 'Invalid work details.' );

    html::h3_start();
    echo 'Upload images of manifestation:';
    html::h3_end();

    $funcname = $this->proj_database_function_name( 'decode_work', TRUE );
    $statement = "select $funcname( '$this->work_id' )";
    $work_desc = $this->db_select_one_value( $statement );

    $funcname = $this->proj_database_function_name( 'decode_manifestation', TRUE );
    $statement = "select $funcname( '$this->manifestation_id' )";
    $manifestation_desc = $this->db_select_one_value( $statement );

    $funcname = $this->proj_database_function_name( 'list_manifestation_images', TRUE );
    $statement = "select $funcname( '$this->manifestation_id' )";
    $this->image_list = $this->db_select_one_value( $statement );

    $this->echo_safely( 'Work: ' . $work_desc );
    html::new_paragraph();
    $this->echo_safely( 'Manifestation: ' . $manifestation_desc );
    html::new_paragraph();
  }
  #----------------------------------------------------------------------------------

  function read_and_display_any_entity( $entity_type ) {

    $this->set_entity_type( $entity_type );

    $this->entity_id = $this->read_post_parm( $this->entity_id_fieldname );
    if( ! $this->entity_id ) die( "Invalid $entity_type details." );

    html::h3_start();
    echo "Upload images of $this->entity_type:";
    html::h3_end();

    $funcname = $this->proj_database_function_name( 'decode', $include_collection_code = TRUE );
    $statement = "select $funcname( '$this->entity_tablename', '$this->entity_id' )";
    $decode = $this->db_select_one_value( $statement );

    $funcname = $this->proj_database_function_name( 'list_images_of_entity', TRUE );
    $statement = "select $funcname( '$this->entity_tablename', '$this->entity_id' )";
    $this->image_list = $this->db_select_one_value( $statement );

    $this->echo_safely( ucfirst( $entity_type ) . ': ' . $decode );
    html::new_paragraph();
  }
  #----------------------------------------------------------------------------------

  function image_upload_form( $entity_type = 'manifestation' ) {

    $this->set_entity_type( $entity_type );

    if( $entity_type == 'manifestation' )
      $this->read_and_display_work_and_manif();
    else
      $this->read_and_display_any_entity( $entity_type );

    if( $this->image_list ) {
      html::h4_start();
      echo 'Existing image file(s):';
      html::h4_end();
      $this->echo_safely( $this->image_list );
      html::new_paragraph();

      html::h4_start();
      echo 'Upload new image file:';
      html::h4_end();
    }

    html::form_start( $class_name = 'image', 
                      $method_name = 'process_uploaded_files', 
                      $form_name = NULL,  # use default
                      $form_target = '_self',
                      $onsubmit_validation = FALSE, 
                      $form_destination = NULL, 
                      $form_method='POST',
                      $parms = 'enctype="multipart/form-data"' );

    html::hidden_field( 'entity_type', $entity_type );

    if( $entity_type == 'manifestation' ) {
      html::hidden_field( 'work_id', $this->work_id );
      html::hidden_field( 'manifestation_id', $this->manifestation_id );
    }
    else
      html::hidden_field( $this->entity_id_fieldname, $this->entity_id );

    html::file_upload_field( $fieldname = 'files_to_process', 
                             $label = 'Choose a file', 
                             $value = NULL, 
                             $size = IMAGE_UPLOAD_FIELD_SIZE );
    html::new_paragraph();

    html::submit_button( 'upload_button', 'Upload' );
    html::form_end();
  }
  #----------------------------------------------------------------------------------

  function process_uploaded_files() {

    $entity_type = $this->read_post_parm( 'entity_type' );
    $this->set_entity_type( $entity_type );

    if( $entity_type == 'manifestation' )
      $this->read_and_display_work_and_manif();
    else
      $this->read_and_display_any_entity( $entity_type );
    html::new_paragraph();

    $filecount = count( $_FILES );
    if( ! $filecount ) {
      echo 'No files were uploaded.';
      return;
    }
    elseif( $filecount > 1 ) {
      echo LINEBREAK;
      echo 'You have tried to upload ' .  $filecount . ' files at once. Please just upload one file at a time.';
      return;
    }

    $one_file = $_FILES[ 'files_to_process' ];
    extract( $one_file, EXTR_OVERWRITE );
    $invalid = FALSE;
    if( ! $this->is_ok_free_text( $name ))     $invalid = TRUE;
    if( ! $this->is_ok_free_text( $tmp_name )) $invalid = TRUE;
    if( ! $this->is_ok_free_text( $type ))     $invalid = TRUE;
    if( ! $this->is_integer( $error ))         $invalid = TRUE;
    if( ! $this->is_integer( $size ))          $invalid = TRUE;

    if( ! is_uploaded_file( $tmp_name ))       $invalid = TRUE;

    if( $invalid ) die( 'Invalid image details.' );

    #----------------------------------------------------------------------------
    # Get a new, unique filename based on original catalogue and image ID number.
    # Or for people, locations etc, entity type plus image ID number.
    #----------------------------------------------------------------------------
    if( $entity_type == 'manifestation' ) {
      $this->work_obj = new Work( $this->db_connection );
      $this->work_obj->set_work_by_text_id( $this->work_id );

      $cat = $this->work_obj->proj_get_core_catalogue();
      if( ! $cat ) $cat = $this->get_system_prefix();
      $prefix = $cat;
    }
    else 
      $prefix = $this->entity_classname;

    if( $prefix == 'person' ) { # check to see if it is an organisation
      $statement = 'select is_organisation from ' . $this->proj_person_tablename() 
                 . " where person_id = '$this->entity_id'";
      $is_org = $this->db_select_one_value( $statement );
      if( $is_org ) $prefix = 'organisation';
    }

    $statement = "select nextval('" . $this->proj_id_seq_name( $this->proj_image_tablename()) . "'::regclass )";
    $image_id = $this->db_select_one_value( $statement );

    $extension = $this->proj_get_file_extension( $name );

    $new_filename = $prefix . $image_id . '.' . $extension;
    $intermediate_filename = 'tmp' . $new_filename;

    $new_path = IMAGE_DIR_FOR_UPLOADS . $new_filename;
    $intermediate_path = IMAGE_DIR_FOR_UPLOADS . $intermediate_filename;

    if( $entity_type == 'manifestation' )  # need to use work object so it can check catalogue
      $thumbnail_path = $this->work_obj->proj_default_thumbnail_file( $new_path, $entity_type );
    else
      $thumbnail_path = $this->proj_default_thumbnail_file( $new_path, $entity_type );

    #----------------------------------------
    # Move the file to its permanent position
    #----------------------------------------
    $moved = move_uploaded_file( $tmp_name, $new_path );
    if( $moved )
      echo 'Uploaded file to server...';
    else 
      die( 'FAILED TO MOVE file to image directory.');

    html::new_paragraph();

    #-------------------------------------
    # Resize the file and make a thumbnail
    #-------------------------------------
    $displayable = $this->proj_is_displayable_image_file( $extension );
    if( $displayable ) {
      echo 'Resizing file for web display...';
      html::new_paragraph();
      flush();

      $statement = "mv $new_path $intermediate_path";
      exec( $statement );

      $statement = "convert $intermediate_path -resize " . UPLOADED_IMAGE_WIDTH_PX . " $new_path";
      exec( $statement );
      if( ! file_exists( $new_path )) {
        die( 'Could not resize image.' );
      }

      echo 'Creating thumbnail file...';
      html::new_paragraph();
      flush();

      $statement = "convert $new_path -resize " . IMAGE_THUMBNAIL_WIDTH_PX . " $thumbnail_path";
      exec( $statement );
      if( ! file_exists( $thumbnail_path )) {
        die( 'Could not create image thumbnail.' );
      }

      $deleted = unlink( $intermediate_path );
      if( ! $deleted ) die( 'An error occurred while processing the image.' );
    }
    
    $readable = chmod( $new_path, 0444 );
    if( ! $readable )
      die( 'ERROR: FAILED to make file accessible.');

    #---------------------------------------
    # Set values for insertion into database
    #---------------------------------------
    $this->image_filename = str_replace( IMAGE_DIR_FOR_UPLOADS, IMAGE_URL_START . 'uploaded/', 
                                         $new_path );
    $this->thumbnail =  str_replace( IMAGE_DIR_FOR_UPLOADS, IMAGE_URL_START . 'uploaded/', 
                                     $thumbnail_path );

    $this->set_default_credits();
    $this->set_default_licence();

    $this->can_be_displayed = 'N';
    if( $this->credits > '' && $this->licence_details . $this->licence_url > '' )
      $this->can_be_displayed = 'Y';

    $imgs = $this->set_entity_images( $this->entity_id, $this->entity_type );
    $existing_img_count = count( $imgs );
    if( $existing_img_count == NULL ) $existing_img_count = 0;
    $this->display_order = $existing_img_count + 1;

    $this->insert_image();

    #--------------------------------------
    # Allow the user to enter another image
    #--------------------------------------
    if( $entity_type == 'manifestation' ) {
			$work_class = PROJ_COLLECTION_WORK_CLASS;
			$work_obj = new $work_class ( $this->db_connection );
			$work_obj->set_work_by_text_id( $this->work_id );
			$this->write_post_parm( 'iwork_id', $work_obj->iwork_id );
			$this->write_post_parm( 'selected_tab', 'manifestations_tab' );
			$this->write_post_parm( 'manifestation_id', $this->manifestation_id );
			$this->write_post_parm( 'edit_manifestation_button', 'Edit' );
			$work_obj->edit_work();
    }
    elseif( $entity_type == 'person' ) {
      $statement = "select iperson_id from $this->entity_tablename where person_id = '$this->entity_id'";
      $iperson_id = $this->db_select_one_value( $statement );
			$this->write_post_parm( 'iperson_id', $iperson_id );

			$person_obj = new Person( $this->db_connection );
			$person_obj->edit_person();
    }
    else {
			$this->write_post_parm( $this->entity_id_fieldname, $this->entity_id );
      $entity_obj = new $this->entity_classname( $this->db_connection );
      $funcname = 'edit_' . $this->entity_classname;
			$entity_obj->$funcname();
    }

    $anchor_name = 'imgs_anchor';
    $script = 'window.location.href = "' . $_SERVER['PHP_SELF'] . '" + "#' . $anchor_name . '"';
    html::write_javascript_function( $script );

  }
  #----------------------------------------------------------------------------------

  function set_default_credits() {

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );

    $repos = $this->rel_obj->get_other_side_for_this_on_left( $this_table = $this->proj_manifestation_tablename(), 
                                                              $this_id = $this->manifestation_id , 
                                                              $reltype = RELTYPE_MANIF_STORED_IN_REPOS, 
                                                              $other_table = $this->proj_institution_tablename());
    if( count( $repos ) != 1 ) return;

    $repos_id = $repos[0][ 'other_id_value' ];

    $repos_obj = new Repository( $this->db_connection );
    $repos_name = $repos_obj->get_repository_desc_for_credits( $repos_id );
    $repos_name = trim( $repos_name );
    if( ! $repos_name ) return;

    $copyright_symbol = mb_convert_encoding( '&#169;', $this->get_character_encoding(), 'HTML-ENTITIES');

    $this->credits = $copyright_symbol . ' ' . $repos_name;
  }
  #----------------------------------------------------------------------------------

  function set_default_licence() {

    $this->licence_details = '';
    $this->licence_url = '';

    if( $this->get_system_prefix() == CULTURES_OF_KNOWLEDGE_SYS_PREFIX ) {
      $this->licence_url = CULTURES_OF_KNOWLEDGE_DEFAULT_LICENCE_URL;
    }
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'image_id':
      case 'location_id':
      case 'institution_id':
      case 'display_order':
      case 'delete_image':
        if( $this->is_integer( $this->parm_value ))
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value );

      case 'can_be_displayed':
        switch( $this->parm_value ) {
          case 'Y':
          case 'N':
            return TRUE;
          default:
            if( $this->is_integer( $this->parm_value )) # sometimes holds image ID, just to help in form processing
              return TRUE;
            else
              return $this->is_array_of_alphanumeric( $this->parm_value );
        }

      case 'image_filename':
      case 'thumbnail':
      case 'licence_details':
      case 'licence_url':
      case 'credits':
        if( $this->is_ok_free_text( $this->parm_value ))
          return TRUE;
        else
          return $this->is_array_of_ok_free_text( $this->parm_value );

      case 'manifestation_id':
      case 'work_id':
      case 'person_id':
        return $this->is_html_id( $this->parm_value );

      case 'entity_type':
        return $this->is_alphanumeric( $this->parm_value, $allow_underscores = TRUE );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
