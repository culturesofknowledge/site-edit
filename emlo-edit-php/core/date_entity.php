<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


define( 'DTE_UNKNOWN_YEAR', '9999' );
define( 'DTE_UNKNOWN_MONTH', '12' );

define( 'DATE_RANGE_NOTES_OFFSET', '15' );
define( 'DATE_RANGE_NOTES_LIST_OFFSET', '90' );

define( 'DATE_UNCERTAINTY_FLAGS_LEFT_MARGIN', '10' );

define( 'STD_DATE_INPUT_FIELD_SIZE', 10 );


#----- Calendar types -----------

define( 'CALENDAR_TYPE_UNKNOWN',      ''   );
define( 'CALENDAR_TYPE_UNKNOWN_DESC', 'Unknown' );

define( 'CALENDAR_TYPE_GREG',      'G'  );
define( 'CALENDAR_TYPE_GREG_DESC', 'Gregorian' );

define( 'CALENDAR_TYPE_JULIAN_MAR',      'JM' );
define( 'CALENDAR_TYPE_JULIAN_MAR_DESC', 'Julian (year starting 25th Mar)' );

define( 'CALENDAR_TYPE_JULIAN_JAN',      'JJ' );
define( 'CALENDAR_TYPE_JULIAN_JAN_DESC', 'Julian (year starting 1st Jan)' );

define( 'CALENDAR_TYPE_OTHER',      'O'  );
define( 'CALENDAR_TYPE_OTHER_DESC', 'Other' );

#----- End calendar types -----------

define( 'DATE_RANGE_HELP_1', 'The work is known to have been written over a period of two or more days');
define( 'DATE_RANGE_HELP_2', 'The work cannot be precisely dated even to a single year' );

class Date_Entity extends Project {

  #----------------------------------------------------------------------------------

  function Date_Entity( &$db_connection, $date_format = 'dd/mm/yyyy' ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    $date_format = strtolower( trim( $date_format ));

    if( $date_format == 'dd/mm/yyyy' )
      $this->date_format = $date_format;
    else
      $this->date_format = 'yyyy-mm-dd';

    $this->set_month_list();
    $this->set_calendar_list();
  }

  #----------------------------------------------------------------------------------

  function clear() {

    $keep_date_format = $this->date_format;
    $keep_month_list = $this->month_list;
    $keep_calendar_list = $this->calendar_list;

    parent::clear();

    $this->date_format = $keep_date_format;
    $this->month_list = $keep_month_list;
    $this->calendar_list = $keep_calendar_list;
  }
  #----------------------------------------------------------------------------------

  function write_date_entry_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' span.date_range_notes {'                                  . NEWLINE;
    echo '   position: relative; '                                   . NEWLINE;
    echo '   left: ' . DATE_RANGE_NOTES_OFFSET . 'px;'               . NEWLINE;
    echo ' }'                                                        . NEWLINE;

    echo ' ul.date_range_notes {'  . NEWLINE;
    echo '   margin-left: ' . DATE_RANGE_NOTES_LIST_OFFSET . 'px;'   . NEWLINE;
    echo '   margin-top: 0px;'                                       . NEWLINE; 
    echo ' }'                                                        . NEWLINE;

    echo ' .date_entry_field hr { ' . NEWLINE;
    echo '   color: ' . html::get_contrast1_colour() . ';'              . NEWLINE;  # IE
    echo '   background-color: ' . html::get_contrast1_colour() . ';'   . NEWLINE;  # Firefox
    echo ' }'                                                           . NEWLINE;

    echo ' label.mainlabel {'                                           . NEWLINE;
    echo '   font-weight: bold;'                                        . NEWLINE; 
    echo ' }'                                                           . NEWLINE;

    echo ' label.seconddate, span.nextfield {'                          . NEWLINE;
    echo '   margin-left: 20px;'                                        . NEWLINE; 
    echo ' }'                                                           . NEWLINE;

    echo ' input.readonly_date, input.refreshbutton { '                     . NEWLINE;
    echo '   background-color: ' . html::header_background_colour() . ';'   . NEWLINE;
    echo ' }'                                                               . NEWLINE;

    echo ' input.refreshbutton { '                                          . NEWLINE;
    echo '   font-size: 9pt;'                                               . NEWLINE;
    echo ' }'                                                               . NEWLINE;

    echo ' .date_uncertainty_flags input {'                                . NEWLINE;
    echo '   margin-left: ' . DATE_UNCERTAINTY_FLAGS_LEFT_MARGIN . 'px;'   . NEWLINE;
    echo '   margin-top: 0px;'                                             . NEWLINE; 
    echo ' }'                                                              . NEWLINE;

    echo ' ul.compact_calendar_list {'                                     . NEWLINE;
    echo '   display: inline;'                                             . NEWLINE;
    echo '   padding: 0px;'                                                . NEWLINE; 
    echo '   margin: 0px;'                                                . NEWLINE; 
    echo ' }'                                                              . NEWLINE;

    echo ' ul.compact_calendar_list li {'                                  . NEWLINE;
    echo '   display: inline;'                                             . NEWLINE;
    echo '   list-style-type: none;'                                       . NEWLINE;
    echo '   padding: 0px;'                                                . NEWLINE; 
    echo '   margin-top: 0px;'                                             . NEWLINE; 
    echo '   margin-right: 10px;'                                          . NEWLINE; 
    echo ' }'                                                              . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function set_month_list() {

    $this->month_list = array( 0 => '',
                               1 => 'Jan',
                               2 => 'Feb',
                               3 => 'Mar',
                               4 => 'Apr',
                               5 => 'May',
                               6 => 'Jun',
                               7 => 'Jul',
                               8 => 'Aug',
                               9 => 'Sep',
                               10 => 'Oct',
                               11 => 'Nov',
                               12 => 'Dec' );
  }
  #----------------------------------------------------------------------------------

  function set_calendar_list() {

    $this->calendar_list = array( CALENDAR_TYPE_UNKNOWN    => CALENDAR_TYPE_UNKNOWN_DESC,
                                  CALENDAR_TYPE_GREG       => CALENDAR_TYPE_GREG_DESC,
                                  CALENDAR_TYPE_JULIAN_MAR => CALENDAR_TYPE_JULIAN_MAR_DESC,
                                  CALENDAR_TYPE_JULIAN_JAN => CALENDAR_TYPE_JULIAN_JAN_DESC,
                                  CALENDAR_TYPE_OTHER      => CALENDAR_TYPE_OTHER_DESC );
  }
  #----------------------------------------------------------------------------------

