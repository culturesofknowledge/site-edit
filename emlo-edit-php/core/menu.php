<?php
/*
 * PHP class for handling menus
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Note that this version overrides the 'common' version from the 'Aeolus' subversion repository 
 * with a version specific to EMLO/Cultures of Knowledge and IMPAcT.
 * Author: Sushila Burgess
 *
 */

require_once 'proj_components.php';

class Menu extends Project {

  #---------------------------------------------
  # Properties based on project menu table
  #---------------------------------------------
  var $menu_item_id  ;
  var $menu_item_name;
  var $menu_order    ;
  var $parent_id     ;
  var $hidden_parent ;
  var $has_children  ;
  var $class_name    ;
  var $method_name   ;

  var $option_type;  # gets set to 'login', 'menu' or 'form'

  #------------------
  # Other properties
  #------------------
  var $menu_group;  # array holding list of options in submenu

  #-----------------------------------------------------

  function menu( &$db_connection, $item_id = NULL, $class_name = NULL, $method_name = NULL ) { 

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );

    #-------------------------------------
    # Get details of requested menu option
    #-------------------------------------
    if( $this->db_get_username() != CONSTANT_MINIMAL_USER
    &&  PROJ_COLLECTION_CODE != DATA_COLLECTION_TOOL_SUBSYSTEM ) {
      if( $item_id == NULL && $class_name == NULL && $method_name == NULL ) {
        #-----------------------------------------------------------
        # For Cultures of Knowledge only:
        # On first entry to main menu go straight into search option
        #-----------------------------------------------------------
        if( ! $this->parm_found_in_get(  'logout' ) 
        &&  ! $this->parm_found_in_get(  'menu_item_id' ) 
        &&  ! $this->parm_found_in_post( 'menu_item_id' )) {

          if( $this->parm_found_in_get( 'iwork_id' )) {
            $class_name = 'work';
            $method_name = 'one_work_search_results';
          }
          elseif( $this->parm_found_in_get( 'iperson_id' )) {
            $class_name = 'person';
            $method_name = 'one_person_search_results';
          }
          elseif( $this->parm_found_in_get( 'location_id' )) {
            $class_name = 'location';
            $method_name = 'one_location_search_results';
          }
          elseif( $this->parm_found_in_get( 'institution_id' )) {
            $class_name = 'repository';
            $method_name = 'one_repository_search_results';
          }
          elseif( $this->get_system_prefix() == CULTURES_OF_KNOWLEDGE_SYS_PREFIX ) {
            $class_name = PROJ_COLLECTION_WORK_CLASS;
            $work = new $class_name( $this->db_connection );
            $method_name = $work->get_search_method();
            $work = NULL;
          }
        }
      }
    }

    $this->item_id = $item_id;
    $this->class_name = $class_name;
    $this->method_name = $method_name;

    if( $item_id == NULL && $class_name == NULL && $method_name == NULL ) {
      $option_exists = TRUE;
      $this->option_type = 'menu';
      $this->menu_item_name = 'Main Menu';
    }

    elseif( $item_id != NULL ) {
      $option_exists = $this->set_item_by_id();
      if( $this->has_children ) 
        $this->option_type = 'menu';
      else
        $this->option_type = 'form';
    }

    elseif( $this->class_name != NULL and $this->method_name != NULL ) {
      $option_exists = $this->set_item_by_class_and_method();
      $this->option_type = 'form';
    }

