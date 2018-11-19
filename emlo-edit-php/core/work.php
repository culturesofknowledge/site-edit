<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


class Work extends Project {

  #----------------------------------------------------------------------------------

  function Work( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $this->popup_person = new Popup_Person( $this->db_connection );
    $this->rel_obj = new Relationship( $this->db_connection );
    $this->date_entity = new Date_Entity( $this->db_connection );

    $catg_obj = new Catalogue( $this->db_connection );
    $this->catg_list = $catg_obj->get_lookup_list();
  }

  #----------------------------------------------------------------------------------

  function set_work( $iwork_id = NULL ) {

    $this->clear();

    if( ! $iwork_id ) $iwork_id = $this->read_post_parm( 'iwork_id' );
    if( ! $iwork_id ) $iwork_id = $this->read_get_parm( 'iwork_id' );
    if( ! $iwork_id ) return NULL;

    $this->opening_method = $this->read_post_parm( 'opening_method' );
    if( ! $this->opening_method ) 
      $this->opening_method = $this->read_get_parm( 'opening_method' );

    $statement = 'select * from ' . $this->get_collection_setting( 'work' )
               . " where iwork_id = $iwork_id";
    $this->db_select_into_properties( $statement );
    if( ! $this->work_id ) die( 'Invalid work ID ' . $iwork_id );
    $this->iwork_id = $iwork_id;

    $this->this_on_left = $this->proj_get_righthand_side_of_rels( $left_table_name = $this->proj_work_tablename(), 
                                                                  $left_id_value = $this->work_id );

    $this->this_on_right = $this->proj_get_lefthand_side_of_rels( $right_table_name = $this->proj_work_tablename(), 
                                                                  $right_id_value = $this->work_id );

    return $this->iwork_id;
  }
  #----------------------------------------------------------------------------------

  function clear() { # override parent method

    $keep_catg_list = $this->catg_list;
    parent::clear();
    $this->catg_list = $keep_catg_list;
  }
  #----------------------------------------------------------------------------------

  function set_work_by_text_id( $work_id = NULL ) {

    $this->clear();
    if( ! $work_id ) $work_id = $this->read_post_parm( 'work_id' );
    if( ! $work_id ) $work_id = $this->read_get_parm( 'work_id' );
    if( ! $work_id ) return NULL;

    $statement = 'select iwork_id from ' . $this->proj_work_tablename()
               . " where work_id = '$work_id'";
    $iwork_id = $this->db_select_one_value( $statement );

    return $this->set_work( $iwork_id );
  }
  #----------------------------------------------------------------------------------

  function is_expanded_view() {

    switch( $this->menu_method_name ) {
      case 'alternative_db_search':
      case 'alternative_work_search_results':
      case 'agent_mentioned_in_works_search_results':
        return TRUE;

      default:
        return FALSE;
    }
  }
  #-----------------------------------------------------

  function get_search_table() {

    if( $this->is_expanded_view())
      return $this->get_collection_setting( 'work_view' );
    else
      return $this->get_collection_setting( 'compact_work_view' );
  }
  #-----------------------------------------------------

  function get_results_method() {

    if( $this->menu_method_name == 'person_works_search_results'
    ||  $this->menu_method_name == 'location_works_search_results'
    ||  $this->menu_method_name == 'agent_mentioned_in_works_search_results' ) 
      return $this->menu_method_name;

    if( $this->is_expanded_view())
      return 'alternative_work_search_results';
    else
      return 'work_search_results';
  }
  #-----------------------------------------------------

  function get_other_results_method() {

    if( $this->is_expanded_view())
      return 'work_search_results';
    else
      return 'alternative_work_search_results';
  }
  #-----------------------------------------------------

  function get_search_method() {

    if( $this->is_expanded_view())
      return 'alternative_db_search';
    else
      return 'db_search';
  }
  #-----------------------------------------------------

  function get_other_search_method() {

    if( $this->is_expanded_view())
      return 'db_search';
    else
      return 'alternative_db_search';
  }
  #-----------------------------------------------------

  function set_default_simplified_search() {

    if( ! $this->parm_found_in_post( 'simplified_search' ) 
    &&  ! $this->parm_found_in_get(  'simplified_search' )) {

      #if( ! $this->is_expanded_view())
        $this->write_post_parm( 'simplified_search', 'Y' );  # default to simplified search
    }
  }
  #-----------------------------------------------------
  
  function set_work_search_parms() {  # Only make this database available for 'logged in' search 

    $this->db_remember_presentation_style(); # If a saved presentation style is found, it is written to POST.
                                             # (On very first login ever, defaults will be used.)

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->write_post_parm( 'order_by', $this->get_default_order_by_col() );

    $this->entries_per_page = $this->read_post_parm( 'entries_per_page' );
    if( ! $this->entries_per_page ) $this->write_post_parm( 'entries_per_page', 100 );

    $this->from_table = $this->get_search_table();

    $this->results_method = $this->get_results_method();

    $this->set_default_simplified_search();
  }
  #-----------------------------------------------------

  function get_default_order_by_col() {
    return 'date_of_work_std';
  }
  #-----------------------------------------------------

  function db_set_search_result_parms() {  # Overrides parent method from DBEntity.

    $this->search_method  = $this->get_search_method();
    $this->results_method = $this->get_results_method();
    $this->keycol         = 'iwork_id';

    $this->from_table = $this->get_search_table();

    $this->order_by = $this->read_post_parm( 'order_by' );
    if( ! $this->order_by ) $this->write_post_parm( 'order_by', 'date_of_work_std' );

    $this->force_printing_across_page = TRUE;

    $this->edit_method = NULL;
    $this->edit_tab    = NULL;

    if( $this->proj_edit_mode_enabled()) {
      $this->edit_method = 'edit_work';
      $this->edit_tab    = '_blank';
    }
  }
  #-----------------------------------------------------

  function db_search_results_plugin_1() {

    $this->compactbutton_stylesheet();
  }
  #-----------------------------------------------------

  function compactbutton_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' input.compactbutton{'                                  . NEWLINE; 
    echo '   margin-top: 0px; '                                   . NEWLINE;
    echo '   margin-bottom: 0px; '                                . NEWLINE;
    echo '   margin-left: 0px; '                                  . NEWLINE;
    echo '   margin-right: 0px; '                                 . NEWLINE;
    echo '   padding: 0px; '                                      . NEWLINE;
    echo '   font-size: 7pt; '                                    . NEWLINE;
    echo ' }'                                                     . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------