  function decode_calendar( $calendar_code ) {

    $calendar_desc = CALENDAR_TYPE_UNKNOWN_DESC;

    foreach( $this->calendar_list as $code => $desc ) {
      if( $code == $calendar_code ) {
        $calendar_desc = $desc;
        break;
      }
    }

    return $calendar_desc;
  }
  #----------------------------------------------------------------------------------

  function set_date_format_yyyy_mm_dd() {

    $this->date_format = 'yyyy-mm-dd';
  }
  #----------------------------------------------------------------------------------

  function set_date_format_dd_mm_yyyy() {

    $this->date_format = 'dd/mm/yyyy';
  }
  #----------------------------------------------------------------------------------

  function get_date_order_desc( $bracketed = FALSE ) {

    if( $this->date_format == 'dd/mm/yyyy' ) {
      $order_desc = 'day, month, year';
    }
    else {
      $order_desc = 'year, month, day';
    }

    if( $bracketed ) $order_desc = ' (' . $order_desc . ') ';

    return $order_desc;
  }
  #----------------------------------------------------------------------------------

  function set_ids_for_css( $fieldname, $initialise = FALSE ) {

    if( $initialise || ! $this->first_fieldname ) 
      $this->first_fieldname = $fieldname;

    $this->sortable_date_orig_calendar = $this->first_fieldname;
    $this->sortable_date_gregorian     = $this->first_fieldname . '_gregorian';

    $this->sortable_date_manual_entry  = $this->sortable_date_orig_calendar . '_manual_entry';
    $this->sortable_date_refresh       = $this->sortable_date_orig_calendar . '_refresh';

    $this->generate_sortable_dates_script = 'generate_' . $this->sortable_date_orig_calendar;

    #---------

    $this->fieldname     = $fieldname;

    $this->range_checkbox_id = $this->get_range_checkbox_id();

    $this->fieldset_id   = $this->get_fieldset_id(  $fieldname );

    $this->date_span_id  = $this->get_span_id(  $fieldname );
    $this->label_id      = $this->get_label_id( $fieldname );

    $this->year_id       = $this->get_year_fieldname( $fieldname );
    $this->year_span_id  = $this->get_span_id( $this->get_year_fieldname( $fieldname ));

    $this->month_id      = $this->get_month_fieldname( $fieldname );
    $this->month_span_id = $this->get_span_id( $this->get_month_fieldname( $fieldname ));

    $this->day_id        = $this->get_day_fieldname(   $fieldname );

    $this->day_span_id   = $this->get_span_id( $this->get_day_fieldname( $fieldname ));

    $this->order_desc_id = $this->get_order_desc_id( $fieldname );

    $this->reset = array( $this->year_span_id,
                          $this->month_span_id,
                          $this->day_span_id,
                          $this->order_desc_id,
                          $this->label_id );

    $this->show_script_name = 'show_' . $fieldname;
    $this->hide_script_name = 'hide_' . $fieldname;

    $this->checkbox_onclick_script_name = 'show_or_hide_' . $fieldname;
  }
  #----------------------------------------------------------------------------------

  function get_show_script( $fieldname ) {

    $this->set_ids_for_css( $fieldname );

    $script  = 'function ' . $this->show_script_name . '() {' . NEWLINE;
    $script .= '  var span_element;'                          . NEWLINE;

    foreach( $this->reset as $one_reset ) {
      $script .= '  span_element = document.getElementById( "' . $one_reset . '" );'  . NEWLINE;

      $innerhtml = '';
      switch( $one_reset ) {

        case $this->label_id:
          break;  # set labels via a separate function

        case $this->year_span_id:
          $innerhtml = $this->get_year_entry_field_html( $year );
          break;

        case $this->month_span_id:
          $innerhtml = $this->get_month_entry_field_html( $month );
          break;

        case $this->day_span_id:
          $innerhtml = $this->get_day_entry_field_html( $day );
          break;

        case $this->order_desc_id:
          $innerhtml = $this->get_date_order_desc( $bracketed = TRUE );
          break;

        default:
          break;
      }

      $script .= '  span_element.innerHTML = "' . $this->escape( $innerhtml ) . '";' . NEWLINE;
    }

    $script .= '}' . NEWLINE;

    return $script;
  }
  #----------------------------------------------------------------------------------

  function get_hide_script( $fieldname ) {

    $this->set_ids_for_css( $fieldname );

    $script  = 'function ' . $this->hide_script_name . '() {'         . NEWLINE;
    $script .= '  var span_element;'                          . NEWLINE;

    foreach( $this->reset as $one_reset ) {
      $script .= '  span_element = document.getElementById( "' . $one_reset . '" );'  . NEWLINE;
      $script .= '  span_element.innerHTML = "";'                                     . NEWLINE;
    }

    $script .= '}' . NEWLINE;

    return $script;
  }
  #----------------------------------------------------------------------------------


