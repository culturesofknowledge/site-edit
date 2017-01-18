<?php
/*
 * PHP class for handling users of the offline data collection tool.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'TOOL_DB_USERNAME', 'cofkcontributor' );
define( 'TOOL_USERNAME_LOGIN_FIELD_SIZE', 60 );
define( 'TOOL_USER_TEXT_FIELDS_SIZE', 100 );

class Tool_User extends User {

  #-----------------------------------------------------

  function Tool_User( &$db_connection ) { 

    #-------------------------------------------------------------------
    # Check we have got a valid (but minimal) connection to the database
    # Passed in by first script called from Apache document root.
    #-------------------------------------------------------------------
    $this->User( $db_connection );
  }
  #-----------------------------------------------------

  function set_user( $username = NULL, $being_edited_by_logged_in_user = FALSE ) {
    
    $this->clear(); # clear all properties except DB connection
    if( $username == NULL ) return NULL;

    if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );
    $select_user_func = $this->project_obj->proj_database_function_name( $core_function_name = 'select_user', 
                                                                         $include_collection_code = FALSE );
    $statement = "select $select_user_func( '$username' )";  # selects *, but password is blanked out
    $this->user_row = NULL;
    $this->user_row = $this->db_select_one_value( $statement );

    if( $this->user_row ) { # selected from the main 'users' table, but it may just be a string of commas
      $this->parse_user_row();
    }

    $this->write_session_parm( 'user_of_main_db', 0 );
    if( ! $this->username ) { # check 'tool users'
      $statement = 'select tool_user_email as username, tool_user_surname as surname, tool_user_forename as forename,'
                 . ' tool_user_email as email from ' . $this->project_obj->proj_collect_tool_user_tablename()
                 . " where tool_user_email = '$username'";
      $this->db_select_into_properties( $statement );
    }
    else
      $this->write_session_parm( 'user_of_main_db', 1 );

    $this->person_name = $this->get_person_name();

    if( ! $being_edited_by_logged_in_user ) {
      $this->write_session_parm( 'user_email', $this->email );
      $this->write_session_parm( 'person_name', $this->person_name );
    }
    return $this->username;
  }
  #-----------------------------------------------------

  function set_db_connection( $the_username ) {

    # For upload from data collection tool, we will use a generic database user.
    $this->db_connection = new DBQuery( TOOL_DB_USERNAME );
  }
  #-----------------------------------------------------

  function check_md5_username_and_pw() { # overrides parent method

    $login_result = parent::check_md5_username_and_pw();

    if( $login_result == LOGIN_FAILED_BAD_USR_OR_PW ) { # not in main users table, so try tool users

      $decode = NULL;
      if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );

      $statement = 'select tool_user_email from ' . $this->project_obj->proj_collect_tool_user_tablename() 
                 . " where md5( md5( tool_user_email ) || '$this->login_token' ) = '" . $this->md5_username . "' "
                 . " and md5( tool_user_pw || '$this->login_token' ) = '" . $this->md5_pw . "'";

      $decode = $this->db_select_one_value( $statement );
      if( $decode ) return LOGIN_SUCCESS;
    }

    return $login_result;
  }
  #-----------------------------------------------------

  function decode_username() {

    $this->username = '';
    parent::decode_username();

    if( ! $this->username ) { # check the tool users table instead

      if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );

      $statement = 'select tool_user_email from ' . $this->project_obj->proj_collect_tool_user_tablename() 
                 . " where md5( md5( tool_user_email ) || '$this->login_token' ) = '" . $this->md5_username . "'";

      $this->username = $this->db_select_one_value( $statement );
    }
  }
  #-----------------------------------------------------

  function check_session() {

    $session_result = parent::check_session();

    if( $session_result == SESSION_NOT_FOUND ) { # try checking 'tool sessions' 

      if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );
      $func = $this->project_obj->proj_database_function_name( 'check_collect_tool_session', 
                                                               $include_collection_code = FALSE );
      $statement = "select $func( '$this->username', '$this->session_token' )";
      $session_result = $this->db_select_one_value( $statement );
    }

    return $session_result;
  }
  #-----------------------------------------------------

  function save_session( $old_session_token, $new_session_token ) {

    if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );

    $statement = 'select tool_user_id from ' . $this->project_obj->proj_collect_tool_user_tablename() 
               . " where tool_user_email = '" . $this->escape( $this->username ) . "'";
    $tool_user_id = $this->project_obj->db_select_one_value( $statement );

    if( ! $tool_user_id )
      $session_saved = parent::save_session( $old_session_token, $new_session_token );

    else {
      $func = $this->project_obj->proj_database_function_name( 'save_collect_tool_session_data', 
                                                               $include_collection_code = FALSE );
      $statement = "select $func( '$this->username', '$old_session_token', '$new_session_token' )";
      $session_saved = $this->db_select_one_value( $statement );
    }

    return $session_saved;
  }
  #-----------------------------------------------------

  function request_login_token( $errmsg = NULL, $suppress_header = FALSE ) { # overrides parent method

    # The following 'test functions' check Javascript is enabled. This is left over from pre-https days, and strictly
    # speaking is probably no longer necessary, but perhaps helps keep the passwords a bit more secure in their table.

    $this->login_page_start( 'write_javascript_test_functions', $suppress_header ); 
                                                                                    

    #---- Start existing users ----#
    HTML::form_start();
    if( $errmsg ) {
      HTML::h3_start();
      echo 'The system could not log you in.'; 
      HTML::h3_end();
      echo $errmsg;
      HTML::new_paragraph();
    }
    else {
      HTML::h3_start();
      echo 'Already registered to use the ' . CFG_SYSTEM_TITLE . '?'; 
      HTML::h3_end();
      echo 'Please log in: ';
    }
    HTML::submit_button( 'login_button', 'Log in', 1 /*tabindex*/, ' onclick="check_js_enabled()" ' );

    HTML::hidden_field( LOGIN_REQUEST, $this->create_login_request() );
    HTML::hidden_field( 'focus_form',  'user_login' );
    HTML::hidden_field( 'focus_field', 'raw_usr' );

    $this->remember_get_parms(); # In case a record ID has been passed in, then after login
                                 # you can go straight to that record.

    $this->forgot_password_link(); # offer reset if they have forgotten password 

    HTML::form_end();
    #---- End existing users ----#

    #---- Start new users ----#
    HTML::new_paragraph();
    HTML::form_start( $class_name = 'tool_user', $method_name = 'register' );
    HTML::h3_start();
    echo 'Not yet registered?'; 
    HTML::h3_end();
    echo 'Please register now: ';
    HTML::submit_button( 'register_button', 'Register', 1 /*tabindex*/ );
    HTML::form_end();
    #---- End new users ----#

    HTML::new_paragraph();
    $this->login_page_end();
  }
  #-----------------------------------------------------
  function get_username_field_size() {  # overrides parent method from User class
    return TOOL_USERNAME_LOGIN_FIELD_SIZE;
  }
  #-----------------------------------------------------

  function login() {  # overrides parent method from User class

    $class_name = NULL;
    $method_name = NULL;
    $option = NULL;
    $currently_registering = FALSE;

    if( $this->parm_found_in_post( 'class_name' )) {
      $class_name = strtolower( $this->read_post_parm( 'class_name' ));
    }
    if( $this->parm_found_in_post( 'method_name' )) {
      $method_name = strtolower( $this->read_post_parm( 'method_name' ));
    }

    if( $class_name == 'tool_user' ) {
      if( $method_name == 'register' || $method_name == 'create_registration' 
      ||  $method_name == 'reset_password' ) {
        if( ! $this->parm_found_in_post( 'cancel_button' ))
          $currently_registering = TRUE;
      }
    }
    elseif( $this->parm_found_in_get( 'option' ) && ! $class_name && ! $method_name ) {
      $option = $this->read_get_parm( 'option' );
      if( $option == 'forgotpass' ) {
        $currently_registering = TRUE;
        $method_name = 'forgot_password';
      }
    }

    if( $currently_registering ) {
      $this->$method_name();
    }
    else {
      parent::login();  # go through the normal login procedure
    }
  }
  #-----------------------------------------------------

  function register( $err_msgs = NULL ) {

    $this->registration_page_start();

    if( is_array( $err_msgs ) && count( $err_msgs ) > 0 ) {
      HTML::div_start( 'class="warning"' );
      foreach( $err_msgs as $err_msg ) {
        echo $err_msg;
        HTML::new_paragraph();
      }
      HTML::div_end();
    }
 
    HTML::form_start( $class_name = 'tool_user', $method_name = 'create_registration' );

    HTML::italic_start();
    echo 'All fields must be completed.';
    HTML::italic_end();

    HTML::div_start( 'class="dataentrytextfields"' );

    $username = $this->read_post_parm( 'username' );
    $email_address = $this->read_post_parm( 'email_address' );
    $surname  = $this->read_post_parm( 'surname' );
    $forename = $this->read_post_parm( 'forename' );

    HTML::new_paragraph();
    HTML::h3_start();
    echo 'Email address:';
    HTML::h3_end();

    HTML::italic_start();
    echo 'Your email address will be your username on this system.';
    HTML::italic_end();
    HTML::new_paragraph();
    HTML::input_field( 'username', 'Enter a valid email address', $username, FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );

    HTML::new_paragraph();
    HTML::input_field( 'email_address', 'Confirm email address', $email_address, FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );

    HTML::new_paragraph();

    HTML::h3_start();
    echo 'Name:';
    HTML::h3_end();
    HTML::input_field( 'surname', 'Surname', $surname );

    HTML::new_paragraph();
    HTML::input_field( 'forename', 'Forename', $forename );

    HTML::new_paragraph();
    HTML::h3_start();
    echo 'Password:';
    HTML::h3_end();
    HTML::password_field( 'pass1', 'Choose a password', NULL );

    HTML::new_paragraph();
    HTML::password_field( 'pass2', 'Confirm password', NULL );

    HTML::div_end();

    HTML::new_paragraph();
    HTML::span_start( 'class="dataentrytextfields"' );
    HTML::submit_button( 'save_button', 'Save' );
    HTML::span_end();
    HTML::submit_button( 'cancel_button', 'Cancel' );
    HTML::new_paragraph();

    HTML::form_end();
    $this->registration_page_end();
  }
  #-----------------------------------------------------

  function registration_page_start() {

    HTML::html_start();
    HTML::html_head_start();
    HTML::write_stylesheet();
    $this->menu_obj->page_body_start();
    $this->menu_obj->page_head( 'User Registration', TRUE ); # TRUE = suppress breadcrumb trail
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function registration_page_end() {

    $this->menu_obj->page_foot( $suppress_breadcrumbs = TRUE );
    $this->menu_obj->page_body_end();
    HTML::html_end();
  }
  #-----------------------------------------------------

  function create_registration() {

    #-----------------------------
    # Validate the details entered
    #-----------------------------
    $failed_validation = FALSE;
    $err_msgs = array();
    $fieldnames = array( 'username' => 'Email address',
                         'email_address' => 'Confirmation of email address',
                         'surname'  => 'Surname',
                         'forename' => 'Forename',
                         'pass1' => 'Password',
                         'pass2' => 'Confirmation of password' );

    foreach( $fieldnames as $fieldname => $label ) {
      $this->$fieldname = trim( $this->read_post_parm( $fieldname ));
      if( strlen( $this->$fieldname ) == 0 ) {
        $failed_validation = TRUE;
        $err_msgs[] = $label . ' is a mandatory field and must be entered.';
      }
    }

    if( $this->username && $this->email_address && $this->username != $this->email_address ) {
      $failed_validation = TRUE;
      $err_msgs[] = 'Email address and confirmation do not match. Please enter the same in both fields.';
    }

    if( $this->pass1 && $this->pass2 && $this->pass1 != $this->pass2 ) {
      $failed_validation = TRUE;
      $err_msgs[] = 'Password and confirmation do not match. Please enter the same in both fields.';
    }

    if( $failed_validation ) {
      $this->register( $err_msgs );
      return;
    }

    #--------------------------
    # Save the new user details
    #--------------------------
    $tool_user_connection = new DBQuery( TOOL_DB_USERNAME );
    if( ! $this->project_obj ) $this->project_obj = new Project( $tool_user_connection );
    $tool_user_table = $this->project_obj->proj_collect_tool_user_tablename();

    $statement = 'insert into ' . $tool_user_table 
               . ' (tool_user_email, tool_user_surname, tool_user_forename, tool_user_pw) values ( '
               . "'" . $this->escape( $this->username ) . "', "
               . "'" . $this->escape( $this->surname ) . "', "
               . "'" . $this->escape( $this->forename ) . "', "
               . "'" . md5( $this->pass1 ) . "'" 
               . ' ) ';
    $this->project_obj->db_run_query( $statement );

    $this->let_user_in();
  }
  #-----------------------------------------------------

  function look_up_details_of_user( $username, $required_field ) {

    # Normally, the method set_user() will set the session username.
    # But if one user (a supervisor) is looking at a different user, don't set the session variables.

    $my_username = $this->db_get_username();
    if( $my_username == $username )
      $looking_at_different_user = FALSE;
    else
      $looking_at_different_user = TRUE;
    
    if( $this->username != $username ) $this->set_user( $username, $looking_at_different_user );
    if( $this->username != $username ) die( 'Invalid user.' );

    return $this->$required_field;
  }
  #-----------------------------------------------------

  function look_up_person_name( $username ) {

    return $this->look_up_details_of_user( $username, $required_field = 'person_name' );
  }
  #-----------------------------------------------------

  function look_up_email_address( $username ) {

    return $this->look_up_details_of_user( $username, $required_field = 'email' );
  }
  #-----------------------------------------------------

  function edit_self_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' .forms_side_by_side form {'                              . NEWLINE; 
    echo '   display: inline; '                                     . NEWLINE;
    echo '   float:left; '                                          . NEWLINE;
    echo '   margin-right: 10px; '                                  . NEWLINE;
    echo '   margin-bottom: 10px; '                                 . NEWLINE;
    echo '   border-style: solid; '                                 . NEWLINE;
    echo '   border-width: 1px; '                                   . NEWLINE;
    echo '   border-color: ' . HTML::get_contrast2_colour() . ';'   . NEWLINE;
    echo '   padding: 10px;'                                        . NEWLINE;
    echo '   height: 16em;'                                         . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .forms_side_by_side br { '                               . NEWLINE;
    echo '   clear: left; '                                         . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .forms_side_by_side div.fields_div {'                    . NEWLINE; 
    echo '   height: 9em;'                                          . NEWLINE;
    echo ' }'                                                       . NEWLINE;

    echo ' .forms_side_by_side div.button_div {'                       . NEWLINE; 
    echo '   background-color: ' . HTML::get_highlight2_colour() . ';' . NEWLINE;
    echo '   margin-top: 15px; '                                       . NEWLINE;
    echo '   font-size: 11pt; '                                        . NEWLINE;
    echo '   text-align: right; '                                      . NEWLINE;
    echo ' }'                                                          . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #-----------------------------------------------------

  function edit_self() {

    $this->clear();
    $username = $this->read_session_parm( 'username' );
    if( ! $username ) die( 'Invalid input.' );

    if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );
    $tool_user_tablename = $this->project_obj->proj_collect_tool_user_tablename();
    $this->tool_user_tablename = $tool_user_tablename;
    $statement = "select * from $tool_user_tablename where tool_user_email = '$username'" ;
    $this->db_select_into_properties( $statement );
    if( ! $this->tool_user_id ) die( 'Invalid input.' );

    $this->edit_self_stylesheet();

    HTML::div_start( 'class="forms_side_by_side"' );  # we'll have name-change and password-change forms side by side

    $this->password_change_form();

    $this->name_change_form();

    $this->username_change_form();

    echo LINEBREAK;
    HTML::div_end();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------

  function name_change_form() {

    HTML::form_start( 'tool_user', 'save_names' );
    HTML::h3_start();
    echo 'Change surname or forename:';
    HTML::h3_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="dataentrytextfields fields_div"' );

    HTML::input_field( 'surname', 'Surname', $this->tool_user_surname );
    HTML::new_paragraph();

    HTML::input_field( 'forename', 'Forename', $this->tool_user_forename );
    HTML::new_paragraph();

    HTML::hidden_field( 'tool_user_id', $this->tool_user_id );
    HTML::hidden_field( 'tool_user_email', $this->tool_user_email );

    HTML::div_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="button_div"' );
    echo 'Save change of name: ';
    HTML::submit_button( 'save_names_button', 'Save' );
    HTML::div_end();

    HTML::form_end();
  }
  #-----------------------------------------------------

  function password_change_form() {

    HTML::form_start( 'tool_user', 'save_password' );
    HTML::h3_start();
    echo 'Change password:';
    HTML::h3_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="dataentrytextfields fields_div"' );

    HTML::password_field( 'oldpass', 'Current password', NULL );
    HTML::new_paragraph();

    HTML::password_field( 'pass1', 'New password', NULL );
    HTML::new_paragraph();

    HTML::password_field( 'pass2', 'Confirm new password', NULL );

    HTML::hidden_field( 'tool_user_id', $this->tool_user_id );
    HTML::hidden_field( 'tool_user_email', $this->tool_user_email );

    HTML::div_end();
    HTML::new_paragraph();

    HTML::div_start( 'class="button_div"' );
    echo 'Save new password: ';
    HTML::submit_button( 'save_names_button', 'Save' );
    HTML::div_end();

    HTML::form_end();
  }
  #-----------------------------------------------------

  function username_change_form() {

    HTML::form_start( 'tool_user', 'save_username' );
    HTML::h3_start();
    echo 'Change email address:';
    HTML::h3_end();
    HTML::new_paragraph();


    HTML::div_start( 'class="dataentrytextfields fields_div"' );
    HTML::italic_start();
    echo 'Your new email address will become your username on this system.';
    HTML::italic_end();
    HTML::new_paragraph();

    HTML::input_field( 'username', 'New email address', $this->tool_user_email, FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );
    HTML::new_paragraph();

    HTML::input_field( 'email_address', 'Confirm new email address', $this->tool_user_email, 
                       FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );

    HTML::hidden_field( 'tool_user_id', $this->tool_user_id );
    HTML::hidden_field( 'tool_user_email', $this->tool_user_email );

    HTML::div_end();

    HTML::div_start( 'class="button_div"' );
    echo 'Save new email address: ';
    HTML::submit_button( 'save_email_button', 'Save' );
    HTML::div_end();

    HTML::form_end();
  }
  #-----------------------------------------------------

  function get_tool_user_into_properties() {

    $this->clear();

    $tool_user_email = $this->read_post_parm( 'tool_user_email' );
    if( ! $tool_user_email ) die( 'Invalid input.' );

    $tool_user_id = $this->read_post_parm( 'tool_user_id' );
    if( ! $tool_user_id ) die( 'Invalid input.' );

    if( ! $this->project_obj ) $this->project_obj = new Project( $this->db_connection );
    $this->tool_user_tablename = $this->project_obj->proj_collect_tool_user_tablename();

    $statement = "select * from $this->tool_user_tablename where tool_user_id = $tool_user_id"
               . " and tool_user_email = '$tool_user_email'";
    $this->db_select_into_properties( $statement );

    if( ! $this->tool_user_id || ! $this->tool_user_email ) 
      die( 'Invalid input.' );
  }
  #-----------------------------------------------------

  function save_names() {

    $this->get_tool_user_into_properties();

    $surname = $this->read_post_parm( 'surname' );
    $forename = $this->read_post_parm( 'forename' );

    $failed_validation = FALSE;
    $err_msg = '';

    if( trim( $surname ) == '' )  {
      $err_msg = 'Error: surname must be entered.';
      $failed_validation = TRUE;
    }

    if( trim( $forename ) == '' )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: forename must be entered.';
      $failed_validation = TRUE;
    }

    if( $failed_validation ) {
      HTML::div_start( 'class="errmsg"' );
      echo $err_msg;
      echo LINEBREAK;
      echo 'Reverting to original values...';
      HTML::div_end();
      HTML::new_paragraph();
    }
    else {
      if( strlen( $surname ) > TOOL_USER_TEXT_FIELDS_SIZE ) 
        $surname = substr( $surname, 0, TOOL_USER_TEXT_FIELDS_SIZE );   
      if( strlen( $forename ) > TOOL_USER_TEXT_FIELDS_SIZE ) 
        $forename = substr( $forename, 0, TOOL_USER_TEXT_FIELDS_SIZE );   

      $statement = "update $this->tool_user_tablename"
                 . " set tool_user_surname = '" . $this->escape( $surname ) . "', "
                 . " tool_user_forename = '" . $this->escape( $forename ) . "' "
                 . " where tool_user_id = $this->tool_user_id"
                 . " and tool_user_email = '$this->tool_user_email'";
      $this->db_run_query( $statement );

      echo $this->get_datetime_now_in_words() . '. Change of name has been saved.';
    }

    $this->edit_self();
  }
  #-----------------------------------------------------

  function save_password() {

    $this->get_tool_user_into_properties();

    $oldpass = $this->read_post_parm( 'oldpass' );
    $pass1 = $this->read_post_parm( 'pass1' );
    $pass2 = $this->read_post_parm( 'pass2' );

    $failed_validation = FALSE;
    $err_msg = '';

    if( trim( $oldpass ) == '' )  {
      $err_msg = 'Error: current password must be entered.';
      $failed_validation = TRUE;
    }

    elseif( md5( $oldpass ) != $this->tool_user_pw )  {
      $err_msg = 'Error: current password is incorrect.';
      $failed_validation = TRUE;
    }

    if( trim( $pass1 ) == '' )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new password must be entered.';
      $failed_validation = TRUE;
    }

    if( trim( $pass2 ) == '' )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: confirmation of new password must be entered.';
      $failed_validation = TRUE;
    }

    if( $pass1 && $pass2 && $pass1 != $pass2 )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new password and confirmation do not match.';
      $failed_validation = TRUE;
    }

    if( strlen( $surname ) > TOOL_USER_TEXT_FIELDS_SIZE ) {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new password is too long.';
      $failed_validation = TRUE;
    }

    if( $failed_validation ) {
      HTML::div_start( 'class="errmsg"' );
      echo $err_msg;
      HTML::div_end();
      HTML::new_paragraph();
    }
    else {
      $statement = "update $this->tool_user_tablename"
                 . " set tool_user_pw = '" . md5( $pass1 ) . "'"
                 . " where tool_user_id = $this->tool_user_id"
                 . " and tool_user_email = '$this->tool_user_email'";
      $this->db_run_query( $statement );

      echo $this->get_datetime_now_in_words() . '. New password has been saved.';
    }

    $this->edit_self();
  }
  #-----------------------------------------------------

  function save_username() {

    $this->get_tool_user_into_properties();

    $username = $this->read_post_parm( 'username' );
    $email_address = $this->read_post_parm( 'email_address' );

    $failed_validation = FALSE;
    $err_msg = '';

    if( trim( $username ) == '' )  {
      $err_msg .= 'Error: new email address must be entered.';
      $failed_validation = TRUE;
    }

    if( trim( $email_address ) == '' )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: confirmation of new email address must be entered.';
      $failed_validation = TRUE;
    }

    if( $username && $email_address && $username != $email_address )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new email address and confirmation do not match.';
      $failed_validation = TRUE;
    }

    elseif( strlen( $email_address ) > TOOL_USER_TEXT_FIELDS_SIZE ) {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new email address is too long.';
      $failed_validation = TRUE;
    }

    $statement = "select tool_user_id from $this->tool_user_tablename"
               . " where tool_user_email = '" . $this->escape( $username ) . "'"
               . " and tool_user_id != $this->tool_user_id";
    $other_tool_user_id = $this->db_select_one_value( $statement );
    if( $other_tool_user_id ) {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: new email address is already in use.';
      $failed_validation = TRUE;
    }

    if( $failed_validation ) {
      HTML::div_start( 'class="errmsg"' );
      echo $err_msg;
      echo LINEBREAK . 'Reverting to original value...';
      HTML::div_end();
      HTML::new_paragraph();
    }
    else {
      $statement = "update $this->tool_user_tablename"
                 . " set tool_user_email = '" . $this->escape( $username ) . "' "
                 . " where tool_user_id = $this->tool_user_id"
                 . " and tool_user_email = '$this->tool_user_email'";
      $this->db_run_query( $statement );

      $this->write_session_parm( 'username', $username );
      $this->write_session_parm( 'user_email', $user_email );

      echo $this->get_datetime_now_in_words() . '. New email address has been saved.';
    }

    $this->edit_self();
  }
  #-----------------------------------------------------

  function forgot_password_link() {


    echo '<style type="text/css">' . NEWLINE;
    echo ' .forgotpass a:link {'                    . NEWLINE;
    echo '   font-style: italic;'                                    . NEWLINE;
    echo '   font-size: 11pt;'                                      . NEWLINE;
    echo '   text-decoration: none;'                                . NEWLINE;
    echo '   color: ' .  HTML::get_contrast1_colour() . ';'         . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo ' .resetpass {'                    . NEWLINE;
    echo '   font-weight: bold;'                                    . NEWLINE;
    echo '   font-size: 12pt;'                                      . NEWLINE;
    echo ' }'                                                       . NEWLINE;
    echo '</style>';

    $href = $_SERVER['PHP_SELF'] . '?option=forgotpass';
    $title = 'Forgot your password?';

    HTML::span_start( 'class="widespaceonleft forgotpass"' );
    
    echo '[ ';
    HTML::link( $href, $title, $title );
    echo ' ]';

    HTML::span_end();

    if( $this->parm_found_in_post( 'auto_reset_password' )) {
      HTML::new_paragraph();
      HTML::div_start( 'class="resetpass"' );
      echo 'N.B. Your password has now been reset and the new password has been sent'
           . ' to the email address you provided.';
      echo LINEBREAK;
      echo 'Please check your email and log in with the new password.';
      HTML::div_end();
    }
  }
  #-----------------------------------------------------

  function forgot_password( $err_msg = NULL ) {

    $this->registration_page_start();

    if( $err_msg ) {
      HTML::new_paragraph();
      HTML::div_start( 'class="errmsg"' );
      echo $err_msg;
      HTML::div_end();
      HTML::new_paragraph();
    }

    HTML::form_start( 'tool_user', 'reset_password' );

    HTML::new_paragraph();
    HTML::h3_start();
    echo 'Reset password:';
    HTML::h3_end();

    HTML::h4_start();
    echo 'Please enter the email address which you registered as your username on this system:';
    HTML::h4_end();

    HTML::italic_start();
    echo 'You need to have registered using a valid email address as your username.'
          . ' A new password can then be sent to that email address.';
    HTML::italic_end();
    HTML::new_paragraph();
    HTML::form_start( 'tool_user', 'reset_password' );

    HTML::div_start( 'class="dataentrytextfields"' );

    HTML::input_field( 'username', 'Email address under which registered', $username, 
                       FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );

    HTML::new_paragraph();
    echo LINEBREAK;

    HTML::input_field( 'email_address', 'Confirm email address', $email_address, 
                       FALSE, TOOL_USERNAME_LOGIN_FIELD_SIZE );

    HTML::div_end();
    echo LINEBREAK;

    HTML::submit_button( 'reset_button', 'Reset' );
    HTML::submit_button( 'cancel_button', 'Cancel' );

    HTML::form_end();

    HTML::new_paragraph();
    HTML::div_start( 'class="bold"' );
    echo 'When you click Reset, a new password will be automatically generated for you,'
         . ' and will be sent to the email address given above.';
    HTML::div_end();
    HTML::new_paragraph();
    echo 'Please check your email, log in using the new password,'
         . ' and then change that password to one of your own choice.';
    HTML::new_paragraph();

    $this->registration_page_end();
  }
  #-----------------------------------------------------

  function reset_password() {

    $username = $this->read_post_parm( 'username' );
    $email_address = $this->read_post_parm( 'email_address' );
    $tool_user_id = NULL;

    $failed_validation = FALSE;
    $err_msg = '';

    if( trim( $username ) == '' )  {
      $err_msg .= 'Error: email address must be entered before password can be reset.';
      $failed_validation = TRUE;
    }

    if( trim( $email_address ) == '' )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: confirmation of email address must be entered before password can be reset.';
      $failed_validation = TRUE;
    }

    if( $username && $email_address && $username != $email_address )  {
      if( $err_msg ) $err_msg .= LINEBREAK;
      $err_msg .= 'Error: email address and confirmation do not match.';
      $failed_validation = TRUE;
    }

    if( ! $failed_validation ) {
      $supervisor_name = $this->get_supervisor(); 
      $super_db_connection = new DBQuery ( $supervisor_name );
      $this->project_obj = new Project( $super_db_connection );
      $this->tool_user_tablename = $this->project_obj->proj_collect_tool_user_tablename();

      $statement = "select tool_user_id from $this->tool_user_tablename"
                 . " where tool_user_email = '" . $this->escape( $username ) . "'";
      $tool_user_id = $this->project_obj->db_select_one_value( $statement );
      if( ! $tool_user_id ) {
        if( $err_msg ) $err_msg .= LINEBREAK;
        $err_msg .= 'Error: email address not found in user registration details.';
        $failed_validation = TRUE;
      }
    }

    if( ! $failed_validation ) {
      $newpass = $this->get_newpass();

      $statement = "update $this->tool_user_tablename"
                 . " set tool_user_pw = '" . md5( $newpass ) . "' "
                 . " where tool_user_id = $tool_user_id"
                 . " and tool_user_email = '$username'";
      $this->project_obj->db_run_query( $statement );
      $this->write_post_parm( 'auto_reset_password', 1 );

      $statement = "select * from $this->tool_user_tablename where tool_user_id = $tool_user_id";
      $this->db_select_into_properties( $statement );

      $subject = CFG_SYSTEM_TITLE . ' password reset'; 

      $message = "Dear $this->tool_user_forename $this->tool_user_surname,";
      $message .= NEWLINE . NEWLINE;
      $message .= 'Your password for the ' . CFG_SYSTEM_TITLE . ' system has been reset.';
      $message .= NEWLINE . NEWLINE;
      $message .= 'New password: ' . $newpass;
      $message .= NEWLINE . NEWLINE;
      $message .= "Please log into the system using the username '$username' and the new password,";
      $message .= ' which you can copy and paste from this email.';
      $message .= ' Then change the password to one of your own choice.';
      $message .= NEWLINE . NEWLINE;
      $message .= 'Do not reply to this message, as it was automatically generated'
               .  ' from an email address which is not monitored.';
      $message .= NEWLINE . NEWLINE;

      $headers = 'From: ' . CFG_SYSTEM_TITLE . ' <noreply@bdlss.bodleian.ox.ac.uk>' . NEWLINE; 

      $success = mail( $username, $subject, $message, $headers );
    }

    $this->project_obj = NULL; # probably best not to leave that supervisor connection hanging around

    if( ! $failed_validation ) {
      parent::login();
    }
    else {
      $this->forgot_password( $err_msg );
    }
  }
  #-----------------------------------------------------
  function get_newpass() {

    $passlength = 8;  # an arbitrary length
    $min_char = 32;   # first printable character (space)
    $max_char = 126;  # last printable character, as 127 is DEL

    # Might exclude a few characters that could just possibly be a bit problematic
    $reject = array( '<', '>', '"', "'", '`' );

    $newpass = '';
    while( strlen( $newpass ) < $passlength ) {
      $randnum = rand( $min_char, $max_char );
      $one_char = chr( $randnum );
      $one_char == trim( $one_char );
      if( ! $one_char ) continue;
      if( in_array( $one_char, $reject )) continue;
      $newpass .= $one_char;
    }
    return $newpass;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {

    switch( $parm_name ) {

      case 'username':
        if( parent::validate_parm( $parm_name ))
          return TRUE;
        else
          return $this->is_email_address( $this->parm_value ); 

      case 'email_address':
      case 'tool_user_email':
        return $this->is_email_address( $this->parm_value ); 

      case 'pass1':
      case 'pass2':
      case 'oldpass':
        return $this->is_ok_free_text( $this->parm_value );

      case 'class_name':
      case 'method_name':
        return Application_Entity::validate_parm( $parm_name );

      case 'option':
        return Contribution::validate_parm( $parm_name );

      case 'tool_user_id':
        return $this->is_integer( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