  function db_list_columns(  $table_or_view = NULL ) {  # overrides parent class

    $rawcols = parent::db_list_columns( $table_or_view );
    if( ! is_array( $rawcols )) return NULL;

    $columns = array();
    foreach( $rawcols as $row ) {
      $column_label = NULL;
      $search_help_text = NULL;
      $search_help_class = NULL;
      extract( $row, EXTR_OVERWRITE );
      $skip_it = FALSE;

      #------------------------
      # Include or omit columns
      #------------------------
      # And set some columns as non-searchable
      switch( $column_name ) {

        case 'work_id':

        case 'senders_for_display':
        case 'senders_searchable':  # use creators not senders
        case 'addressees_for_display':
        case 'creators_for_display':
        case 'places_from_for_display':
        case 'places_to_for_display':
        case 'manifestations_for_display':
        case 'related_works_for_display':
        case 'comments_on_work_for_display':
        case 'record_edited_by_for_display':
        case 'date_of_work_std_for_display':
        case 'summary_for_display':
        case 'raw_edit_status':
        #case 'flags':
        #case 'images':  -- Iva from Comenius project has requested search on images

          $row[ 'searchable' ] = FALSE;
          break;

        default:
          break;
      }

      #------------------------------------------------------------
      # Some columns should appear only in 'order by' dropdown list 
      # (and in 'order by' description on results page).
      #------------------------------------------------------------
      if( ! $this->getting_order_by_cols && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {

          case 'sender_date_recipient':
          case 'sender_recipient_date':
          case 'recipient_date_sender':
          case 'recipient_sender_date':
          case 'date_sender_recipient':
          case 'date_recipient_sender':

            $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      #-------------------------------------------------------
      # But exclude some columns from 'order by' dropdown list
      #-------------------------------------------------------
      if( $this->getting_order_by_cols ) {  
        switch( $column_name ) {

          case 'work_id':

          case 'sender_or_recipient':
          case 'place_to_or_from':

          case 'senders_for_display':
          case 'senders_searchable':  # use creators not senders
          case 'summary_searchable':
          case 'addressees_for_display':
          case 'creators_for_display':
          case 'places_from_for_display':
          case 'places_to_for_display':
          case 'manifestations_for_display':
          case 'related_works_for_display':
          case 'comments_on_work_for_display':
          case 'record_edited_by_for_display':
          case 'summary_for_display':
          case 'raw_edit_status':

          case 'abstract':
          case 'subjects':
          case 'keywords':
          case 'notes_on_authors':
          case 'general_notes':
          case 'addressed_to':
          case 'incipit':
          case 'explicit':
          case 'ps':
          #case 'flags':
          case 'images':
          case 'people_mentioned':
          case 'incipit_and_explicit':

            $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      #---------------------------------------------
      # Some columns are queryable but not displayed
      #---------------------------------------------
      if( ! $this->entering_selection_criteria && ! $this->reading_selection_criteria ) {
        switch( $column_name ) {

          case 'sender_or_recipient':
          case 'place_to_or_from':

          case 'work_id':

          case 'date_of_work_std_year':
          case 'date_of_work_std_month':
          case 'date_of_work_std_day':

          case 'senders_searchable':
          case 'senders_for_display':  # use creators not senders here
          case 'addressees_searchable':
          case 'creators_searchable':
          case 'places_from_searchable':
          case 'places_to_searchable':
          case 'manifestations_searchable':
          #case 'drawer':
          case 'related_works_searchable':
          case 'related_works_for_display':
          case 'comments_on_work_searchable':
          case 'comments_on_work_for_display':
          case 'record_edited_by_searchable':
          case 'record_edited_by_for_display':
          case 'summary_searchable':
          case 'keywords':
          case 'ps':
          case 'incipit':
          case 'explicit':
          case 'addressed_to':
          case 'date_of_work_as_marked':   # bundle it in with Date for Ordering
          case 'people_mentioned':         # bundle it in with abstract
          case 'original_notes':           # bundle it in with abstract
          case 'general_notes':            # bundle it in with abstract
          case 'notes_on_authors':         # bundle it in with author name
          case 'origin_as_marked':         # bundle it in with standardised place name
          case 'destination_as_marked':    # bundle it in with standardised place name
          case 'language_of_work':
          case 'manifestation_type':
          case 'relevant_to_cofk':   # bundle it in with description or date
          case 'original_catalogue': # bundle it in with Last Edit
          case 'accession_code':     # bundle it in with Last Edit
          case 'work_to_be_deleted': # bundle it in with Flags
          case 'edit_status':        # bundle it in with Flags
          case 'raw_edit_status':
          case 'change_user':
          case 'manif_count':        # bundle it in with manifestation details
            $skip_it = TRUE;

            if( $this->get_system_prefix() == IMPACT_SYS_PREFIX && $column_name == 'people_mentioned' )
              $skip_it = FALSE;  # IMPAcT *does* display a separate column for people mentioned.
            break;

          case 'description':
            if( $this->is_expanded_view()) $skip_it = TRUE;  # expanded view has info in separate columns
            break;

          case 'abstract':
            if( ! $this->is_expanded_view()) $skip_it = TRUE;
            break;

          case 'subjects':
            if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) {
              if( ! $this->is_expanded_view()) $skip_it = TRUE;
            }
            break;

          default:
            break;
        }
      }

      #---------------------------------------------
      # Some columns are displayed but not queryable 
      #---------------------------------------------
      else if( $this->entering_selection_criteria || $this->reading_selection_criteria ) {
        switch( $column_name ) {

          case 'senders_for_display':
          case 'senders_searchable':  # use creators field not senders field
          case 'addressees_for_display':
          case 'creators_for_display':
          case 'places_from_for_display':
          case 'places_to_for_display':
          case 'manifestations_for_display':
          case 'related_works_for_display':
          case 'comments_on_work_for_display':
          case 'summary_for_display':
          #case 'images':  -- Iva from Comenius project has requested search on images

            $skip_it = TRUE;
            break;

          # Reduce number of queryable columns in 'compact' view
          case 'received':
          case 'received2':

            if( ! $this->is_expanded_view()) $skip_it = TRUE;
            break;

          default:
            break;
        }
      }

      if( $skip_it ) continue;

      #------------------
      # Set column labels
      #------------------
      switch( $column_name ) {

        case 'iwork_id':
          $column_label = 'Work ID';
          $search_help_text = 'The unique ID for the record within the current CofK database.';
          break;

        case 'date_of_work_std':
          $column_label = 'Date for ordering (in original calendar)';
          if( ! $this->entering_selection_criteria ) $column_label = 'Date for ordering';
          break;

        case 'date_of_work_std_gregorian':
          $column_label = 'Date in Gregorian calendar';
          break;

        case 'date_of_work_std_year':
        case 'date_of_work_std_month':
        case 'date_of_work_std_day':
          $column_label = ucfirst( str_replace( 'date_of_work_std_', '', $column_name ));
          break;

        case 'sender_or_recipient':
          $column_label = 'Sender or recipient';
          break;

        case 'addressees_for_display':
        case 'addressees_searchable':
          $column_label = 'Addressee';
          break;

        case 'creators_for_display':
        case 'creators_searchable':
          $column_label = 'Author/Sender';
          break;

        case 'places_from_for_display':
        case 'places_from_searchable':
          $column_label = 'Origin';
          if( $this->entering_selection_criteria ) 
            $column_label = 'Origin (standardised)';
          else
            $column_label = 'Origin';
          break;

        case 'places_to_for_display':
        case 'places_to_searchable':
          if( $this->entering_selection_criteria ) 
            $column_label = 'Destination (standardised)';
          else
            $column_label = 'Destination';
          break;

        case 'place_to_or_from':
          $column_label = 'Origin or destination';
          break;

        case 'manifestations_for_display':
        case 'manifestations_searchable':
          $column_label = 'Manifestations';
          break;

        case 'related_works_for_display':
        case 'related_works_searchable':
          $column_label = 'Related works';
          break;

        case 'comments_on_work_for_display':
        case 'comments_on_work_searchable':
          $column_label = 'Comments on work';
          break;

        case 'ps':
          $column_label = 'PS';
          break;

        case 'flags':
          if( $this->entering_selection_criteria || $this->reading_selection_criteria 
          || $this->printable_output || $this->record_layout == 'down_page' ) 
            $column_label = 'Flags';
          else
            $column_label = '';

          $search_help_text = "May contain the words 'Date of work', 'Author/sender', 'Addressee', 'Origin' and/or"
                            . " 'Destination', followed by 'INFERRED', 'UNCERTAIN' or, in the case of date,"
                           . " 'APPROXIMATE'. E.g. <em>Author/sender INFERRED</em>.";
          break;

        case 'record_edited_by_searchable':
        case 'record_edited_by_for_display':
          $column_label = 'Source of record';
          break;

        case 'summary_searchable':
        case 'summary_for_display':
          $column_label = 'Summary';
          break;

        case 'abstract':
          $column_label = $this->db_get_default_column_label( $column_name );
          if( ! $this->entering_selection_criteria ) $column_label = 'Other details';
          break;

        case 'keywords':
          $column_label = $this->db_get_default_column_label( $column_name );
          if( $this->entering_selection_criteria || $this->reading_selection_criteria ) 
            $column_label = 'Keywords, works and places mentioned';
          break;

        default:
          if( ! $column_label ) # may have been pre-set by CofK Entity
            $column_label = $this->db_get_default_column_label( $column_name );
          break;
      }
      $row[ 'column_label' ] = $column_label;


      #----------------
      # Set search help
      #----------------
      switch( $column_name ) {

        case 'description':
        case 'edit_status':
          $search_help_text = $this->get_search_help_text( $column_name );
          break;

        case 'sender_or_recipient':
          $search_help_text = 'Enter part or all of the name of either the author/sender or the addressee '
                            . ' to find all letters either to or from a particular person.';
          break;

        case 'date_of_work_as_marked':
          $search_help_text = 'This field could contain the actual words marked within the letter, such as '
                            . " 'ipsis Kalendis Decembribus C I. I. CCVI', or a modern researcher's notation "
                            . " such as 'n.d.'";
          break;

        case 'date_of_work_std':
          $search_help_text = 'To find works from a specified period, '
                            . "enter dates 'from' and 'to' as YYYY or DD/MM/YYYY. "
                            . ' Either end of the date-range may be left blank, e.g.<ul>'
                            . "<li>'From 1633' to find works dated from 1st January 1633 onwards</li>" 
                            . "<li>'To 1634' to find works dated up to 31st December 1634</li></ul>";
          break;


        case 'date_of_work_std_year':
          $search_help_text = 'Year in which work was created. '
                            . " (Use 'is blank' option in Advanced Search to find works without year.)";
          break;

        case 'date_of_work_std_month':
          $search_help_text = 'Month (1-12) in which work was created. '
                            . " (Use 'is blank' option to find works without month.)";
          break;

        case 'date_of_work_std_day':
          $search_help_text = 'Day on which work was created. '
                            . " (Use 'is blank' option to find works without day.)";
          break;

        case 'relevant_to_cofk':
          $search_help_text = 'Yes, No or ?';
          break;

        case 'place_to_or_from':
          $search_help_text = 'The place to or from which a letter was sent, in standard modern format.';
          break;

        case 'places_from_searchable':
          $search_help_text = 'The place from which a letter was sent, in standard modern format.';
          break;

        case 'places_to_searchable':
          $search_help_text = 'The place to which a letter was sent, in standard modern format.';
          break;

        case 'manifestations_searchable':
          if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
            $search_help_class = IMPACT_SYS_PREFIX . '_manifestation';
          else
            $search_help_class = 'manifestation';
          break;

        case 'manifestation_type':
          $search_help_class = 'document_type';
          break;

        #case 'drawer':
        #  $search_help_class = 'drawer';
        #  break;

        case 'accession_code':
          $search_help_text = 'Typically contains the name of the researcher who contributed the data.';
          break;

        case 'original_catalogue':
          $search_help_class = 'catalogue';
          break;

        case 'work_to_be_deleted':
          $search_help_text = "Yes or No. If 'Yes', the record is marked for deletion.";
          break;

        case 'people_mentioned':
          $search_help_text = 'This field contains a list of people mentioned within a work.';
          break;

        case 'keywords':
          $search_help_text = 'This field contains keywords, plus a list of places and works'
                            . ' mentioned within a work.';
          break;

        case 'abstract':
          $search_help_text = 'Contains a summary of the contents of the work';
          break;

        case 'subjects':
          $search_help_class = 'subject';
          break;

        case 'images':
          $search_help_text = 'Contains filenames of any scanned images of manifestations.';
          break;

        default:
          break;
      }
      $row[ 'search_help_text' ] = $search_help_text;
      $row[ 'search_help_class' ] = $search_help_class;

      $columns[] = $row;
    }

    return $columns;
  }
  #-----------------------------------------------------

  function db_get_possible_order_by_cols( $columns ) {

    $this->getting_order_by_cols = TRUE;
    $columns = $this->db_list_columns( $this->from_table ); # refresh list of included and omitted columns
    $this->getting_order_by_cols = FALSE;

    # Sort the list in alphabetic order by column label
    $labels = array();
    foreach( $columns as $keynum => $coldata ) {
      $labels[ $keynum ] = $coldata[ 'column_label' ];
    }
    asort( $labels );

    $sorted_columns = array();
    foreach( $labels as $keynum => $label ) {
      $sorted_columns[] = $columns[ $keynum ];
    }

    return parent::db_get_possible_order_by_cols( $sorted_columns );
  }
  #-----------------------------------------------------

  function db_choose_order_by_col( $possible_order_by_cols = NULL, $default_order_by_col = '1' ) {

    echo 'Your choice of presentation options will remain in force until you choose a new set of options.';
    HTML::new_paragraph();

    parent::db_choose_order_by_col( $possible_order_by_cols, $default_order_by_col );
  }
  #-----------------------------------------------------
  function db_choose_asc_desc() {
    HTML::span_start( 'class="widespaceonleft"' );
    parent::db_choose_asc_desc();
    HTML::span_end( 'widespaceonleft' );
  }
  #-----------------------------------------------------

  function db_search( $table_or_view = NULL, $class_name = NULL, $method_name = NULL ) {

    $this->set_work_search_parms();
    parent::db_search( $this->from_table, $this->app_get_class( $this ), $this->results_method );
  }
  #-----------------------------------------------------

  function work_search_results() {
    $this->db_search_results();
  }
  #-----------------------------------------------------

  function alternative_db_search() {
    $this->db_search( NULL ); # parameters will be set depending on value of $this->menu_method_name
  }
  #-----------------------------------------------------

  function alternative_work_search_results() {
    $this->work_search_results(); # parameters will be set depending on value of $this->menu_method_name
  }
  #-----------------------------------------------------
  function db_write_custom_page_button() {  # allow swap between main view and alternative view

    if( $this->is_expanded_view()) { # don't offer Compact View if querying on column that doesn't exist in that view
      if( ! $this->can_do_same_query_in_compact_view()) {
        return;
      }
    }

    $other_method = $this->get_other_results_method();
    if( $this->is_expanded_view())
      $other_method_name = 'Compact view';
    else
      $other_method_name = 'Expanded view';

    HTML::form_start( $this->app_get_class( $this ), $other_method, NULL, $form_target = '_self' );

    $page_required = $this->db_page_required;  # current page or page 1 if we only have one page
    if( ! $page_required ) $page_required = 1;
    $this->db_write_pagination_fields( $page_required );

    HTML::submit_button( 'change_view_button', $other_method_name, $tabindex=1, ' class="pagelist" ' );

    HTML::form_end();
  }
  #-----------------------------------------------------
  function can_do_same_query_in_compact_view() {

    switch( $this->menu_method_name ) {

      case 'agent_mentioned_in_works_search_results':
        return FALSE;

      default:
        break;
    }
    return TRUE;
  }
  #-----------------------------------------------------

  function db_explain_how_to_query() {

	  HTML::span_start( 'style="cursor:pointer;text-decoration:underline" onclick="(help_text.style.display===\'block\') ? help_text.style.display=\'none\' : help_text.style.display=\'block\'"');
	  echo 'Show help';
	  HTML::span_end();
	  HTML::new_paragraph();

	  HTML::div_start('id="help_text" style="display:none;width:1000px;margin-left:20px;"');

	  echo "
	  	<style>#help_text li { line-height: 1.5;}</style>
	  ";

    echo 'Enter selection in one or more fields and click the Search button or press the Return key.'
         . ' Please note:';
    HTML::new_paragraph();

    HTML::ulist_start();

    HTML::listitem_start();
    echo 'Most of the fields in this form are text fields: you make your selection by entering a word or phrase,';
    echo ' or part of a word, to be found anywhere within the relevant piece of text.';

	  HTML::ulist_start();
	  HTML::listitem_start();

    echo ' For example, if you entered ';
    HTML::bold_start();
    echo 'Bister';
    HTML::bold_end();
    echo " in the 'Sender or Recipient' field, you would find all works to or from BISTERFELD, JOHANN HEINRICH "; 
    echo ' and BISTERFELD, JOHN';
    HTML::listitem_end();

	  HTML::listitem_end();
	  HTML::ulist_end();

    HTML::listitem_start();
    echo 'You do not have to match the case of text fields, e.g. ';
    HTML::bold_start();
    echo 'comenius';
    HTML::bold_end();
    echo ' is equivalent to ';
    HTML::bold_start();
    echo 'Comenius';
    HTML::bold_end();
    echo '.';
    HTML::listitem_end();


    HTML::listitem_start();
    echo ' You can use the wildcard ';
    HTML::bold_start();
    echo '%';
    HTML::bold_end();
    echo ' (percent sign) to represent any number of characters.';

	  HTML::ulist_start();
	  HTML::listitem_start();

    echo ' For example, if you entered ';
    HTML::bold_start() . 'pelham%william' . HTML::bold_end();
    echo " in the 'Sender or Recipient' field, you would find works to or from PELHAM, SIR WILLIAM ";
    echo ' as well as PELHAM, WILLIAM.';

	  HTML::listitem_end();
	  HTML::ulist_end();

    HTML::listitem_end();

    HTML::listitem( 'Fields marked with an asterisk are non-text fields (dates or numbers).'
         . ' To find records '
         . " with dates within a certain range, enter the start and/or the end of the period of interest"
         . ' in the From/To fields.'
         . ' To find records containing a certain number (work ID), enter the exact number you are interested in.'
         );

    HTML::ulist_end();

    HTML::new_paragraph();

	  HTML::div_end(NULL,"help_text");
  }
  #-----------------------------------------------------

  function db_get_selection_cols( $columns ) {  # strip out unwanted columns

    $selection_cols = '*';

    # Get full set of columns, not just displayed ones
    $columns = parent::db_list_columns( $this->from_table );

    if( is_array( $columns )) {
      $selection_cols = '';

      foreach( $columns as $column_details ) {
        $column_name = $column_details[ 'column_name' ];

        switch( $column_name ) {

          case 'sender_or_recipient':
          case 'place_to_or_from':
            break;

          case 'creators_for_display':
          case 'addressees_for_display':
          case 'places_from_for_display':
          case 'places_to_for_display':
          case 'manifestations_for_display':
          case 'related_works_for_display':
          case 'comments_on_work_for_display':
          case 'record_edited_by_for_display':
          case 'summary_for_display':
          case 'work_id':
            if( $this->csv_output ) break;

          default:
            if( $selection_cols ) $selection_cols .= ', ';
            $selection_cols .= $column_name;
            break;
        }
      }
    }
    return $selection_cols;
  }
  #-----------------------------------------------------

  function db_get_default_column_label( $column_name = NULL ) {

    switch( $column_name ) {

      case 'original_calendar':
        return 'Original calendar';

      case 'date_of_work_std':
      case 'date_of_work_std_for_display':
        if( $this->in_overview ) {
          return 'Date for ordering (in original calendar)';
        }
        return 'Date in original calendar';

      case 'date_of_work_std_gregorian':
        return 'Date for ordering (Gregorian)';

      case 'date_of_work_std_is_range':
        return 'Date of work is range';

      case 'senders_searchable':
      case 'addressees_searchable':
      case 'creators_searchable':
      case 'places_from_searchable':
      case 'places_to_searchable':
      case 'manifestations_searchable':
      case 'related_works_searchable':
      case 'comments_on_work_searchable':
      case 'summary_searchable':
        return parent::db_get_default_column_label( str_replace( '_searchable', '', $column_name ));

      case 'authors_as_marked':
        return 'Authors/senders as marked';

      case 'authors_inferred':
        return 'Authors/senders inferred';

      case 'authors_uncertain':
        return 'Authors/senders uncertain';

      case 'record_edited_by_searchable':
      case 'accession_code':
        return 'Source of record';

      case 'work_to_be_deleted':
        return 'Record to be deleted';

      case 'edit_status':
        return 'Status of record';

      case 'iwork_id':
      case 'work_id':
        $column_label = 'Work ID';
        if( $column_name == 'work_id' && $this->in_overview ) 
          $column_label = 'Alternative work ID (for internal system use)';
        return $column_label;

      case 'date_of_work_std_year':
      case 'date_of_work_std_month':
      case 'date_of_work_std_day':
        $column_label = ucfirst( str_replace( 'date_of_work_std_', '', $column_name ));
        if( $this->in_overview ) {
          if( $this->date_of_work_std_is_range ) {
            $column_label = 'From ' . strtolower( $column_label );
          }
        }
        return $column_label;

      case 'date_of_work2_std_year':
      case 'date_of_work2_std_month':
      case 'date_of_work2_std_day':
        $column_label = ucfirst( str_replace( 'date_of_work2_std_', '', $column_name ));
        if( $this->in_overview && $this->date_of_work_std_is_range ) {
          $column_label = 'To ' . strtolower( $column_label );
        }
        else
          $column_label .= ' 2';
        return $column_label;

      case 'manifestation_type':
        return 'Document type';

      case 'editors_notes':
        return "Editor's notes";

      case 'relevant_to_cofk':
        return 'Relevant to CofK';
   
      case 'notes_on_authors':
        return 'Notes on authors/senders';
   
      case 'creators_for_display':
        return 'Authors (standard format)';

      case 'senders':
        return 'Senders (standard format)';

      case 'signatories':
        return 'Signatories (standard format)';

      case 'addressees_for_display':
        return 'Recipients (standard format)';

      case 'intended_recipients':
        return 'Intended recipients (standard format)';

      case 'places_from_for_display':
        return 'Place from which sent (standard format)';

      case 'places_to_for_display':
        return 'Place to which sent (standard format)';

      case 'ps':
        return 'Postscript';

      case 'explicit':
        return 'Explicit';

      case 'creation_timestamp':
        return 'Creation date/time';

      case 'creation_user':
        return 'Created by user';

      case 'change_timestamp':
        return 'Date/time of last change';

      case 'change_user':
        return 'Changed by user';

      case 'manif_count':
        return 'Number of manifestations';

      default:
        return parent::db_get_default_column_label( $column_name );
    }
  }
  #-----------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    $column_value = parent::db_browse_reformat_data( $column_name, $column_value );

    # ---- Display author and addressee aliases

    if(( $column_name == 'creators_for_display' || $column_name == 'addressees_for_display' )
    && ! $this->printable_output && ! $this->csv_output ) {

      if( $column_name == 'creators_for_display' )
        $moreinfo = $this->get_author_aliases();
      else
        $moreinfo = $this->get_addressee_aliases();

      if( $moreinfo ) {
        $moreinfo = str_replace( '"', "'", $moreinfo );
        $moreinfo = str_replace( NEWLINE, ' -- ', $moreinfo );

        $script = ' onclick="alert(' . "'" . $this->escape( $moreinfo ) . "')" . '" ';
        $style = ' class="compactbutton" ';
        $title = ' title="Show aliases" ';

        HTML::button( $button_name = $column_name . '_moreinfo_button_' . $this->current_row_of_data[ 'iwork_id' ],
                      $value = '+', 
                      $tabindex = 1,
                      $other_parms = $title . $script . $style );
      }
    }

    # ---- Display flags

    if( $column_name == 'flags' && $this->current_row_of_data[ 'work_to_be_deleted' ] == 'Yes' ) {
      if( ! $this->printable_output && ! $this->csv_output && $this->record_layout != 'down_page' ) {
        $this->proj_image_msg_button( $image_file = PROJ_MARKED_FOR_DELETION_FILE, 
                                      $msg = 'Record is marked for deletion!', 
                                      $button_name = 'deletion_flag_' . $this->current_row_of_data[ 'iwork_id' ] ); 
        echo ' ';
      }
      else
        $column_value = '*** N.B. Record is marked for deletion! *** ' . NEWLINE . $column_value;
    }

    if( $column_name == 'flags' && $this->current_row_of_data[ 'edit_status' ] > '' ) {
      $edit_status = $this->current_row_of_data[ 'edit_status' ];
      $raw_edit_status = $this->current_row_of_data[ 'raw_edit_status' ];
      $status_msg = 'Status of record: ' . $edit_status; 

      if( ! $this->printable_output && ! $this->csv_output && $this->record_layout != 'down_page' ) {
        $status_pic = "$raw_edit_status.png";

        $this->proj_image_msg_button( $image_file = $status_pic, 
                                      $msg = $status_msg,
                                      $button_name = 'edit_status_flag_' . $this->current_row_of_data[ 'iwork_id' ] ); 
        echo ' ';
      }
      else
        $column_value = $edit_status . NEWLINE . $column_value;
    }

    if( $column_name == 'flags' && $column_value > '' ) {
      if( ! $this->printable_output && ! $this->csv_output && $this->record_layout != 'down_page' ) {
        $this->proj_image_msg_button( $image_file = PROJ_FLAGS_EXIST_FILE, 
                                      $msg = $column_value, 
                                      $button_name = 'flag_' . $this->current_row_of_data[ 'iwork_id' ] ); 
        $column_value = '';
      }
    }

    # ---- Display date as marked in with standardised date

    elseif( $column_name == 'date_of_work_std' ) {
      if( ! $this->csv_output ) {
        #---------------------
        # Add 'date as marked'
        #---------------------
        $date_of_work_as_marked = $this->current_row_of_data[ 'date_of_work_as_marked' ];
        if( $this->current_row_of_data[ 'date_of_work_as_marked' ] ) {
          $column_value .= NEWLINE . NEWLINE . 'As marked: ' . $date_of_work_as_marked;
        }
      }
    }

    # -- Display people mentioned, general notes and language(s) in with abstract

    elseif( $column_name == 'abstract' ) {  # add info about people mentioned etc in same cell as abstract

      $column_value = $this->proj_reduce_no_of_blank_lines( $column_value );
      $column_value = str_replace( NEWLINE, ' ', $column_value );

      if( $column_value ) $column_value = 'Abstract: ' . $column_value;

      $language_of_work = $this->current_row_of_data[ 'language_of_work' ];
      if( $language_of_work ) {
        if( $column_value ) $column_value .= NEWLINE;
        $column_value .= 'Language(s): ' . $language_of_work;
      }

      $people_mentioned = $this->current_row_of_data['people_mentioned'];
      if( $people_mentioned ) {
        if( $column_value ) $column_value .= NEWLINE;
        $column_value .= 'People mentioned: ' . $people_mentioned;
      }

      $general_notes = $this->current_row_of_data['general_notes'];
      if( $general_notes ) {
        if( $column_value ) $column_value .= NEWLINE;
        $column_value .= 'Notes: ' . $general_notes;
      }

      $keywords = $this->current_row_of_data['keywords']; # put keywords before abstract
      if( $keywords ) {
        if( ! $this->string_starts_with( $keywords, 'Keywords' )
        &&  ! $this->string_starts_with( $keywords, 'Mention' ))
          $keywords = 'Keywords: ' . $keywords;

        if( $column_value )
          $column_value = $keywords . NEWLINE . $column_value;
        else
          $column_value = $keywords;
      }
    }

    # ---- In expanded view, display author role

    elseif( $column_name == 'creators_for_display'
         || ( $column_name == 'description' && ( $this->printable_output == 'Y' || $this->is_expanded_view() ))) {
      
      $notes_on_authors = $this->current_row_of_data['notes_on_authors'];
      if( $notes_on_authors ) {
        if( $column_value ) $column_value .= NEWLINE . NEWLINE;
        $column_value .= 'Role: ' . $notes_on_authors;
      }

      if ( $column_name == 'description' ) {
        $keywords = $this->current_row_of_data['keywords'];
        $keywords = trim( $keywords );
        if( $keywords ) {
          $keywords = 'Keywords: ' . $keywords;
          $column_value .= NEWLINE . NEWLINE . $keywords;
        }
      }
    }

    # ---- In expanded view, display places as marked with standardised places

    elseif( $column_name == 'places_from_for_display' ) {
      $origin_as_marked = $this->current_row_of_data['origin_as_marked'];
      if( $origin_as_marked && $origin_as_marked != $column_value ) {
        if( $column_value ) $column_value .= NEWLINE . NEWLINE;
        $column_value .= 'As marked: ' . $origin_as_marked;
      }
    }

    elseif( $column_name == 'places_to_for_display' ) {
      $destination_as_marked = $this->current_row_of_data['destination_as_marked'];
      if( $destination_as_marked && $destination_as_marked != $column_value ) {
        if( $column_value ) $column_value .= NEWLINE . NEWLINE;
        $column_value .= 'As marked: ' . $destination_as_marked;
      }
    }

    # ---- In compact view, display subjects, aliases, notes and author role in button on description

    elseif( $column_name == 'description' ) {

      if( ! $this->csv_output ) {
        $keywords = $this->current_row_of_data['keywords'];
        $keywords = trim( $keywords );
        if( $keywords ) {
          $keywords = 'Keywords: ' . $keywords;
          $column_value .= NEWLINE . NEWLINE . $keywords;
        }
      }

      if( ! $this->printable_output && ! $this->csv_output ) {

        $abstract = $this->current_row_of_data['abstract'];
        $abstract = str_replace( '<p>', '', $abstract );
        $abstract = str_replace( '</p>', ' ', $abstract );
        $abstract = trim( $abstract );
        if( $abstract ) $abstract = 'Abstract: ' . $abstract;

        $notes_on_authors = $this->current_row_of_data['notes_on_authors'];
        $notes_on_authors = trim( $notes_on_authors );
        if( $notes_on_authors ) $notes_on_authors = 'Role of author/sender: ' . $notes_on_authors;

        $general_notes = $this->current_row_of_data['general_notes'];
        $general_notes = trim( $general_notes );
        if( $general_notes ) $general_notes = 'Notes: ' . $general_notes;

        $author_aliases = $this->get_author_aliases();
        $addressee_aliases = $this->get_addressee_aliases();

        $subjects = $this->current_row_of_data['subjects'];
        $subjects = trim( $subjects );
        if( $subjects ) $subjects = 'Subject(s): ' . $subjects;

        if( $abstract || $notes_on_authors || $general_notes || $author_aliases || $addressee_aliases || $subjects ) {

          $moreinfo = $notes_on_authors;

          if( $moreinfo && $author_aliases ) $moreinfo .= NEWLINE;
          $moreinfo .= $author_aliases;

          if( $moreinfo && $addressee_aliases ) $moreinfo .= NEWLINE;
          $moreinfo .= $addressee_aliases;

          if( $moreinfo && $subjects ) $moreinfo .= NEWLINE;
          $moreinfo .= $subjects;

          if( $moreinfo && $abstract ) $moreinfo .= NEWLINE;
          $moreinfo .= $abstract;

          if( $moreinfo && $general_notes ) $moreinfo .= NEWLINE;
          $moreinfo .= $general_notes;

          $moreinfo = str_replace( '"', "'", $moreinfo );
          $moreinfo = str_replace( NEWLINE, ' -- ', $moreinfo );
          $moreinfo = str_replace( CARRIAGE_RETURN, '', $moreinfo );

          $script = ' onclick="alert(' . "'" . $this->escape( $moreinfo ) . "')" . '" ';
          $style = ' class="compactbutton" ';
          $title = ' title="Show further details such as abstract or aliases by which correspondents were known" ';

          HTML::button( $button_name = 'work_moreinfo_button_' . $this->current_row_of_data[ 'iwork_id' ],
                        $value = '+', 
                        $tabindex = 1,
                        $other_parms = $title . $script . $style );
        }
      }
    }

    elseif( $column_name == 'related_resources' && ! $this->csv_output ) {
      $column_value = $this->proj_convert_non_html_list( $column_value, $separator = NON_HTML_LIST_SEPARATOR );
    }

    elseif( $column_name == 'iwork_id' && ! $this->csv_output && ! $this->printable_output ) {
      $class_name = $this->app_get_class( $this );
      if( $class_name == 'work' || $class_name == PROJ_COLLECTION_WORK_CLASS ) { # don't risk messing up popups!

        $href = $_SERVER[ 'PHP_SELF' ] . '?iwork_id=' . $column_value;
        $title = 'Full details of record no. ' . $column_value;
        HTML::link( $href, $displayed_text = $column_value, $title, $target = '_blank' ); 

        $column_value = ''; # don't repeat ID number twice
        echo LINEBREAK;
      }
    }

    elseif( $column_name == 'change_timestamp' ) {

      $accession_code = $this->current_row_of_data[ 'accession_code' ];
      $original_catalogue = $this->current_row_of_data[ 'original_catalogue' ];
      if( $accession_code || $original_catalogue ) {
        $column_value .= NEWLINE . 'Source: ' . $accession_code;
      }
      if( $original_catalogue ) {
        $column_value .= ' [' . $original_catalogue . ']';
      }
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function get_author_aliases() {

    $author_aliases = $this->current_row_of_data['creators_searchable'];
    if( $this->string_contains_substring( $author_aliases, 'alias:' )) 
      $author_aliases = 'Further details of author: ' . $author_aliases;
    else
      $author_aliases = '';
    return $author_aliases;
  }
  #-----------------------------------------------------

  function get_addressee_aliases() {

    $addressee_aliases = $this->current_row_of_data['addressees_searchable'];
    if( $this->string_contains_substring( $addressee_aliases, 'alias:' )) 
      $addressee_aliases = 'Further details of addressee: ' . $addressee_aliases;
    else
      $addressee_aliases = '';
    return $addressee_aliases;
  }
  #-----------------------------------------------------

  function edit_work () {  # all add/edit functions are in editable_work.php
                           # or in its child class Selden_Work
  }
  #-----------------------------------------------------

  function add_work () {  # all add/edit functions are in editable_work.php
                          # or in its child class Selden_Work

  }
  #-----------------------------------------------------

  function save_work () {  # all add/edit functions are in editable_work.php
                           # or in its child class Selden_Work
  }
  #-----------------------------------------------------

  function get_ids_of_people_with_role_in_work( $work_id, $role_type = NULL ) {

    if( ! $work_id ) return NULL;

    $people = $this->rel_obj->get_other_side_for_this_on_both_sides( $this_table = $this->proj_work_tablename(), 
                                                                     $this_id = $work_id, 
                                                                     $reltype = $role_type, 
                                                                     $other_table = $this->proj_person_tablename() );
    return $people;
  }
  #-----------------------------------------------------

  function get_names_of_people_with_role_in_work( $work_id, $role_type = NULL ) {

    if( ! $work_id ) return NULL;

    $person_ids = $this->get_ids_of_people_with_role_in_work( $work_id, $role_type );

    $people = array();

    if( is_array( $person_ids )) {
      foreach( $person_ids as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $person_id = $other_id_value;
        $name = $this->popup_person->get_person_desc_from_id( $person_id );
        $people[] = array( 'id' => $person_id, 'name' => $name );
      }
    }

    return $people;
  }
  #-----------------------------------------------------

  function get_creator_desc( $work_id, $html_output = FALSE ) {

    if( ! $work_id ) return NULL;

    $creators = $this->get_names_of_people_with_role_in_work( $work_id, RELTYPE_PERSON_CREATOR_OF_WORK );

    return $this->proj_format_names_for_output( $creators, $html_output );
  }
  #-----------------------------------------------------

  function get_addressee_desc( $work_id, $html_output = FALSE ) {

    if( ! $work_id ) return NULL;

    $addressees = $this->get_names_of_people_with_role_in_work( $work_id, RELTYPE_WORK_ADDRESSED_TO_PERSON );

    return $this->proj_format_names_for_output( $addressees, $html_output );
  }
  #-----------------------------------------------------

  function get_origin_desc( $work_id ) {

    return $this->get_place_desc( $work_id, RELTYPE_WORK_SENT_FROM_PLACE );
  }
  #-----------------------------------------------------

  function get_destination_desc( $work_id ) {

    return $this->get_place_desc( $work_id, RELTYPE_WORK_SENT_TO_PLACE );
  }
  #-----------------------------------------------------

  function get_place_desc( $work_id, $relationship_type = NULL ) {

    if( ! $work_id ) return NULL;
    if( ! $relationship_type ) $relationship_type = RELTYPE_WORK_SENT_FROM_PLACE;

    $statement = 'select l.location_name '
               . ' from ' . $this->get_collection_setting( 'location' ) . ' l, ' 
               . $this->get_collection_setting( 'relationship' ) . ' r '
               . " where left_table_name = '" . $this->get_collection_setting( 'work' ) . "' "
               . " and left_id_value = '" . $this->escape( $work_id ) . "' "
               . " and relationship_type = '" . $relationship_type . "' "
               . " and right_table_name = '" . $this->get_collection_setting( 'location' ) . "' "
               . ' and right_id_value = l.location_id '
               . ' order by r.relationship_id';

    $places = $this->db_select_into_array( $statement );
    if( ! is_array( $places )) return NULL;

    $place_list = '';

    foreach( $places as $place ) {
      $name = $place[ 'location_name' ];
      if( $place_list > '' ) $place_list .= ', ';
      $place_list .= $name;
    }

    return $place_list;
  }
  #-----------------------------------------------------

  function get_work_desc( $work_id ) {

    if( $work_id == $this->work_id && $this->description > '' ) {
      return $this->description;
    }
    else {
      return $this->generate_work_desc( $work_id );
    }
  }
  #-----------------------------------------------------

  function generate_work_desc( $work_id ) {

    if( ! $work_id ) return '[New record]';

    $function_name = $this->proj_database_function_name( 'get_work_desc', $include_collection_code = TRUE );
    $statement = "select * from $function_name( '$work_id' )";
    $desc = $this->db_select_one_value( $statement );
    if( ! $desc ) $desc = 'Invalid work ID';

    return $desc;
  }
  #-----------------------------------------------------

  function proj_get_description_from_id( $entity_id ) {  # Overrides method from CofK Entity
                                                         # Used by popup screens
    return $this->get_work_desc( $entity_id );
  }
  #-----------------------------------------------------

  function refresh_work_desc( $work_id ) {

    if( ! $work_id ) return;

    $desc = $this->generate_work_desc( $work_id );

    $tabs = array( 'work', 'queryable_work' );

    foreach( $tabs as $tab ) {
      $tablename_func = 'proj_' . $tab . '_tablename';

      $statement = 'update ' . $this->$tablename_func() 
                 . " set description = '" . $this->escape( $desc ) . "' "
                 . " where work_id = '$work_id'";

      $this->db_run_query( $statement );
    }
  }
  #-----------------------------------------------------

  function refresh_queryable_authors( $work_id ) {

    if( ! $work_id ) return;
    $searchable_creator = $this->get_creator_desc( $work_id, $html_output = FALSE );
    $creator_for_display = $this->get_creator_desc( $work_id, $html_output = TRUE );

    $statement = 'update ' . $this->proj_queryable_work_tablename() . ' set '
               . " creators_searchable = '" . $this->escape( $searchable_creator ) . "', " 
               . " creators_for_display = '" . $this->escape( $creator_for_display ) . "' " 
               . " where work_id = '$work_id'";

    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function refresh_queryable_addressees( $work_id ) {

    if( ! $work_id ) return;
    $searchable_addressee = $this->get_addressee_desc( $work_id, $html_output = FALSE );
    $addressee_for_display = $this->get_addressee_desc( $work_id, $html_output = TRUE );

    $statement = 'update ' . $this->proj_queryable_work_tablename() . ' set '
               . " addressees_searchable = '" . $this->escape( $searchable_addressee ) . "', " 
               . " addressees_for_display = '" . $this->escape( $addressee_for_display ) . "' " 
               . " where work_id = '$work_id'";

    $this->db_run_query( $statement );
  }
  #-----------------------------------------------------

  function get_search_help_text( $column_name = NULL ) {

    $help = NULL;
    if( ! $column_name ) return $help;

    switch( $column_name ) {

      case 'description':
        $help = "This is in the style 'DD Mon YYYY: Author/Sender (place) to Addressee (place)',"
                . ' e.g. 8 Mar 1693: Bulkeley, Sir Richard (Dunlaven, County Wicklow)'
                . ' to Lister, Martin (Old Palace Yard, Westminster).';
        break;

      case 'edit_status':
        $help = 'Shows whether the record needs further editing.'
              . " May contain the words 'Editing complete' or 'Check with original manuscript',"
              . ' otherwise will be blank.';
        break;

      default:
        break;
    }

    return $help;
  }
  #-----------------------------------------------------

  function agent_mentioned_in_works_search_results() {
    $this->person_works_search_results();
  }
  #-----------------------------------------------------

  function person_works_search_results() {
    $this->person_or_place_works_search_results( $object_type = 'person' );
  }
  #-----------------------------------------------------

  function location_works_search_results() {
    $this->person_or_place_works_search_results( $object_type = 'location' );
  }
  #-----------------------------------------------------

  function person_or_place_works_search_results( $object_type = 'person' ) {

    $keycol_name = $object_type . '_id';
    $view_type_parm_name = $object_type . '_works_view_type';

    $id_value = $this->read_get_parm( "$keycol_name" );

    if( $id_value ) {  # We've come into this page via a GET link from Person or Place search results
      $view_type = $this->read_get_parm( "$view_type_parm_name" );
      $get_viewname_method = 'proj_' . $object_type . '_' . $view_type . '_viewname';
      $summary_view = $this->$get_viewname_method();

      $statement = "select iwork_id from $summary_view "
                 . " where $keycol_name = '$id_value' ";

      $ids = $this->db_select_into_array( $statement );
      foreach( $ids as $row ) {
        if( $iwork_id_string > '' ) $iwork_id_string .= ', ';
        $iwork_id_string .= $row[ 'iwork_id' ];
      }

      $this->write_post_parm( "$keycol_name", $id_value );
      $this->write_post_parm( 'iwork_id_string', $iwork_id_string );
      $this->write_post_parm( "$view_type_parm_name", $view_type );
    }
    else {
      $id_value = $this->read_post_parm( "$keycol_name" );
      $iwork_id_string = $this->read_post_parm( 'iwork_id_string' );
      $view_type = $this->read_post_parm( "$view_type_parm_name" );
    }

    $func = 'db_search_results';
    $view_desc = 'Letters ';
    switch( $view_type ) {
      case 'sent':
      case 'author':
        $view_desc .= ' from ';
        if( $object_type == 'location' )
          $secondary_search_term_name = 'places_from_searchable';
        else
          $secondary_search_term_name = 'creators_searchable';
        break;

      case 'recd':
        $view_desc .= ' to ';
        if( $object_type == 'location' )
          $secondary_search_term_name = 'places_to_searchable';
        else
          $secondary_search_term_name = 'addressees_searchable';
        break;

      case 'mentioned':
        $func = 'alternative_work_search_results';
        $view_desc .= ' mentioning ';
        # Have not so far been asked to add a 'places mentioned' total to the locations view.
        $secondary_search_term_name = 'people_mentioned';
        break;

      default:
        $view_desc .= ' to or from ';
        if( $object_type == 'location' ) 
          $secondary_search_term_name = 'place_to_or_from';
        else
          $secondary_search_term_name = 'sender_or_recipient';
        break;
    }

    # Enable a (rather rough and ready) switch from Compact view to Expanded and vice versa
    if( $object_type == 'location' ) {
      if( ! $this->popup_location ) $this->popup_location = new Location( $this->db_connection );
      $secondary_search_term_value = $this->popup_location->get_location_desc_from_id( $id_value );
    }
    else
      $secondary_search_term_value = $this->popup_person->get_person_desc_from_id( $id_value );
    $view_desc .= $secondary_search_term_value;

    $this->write_post_parm( $secondary_search_term_name, $secondary_search_term_value );
    $this->write_post_parm( 'text_query_op_' . $secondary_search_term_name, 'contains' );
    $this->write_post_parm( 'manual_search', 'Y' ); # enable 'Refine Search'

    $this->$func();
  }
  #-----------------------------------------------------

  function db_read_selection_criteria( $columns ) {

    if( $this->menu_method_name == 'person_works_search_results'       # user clicked on a link from 'Person' display
    ||  $this->menu_method_name == 'location_works_search_results' ) { # user clicked on a link from 'Place' display

      # Set up display of 'order by' clause etc.
      $this->entering_selection_criteria = FALSE;
      $this->reading_selection_criteria = TRUE;
      $columns = $this->db_list_columns( $this->from_table );
      parent::db_read_selection_criteria( $columns );  

      # Now override the where-clause created by DBEntity
      $iwork_id_string = $this->read_post_parm( 'iwork_id_string' );
      $where_clause = " iwork_id in ( $iwork_id_string ) ";
      return $where_clause;
    }

    else # Run normal DBEntity version
      return parent::db_read_selection_criteria( $columns );
  }
  #-----------------------------------------------------

  function db_write_hidden_selection_criteria() {

    $related_object_type = NULL;

    if( $this->menu_method_name == 'person_works_search_results' ) {  # you've clicked on a link in list of people

      $related_object_type = 'person';

      $secondary_search_terms = array( 'creators_searchable', 
                                       'addressees_searchable', 
                                       'sender_or_recipient',
                                       'people_mentioned' );
    }

    elseif( $this->menu_method_name == 'location_works_search_results' ) {  # you've clicked on a link in list of places

      $related_object_type = 'location';

      $secondary_search_terms = array( 'places_from_searchable', 
                                       'places_to_searchable',
                                       'place_to_or_from' );
    }

    if( $related_object_type ) {  # you've clicked on a link in a list of people or places
      $keycol_name = $related_object_type . '_id';
      $view_type_parm = $related_object_type . '_works_view_type';

      $iwork_id_string = $this->read_post_parm( 'iwork_id_string' );
      HTML::hidden_field( 'iwork_id_string', $iwork_id_string );

      $id_value = $this->read_post_parm( "$keycol_name" );
      HTML::hidden_field( "$keycol_name", $id_value );

      $view_type = $this->read_post_parm( $view_type_parm );
      HTML::hidden_field( $view_type_parm, $view_type );

      foreach( $secondary_search_terms as $search_term ) {
        if( $this->parm_found_in_post( $search_term )) {
          $$search_term = $this->read_post_parm( $search_term );
          HTML::hidden_field( $search_term, $$search_term );
          HTML::hidden_field( 'text_query_op_' . $search_term, 'contains' );
          break; # there will only be one of these secondary search terms
        }
      }
    }

    else # run normal DBEntity version
      parent::db_write_hidden_selection_criteria();
  }
  #-----------------------------------------------------

  function overview() {

    $iwork_id = $this->read_post_parm( 'iwork_id' );
    if( ! $iwork_id ) $iwork_id = $this->read_get_parm( 'iwork_id' );

    if( ! $iwork_id ) {
      HTML::new_paragraph();
      echo 'This is a new record. No details have yet been saved.';
      HTML::new_paragraph();
      return;
    } 

    #---- Select all details for overview into properties ----
    $this->select_details_for_overview( $iwork_id );

    #---- Start displaying details onscreen OR preparing CSV file ----

    $this->write_overview_stylesheet();
    $this->in_overview = TRUE;

    $class_name = $this->app_get_class( $this );
    if( $class_name != 'editable_work' ) {  # Editable work already displays work description then row of tabs

      HTML::h3_start();
      $this->echo_safely( $this->description );
      HTML::h3_end();

      HTML::div_start( 'class="buttonrow"' );

      if( $this->proj_edit_mode_enabled()) {
        HTML::form_start( PROJ_COLLECTION_WORK_CLASS, 'edit_work' );
        HTML::hidden_field( 'iwork_id', $this->iwork_id );
        HTML::submit_button( 'edit_button', 'Edit' );
        HTML::form_end();
      }

      HTML::form_start( PROJ_COLLECTION_WORK_CLASS, 'db_search' );
      HTML::submit_button( 'search_button', 'Search' );
      HTML::form_end();

      echo LINEBREAK;
      HTML::div_end();
      HTML::horizontal_rule();
    }

    $this->overview_by_email = array();
    $this->csv_output = $this->read_get_parm( 'csv_output' );

    if( ! $this->csv_output ) { # not already producing CSV output from an earlier request

      if( $this->read_session_parm( 'user_email' )) { # user has entered an email address via 'Edit your own details' 
        echo 'You can have the following summary sent to you by email as a ';
        $href = $_SERVER[ 'PHP_SELF' ] . '?iwork_id=' . $iwork_id . '&csv_output=Y';
        $title = 'Spreadsheet output of details for record no. ' . $iwork_id;
        HTML::link( $href, $displayed_text = 'spreadsheet', $title, $target = '_blank' ); 
      }
      else
        echo "Note: You have not entered an email address for yourself via the 'Edit your own details'"
             . ' option of the Main Menu, so this summary cannot currently be emailed to you in spreadsheet format.'
             . " Try clicking the Main Menu link at the top of the page and choosing 'Edit your own details'."
             . ' Once you have entered an email address for yourself, this and other query results can be emailed'
             . ' to you at the click of a button.';

      HTML::table_start( 'class="overview"' );
    }

    $columns = $this->get_columns_for_overview();
    foreach( $columns as $column_name ) {

      $column_label = $this->db_get_default_column_label( $column_name );
      $column_value = $this->$column_name;

      switch( $column_name ) {  # in some cases write out headings
        case 'iwork_id':
          $this->display_one_detail_of_overview( 'General', NULL, $is_heading = TRUE );
          break;
        case 'date_of_work_as_marked':
          $this->display_one_detail_of_overview( 'Date of work', NULL, $is_heading = TRUE );
          break;
        case 'creators_for_display':
          $this->display_one_detail_of_overview( 'Authors/senders', NULL, $is_heading = TRUE );
          break;
        case 'addressees_for_display':
          $this->display_one_detail_of_overview( 'Addressees', NULL, $is_heading = TRUE );
          break;
        case 'reply_to':
          $this->display_one_detail_of_overview( 'Replies', NULL, $is_heading = TRUE );
          break;
        case 'places_from_for_display':
          $this->display_one_detail_of_overview( 'Place of origin', NULL, $is_heading = TRUE );
          break;
        case 'places_to_for_display':
          $this->display_one_detail_of_overview( 'Destination', NULL, $is_heading = TRUE );
          break;

        case 'language_of_work':
          $this->display_one_detail_of_overview( 'Language and content', NULL, $is_heading = TRUE );
          break;

        case 'general_notes':
          # We'll do 'Manifestations' just before doing 'Further details and links' 
          # as this corresponds to the order in the database structure documentation.

          $this->overview_of_manifestations();

          $this->display_one_detail_of_overview( 'Further details and links', NULL, $is_heading = TRUE );
          break;
        case 'work_id':
          $this->display_one_detail_of_overview( 'System information', NULL, $is_heading = TRUE );
          break;
      }

      switch( $column_name ) {

        case 'edit_status':  # has not yet been put on editing interface
          break;

        case 'original_calendar':
          $column_value = $this->date_entity->decode_calendar( $column_value );
          $this->display_one_detail_of_overview( $column_label, $column_value ) ;
          break;

        case 'date_of_work2_std_year':  # no need to display unless date of work is a range
        case 'date_of_work2_std_month':
        case 'date_of_work2_std_day':
          if( $this->date_of_work_std_is_range )
            $this->display_one_detail_of_overview( $column_label, $column_value ) ;
          break;


        case 'relevant_to_cofk':
          if( PROJ_SUB_COLLECTION == 'cardindex' ) { # the others only contain relevant material anyway
            if( $column_value == 'Y' )
              $column_value = 'Yes';
            elseif( $column_value == 'N' )
              $column_value = '*** No ***';
            else
              $column_value = 'Unknown';
            $this->display_one_detail_of_overview( $column_label, $column_value ) ;
          }
          break;

        case 'editors_notes':
          if( PROJ_SUB_COLLECTION == 'cardindex' ) { # the others don't have this on the interface
            $this->display_one_detail_of_overview( $column_label, $column_value ) ;
          }
          break;

        case 'date_of_work_std_is_range':
        case 'date_of_work_inferred':
        case 'date_of_work_uncertain':
        case 'date_of_work_approx':
        case 'authors_inferred':
        case 'authors_uncertain':
        case 'addressees_inferred':
        case 'addressees_uncertain':
        case 'destination_inferred':
        case 'destination_uncertain':
        case 'origin_inferred':
        case 'origin_uncertain':
        case 'work_is_translation':
        case 'work_to_be_deleted':
          if( $column_value )
            $column_value = '*** Yes ***';
          else
            $column_value = 'No';
          $this->display_one_detail_of_overview( $column_label, $column_value ) ;

          break;

        case 'change_timestamp':
        case 'creation_timestamp':
          $column_value = substr( $column_value, 0, strlen( 'yyyy-mm-dd hh:mi' ));
          $this->display_one_detail_of_overview( $column_label, $column_value ) ;
          break;

        case 'change_user':
          $this->display_one_detail_of_overview( $column_label, $column_value ) ;

          if( $this->proj_edit_mode_enabled()) { # non-editors don't need to poke around in the audit trail
            HTML::tablerow_start();
            HTML::tabledata( '' );
            HTML::tabledata_start( 'class="fieldvalue"' );
            $this->audit_trail_link();
            HTML::tabledata_end();
            HTML::tablerow_end();
          }
          break;

        default:
          $this->display_one_detail_of_overview( $column_label, $column_value ) ;
      }
    }

    if( $this->csv_output ) {
      $msg_subject = 'Work ' . $this->iwork_id . ': ' . $this->description;

      $this->db_produce_csv_output( $this->overview_by_email,
                                    $msg_recipient = NULL, # by default send file to self
                                    $msg_body = NULL,      # use default
                                    $msg_subject );
    }
    else # not in CSV output mode
      HTML::table_end();

  }
  #-----------------------------------------------------

  function audit_trail_link() {

    if( ! $this->iwork_id ) return; # 'set work' needs to have been done

    if( ! $this->csv_output && ! $this->printable_output ) {

      $href = $_SERVER[ 'PHP_SELF' ] . '?class_name=audit_trail'
                                     . '&method_name=one_work_search_results'
                                     . '&table_name=' . $this->proj_work_tablename() 
                                     . '&key_value=' . $this->iwork_id;

      $title = 'Display audit trail for work ' . $this->iwork_id;
      HTML::link( $href, $displayed_text = 'Display audit trail', $title, $target = '_blank' ); 
    }
  }
  #-----------------------------------------------------

  function select_details_for_overview( $iwork_id ) {

    if( ! $iwork_id ) die( 'Invalid work ID.' );

    $this->set_work( $iwork_id );
    $statement = $this->get_view_select_for_overview( $iwork_id );
    if( $statement ) $this->db_select_into_properties( $statement );

    $this->answered_by = $this->get_decoded_reply_to_this();
    $this->reply_to = $this->get_decoded_answered_by_this();
    $this->notes_on_addressees = $this->get_decoded_notes_on_addressees();
    $this->intended_recipients = $this->get_decoded_intended_recipient();
    $this->signatories = $this->get_decoded_signatory();
    $this->senders = $this->get_decoded_sender();
    $this->notes_on_people_mentioned = $this->get_decoded_notes_on_people_mentioned();
    $this->places_mentioned = $this->get_decoded_places_mentioned();
    $this->works_mentioned = $this->get_decoded_works_mentioned();
    $this->works_that_mention_this_one = $this->get_decoded_works_mentioning();
    $this->notes_on_date_of_work = $this->get_decoded_notes_on_date_of_work();

    $this->manif_ids = $this->get_manifestation_ids();
    $this->manif_obj = new Manifestation( $this->db_connection );
  }
  #-----------------------------------------------------

  function get_view_select_for_overview( $iwork_id ) {  # Make sure you don't try to select columns
                                                        # that don't exist in the appropriate view
    if( ! $iwork_id ) die( 'Invalid work ID.' );

    $cols = DBEntity::db_list_columns( $this->proj_work_viewname() );
    $viewcols = array();

    foreach( $cols as $crow ) {
      $column_name = $crow[ 'column_name' ];
      $viewcols[] = $column_name;
    }

    $selection_cols = '';

    $potential_selection_cols = array( 'creators_for_display',
                                       'notes_on_authors',
                                       'addressees_for_display',
                                       'places_from_for_display',
                                       'places_to_for_display',
                                       'related_resources',
                                       'general_notes',
                                       'original_catalogue',
                                       'images',
                                       'subjects',
                                       'people_mentioned' );

    foreach( $potential_selection_cols as $col ) {
      if( in_array( $col, $viewcols )) {
        if( $selection_cols > '' ) $selection_cols .= ', ';
        $selection_cols .= $col;
      }
    }

    if( ! $selection_cols ) return NULL;

    $statement = 'select ' . $selection_cols
               . ' from ' . $this->proj_work_viewname() . " where iwork_id = $iwork_id";
    return $statement;
  }
  #-----------------------------------------------------

  function display_one_detail_of_overview( $column_label, $column_value, $is_heading = FALSE ) {

    if( $this->csv_output ) {
      if( $is_heading && $column_label ) $column_label .= ':';

      $column_value = str_replace( IMAGE_ID_START_MARKER, '', $column_value );
      $column_value = str_replace( IMAGE_ID_END_MARKER,  ' ', $column_value );

      $column_value = str_replace( LINK_TEXT_START_MARKER, '', $column_value );
      $column_value = str_replace( LINK_TEXT_END_MARKER,  ' ', $column_value );

      $column_value = str_replace( HREF_START_MARKER,  '', $column_value );
      $column_value = str_replace( HREF_END_MARKER, ' ',   $column_value );

      $column_value = str_replace( '<ul><li>',    '', $column_value );
      $column_value = str_replace( '</li></ul>',  '', $column_value );

      $column_value = str_replace( '</li><li>',  ' ' . NON_HTML_LIST_SEPARATOR . ' ', $column_value );

      $this->overview_by_email[] = array( 'Field' => $column_label, 'Value' => $column_value );
      return;
    }

    $bold = FALSE;
    if( $this->string_starts_with( $column_value, '***' ) && $this->string_ends_with( $column_value, '***' ))
      $bold = TRUE;

    HTML::tablerow_start();

    $parms = NULL;
    if( ! $is_heading ) $parms = 'class="fieldtitle"';

    HTML::tabledata_start( $parms );
    if( $column_label ) {
      if( $is_heading ) HTML::h4_start();
      $this->echo_safely( $column_label . ':' );
      if( $is_heading ) HTML::h4_end();
    }
    HTML::tabledata_end();

    $parms = NULL;
    if( ! $is_heading ) $parms = 'class="fieldvalue"';

    HTML::tabledata_start( $parms );
    if( $bold || $is_heading ) HTML::bold_start();
    $this->echo_safely( $column_value );
    if( $bold || $is_heading ) HTML::bold_end();
    HTML::tabledata_end();

    HTML::tablerow_end();
  }
  #-----------------------------------------------------

  function get_columns_for_overview() {


    $columns = array(  # Not using 'DB list columns' here, as would like to arrange in different order
                       # AND add columns from view.
      'iwork_id',
      'description',
      'original_catalogue',
      'accession_code',
      'editors_notes',
      'relevant_to_cofk',
      'edit_status',
      'work_to_be_deleted',

      'date_of_work_as_marked',
      'original_calendar',
      'date_of_work_std_is_range',
      'date_of_work_std_year',
      'date_of_work_std_month',
      'date_of_work_std_day',
      'date_of_work2_std_year',
      'date_of_work2_std_month',
      'date_of_work2_std_day',
      'date_of_work_inferred',
      'date_of_work_uncertain',
      'date_of_work_approx',
      'notes_on_date_of_work',
      'date_of_work_std',
      'date_of_work_std_gregorian',

      'creators_for_display',
      'senders',
      'signatories',
      'authors_as_marked',
      'notes_on_authors',
      'authors_inferred',
      'authors_uncertain',

      'addressees_for_display',
      'intended_recipients',
      'addressees_as_marked',
      'notes_on_addressees',
      'addressees_inferred',
      'addressees_uncertain',

      'reply_to',
      'answered_by',

      'places_from_for_display',
      'origin_as_marked',
      'origin_inferred',
      'origin_uncertain',

      'places_to_for_display',
      'destination_as_marked',
      'destination_inferred',
      'destination_uncertain',

      'language_of_work',
      'work_is_translation',
      'incipit',
      'explicit',
      'ps',
      'subjects',
      'abstract',
      'keywords',
      'people_mentioned',
      'notes_on_people_mentioned',
      'places_mentioned',
      'works_mentioned',
      'works_that_mention_this_one',

      'general_notes',
      'related_resources',

      'work_id',
      'creation_timestamp',
      'creation_user',
      'change_timestamp',
      'change_user'
    );

    return $columns;
  }
  #-----------------------------------------------------

  function write_overview_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    #----

    echo ' .overview ul {'                                        . NEWLINE;
    echo '    margin-top: 0px;'                                   . NEWLINE;
    echo '    margin-bottom: 0px;'                                . NEWLINE;
    echo '    margin-left: 14px;'                                  . NEWLINE;
    echo '    padding-left: 0px;'                                 . NEWLINE;
    echo '    padding-top: 0px;'                                  . NEWLINE;
    echo ' }'                                                     . NEWLINE;

    echo ' .overview li {'                                        . NEWLINE;
    echo '    margin-bottom: 8px;'                                . NEWLINE;
    echo '    margin-left: 0px;'                                  . NEWLINE;
    echo ' }'                                                     . NEWLINE;

    echo ' .overview td.fieldtitle {'                             . NEWLINE;
    echo '    text-align: right;'                                 . NEWLINE;
    echo '    font-style: italic;'                                . NEWLINE;
    echo ' }'                                                     . NEWLINE;

    echo ' .overview td.fieldvalue {'                              . NEWLINE;
    echo '    padding-bottom: 5px;'                                . NEWLINE;
    echo '    padding-left: 10px;'                                 . NEWLINE;
    echo ' }'                                                      . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function overview_of_manifestations() {

    $current_manif = 0;
    if( ! $this->manif_count ) $this->manif_count = 0;

    $this->display_one_detail_of_overview( 'Manifestations', NULL, $is_heading = TRUE );
    foreach( $this->manif_ids as $manif ) {
      $current_manif++;

      $this->manifestation_id = $manif[ 'other_id_value' ];

      $this->manif_overview = $this->manif_obj->get_manifestation_overview( $this->manifestation_id,
                                                                            $current_manif, $this->manif_count );

      $fieldcount = 0;
      foreach( $this->manif_overview as $mrow ) {
        $fieldcount++;

        $field = $mrow[ 'Field' ];
        $value = $mrow[ 'Value' ];

        if( $fieldcount > 1 ) 
          $this->display_one_detail_of_overview( $field, $value );

        else {  # use first entry of manifestation (document type) as heading
          $field = NULL;
          $this->display_one_detail_of_overview( $field, $value, $is_heading = TRUE );
        }
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_decoded_reply_to_this() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_LATER_WORK_REPLIES_TO_EARLIER_WORK, 
                                            $required_table = $this->proj_work_tablename(), 
                                            $this_side = 'right' ); # Later replies to earlier. This is earlier.
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_answered_by_this() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_LATER_WORK_REPLIES_TO_EARLIER_WORK, 
                                            $required_table = $this->proj_work_tablename(), 
                                            $this_side = 'left' ); # Later replies to earlier. This is later.
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_notes_on_addressees() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_COMMENT_REFERS_TO_ADDRESSEE, 
                                            $required_table = $this->proj_comment_tablename(), 
                                            $this_side = 'right' ); # Comment refers to (addressee of) work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_notes_on_people_mentioned() {

    $result=$this->proj_get_decoded_rels($required_relationship_type=RELTYPE_COMMENT_REFERS_TO_PEOPLE_MENTIONED_IN_WORK, 
                                         $required_table = $this->proj_comment_tablename(), 
                                         $this_side = 'right' ); # Comment refers to (person mentioned in) work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_notes_on_date_of_work() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_COMMENT_REFERS_TO_DATE, 
                                            $required_table = $this->proj_comment_tablename(), 
                                            $this_side = 'right' ); # Comment refers to (date of) work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_intended_recipient() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_WORK_INTENDED_FOR_PERSON, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'left' ); # Work intended for person
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_sender() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_PERSON_SENDER_OF_WORK, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'right' ); # Person sent work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_signatory() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_PERSON_SIGNATORY_OF_WORK, 
                                            $required_table = $this->proj_person_tablename(), 
                                            $this_side = 'right' ); # Person signed work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_places_mentioned() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_WORK_MENTIONS_PLACE, 
                                            $required_table = $this->proj_location_tablename(), 
                                            $this_side = 'left' ); # Work mentions place
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_works_mentioned() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_LATER_WORK_MENTIONS_EARLIER_WORK, 
                                            $required_table = $this->proj_work_tablename(), 
                                            $this_side = 'left' ); # Later work mentions earlier work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_decoded_works_mentioning() {