  function date_entry_fieldset( $fields, $calendar_field, $legend, $extra_msg = NULL, 
                                $hide_sortable_dates = FALSE, $include_uncertainty_flags = FALSE,
                                $date_range_help = array( DATE_RANGE_HELP_1, DATE_RANGE_HELP_2 ),
                                $display_calendars_in_main_fieldset = FALSE ) {

    $errmsg = NULL;
    if( ! is_array( $fields )) $errmsg = 'No date fields passed in';
    if( count( $fields ) < 1 ) $errmsg = 'Too few date fields passed in';
    if( count( $fields ) > 2 ) $errmsg = 'Too many date fields passed in';
    if( $errmsg ) die ( $ermsg );

    $this->calendar_field = $calendar_field;

    $enable_date_range = FALSE;
    if( count( $fields ) == 2 ) $enable_date_range = TRUE;

    html::new_paragraph();

    $fieldname = $fields[ 0 ];
    $this->set_ids_for_css( $fieldname, $initialise = TRUE );
    html::fieldset_start( $legend, $this->fieldset_id, 'class="date_entry_field"' );
    html::new_paragraph();

    $range_checkbox_ticked = $this->get_property_value( $this->range_checkbox_id );

    $this->change_label_script = NULL;
    $this->change_label_script_name = 'set_labels_for_' . $this->fieldset_id;

    #-----------

    $this->write_sortable_dates_generation_script( $fields );

    #-----------

    if( $display_calendars_in_main_fieldset ) {

      html::italic_start();
      echo 'Calendar of ' . strtolower( $legend ) . ': ';
      html::italic_end();
      $this->calendar_selection_within_main_fieldset();
    }
    #-----------

    if( $enable_date_range ) {

      $first = TRUE;
      foreach( $fields as $fieldname ) {
        $this->set_ids_for_css( $fieldname );

        $this->set_change_label_script( $first );

        if( ! $first ) {

          $hide_script = $this->get_hide_script( $fieldname );
          html::write_javascript_function( $hide_script );

          $show_script = $this->get_show_script( $fieldname );
          html::write_javascript_function( $show_script );

          html::write_javascript_function(  $this->change_label_script );
          html::write_javascript_function(  $this->checkbox_onclick_script());
        }
        $first = FALSE;
      }

      $checkbox_parms = 'onclick="' . $this->checkbox_onclick_script_name . '( this ); '
                      . $this->get_call_to_extra_onclick_script() # follow function call with semi-colon
                      . $this->generate_sortable_dates_script . '()"';

      html::checkbox_with_label_on_left( $this->range_checkbox_id, 'Date range?', $range_checkbox_ticked,
                                         $value_when_checked = 1, $in_table = FALSE, $tabindex=1, 
                                         $input_instance = NULL, $parms = $checkbox_parms );

      html::span_start( 'class="date_range_notes"' );
      html::italic_start();
      echo ' ';

      $date_range_help_count = count( $date_range_help );
      if( $date_range_help_count > 1 ) {
        $number_word = 'either';
        if( $date_range_help_count > 2 ) $number_word = 'any';
        echo "Tick the 'Date range' box in $number_word of the following cases: ";
        html::ulist_start( 'class="date_range_notes"' );
        foreach( $date_range_help as $help_sentence ) {
          html::listitem( $help_sentence );
        }
        html::ulist_end();
      }
      elseif( $date_range_help_count == 1 ) {
        echo ' ' . $date_range_help[0];
      }

      html::italic_end();
      html::span_end( 'date_range_notes' );
      html::new_paragraph();
      if( $date_range_help_count > 1 ) {
        html::horizontal_rule();
        html::new_paragraph();
      }
    }

    #-----------

    $first = TRUE;
    foreach( $fields as $fieldname ) {
      $this->set_ids_for_css( $fieldname );

      $label = $this->get_label( $range_checkbox_ticked, $first );

      $this->suppress_display = FALSE;
      if( ! $first ) {
        if( ! $range_checkbox_ticked ) $this->suppress_display = TRUE;
      }

      $this->date_entry_field( $fieldname, $label, $is_first_date=$first, $show_order = TRUE );
      $first = FALSE;
    }

    if( $extra_msg ) {
      html::new_paragraph();
      html::italic_start();
      echo $extra_msg;
      html::italic_end();
    }

    if( $hide_sortable_dates ) {
      $this->hidden_sortable_dates();
      $this->extra_date_fields(); # a method that can be overridden by child classes e.g. Islamic Date Entity
    }
    else {
      html::new_paragraph();
      html::horizontal_rule();
      html::new_paragraph();

      $this->sortable_date_entry();
    }

    if( $include_uncertainty_flags ) {
      html::new_paragraph();
      html::italic_start();
      echo 'Issues with ' . strtolower( $legend ) . ':';
      html::italic_end();

      html::span_start( 'class="date_uncertainty_flags"' );
      $flags = array( 'inferred', 'uncertain', 'approx' );
      foreach( $flags as $flag ) {
        $flagfield = $fields[0] . '_' . $flag;
        $flaglabel = $flag;
        if( $flaglabel == 'approx' ) $flaglabel = 'approximate';
        $this->basic_checkbox( $flagfield, 'Date is ' . $flaglabel, $this->$flagfield );
        echo ' ';
      }
      html::span_end();
    }

    html::fieldset_end( $this->fieldset_id );
  }
  #-----------------------------------------------------


  function date_entry_field( $fieldname, $label = NULL, $is_first_date, $show_order = TRUE ) {

    $this->set_ids_for_css( $fieldname );

    echo NEWLINE;
    html::span_start( 'class="date" id="' . $this->date_span_id . '"' );

    $label_parms = 'class="mainlabel';
    if( ! $is_first_date ) $label_parms .= ' seconddate';
    $label_parms .= '"';

    html::label( $label, $this->label_id, $label_parms );

    if( $this->date_format == 'dd/mm/yyyy' ) {
      $this->day_entry_field();
      $this->month_entry_field();
      $this->year_entry_field();
    }
    else {
      $this->year_entry_field();
      $this->month_entry_field();
      $this->day_entry_field();
    }

    $order_desc = $this->get_date_order_desc( $bracketed = TRUE );
    html::italic_start();
    html::span_start( 'id="' . $this->order_desc_id . '"' );
    if( $show_order && ! $this->suppress_display ) echo $order_desc;
    html::span_end( 'order desc' );
    html::italic_end();

    html::span_end( $this->date_span_id );
  }
  #-----------------------------------------------------

  function year_entry_field() {

    echo NEWLINE . NEWLINE;

    html::span_start( 'class="year" id="' . $this->year_span_id . '"' );
    
    if( ! $this->suppress_display )
      echo $this->get_year_entry_field_html();

    html::span_end( 'year: ' . $this->fieldname );
  }
  #-----------------------------------------------------

  function month_entry_field() {

    echo NEWLINE . NEWLINE;

    html::span_start( 'class="month" id="' . $this->month_span_id . '"' );

    if( ! $this->suppress_display )
      echo $this->get_month_entry_field_html();

    html::span_end( 'month: ' . $this->fieldname );
  }
  #-----------------------------------------------------

  function day_entry_field() {

    echo NEWLINE . NEWLINE;

    html::span_start( 'class="day" id="' . $this->day_span_id . '"' );

    if( ! $this->suppress_display )
      echo $this->get_day_entry_field_html();

    html::span_end( 'day: ' . $this->fieldname );
  }
  #-----------------------------------------------------

