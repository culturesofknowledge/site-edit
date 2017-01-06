<?php
# Contribute data to Cultures of Knowledge using the offline data collection tool
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

if( Application_Entity::get_system_prefix() == IMPACT_SYS_PREFIX )
  define( 'MAIN_EDITING_INTERFACE_FILE', 'impact.php' );
else
  define( 'MAIN_EDITING_INTERFACE_FILE', 'union.php' );

define( 'TOOL_DOWNLOAD_URL', 
        'https://emlo-edit.bodleian.ox.ac.uk/culturesofknowledge/tool/EMLOcollect.odb' );

define( 'OPENOFFICE_DOWNLOAD_URL', 'http://www.openoffice.org/download/' );

class Contribution extends Application_Entity {

  #----------------------------------------------------------------------------------

  function Contribution() {

    $this->Application_Entity();
  }
  #----------------------------------------------------------------------------------
  function app_get_user_object_for_login( &$db_connection ) { # overrides parent method
                                                              # from Application Entity
    $the_user = new Tool_User( $db_connection );              
    return $the_user;
  }
  #-----------------------------------------------------

  function app_run_menu( $the_user, $menu_item_id=NULL, $class_name=NULL, $method_name=NULL  ) { 

    # Overrides parent class (application entity) so that we can bypass use of the menu table
    # which is only accessible to people in the main users table (not data collection tool users).

    $this->db_connection = $the_user->db_connection;
    
    $option = '';
    $class_name = '';
    $method_name = '';
    $valid_option = TRUE;
    $title = 'Contributor Menu';

    if( $this->parm_found_in_get( 'option' )) {
      $option = $this->read_get_parm( 'option' );
    }
    elseif( $this->parm_found_in_post( 'class_name' )
         && $this->parm_found_in_post( 'method_name' )) {
      $class_name = $this->read_post_parm( 'class_name' );
      $method_name = $this->read_post_parm( 'method_name' );
    }

    #-------------------------------------------------
    # Items called via GET from the menu
    # (logout is handled by parent Application Entity)
    #-------------------------------------------------
    if( $option == 'upload' ) {
      $title = 'Upload new contribution';
      $class_name = 'upload';
      $method_name = 'file_upload_form';
    }
    elseif( $option == 'history' ) {
      $title = 'View your earlier contributions';
      $class_name = 'upload';
      $method_name = 'history';
    }
    elseif( $option == 'account' ) {
      $title = 'Change password, name or email address';
      $class_name = 'tool_user';
      $method_name = 'edit_self';
    }
    elseif( $option == 'forgotpass' ) {
      $title = 'Forgot your password?';
      $class_name = 'tool_user';
      $method_name = 'forgot_password';
    }


    #---------------------------------------
    # Items called via POST from later forms
    #---------------------------------------
    elseif( $class_name == 'upload' && $method_name == 'process_uploaded_files' ) {
      $title = 'Processing new contribution';
    }

    elseif( $class_name == 'upload' && $method_name == 'export_upload_to_csv' ) {
      $title = 'Export details of contribution to spreadsheet';
    }

    elseif( $class_name == 'tool_user' 
    && ( $method_name == 'save_password' || $method_name == 'save_username' || $method_name == 'save_names' )) {
      $title = 'Change password, name or email address';
    }


    else {
      $valid_option = FALSE;  # unknown option
    }

    #-------------------------------------------
    # Either call another method or display menu
    #-------------------------------------------
    $this->menu_obj = new Menu( $the_user->db_connection, NULL, NULL, NULL );

    $in_menu = FALSE;
    if( ! $method_name || ! $valid_option ) $in_menu = TRUE;

    $this->contributor_page_start( $title, $in_menu );

    if( $class_name && $method_name && $valid_option ) {
      $obj = new $class_name( $this->db_connection );
      $obj->$method_name();
    }
    elseif( $method_name && $valid_option ) {
      $this->$method_name();
    }
    else {
      $this->contributor_menu();
    }

    $this->contributor_page_end( $in_menu );
  }
  #-----------------------------------------------------

