<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

define( 'FLD_SIZE_DATE_AS_MARKED', 40 );
define( 'FLD_SIZE_DATE_NOTES_ROWS', 3 );
define( 'FLD_SIZE_DATE_NOTES_COLS', 50 );

define( 'FLD_SIZE_AUTHORS_AS_MARKED', 70 );
define( 'FLD_SIZE_PLACES_AS_MARKED', 70 );
define( 'FLD_SIZE_ACCESSION_CODE', 70 );
define( 'MAX_SIZE_ACCESSION_CODE', 250 );

define( 'FLD_SIZE_INCIPIT_ROWS', 4 );
define( 'FLD_SIZE_INCIPIT_COLS', 70 );

define( 'FLD_SIZE_EXCIPIT_ROWS', 4 );
define( 'FLD_SIZE_EXCIPIT_COLS', 70 );

define( 'FLD_SIZE_ABSTRACT_ROWS', 6 );
define( 'FLD_SIZE_ABSTRACT_COLS', 90 );

define( 'FLD_SIZE_KEYWORD_ROWS', 4 );
define( 'FLD_SIZE_KEYWORD_COLS', 70 );

define( 'FLD_SIZE_NOTES_ON_WORK_ROWS', 6 );
define( 'FLD_SIZE_NOTES_ON_WORK_COLS', 90 );

define( 'REPLY_TD_WIDTH', 350 );

if( Application_Entity::get_system_prefix() == IMPACT_SYS_PREFIX )
  define( 'DEFAULT_WORK_EDIT_TAB', 'work_tab' );
else
  define( 'DEFAULT_WORK_EDIT_TAB', 'correspondents_tab' );

define( 'WORK_ID_PAD_ZEROES', 9 );  # how much to pad the 'iwork id' in order to make 'work id'

class Editable_Work extends Work {

  #----------------------------------------------------------------------------------

  function Editable_Work( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Work( $db_connection );

    $this->date_entity = new Date_Entity( $this->db_connection );
    $this->manifest_obj = new Manifestation( $this->db_connection );
    $this->extra_anchors = array();
  }

  #----------------------------------------------------------------------------------

  function edit_work ( $just_saved = NULL ) {

    $new_record = FALSE;
    if( $this->read_post_parm( 'iwork_id' ))
      $this->set_work();
    else
      $new_record = TRUE;

    $focus_script = $this->write_work_entry_form( $new_record, $just_saved );

    if( $focus_script ) HTML::write_javascript_function( $focus_script );
  }
  #-----------------------------------------------------

  function add_work () {

    $this->clear();
    $this->write_post_parm( 'selected_tab', DEFAULT_WORK_EDIT_TAB ); # overwrite anything from Edit Existing Work
    $this->write_work_entry_form( $new_record = TRUE, $just_saved = FALSE );
  }
  #-----------------------------------------------------

  function write_work_entry_form( $new_record, $just_saved ) {

    $focus_script = '';

    $this->write_work_entry_stylesheet();
    $this->write_tabform_stylesheet();
    $this->date_entity->write_date_entry_stylesheet();
    $this->write_display_change_script();

    $this->form_name = HTML::form_start( $this->app_get_class( $this ), 'save_work', NULL, NULL, $onsubmit_validation = TRUE );

    $this->write_js_prompt_to_confirm_submission();  # prompt if they try to change tabs without saving
    $this->write_js_enable_form_submission();  # re-enable form submission after they have saved

    if( ! $new_record ) {
      HTML::hidden_field( 'iwork_id', $this->iwork_id );
      HTML::hidden_field( 'opening_method', $this->opening_method );

      HTML::h3_start();
      echo $this->get_work_desc( $this->work_id );
      HTML::h3_end();

      HTML::italic_start();
      echo 'Work ID ' . $this->iwork_id 
         . '. Last change ' . $this->postgres_date_to_words($this->change_timestamp)
         . ' by ' . $this->change_user . LINEBREAK;
      HTML::italic_end();

      # Get a script to put the focus on the 'Refresh' button (closes this tab and refreshes the opening one).
      $focus_script = $this->proj_write_post_save_refresh_button( $just_saved, $this->opening_method );
    }

    elseif( $this->parm_found_in_post( 'iwork_id' )) {  # came from 'Edit existing record' screen via 'New' button
      $this->unset_post_parm( 'iwork_id' ); # just in case, let's make sure we avoid any mix-ups!
      HTML::h3_start();
      echo 'Create new record';
      HTML::h3_end();
    }

    $this->button_area();

    $this->tab_area();

    $this->tab_method();

    HTML::form_end();
    HTML::new_paragraph();

    # If the user clicked a 'Save and continue' button, return them to the section of the form where they were working
    # rather than to the 'Refresh' button at the top of the form.

    $anchor_script = $this->proj_get_anchor_script_after_save();

    if( $anchor_script )
      return $anchor_script;
    else
      return $focus_script;
  }
  #-----------------------------------------------------

  function date_fields() {

    $this->date_as_marked_field() ;
    HTML::new_paragraph();

    $this->original_calendar_field() ;
    HTML::new_paragraph();

    $this->date_as_marked_std_field();
    HTML::new_paragraph();

    $extra_anchor = 'date_notes';
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    $this->date_flags();
    HTML::new_paragraph();

    $this->date_notes_field();
    HTML::new_paragraph();

    if( $this->app_get_class( $this ) != 'selden_work' )
      $this->extra_save_button( 'date_notes' );
  }
  #-----------------------------------------------------

  function date_as_marked_field() {

    HTML::span_start( 'class="workfield"' );

    HTML::input_field( 'date_of_work_as_marked',  $label = 'Date of work as marked', $this->date_of_work_as_marked, 
                       FALSE, FLD_SIZE_DATE_AS_MARKED );
    
    HTML::span_end( 'workfield' );
  }
  #-----------------------------------------------------

  function original_calendar_field() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Original calendar: ' );
    HTML::span_end( 'workfield' );