  function get_year_entry_field_html() {

    $year = $this->get_property_value( $this->year_id );

    $html  = '<input type="input" name="' . $this->year_id . '" id="' . $this->year_id . '" value="';
    $html .= html::call_htmlentities( $year );
    $html .= '"';

    $html .= ' size="4" ';
    $html .= ' tabindex="1" ';

    $html .= 'onchange="js_check_value_is_numeric( this ); ' 
                      . $this->generate_sortable_dates_script . '()'
                      . $this->get_call_to_extra_onchange_script( $this->year_id ) # start with semi-colon
                      . '"';

    $html .= ' />';

    return $html;
  }
  #-----------------------------------------------------

  function get_month_entry_field_html() {

    $month = $this->get_property_value( $this->month_id );

    if( $month == NULL ) $month = 0;

    $html  = '<select name="' . $this->month_id . '" id="' . $this->month_id . '" ';
    $html .= ' tabindex="1" '; 

    $html .= 'onchange="' . $this->generate_sortable_dates_script . '()'
           . $this->get_call_to_extra_onchange_script( $this->month_id ) # start with semi-colon
           . '"';

    $html .= ' >';

    for( $i = 0; $i <= 12; $i++ ) {
      $display = $this->month_list[ $i ];

      $html .= '<option value="' . $i . '" ';
      if( $month == $i ) $html .= ' selected';
      $html .= '>';
      $html .= $display;
      $html .= '</option>';
    }

    $html .= '</select>';

    return $html;
  }
  #-----------------------------------------------------

  function get_day_entry_field_html() {

    $day = $this->get_property_value( $this->day_id );

    if( $day == NULL ) $day = 0;

    $html  = '<select name="' . $this->day_id . '" id="' . $this->day_id . '" ';
    $html .= ' tabindex="1" '; 

    $html .= 'onchange="' . $this->generate_sortable_dates_script . '()'
           . $this->get_call_to_extra_onchange_script( $this->day_id ) # start with semi-colon
           . '"';

    $html .= ' >';

    for( $i = 0; $i <= 31; $i++ ) {
      $display = '';
      if( $i > 0 ) $display = $i;

      $html .= '<option value="' . $i . '" ';
      if( $day == $i ) $html .= ' selected';
      $html .= '>';
      $html .= $display;
      $html .= '</option>';
    }

    $html .= '</select>';

    return $html;
  }
  #-----------------------------------------------------

  function make_complete_date( $year, $month, $day, $is_julian = FALSE ) {

    $year = trim( $year );
    $month = trim( $month );
    $day = trim( $day );

    if( strlen( $year )  > 0 ) $year  = intval( $year );
    if( strlen( $month ) > 0 ) $month = intval( $month );
    if( strlen( $day )   > 0 ) $day   = intval( $day );

    if( $year && ( $year < 1000 || $year > 9999 )) {
      $this->report_date_format_error( 'Year, if entered, must be four figures.' );
      return NULL;
    }

    if( ! $year ) $year = DTE_UNKNOWN_YEAR;

    if( ! $month ) $month = DTE_UNKNOWN_MONTH;

    $max_day_of_this_month = $this->get_max_day_of_month( $month, $year, $is_julian ); 

    if( ! $day ) $day = $max_day_of_this_month; 

    if( $day > $max_day_of_this_month ) {
      $this->report_date_format_error( "Maximum day for this month is $max_day_of_this_month." );
      return NULL;
    }

    if( $this->date_format == 'dd/mm/yyyy' )
      return str_pad( $day, 2, '0', STR_PAD_LEFT ) . '/' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '/' . $year;
    else
      return $year . '-' . str_pad( $month, 2, '0', STR_PAD_LEFT ) . '-' . str_pad( $day, 2, '0', STR_PAD_LEFT );
  }
  #----------------------------------------------------------------------------------

  function get_max_day_of_month( $month, $year = NULL, $is_julian = FALSE ) {

    switch( $month ) {
      case 9:  # 30 days hath September,
      case 4:  # April,
      case 6:  # June,
      case 11: # And November
        $max_day = 30;
        break;

      case 2:
        $max_day = 28;

        if( $year > 0 ) {
          if( $year % 4 == 0 ) {  # Leap year (BUT not if divisible by 100 unless also by 400, or Julian)

            if( $year % 400 == 0 ) 
              $max_day = 29;

            elseif( $year % 100 > 0 || $is_julian ) 
              $max_day = 29;
          }
        }
        break;

      default:
        $max_day = 31;
    }

    return $max_day;
  }
  #----------------------------------------------------------------------------------

  function report_date_format_error( $errmsg = 'Invalid date' ) {

    $this->failed_validation = TRUE;
    html::div_start( 'class="errmsg"' );
    echo $errmsg;
    html::div_end();
    html::new_paragraph();
  }
  #----------------------------------------------------------------------------------

  function is_postgres_timestamp( $the_date ) {  # overrides version from application entity

    if( ! parent::is_postgres_timestamp( $the_date )) {  # maybe using a year of 9999
      if( $this->string_starts_with( $the_date, DTE_UNKNOWN_YEAR )) {
        $the_date = '1000' . substr( $the_date, strlen( DTE_UNKNOWN_YEAR ));
      }
    }

    return parent::is_postgres_timestamp( $the_date );
  }
  #----------------------------------------------------------------------------------

  function get_day_fieldname( $fieldname ) {
    return $fieldname . '_day';
  }
  #----------------------------------------------------------------------------------

  function get_month_fieldname( $fieldname ) {
    return $fieldname . '_month';
  }
  #----------------------------------------------------------------------------------

  function get_year_fieldname( $fieldname ) {
    return $fieldname . '_year';
  }
  #----------------------------------------------------------------------------------

  function get_fieldset_id( $fieldname ) {

    return $fieldname . '_fieldset';
  }
  #----------------------------------------------------------------------------------

  function get_span_id( $fieldname ) {

    return $fieldname . '_span';
  }
  #----------------------------------------------------------------------------------

  function get_order_desc_id( $fieldname ) {

    return $fieldname . '_order_desc';
  }
  #----------------------------------------------------------------------------------

  function get_label_id( $fieldname ) {

    return $fieldname . '_label';
  }
  #----------------------------------------------------------------------------------