  function contributor_menu() {

    echo '<style type="text/css">'                                    . NEWLINE;

    echo ' div.contribmenu {'                                         . NEWLINE; 
    echo '   line-height: 30px;'                                      . NEWLINE;
    echo ' }'                                                         . NEWLINE;

    echo ' div.extramenu {'                                           . NEWLINE; 
    echo '   display: inline; '                                       . NEWLINE;
    echo '   float:right; '                                           . NEWLINE;
    echo '   padding: 25px;'                                          . NEWLINE;
    echo '   border-style: solid;'                                    . NEWLINE;
    echo '   border-width: 1px;'                                      . NEWLINE;
    echo '   border-color: ' . html::get_contrast1_colour() . ';'     . NEWLINE;
    echo ' }'                                                         . NEWLINE;

    echo ' div.reviewmenu {'                                          . NEWLINE; 
    echo '   margin-top: 25px;'                                       . NEWLINE;
    echo '   padding-top: 25px;'                                      . NEWLINE;
    echo '   border-top-style: solid;'                                . NEWLINE;
    echo '   border-top-width: 1px;'                                  . NEWLINE;
    echo '   border-top-color: ' . html::get_contrast1_colour() . ';' . NEWLINE;
    echo ' }'                                                         . NEWLINE;

    echo '  br.clearboth {'                                           . NEWLINE;
    echo '    clear: both;'                                           . NEWLINE;
    echo ' }'                                                         . NEWLINE;

    echo '</style>'                                                   . NEWLINE;

    #----
    html::div_start( 'class="contribmenu"' );
    echo LINEBREAK;
    #----

    html::div_start( 'class="extramenu"' );
    html::link( $href=TOOL_DOWNLOAD_URL, $displayed_text='Download a fresh copy of the tool', 
                $title='Download a fresh copy of the tool' );
    html::new_paragraph();

    html::link( $href=OPENOFFICE_DOWNLOAD_URL, $displayed_text='Download OpenOffice', 
                $title='Download OpenOffice', $target = '_blank' );
    html::new_paragraph();

    if( $this->user_is_supervisor() ) {

      $username = $this->read_session_parm( 'username' );
      $db_connection = new DBQuery ( $username );
      $project_obj = new Project( $db_connection );              
      $statement = 'select menu_item_id from ' . $project_obj->proj_menu_tablename()
                 . " where class_name = 'tool_menu' and method_name = 'reviewer_menu' ";
      $reviewer_menu_item = $project_obj->db_select_one_value( $statement );
      $project_obj = NULL;

      html::div_start( 'class="reviewmenu"' );
      html::link_start( $this->get_main_editing_interface_file() . '?menu_item_id=' . $reviewer_menu_item,
                        'Options for reviewers' ); # <a>
      echo 'Options for reviewers';
      html::link_end();
      html::div_end();  # end reviewmenu
    }
    html::div_end();    # end extramenu

    #----

    html::div_start( 'class="mainmenu"' );
    html::ulist_start();   # <ul>

    $options = array( 'upload'  => 'Upload new contribution',
                      'history' => 'View your earlier contributions',
                      'maindb'  => 'Go to the main editing interface',
                      'account' => 'Change password, name or email address',
                      'logout'  => 'Log out' );

    $user_of_main_db = $this->read_session_parm( 'user_of_main_db' );

    $href = $_SERVER['PHP_SELF'];
    foreach( $options as $option => $title ) {

      if( $option == 'maindb' && ! $user_of_main_db ) continue; 
      if( $option == 'account' && $user_of_main_db ) continue; # they should use MAIN password change screen

      html::listitem_start();  # <li>
      if( $option == 'logout' ) {
        html::link_start( $href . '?logout=1', $title ); # <a>
      }
      elseif( $option == 'maindb' ) {
        html::link_start( $this->get_main_editing_interface_file() . '?menu_item_id=', # sends you to Main Menu
                          $title ); # <a>
      }
      else {
        html::link_start( $href . '?option=' . $option, $title ); # <a>
      }
      echo $title;
      html::link_end(); # </a>
      html::new_paragraph();
      html::listitem_end();  # </li>
    }
    html::ulist_end();   # </ul>
    html::div_end();     # end mainmenu

    #---
    html::linebreak( 'class="clearboth"' );
    html::div_end();    # end contribmenu
  }
  #-----------------------------------------------------