    $result = $this->proj_get_decoded_rels( $required_relationship_type = RELTYPE_LATER_WORK_MENTIONS_EARLIER_WORK, 
                                            $required_table = $this->proj_work_tablename(), 
                                            $this_side = 'right' ); # Later work mentions earlier work
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_manifestation_ids() {

    $result = $this->proj_get_rel_ids( $required_relationship_type = RELTYPE_MANIFESTATION_IS_OF_WORK, 
                                       $required_table = $this->proj_manifestation_tablename(), 
                                       $this_side = 'right' ); 
    return $result;
  }
  #----------------------------------------------------------------------------------

  function one_work_search_results() {

    if( $this->get_system_prefix() == IMPACT_SYS_PREFIX ) {
      $impact_work_class = PROJ_COLLECTION_WORK_CLASS;
      $impact_work_obj = new $impact_work_class( $this->db_connection );

      # Allow the called class to know which menu option has called it
      # This is essential for the CSV output option

      $impact_work_obj->menu_item_id          = $this->menu_item_id;
      $impact_work_obj->menu_item_name        = $this->menu_item_name ;
      $impact_work_obj->menu_parent_id        = $this->parent_id ;
      $impact_work_obj->menu_class_name       = $this->class_name ;
      $impact_work_obj->menu_method_name      = $this->method_name ;
      $impact_work_obj->menu_user_restriction = $this->user_restriction ;
      $impact_work_obj->menu_hidden_parent    = $this->hidden_parent ;
      $impact_work_obj->menu_called_as_popup  = $this->called_as_popup ;

      $impact_work_obj->printable_output      = $this->printable_output;
      $impact_work_obj->csv_output            = $this->csv_output;

      $impact_work_obj->username              = $this->username;
      $impact_work_obj->person_name           = $this->person_name;

      $impact_work_obj->overview();
      return;
    }

    $this->overview();
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'work_id':
      case 'person_id':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      case 'iwork_id':
      case 'date_of_work_std_year':
      case 'date_of_work_std_month':
      case 'date_of_work_std_day':
      case 'date_of_work2_std_year':
      case 'date_of_work2_std_month':
      case 'date_of_work2_std_day':
      case 'date_of_work_std_is_range':
      case 'work_type':
      case 'location_id':
      case 'manif_count':

        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'date_of_work_inferred':
      case 'date_of_work_uncertain':
      case 'date_of_work_approx':
      case 'authors_inferred':
      case 'authors_uncertain':
      case 'addressees_inferred':
      case 'addressees_uncertain':
      case 'destination_inferred':
      case 'destination_uncertain':
      case 'origin_inferred':
      case 'origin_uncertain':
      case 'work_is_translation':
      case 'work_to_be_deleted':
        if( $this->reading_parms_for_update )
          return $this->is_integer( $this->parm_value );
        else
          return $this->is_alphabetic_or_blank( $this->parm_value );

      case 'work_title':
      case 'work_alternative_titles':
      case 'description':
      case 'date_of_work_as_marked':
      case 'original_calendar':
      case 'sender_or_recipient':
      case 'place_to_or_from':
      case 'senders_searchable':
      case 'addressees_searchable':
      case 'creators_searchable':
      case 'places_from_searchable':
      case 'places_to_searchable':
      case 'manifestations_searchable':
      case 'images':
      case 'related_works_searchable':
      case 'comments_on_work_searchable':
      case 'record_edited_by_searchable':
      case 'summary_searchable':
      case 'abstract':
      case 'keywords':
      case 'language_of_work':
      case 'manifestation_type':
      case 'id_number_or_shelfmark':
      case 'addressed_to':
      case 'incipit':
      case 'explicit':
      case 'incipit_and_explicit':
      case 'ps':
      case 'editors_notes':
      case 'original_notes':
      case 'people_mentioned':
      case 'authors_as_marked':
      case 'addressees_as_marked':
      case 'destination_as_marked':
      case 'origin_as_marked':
      case 'accession_code':
      case 'original_catalogue':
      case 'edit_status':
      case 'notes_on_authors':
      case 'general_notes':
      case 'flags':
      case 'related_resources':
      case 'type_of_work':  # decoded version from the IMPAcT view
      case 'subjects':      # decoded version from the IMPAcT view
      case 'related_works':

        return $this->is_ok_free_text( $this->parm_value );


      case 'date_of_work_std':
      case 'date_of_work_std2':
      case 'date_of_work_std_gregorian':
      case 'date_of_work_std_gregorian2':
      case 'christian_date': # from IMPAcT work view
      case 'christian_date2':

        if( $this->reading_parms_for_update ) {
          $parm_value = $this->parm_value;
          if( $this->string_starts_with( $parm_value, '9999' )) # Postgres timestamp check won't allow this year
            $parm_value = '1000' . substr( $parm_value, 4 );
          return $this->is_postgres_timestamp( $parm_value );
        }

        $this->parm_value = $this->yyyy_to_dd_mm_yyyy( $parm_name, $this->parm_value );
        return $this->is_dd_mm_yyyy( $this->parm_value, $allow_blank = TRUE, $allow_pre_1950 = TRUE );

      case 'drawer':
        return $this->is_alphanumeric_or_blank( str_replace( str_replace( '-', '_', $this->parm_value ), ' ', '' ),
                                                $allow_underscores = TRUE, $allow_all_whitespace = TRUE );

      case 'person_works_view_type':
      case 'location_works_view_type':
        return $this->is_alphanumeric_or_blank( $allow_underscores = TRUE );

      case 'relevant_to_cofk':
        if( $this->parm_value == '?' )
          return TRUE;
        else
          return $this->is_alphanumeric_or_blank( $this->parm_value );

      case 'iwork_id_string':
        return $this->is_comma_separated_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}