  function get_range_checkbox_id() {

    return $this->first_fieldname . '_is_range';
  }
  #----------------------------------------------------------------------------------

  function get_label( $range_checkbox_ticked, $is_first_field ) {

    $label = '';

    if( $range_checkbox_ticked ) {
      if( $is_first_field )
        $label = 'From: ';
      else
        $label = 'To: ';
    }
    else {
      if( $is_first_field )
        $label = 'Date: ';
    }

    return $label;
  }
  #----------------------------------------------------------------------------------

  function checkbox_onclick_script() {

    $script  = 'function ' . $this->checkbox_onclick_script_name . '( the_checkbox ) {' . NEWLINE;
    $script .= '  if( the_checkbox.checked == true ) {'                                 . NEWLINE;
    $script .= '    ' . $this->show_script_name . '();'                                 . NEWLINE;
    $script .= '  }'                                                                    . NEWLINE;
    $script .= '  else {'                                                               . NEWLINE;
    $script .= '    ' . $this->hide_script_name . '();'                                 . NEWLINE;
    $script .= '  }'                                                                    . NEWLINE;
    $script .= '  ' . $this->change_label_script_name . '( the_checkbox );'             . NEWLINE;
    $script .= '}'                                                                      . NEWLINE;

    return $script;
  }
  #----------------------------------------------------------------------------------

  function set_change_label_script( $first ) {

    $script = '';

    if( $first ) {
      $script  = 'function ' . $this->change_label_script_name . '( the_checkbox ) {'       . NEWLINE;
      $script .= '  var the_element;'                                                       . NEWLINE;
    }

    $script .= '  the_element = document.getElementById( "' . $this->label_id . '" );'      . NEWLINE;

    $label = $this->get_label( $range_checkbox_ticked = TRUE, $first );

    $script .= '  if( the_checkbox.checked == true ) {'                                     . NEWLINE;
    $script .= '    the_element.innerHTML = "' . $this->escape( $label ) . '";'             . NEWLINE;
    $script .= '  }'                                                                        . NEWLINE;

    $label = $this->get_label( $range_checkbox_ticked = FALSE, $first );

    $script .= '  else {'                                                                   . NEWLINE;
    $script .= '    the_element.innerHTML = "' . $this->escape( $label ) . '";'             . NEWLINE;
    $script .= '  }'                                                                        . NEWLINE;

    if( ! $first ) $script .= '}'                                                           . NEWLINE;

    $this->change_label_script .= $script;
  }
  #----------------------------------------------------------------------------------

  function get_property_value( $property_name ) {

    $property_name = trim( $property_name );
    if( ! $property_name ) return NULL;

    $property_value = $this->$property_name;

    return $property_value;
  }
  #----------------------------------------------------------------------------------

  function set_properties( $properties ) {

    $this->clear();

    if( ! is_array( $properties )) return;

    foreach( $properties as $property_name => $property_value ) {
      $this->$property_name = $property_value;
    }
  }
  #----------------------------------------------------------------------------------

  function list_properties_to_set( $date_fields ) {  # Make an empty list of year/month/day fields.
                                                     # The calling class will then fill in the values
                                                     # and pass the array back via set_properties().

    $properties = array();

    if( ! is_array( $date_fields )) return $properties;

    foreach( $date_fields as $fieldname ) {

      $this->set_ids_for_css( $fieldname );

      $year_fieldname  = $this->year_id;
      $month_fieldname = $this->month_id;
      $day_fieldname   = $this->day_id;

      $properties[ "$year_fieldname" ]  = NULL;
      $properties[ "$month_fieldname" ] = NULL;
      $properties[ "$day_fieldname" ]   = NULL;
    }

    if( count( $date_fields ) == 2 ) {
      $checkbox_fieldname = $this->range_checkbox_id;
      $properties[ "$checkbox_fieldname" ]  = NULL;
    }

    $std = $this->sortable_date_orig_calendar;
    $properties[ "$std" ] = NULL;

    $greg = $this->sortable_date_gregorian;
    $properties[ "$greg" ] = NULL;

    return $properties;
  }
  #----------------------------------------------------------------------------------

  function sortable_date_entry() {

    html::italic_start();
    echo 'Dates for ordering (these will normally be automatically generated as you enter day, month and year):';
    html::italic_end();
    html::new_paragraph();

    $this->sortable_date_field( $this->sortable_date_orig_calendar,
                                'In original calendar',
                                $this->get_property_value( $this->sortable_date_orig_calendar ),
                                $first = TRUE );

    echo ' ';

    $this->sortable_date_field( $this->sortable_date_gregorian,
                                'In Gregorian calendar',
                                $this->get_property_value( $this->sortable_date_gregorian ));
    echo ' ';

    $this->sortable_date_refresh_button();

    echo ' ';

    $this->sortable_date_manual_entry();
  }
  #----------------------------------------------------------------------------------

  function hidden_sortable_dates() {

    html::hidden_field( $this->sortable_date_orig_calendar,
                        $this->get_property_value( $this->sortable_date_orig_calendar ));

    html::hidden_field( $this->sortable_date_gregorian,
                        $this->get_property_value( $this->sortable_date_gregorian ));

    html::hidden_field( $this->sortable_date_manual_entry, NULL );
  }
  #----------------------------------------------------------------------------------

  function sortable_date_field( $fieldname, $label, $field_value, $first = FALSE ) {

    $parms = 'READONLY class="readonly_date"';

    if( ! $first ) html::span_start( 'class="nextfield"' );

    html::input_field( $fieldname, $label, $field_value,
                       FALSE, $size = STD_DATE_INPUT_FIELD_SIZE, $tabindex=0,
                       NULL, NULL, $input_parms = $parms );

    if( ! $first ) html::span_end( 'nextfield' );
  }
  #----------------------------------------------------------------------------------

