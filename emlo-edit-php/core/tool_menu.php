<?php
/*
 * Menu options for the offline data collection tool.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'TOOL_INTERFACE_FILE', 'collect.php' );
 
class Tool_Menu extends Project {

  #-----------------------------------------------------

  function Tool_Menu( &$db_connection ) { 

    $this->Project( $db_connection );
  }
  #-----------------------------------------------------

  function reviewer_menu() {

    $tool_interface_file = $this->get_tool_interface_file(); # collect.php

    HTML::div_start( 'class="mainmenu"' );

    if( $this->user_is_supervisor() ) {
      $statement = 'select count(*) from ' . $this->proj_collect_upload_tablename() . ' u, '
                 . $this->proj_collect_status_tablename() . ' s '
                 . ' where u.upload_status = s.status_id'
                 . ' and s.editable = 1';
      $num_outstanding = $this->db_select_one_value( $statement );
      if( $num_outstanding > 0 ) {
        $statement = 'select menu_item_id from ' . $this->proj_menu_tablename()
                   . " where class_name = 'upload' and method_name = 'upload_list'";
        $menu_item_id = $this->db_select_one_value( $statement );
        if( ! $menu_item_id ) die( 'Menu not correctly set up.' );
        $review_href = $_SERVER[ 'PHP_SELF' ] . '?menu_item_id=' . $menu_item_id;
        echo 'There are currently';
        HTML::link_start( $review_href, 'Display contributions awaiting review' );
        echo " $num_outstanding contributions";
        HTML::link_end();
        echo ' awaiting review.';
      }
      else {
        echo 'No contributions awaiting review.';
      }
      HTML::new_paragraph();
    }

    HTML::ulist_start();

    if( $this->user_is_supervisor() && $num_outstanding > 0 ) {
      HTML::listitem_start();
      HTML::link_start( $review_href, 'Display contributions awaiting review' );
      echo 'Display contributions awaiting review';
      HTML::link_end();
      HTML::listitem_end();
      HTML::new_paragraph();
    }

    if( $this->user_is_supervisor() ) {
      $statement = 'select menu_item_id from ' . $this->proj_menu_tablename()
                 . " where class_name = 'contributed_work' and method_name = 'db_search'";
      $menu_item_id = $this->db_select_one_value( $statement );
      if( ! $menu_item_id ) die( 'Menu not correctly set up.' );
      $browse_href = $_SERVER[ 'PHP_SELF' ] . '?menu_item_id=' . $menu_item_id;

      HTML::listitem_start();
      HTML::link_start( $browse_href, 'Browse or search contributed works' );
      echo 'Browse or search contributed works';
      HTML::link_end();
      HTML::listitem_end();
      HTML::new_paragraph();

      $statement = 'select menu_item_id from ' . $this->proj_menu_tablename()
                 . " where class_name = 'contributor' and method_name = 'db_search'";
      $menu_item_id = $this->db_select_one_value( $statement );
      if( ! $menu_item_id ) die( 'Menu not correctly set up.' );
      $browse_href = $_SERVER[ 'PHP_SELF' ] . '?menu_item_id=' . $menu_item_id;

      HTML::listitem_start();
      HTML::link_start( $browse_href, 'Browse or search contributors' );
      echo 'Browse or search contributors';
      HTML::link_end();
      HTML::listitem_end();
      HTML::new_paragraph();
    }

      if( $this->user_is_supervisor() ) {
          $statement = 'SELECT menu_item_id FROM ' . $this->proj_menu_tablename()
              . " WHERE class_name = 'upload' AND method_name = 'file_upload_excel_form'";
          $menu_item_id = $this->db_select_one_value($statement);
          if (!$menu_item_id) die('Menu not correctly set up.');
          $browse_href = $_SERVER['PHP_SELF'] . '?menu_item_id=' . $menu_item_id;

          HTML::listitem_start();
          HTML::link_start($browse_href, 'Upload an Excel file with works, people, places, repositories and manifestat');
          echo 'Upload an Excel File';
          HTML::link_end();
          HTML::listitem_end();
          HTML::new_paragraph();
      }

    HTML::listitem_start();
    HTML::link_start( $href = $tool_interface_file, $title='Options for contributors' );
    echo 'Options for contributors';
    HTML::link_end();
    HTML::listitem_end();
    HTML::new_paragraph();

    HTML::ulist_end();
    HTML::div_end();
  }
  #-----------------------------------------------------

  function contributor_menu() {

    $tool_interface_file = $this->get_tool_interface_file(); # collect.php

    HTML::div_start( 'class="mainmenu"' );

    HTML::new_paragraph();
    HTML::h3_start();
    echo 'You are about to be redirected to the Contributor Menu of the offline data collection tool.';
    HTML::h3_end();
    HTML::new_paragraph();
  
    echo 'If the redirection does not happen within a couple of seconds, please follow this link to the ';

    HTML::link_start( $href = $tool_interface_file, $title='Contributor menu' );
    echo 'contributor menu.';
    HTML::link_end();
    HTML::new_paragraph();

    HTML::div_end();

    $script = 'function goToTool() {'                                    . NEWLINE
            . '  document.location.href="' . $tool_interface_file . '";' . NEWLINE
            . '}'                                                        . NEWLINE;
    HTML::write_javascript_function( $script );

    $script = 'window.setTimeout( goToTool, 1500);';
    HTML::write_javascript_function( $script );
  }
  #-----------------------------------------------------
  function get_tool_interface_file() {
    $tool_interface_file = TOOL_INTERFACE_FILE; # collect.php
    if( $this->string_contains_substring( $_SERVER[ 'PHP_SELF' ], '/dev_' )) {
      $tool_interface_file = 'dev_' . $tool_interface_file;
    }
    return $tool_interface_file;
  }
  #-----------------------------------------------------
  function validate_parm( $parm_name ) {

    switch( $parm_name ) {
      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