    if( ! $option_exists ) {
      if( $this->debug ) {
        $this->echo_safely( 'Menu item id: "' . $this->item_id . '"' );
        echo LINEBREAK;
        $this->echo_safely( 'Class name: "' . $this->class_name . '"' );
        echo LINEBREAK;
        $this->echo_safely( 'Method name: "' . $this->method_name . '"' );
        echo LINEBREAK;
      }
      die( 'Invalid menu option details.' );
    }
  }
  #-----------------------------------------------------

  function page_body_start() {

    #-------------------------------------------------
    # You may want to set focus on a particular field.
    #-------------------------------------------------
    $focus_form  = $this->read_post_parm( 'focus_form' );
    $focus_field = $this->read_post_parm( 'focus_field' );
    $required_anchor = $this->read_post_parm( 'required_anchor' );

    #------------------------------------------------------------------------------------------------------
    # If you want to set focus in a form called directly from the main menu, you need to hard-code the form
    # and field name here, as you will not have had a chance to set up hidden fields in an earlier form.
    # Prime example is "Search by barcode".
    #------------------------------------------------------------------------------------------------------
    if( $focus_form==NULL || $focus_field==NULL ) { 
      $focus_form = $this->class_name . '_' . $this->method_name; 
      $focus_field = NULL;

      if( $focus_form == 'barcode_search_by_barcode' ) $focus_field = 'barcode';
    }

    # The HTML class will only attempt to set focus if BOTH form and field are non-null
    HTML::body_start( $focus_form, $focus_field, $required_anchor );
  }
  #-----------------------------------------------------

  function page_body_end() {

    HTML::body_end();
  }
  #-----------------------------------------------------

  function run_menu( $username=NULL, $person_name=NULL, $login_time=NULL, $prev_login=NULL )
  {
    $this->username = $username;
    $this->person_name = $person_name;
    $this->login_time = $login_time;
    $this->prev_login = $prev_login;

    $suppress_breadcrumbs = FALSE;
    $suppress_colours = FALSE;

    $printable_output = $this->read_post_parm( 'printable_output' );
    $csv_output = $this->read_post_parm( 'csv_output' );
    $excel_output = $this->read_post_parm( 'excel_output' );


    if( $printable_output ) {
      $suppress_breadcrumbs = TRUE;
      $suppress_colours = TRUE;
      $this->printable_output = $printable_output;
    }

    $menu_group_selected = FALSE;

    if( $this->option_type == 'menu' ) {  # if the menu has only one option, go straight into it
      $this->menu_group = NULL;
      $this->select_menu_group();
      if( count( $this->menu_group ) == 1 ) { # re-initialise menu object
        $row = $this->menu_group[0];
        $this->menu( $this->db_connection, $item_id = $row['menu_item_id'] );
        $this->run_menu(  $this->username,
                          $this->person_name,
                          $this->login_time,
                          $this->prev_login );
        return;
      }
      else
        $menu_group_selected = TRUE;
    }

    $this->construct_breadcrumb_trail();

    HTML::html_start();

    HTML::html_head_start();
    HTML::write_stylesheet(  FALSE, FALSE, $this->printable_output );
    $this->write_js_check_form_valid();        # Call this in "on submit" event of any form (first call a function 
                                               # to set "validation err" field to 1 if applicable).
    $this->write_js_drop_form_validation();    # Can be called by Cancel button in forms with JS validation enabled
    $this->write_js_check_value_is_numeric();  # Function which may optionally be used for client-side validation.
    $this->write_js_open_search_window();      # Can open a search window which then returns a value to calling form

    HTML::html_head_end();

    $this->page_body_start();

    $this->page_head( $override_title = NULL, $suppress_breadcrumbs, $suppress_colours );

    #-------------------------------------
    #  Either display list of menu options
    #-------------------------------------
    if( $this->option_type == 'menu' ) {

      if( ! $menu_group_selected ) {
        $this->menu_group = NULL;
        $this->select_menu_group();
      }
      if( is_array( $this->menu_group )) $this->display_menu_form();
    }


    #--------------------------------------------------------------------------------------------
    # Or invoke a method (given EITHER in menu option info from DB, OR passed from previous form)
    #--------------------------------------------------------------------------------------------
    elseif( $this->class_name != NULL and $this->method_name != NULL ) {

      $class_to_create = trim( strtolower( $this->class_name ));
      $method_to_call  = trim( strtolower( $this->method_name ));

      $valid_classes = $this->app_get_declared_classes();
      $is_valid = array_search( $class_to_create, $valid_classes );
      if( $is_valid ) {
        $valid_methods = $this->app_get_class_methods( $class_to_create );
        $is_valid = array_search( $method_to_call, $valid_methods );
      }

      if( $is_valid ) {
        $this->write_session_parm( 'latest_menu', $this->parent_id );

        $menu_item = new $class_to_create( $this->db_connection );

        # Allow the called class to know which menu option has called it
        $menu_item->menu_item_id          = $this->menu_item_id;
        $menu_item->menu_item_name        = $this->menu_item_name ;
        $menu_item->menu_parent_id        = $this->parent_id ;
        $menu_item->menu_class_name       = $this->class_name ;
        $menu_item->menu_method_name      = $this->method_name ;
        $menu_item->menu_user_restriction = $this->user_restriction ;
        $menu_item->menu_hidden_parent    = $this->hidden_parent ;
        $menu_item->menu_called_as_popup  = $this->called_as_popup ;

        $menu_item->printable_output      = $printable_output;
        $menu_item->csv_output            = $csv_output;
        $menu_item->excel_output          = $excel_output;

        $menu_item->username              = $username;
        $menu_item->person_name           = $person_name;

        $menu_item->$method_to_call();

        if( $class_to_create == 'user' ) {  # User details may have changed. Refresh before displaying page footer.
          $menu_item->set_user( $username );
          $this->person_name = $menu_item->get_person_name();
        }
      }
      else
        die('Menu option still under construction.');
    }

    $this->page_foot( $suppress_breadcrumbs );
    $this->page_body_end();

    HTML::html_end();
  }
  #-----------------------------------------------------

  function display_menu_form() {

    if( is_array( $this->menu_group )) {
      $this->write_session_parm( 'latest_menu', $this->menu_item_id );

      HTML::div_start( 'class="mainmenu"');
      echo LINEBREAK;

      HTML::ulist_start();   # <ul>

      foreach( $this->menu_group as $row ) {
        if( is_array( $row )) {
          HTML::listitem_start();  # <li>

          $href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $row['menu_item_id'];
          $title = $row['menu_item_name'];

          HTML::link_start( $href, $title );  # <a>
          echo $title;
          HTML::link_end();                   # </a>

          HTML::listitem_end();  # </li>
          HTML::new_paragraph();
        }
      }

      HTML::ulist_end();   # </ul>

      echo LINEBREAK;
      HTML::div_end();

    }
  }
  #-----------------------------------------------------

  function set_item_by_id() {

    if( $this->item_id == NULL || $this->item_id < 1 ) return NULL;

    $statement = 'select * from  ' . $this->proj_menu_tablename() . " where menu_item_id = $this->item_id";
    $statement = $statement . ' and menu_item_id > 0'; # Do not let them display hidden options (parent id -1)
    $statement = $statement . $this->restrict_menu_access();

    $option_exists = $this->db_select_into_properties( $statement );

    $this->orig_parent_id = $this->parent_id;

    if( $option_exists ) {
      if( $this->parent_id < 0 ) {
        $option_exists = FALSE; # Do not normally let them display hidden options (parent id -1)
        # However, let them call a popup search window 
        # *IF* flag is set to say this menu item can be called in this way.
        if( $this->called_as_popup ) $option_exists = TRUE;
      }
    }

    return $option_exists;
  }
  #-----------------------------------------------------
  # N.B. Hidden options should be given a parent ID of -1

  function set_item_by_class_and_method() {

    if( $this->class_name == NULL || $this->method_name == NULL ) return NULL;

    $statement = 'select * from ' . $this->proj_menu_tablename() . " where class_name = '$this->class_name' " 
                 . " and method_name='$this->method_name'";
    $statement .= $this->collection_where_clause();
    $statement = $statement . $this->restrict_menu_access();
    $statement = $statement . ' order by menu_item_name';

    # Some users may have access to several menus, with the same option on all the menus.
    # Try to work out where they are really coming from.
    $latest_menu = $this->read_session_parm( 'latest_menu' );
    $found_parent = FALSE;
    $options = $this->db_select_into_array( $statement );

    $option_exists = 0;
    $row = NULL;
    if( is_array( $options )) {
      $option_exists = count( $options );
      foreach( $options as $row ) {
        if( $row['parent_id'] == $latest_menu || $row['hidden_parent'] == $latest_menu ) {
          $found_parent = TRUE;
          break;
        }
      }
    }

    if( $option_exists && ! $found_parent ) {
      $row = $options[0];
    }
 
    if( $option_exists ) {
      foreach( $row as $colname => $colvalue ) {
        $this->$colname = $colvalue;
      }
    }

    $this->orig_parent_id = $this->parent_id;

    if( $this->parent_id < 0 ) $this->parent_id = $this->hidden_parent;

    return $option_exists;
  }
  #-----------------------------------------------------

  function select_menu_group() {

    $statement = 'select * from ' . $this->proj_menu_tablename() . ' where parent_id ';
    if( $this->menu_item_id == NULL || $this->menu_item_id < 1 )
      $statement = $statement . ' is null';
    else
      $statement = $statement . " = $this->menu_item_id";
    $statement = $statement . $this->restrict_menu_access();

    $statement .= $this->collection_where_clause();

    $statement = $statement . ' order by menu_order';

    $this->menu_group = $this->db_select_into_array( $statement );
  }
  #-----------------------------------------------------

  function select_menu_item_by_id( $menu_item_id ) {

    if( $menu_item_id == NULL || $menu_item_id < 1 ) return NULL;

    $statement = 'select * from ' . $this->proj_menu_tablename() . " where menu_item_id = $menu_item_id";
    $statement = $statement . $this->restrict_menu_access();

    $this->db_run_query( $statement );
    $row = $this->db_fetch_next_row();
    return $row;
  }
  #-----------------------------------------------------

  function breadcrumbs() {

    $breadcrumb_trail_printed = FALSE;
    if( ! is_array( $this->breadcrumb_trail )) return $breadcrumb_trail_printed;

    $option_depth = 0;
    foreach( $this->breadcrumb_trail as $option ) {
      $option_depth++;
      if( $option_depth > 1 ) HTML::bullet_point();

      if( ! $option ) { # main menu
        $href = $_SERVER['PHP_SELF'] . '?menu_item_id=';
        $parent_name = 'Main Menu';
      }
      else {
        $href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $option;
        $row = $this->select_menu_item_by_id( $option );
        $parent_name = $row['menu_item_name'];
      }

      HTML::link_start( $href, $parent_name );
      echo $parent_name;
      HTML::link_end();
    }

    $breadcrumb_trail_printed = TRUE;
    return $breadcrumb_trail_printed;
  }
  #-----------------------------------------------------

  function construct_breadcrumb_trail() {

    $this->breadcrumb_trail = array();

    if( ! $this->menu_item_id )  # in top-level, main menu
      $this->breadcrumb_trail = NULL;

    else {
      if( $this->parent_id < 0 && $this->hidden_parent > 0 )
        $this->parent_id = $this->hidden_parent;

      $parent_id = $this->parent_id;
      while( $parent_id > 0 ) { # Parent menu is not top menu
        $this->breadcrumb_trail[] = $parent_id;
        $row = $this->select_menu_item_by_id( $parent_id );
        $parent_id = $row[ 'parent_id' ];
        if( $parent_id <= 0 ) $parent_id = $row[ 'hidden_parent' ];
      }
      $this->breadcrumb_trail[] = $parent_id;  # add main menu
      krsort( $this->breadcrumb_trail );
    }
  }
  #-----------------------------------------------------

  function cofk_logo( $logo_class ) {  # Logo and link for Cultures of Knowledge

    if( ! $this->printable_output )
      echo '<a href="' . $_SERVER[ 'PHP_SELF' ] . '" title="EMLO Edit home page" >';

    echo  '<img src="' . PROJ_LOGO_FILE . '" alt="EMLO Edit"  class="' . $logo_class . '" >';

    if( ! $this->printable_output )
      echo '</a>';
  }
  #-----------------------------------------------------

  function impt_logo( $logo_class ) {  # Logo and link for IMPAcT

    if( ! $this->printable_output )
      echo '<a href="' . PROJ_MAIN_SITE . '" title="Return to IMPAcT main site" >';

    echo  '<img src="' . PROJ_LOGO_FILE . '" alt="IMPAcT"  class="' . $logo_class . '" >';

    if( ! $this->printable_output )
      echo '</a>';
  }
  #-----------------------------------------------------

  function ox_logo( $link_class = 'footimg' ) {

    echo NEWLINE;

    if( $link_class == 'footimg' && $this->printable_output )
      $img_style = ' class="printfootimg"';
    else
      $img_style = '';

    if( ! $this->printable_output )
      echo '<a href="http://www.ox.ac.uk/" title="University of Oxford" target="_blank" '
           . ' class="' . $link_class . '"'
           . ' >';

    echo  '<img src="ox_brand3_pos_rect.gif" alt="University of Oxford logo"' . $img_style . ' >';

    if( ! $this->printable_output )
      echo '</a>';


    if( ! $this->printable_output )
      echo '<a href="http://www.bodleian.ox.ac.uk/" title="Bodleian Libraries" target="_blank" '
           . ' class="' . $link_class . '"'
           . ' >';

    echo  '<img src="bod-libraries-140.png" alt="Bodleian Libraries logo"' . $img_style . ' >';

    if( ! $this->printable_output )
      echo '</a>';

    echo NEWLINE;

  }
  #-----------------------------------------------------

  function funder_logos( $link_class = 'footimg' ) {

    if( $this->get_system_prefix() == CULTURES_OF_KNOWLEDGE_SYS_PREFIX )
      $this->mellon_logo();

    elseif( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
      $this->impact_funder_logos();
  }
  #-----------------------------------------------------

  function mellon_logo( $link_class = 'footimg' ) {

    if( $this->get_system_prefix() != CULTURES_OF_KNOWLEDGE_SYS_PREFIX ) return;

    echo NEWLINE;
    echo ' ';

    if( ! $this->printable_output )
      echo '<a href="http://www.mellon.org" title="The Andrew W. Mellon Foundation" target="_blank" '
           . ' class="' . $link_class . '"'
           . ' >';

    echo  '<img src="CofKDatabaseMellonLogo.jpg" alt="The Andrew W. Mellon Foundation">';

    if( ! $this->printable_output )
      echo '</a>';

    echo NEWLINE;
  }
  #-----------------------------------------------------

  function impact_funder_logos( $link_class = 'footimg' ) {

    if( $this->get_system_prefix() != IMPACT_SYS_PREFIX ) return;

    echo NEWLINE;
    echo '<style type="text/css">' . NEWLINE;
    echo ' img.impact_footimg {'   . NEWLINE;
    echo '   height: 100px; '      . NEWLINE;
    echo ' }'                      . NEWLINE;
    echo '</style>'                . NEWLINE;
    echo NEWLINE;

    if( ! $this->printable_output )
      echo '<a href="http://erc.europa.eu/" title="European Research Council" target="_blank"'
           . ' class="' . $link_class . '"'
           . ' >';

    echo  '<img src="small_ERC_logo.jpg" alt="European Research Council" class="impact_footimg" >';

    if( ! $this->printable_output )
      echo '</a>';

    echo ' ';

    if( ! $this->printable_output )
      echo '<a href="http://cordis.europa.eu/fp7/ideas/home_en.html"'
           . ' title="European Commission | CORDIS | Seventh Framework Programme"'
           . ' target="_blank" class="' . $link_class . '"'
           . ' >';

    echo  '<img src="FP7-ide-RGB.gif" alt="European Commission | CORDIS | Seventh Framework Programme"'
          . ' class="impact_footimg" >';

    if( ! $this->printable_output )
      echo '</a>';


    echo NEWLINE;
  }
  #-----------------------------------------------------

  function page_head( $override_title = NULL, $suppress_breadcrumbs = FALSE, $suppress_colours = FALSE ) {

    if( $this->called_as_popup ) $suppress_breadcrumbs = TRUE;

    if( $suppress_colours ) {
      $div_class = 'printbanner';
      $text_class = 'printbannertext';
      $logo_class = 'printbannerlogo';
    }
    else {
      $div_class = 'banner';
      $text_class = 'bannertext';
      $logo_class = 'bannerlogo';
    }
      
    HTML::div_start( ' class="' . $div_class . '" id="pagebanner" ' );

    HTML::page_top_anchor();
    echo LINEBREAK;

    $logo_function = $this->get_system_prefix() . '_logo'; # e.g. 'cofk_logo', 'impt_logo'
    $this->$logo_function( $logo_class );

    HTML::h1_start();
    if( CONSTANT_DATABASE_NAME == 'ouls' )
      echo CFG_SYSTEM_TITLE;
    else
      echo trim( CFG_SYSTEM_TITLE ) . ': Test/Training DB';
    HTML::h1_end();

    if( ! $suppress_breadcrumbs ) {
      $breadcrumb_trail_printed = $this->breadcrumbs();
      if( $breadcrumb_trail_printed ) HTML::bullet_point();
      HTML::link_to_page_bottom( $tabindex=1, $title='Bottom of Page' );

      HTML::bullet_point();
      $href = $_SERVER['PHP_SELF'] . '?logout=1';
      HTML::link_start( $href, 'Log out of ' . CFG_SYSTEM_TITLE );
      echo 'Logout';
      HTML::link_end();
    }
	  echo LINEBREAK;
    echo '<hr style="border: 7px solid white;">';

    HTML::h2_start( "margin:13px 10px 0px 19px");
    if( $override_title )
      echo $override_title;
    else
      echo $this->menu_item_name;
    HTML::h2_end();

    echo LINEBREAK;

    HTML::div_end();
    HTML::new_paragraph();

    if( PROJ_RELOAD_IN_PROGRESS ) {
      HTML::h1_start();
      echo 'The data is currently being upgraded and is incomplete. ';
      echo 'Please try again in about 10 minutes when the new version of the data will be available.';
      HTML::h1_end();
    }
  }
  #-----------------------------------------------------------------

  function page_foot( $suppress_breadcrumbs = FALSE ) {

    if( $this->called_as_popup ) $suppress_breadcrumbs = TRUE;

    HTML::linebreak();
    HTML::horizontal_rule();

    HTML::new_paragraph();

    if( ! $suppress_breadcrumbs ) $this->footerlinks();

    $this->footimgs();
    HTML::new_paragraph();
  }
  #-----------------------------------------------------------------

  function footerlinks() {

    HTML::div_start( 'class="footerlinks"' );
    HTML::new_paragraph();

    $breadcrumb_trail_printed = $this->breadcrumbs();
    if( $breadcrumb_trail_printed ) HTML::bullet_point();

    HTML::link_to_page_top(  $tabindex=1, $title='Top of Page' );
    HTML::page_bottom_anchor();

    HTML::bullet_point();
    $href = $_SERVER['PHP_SELF'] . '?logout=1';
    HTML::link_start( $href, 'Log out of ' . CFG_SYSTEM_TITLE );
    echo 'Logout';
    HTML::link_end();

    HTML::new_paragraph();

    $this->footnotes();

    HTML::div_end( 'footerlinks' );
  }
  #-----------------------------------------------------------------

  function footnotes() {

    HTML::table_start( ' width="100%" ' );
    HTML::tablerow_start();
    HTML::tabledata_start();

    $this->help_notes();

    HTML::tabledata_end();
    HTML::tabledata_start( ' class="rightaligned" ' );

    if( $this->username ) {
      HTML::small_start();
      echo $this->get_datetime_now_in_words();
      echo LINEBREAK;
      if( $this->user_is_supervisor())
        echo 'YOU ARE LOGGED IN AS A SUPERVISOR:' ;
      else
        echo 'You are logged in as: ' ;
      echo LINEBREAK;
      echo $this->person_name . ' (' . $this->username . ')' ;
      echo LINEBREAK;
      echo 'Latest login: ' . $this->postgres_date_to_dd_mm_yyyy( $this->login_time );
      echo LINEBREAK;
      echo 'Previous login: ' . $this->postgres_date_to_dd_mm_yyyy( $this->prev_login );
      HTML::small_end();
    }

    HTML::tabledata_end();
    HTML::tablerow_end();
    HTML::table_end();
  }
  #-----------------------------------------------------------------

  function help_notes() {

    HTML::small_start();
    echo 'Help and hints: ';

    HTML::ulist_start();
    HTML::listitem_start();
    echo 'For technical support, contact ';
    HTML::link( $href = 'mailto:support@bodleian.ox.ac.uk', 
                $displayed_text = 'support@bodleian.ox.ac.uk', 
                $title = 'Technical Support', 
                $target = '_blank' );
    HTML::listitem_end();
    
    HTML::listitem( 'Ctrl-Home in Windows takes you to the top of the page. ' );
    HTML::listitem( 'Ctrl-End in Windows takes you to the bottom of the page.' );
    HTML::ulist_end();
    HTML::small_end();
  }
  #-----------------------------------------------------------------

  function footimgs() {

    if( ! $this->called_as_popup ) {

      HTML::div_start( 'class="footimgs"' );
      $this->ox_logo();
      echo ' ';
      $this->funder_logos();
      HTML::div_end( 'footimgs' );
    }
  }
  #-----------------------------------------------------------------

  function restrict_menu_access() { 

    $this->user_roles    = $this->read_session_parm( 'user_roles' );

    $where_clause = " and (user_restriction = '' ";
    if( $this->user_roles ) $where_clause = $where_clause . " or user_restriction in ( $this->user_roles )";
    $where_clause = $where_clause . ')';
    return $where_clause;
  }
  #-----------------------------------------------------------------

  function collection_where_clause() {

    $collection = '';
    if( PROJ_SUB_COLLECTION  > '' )
      $collection = PROJ_SUB_COLLECTION;
    elseif( PROJ_COLLECTION_CODE > '' )
      $collection = PROJ_COLLECTION_CODE;

    $clause = " and ( collection = '' ";
    
    if( $collection ) $clause .= " or collection = '$collection' " ;

    $clause .= ') ';
    return $clause;
  }
  #-----------------------------------------------------------------

  function return_to_main_menu() {  # called when a class and method has previously been called, but user cancelled

    $this->clear();
    $this->select_menu_group();
    if( is_array( $this->menu_group )) $this->display_menu_form();
  }
  #-----------------------------------------------------------------

  function get_menu_item_id() {
    return $this->menu_item_id;
  }
  #-----------------------------------------------------------------

  function get_menu_item_name() {
    return $this->menu_item_name;
  }
  #-----------------------------------------------------------------

  function get_menu_item_class() {
    return $this->class_name;
  }
  #-----------------------------------------------------------------

  function get_menu_item_method() {
    return $this->method_name;
  }
  #-----------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'focus_form':
      case 'focus_field':
      case 'required_anchor':

        return $this->is_alphanumeric_or_blank( $this->parm_value, $allow_underscores = TRUE );

      case 'latest_menu':
        return $this->is_integer( $this->parm_value, $allow_negative = TRUE );

      case 'breadcrumb_trail':
        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_array_of_integers( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