  function sortable_date_manual_entry() {

    $script_name = 'change_' . $this->sortable_date_manual_entry;
    $script_name1 = $script_name . '1';

    $script  = 'function ' . $script_name1 . '( the_checkbox, input_field ) {'             . NEWLINE;
    $script .= '  if( the_checkbox.checked == true ) {'                                    . NEWLINE;
    $script .= '    input_field.readOnly = false;'                                         . NEWLINE;
    $script .= '    input_field.className = "";'                                           . NEWLINE;
    $script .= '    input_field.tabIndex = 1;'                                             . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '  else {'                                                                  . NEWLINE;
    $script .= '    input_field.readOnly = true;'                                          . NEWLINE;
    $script .= '    input_field.className = "readonly_date";'                              . NEWLINE;
    $script .= '    input_field.tabIndex = 0;'                                             . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '}'                                                                         . NEWLINE;

    html::write_javascript_function( $script );

    $script  = 'function ' . $script_name . '( the_checkbox ) {'                                      . NEWLINE;
    $script .= '  var orig_cal_field;'                                                                . NEWLINE;
    $script .= '  var greg_field;'                                                                    . NEWLINE;

    $script .= "  orig_cal_field = document.getElementById( '$this->sortable_date_orig_calendar'  );" . NEWLINE;
    $script .= "  ${script_name1}( the_checkbox, orig_cal_field );"                                   . NEWLINE;

    $script .= "  greg_field = document.getElementById( '$this->sortable_date_gregorian'  );"         . NEWLINE;
    $script .= "  ${script_name1}( the_checkbox, greg_field );"                                       . NEWLINE;

    $script .= '  if( the_checkbox.checked == true ) {'                                    . NEWLINE;
    $script .= '    orig_cal_field.focus();'                                               . NEWLINE;
    $script .= '  }'                                                                       . NEWLINE;
    $script .= '}'                                                                         . NEWLINE;

    html::write_javascript_function( $script );

    $parms = 'onclick="' . $script_name . '( this )"';

    #----

    html::span_start( 'class="nextfield"' );

    html::checkbox( $this->sortable_date_manual_entry, 'Enter manually', $is_checked = FALSE,
                    $value_when_checked = 1, FALSE, $tabindex=1, NULL, $parms );

    html::span_end( 'nextfield' );
  }
  #----------------------------------------------------------------------------------