  function contributor_page_start( $heading_text = 'Contributor menu', $in_menu = TRUE ) {

    $this->expire_page();
    html::html_start();
    html::html_head_start();

    $this->menu_obj->page_body_start();
    html::write_stylesheet();

    $this->menu_obj->page_head( $heading_text, TRUE ); # TRUE = suppress breadcrumb trail

    if( ! $in_menu ) {
      $this->contributor_navigation_links();  # offer links back to main menu and to log out
      html::new_paragraph();
      html::horizontal_rule();
      html::new_paragraph();
    }
  }
  #-----------------------------------------------------

  function contributor_page_end( $in_menu = TRUE ) {

    html::new_paragraph();

    if( $in_menu )
      $this->contributor_logged_in_msg();
    else
      $this->contributor_navigation_links();  # offer links back to main menu and to log out

    $this->menu_obj->page_foot( $suppress_breadcrumbs = TRUE );
    $this->menu_obj->page_body_end();

    html::html_end();
  }
  #-----------------------------------------------------

  function contributor_navigation_links() {

    html::new_paragraph();

    $this->back_to_contributor_menu_link();
    echo ' | ';
    $this->logout_of_contributor_menu_link();
    echo ' | ';
    $this->contributor_logged_in_msg();
  }
  #-----------------------------------------------------

  function contributor_logged_in_msg() {

    $db_connection = new DBQuery ( TOOL_DB_USERNAME );
    $the_user = new Tool_User( $db_connection );              
    $username = $the_user->read_session_parm( 'username' );
    $found = $the_user->set_user( $username );
    if( ! $found ) die( 'Invalid user details.' );
    $person_name = $the_user->get_person_name();
    html::italic_start();
    $this->echo_safely( 'You are logged in as ' . $person_name  . ' (' . $username . ') ' );
    html::italic_end();
  }
  #-----------------------------------------------------

  function back_to_contributor_menu_link() {

    $href = $_SERVER['PHP_SELF'];
    $title = 'Menu';

    html::span_start( 'class="boldlink"' );
    html::link_start( $href, $title ); # <a>
    echo $title;
    html::link_end(); # </a>
    html::span_end();
  }
  #-----------------------------------------------------

  function logout_of_contributor_menu_link() {

    $href = $_SERVER['PHP_SELF'] . '?logout=1';
    $title = 'Logout';

    html::span_start( 'class="boldlink"' );
    html::link_start( $href, $title ); # <a>
    echo $title;
    html::link_end(); # </a>
    html::span_end();
  }
  #-----------------------------------------------------

  function logout() {

    # May need to delete data from the 'tool session' table instead of from the main sessions table.
    $session_token = $this->read_get_parm(  SESSION_TOKEN_FIELD  );

    $minimal_db_connection = new DBQuery ( CONSTANT_MINIMAL_USER );
    if( ! $this->project_obj ) $this->project_obj = new Project( $minimal_db_connection );
    $func = $this->project_obj->proj_database_function_name( 'delete_collect_tool_session', 
                                                             $include_collection_code = FALSE );
    $minimal_db_connection->db_run_query( "select $func( '$session_token' )" );
    $minimal_db_connection = NULL;

    # Now delete from the main sessions table
    parent::logout();
  }
  #-----------------------------------------------------
  function get_main_editing_interface_file() {
    $main_editing_interface_file = MAIN_EDITING_INTERFACE_FILE; # e.g. union.php
    if( $this->string_contains_substring( $_SERVER[ 'PHP_SELF' ], '/dev_' )) {
      $main_editing_interface_file = 'dev_' . $main_editing_interface_file;
    }
    return $main_editing_interface_file;
  }
  #-----------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'option':
        return $this->is_alphanumeric_or_blank( $this->parm_value, 
                                                $allow_underscores = TRUE, 
                                                $allow_all_whitespace = FALSE );

      case 'user_of_main_db':
        return $this->is_on_off_switch( $this->parm_value );

      case 'username':
        return User::validate_parm( $parm_name );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