    $this->date_entity->calendar_selection_field( $fieldname='original_calendar', 
                                                  $selected_calendar = $this->original_calendar ); 
  }
  #-----------------------------------------------------

  function date_as_marked_std_field() {

    $this->date_entry_fieldset( $fields = array( 'date_of_work_std', 
                                                 'date_of_work2_std' ),

                                $legend     = 'Date of work in standard format', 

                                $extra_msg = 'Leave any part or parts of the date blank if necessary.' 
                                           . '<p>'
                                           . ' If the original work was dated using the Julian calendar,'
                                           . ' please retain the Julian day/month here.'
                                           . ' For example, if the date was marked as 8th January 1659/60'
                                           . ' in the original work, then enter the day here'
                                           . " as '8' not '18'."
                                           . '<p>'
                                           . ' However, please do adjust the year for works'
                                           . ' dated using the form of Julian calendar where the New Year began'
                                           . ' on 25th March. For example, 8th January 1659/60'
                                           . ' should be entered as 8-Jan-1660.',

                                $calendar_fieldname = 'original_calendar' );
  }
  #-----------------------------------------------------

  function date_flags() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Issues with date of work: ' );
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

    $this->basic_checkbox( 'date_of_work_inferred', 'Date is inferred', $this->date_of_work_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_date_field() {

    $this->basic_checkbox( 'date_of_work_uncertain', 'Date is uncertain', $this->date_of_work_uncertain );
  }
  #-----------------------------------------------------

  function flag_approx_date_field() {

    $this->basic_checkbox( 'date_of_work_approx', 'Date is approximate', $this->date_of_work_approx );
  }
  #-----------------------------------------------------

  function date_notes_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_DATE, 'Notes on date:' );
  }
  #-----------------------------------------------------

  function notes_on_patron_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_WORK,
                             'Bibliographic references and any other notes on patron:' );
  }
  #-----------------------------------------------------

  function notes_on_dedicatee_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_WORK,
                             'Bibliographic references and any other notes on dedicatee:' ); 
  }
  #-----------------------------------------------------

  function person_entry_field( $fieldset_name,               # e.g. 'author_sender'
                               $section_heading,             # e.g. 'Authors/senders:'
                               $decode_display,              # e.g. 'author/sender'
                               $separate_section = TRUE,     # add horizontal rule, bold heading and Save key
                               $extra_notes = NULL,
                               $include_date_fields = FALSE ) {

    if( ! $this->popup_person ) $this->popup_person = new Popup_Person( $this->db_connection );
    $this->popup_person->set_work_id( $this->work_id );

    $manifs = $this->proj_get_relationships_of_type( RELTYPE_MANIFESTATION_IS_OF_WORK );
    $manif_string = '';
    if( count( $manifs ) > 0 ) {
      foreach( $manifs as $row ) {
        $manif_id = $row[ 'left_id_value' ];
        if( $manif_string > '' ) $manif_string .= ',';
        $manif_string .= $manif_id;
      }
    }

    $this->popup_person->set_manifestation_ids( $manif_string );

    # Method from Project class
    $this->proj_edit_area_calling_popups( $fieldset_name, 
                                          $section_heading, 
                                          $decode_display,
                                          $separate_section,
                                          $extra_notes,
                                          $popup_object_name = 'popup_person',
                                          $popup_object_class = 'popup_person',
                                          $include_date_fields );
  }
  #-----------------------------------------------------

  function author_sender_field( $horizontal_rule = TRUE, $heading = 'Authors/Senders:' ) { 

    HTML::new_paragraph();
    if( $horizontal_rule ) {
      HTML::horizontal_rule();
    }

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'authors_senders', $heading_level = 4 );

    $this->person_entry_field( FIELDSET_AUTHOR_SENDER,
                               $section_heading             = 'Authors/senders (standardised):',
                               $decode_display              = 'author/sender',
                               $separate_section            = FALSE );

    echo LINEBREAK;

    $this->authors_as_marked_field();

    HTML::new_paragraph();
    $this->author_flags();

    HTML::new_paragraph();
    $this->notes_on_authors_field();
  }
  #-----------------------------------------------------

  function addressee_field( $horizontal_rule = TRUE, $heading = 'Addressees:' ) {

    HTML::new_paragraph();
    if( $horizontal_rule ) {
      HTML::horizontal_rule();
    }

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'addressees', $heading_level = 4 );

    $this->person_entry_field( FIELDSET_ADDRESSEE,
                               $section_heading             = 'Addressees (standardised):',
                               $decode_display              = 'addressee',
                               $separate_section            = FALSE );
    echo LINEBREAK;

    $this->addressees_as_marked_field();

    HTML::new_paragraph();
    $this->addressee_flags();

    HTML::new_paragraph();
    $this->notes_on_addressees_field();
  }
  #-----------------------------------------------------

  function author_flags( $label_part = 'Authors/senders' ) {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Issues with ' . strtolower( $label_part ) . ': ' );
    HTML::span_end( 'workfield' );

    HTML::ulist_start( 'class="dateflags"' );

    HTML::listitem_start();
    $this->flag_inferred_author_field( $label_part ) ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_uncertain_author_field( $label_part ) ;
    HTML::listitem_end();

    HTML::ulist_end();
  }
  #-----------------------------------------------------

  function flag_inferred_author_field( $label_part = 'Authors/senders' ) {

    $this->basic_checkbox( 'authors_inferred', $label_part . ' inferred', $this->authors_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_author_field( $label_part = 'Authors/senders' ) {

    $this->basic_checkbox( 'authors_uncertain', $label_part . ' uncertain', $this->authors_uncertain );
  }
  #-----------------------------------------------------

  function notes_on_authors_field( $label_part = 'Authors/senders' ) {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $label = 'Bibliographic references and any other notes on ' . strtolower( $label_part ) . ':';
    }
    else
      $label = 'Notes on ' . strtolower( $label_part ) . ':';

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_AUTHOR,
                             $label );
  }
  #-----------------------------------------------------

  function notes_on_addressees_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ADDRESSEE, 'Notes on addressees:' );
  }
  #-----------------------------------------------------

  function addressee_flags() {

    HTML::span_start( 'class="workfield"' );
    HTML::label( 'Issues with addressees: ' );
    HTML::span_end( 'workfield' );

    HTML::ulist_start( 'class="dateflags"' );

    HTML::listitem_start();
    $this->flag_inferred_addressee_field() ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_uncertain_addressee_field() ;
    HTML::listitem_end();

    HTML::ulist_end();
  }
  #-----------------------------------------------------

  function flag_inferred_addressee_field() {

    $this->basic_checkbox( 'addressees_inferred', 'Addressees inferred', $this->addressees_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_addressee_field() {

    $this->basic_checkbox( 'addressees_uncertain', 'Addressees uncertain', $this->addressees_uncertain );
  }
  #-----------------------------------------------------

  function people_mentioned_field() {

    $this->person_entry_field( FIELDSET_PEOPLE_MENTIONED,
                               $section_heading             = NULL, # heading is done by form sections
                               $decode_display              = 'person mentioned',
                               $separate_section            = FALSE );
  }
  #-----------------------------------------------------

  function places_mentioned_field() {

    $this->multiple_place_entry_field( $fieldset_name = FIELDSET_PLACES_MENTIONED,
                                       $section_heading = NULL, # heading is done by form sections
                                       $decode_display = 'place mentioned',
                                       $separate_section = FALSE, # don't add horizontal rule, bold heading and Save key
                                       $extra_notes = NULL );
  }
  #-----------------------------------------------------

  function works_mentioned_field() {

    $this->work_entry_field( $fieldset_name = FIELDSET_WORKS_MENTIONED,
                             $section_heading = NULL, # heading is done by form sections
                             $decode_display = 'work mentioned',
                             $separate_section = FALSE, # don't add horizontal rule, bold heading and Save key
                             $extra_notes = NULL );
  }
  #-----------------------------------------------------

  function work_entry_field( $fieldset_name,               # e.g. 'earlier_work_answered_by_this'
                             $section_heading,             # e.g. 'Earlier letters answered by this:'
                             $decode_display,              # e.g. 'work replied to'
                             $separate_section = TRUE,     # add horizontal rule, bold heading and Save key
                             $extra_notes = NULL,
                             $include_date_fields = FALSE ) {

    # Method from Project object
    $this->proj_edit_area_calling_popups( $fieldset_name, 
                                          $section_heading, 
                                          $decode_display,
                                          $separate_section,
                                          $extra_notes,
                                          $popup_object_name = 'popup_work',
                                          $popup_object_class = 'popup_work',
                                          $include_date_fields );
  }
  #-----------------------------------------------------

  function earlier_work_answered_by_this_field() {

    HTML::horizontal_rule();

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'earlier_letters', $heading_level = 4 );

    $this->work_entry_field( FIELDSET_EARLIER_WORK_ANSWERED_BY_THIS,
                             $section_heading             = 'Earlier letters answered by this one:',
                             $decode_display              = 'letter answered by this one',
                             $separate_section            = FALSE );

    $this->extra_save_button( 'earlier_letters' );
  }
  #-----------------------------------------------------

  function later_work_answering_this_field() {

    HTML::horizontal_rule();

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'later_letters', $heading_level = 4 );

    $this->work_entry_field( FIELDSET_LATER_WORK_ANSWERING_THIS,
                             $section_heading             = 'Later letters answering this one:',
                             $decode_display              = 'reply to this',
                             $separate_section            = FALSE );

    $this->extra_save_button( 'later_letters' );
  }
  #-----------------------------------------------------

    function matching_work() {

        HTML::horizontal_rule();

        # Heading is now set in method 'proj_list_form_sections()'.
        $this->proj_form_section_links( 'matching_letters', $heading_level = 4 );

        $this->work_entry_field( FIELDSET_MATCHING_WORK,
            $section_heading             = 'Matching letters:',
            $decode_display              = 'match',
            $separate_section            = FALSE );

        $this->extra_save_button( 'matching_letters' );
    }
    #-----------------------------------------------------

  function place_of_origin_field() {

    $this->place_entry_field( FIELDSET_ORIGIN );
  }
  #-----------------------------------------------------

	function notes_on_origin_field() {

		$this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ORIGIN, 'Notes on origin:' );
	}
	#-----------------------------------------------------

  function destination_field() {

    $this->place_entry_field( FIELDSET_DESTINATION );
  }
  #-----------------------------------------------------

	function notes_on_destination_field() {

		$this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_DESTINATION, 'Notes on destination:' );
	}
	#-------
	
	function notes_for_route_field() {

		$this->proj_notes_field( RELTYPE_COMMENT_DEFINES_ROUTE, 'Route from origin to destination' );
	}
	#-------
	
  function place_entry_field( $fieldset_name ) {

    $calling_field = $this->proj_new_id_fieldname_from_fieldset_name( $fieldset_name );
    $decode_fieldname = $this->proj_decode_fieldname_from_id_fieldname( $calling_field );
    $decode_field_label = $this->proj_get_field_label( $fieldset_name );

    $decode_field_initial_value = 'Select or create ' . strtolower( $decode_field_label );
    $calling_field_value = NULL;

    $place = $this->proj_get_entries_for_fieldgroup( $fieldset_name );

    if( count( $place ) > 0 ) {
      foreach( $place as $id => $rels ) {
        $calling_field_value = $id;
        $decode_field_initial_value = $this->location_obj->proj_get_description_from_id( $id );
        break;  # we're only expecting one origin or destination per letter
      }
    }

    $this->location_obj->proj_input_fields_calling_popups( 
                           $calling_form = $this->form_name, $calling_field,
                           $decode_fieldname, 
                           $decode_field_label . ' (standard format)', 
                           $decode_field_initial_value,
                           NULL, NULL, $calling_field_value ); 

    HTML::div_start( 'class="workfield"' );
    $parms = 'onclick="document.' . $this->form_name . '.' . $calling_field    . ".value='';"
                    . 'document.' . $this->form_name . '.' . $decode_fieldname . ".value='';" . '"';
    HTML::button( 'clear_' . $fieldset_name . '_button', 'X', $tabindex=1, $parms );
    echo ' (Click to blank out standard-format ' . strtolower( $decode_field_label )
         . ' on screen, then Save to finalise.)';
    HTML::div_end();
  }
  #-----------------------------------------------------

  function multiple_place_entry_field( $fieldset_name,               # e.g. 'places_mentioned'
                                       $section_heading,             # e.g. 'Places mentioned:'
                                       $decode_display,              # e.g. 'place mentioned'
                                       $separate_section = TRUE,     # add horizontal rule, bold heading and Save key
                                       $extra_notes = NULL,
                                       $include_date_fields = FALSE ) {

    # Method from Project class
    $this->proj_edit_area_calling_popups( $fieldset_name, 
                                          $section_heading, 
                                          $decode_display,
                                          $separate_section,
                                          $extra_notes,
                                          $popup_object_name = 'popup_location',
                                          $popup_object_class = 'popup_location',
                                          $include_date_fields );
  }
  #-----------------------------------------------------

  function origin_as_marked_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    HTML::input_field( 'origin_as_marked', $label = $this->proj_get_field_label( 'origin_as_marked' ),
                       $this->origin_as_marked, 
                       FALSE, FLD_SIZE_PLACES_AS_MARKED );
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function destination_as_marked_field() {

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    HTML::input_field( 'destination_as_marked',  $label = 'Destination as marked', $this->destination_as_marked, 
                       FALSE, FLD_SIZE_PLACES_AS_MARKED );
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function flag_inferred_origin_field() {

    $this->basic_checkbox( 'origin_inferred', $this->proj_get_field_label( 'origin_inferred' ), 
                           $this->origin_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_origin_field() {

    $this->basic_checkbox( 'origin_uncertain', $this->proj_get_field_label( 'origin_uncertain' ), 
                           $this->origin_uncertain );
  }
  #-----------------------------------------------------

  function flag_inferred_destination_field() {

    $this->basic_checkbox( 'destination_inferred', 'Destination inferred', $this->destination_inferred );
  }
  #-----------------------------------------------------

  function flag_uncertain_destination_field() {

    $this->basic_checkbox( 'destination_uncertain', 'Destination uncertain', $this->destination_uncertain );
  }
  #-----------------------------------------------------

  function relevant_to_cofk_field() {

    $values = array( 'Y' => 'Yes', 'N' => 'No', '' => 'Unknown' );

    HTML::bold_start();
    echo 'Relevant to Cultures of Knowledge? ';
    HTML::bold_end();

    $i = 0;
    foreach( $values as $value => $label ) {
      $i++;

      HTML::radio_button( $fieldname = 'relevant_to_cofk', 
                          $label, 
                          $value_when_checked = $value, 
                          $current_value = $this->relevant_to_cofk, 
                          $tabindex=1, 
                          $button_instance=$i, 
                          $script=NULL ) ;
    }
  }
  #-----------------------------------------------------

  function patrons_field( $horizontal_rule = TRUE, $heading = 'Patrons' ) {

    HTML::new_paragraph();
    if( $horizontal_rule ) {
      HTML::horizontal_rule();
    }

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'patrons', $heading_level = 4 );

    $this->person_entry_field( FIELDSET_PATRONS_OF_WORK,
                               $section_heading  = '',
                               $decode_display   = 'patron',
                               $separate_section = FALSE );

    HTML::new_paragraph();
    $this->notes_on_patron_field();
  }
  #-----------------------------------------------------

  function dedicatees_field( $horizontal_rule = TRUE, $heading = 'Dedicatees' ) {

    HTML::new_paragraph();
    if( $horizontal_rule ) {
      HTML::horizontal_rule();
    }

    # Heading is now set in method 'proj_list_form_sections()'.
    $this->proj_form_section_links( 'dedicatees', $heading_level = 4 );

    $this->person_entry_field( FIELDSET_DEDICATEES_OF_WORK,
                               $section_heading  = '',
                               $decode_display   = 'dedicatee',
                               $separate_section = FALSE );
    HTML::new_paragraph();
    $this->notes_on_dedicatee_field();
  }
  #-----------------------------------------------------

  function save_work() {
  
    $just_saved = FALSE;
    $new_record = FALSE;

    $action = $this->assess_submit_key();

    if( $action == 'cancel' ) {
      $this->return_to_search();
      return;
    }

    if( $action == 'add' ) {
      $this->add_work();
      return;
    }

    if( $action == 'upload_images' ) {
      $this->go_to_image_upload_page();
      return;
    }

    if( $action == 'save' ) {
      $iwork_id = $this->read_post_parm( 'iwork_id' );
      if( $iwork_id )
        $this->set_work( $iwork_id );
      else
        $new_record = TRUE;

      $this->save_work_fields( $new_record );
      $just_saved = TRUE;
    }

    $this->edit_work( $just_saved );
  }
  #-----------------------------------------------------

  function save_work_fields( $new_record ) {

    echo 'Saving data...';
    flush();

    $selected_tab = $this->read_post_parm( 'selected_tab' );
    $this->set_fields_and_functions( $selected_tab );

    $this->db_run_query( 'BEGIN TRANSACTION' );

    if( $new_record ) {
      $statement = $this->get_core_work_insert_statement();
      $this->db_run_query( $statement );
    }

    if( $selected_tab == 'manifestations_tab' ) {
      $this->manifest_obj->save_manifestation();
    }
    elseif( $selected_tab == 'related_tab' ) {
      $this->save_resources();
    }

    if( ! $new_record ) {
      $statement = $this->get_core_work_update_statement();
      if( $statement ) $this->db_run_query( $statement );
    }

    if( count( $this->save_functions_for_current_tab ) > 0 ) {
      foreach( $this->save_functions_for_current_tab as $func ) {
        $this->$func();
      }
    }

    $this->save_comments( $selected_tab ); # general notes, or notes specifically on date, author, addressee etc.

    #-----------------------------------------------------------
    # Check whether related data like manifestations has changed
    # (will have cascaded into 'queryable' table by now).
    # If so, update timestamp/user of work table.
    #-----------------------------------------------------------
    $this->set_wider_timestamp( $this->work_id );
    
    $this->db_run_query( 'COMMIT' );
  }
  #-----------------------------------------------------

  function save_author_sender() {

    $this->rel_obj->save_rels_for_field_type( $field_type = FIELDSET_AUTHOR_SENDER, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_addressee() {

    $this->rel_obj->save_rels_for_field_type( $field_type = FIELDSET_ADDRESSEE, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_people_mentioned() {

    $this->rel_obj->save_rels_for_field_type( $field_type = FIELDSET_PEOPLE_MENTIONED, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_places_mentioned() {

    $this->rel_obj->save_rels_for_field_type( $field_type = FIELDSET_PLACES_MENTIONED,
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_works_mentioned() {

    $this->rel_obj->save_rels_for_field_type( $field_type = FIELDSET_WORKS_MENTIONED,
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_earlier_work_answered_by_this() {

    $this->rel_obj->save_rels_for_field_type( FIELDSET_EARLIER_WORK_ANSWERED_BY_THIS, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_later_work_answering_this() {

    $this->rel_obj->save_rels_for_field_type( FIELDSET_LATER_WORK_ANSWERING_THIS, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

    function save_matching_work() {

        $this->rel_obj->save_rels_for_field_type( FIELDSET_MATCHING_WORK,
            $known_id_value = $this->work_id );
    }
    #-----------------------------------------------------

  function save_patrons_of_work() {

    $this->rel_obj->save_rels_for_field_type( FIELDSET_PATRONS_OF_WORK, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_dedicatees_of_work() {

    $this->rel_obj->save_rels_for_field_type( FIELDSET_DEDICATEES_OF_WORK, 
                                              $known_id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function save_subjects() {

    $subj_obj = new Subject( $this->db_connection );
    $subj_obj->save_subjects( $this->work_id );
  }
  #-----------------------------------------------------

  function save_comments( $selected_tab ) {

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );
    $reltypes = array();

    switch( $selected_tab ) {

      case 'correspondents_tab':  # notes on author/sender or addressee
      case 'work_tab':            # IMPAcT version of authors tab
        $reltypes[] = RELTYPE_COMMENT_REFERS_TO_AUTHOR;
        $reltypes[] = RELTYPE_COMMENT_REFERS_TO_ADDRESSEE;

        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_TITLE_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_TYPE_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_BASIS_TEXTS_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_COMMENTARIES_ON_WORK;
        }
        break;

      case 'dates_tab':           # notes on dates
        $reltypes[] = RELTYPE_COMMENT_REFERS_TO_DATE;
        break;

      case 'places_tab':
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COMPOSITION_OF_WORK;
        }

		  $reltypes[] = RELTYPE_COMMENT_REFERS_TO_ORIGIN;
		  $reltypes[] = RELTYPE_COMMENT_REFERS_TO_DESTINATION;
		  $reltypes[] = RELTYPE_COMMENT_DEFINES_ROUTE;
        break;

      case 'other_tab':           # general notes
        $reltypes[] = RELTYPE_COMMENT_REFERS_TO_ENTITY;
        $reltypes[] = RELTYPE_COMMENT_REFERS_TO_PEOPLE_MENTIONED_IN_WORK;  # notes on people mentioned in work
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_INCIPIT_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_EXCIPIT_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_COLOPHON_OF_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_PLACES_MENTIONED_IN_WORK;
          $reltypes[] = RELTYPE_COMMENT_REFERS_TO_WORKS_MENTIONED_IN_WORK;
        }
        break;

      default:
        break;
    }

    foreach( $reltypes as $reltype ) {
      $this->comment_obj->save_comments( $this->proj_work_tablename(), 
                                         $this->work_id, 
                                         $reltype );
    }
  }
  #-----------------------------------------------------

  function save_resources( $selected_tab ) {

    if( ! $this->resource_obj ) $this->resource_obj = new Resource( $this->db_connection );

    $this->resource_obj->save_resources( $this->proj_work_tablename(), 
                                         $this->work_id );
  }
  #-----------------------------------------------------

  function save_origin() {
    $this->save_single_place( FIELDSET_ORIGIN );
  }
  #-----------------------------------------------------

  function save_destination() {
    $this->save_single_place( FIELDSET_DESTINATION );
  }
  #-----------------------------------------------------

  function save_single_place( $fieldset_name ) {

    $fieldname = $this->proj_new_id_fieldname_from_fieldset_name( $fieldset_name );
    $new_value = $this->read_post_parm( $fieldname );

    $old_value = NULL;

    $place = $this->proj_get_entries_for_fieldgroup( $fieldset_name );
    if( count( $place ) > 0 ) {
      foreach( $place as $id => $rels ) {
        $old_value = $id;
        break;  # we're only expecting one origin or destination per letter
      }
    }

    if( $new_value != $old_value ) {

      # The 'relationship setting' array will give us left/right table names etc.
      $relationship_setting = $this->rel_obj->get_relationship_field_setting( $fieldset_name );
      extract( $relationship_setting, EXTR_OVERWRITE );
      foreach( $reltypes as $key => $value ) {
        $relationship_type = $key;
        break; # just get one row
      }

      if( $side_to_get == 'left' ) {
        $left_id_value = $old_value;
        $right_id_value = $this->work_id;
      }
      else {
        $right_id_value = $old_value;
        $left_id_value = $this->work_id;
      }

      if( $old_value ) {  # delete old origin or destination (we'll assume there is only one of each)
        $this->rel_obj->delete_relationship( $left_table_name, $left_id_value,
                                             $relationship_type,
                                             $right_table_name, $right_id_value );
      }

      if( $side_to_get == 'left' )
        $left_id_value = $new_value;
      else
        $right_id_value = $new_value;

      if( $new_value ) {  # insert new origin or destination (we'll assume there is only one of each)
        $this->rel_obj->insert_relationship( $left_table_name, $left_id_value,
                                             $relationship_type,
                                             $right_table_name, $right_id_value );
      }
    }
  }
  #-----------------------------------------------------

  function save_languages() {
    $this->proj_save_languages( $object_type = 'work', $id_value = $this->work_id );
  }
  #-----------------------------------------------------

  function list_tabs( $get_all_possible = FALSE ) {

    $tabs = array( 'correspondents_tab' => 'Correspondents',
                   'dates_tab'          => 'Dates',
                   'places_tab'         => 'Places',
                   'manifestations_tab' => 'Manifestations',
                   'related_tab'        => 'Related resources',
                   'other_tab'          => 'Other details',
                   'overview_tab'       => 'Overview' );

    if( ! $get_all_possible ) {
      if( ! $this->iwork_id ) { # new record, so only display the most basic tabs
        $tabs_for_new_record = 3; # correspondents, dates, places
        while( count(  $tabs ) > $tabs_for_new_record ) {
          $removed = array_pop( $tabs );
        }
      }
    }

    return $tabs;
  }
  #-----------------------------------------------------

  function tab_area() {

    $tabs = $this->list_tabs();


    $selected_tab = $this->read_post_parm( 'selected_tab' );
    if( ! $selected_tab ) $selected_tab = DEFAULT_WORK_EDIT_TAB;
    HTML::hidden_field( 'selected_tab', $selected_tab );

    $script  = ' function select_tab( tab_name ) {'                            . NEWLINE;
    $script .= '   var proceed=js_prompt_to_confirm_submission();'             . NEWLINE;
    $script .= '   if( proceed == true ) {'                                    . NEWLINE;
    $script .= '     var tabfield = document.getElementById( "selected_tab" );'. NEWLINE;
    $script .= '     js_drop_form_validation( tabfield );'                     . NEWLINE;
    $script .= '     tabfield.value = tab_name;'                               . NEWLINE;
    $script .= '   }'                                                          . NEWLINE;
    $script .= ' }'                                                            . NEWLINE;

    HTML::write_javascript_function( $script );

    HTML::div_start( 'class="tabform"' );
    HTML::new_paragraph();
    HTML::div_start( 'class="tabrow"' );
    //HTML::new_paragraph();

    foreach( $tabs as $tab => $tab_desc) {
      $parms = '';

      if( $tab == $selected_tab ) {
        $parms = 'class="fronttab"';
      }
      else {
        $parms = 'class="backtab"';
      }
      $parms .= ' onclick="select_tab( ' . "'" . $tab . "'" . ' )"';

      HTML::submit_button( $tab, $tab_desc, $tabindex=1, $parms );
    }

    HTML::div_end( 'tabrow' );

    //HTML::new_paragraph( 'class="undertabs"' );
    HTML::div_end( 'tabform' );
    //HTML::new_paragraph();



    $this->selected_tab = $selected_tab;
    $this->selected_tab_desc = $tabs[ "$selected_tab" ];
  }
  #-----------------------------------------------------

  function button_area( $start_with_horizontal_rule = TRUE ) {

    if( $start_with_horizontal_rule ) HTML::horizontal_rule();

    $this->original_catalogue_field();
    echo ' ';

    $this->edit_status_field();
    echo ' ';

    $this->work_deletion_field();
    echo ' ';

    HTML::span_start( 'class="widespaceonleft"' );

    HTML::submit_button( 'save_button', 'Save', $tabindex=1, 'onclick="js_enable_form_submission()"' );

    # HTML::italic_start();  -- No longer works in Firefox 3.6. Overridden by 'History' menu option.
    # echo " (Shortcut key for 'Save' is S.) "; -- Also we need more space on form for catalogue fields.
    # HTML::italic_end();

    HTML::submit_button( 'cancel_button', 'Search', $tabindex=1, 
                         $parms = $this->abandon_changes_button_parms());

    if( $this->iwork_id ) { # not already in 'Add New' option
      HTML::submit_button( 'add_new_work_button', 'New work', $tabindex=1, 
                           $parms = $this->abandon_changes_button_parms());
    }

    HTML::span_end();
  }
  #-----------------------------------------------------

  function abandon_changes_button_parms() {

    $parms = 'onclick="var proceed=js_prompt_to_confirm_submission(); '
           . 'if( proceed ) { js_drop_form_validation( this ); }"';
    return $parms;
  }
  #-----------------------------------------------------

  function original_catalogue_field() {

    $catg_obj = new Catalogue( $this->db_connection );

    if( ! $this->can_change_source_of_data()) {
      if( $this->original_catalogue ) {
        $catg_id = $catg_obj->get_lookup_id_from_code( $this->original_catalogue );
        $catg_name = $catg_obj->get_lookup_desc( $catg_id );
        echo 'Original catalogue: ' . $catg_name . ' ';
      }
      return;
    }

    $catg_obj->catg_code_dropdown( $field_name = 'original_catalogue', 
                                   $field_label = 'Original catalogue',
                                   $selected_code = $this->original_catalogue );
  }
  #-----------------------------------------------------

  function can_change_source_of_data() {

    # Can enter if currently blank, and can change if you are the original creator or a supervisor.
    if( ! $this->iwork_id ) return TRUE;
    if( ! $this->original_catalogue ) return TRUE;

    if( $this->username == $this->creation_user ) return TRUE;

    if( $this->user_is_supervisor()) return TRUE;

    # Also can change it if you are in the group of users for this original catalogue
    if( $this->proj_is_member_of_research_group( $this->original_catalogue )) return TRUE;

    return FALSE;
  }
  #-----------------------------------------------------

  function work_deletion_field() {

    if( $this->iwork_id ) {  # existing work

      $fieldname = 'work_to_be_deleted';
      $span_id = 'work_to_be_deleted_span';
      $normal_class = 'narrowspaceonleft';
      $warning_class = $normal_class . ' ' . 'warning';
      $change_display_scriptname = 'change_display_of_' . $span_id;

      if( $this->work_to_be_deleted )
        $current_class = $warning_class;
      else
        $current_class = $normal_class;

      $script = "function $change_display_scriptname( chkbox ) {"               . NEWLINE
              . "  var theSpan = document.getElementById( '$span_id' );"        . NEWLINE
              . '  if( chkbox.checked ) {'                                      . NEWLINE
              . "    theSpan.className = '$warning_class';"                     . NEWLINE
              . '  }'                                                           . NEWLINE 
              . '  else {'                                                      . NEWLINE
              . "    theSpan.className = '$normal_class';"                      . NEWLINE
              . '  }'                                                           . NEWLINE 
              . '}'                                                             . NEWLINE;
      HTML::write_javascript_function( $script );

      HTML::span_start( 'class="' . $current_class . '" id="' . $span_id . '"' );
      $parms = 'onclick="' . $change_display_scriptname . '( this )"';
             
      HTML::checkbox( $fieldname, $label = 'Work marked for deletion', $is_checked = $this->work_to_be_deleted, 
                      $value_when_checked = 1, $in_table = FALSE,
                      $tabindex=1, 
                      $input_instance = NULL,  # there will only be one 'work deletion' checkbox
                      $parms );

      HTML::span_end();
    }
  }
  #----------------------------------------------------------------------------------

  function edit_status_field() { # just a stub here, but is used by IMPAcT
  }
  #----------------------------------------------------------------------------------

  function extra_save_button( $prefix = NULL, $new_paragraph = TRUE, 
                              $parms='onclick="js_enable_form_submission()" class ="workfield_save_button"') {

    $this->proj_extra_save_button( $prefix, $new_paragraph, $parms );
  }
  #-----------------------------------------------------

  function assess_submit_key() {

    $action = '';

    if( $this->parm_found_in_post( 'cancel_button' ))
      $action = 'cancel';

    elseif( $this->parm_found_in_post( 'add_new_work_button' ))
      $action = 'add';

    elseif( $this->parm_found_in_post( 'edit_manifestation_button' )) {
      $action = 'edit';
    }

    elseif( $this->parm_found_in_post( 'upload_images_button' )) {
      $action = 'upload_images';
    }

    if( ! $action ) {
      $tabs = $this->list_tabs( $get_all_possible = TRUE );
      foreach( $tabs as $tab => $tab_label ) {
        if( $this->parm_found_in_post( $tab )) {
          $action = 'edit';
          break;
        }
      }
    }

    if( ! $action ) 
      $action = 'save';

    return $action;
  }
  #-----------------------------------------------------

  function return_to_search() {

    HTML::italic_start();
    echo 'Edit has been cancelled.';
    HTML::italic_end();
    HTML::new_paragraph();

    $this->set_work_search_parms();

    $this->entering_selection_criteria = TRUE; # assess column as if in Enter Search Criteria form
    $cols = $this->db_list_columns( $this->from_table );
    foreach( $cols as $crow ) {
      $column_name = $crow[ 'column_name' ];
      if( $this->parm_found_in_post( $column_name )) $this->unset_post_parm( $column_name );
    }

    $this->db_search();
  }
  #-----------------------------------------------------

  function tab_method() {

	  HTML::div_start( 'class="tabArea"' );
    $method_name = $this->selected_tab;
    $method_desc = $this->selected_tab_desc;

    if( ! method_exists( $this, $method_name )) {
      HTML::new_paragraph();
      echo "'$method_desc' tab is not yet available.";
      return;
    }

    $this->set_fields_and_functions( $method_name );  # List relevant fields in the 'work' table
                                                      # and functions for creating relationships etc

    $this->$method_name();
	  HTML::div_end( "tabArea" );
  }
  #-----------------------------------------------------

  function proj_list_form_sections() {

    $form_sections = array();

    $selected_tab = $this->read_post_parm( 'selected_tab' );
    if( ! $selected_tab ) $selected_tab = DEFAULT_WORK_EDIT_TAB;

    switch( $selected_tab ) {
      case 'correspondents_tab':
        $form_sections = array( 'authors_senders' => 'Authors/senders',
                                'addressees'      => 'Addressees',
                                'earlier_letters' => 'Earlier letters',
                                'later_letters'   => 'Later letters' ,
                                'matching_letters'   => 'Matching letters');
        break;

      case 'other_tab':
        $form_sections = array( 'editors_notes'         => "Editors' notes",
                                'langs_used'            => 'Language(s)',
                                'quotes_from_work'      => 'Incipit and explicit',
                                'abstract_and_keywords' => 'Abstract, subjects and keywords',
                                'people_mentioned'      => 'People mentioned',
                                'places_mentioned'      => 'Places mentioned',
                                'works_mentioned'       => 'Works mentioned',
                                'general_notes'         => 'General notes on work' );
        if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
          $discard = array_shift( $form_sections ); # IMPAcT has editors' notes on a different tab
          $discard = array_shift( $form_sections ); # IMPAcT has language on a different tab
        }
        break;

      case 'dates_tab':          # small enough not to need dividing into sections
      case 'places_tab':         # small enough not to need dividing into sections
      case 'manifestations_tab': # form is written out by manifestation object
      case 'related_tab':        # resources have their own way of creating form section links
      case 'overview_tab':
      default:
        break;
    }

    return $form_sections;
  }
  #-----------------------------------------------------

  function correspondents_tab() {

    $this->author_sender_field();
    $this->extra_save_button( 'authors_senders' );

    $this->addressee_field();
    $this->extra_save_button( 'addressees' );

    $this->earlier_work_answered_by_this_field();

    $this->later_work_answering_this_field();

      $this->matching_work();
  }
  #-----------------------------------------------------

  function dates_tab() {

    $this->date_fields();
  }
  #-----------------------------------------------------

  function places_tab() {

    $extra_anchor = 'place_of_origin';
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    $this->location_obj = new Popup_Location( $this->db_connection );

    HTML::bold_start();
    echo $this->proj_get_field_label( 'origin' ) . ':';
    HTML::bold_end();

    $this->origin_as_marked_field();
	  HTML::linebreak();

    $this->place_of_origin_field();

    HTML::ulist_start( 'class="dateflags"' );

    HTML::listitem_start();
    $this->flag_inferred_origin_field() ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_uncertain_origin_field() ;
    HTML::listitem_end();

    HTML::ulist_end();
    HTML::new_paragraph();

	  HTML::linebreak();
	  $this->notes_on_origin_field();

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $this->notes_on_place_of_composition_field();
    }

	  HTML::linebreak();
    $this->extra_save_button( 'place_of_origin' );

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::new_paragraph();

    #-----------

    $extra_anchor = 'destination';
    HTML::anchor( $extra_anchor . '_anchor' );
    $this->extra_anchors[] = $extra_anchor;

    HTML::bold_start();
    echo $this->proj_get_field_label( 'destination' ) . ':';
    HTML::bold_end();

    $this->destination_as_marked_field();
	  HTML::linebreak();

    $this->destination_field();

    HTML::ulist_start( 'class="dateflags"' );

    HTML::listitem_start();
    $this->flag_inferred_destination_field() ;
    HTML::listitem_end();

    HTML::listitem_start();
    $this->flag_uncertain_destination_field() ;
    HTML::listitem_end();

    HTML::ulist_end();
    HTML::new_paragraph();

	  HTML::linebreak();
	  $this->notes_on_destination_field();

	  HTML::linebreak();
    $this->extra_save_button( 'destination' );

	  HTML::new_paragraph();
	  HTML::horizontal_rule();
	  HTML::new_paragraph();

	  #-----------

	  $extra_anchor = 'route';
	  HTML::anchor( $extra_anchor . '_anchor' );
	  $this->extra_anchors[] = $extra_anchor;

	  HTML::bold_start();
	  echo $this->proj_get_field_label( 'route' ) . ':';
	  HTML::bold_end();

	  $this->notes_for_route_field();

	  HTML::linebreak();
	  $this->extra_save_button( 'destination' );
  }
  #-----------------------------------------------------

  function manifestations_tab() {

    $this->manifest_obj->manifestation_entry( $this->work_id, $this->form_name );
  }
  #-----------------------------------------------------

  function proj_enable_popup_add_method() {  # overrides method from Project

    return FALSE;  # don't enable 'Add' popup for works, only 'Select' popup
  }
  #----------------------------------------------------------------------------------

  function other_tab() {

    if( $this->can_change_source_of_data())
      $this->accession_code_field();
    else
      $this->echo_record_edited_by();

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {

      $this->proj_form_section_links( 'editors_notes', $heading_level = 0 );
      $this->editors_notes_field();

      HTML::new_paragraph();
      HTML::horizontal_rule();
      HTML::new_paragraph();

      $this->proj_form_section_links( 'langs_used', $heading_level = 4 );
      $this->languages_field();
    }

    $this->proj_form_section_links( 'quotes_from_work', $heading_level = 4 );
    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    $this->incipit_field();
    HTML::new_paragraph();

    $this->excipit_field();
    HTML::new_paragraph();

    $this->ps_field();
    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      $button_prefix = 'colophon';
    else
      $button_prefix = 'ps';
    $this->extra_save_button( $button_prefix );

    HTML::new_paragraph();
    HTML::horizontal_rule();
    #---------------------------------------

    $this->proj_form_section_links( 'abstract_and_keywords', $heading_level = 4 );

    $this->abstract_field();
    HTML::new_paragraph();

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
      HTML::div_end( 'workfield' );
      $this->work_subjects_field();
      HTML::div_start( 'class="workfield"' );
      HTML::new_paragraph();
    }

    $this->keywords_field();
    $this->extra_save_button( 'abstract_and_keywords' );

    HTML::new_paragraph();
    HTML::horizontal_rule();
    HTML::div_end( 'workfield' );
    #---------------------------------------

    $this->proj_form_section_links( 'people_mentioned', $heading_level = 4 );

    $this->people_mentioned_field();
    
    HTML::new_paragraph();

    $this->notes_on_people_mentioned_field();

    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'people_mentioned' );
    HTML::div_end( 'workfield' );

    HTML::horizontal_rule();
    HTML::new_paragraph();

    #-----

    $this->proj_form_section_links( 'places_mentioned', $heading_level = 4 );

    $this->places_mentioned_field();
    
    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'places_mentioned' );
    HTML::div_end( 'workfield' );

    HTML::horizontal_rule();
    HTML::new_paragraph();

    #-----

    $this->proj_form_section_links( 'works_mentioned', $heading_level = 4 );

    $this->works_mentioned_field();
    
    HTML::div_start( 'class="workfield"' );
    $this->extra_save_button( 'works_mentioned' );
    HTML::div_end( 'workfield' );

    HTML::horizontal_rule();
    HTML::new_paragraph();

    #-----

    $this->proj_form_section_links( 'general_notes', $heading_level = 4 );

    $this->notes_on_work_field();

    HTML::new_paragraph();

    $this->extra_save_button( 'general_notes' );
  }
  #-----------------------------------------------------

  function related_tab() {

    if( ! $this->resource_obj ) $this->resource_obj = new Resource( $this->db_connection );

    $resources = $this->proj_get_rels_for_resources();

    $this->resource_obj->edit_resources( $resources, $this->proj_work_tablename() );
  }
  #-----------------------------------------------------

  function overview_tab() {

    $this->overview();
  }
  #-----------------------------------------------------

  function authors_as_marked_field( $label_part = 'Authors/senders' ) {

    HTML::span_start( 'class="workfieldaligned italiclabel"' );
    HTML::label( $label_part . ' as marked (optional, e.g. may be relevant if an alias was used):' );
    HTML::span_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );
    HTML::new_paragraph();

    HTML::input_field( 'authors_as_marked',  $label = $label_part . ' as marked', $this->authors_as_marked, 
                       FALSE, FLD_SIZE_AUTHORS_AS_MARKED );
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function addressees_as_marked_field() {

    HTML::span_start( 'class="workfieldaligned italiclabel"' );
    HTML::label( 'Addressees as marked (optional, e.g. may be relevant if an alias was used):' );
    HTML::span_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="workfield"' );

    HTML::input_field( 'addressees_as_marked',  $label = 'Addressees as marked', $this->addressees_as_marked, 
                       FALSE, FLD_SIZE_AUTHORS_AS_MARKED );
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function accession_code_field() {

    HTML::input_field( 'accession_code',  $label = 'Source of data', $this->accession_code, 
                       FALSE, FLD_SIZE_ACCESSION_CODE );
    echo ' (max. ' . MAX_SIZE_ACCESSION_CODE . ' characters)';
    HTML::new_paragraph();
    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function languages_field() {

    $possible_langs = $this->proj_get_possible_languages();
    $actual_langs = $this->proj_get_languages_used( $object_type = 'work', $id_value = $this->work_id );
    if( ! $this->language_obj ) $this->language_obj = new Language( $this->db_connection );
    $this->language_obj->language_entry_fields( $possible_langs, $actual_langs );
  }
  #-----------------------------------------------------

  function incipit_field() {

    $this->proj_textarea( 'incipit', FLD_SIZE_INCIPIT_ROWS, FLD_SIZE_INCIPIT_COLS, 
                          $value = $this->incipit, $label = 'Incipit' );
  }
  #-----------------------------------------------------

  function excipit_field() {

    $this->proj_textarea( 'explicit', FLD_SIZE_EXCIPIT_ROWS, FLD_SIZE_EXCIPIT_COLS, 
                          $value = $this->explicit, $label = $this->db_get_default_column_label( 'explicit' ));
  }
  #-----------------------------------------------------

  function ps_field() {

    $label = $this->db_get_default_column_label( 'ps' ); # for IMPAcT, we use this field for Colophon

    $this->proj_textarea( 'ps', FLD_SIZE_EXCIPIT_ROWS, FLD_SIZE_EXCIPIT_COLS, 
                          $value = $this->ps, $label );
  }
  #-----------------------------------------------------

  function abstract_field() {

    $this->proj_textarea( 'abstract', FLD_SIZE_ABSTRACT_ROWS, FLD_SIZE_ABSTRACT_COLS, 
                    $value = $this->abstract, $label = 'Abstract' );
  }
  #-----------------------------------------------------

  function work_subjects_field() {

    $subj_obj = new Subject( $this->db_connection );
    $subj_obj->subject_entry_fields( $this->work_id );
  }
  #-----------------------------------------------------

  function keywords_field() {

    $this->proj_textarea( 'keywords', FLD_SIZE_KEYWORD_ROWS, FLD_SIZE_KEYWORD_COLS, 
                          $value = $this->keywords, $label = 'Keywords (ideally separated by semi-colons)' );
  }
  #-----------------------------------------------------

  function notes_on_work_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_ENTITY, NULL );
  }
  #-----------------------------------------------------

  function notes_on_people_mentioned_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PEOPLE_MENTIONED_IN_WORK,
                             'Notes on people mentioned in work:' );
  }
  #-----------------------------------------------------

  function notes_on_place_of_composition_field() {

    $this->proj_notes_field( RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COMPOSITION_OF_WORK, 
                            'Bibliographic references and other notes on place of composition:' );
  }
  #-----------------------------------------------------

  function echo_record_edited_by() {

    HTML::italic_start();
    echo 'Source of record: ' . $this->accession_code;
    HTML::italic_end();
    HTML::new_paragraph();
    HTML::horizontal_rule();
  }
  #-----------------------------------------------------

  function proj_get_entitydesc_td_width( $fieldset_name ) {  # overrides parent version

    switch( $fieldset_name ) {

      case FIELDSET_EARLIER_WORK_ANSWERED_BY_THIS:
      case FIELDSET_LATER_WORK_ANSWERING_THIS:
        return REPLY_TD_WIDTH;

      default:
        return parent::proj_get_entitydesc_td_width( $fieldset_name );
    }
  }
  #-----------------------------------------------------

  function set_wider_timestamp( $work_id ) {

    $statement = 'select change_timestamp from ' . $this->proj_work_tablename()
               . " where work_id = '$work_id'";
    $restricted_timestamp = $this->db_select_one_value( $statement );

    $statement = 'select change_timestamp from ' . $this->proj_queryable_work_tablename()
               . " where work_id = '$work_id'";
    $wider_timestamp = $this->db_select_one_value( $statement );

    if( $wider_timestamp > $restricted_timestamp ) {
      $statement = 'update ' . $this->proj_work_tablename() 
                 . " set change_timestamp = '$wider_timestamp'::timestamp, "
                 . ' change_user = user '
                 . " where work_id = '$work_id'";
      $this->db_run_query( $statement );
    }
  }
  #-----------------------------------------------------

  function is_integer_checkbox_field( $column_name ) {  # If the field was not found in Post, it was unchecked,
                                                        # so we may need to update the record accordingly.
    switch( $column_name ) {
      case 'date_of_work_std_is_range':
      case 'date_of_work_inferred':
      case 'date_of_work_uncertain':
      case 'date_of_work_approx':
      case 'authors_inferred':
      case 'authors_uncertain':
      case 'addressees_inferred':
      case 'addressees_uncertain':
      case 'origin_inferred':
      case 'origin_uncertain':
      case 'destination_inferred':
      case 'destination_uncertain':
      case 'work_is_translation':
      case 'work_to_be_deleted':
      case 'title_of_work_inferred':
      case 'title_of_work_uncertain':
      case 'title_of_work_unknown':
        return TRUE;

      default:
        return FALSE;
    }
  }
  #-----------------------------------------------------

  function get_core_work_update_statement() {
    if( ! $this->work_id ) die( 'No work ID provided.' ); # should have set work ID earlier

    $this->reading_parms_for_update = TRUE;  # used in 'validate parm'
    $detect_changes = '';
    $fieldcount = 0;

    $statement = 'update ' . $this->proj_work_tablename() . ' set ';
    $column_list = DBEntity::db_list_columns( $this->proj_work_tablename());
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );

      $skip_it = FALSE;
      switch( $column_name ) {
        case 'work_id':
        case 'iwork_id':
        case 'description':
        case 'creation_timestamp':
        case 'creation_user':
        case 'change_timestamp':
        case 'change_user':

          $skip_it = TRUE;
          break;

        default:
          if( ! $this->field_is_on_current_tab( $column_name )) $skip_it = TRUE;
          break;
      }
      if( $skip_it ) continue;
      $fieldcount++;

      $blank_field_out = FALSE;   # Normally, don't blank out values not found in Post. The exceptions are checkboxes
                                  # on the form, AND those fields that disappear when 'date range' is unchecked.

      if( $this->parm_found_in_post( $column_name ))  {
        $this->$column_name = $this->read_post_parm( $column_name );

        switch( $column_name ) {

          case 'date_of_work_std_year':
          case 'date_of_work_std_month':
          case 'date_of_work_std_day':
          case 'date_of_work2_std_year':
          case 'date_of_work2_std_month':
          case 'date_of_work2_std_day':
            if( $this->$column_name == 0 ) $this->$column_name = '';
            break;

          case 'accession_code':
            if( strlen( $this->$column_name ) > MAX_SIZE_ACCESSION_CODE )
              $this->$column_name = substr( $this->$column_name, 0, MAX_SIZE_ACCESSION_CODE );
            break;

          default:
            break;
        }
      }

      else { # The field did not appear on the calling form.
        switch( $column_name ) {

          case 'date_of_work2_std_year':
          case 'date_of_work2_std_month':
          case 'date_of_work2_std_day':
            $blank_field_out = TRUE;
            break;

          default:
            if( $this->is_integer_checkbox_field( $column_name )) 
              $blank_field_out = TRUE;
            break;
        }
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
    $statement .= " and work_id = '$this->work_id'";

    if( ! $fieldcount ) $statement = NULL; # no relevant fields on current tab

    return $statement;
  }
  #-----------------------------------------------------

  function get_core_work_insert_statement() {

    $this->reading_parms_for_update = TRUE;  # used in 'validate parm'

    $col_clause = '';
    $val_clause = '';

    $statement = "select nextval( '" . $this->proj_id_seq_name( $this->proj_work_tablename()) . "'::regclass )";
    $this->iwork_id = $this->db_select_one_value( $statement );

    $function_name = $this->get_system_prefix() . '_common_make_text_id';
    $statement = "select $function_name( '" . $this->proj_work_tablename() . "', "
                                            . "'iwork_id', "
                                            . "$this->iwork_id )";
    $this->work_id = $this->db_select_one_value( $statement ); 

    $this->write_post_parm( 'iwork_id', $this->iwork_id );

    $column_list = DBEntity::db_list_columns( $this->proj_work_tablename());
    $first_column = TRUE;

    foreach( $column_list as $crow ) {
      extract( $crow, EXTR_OVERWRITE );

      $skip_it = FALSE;
      switch( $column_name ) {
        case 'description':
        case 'creation_timestamp':
        case 'creation_user':
        case 'change_timestamp':
        case 'change_user':

          $skip_it = TRUE;
          break;

        case 'iwork_id':
        case 'work_id':
        case 'accession_code':
          break;

        default:
          if( ! $this->field_is_on_current_tab( $column_name )) $skip_it = TRUE;
          break;
      }
      if( $skip_it ) continue;

      switch( $column_name ) {
        case 'iwork_id':
        case 'work_id':
          break;

        case 'accession_code':
          $this->$column_name = $this->read_post_parm( $column_name );
          if( $this->$column_name == '' ) 
            $this->$column_name = $this->person_name . ' ' . $this->get_datetime_now_in_words();
          if( strlen( $this->$column_name ) > MAX_SIZE_ACCESSION_CODE )
            $this->$column_name = substr( $this->$column_name, 0, MAX_SIZE_ACCESSION_CODE );
          break;

        default:
          if( ! $this->parm_found_in_post( $column_name )) 
            $skip_it = TRUE;
          else
            $this->$column_name = $this->read_post_parm( $column_name );
          break;
      }

      if( $skip_it ) continue;

      if( ! $first_column ) {
        $col_list .= ', ';
        $val_list .= ', ';
      }
      $col_list .= $column_name;

      $value = $this->$column_name;

      if( $is_numeric ) {
        if( $value == '' ) $value = 'null';
        $val_list .= "$value";
      }

      elseif( $is_date ) {
        if( $value == '' ) {
          $value = 'null';
          $val_list .= "$value";
        }
        else
          $val_list .= "'$value'::date";
      }

      else
        $val_list .= "'" . $this->escape( $value ) . "'";

      $first_column = FALSE;
    }

    $statement = 'insert into ' . $this->proj_work_tablename() . " ( $col_list ) values ( $val_list )";
    return $statement;
  }
  #-----------------------------------------------------

  function set_fields_and_functions( $tab ) {

    $fields = array();
    $funcs = array();

    $fields[] = 'work_to_be_deleted';
    $fields[] = 'original_catalogue';
    $fields[] = 'edit_status';  # at the moment the 'edit status field' method is just a stub (10-jun-2011)

    switch( $tab ) {

      case 'correspondents_tab' :
        $fields[] = 'authors_as_marked';
        $fields[] = 'addressees_as_marked';
        $fields[] = 'authors_inferred';
        $fields[] = 'authors_uncertain';
        $fields[] = 'addressees_inferred';
        $fields[] = 'addressees_uncertain';

        $funcs[] = 'save_author_sender';
        $funcs[] = 'save_addressee';
        $funcs[] = 'save_earlier_work_answered_by_this';
        $funcs[] = 'save_later_work_answering_this';
          $funcs[] = 'save_matching_work';

        break;

      case 'dates_tab'          :
        $fields[] = 'date_of_work_as_marked';
        $fields[] = 'original_calendar';
        $fields[] = 'date_of_work_std';
        $fields[] = 'date_of_work_std_gregorian';
        $fields[] = 'date_of_work_std_year';
        $fields[] = 'date_of_work_std_month';
        $fields[] = 'date_of_work_std_day';
        $fields[] = 'date_of_work2_std_year';
        $fields[] = 'date_of_work2_std_month';
        $fields[] = 'date_of_work2_std_day';
        $fields[] = 'date_of_work_std_is_range';
        $fields[] = 'date_of_work_inferred';
        $fields[] = 'date_of_work_uncertain';
        $fields[] = 'date_of_work_approx';
        break;

      case 'places_tab'         :
        $fields[] = 'destination_as_marked';
        $fields[] = 'origin_as_marked';
        $fields[] = 'destination_inferred';
        $fields[] = 'destination_uncertain';
        $fields[] = 'origin_inferred';
        $fields[] = 'origin_uncertain';

        $funcs[] = 'save_origin';
        $funcs[] = 'save_destination';
        break;

      case 'other_tab'          :
        $fields[] = 'abstract';
        $fields[] = 'keywords';
        $fields[] = 'work_is_translation';
        $fields[] = 'incipit';
        $fields[] = 'explicit';
        $fields[] = 'ps';
        $fields[] = 'accession_code';
        $fields[] = 'work_to_be_deleted';
        $fields[] = 'editors_notes';
        #$fields[] = 'relevant_to_cofk';

        $funcs[] = 'save_people_mentioned';
        $funcs[] = 'save_places_mentioned';
        $funcs[] = 'save_works_mentioned';
        if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
          $funcs[] = 'save_languages';
          $funcs[] = 'save_subjects';
        }
        break;

      case 'manifestations_tab' :
        break;

      case 'related_tab'        :
        break;

      case 'overview_tab'       :  # no fields for update
      default:
        break;
    }

    $this->fields_on_current_tab = $fields;
    $this->save_functions_for_current_tab = $funcs;
  }
  #-----------------------------------------------------

  function field_is_on_current_tab( $fieldname ) {  # The main editing interface has separate tabs

    if( in_array( $fieldname, $this->fields_on_current_tab ))
      return TRUE;
    else
      return FALSE;
  }
  #-----------------------------------------------------

  function go_to_image_upload_page() {

    $img = new Image( $this->db_connection );
    $img->image_upload_form();
  }
  #-----------------------------------------------------

  function editors_notes_field() {

    HTML::new_paragraph();
    HTML::div_start( 'class="workfield"' );

    echo '<style type="text/css">'                           . NEWLINE;
    echo '  textarea#editors_notes { vertical-align: top; }' . NEWLINE;
    echo '</style>'                                          . NEWLINE;

    $this->proj_textarea( 'editors_notes', FLD_SIZE_NOTES_ON_WORK_ROWS, FLD_SIZE_NOTES_ON_WORK_COLS, 
                          $value = $this->editors_notes, $label = "Editors' notes" );

    HTML::div_end();
    HTML::new_paragraph();
    $this->extra_save_button( 'editors_notes' );
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'selected_tab':
        return $this->is_alphanumeric_or_blank( $this->parm_value,
                                                $allow_underscores = TRUE );

      case $this->proj_new_id_fieldname_from_fieldset_name( FIELDSET_ORIGIN ):
      case $this->proj_new_id_fieldname_from_fieldset_name( FIELDSET_DESTINATION ):
        return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