  function write_sortable_date_value_script() {

    $script_name = $this->generate_sortable_dates_script . '_value';

    $script  = 'function ' . $script_name . '( year_val, month_val, day_val ) {' . NEWLINE;
    $script .= '  var max_day_of_month;'                                         . NEWLINE;

    $script .= '  if( year_val == "" ) {'                                        . NEWLINE;
    $script .= '    year_val = "' . DTE_UNKNOWN_YEAR . '";'                      . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  if( isNaN( parseInt( year_val ))) {'                           . NEWLINE;
    $script .= '    year_val = "' . DTE_UNKNOWN_YEAR . '";'                      . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  month_val=parseInt( month_val );'                              . NEWLINE;
    $script .= '  day_val=parseInt( day_val );'                                  . NEWLINE;

    $script .= '  if( month_val == 0 ) {'                                        . NEWLINE;
    $script .= '    month_val = ' . DTE_UNKNOWN_MONTH . ';'                      . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  switch( month_val ) {'                                         . NEWLINE;
    $script .= '    case 9:'                                                     . NEWLINE;
    $script .= '    case 4:'                                                     . NEWLINE;
    $script .= '    case 6:'                                                     . NEWLINE;
    $script .= '    case 11:'                                                    . NEWLINE;
    $script .= '      max_day_of_month = 30;'                                    . NEWLINE;
    $script .= '      break;'                                                    . NEWLINE;

    $script .= '    case 2:'                                                     . NEWLINE;
    $script .= '      max_day_of_month = 28;'                                    . NEWLINE;  # ignore leaps for now!
    $script .= '      break;'                                                    . NEWLINE;

    $script .= '    default:'                                                    . NEWLINE;
    $script .= '      max_day_of_month = 31;'                                    . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  if( day_val == 0 ) {'   . NEWLINE;
    $script .= '    day_val = max_day_of_month;'                                 . NEWLINE;
    $script .= '  }' . NEWLINE;

    $script .= '  if( day_val > max_day_of_month ) {'                            . NEWLINE;
    $script .= '    if( day_val != 29 || month_val != 2 || year_val%4 != 0 ) {'  . NEWLINE;
    $script .= '      day_val = max_day_of_month;'                               . NEWLINE;
    $script .= '    }'                                                           . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  if( day_val < 10 ) {'                                          . NEWLINE;
    $script .= '    day_val = "0" + day_val;'                                    . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  if( month_val < 10 ) {'                                        . NEWLINE;
    $script .= '    month_val = "0" + month_val;'                                . NEWLINE;
    $script .= '  }'                                                             . NEWLINE;

    $script .= '  return year_val + "-" + month_val + "-" + day_val;'            . NEWLINE;

    $script .= '}'                                                               . NEWLINE;

    html::write_javascript_function( $script );

    return $script_name;
  }
  #----------------------------------------------------------------------------------

  function write_sortable_dates_generation_script( $fields ) {

    $unknown_date = DTE_UNKNOWN_YEAR . '-' . DTE_UNKNOWN_MONTH . '-' . $this->get_max_day_of_month( DTE_UNKNOWN_MONTH );

    $greg_script_name = $this->write_gregorian_script();

    $value_script_name = $this->write_sortable_date_value_script();

    $script_name = $this->generate_sortable_dates_script;

    $script = 'function ' . $script_name . '() { ' . NEWLINE;
    $script .= '  var first_datestring = "";' . NEWLINE;
    $script .= '  var second_datestring = "";' . NEWLINE;
    $script .= '  var result = "";' . NEWLINE;

    $first = TRUE;

    foreach( $fields as $fieldname ) {
      $this->set_ids_for_css( $fieldname );

      if( $first ) {
        $first_year_id  = $this->year_id;
        $first_month_id = $this->month_id;
        $first_day_id   = $this->day_id;
      }
      else {
        $second_year_id  = $this->year_id;
        $second_month_id = $this->month_id;
        $second_day_id   = $this->day_id;
      }

      $first = FALSE;
    }

    $script .= "  var first_year_field  = document.getElementById( '$first_year_id' );"   . NEWLINE;
    $script .= "  var first_month_field = document.getElementById( '$first_month_id' );"  . NEWLINE;
    $script .= "  var first_day_field   = document.getElementById( '$first_day_id' );"    . NEWLINE;
    $script .= '  first_datestring = ' . $value_script_name . '( first_year_field.value, '
                                                            . '  first_month_field.value, '
                                                            . '  first_day_field.value );' . NEWLINE;
    if( count( $fields ) == 2 ) {
      $script .= "  var is_range = document.getElementById( '$this->range_checkbox_id' );"       . NEWLINE;
      $script .= '  if( is_range.checked ) {'                                                    . NEWLINE;
      $script .= "    var second_year_field  = document.getElementById( '$second_year_id' );"    . NEWLINE;
      $script .= "    var second_month_field = document.getElementById( '$second_month_id' );"   . NEWLINE;
      $script .= "    var second_day_field   = document.getElementById( '$second_day_id' );"     . NEWLINE;
      $script .= '    second_datestring = ' . $value_script_name . '( second_year_field.value, '
                                                                 . '  second_month_field.value, '
                                                                 . '  second_day_field.value );' . NEWLINE;

      $script .= '  }'                                                                           . NEWLINE;
    }

    $script .= '  if( second_datestring == "" || second_datestring == "' . $unknown_date . '" ) {' . NEWLINE;
    $script .= '    result = first_datestring;'                                                  . NEWLINE;
    $script .= '  }'                                                                             . NEWLINE;
    $script .= '  else {'                                                                        . NEWLINE;
    $script .= '    if( second_datestring > first_datestring || first_datestring == "" || first_datestring == "';
    $script .=        $unknown_date . '" ) {'                                                    . NEWLINE;
    $script .= '      result = second_datestring;'                                               . NEWLINE;
    $script .= '    }'                                                                           . NEWLINE;
    $script .= '    else {'                                                                      . NEWLINE;
    $script .= '      result = first_datestring;'                                                . NEWLINE;
    $script .= '    }'                                                                           . NEWLINE;
    $script .= '  }'                                                                             . NEWLINE;

    $script .= "  var result_field = document.getElementById( '$this->sortable_date_orig_calendar' );"   
                                                                                                 . NEWLINE;

    $script .= "  var manual_entry = document.getElementById( '$this->sortable_date_manual_entry' );"   
                                                                                                 . NEWLINE;
    $script .= '  if( manual_entry.checked == false ) {'                                         . NEWLINE;

    #----------------------------------------------
    # Generate 'sortable date in original calendar'
    #----------------------------------------------
    $script .= "    result_field.value = result;"                                                . NEWLINE;

    #----------------------------------------------
    # Generate 'Gregorian sortable' field
    #----------------------------------------------
    $script .= '    ' . $greg_script_name . '( result );'                                        . NEWLINE; 

    $script .= '  }'                                                                             . NEWLINE;

    $script .= '} '                                                                              . NEWLINE;

    html::write_javascript_function( $script );
  }
  #----------------------------------------------------------------------------------

  function write_gregorian_script() {

    $script_name = $this->generate_sortable_dates_script . '_greg';

    $script  = 'function ' . $script_name . '( sortable_date ) { '               . NEWLINE;

    $script .= '  var date_parts = sortable_date.split("-");'                    . NEWLINE;
    $script .= '  var partcount = date_parts.length;'                            . NEWLINE;
    $script .= '  if( partcount != 3 ) {'                                        . NEWLINE;
    $script .= '    alert("Invalid date value in " + partcount + " parts");'     . NEWLINE;
    $script .= '    return;'                                                     . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  var year_val = date_parts[0];'                                 . NEWLINE;
    $script .= '  var month_val = date_parts[1] '                                . NEWLINE;
    $script .= '  var day_val = date_parts[2];'                                  . NEWLINE;

    $script .= '  if( month_val.substr( 0, 1 ) == "0" ) { '                      . NEWLINE;
    $script .= '    month_val = month_val.substr( 1, 1 );'                       . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  if( day_val.substr( 0, 1 ) == "0" ) { '                        . NEWLINE;
    $script .= '    day_val = day_val.substr( 1, 1 );'                           . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  year_val = parseInt( year_val );'                              . NEWLINE;
    $script .= '  month_val = parseInt( month_val );'                            . NEWLINE;
    $script .= '  day_val = parseInt( day_val );'                                . NEWLINE;

    $script .= '  var max_day_of_month = 31;'                                    . NEWLINE;
    $script .= '  switch( month_val ) {'                                         . NEWLINE;
    $script .= '    case 9:'                                                     . NEWLINE;
    $script .= '    case 4:'                                                     . NEWLINE;
    $script .= '    case 6:'                                                     . NEWLINE;
    $script .= '    case 11:'                                                    . NEWLINE;
    $script .= '      max_day_of_month = 30;'                                    . NEWLINE;
    $script .= '      break;'                                                    . NEWLINE;
    $script .= '    case 2:'                                                     . NEWLINE;
    $script .= '      max_day_of_month = 28;'                                    . NEWLINE;
    $script .= '      if( year_val % 4 == 0 ) {'                                 . NEWLINE;
    $script .= '        if( year_val % 100 > 0 || year_val % 400 == 0 ) {'       . NEWLINE;
    $script .= '          max_day_of_month = 29;'                                . NEWLINE;
    $script .= '        } '                                                      . NEWLINE;
    $script .= '      } '                                                        . NEWLINE;
    $script .= '      break;'                                                    . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  var diffdays = 0;'                                             . NEWLINE;

    if( $this->calendar_field ) {
      $script .= '  var numcalendars = ' . count($this->calendar_list) . ';'       . NEWLINE;
      $script .= '  var calendarcode = "";'                                        . NEWLINE;
      $script .= '  var calendarradioname;'                                        . NEWLINE;
      $script .= '  var calendarradiofield;'                                       . NEWLINE;
      $script .= '  for( i = 1; i <= numcalendars; i++ ) {'                        . NEWLINE;
      $script .= '    calendarradioname = "' .  $this->calendar_field . '" + i;'   . NEWLINE;
      $script .= '    calendarradiofield = document.getElementById( calendarradioname );' 
                                                                                   . NEWLINE;
      $script .= '    if( calendarradiofield != null ) {'                          . NEWLINE;
      $script .= '      if( calendarradiofield.checked == true ) {'                . NEWLINE;
      $script .= '        calendarcode = calendarradiofield.value;'                . NEWLINE;
      $script .= '        break;'                                                  . NEWLINE;
      $script .= '      } '                                                        . NEWLINE;
      $script .= '    } '                                                          . NEWLINE;
      $script .= '  } '                                                            . NEWLINE;
      
      $script .= '  if( calendarcode == "' . CALENDAR_TYPE_JULIAN_MAR . '"' 
              .  '  ||  calendarcode == "' . CALENDAR_TYPE_JULIAN_JAN . '" ) {'    . NEWLINE;

      $script .= '    diffdays = 10;'                                              . NEWLINE;

      $script .= '    if( year_val > 1700 ) {'                                     . NEWLINE;
      $script .= '      diffdays = 11;'                                            . NEWLINE;
      $script .= '    } '                                                          . NEWLINE;
      $script .= '    else if( year_val == 1700 && month_val > 2 ) {'              . NEWLINE;
      $script .= '      diffdays = 11;'                                            . NEWLINE;
      $script .= '    } '                                                          . NEWLINE;
      $script .= '    else if( year_val==1700 && month_val==2 && day_val==29) {'   . NEWLINE;
      $script .= '      diffdays = 11;'                                            . NEWLINE;
      $script .= '    } '                                                          . NEWLINE;
      $script .= '  } '                                                            . NEWLINE;
    }

    $script .= '  var new_year = parseInt( year_val );'                          . NEWLINE;
    $script .= '  var new_month = parseInt( month_val );'                        . NEWLINE;
    $script .= '  var new_day = parseInt( day_val ) + parseInt( diffdays );'     . NEWLINE;


    $script .= '  if( new_day > max_day_of_month ) {'                            . NEWLINE;
    $script .= '    new_day = new_day - max_day_of_month;'                       . NEWLINE;
    $script .= '    new_month++;'                                                . NEWLINE;
    $script .= '    if( new_month > 12 ) {'                                      . NEWLINE;
    $script .= '      new_month = 1;'                                            . NEWLINE;
    $script .= '      new_year++;'                                               . NEWLINE;
    $script .= '      if( new_year > ' . DTE_UNKNOWN_YEAR . ' ) {'               . NEWLINE;
    $script .= '        new_year = ' . DTE_UNKNOWN_YEAR . ';'                    . NEWLINE;
    $script .= '      } '                                                        . NEWLINE;
    $script .= '    } '                                                          . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  if( new_day < 10 ) {'                                          . NEWLINE;
    $script .= '    new_day = "0" + new_day;'                                    . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  if( new_month < 10 ) {'                                        . NEWLINE;
    $script .= '    new_month = "0" + new_month;'                                . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;

    $script .= '  var result = new_year + "-" + new_month + "-" + new_day;'      . NEWLINE;
    $script .= '  var result_field = document.getElementById( "' .  $this->sortable_date_gregorian . '" );'
                                                                                 . NEWLINE;
    $script .= '  if( result_field != null ) {'                                  . NEWLINE;
    $script .= '  result_field.value = result;'                                  . NEWLINE;
    $script .= '  } '                                                            . NEWLINE;
    $script .= '} '                                                              . NEWLINE;

    html::write_javascript_function( $script );

    return $script_name;
  }
  #----------------------------------------------------------------------------------

  function calendar_selection_within_main_fieldset() { # alternatively you could have a separate field elsewhere

    $fieldname = $this->calendar_field;
    if( ! $fieldname ) return;

    $field_value = $this->$fieldname;
    $this->calendar_selection_field( $fieldname, $field_value, $compact = TRUE );
  }
  #----------------------------------------------------------------------------------

  function calendar_selection_field( $fieldname = 'calendar', $selected_calendar = NULL, $compact = FALSE ) {

    $list_class = 'calendartypes';
    if( $compact ) $list_class = 'compact_calendar_list';

    html::ulist_start( 'class="' . $list_class . '"' );

    $i = 0;
    foreach( $this->calendar_list as $value => $label ) {
      $i++;
      html::listitem_start();

      html::radio_button( $fieldname, 
                          $label, 
                          $value_when_checked = $value, 
                          $current_value = $selected_calendar, 
                          $tabindex=1, 
                          $button_instance=$i, 
                          $script=NULL ) ;
      html::listitem_end();
    }

    html::ulist_end();

    if( $compact ) {
      echo LINEBREAK;
      html::horizontal_rule();
    }
  }
  #----------------------------------------------------------------------------------

  function sortable_date_refresh_button() {

    html::span_start( 'class="nextfield"' );

    $script_name = $this->sortable_date_refresh . '_onclick';

    $script  = 'function ' . $script_name . '() { '                                      . NEWLINE;
    $script .= '  var manual_entry_checkbox;'                                           . NEWLINE;
    $script .= '  var checked;'                                                         . NEWLINE;

    $script .= "  manual_entry_checkbox = document.getElementById( '$this->sortable_date_manual_entry' );" 
                                                                                        . NEWLINE;
    $script .= "  checked = manual_entry_checkbox.checked;"                             . NEWLINE;
    $script .= "  manual_entry_checkbox.checked = false;"                               . NEWLINE;

    $script .= '  ' . $this->generate_sortable_dates_script . '();'                     . NEWLINE;

    $script .= "  manual_entry_checkbox.checked = checked;"                             . NEWLINE;
    $script .= '} '                                                                      . NEWLINE;

    html::write_javascript_function( $script );
    html::button( $this->sortable_date_refresh, 'Refresh', $tabindex=1,
                  'onclick="' . $script_name . '()"  class="refreshbutton" ' );

    html::span_end();
  }
  #----------------------------------------------------------------------------------

  function get_month_name( $month_number = NULL ) {

    $month_name = NULL;

    foreach( $this->month_list as $number => $name ) {
      if( $number == $month_number ) {
        $month_name = $name;
        break;
      }
    }

    return $month_name;
  }
  #----------------------------------------------------------------------------------

  function extra_date_fields() { # a method that can be overridden by child classes e.g. Islamic Date Entity
  }
  #----------------------------------------------------------------------------------

  function get_call_to_extra_onchange_script( $changed_fieldname ) { # a method that can be overridden by child classes 
                                                                     # e.g. Islamic Date Entity
    return '';
  }
  #----------------------------------------------------------------------------------

  function get_call_to_extra_onclick_script( $checkbox_id ) { # a method that can be overridden by child classes 
                                                              # e.g. Islamic Date Entity
    return '';
  }
  #----------------------------------------------------------------------------------
}
?>
