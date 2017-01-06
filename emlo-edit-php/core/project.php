<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================

#------------------------
# Data import information
#------------------------
define( 'PROJ_RELOAD_IN_PROGRESS', FALSE ); # change this to TRUE to display a big warning message
define( 'PROJ_INITIAL_IMPORT', 'Initial import' );  # saved instead of username in 'change user' column

#-------------------------------------------------
# 'System prefix' specifies the different projects
#-------------------------------------------------
define( 'CULTURES_OF_KNOWLEDGE_SYS_PREFIX', 'cofk' );
define( 'IMPACT_SYS_PREFIX', 'impt' );
define( 'DATA_COLLECTION_TOOL_SUBSYSTEM', 'collect' );

#---------------------------------
# Image files and links from logos
#---------------------------------
if( Application_Entity::get_system_prefix() == CULTURES_OF_KNOWLEDGE_SYS_PREFIX ) {
  define( 'PROJ_LOGO_FILE', 'CofKLogo.png' );
  define( 'PROJ_FAVICON', 'CofK_Favicon.ico' );
  define( 'PROJ_TINY_LOGO_FILE', 'tinyCofKLogo.PNG' ); # used for showing 'Relevant to Cultures of Knowledge' flag
  define( 'PROJ_MAIN_SITE', CULTURES_OF_KNOWLEDGE_MAIN_SITE ); # main site URL is set in union.php
}
elseif( Application_Entity::get_system_prefix() == IMPACT_SYS_PREFIX ) {
  define( 'PROJ_LOGO_FILE', 'IMPAcTLogo.jpg' );
  define( 'PROJ_FAVICON', 'Impt_Favicon.ico' );
  define( 'PROJ_MAIN_SITE', IMPACT_MAIN_SITE ); # main site URL will be set in impact.php
}
else {
  die( 'Unknown project.' );
}

define( 'PROJ_LOGO_HEIGHT_PX', '90' );
define( 'PROJ_RELEVANCE_UNKNOWN_FILE', 'relevance_unknown.png' );
define( 'PROJ_FLAGS_EXIST_FILE', 'pling.png' );
define( 'PROJ_MARKED_FOR_DELETION_FILE', 'del.png' );

#----------------
# Field sizes etc
#----------------
define( 'WORK_FIELD_LABEL_WIDTH', 160 );
define( 'WORK_FIELD_POS', 170 );

define( 'POPUP_SELECTION_DECODE_FIELD_SIZE', 60 ); 

define( 'ENTITYDESC_TD_WIDTH', 152 );
define( 'IMAGE_THUMBNAIL_WIDTH_PX', 60 );

define( 'LISTER_IMAGE_THUMBNAIL_WIDTH_PX', 50 );

define( 'FLD_SIZE_RELATIONSHIP_DATES', 11 );

#----------------------
# Markers for links etc
#----------------------
define( 'IMAGE_ID_START_MARKER',    'xxxCofkImageIDStartxxx'    );
define( 'IMAGE_ID_END_MARKER',      'xxxCofkImageIDEndxxx'      );

define( 'LINK_TEXT_START_MARKER', 'xxxCofkLinkStartxxx' );
define( 'LINK_TEXT_END_MARKER', 'xxxCofkLinkEndxxx' );
define( 'HREF_START_MARKER',    'xxxCofkHrefStartxxx'    );
define( 'HREF_END_MARKER',      'xxxCofkHrefEndxxx'      );

define( 'ALTLABELS_SEPARATOR', ' / ' );
define( 'NON_HTML_LIST_SEPARATOR', '~' );
define( 'NON_HTML_LIST_SEPARATOR2', '--' );



class Project extends DBEntity {
  #----------------------------------------------------------------------------------

  function Project( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->DBEntity( $db_connection );
    $this->set_collection_properties();

    $this->this_on_left = array();  # to hold relationship information
    $this->this_on_right = array();

    # Look up a set of text markers for formatting purposes
    if( $this->db_get_username()   != CONSTANT_MINIMAL_USER 
    &&  PROJ_COLLECTION_CODE != DATA_COLLECTION_TOOL_SUBSYSTEM ) {

      # Make sure that we use exactly the same tags as in SQL database functions
      $markers = $this->proj_get_list_tag_property_names();
      $func = $this->proj_database_function_name( 'get_constant_value', $include_collection_code = TRUE );
      foreach( $markers as $marker ) {
        $statement = "select $func ( '$marker' )";
        $this->$marker = $this->db_select_one_value( $statement );
      }
    }
  }

  #----------------------------------------------------------------------------------

  function proj_get_list_tag_property_names() {

    $markers = array( 'list_item_marker',         # ' ~ ' (i.e. tilde surrounded by spaces)

                      'html_list_start_marker',   # '<ul><li>'
                      'html_list_item_marker',    # '</li><li>'
                      'html_list_end_marker',     # '</li></ul>'

                      'html_olist_start_marker',  # '<ol><li>'
                      'html_olist_end_marker' );  # '</li></ol>'
    return $markers;
  }
  #----------------------------------------------------------------------------------

  function clear() {

    $list_tags = $this->proj_get_list_tag_property_names();

    foreach( $list_tags as $tagname ) {
      $keepname = 'keep_' . $tagname;
      $$keepname = $this->$tagname;
    }

    parent::clear();
    $this->set_collection_properties();

    foreach( $list_tags as $tagname ) {
      $keepname = 'keep_' . $tagname;
      $this->$tagname = $$keepname;
    }
  }
  #-----------------------------------------------------

  function set_collection_properties() {

    $this->collection_prefix = $this->get_system_prefix();  # will give 'cofk' or 'impt'

    if( PROJ_COLLECTION_CODE ) {
      $this->collection_prefix .= '_' . PROJ_COLLECTION_CODE;  # e.g. could give 'cofk_union'
    }
  }
  #-----------------------------------------------------

  function get_collection_setting( $property ) {  # e.g. if you pass in 'work', you'll get back:
                                                  # - 'cofk_union_work' when in Cultures of Knowledge Union DB
                                                  # - 'impt_work' when in Impact DB (assuming they don't have sub-catgs)
    return $this->collection_prefix . '_' . $property;
  }
  #-----------------------------------------------------

  function proj_edit_mode_enabled() {

    $edit_mode_enabled = FALSE;

    switch( PROJ_COLLECTION_CODE ) {
      case 'union':
      case '':
        if( $this->proj_user_has_edit_privs()) $edit_mode_enabled = TRUE;
        break;

      default:
        $edit_mode_enabled = FALSE;
        break;
    }
    return $edit_mode_enabled;
  }
  #-----------------------------------------------------

  function proj_user_has_edit_privs() {

    $has_edit_privs = FALSE;

    # Work out the role name for editors of this database
    $proj_editor_role_code = $this->get_system_prefix() . 'editor';

    $statement = 'select user';
    $username = $this->db_select_one_value( $statement );

    $function_name = $this->proj_database_function_name( 'select_user_roles', $include_collection_code = FALSE );
    $statement = "select $function_name( '$username' )";
    $role_list = $this->db_select_one_value( $statement );

    $roles = explode( ',', $role_list );

    foreach( $roles as $role ) {
      $role = str_replace( "'", '', $role );
      switch( $role ) {
        case SUPERVISOR_ROLE_CODE:
        case $proj_editor_role_code:
          $has_edit_privs = TRUE;
          break;
      }
    }

    return $has_edit_privs;
  }
  #-----------------------------------------------------

  function proj_get_original_catalogue() {

    $original_catalogue = $this->original_catalogue;

    if( ! $original_catalogue ) {
      if( $this->current_row_of_data ) 
        $original_catalogue = $this->current_row_of_data[ 'original_catalogue' ];
    }

    return $original_catalogue;
  }
  #-----------------------------------------------------

  function proj_get_original_catalogue_code() {

    $cat = $this->proj_get_original_catalogue();  # May return either code or decode, 
                                                  # e.g. 'lister' or 'Lister catalogue'.

    if( $cat ) {  # convert 'Lister catalogue' to 'lister' etc.
      if( ! $this->catg_list ) {
        $catg_obj = new Catalogue( $this->db_connection );
        $this->catg_list = $catg_obj->get_lookup_list();
      }

      foreach( $this->catg_list as $catg_row ) {
        extract( $catg_row, EXTR_OVERWRITE );
        if( $cat == $catalogue_code ) {
          break;
        }
        elseif( $cat == $catalogue_name ) {
          $cat = $catalogue_code;
          break;
        }
      }
    }

    return $cat;
  }
  #-----------------------------------------------------

  function proj_get_core_catalogue() {

    $cat = $this->proj_get_original_catalogue_code();
    if( ! $cat ) $cat = PROJ_SUB_COLLECTION;
    if( ! $cat ) $cat = PROJ_COLLECTION_CODE;
    return $cat;
  }
  #-----------------------------------------------------

  function proj_menu_tablename() {
    return $this->get_system_prefix() . '_menu';
  }
  #----------------------------------------------------------------------------------

  function proj_person_tablename() {
    return $this->get_collection_setting( 'person' );
  }
  #----------------------------------------------------------------------------------

  function proj_person_viewname() {

    $view = $this->proj_person_tablename() . '_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_person_sent_viewname() {

    $view = $this->proj_person_tablename() . '_sent_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_person_recd_viewname() {

    $view = $this->proj_person_tablename() . '_recd_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_person_mentioned_viewname() {

    $view = $this->proj_person_tablename() . '_mentioned_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_person_all_works_viewname() {

    $view = $this->proj_person_tablename() . '_all_works_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_person_author_viewname() {

    $view = $this->proj_person_tablename() . '_author_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_organisation_viewname_from_table() {

    $view = str_replace( 'person', 'organisation', $this->proj_person_viewname()) . '_from_table';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_organisation_viewname_from_view() {

    $view = str_replace( 'person', 'organisation', $this->proj_person_viewname()) . '_from_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_work_tablename() {
    return $this->get_collection_setting( 'work' );
  }
  #----------------------------------------------------------------------------------

  function proj_work_viewname() {
    return $this->proj_work_tablename() . '_view';
  }
  #----------------------------------------------------------------------------------

  function proj_compact_work_viewname() {
    return $this->get_collection_setting( 'compact_work_view' );
  }
  #----------------------------------------------------------------------------------

  function proj_queryable_work_tablename() {
    return $this->get_collection_setting( 'queryable_work' );
  }
  #----------------------------------------------------------------------------------

  function proj_manifestation_tablename() {
    return $this->get_collection_setting( 'manifestation' );
  }
  #----------------------------------------------------------------------------------

  function proj_image_tablename() {
    return $this->get_collection_setting( 'image' );
  }
  #----------------------------------------------------------------------------------

  function proj_relationship_tablename() {
    return $this->get_collection_setting( 'relationship' );
  }
  #----------------------------------------------------------------------------------

  function proj_relationship_type_tablename() {
    return $this->get_collection_setting( 'relationship_type' );
  }
  #----------------------------------------------------------------------------------

  function proj_location_tablename() {
    return $this->get_collection_setting( 'location' );
  }
  #----------------------------------------------------------------------------------

  function proj_location_viewname() {
    return $this->proj_location_tablename() . '_view';
  }
  #----------------------------------------------------------------------------------

  function proj_location_sent_viewname() {

    $view = $this->proj_location_tablename() . '_sent_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_location_recd_viewname() {

    $view = $this->proj_location_tablename() . '_recd_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_location_all_works_viewname() {

    $view = $this->proj_location_tablename() . '_all_works_view';
    return $view;
  }
  #----------------------------------------------------------------------------------

  function proj_comment_tablename() {
    return $this->get_collection_setting( 'comment' );
  }
  #----------------------------------------------------------------------------------

  function proj_event_tablename() {
    return $this->get_collection_setting( 'event' );
  }
  #----------------------------------------------------------------------------------

  function proj_institution_tablename() {
    return $this->get_collection_setting( 'institution' );
  }
  #----------------------------------------------------------------------------------

  function proj_institution_viewname() {
    return $this->proj_institution_tablename() . '_view';
  }
  #----------------------------------------------------------------------------------

  function proj_institution_query_viewname() {
    return $this->proj_institution_tablename() . '_query_view';
  }
  #----------------------------------------------------------------------------------

  function proj_resource_tablename() {
    return $this->get_collection_setting( 'resource' );
  }
  #----------------------------------------------------------------------------------

  function proj_audit_trail_tablename() {
    return $this->get_collection_setting( 'audit_literal' );
  }
  #----------------------------------------------------------------------------------

  function proj_relationship_audit_trail_tablename() {
    return $this->get_collection_setting( 'audit_relationship' );
  }
  #----------------------------------------------------------------------------------

  function proj_audit_trail_viewname() {
    return $this->get_collection_setting( 'audit_trail_view' );
  }
  #----------------------------------------------------------------------------------

  function proj_audit_trail_table_viewname() {
    return str_replace( 'audit_trail_view', 'audit_trail_table_view',
                        $this->get_collection_setting( 'audit_trail_view' ));
  }
  #----------------------------------------------------------------------------------

  function proj_audit_trail_column_viewname() {
    return str_replace( 'audit_trail_view', 'audit_trail_column_view',
                        $this->get_collection_setting( 'audit_trail_view' ));
  }
  #----------------------------------------------------------------------------------

  function proj_work_type_tablename() {
    return $this->get_collection_setting( 'work_type' );
  }
  #----------------------------------------------------------------------------------

  function proj_speed_entry_text_tablename() {
    return $this->get_collection_setting( 'speed_entry_text' );
  }
  #----------------------------------------------------------------------------------

  function proj_role_category_tablename() {
    return $this->get_collection_setting( 'role_category' );
  }
  #----------------------------------------------------------------------------------

  function proj_catalogue_tablename() {
    return $this->get_system_prefix() . '_lookup_catalogue';
  }
  #----------------------------------------------------------------------------------

  function proj_document_type_tablename() {
    return $this->get_system_prefix() . '_lookup_document_type';
  }
  #----------------------------------------------------------------------------------

  function proj_org_type_tablename() {
    return $this->get_collection_setting() . 'org_type';
  }
  #----------------------------------------------------------------------------------

  function proj_org_subtype_tablename() {
    return $this->get_system_prefix() . '_org_subtype';
  }
  #----------------------------------------------------------------------------------

  function proj_nationality_tablename() {
    return $this->get_collection_setting( 'nationality' );
  }
  #----------------------------------------------------------------------------------

  function proj_nisba_tablename() { # only used by IMPAcT. Apparently a nisba is a geographical
                                    # affiliation, a bit like a nationality but elective.
    return $this->get_system_prefix() . '_nisba';
  }
  #----------------------------------------------------------------------------------

  function proj_users_and_roles_viewname() {
    return $this->get_system_prefix() . '_users_and_roles_view';
  }
  #----------------------------------------------------------------------------------

  function proj_drawer_tablename() { # for use with Selden End card index data (refers to drawer in filing cabinet)
    return 'cofk_selden_drawer';
  }
  #----------------------------------------------------------------------------------

  function proj_language_of_work_tablename() {
    return $this->get_collection_setting( 'language_of_work' );
  }
  #----------------------------------------------------------------------------------

  function proj_language_of_manifestation_tablename() {
    return $this->get_collection_setting( 'language_of_manifestation' );
  }
  #----------------------------------------------------------------------------------

  function proj_favourite_language_tablename() {
    return $this->get_collection_setting( 'favourite_language' );
  }
  #----------------------------------------------------------------------------------

  function proj_language_viewname() {
    return $this->get_collection_setting( 'language_view' );
  }
  #----------------------------------------------------------------------------------

  function proj_favourite_language_viewname() {
    return $this->get_collection_setting( 'favourite_language_view' );
  }
  #----------------------------------------------------------------------------------

  function proj_publication_tablename() {
    return $this->get_collection_setting( 'publication' );
  }
  #----------------------------------------------------------------------------------

  function proj_subject_tablename() {
    return $this->get_collection_setting( 'subject' );
  }
  #----------------------------------------------------------------------------------
  function proj_collect_tool_user_tablename() {
    return $this->get_system_prefix() . '_collect_tool_user';
  }
  #----------------------------------------------------------------------------------
  function proj_collect_addressee_of_work_tablename() {
    return $this->get_system_prefix() . '_collect_addressee_of_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_author_of_work_tablename() {
    return $this->get_system_prefix() . '_collect_author_of_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_image_of_manif_tablename() {
    return $this->get_system_prefix() . '_collect_image_of_manif';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_institution_tablename() {
    return $this->get_system_prefix() . '_collect_institution';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_location_tablename() {
    return $this->get_system_prefix() . '_collect_location';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_manifestation_tablename() {
    return $this->get_system_prefix() . '_collect_manifestation';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_occupation_of_person_tablename() {
    return $this->get_system_prefix() . '_collect_occupation_of_person';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_person_tablename() {
    return $this->get_system_prefix() . '_collect_person';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_person_mentioned_in_work_tablename() {
    return $this->get_system_prefix() . '_collect_person_mentioned_in_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_place_mentioned_in_work_tablename() {
    return $this->get_system_prefix() . '_collect_place_mentioned_in_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_subject_of_work_tablename() {
    return $this->get_system_prefix() . '_collect_subject_of_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_language_of_work_tablename() {
    return $this->get_system_prefix() . '_collect_language_of_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_upload_tablename() {
    return $this->get_system_prefix() . '_collect_upload';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_work_tablename() {
    return $this->get_system_prefix() . '_collect_work';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_work_summary_tablename() {
    return $this->proj_collect_work_tablename() . '_summary';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_work_summary_viewname() {
    return $this->proj_collect_work_summary_tablename() . '_view';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_status_tablename() {
    return $this->get_system_prefix() . '_collect_status';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_work_resource_tablename() {
    return $this->get_system_prefix() . '_collect_work_resource';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_person_resource_tablename() {
    return $this->get_system_prefix() . '_collect_person_resource';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_location_resource_tablename() {
    return $this->get_system_prefix() . '_collect_location_resource';
  }
  #----------------------------------------------------------------------------------

  function proj_collect_institution_resource_tablename() {
    return $this->get_system_prefix() . '_collect_institution_resource';
  }
  #----------------------------------------------------------------------------------

  function proj_primary_key( $tablename ) {

    switch( $tablename ) {

      case $this->proj_queryable_work_tablename():
        return 'work_id';

      default:
        $prefix_length = strlen( $this->collection_prefix . '_' );
        $colname = substr( $tablename, $prefix_length );
        $colname .= '_id';
        return $colname;
    }
  }
  #----------------------------------------------------------------------------------

  function proj_database_function_name( $core_function_name, $include_collection_code = FALSE ) {

    $function_name = 'dbf_' . $this->get_system_prefix() . '_';
    if( $include_collection_code && PROJ_COLLECTION_CODE ) $function_name .= PROJ_COLLECTION_CODE . '_';
    $function_name .= $core_function_name;
    return $function_name;
  }
  #----------------------------------------------------------------------------------

  function proj_id_seq_name( $table_name ) {

    $seq_name = '';

    switch( $table_name ) {

      case $this->proj_work_tablename():
      case $this->proj_queryable_work_tablename():
        $seq_name = $this->proj_work_tablename() . '_iwork_id_seq';
        break;

      case $this->proj_person_tablename():
        $seq_name = $this->proj_person_tablename() . '_iperson_id_seq';
        break;

      case $this->proj_audit_trail_tablename():
      case $this->proj_relationship_audit_trail_tablename():
        $seq_name = str_replace( 'literal', 'id_seq', $this->proj_audit_trail_tablename());
        break; 

      default:
        $seq_name = $table_name . '_id_seq';
        break;
    }

    return $seq_name;
  }
  #----------------------------------------------------------------------------------

  function proj_get_lefthand_side_of_rels( $right_table_name, $right_id_value, $order_by = NULL ) {

    if( ! $right_table_name ) return NULL;
    if( ! $right_id_value )   return NULL;

    if( ! $order_by )
      $order_by = 'left_table_name, relationship_valid_from, relationship_valid_till, relationship_id';

    $statement = 'select * from ' . $this->proj_relationship_tablename()
               . " where right_table_name = '$right_table_name' "
               . " and right_id_value = '$right_id_value' "
               . " order by $order_by";
    
    $rels = $this->db_select_into_array( $statement );
    return $rels;
  }
  #----------------------------------------------------------------------------------

  function proj_get_righthand_side_of_rels( $left_table_name, $left_id_value, $order_by = NULL ) {

    if( ! $left_table_name ) return NULL;
    if( ! $left_id_value )   return NULL;

    if( ! $order_by )
      $order_by = 'right_table_name, relationship_valid_from, relationship_valid_till, relationship_id';

    $statement = 'select * from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '$left_table_name' "
               . " and left_id_value = '$left_id_value' "
               . " order by $order_by";
    
    $rels = $this->db_select_into_array( $statement );
    return $rels;
  }
  #----------------------------------------------------------------------------------

  function write_work_entry_stylesheet() {

    echo '<style type="text/css">' . NEWLINE;

    echo ' span.popupinputfield input, label.popupnoentry {'                  . NEWLINE;
    echo '   margin-left: ' . WORK_FIELD_POS . 'px;'                          . NEWLINE; 
    echo ' }'                                                                 . NEWLINE;

    echo ' span.popupinputfield input {'                                      . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    . NEWLINE;
    echo ' }'                                                                 . NEWLINE;

    echo ' span.popupinputfield label {'                      . NEWLINE;
    echo '   position: absolute; '                            . NEWLINE;
    echo '   text-align:right; '                              . NEWLINE;
    echo '   width: ' . WORK_FIELD_LABEL_WIDTH . 'px;'        . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    #----

    echo ' table.relationshiplist td {'                       . NEWLINE;
    echo '   vertical-align:top;'                             . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.entitydesc {'                                   . NEWLINE; 
    echo '   padding: 8px;'                                   . NEWLINE;
    echo '   font-size: 11pt; '                               . NEWLINE;
    echo '   text-align: right; '                             . NEWLINE;
    echo '   background-color: ' .  html::get_highlight2_colour() . ';' . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.entityrels {'                                   . NEWLINE; 
    echo '   padding: 3px;'                                   . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    
                                                              . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.relunchecked {'                                 . NEWLINE; 
    echo '   font-weight: normal; '                           . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.relchecked { '                                  . NEWLINE; 
    echo '   font-weight: bold; '                             . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.delunchecked { '                                . NEWLINE; 
    echo '   padding: 8px;'                                   . NEWLINE;
    echo '   font-style: italic; '                            . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' td.delchecked { '                                    . NEWLINE; 
    echo '   padding: 8px;'                                   . NEWLINE;
    echo '   background-color: ' . html::header_background_colour() . ';' . NEWLINE;
    echo '   font-weight: bold; '                             . NEWLINE;
    echo ' }'                                                 . NEWLINE;


    #----

    echo ' input.basic_checkbox {'                            . NEWLINE;
    echo '   margin-left: 0px;'                               . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    #----

    echo ' .workfield label  {'                               . NEWLINE;
    echo '   position: absolute; '                            . NEWLINE;
    echo '   text-align:right; '                              . NEWLINE;
    echo '   width: ' . WORK_FIELD_LABEL_WIDTH . 'px;'        . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield input, .workfield textarea, .workfield select, span.workfieldaligned  {'           
                                                              . NEWLINE;
    echo '   margin-left: ' . WORK_FIELD_POS . 'px;'          . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' .workfield p {'                                    . NEWLINE;
    echo '   margin-top: 20px;'                               . NEWLINE; 
    echo '   margin-bottom: 20px;'                            . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    echo ' input.workfield_save_button  {'                    . NEWLINE;
    echo '   margin-left: ' . WORK_FIELD_POS . 'px;'          . NEWLINE; 
    echo ' }'                                                 . NEWLINE;
    #----

    echo ' td.editing_manif {'                                                . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    . NEWLINE;
#-- garbles Arabic script etc to try and put it into italic
#-- echo '   font-style: italic;'                                             . NEWLINE;
    echo ' }'                                                                 . NEWLINE;

    #----

    echo ' ul.calendartypes, ul.dateflags {'                  . NEWLINE;
    echo '   list-style-type: none;'                          . NEWLINE;
    echo '   position: relative; '                            . NEWLINE;
    echo '   left: '. WORK_FIELD_POS . 'px; '                 . NEWLINE;
    echo '   padding: 0px;'                                   . NEWLINE; 
    echo '   margin-left: 0px;'                               . NEWLINE; 
    echo ' }';

    echo ' li.calendartypes, li.dateflags {'                  . NEWLINE;
    echo '   padding: 0px;'                                   . NEWLINE; 
    echo '   margin-left: 0px;'                               . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    #----

    echo ' div.form_section_links_div  {'                     . NEWLINE;
    echo '   line-height: 20px;'                              . NEWLINE; 
    echo ' }'                                                 . NEWLINE;

    #----

    echo ' span.highlighted, textarea.highlighted, input.highlighted {'       . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    . NEWLINE;
    echo ' }'                                                                 . NEWLINE;

    echo ' .boldlabel label  {'                               . NEWLINE;
    echo '   font-weight: bold;'                              . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' .italiclabel label  {'                             . NEWLINE;
    echo '   font-style: italic;'                             . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo ' fieldset {'                                        . NEWLINE;
    echo '   padding: 10px;'                                  . NEWLINE;
    echo ' }'                                                 . NEWLINE;

    echo '</style>' . NEWLINE;
  }
  #----------------------------------------------------------------------------------

  function write_tabform_stylesheet() {

    # ---- Tab selection in 'edit work' form

    echo '<style type="text/css">' . NEWLINE;

    echo ' div.tabform { '                                                     . NEWLINE;
    echo '   background-color: ' . html::header_background_colour() . ';'      . NEWLINE;
    echo '   margin-top: 30px;'                                                . NEWLINE; 
    echo '   margin-bottom: 30px;'                                             . NEWLINE; 
    echo '   padding: 0px;'                                                    . NEWLINE; 
    echo ' }'                                                                  . NEWLINE;

    echo ' div.tabrow {'                                                       . NEWLINE;
    echo '   background-color: ' . html::header_background_colour() . ';'      . NEWLINE;
    echo '   padding: 0px;'                                                    . NEWLINE; 
    echo '   margin-bottom: 0px;'                                              . NEWLINE; 
    echo ' }'                                                                  . NEWLINE;

    echo ' input.fronttab {'                                                  . NEWLINE;
    echo '   font-weight: bold;'                                              . NEWLINE;
    echo '   font-size: 11pt;'                                                . NEWLINE;
    echo '   background-color: ' .  html::header_background_colour() . ';'    . NEWLINE;

    echo '   border-left-style: solid;'                                       . NEWLINE;
    echo '   border-left-width: 1px;'                                         . NEWLINE;
    echo '   border-left-color: ' . html::get_contrast1_colour() . ';'        . NEWLINE;

    echo '   border-right-style: solid;'                                      . NEWLINE;
    echo '   border-right-width: 1px;'                                        . NEWLINE;
    echo '   border-right-color: ' . html::get_contrast1_colour() . ';'       . NEWLINE;

    echo '   border-top-style: solid;'                                        . NEWLINE;
    echo '   border-top-width: 1px;'                                          . NEWLINE;
    echo '   border-top-color: ' . html::get_contrast1_colour() . ';'         . NEWLINE;

    echo '   border-bottom-style: solid;'                                     . NEWLINE;
    echo '   border-bottom-width: 1px;'                                       . NEWLINE;
    echo '   border-bottom-color: ' . html::header_background_colour() . ';'  . NEWLINE;

    echo '   border-top-left-radius:          8px;'                           . NEWLINE;
    echo '   -moz-border-top-left-radius:     8px;'                           . NEWLINE;
    echo '   -o-border-top-left-radius:       8px;'                           . NEWLINE;
    echo '   -webkit-border-top-left-radius:  8px;'                           . NEWLINE;
    echo '   border-top-right-radius:         8px;'                           . NEWLINE;
    echo '   -moz-border-top-right-radius:    8px;'                           . NEWLINE;
    echo '   -o-border-top-right-radius:      8px;'                           . NEWLINE;
    echo '   -webkit-border-top-right-radius: 8px;'                           . NEWLINE;

    echo ' }'                                                                 . NEWLINE;

    echo ' input.backtab {'                                                    . NEWLINE;
    echo '   background-color: white;'                                         . NEWLINE;

    echo '   border-left-style: solid;'                                        . NEWLINE;
    echo '   border-left-width: 1px;'                                          . NEWLINE;
    echo '   border-left-color: ' . html::get_contrast1_colour() . ';'         . NEWLINE;

    echo '   border-right-style: solid;'                                       . NEWLINE;
    echo '   border-right-width: 1px;'                                         . NEWLINE;
    echo '   border-right-color: ' . html::get_contrast1_colour() . ';'        . NEWLINE;

    echo '   border-top-style: solid;'                                         . NEWLINE;
    echo '   border-top-width: 1px;'                                           . NEWLINE;
    echo '   border-top-color: ' . html::get_contrast1_colour() . ';'          . NEWLINE;

    echo '   border-bottom-style: solid;'                                      . NEWLINE;
    echo '   border-bottom-width: 1px;'                                        . NEWLINE;
    echo '   border-bottom-color: ' . html::get_contrast1_colour() . ';'       . NEWLINE;

    echo '   border-top-left-radius:          5px;'                            . NEWLINE;
    echo '   -moz-border-top-left-radius:     5px;'                            . NEWLINE;
    echo '   -o-border-top-left-radius:       5px;'                            . NEWLINE;
    echo '   -webkit-border-top-left-radius:  5px;'                            . NEWLINE;
    echo '   border-top-right-radius:         5px;'                            . NEWLINE;
    echo '   -moz-border-top-right-radius:    5px;'                            . NEWLINE;
    echo '   -o-border-top-right-radius:      5px;'                            . NEWLINE;
    echo '   -webkit-border-top-right-radius: 5px;'                            . NEWLINE;

    echo ' }'                                                                  . NEWLINE;

    echo ' p.undertabs  { '                                                    . NEWLINE;
    echo '   margin-top: 2px;'                                                 . NEWLINE; 
    echo '   margin-bottom: 2px;'                                              . NEWLINE; 
    echo '   padding: 2px;'                                                    . NEWLINE; 
    echo '   height: 2px;'                                                     . NEWLINE; 
    echo ' }'                                                                  . NEWLINE;

    echo '</style>' . NEWLINE;
  }

  #----------------------------------------------------------------------------------

  function proj_make_text_id( $text_element1, $text_element2, $orig_id ) {

    $statement = "select proj_common_make_text_id( '$text_element1', '$text_element2', $orig_id )";
    $text_id = $this->db_select_one_value( $statement );
    return $text_id;
  }
  #----------------------------------------------------------------------------------

  function proj_get_description_from_id( $entity_id ) {  # This method MUST be overridden in child class

    return 'Entity description must be passed back here.';
  }
  #----------------------------------------------------------------------------------
  # See Editable Work for how to use 'date entry fieldset' method

  function date_entry_fieldset( $date_fields, $legend=NULL, $extra_msg=NULL, $calendar_fieldname = NULL,
                                $date_range_help = array( DATE_RANGE_HELP_1, DATE_RANGE_HELP_2 ),
                                $hide_sortable_dates = FALSE, $include_uncertainty_flags = FALSE ) {

    if( $this->date_entity )
      $this->date_entity->clear();
    else
      $this->date_entity = new Date_Entity( $this->db_connection );

    $displayed_fields = $this->date_entity->list_properties_to_set( $date_fields );

    foreach( $displayed_fields as $fieldname => $empty ) {
      $displayed_fields[ "$fieldname" ] = $this->$fieldname;
    }

    $this->date_entity->set_properties( $displayed_fields );

    $this->date_entity->date_entry_fieldset( $date_fields, $calendar_fieldname, $legend, $extra_msg,
                                             $hide_sortable_dates, $include_uncertainty_flags,
                                             $date_range_help );
  }
  #----------------------------------------------------------------------------------

  function make_complete_date( $year, $month, $day, $is_julian = FALSE ) {

    if( ! $this->date_entity )
      $this->date_entity = new Date_Entity( $this->db_connection );

    $returned_date = $this->date_entity->make_complete_date( $year, $month, $day, $is_julian );
    if( $this->date_entity->failed_validation ) $this->failed_validation = TRUE;
    return $returned_date;
  }
  #----------------------------------------------------------------------------------

  function proj_get_entries_for_fieldgroup( $fieldgroup ) { # e.g. the Author/Sender field group

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );

    $settings = $this->rel_obj->get_relationship_field_setting( $fieldgroup ); 
    extract( $settings, EXTR_OVERWRITE );

    $relevant_relationship_types = $reltypes;
    $required_table = NULL;

    if( $side_to_get == 'both' ) {  # e.g. symmetrical relationships like 'friend', 'spouse'
      if( $left_table_name == $right_table_name ) $required_table = $left_table_name;

      return $this->proj_get_entries_for_reltypes_both_sides( $relevant_relationship_types,
                                                              $required_table );
    }
    elseif( $side_to_get == 'left' || $side_to_get == 'right' ) {
      if( $side_to_get == 'left' ) {
        $relationship_list = $this->this_on_right;
        $required_table = $left_table_name;
        $id_column_name = 'left_id_value';
      }
      else {
        $relationship_list = $this->this_on_left;
        $required_table = $right_table_name;
        $id_column_name = 'right_id_value';
      }
      return $this->get_entries_for_reltypes( $relationship_list, $relevant_relationship_types, $id_column_name,
                                              $required_table );
    }
  }

  #----------------------------------------------------------------------------------
  # Essentially you select a list of relationships, such as all the relationships for a work, into
  # two arrays (e.g. 'work on right' containing 'author - was creator of - work', or 'work on left'
  # containing 'work - was addressed to - recipient'). Pass in one of the arrays, a list of relevant
  # relationship types such as 'was creator of', and an indication of whether the author ID, etc, is to
  # be found on the left or right of the relationship. Optionally also pass in the table name such
  # as 'person' where the author details are to be found (in case the same relationship code is used
  # for different purposes). 
  #
  # An array of ID values and all the types of relationship in which they occur will then be passed back.
  # E.g. results[0] [Comenius ID] [0]=>Author [1]=>Sender
  #      results[1] [Figulus ID] [0]=>Signatory

  function get_entries_for_reltypes( $relationship_list, $relevant_relationship_types, $id_column_name,
                                     $required_table = NULL ) {

    $results = array();

    foreach( $relationship_list as $one_relationship ) {
      extract( $one_relationship, EXTR_OVERWRITE );

      if( $required_table ) {
        if( $id_column_name == 'left_id_value' ) 
          $table_col = 'left_table_name';
        else
          $table_col = 'right_table_name';
        if( $$table_col != $required_table ) continue;
      }

      if( key_exists( $relationship_type, $relevant_relationship_types ) ) { # found someone with relevant role
        $id_value = $$id_column_name;
        if( key_exists( $id_value, $results ) ) {  # one or more roles already found for person
          $results[ "$id_value" ][] = $relationship_type;  # add an extra role to the array
        }
        else {
          $results[ "$id_value" ] = array( $relationship_type );
        }
      }
    }
    return $results;
  }
  #----------------------------------------------------------------------------------

  function proj_get_entries_for_reltypes_both_sides( $relevant_relationship_types, # array of: code => description
                                                     $required_table = NULL ) {    # table on the other side from this
    #-----------------------------------------------------------------------------------------------------------
    # Need to get relationships with this table/ID either on right or on left.
    #-----------------------------------------------------------------------------------------------------------
    # Arrays '$this->this_on_left' and '$this->this_on_right' should have been populated with rows from the 
    # 'relationships' table. Let's say you want location ID 123. In '$this->this_on_left', you want all the rows 
    # where the lefthand table is location and the lefthand ID is 123. In '$this->this_on_right',
    # you want all the rows where the righthand table is location and the righthand ID is 123.
    #-----------------------------------------------------------------------------------------------------------

    if( ! is_array( $this->this_on_left )) $this->this_on_left = array();
    if( ! is_array( $this->this_on_right )) $this->this_on_right = array();
    $combined_results = array();

    $rels_with_this_on_left = $this->get_entries_for_reltypes( 
                                     $relationship_list           = $this->this_on_left,
                                     $relevant_relationship_types = $relevant_relationship_types,
                                     $id_column_name              = 'right_id_value',
                                     $required_table              = $required_table );

    $rels_with_this_on_right = $this->get_entries_for_reltypes( 
                                     $relationship_list           = $this->this_on_right,
                                     $relevant_relationship_types = $relevant_relationship_types,
                                     $id_column_name              = 'left_id_value',
                                     $required_table              = $required_table );

    # Merge the two
    if( count( $rels_with_this_on_left ) > 0 ) {
      foreach( $rels_with_this_on_left as $key => $rellist ) {
        $combined_results[ "$key" ] = $rellist;
      }
    }

    if( count( $rels_with_this_on_right ) > 0 ) {
      foreach( $rels_with_this_on_right as $key => $rellist ) {

        if( key_exists( $key, $combined_results )) { # already in other list with different relationship?
          foreach( $rellist as $reltype ) {
            if( ! in_array( $reltype, $combined_results[ "$key" ] ))
              $combined_results[ "$key" ][] = $reltype;
          }
        }
        else {
          $combined_results[ "$key" ] = $rellist;
        }
      }
    }

    return $combined_results;
  }
  #----------------------------------------------------------------------------------

  function proj_get_relationships_of_type( $reltype ) {  # pre-selected into arrays 'this on left' and 'this on right'

    $rels = array();
    if( ! $reltype ) return $rels;

    foreach( $this->this_on_left as $row ) {
      if( $row[ 'relationship_type' ] == $reltype ) {
        $rels[] = $row;
      }
    }

    foreach( $this->this_on_right as $row ) {
      if( $row[ 'relationship_type' ] == $reltype ) {
        $rels[] = $row;
      }
    }

    return $rels;
  }
  #----------------------------------------------------------------------------------

  function proj_get_rels_for_notes( $reltype = NULL ) {

    if( ! $reltype ) $reltype = RELTYPE_COMMENT_REFERS_TO_ENTITY;

    $notes = array();

    foreach( $this->this_on_right as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $left_table_name == $this->proj_comment_tablename() && $relationship_type == $reltype ) {
        $notes[] = $row;
      }
    }

    return $notes;
  }
  #----------------------------------------------------------------------------------

  function proj_get_rels_for_resources() {

    $resources = array();

    foreach( $this->this_on_left as $row ) {
      extract( $row, EXTR_OVERWRITE );
      if( $right_table_name == $this->proj_resource_tablename() 
      && $relationship_type == RELTYPE_ENTITY_HAS_RESOURCE ) {

        $resources[] = $row;
      }
    }

    return $resources;
  }
  #----------------------------------------------------------------------------------

  function proj_notes_field( $reltype = NULL, $label = 'Notes:' ) {

    if( ! $reltype ) $reltype = RELTYPE_COMMENT_REFERS_TO_ENTITY;

    if( ! $this->comment_obj ) $this->comment_obj = new Comment( $this->db_connection );

    $notes = $this->proj_get_rels_for_notes( $reltype );

    $this->comment_obj->display_or_edit_comments( $notes, $reltype, $label, $this->form_name, 'bold' );
  }
  #-----------------------------------------------------

  function basic_checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1, 
                           $parms = NULL, $label_on_left = FALSE ) {

    $style = ' class="basic_checkbox" ';
    $parms .= $style;

    html::checkbox( $fieldname, $label, $is_checked, $value_when_checked, $in_table = FALSE,
                     $tabindex=1, $input_instance = NULL, $parms = $parms, $label_on_left );
  }
  #----------------------------------------------------------------------------------

  function proj_initial_popup_selection_method() {  # can be overridden by child class

    return NULL;  # use default
  }
  #----------------------------------------------------------------------------------

  function proj_initial_popup_add_method() {  # can be overridden by child class

    return NULL;  # use default
  }
  #----------------------------------------------------------------------------------

  function proj_enable_popup_add_method() {  # can be overridden by child class

    return TRUE;  # by default have an 'Add' button when calling popups
  }
  #----------------------------------------------------------------------------------

  function set_popup_object( $popup_object_name, $popup_object_class = NULL ) {

    if( ! $popup_object_name  ) $popup_object_name = 'popup_' . $this->app_get_class( $this );  # e.g. popup_person

    if( ! $this->$popup_object_name ) {  # no property such as 'popup_person' exists
      if( ! $popup_object_class ) 
        $popup_object_class = 'popup_' . $this->app_get_class( $this ); # by default property and class have same name

      if( class_exists( $popup_object_class ))
        $this->$popup_object_name = new $popup_object_class( $this->db_connection );
      else
        die( 'No popup object class specified.' );
    }

    $this->popup_object_name = $popup_object_name;

    return $popup_object_name;
  }
  #----------------------------------------------------------------------------------

  function proj_edit_area_calling_popups( $fieldset_name,               # e.g. 'author_sender'
                                          $section_heading,             # e.g. 'Authors/senders:'
                                          $decode_display,              # e.g. 'author/sender'
                                          $separate_section = TRUE,     # add horizontal rule, bold heading, Save key
                                          $extra_notes = NULL,
                                          $popup_object_name = NULL,       # optional: e.g. 'person_selecter'
                                          $popup_object_class = NULL,      # optional: e.g. 'popup_person'
                                          $include_date_fields = FALSE ) {

    $popup_object_name = $this->set_popup_object( $popup_object_name, $popup_object_class );
    $popup_object = $this->$popup_object_name;

    $results = $this->proj_get_entries_for_fieldgroup( $fieldset_name );
    $result_count = count( $results );
    html::anchor( $fieldset_name );

    if( $include_date_fields ) $this->proj_write_rel_date_fields_check_func( $fieldset_name );

    $heading_label_class = 'workfieldaligned';
    if( $separate_section ) {  # horizontal rule and bold heading
      html::horizontal_rule();
      html::new_paragraph();
      $heading_label_class .= ' boldlabel';
    }
    else
      $heading_label_class .= ' italiclabel';

    if( $section_heading ) {
      $heading_label_parms = 'class="' . $heading_label_class . '"';
      html::span_start( $heading_label_parms );
      html::label( $section_heading, $label_id=$fieldset_name . '_sectionhead' );
      html::span_end();
      html::new_paragraph();
    }

    if( $result_count >= 1 ) {
      $this->proj_edit_existing_rels( $fieldset_name, $results, $decode_display, $include_date_fields );
      html::new_paragraph();
    }

    $add_label = 'Add ';
    if( $result_count > 0 ) $add_label .= 'another ';

    if( $this->proj_enable_popup_add_method()) 
      $decode_field_initial_value = 'Please select or create ';
    else
      $decode_field_initial_value = 'Please select ';
    $decode_field_initial_value .= $decode_display . ', then click Save.';

    # Entry field for new author/sender, scribe, enclosure within manifestation, etc.
    $popup_object->proj_input_fields_calling_popups( 
                           $calling_form       = $this->form_name, 
                           $calling_field      = $this->proj_new_id_fieldname_from_fieldset_name( $fieldset_name ),
                           $decode_fieldname   = $this->proj_new_decode_fieldname_from_fieldset_name( $fieldset_name ),
                           $decode_field_label = $add_label . $decode_display,
                           $decode_field_initial_value,
                           $selection_method = NULL, $add_method = NULL,
                           $calling_field_initial_value = NULL,
                           $include_date_fields, $fieldset_name ); 

    if( $extra_notes ) {
      html::new_paragraph();
      html::italic_start();
      $this->echo_safely( $extra_notes );
      html::italic_end();
      html::new_paragraph();
    }

    if( $separate_section ) {
      html::new_paragraph();
      html::submit_button( 'save_' . $fieldset_name . '_button', 'Save', $tabindex = 1, 'class="workfield_save_button"' );
    }
  }
  #--------------------------------------------------------------

  # This function writes out fields for adding NEW relationships.

  function proj_input_fields_calling_popups( $calling_form, $calling_field, 
                                             $decode_fieldname, $decode_field_label = 'Add an entry',
                                             $decode_field_initial_value = 'Please select or create record',
                                             $selection_method = NULL, $add_method = NULL,
                                             $calling_field_initial_value = NULL,
                                             $include_date_fields = FALSE, $fieldgroup_name = NULL ) {

    html::new_paragraph();
    html::hidden_field( $calling_field, $calling_field_initial_value );

    html::span_start( 'class="popupinputfield"' );

    html::input_field( $decode_fieldname, 
                       $decode_field_label, 
                       $decode_field_initial_value,
                       $in_table = FALSE, # handle this manually
                       $size = POPUP_SELECTION_DECODE_FIELD_SIZE, 
                       $tabindex=1,
                       $label_parms = NULL, 
                       $data_parms = NULL, 
                       $input_parms = 'READONLY' );

    html::span_end(); # end CSS styling for input field
    echo ' ';

    if( ! $selection_method ) 
      $selection_method = $this->proj_initial_popup_selection_method();

    if( ! $add_method )
      $add_method = $this->proj_initial_popup_add_method();

    $this->app_popup_searchform_caller( $calling_form, $calling_field, $method_to_call = $selection_method,
                                        $select_desc = NULL, $view_desc = NULL );

    echo ' ';

    if( $this->proj_enable_popup_add_method()) {
      $this->app_popup_addform_caller( $calling_form, $calling_field, $method_to_call = $add_method,
                                       $option_desc = NULL );
    }

    $this->proj_extra_popup_buttons( $id_fieldname = $calling_field, $decode_fieldname );

    html::new_paragraph();

    if( $include_date_fields ) {
      $start_fieldname = $this->proj_start_fieldname_from_id_fieldname( $calling_field );
      $end_fieldname = $this->proj_end_fieldname_from_id_fieldname( $calling_field );

      html::span_start( 'class="workfieldaligned"' );
      echo 'Relevant dates: ';

      $check_function = $this->proj_get_funcname_for_rel_date_fields_check( $fieldgroup_name );
      $input_parms = 'onchange="' . $check_function . '( this, ' . "''" . ' )"';

      html::span_start( 'class="narrowspaceonleft"' );
      html::input_field( $start_fieldname, 'From', NULL, FALSE, $size=FLD_SIZE_RELATIONSHIP_DATES, 
                         $tabindex=1, NULL, NULL, $input_parms  ); 
      html::span_end();

      echo ' ';

      html::span_start( 'class="narrowspaceonleft"' );
      html::input_field( $end_fieldname, 'To', NULL, FALSE, $size=FLD_SIZE_RELATIONSHIP_DATES, 
                         $tabindex=1, NULL, NULL, $input_parms  ); 
      html::span_end();

      html::span_start( 'class="narrowspaceonleft"' );
      html::italic_start();
      if( $this->get_system_prefix() == IMPACT_SYS_PREFIX )
        echo '(Enter CE year only, OR full date in dd/mm/yyyy format)';
      else
        echo '(Enter year only, OR full date in dd/mm/yyyy format)';
      html::italic_end();
      html::span_end();

      html::span_end();

      html::new_paragraph();
    }
  }
  #-----------------------------------------------------
  function proj_extra_popup_buttons( $id_fieldname, $decode_fieldname ) {  

    # Override this function in child class if you want extra buttons as well as the standard
    # 'Select' and 'Create'  when calling popup selection forms.
  }
  #-----------------------------------------------------

  function proj_get_id_field_marker() {

    return '_proj_id_field';
  }
  #-----------------------------------------------------

  function proj_get_decode_field_marker() {

    return '_proj_decode_field';
  }
  #-----------------------------------------------------

  function proj_get_start_date_field_marker() {

    return '_proj_start_date_field';
  }
  #-----------------------------------------------------

  function proj_get_end_date_field_marker() {

    return '_proj_end_date_field';
  }
  #-----------------------------------------------------

  function proj_id_fieldname_from_fieldset_name( $fieldset_name, $entity_id = NULL ) {

    $entity_fieldname = $fieldset_name . $this->proj_get_id_field_marker() . $entity_id;
    return $entity_fieldname;
  }
  #-----------------------------------------------------

  function proj_new_id_fieldname_from_fieldset_name( $fieldset_name ) {

    return $this->proj_id_fieldname_from_fieldset_name( 'new_' . $fieldset_name, NULL );
  }
  #-----------------------------------------------------

  function proj_new_decode_fieldname_from_fieldset_name( $fieldset_name ) {

    $id_fieldname = $this->proj_new_id_fieldname_from_fieldset_name( $fieldset_name );

    $decode_fieldname = $this->proj_decode_fieldname_from_id_fieldname( $id_fieldname );

    return $decode_fieldname;
  }
  #-----------------------------------------------------

  function proj_decode_fieldname_from_id_fieldname( $id_fieldname ) {

    $decode_fieldname = str_replace( $this->proj_get_id_field_marker(), 
                                     $this->proj_get_decode_field_marker(), 
                                     $id_fieldname );
    return $decode_fieldname;
  }
  #-----------------------------------------------------

  function proj_start_fieldname_from_id_fieldname( $id_fieldname, $reltype = NULL ) {

    # The 'ID fieldname' gives the field GROUP, such as Author/Sender, which comprises author, sender, signatory.
    # The 'Reltype' gives the individual relationship type, e.g. just one out of author, sender or signatory.

    $start_fieldname = str_replace( $this->proj_get_id_field_marker(), 
                                    $this->proj_get_start_date_field_marker(), 
                                    $id_fieldname );

    if( $reltype ) $start_fieldname = $reltype . $start_fieldname;
    return $start_fieldname;
  }
  #-----------------------------------------------------

  function proj_end_fieldname_from_id_fieldname( $id_fieldname, $reltype = NULL ) {

    # As above, 'ID fieldname' gives the field GROUP, such as Author/Sender (all three of author, sender, signatory).
    # The 'Reltype' gives the individual relationship type, e.g. just one out of author, sender or signatory.

    $end_fieldname = str_replace( $this->proj_get_id_field_marker(), 
                                  $this->proj_get_end_date_field_marker(), 
                                  $id_fieldname );

    if( $reltype ) $end_fieldname = $reltype . $end_fieldname;
    return $end_fieldname;
  }
  #-----------------------------------------------------

  function proj_make_rel_checkbox_name( $fieldset_name, $relationship_code, $entity_id = NULL ) {

    $rel_checkbox_name = $fieldset_name . '_' . $relationship_code . $this->proj_get_id_field_marker() . $entity_id;
    return $rel_checkbox_name ;
  }
  #-----------------------------------------------------

  function proj_get_entitydesc_td_width( $fieldset_name ) {  # override in child class if required

    switch( $fieldset_name ) {
      default:
        return ENTITYDESC_TD_WIDTH;
    }
  }
  #-----------------------------------------------------

  function proj_edit_existing_rels( $fieldset_name, $results, $core_decode, $include_date_fields = FALSE ) {

    if( ! is_array( $results )) return;

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );
    $relevant_relationship_types = $this->rel_obj->get_relationship_field_setting( $fieldset_name, 'reltypes' );
    if( ! is_array( $relevant_relationship_types )) return;

    if( count( $results ) < 1 ) return;
    if( count( $relevant_relationship_types ) < 1 ) return;

    $popup_object_name = $this->set_popup_object( $this->popup_object_name );
    $popup_object = $this->$popup_object_name;

    $entitydesc_style = $fieldset_name . '_entitydesc';

    echo '<style type="text/css">'                                                 . NEWLINE;
    echo ' td.' . $entitydesc_style . ' {'                                          . NEWLINE; 
    echo '   width: ' . $this->proj_get_entitydesc_td_width( $fieldset_name ) . 'px; ' . NEWLINE;
    echo ' }'                                                                      . NEWLINE;
    echo '</style>'                                                                . NEWLINE;

    html::table_start( 'class="relationshiplist"' );

    foreach( $results as $entity_id => $rels ) {

      html::tablerow_start();

      #--------------------------------------------
      # Display decode for one side of relationship
      #--------------------------------------------
      html::tabledata_start( 'class="entitydesc ' . $entitydesc_style . '"' );

      if( is_a( $popup_object, 'work' )) {
        $iwork_id = $popup_object->set_work_by_text_id( $entity_id );
         $desc = $popup_object->get_work_desc( $entity_id );
         $funcname = $this->proj_database_function_name( 'link_to_edit_app', 
                                                         $include_collection_code = TRUE );
         $statement = "select $funcname ( '" . $this->escape( $desc )  . "', "
                    . "'?iwork_id=$iwork_id' )";
         $desc = $this->db_select_one_value( $statement );
      }
      else
        $desc = $popup_object->proj_get_description_from_id( $entity_id );
      $this->echo_safely( $desc );

      $id_fieldname = $this->proj_id_fieldname_from_fieldset_name( $fieldset_name, $entity_id );
      html::hidden_field( $id_fieldname, $entity_id );
      html::tabledata_end();

      #----------------------------------------------------
      # Checkboxes allowing selection of relationship types,
      #----------------------------------------------------
      $possible_rels = count( $relevant_relationship_types );

      $display_checkboxes = FALSE;
      if( $possible_rels > 1 ) $display_checkboxes = TRUE;
      $current_rel = 0;

      if( $include_date_fields || $display_checkboxes ) {
        html::tabledata_start( 'class="entityrels"' );
        html::table_start();
      }


      foreach( $relevant_relationship_types as $relationship_code => $label ) {
        if( $include_date_fields || $display_checkboxes ) html::tablerow_start();
        $current_rel++;

        $rel_checkbox = $this->proj_make_rel_checkbox_name( $fieldset_name, $relationship_code, $entity_id );
        $is_checked = FALSE;

        if( $this->failed_validation ) {  # Re-displaying earlier data entry so they can correct it.
                                          # Use values from POST.
          # Looks like display of POST values needs adding here! And also for start/end dates below.
        }
        else {
          if( in_array( $relationship_code, $rels )) {
            $is_checked = TRUE;
          }
        }

        if( $display_checkboxes ) { 
          # Display a list of 'role' checkboxes if there is more than one to choose from.
          $selection_td = 'td_' . $rel_checkbox;
          if( $is_checked )
            $checkbox_td_class = 'relchecked';
          else
            $checkbox_td_class = 'relunchecked';
          $action = '"showIfRelSelected( this, ' . "'$selection_td'" . ')"';

          html::tabledata_start( 'class="entityrels ' . $checkbox_td_class . '" id="' . $selection_td . '"' );
          html::checkbox( $rel_checkbox, $label, $is_checked, $value_when_checked = $relationship_code, FALSE, 1, NULL,
                          $parms = "onchange=$action onclick=$action" );
          html::tabledata_end();
        }
        else {
          # If there is only one option, it can be a hidden field because the user does not need to change it.
          html::hidden_field( $rel_checkbox, $relationship_code );
        }

        #----------------
        # Start/end dates
        #----------------
        if( $include_date_fields ) {
          html::tabledata_start( 'class="entityrels"' );  # start list of start/end dates
          $this->proj_relationship_date_entry_fields( $fieldset_name, $entity_id, $relationship_code, $rel_checkbox );
          html::tabledata_end();  # end list of start/end dates
        }
        if( $include_date_fields || $display_checkboxes ) html::tablerow_end();
      }

      if( $include_date_fields || $display_checkboxes ) {
        html::table_end();
        html::tabledata_end();
      }

      #------------------
      # Deletion checkbox
      #------------------
      $deletion_checkbox = 'delete_' . $id_fieldname;
      $deletion_td = 'td_delete_' . $id_fieldname;
      $action = '"showIfForDeletion( this, ' . "'$deletion_td'" . ')"';

      if( $this->failed_validation && $this->parm_found_in_post( $deletion_checkbox )) {
        $is_checked = TRUE;
        $deletion_td_style = 'delchecked';
      }
      else {
        $is_checked = FALSE;
        $deletion_td_style = 'delunchecked';
      }
        
      html::tabledata_start( 'class="' . $deletion_td_style . '"  id="' . $deletion_td . '"' );
      $label = "Delete from '$core_decode' list";
      html::checkbox( $deletion_checkbox, $label, $is_checked, $value_when_checked = $entity_id, FALSE, 1, NULL, 
                      $parms = "onchange=$action onclick=$action" );
      html::tabledata_end();

      html::tablerow_end();
    }

    html::table_end();
  }
  #----------------------------------------------------------------------------------

  function proj_relationship_date_entry_fields( $fieldgroup_name, $entity_id, $relationship_code, $rel_checkbox ) {

    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );
    $side_to_get = $this->rel_obj->get_relationship_field_setting( $fieldgroup_name, 'side_to_get' );

    $id_fieldname = $this->proj_id_fieldname_from_fieldset_name( $fieldgroup_name, $entity_id );
    $start_fieldname = $this->proj_start_fieldname_from_id_fieldname( $id_fieldname, $relationship_code );
    $end_fieldname = $this->proj_end_fieldname_from_id_fieldname( $id_fieldname, $relationship_code );

    $start_date = NULL;
    $end_date = NULL;

    $rels = $this->proj_get_relationships_of_type( $relationship_code );
    foreach( $rels as $rel ) {
      extract( $rel, EXTR_OVERWRITE );
      $found = FALSE;
      $id_column = NULL;

      if( $side_to_get == 'both' ) {  # e.g. in a relationship like 'friend', we might need either side
        if( $left_id_value == $entity_id || $right_id_value == $entity_id ) 
          $found = TRUE;
      }
      elseif( $side_to_get == 'left' || $side_to_get == 'right' ) {
        $id_column = $side_to_get . '_id_value';
        if( $$id_column == $entity_id )
          $found = TRUE;
      }

      if( $found ) {
        $start_date = $relationship_valid_from;
        if( $start_date ) 
          $start_date = substr( $this->postgres_date_to_dd_mm_yyyy( $start_date ), 0, strlen('dd/mm/yyyy'));

        $end_date = $relationship_valid_till;
        if( $end_date ) 
          $end_date = substr( $this->postgres_date_to_dd_mm_yyyy( $end_date ), 0, strlen('dd/mm/yyyy'));

        break;
      }
    }

    $check_function = $this->proj_get_funcname_for_rel_date_fields_check( $fieldgroup_name );
    $input_parms = 'onchange="' . $check_function . '( this, ' . "'$rel_checkbox'" . ' )"';

    html::input_field( $start_fieldname, 'From', $start_date, FALSE, $size=FLD_SIZE_RELATIONSHIP_DATES, 
                       $tabindex=1, NULL, NULL, $input_parms );
    echo ' ';
    html::input_field( $end_fieldname, 'To', $end_date, FALSE, $size=FLD_SIZE_RELATIONSHIP_DATES, 
                       $tabindex=1, NULL, NULL, $input_parms );
  }
  #----------------------------------------------------------------------------------

  function proj_write_rel_date_fields_check_func( $fieldgroup_name ) {

    $function_name = 'check_' . $fieldgroup_name . '_dates';

    $script = ' function ' . $function_name . '( field, checkbox_name ) { '             . NEWLINE

            # If value is blank, no further checks are needed.
            . '   field.value = field.value.replace( " ", "" );'                        . NEWLINE
            . '   var fieldval = field.value;'                                          . NEWLINE
            . '   if( fieldval == "" ) { '                                              . NEWLINE
            . '     return;'                                                            . NEWLINE
            . '   } '                                                                   . NEWLINE

            # If value is non-blank, make sure corresponding checkbox is ticked.
            . '   if( checkbox_name != "" ) {'                                          . NEWLINE
            . '     var rel_checkbox=document.getElementById( checkbox_name );'         . NEWLINE
            . '     rel_checkbox.checked = true;'                                       . NEWLINE
            . '     var checkbox_td_name = "td_" + checkbox_name;'                      . NEWLINE
            . '     var checkbox_td = document.getElementById( checkbox_td_name );'     . NEWLINE
            . '     checkbox_td.className = "entityrels relchecked";'                   . NEWLINE
            . '   } '                                                                   . NEWLINE

            # See if they've entered integer year. Put up a message if it is more than 4 figures.
            . '   var is_integer = true;'                                               . NEWLINE
            . '   var one_char = "";'                                                   . NEWLINE
            . '   for( i = 0; i < fieldval.length; i++ ) { '                            . NEWLINE
            . '     one_char = fieldval.substr( i, 1 );'                                . NEWLINE
            . '     if( one_char < "0" || one_char > "9" ) { '                          . NEWLINE
            . '       is_integer = false; '                                             . NEWLINE
            . '       break;'                                                           . NEWLINE
            . '     } '                                                                 . NEWLINE
            . '   } '                                                                   . NEWLINE

            . '   if( is_integer == true ) { '                                          . NEWLINE
            . '     if( fieldval.length > 4 ) {'                                        . NEWLINE
            . '       alert( "Invalid year: too large!" );'                             . NEWLINE
            . '     } '                                                                 . NEWLINE
            . '     return;'                                                            . NEWLINE
            . '   } '                                                                   . NEWLINE

            # If they didn't enter a year, see if they entered a date in dd/mm/yyyy format.
            . '   var is_date = true;'                                                  . NEWLINE
            . '   date_parts = fieldval.split( "/" );'                                  . NEWLINE
            . '   if( date_parts.length != 3 ) { '                                      . NEWLINE
            . '     is_date = false;'                                                   . NEWLINE
            . '   } '                                                                   . NEWLINE

            . '   if( is_date == true ) { '                                             . NEWLINE
            . '     var date_part = "";'                                                . NEWLINE
            . '     for( i = 0; i < 3; i++ ) { '                                        . NEWLINE
            . '       date_part = date_parts[ i ];'                                     . NEWLINE
            . '       for( j = 0; j < date_part.length; j++ ) { '                       . NEWLINE
            . '         one_char = date_part[ j ];'                                     . NEWLINE
            . '         if( one_char < "0" || one_char > "9" ){ '                       . NEWLINE
            . '           is_date = false; '                                            . NEWLINE
            . '           break;'                                                       . NEWLINE
            . '         } '                                                             . NEWLINE
            . '       } '                                                               . NEWLINE
            . '       if( is_date == false ) { '                                        . NEWLINE
            . '         break;'                                                         . NEWLINE
            . '       } '                                                               . NEWLINE
            . '       number = parseInt( date_part, 10 ); '                             . NEWLINE
            . '       if( number < 1 ) { '                                              . NEWLINE
            . '         is_date = false;'                                               . NEWLINE
            . '         break;'                                                         . NEWLINE
            . '       } '                                                               . NEWLINE
            . '       if( i == 0 && number > 31 ) { '                                   . NEWLINE
            . '         alert( "Invalid day: too large!" );'                            . NEWLINE
            . '         return;'                                                        . NEWLINE
            . '       } '                                                               . NEWLINE
            . '       else if( i == 1 && number > 12 ) { '                              . NEWLINE
            . '         alert( "Invalid month: too large!" );'                          . NEWLINE
            . '         return;'                                                        . NEWLINE
            . '       } '                                                               . NEWLINE
            . '       else if( i == 2 && number > 9999 ) { '                            . NEWLINE
            . '         alert( "Invalid year: too large!" );'                           . NEWLINE
            . '         return;'                                                        . NEWLINE
            . '       } '                                                               . NEWLINE
            . '     } '                                                                 . NEWLINE
            . '   } '                                                                   . NEWLINE

            # Neither a year nor a date, so put up a message.
            . '   if( is_date == false ) { '                                            . NEWLINE
            . '     alert("Invalid value for date: not a year or dd/mm/yyyy!")'         . NEWLINE
            . '   } '                                                                   . NEWLINE
            . ' } '                                                                     . NEWLINE;

    html::write_javascript_function( $script );
  }
  #----------------------------------------------------------------------------------

  function proj_get_funcname_for_rel_date_fields_check( $fieldgroup_name ) {
    $function_name = 'check_' . $fieldgroup_name . '_dates';
    return $function_name;
  }
  #----------------------------------------------------------------------------------

  function write_display_change_script() {

    $script  = ' function changeDisplayStyleOnClick( theCheckbox, tdName, checkedStyle, uncheckedStyle ) { ' . NEWLINE;  
    $script .= '   var checkboxCell = document.getElementById( tdName );'               . NEWLINE;
    $script .= '   var newDisplayStyle = uncheckedStyle;'                               . NEWLINE;
    $script .= '   if( theCheckbox.checked ) {'                                         . NEWLINE;
    $script .= '     newDisplayStyle = checkedStyle;'                                   . NEWLINE;
    $script .= '   }   '                                                                . NEWLINE;
    $script .= '   checkboxCell.className = newDisplayStyle;'                           . NEWLINE;
    $script .= ' }   '                                                                  . NEWLINE;

    html::write_javascript_function( $script );

    $script  = ' function showIfForDeletion( theCheckbox, tdName ) {   '                            . NEWLINE;  
    $script .= '   changeDisplayStyleOnClick( theCheckbox, tdName, "delchecked", "delunchecked" );' . NEWLINE;  
    $script .= ' }   '                                                                              . NEWLINE;

    html::write_javascript_function( $script );

    $script  = ' function showIfRelSelected( theCheckbox, tdName ) {   '                            . NEWLINE;  
    $script .= '   changeDisplayStyleOnClick( theCheckbox, tdName, "entityrels relchecked", "entityrels relunchecked" );'
                                                                                                    . NEWLINE;  
    $script .= ' }   '                                                                              . NEWLINE;

    html::write_javascript_function( $script );
  }

  #----------------------------------------------------------------------------------

  function proj_publication_popups( $calling_field ) {

    if( ! $calling_field ) return;

    if( ! $this->popup_publication ) $this->popup_publication = new Popup_Publication( $this->db_connection );

    if( ! $this->popup_publication_abbrev )  # this version of the class returns abbreviation not full title
      $this->popup_publication_abbrev = new Popup_Publication_Abbrev( $this->db_connection );

    $selection_method = $this->popup_publication->proj_initial_popup_selection_method();

    $add_method = $this->popup_publication->proj_initial_popup_add_method();

    html::new_paragraph();

    #-------------------
    # 'Copy' button line
    #-------------------
    html::span_start( 'class="workfieldaligned"' );
    html::italic_start();
    echo 'You can copy references from an existing list into the above field: ';
    html::italic_end();
    html::span_end();

    # Decide whether to start the next little group of fields on a new line (if a popup window) or same line.
    $first_field_parms = 'class="narrowspaceonleft highlighted"';
    $calling_class = $this->app_get_class( $this );
    if( $this->string_starts_with( $calling_class, 'popup' )) {
      echo LINEBREAK;
      $first_field_parms = 'class="workfieldaligned highlighted"';
    }

    html::span_start( $first_field_parms );

    $this->popup_publication->app_popup_searchform_caller( $calling_form = $this->form_name, 
                                                           $calling_field, 
                                                           $method_to_call = $selection_method,
                                                           $select_desc = NULL, 
                                                           $view_desc = ' Full details: ' );


    html::span_end();

    #----

    html::span_start( 'class="narrowspaceonleft highlighted"' );

    $this->popup_publication_abbrev->app_popup_searchform_caller( $calling_form = $this->form_name, 
                                                                  $calling_field, 
                                                                  $method_to_call = $selection_method,
                                                                  $select_desc = NULL, 
                                                                  $view_desc = 'Abbreviation: ' );
    html::span_end();

    html::new_paragraph();

    #-------------------
    # 'Add' button line
    #-------------------
    if( $this->popup_publication->proj_enable_popup_add_method()) {

      html::span_start( 'class="workfieldaligned"' );
      html::italic_start();
      echo 'Or add a new reference to the list, then pick up full details or abbreviation: ';
      html::italic_end();
      html::span_end();

      if( $this->string_starts_with( $calling_class, 'popup' )) 
        echo LINEBREAK;

      html::span_start( $first_field_parms );

      $this->popup_publication->app_popup_addform_caller( $calling_form = $this->form_name, 
                                                          $calling_field, 
                                                          $method_to_call = $add_method,
                                                          $option_desc = 'Full details: ' );
      html::span_end();

      #----

      html::span_start( 'class="narrowspaceonleft highlighted"' );

      $this->popup_publication_abbrev->app_popup_addform_caller( $calling_form = $this->form_name, 
                                                          $calling_field, 
                                                          $method_to_call = $add_method,
                                                          $option_desc = 'Abbreviation: ' );

      html::span_end();
      html::new_paragraph();
    }
  }
  #----------------------------------------------------------------------------------

  function proj_extra_save_button( $prefix = NULL, $new_paragraph = TRUE, 
                                   $parms='onclick="js_enable_form_submission()" class ="workfield_save_button"',
                                   $save_and_end_parms = 'onclick="js_enable_form_submission()"' ) {

    if( $prefix ) { # is there already a section of the form containing an HTML anchor with this name?
      $anchor_exists = FALSE;

      $form_sections = $this->proj_list_form_sections();
      $anchor_names = array_keys( $form_sections );

      if( in_array( $prefix, $anchor_names ) ) {
        $anchor_exists = TRUE;
      }
      elseif( is_array( $this->extra_anchors ) ) {
        foreach( $this->extra_anchors as $anchor_name ) {
          if( $prefix == $anchor_name ) {
            $anchor_exists = TRUE;
            break;
          }
        }
      }

      if( ! $anchor_exists ) {
        html::anchor( $prefix . '_anchor' );  # create an anchor that we can return to after Save
      }
    }

    if( $prefix ) $prefix .= '_';

    if( $new_paragraph ) html::new_paragraph();

    #-------------------------
    # Save and Continue button
    #-------------------------
    html::submit_button( $prefix . 'save_button', 'Save', $tabindex = 1, $parms );
    html::span_start( 'class="highlight2"' );
    echo ' (and continue here) ';
    html::span_end();

    #--------------------
    # Save and End button
    #--------------------
    html::span_start( 'class="widespaceonleft"' );

    html::submit_button( $prefix . 'save_and_end_button', 'Save', $tabindex = 1, $save_and_end_parms );
    html::span_start( 'class="highlight2"' );
    echo ' (and return to top of page) ';
    html::span_end(); # end highlight 2
    html::span_end(); # end narrow space on left

    if( $new_paragraph ) html::new_paragraph();
  }
  #-----------------------------------------------------

  function proj_get_anchor_script_after_save( $suffix = '_save_button' ) {

    # If the user clicked a 'Save and continue' button, return them to the section of the form where they were working
    # rather than to the 'Refresh' button at the top of the form.

    $anchor_script = '';
    if( $suffix ) {
      $post_keys = array_keys( $_POST );

      foreach( $post_keys as $post_key ) {
        if( $this->string_ends_with( $post_key, $suffix ) ) {
          $anchor = substr( $post_key, 0, 0 - strlen( $suffix )) . '_anchor';
          $anchor_script = "window.location.hash = '$anchor';";
          break;
        }
      }
    }
    return $anchor_script;
  }
  #-----------------------------------------------------
  #----------------------------------------------------------------------------------
  #==========  Overridden methods from DB Entity or Application Entity ==============
  #----------------------------------------------------------------------------------

  function db_browse_reformat_data( $column_name, $column_value ) {

    $column_value = str_replace( '|', ' -- ', $column_value );

    switch( $column_name ) {
      case 'creation_timestamp':
      case 'change_timestamp':
        $column_value = substr( $column_value, 0, strlen( 'yyyy-mm-dd hh:mm' ));

        if( $column_name == 'change_timestamp' ) {
          $change_user = $this->current_row_of_data['change_user'];

          if( $change_user == PROJ_INITIAL_IMPORT ) {
            $column_value = str_replace( ' 00:00', '', $column_value );
            $change_user = strtolower( $change_user );
          }
          $column_value = $this->postgres_date_to_words( $column_value );
          $column_value .= ' by ' . $change_user;
        }
        break;
    }

    return $column_value;
  }
  #-----------------------------------------------------

  function db_list_columns( $table_or_view = NULL ) { 

    $columns = parent::db_list_columns( $table_or_view );

    $i = -1;
    foreach( $columns as $row ) {
      $i++;
      if( ! $row['search_help_text'] ) 
        $columns[$i]['search_help_text'] = NULL;

      if( $row[ 'column_name' ] == 'change_timestamp' ) {
        $columns[$i]['column_label'] = 'Last edit';
        $columns[$i]['search_help_text'] = 'Enter as dd/mm/yyyy hh:mm or dd/mm/yyyy' 
                                         . ' (please note: dd/mm/yyyy counts as the very start of a day).';
      }

      if( $row[ 'column_name' ] == 'change_user' ) {
        $columns[$i]['column_label'] = 'Last changed by';
        $columns[$i]['search_help_text'] = 'Username of the person who last changed the record.' ;
      }
    }

    return $columns;
  }
  #-----------------------------------------------------

  function db_choose_results_output_style() {  # overrides parent method: default to 'across page'

    $this->record_layout = $this->read_post_parm( 'record_layout' );
    if( ! $this->record_layout ) $this->write_post_parm( 'record_layout', 'across_page' );

    parent::db_choose_results_output_style();
  }
  #-----------------------------------------------------

  function safe_output( $the_value ) {  # overrides parent method from application entity
                                        # Allow unordered lists to be displayed as HTML

    # Reinstate foreign characters like e-acute which are stored as HTML entities
    # Then the parent 'safe output' method will turn them back!
    $the_value = $this->proj_reinstate_foreign_characters( $the_value );

    $the_value = parent::safe_output( $the_value );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<ul>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</ul>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<ol>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</ol>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<li>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</li>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<em>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</em>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<strong>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</strong>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<p>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</p>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<lb/>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<note>' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</note>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '<emph render="bold">' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '<emph render="italic">' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '<emph render="underline">' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '<emph render="super">' );
    $the_value = $this->re_enable_tag( $the_value, $tag = '</emph>' );

    $the_value = $this->re_enable_tag( $the_value, $tag = '&amp;' );

    return $the_value;
  }
  #-----------------------------------------------------

  function echo_safely_with_linebreaks( $the_value ) {  # Overrides parent method from application entity
                                                        # because breaking the value up into lots of different
                                                        # little lines makes the links fail.
    $this->echo_safely( $the_value, $newline_to_break = TRUE );
  }
  #-----------------------------------------------------

  function echo_safely( $the_value, $newline_to_break = FALSE ) {  # overrides parent method from application entity

    $the_value = $this->safe_output( $the_value );  # re-enable unordered list tags 

    if( $this->string_contains_substring( $the_value, IMAGE_ID_START_MARKER )) {
      $parts = explode( IMAGE_ID_START_MARKER, $the_value );
      $the_value = '';
      foreach( $parts as $part ) {
        if( $this->string_contains_substring( $part, IMAGE_ID_END_MARKER )) {
          $end_pos = strpos( $part, IMAGE_ID_END_MARKER );
          $image_filename = substr( $part, 0, $end_pos );
          $the_value .= $this->proj_make_image_link( $image_filename );
          $part = substr( $part, $end_pos + strlen( IMAGE_ID_END_MARKER ));
        }
        $the_value .= $part;
      }
    }

    if( $this->string_contains_substring( $the_value, LINK_TEXT_START_MARKER . HREF_START_MARKER )) {

      $the_value = str_replace( LINK_TEXT_START_MARKER . HREF_START_MARKER, 
                                '<a title="Related item (opens in new tab)" target="_blank" href="', $the_value );

      $the_value = str_replace( HREF_END_MARKER, '">', $the_value );

      $the_value = str_replace( LINK_TEXT_END_MARKER, '</a>', $the_value );
    }

    if( $newline_to_break ) {
      $the_value = str_replace( NEWLINE, LINEBREAK, $the_value );
    }

    echo( $the_value );
  }
  #-----------------------------------------------------

  function proj_extract_image_filenames( $raw_string ) { 

    $filenames = array();

    if( $this->string_contains_substring( $raw_string, IMAGE_ID_START_MARKER )) {
      $parts = explode( IMAGE_ID_START_MARKER, $raw_string );
      foreach( $parts as $part ) {
        if( $this->string_contains_substring( $part, IMAGE_ID_END_MARKER )) {
          $end_pos = strpos( $part, IMAGE_ID_END_MARKER );
          $image_filename = substr( $part, 0, $end_pos );
          $filenames[] = $image_filename;
        }
      }
    }
    return $filenames;
  }
  #-----------------------------------------------------

  function proj_get_thumbnail_file( $image_filename ) {

    # We could possibly have hundreds of images on one single page, and if we have to select the
    # thumbnail file from the database in every case, then it might well lead to performance issues.
    # So get a default value to begin with, and only select from database if this does not seem to work.

    $thumbfile = $this->proj_default_thumbnail_file( $image_filename );

    if( ! $thumbfile || $this->proj_link_is_broken( $thumbfile )) {
      $statement = 'select thumbnail from ' . $this->proj_image_tablename()
                 . " where image_filename = '" . $this->escape( $image_filename ) . "'";
      $thumbfile = $this->db_select_one_value( $statement );
    }
    return $thumbfile;
  }
  #-----------------------------------------------------

  function proj_default_thumbnail_file( $image_filename, $entity_type = NULL ) {

    # Only works have 'original catalogues' so this image must be of a manifestation
    if( $this->original_catalogue && ! $entity_type ) $entity_type = 'manifestation';

    if( ! $entity_type ) return $image_filename;

    if( $entity_type == 'manifestation' )
      $core_catalogue = $this->proj_get_core_catalogue();
    else
      $core_catalogue = '';

    if( $core_catalogue == 'cardindex' ) # for Bodleian card catalogue, we only have a generic thumbnail to represent
                                         # any card, so use the full-sized (but small) image instead of thumbnail
      return $image_filename;

    elseif( $core_catalogue == 'union' && $this->string_contains_substring( $image_filename, '/images/selden_end/' ))
      return $image_filename; # card catalogue viewed via union

    $thumbfile = $image_filename;

    if( $core_catalogue == 'comenius' && $this->string_contains_substring( $image_filename, 'Comenius_WM' ))
      return str_replace( 'Comenius_WM', 'Comenius_Thumb', $thumbfile );

    $final_slash = strrpos( $image_filename, '/' );
    if( $final_slash ) {
      $path = substr( $image_filename, 0, $final_slash + 1 );
      $filename = substr( $image_filename, $final_slash + 1 );
      $thumbfile = $path . 'thumb_' . $filename;
    }

    return $thumbfile;
  }
  #-----------------------------------------------------

  function proj_default_thumbnail_width_px() {  # could override in child class if necessary

    $core_catalogue = $this->proj_get_core_catalogue();

    if( $core_catalogue == 'lister' ) return LISTER_IMAGE_THUMBNAIL_WIDTH_PX;

    return IMAGE_THUMBNAIL_WIDTH_PX;
  }
  #-----------------------------------------------------

  function proj_make_image_link( $image_filename = NULL, $width_px = NULL ) {

    if( ! $image_filename ) return NULL;

    if( $this->printable_output || $this->csv_output ) return $image_filename;

    $href = $image_filename;  # now contains path within the filename
    if( $this->proj_link_is_broken( $href )) {
      $broken = str_replace( '/', ' ', $image_filename );
      $broken = str_replace( '.', ' ', $broken );
      $broken = '[' . $broken . ' not found] ';
      return $broken;
    }

    $link_string = '<a target="_blank" title="View image in new tab" ';
    $link_string .= 'href="' ;
    $link_string .= $href;
    $link_string .= '" >';

    $extension = $this->proj_get_file_extension( $image_filename );
    $displayable = $this->proj_is_displayable_image_file( $extension );

    if( $displayable ) {
      $link_image = $image_filename;

      if( ! $width_px ) { # just display a thumbnail
        $link_image = $this->proj_get_thumbnail_file( $image_filename );
        $width_px = $this->proj_default_thumbnail_width_px();
      }

      if( ! $link_image || ! $width_px ) 
        $link_string .= $image_filename; # Just display the filename as a link

      else {
        $link_string .= '<img src="' . $link_image . '" ';
        $link_string .= ' style="width: ' . $width_px . 'px; margin-bottom: 2px; margin-right: 2px;" ';
        if( $this->work_desc )
          $link_string .= ' alt="Image of: ' . $this->escape( $this->work_desc ) . '" ';
        $link_string .= ' />';
      }
    }
    else {
      $link_string .= strtoupper( $extension );
    }

    $link_string .= '</a>';
    return $link_string;
  }
  #-----------------------------------------------------

  function proj_get_file_extension( $the_filename ) {

    $final_full_stop = strrpos( $the_filename, '.' );
    if( $final_full_stop )
      $extension = substr( $the_filename, $final_full_stop+1 );
    else
      $extension = substr( $the_filename, -3 );
    return $extension;
  }
  #-----------------------------------------------------

  function proj_is_displayable_image_file( $extension ) {

    $extension = strtolower( $extension );

    switch( $extension ) {
      case 'png':
      case 'gif':
      case 'jpg':
        $displayable = TRUE;
        break;

      default:
        $displayable = FALSE;
    }

    return $displayable;
  }
  #-----------------------------------------------------

  function proj_convert_to_alphanumeric( $orig_string, $allow_underscores = TRUE ) {

    $new_string = '';

    for( $i = 0; $i < strlen( $orig_string ); $i++ ) {
      $one_char = substr( $orig_string, $i, 1 );
      if( $this->is_alphanumeric( $one_char )) $new_string .= $one_char;
    }
    return $new_string;
  }
  #-----------------------------------------------------

  function db_pre_filter_csv_output( &$rows, $convert_dates = FALSE ) {  # Note that rows is passed by reference
                                                                        # to save memory.

    parent::db_pre_filter_csv_output( $rows, $convert_dates );

    $column_separator = '!^%CofKColSeparatorUniqStr%^!';  # something we are not likely to find in the real data!

    $rowcount = count($rows);
    for( $i = 0; $i < $rowcount; $i++ ) {

      # Turn the whole row into one string
      $data_value = implode( $column_separator, $rows[ $i ] );
      $replacements = FALSE;

      # Convert numeric HTML entities into characters
      if( $this->string_contains_substring( $data_value, '&#' )) {
        $replacements = TRUE;
        $data_value = $this->proj_reinstate_foreign_characters( $data_value );
      }


      # Remove markers for text links
      if( $this->string_contains_substring( $data_value, LINK_TEXT_START_MARKER . HREF_START_MARKER )) {
        $replacements = TRUE;

        $data_value = str_replace( LINK_TEXT_START_MARKER . HREF_START_MARKER, '', $data_value );
        $data_value = str_replace( HREF_END_MARKER, ' (', $data_value );
        $data_value = str_replace( LINK_TEXT_END_MARKER, ')', $data_value );
      }

      # Remove markers for image links
      if( $this->string_contains_substring( $data_value, IMAGE_ID_START_MARKER )) {
        $replacements = TRUE;

        $data_value = str_replace( IMAGE_ID_START_MARKER, '', $data_value );
        $data_value = str_replace( IMAGE_ID_END_MARKER, ' ', $data_value );
      }

      # Remove markers for list start/ends
      if( $this->string_contains_substring( $data_value, $this->html_list_start_marker )
      ||  $this->string_contains_substring( $data_value, $this->html_list_item_marker )
      ||  $this->string_contains_substring( $data_value, $this->html_list_end_marker )
      ||  $this->string_contains_substring( $data_value, $this->html_olist_start_marker )
      ||  $this->string_contains_substring( $data_value, $this->html_olist_end_marker )) {
        $replacements = TRUE;

        $data_value = str_replace( $this->html_list_start_marker, '', $data_value );
        $data_value = str_replace( $this->html_list_end_marker, NEWLINE, $data_value );

        $data_value = str_replace( $this->html_list_item_marker, $this->list_item_marker . NEWLINE, $data_value );

        $data_value = str_replace( $this->html_olist_start_marker, '', $data_value );
        $data_value = str_replace( $this->html_olist_end_marker, NEWLINE, $data_value );
      }

      # Remove text formatting tags such as '<strong>'
      if( $this->string_contains_substring( $data_value, '<strong>' )
      ||  $this->string_contains_substring( $data_value, '</strong>' )) {
        $replacements = TRUE;

        $data_value = str_replace( '<strong>',  '', $data_value );
        $data_value = str_replace( '</strong>', '', $data_value );
      }


      # If replacements have been made, break the string up into a row array again.
      if( $replacements ) {
        $cleaned = explode( $column_separator, $data_value );

        if( ! $column_name_list ) {
          $column_name_list = array();
          foreach( $rows[ $i ] as $colname => $colvalue ) {
            $column_name_list[] = $colname;
          }
        }

        foreach( $column_name_list as $colno => $colname ) {
          $rows[ $i ][ "$colname" ] = $cleaned[ $colno ];
        }
      }
    }

    return;
  }
  #-----------------------------------------------------

  function proj_convert_non_html_list( $the_string, $separator = NULL, $numeric = FALSE ) {  

    if( ! $separator ) $separator = $this->list_item_marker;

    if( $this->string_contains_substring( $the_string, $separator )) {

      if( $numeric )
        $the_string = $this->html_olist_start_marker . $the_string;
      else
        $the_string = $this->html_list_start_marker . $the_string;

      $the_string = str_replace( $separator, $this->html_list_item_marker, $the_string );

      if( $numeric )
        $the_string .= $this->html_olist_end_marker;
      else
        $the_string .= $this->html_list_end_marker;
    }

    return $the_string;
  }
  #-----------------------------------------------------

  function proj_format_names_for_output( $ids_and_names, $html_output = FALSE, $brief_dates = TRUE ) {  

    # Although the array is called 'IDs and names', it also now (Feb 2012) includes relationship dates.
    if( ! is_array( $ids_and_names )) return NULL;
    $id_and_name_list = '';

    $add_list_tags = FALSE;
    if( count( $ids_and_names ) > 1 && $html_output ) $add_list_tags = TRUE;

    if( $add_list_tags ) $id_and_name_list = '<ul>';

    foreach( $ids_and_names as $id_and_name ) {
      extract( $id_and_name, EXTR_OVERWRITE );

      if( $add_list_tags ) 
        $id_and_name_list .= '<li>';
      elseif( $id_and_name_list > '' ) 
        $id_and_name_list .= ', ';

      $year_from = '';
      $year_till = '';
      $date_string = '';

      if( $relationship_valid_from ) {
        $relationship_valid_from = substr( $relationship_valid_from, 0, strlen( 'yyyy-mm-dd' )); # remove hours etc
        $year_from = substr( $relationship_valid_from, 0, strlen( 'yyyy' ));
        if( substr( $year_from, 0, 1 ) == '0' ) $year_from = substr( $year_from, 1 ); # remove leading zeroes
        if( $brief_dates ) $relationship_valid_from = $year_from;
      }
      if( $relationship_valid_till ) {
        $relationship_valid_till = substr( $relationship_valid_till, 0, strlen( 'yyyy-mm-dd' )); # remove hours etc
        $year_till = substr( $relationship_valid_till, 0, strlen( 'yyyy' ));
        if( substr( $year_till, 0, 1 ) == '0' ) $year_till = substr( $year_till, 1 ); # remove leading zeroes
        if( $brief_dates ) $relationship_valid_till = $year_till;
      }

      if( $relationship_valid_from && $relationship_valid_till ) {
        if( $brief_dates && ( $year_from == $year_till ))
          $date_string = $relationship_valid_from . ': ';
        else
          $date_string = $relationship_valid_from . ' to ' . $relationship_valid_till . ': ';
      }
      elseif( $relationship_valid_from ) {
        $date_string = 'From ' . $relationship_valid_from . ': ';
      }
      elseif( $relationship_valid_till ) {
        $date_string = 'Until ' . $relationship_valid_till . ': ';
      }

      $id_and_name_list .= $date_string;
      $id_and_name_list .= $name;

      if( $add_list_tags )
        $id_and_name_list .= '</li>';
    }

    if( $add_list_tags ) $id_and_name_list .= '</ul>';

    return $id_and_name_list;
  }
  #-----------------------------------------------------

  function proj_image_msg_button( $image_file, $msg, $button_name = 'proj_image_msg_button' ) {

    $msg = str_replace( NEWLINE, ' ', $msg );
    $msg = str_replace( CARRIAGE_RETURN, ' ', $msg );
    $msg = str_replace( '"', "'", $msg );

    echo '<button name="' . $button_name . '" id="' . $button_name . '" ';
    echo ' title="' . $msg . '" ';
    echo ' style="border-style: none; padding: 0px; margin: 1px" ';
    echo ' onclick="alert(' . "'" . $this->escape( $msg ) . "')" . '" ';
    echo ' > ';

    echo '<img src="' . $image_file . '"/>';

    echo '</button>';

  }
  #-----------------------------------------------------

  function proj_reduce_no_of_blank_lines( $the_string ) {

    $the_string = str_replace( '<p>', '', $the_string );
    $the_string = str_replace( '</p>', '', $the_string );
    $the_string = str_replace( LINEBREAK, NEWLINE, $the_string );
    $the_string = str_replace( CARRIAGE_RETURN, NEWLINE, $the_string );
    $the_string = str_replace( '<lb/>', NEWLINE, $the_string );
    while( $this->string_contains_substring( $the_string, NEWLINE . NEWLINE )) {
      $the_string = str_replace( NEWLINE . NEWLINE, NEWLINE, $the_string ); # convert double newlines to single
    }
    if( $this->string_starts_with( $the_string, NEWLINE )) $column_value = substr( $column_value, 1 );
    return $the_string;
  }
  #-----------------------------------------------------

  function proj_link_is_broken( $url ) {

    #JPNP - for test of time url testing takes
    # left in place until decided what to do
     return FALSE;
    #JPNP

    $handle = @fopen( $url, 'r' );  # the @ operator suppresses error message for this single command
    $broken = TRUE;

    if ( $handle ) {
      $broken = FALSE;
      fclose( $handle );
    }
    return $broken;
  }
  #-----------------------------------------------------

  function proj_reinstate_foreign_characters( $the_value ) {
    return $this->app_reinstate_foreign_characters( $the_value ); # we now have a version in application entity
  }
  #-----------------------------------------------------
  function proj_textarea( $fieldname, $rows = 3, $cols = 50, $value = NULL, $label = NULL, 
                          $textarea_parms = NULL, $label_parms = NULL, $input_instance = NULL ) {

    $value = $this->proj_reinstate_foreign_characters( $value );

    $value = str_replace( '<p>', '', $value );
    $value = str_replace( '</p>', NEWLINE, $value );
    $value = str_replace( '<lb/>', NEWLINE, $value );

    html::textarea( $fieldname, $rows, $cols, $value, $label, $textarea_parms, $label_parms, $input_instance );
  }
  #-----------------------------------------------------
  function proj_write_post_save_refresh_button( $just_saved = NULL, $opening_method = NULL ) {

    $focus_script = '';  # focus on 'Refresh' button if applicable

    if( $just_saved && $this->is_search_results_method( $opening_method )) {
      html::new_paragraph();
      echo "Click the 'Refresh' button to close the Edit tab and show the new details in the Search Results page: ";
      html::button( 'refresh_caller_button', 'Refresh', $tabindex=1, 
                    'onclick="window.opener.location.reload(); self.close()"' );
      html::new_paragraph();
      html::italic_start();
      echo '(Note: you will be prompted to confirm that you want to reload the Search Results page. ';
      echo " It is fine to answer 'Yes' to this prompt.)";
      html::italic_end();

      $focus_script = "document.$this->form_name.refresh_caller_button.focus()";
    }

    return $focus_script;
  }
  #-----------------------------------------------------

  function proj_get_rel_ids( $required_relationship_type, $required_table, $this_side, $html_output = TRUE ) {

    $filtered = array();

    if( $this_side == 'right' ) {
      $unfiltered = $this->this_on_right;
      $other_table = 'left_table_name';
      $other_id_value = 'left_id_value';
    }
    else {
      $unfiltered = $this->this_on_left;
      $other_table = 'right_table_name';
      $other_id_value = 'right_id_value';
    }

    foreach( $unfiltered as $row ) {
      extract( $row, EXTR_OVERWRITE );

      if( $relationship_type == $required_relationship_type &&  $$other_table == $required_table ) {

        $filtered[] = array( 'other_table' => $$other_table, 'other_id_value' => $$other_id_value,
                             'relationship_valid_from' => $relationship_valid_from,
                             'relationship_valid_till' => $relationship_valid_till );
      }
    }

    return $filtered;
  }
  #----------------------------------------------------------------------------------

  function proj_get_decoded_rels( $required_relationship_type, $required_table, $this_side, $html_output = TRUE ) {

    $decoded = array();
    if( ! $this->rel_obj ) $this->rel_obj = new Relationship( $this->db_connection );

    $tables_and_ids = $this->proj_get_rel_ids( $required_relationship_type, 
                                               $required_table, 
                                               $this_side, 
                                               $html_output ); 
    foreach( $tables_and_ids as $row ) {
      extract( $row, EXTR_OVERWRITE );

      $desc = $this->rel_obj->get_decode_for_one_id( $other_table, $other_id_value );
      if( ! $desc ) $desc = '[No details found]';

      $decoded[] = array( 'id' => $other_id_value, 'name' => $desc,
                          'relationship_valid_from' => $relationship_valid_from,
                          'relationship_valid_till' => $relationship_valid_till );
    }

    return $this->proj_format_names_for_output( $decoded, $html_output );  
  }
  #----------------------------------------------------------------------------------
  # Produce a set of navigation links to help you jump through the sections of a long form

  function proj_list_form_sections() { # this needs to be overridden in child class

    $form_sections = array();  # need entries of form 'anchor name' => 'displayed description'
    return $form_sections;
  }
  #-----------------------------------------------------

  function proj_form_section_links( $curr_section = NULL, $heading_level = 3, $start_with_linebreak = FALSE ) {

    $form_sections = $this->proj_list_form_sections();
    if( count( $form_sections ) == 0 ) return;

    if( $start_with_linebreak ) echo LINEBREAK;

    if( $curr_section ) $this->proj_form_section_anchor( $curr_section );

    html::div_start( 'class="form_section_links_div"' );

    $first = TRUE;
    foreach( $form_sections as $section => $display ) {
      if( ! $first ) echo ' | ';
      html::link( '#' . $section . '_anchor', $display );
      $first = FALSE;
    }
    html::new_paragraph();

    if( $heading_level ) {
      $start_func = 'bold_start';
      $end_func = 'bold_end';
      if( $this->is_integer( $heading_level )) {
        if( $heading_level >= 3 && $heading_level <= 4 ) {
          $start_func = 'h' . $heading_level . '_start';
          $end_func = 'h' . $heading_level . '_end';
        }
      }
      $desc = $form_sections[ "$curr_section" ];
      if( $desc ) {
        html::$start_func();
        echo $desc . ':';
        html::$end_func();
      }
    }

    html::div_end();
  }
  #-----------------------------------------------------

  function proj_form_section_anchor( $curr_section ) {

    $form_sections = $this->proj_list_form_sections();

    if( $curr_section ) {
      if( key_exists( $curr_section, $form_sections ))
        html::anchor( $curr_section . '_anchor' );
      else
        echo 'Did not find anchor ' . $curr_section;
    }
  }
  #-----------------------------------------------------

  function proj_single_place_entry_field( $fieldset_name, $core_desc = NULL, $decode_field_label = NULL ) {

    if( ! $fieldset_name ) die( 'Invalid input to function.' );

    if( ! $this->location_obj ) $this->location_obj = new Popup_Location( $this->db_connection );

    $calling_field = $this->proj_new_id_fieldname_from_fieldset_name( $fieldset_name );
    $decode_fieldname = $this->proj_decode_fieldname_from_id_fieldname( $calling_field );

    if( ! $core_desc ) $core_desc = str_replace( '_', ' ', $fieldset_name );
    if( ! $decode_field_label ) $decode_field_label = ucfirst( $core_desc );

    $decode_initial_value = 'Select or create ' . $core_desc;
    $calling_field_value = NULL;

    $place = $this->proj_get_entries_for_fieldgroup( $fieldset_name );

    if( count( $place ) > 0 ) {
      foreach( $place as $id => $rels ) {
        $calling_field_value = $id;
        $decode_field_initial_value = $this->location_obj->proj_get_description_from_id( $id );
        break;  # we're only expecting one place via this function, e.g. birthplace, place of death
      }
    }

    $this->location_obj->proj_input_fields_calling_popups( 
                           $calling_form = $this->form_name, $calling_field,
                           $decode_fieldname, $decode_field_label, $decode_field_initial_value,
                           NULL, NULL, $calling_field_value ); 

    html::div_start( 'class="workfield"' );
    $parms = 'onclick="document.' . $this->form_name . '.' . $calling_field    . ".value='';"
                    . 'document.' . $this->form_name . '.' . $decode_fieldname . ".value='';" . '"';
    html::button( 'clear_' . $fieldset_name . '_button', 'X', $tabindex=1, $parms );
    echo ' (Click to blank out ' . $core_desc . ' on screen, then Save to finalise.)';
    html::div_end();
  }
  #-----------------------------------------------------

  function proj_get_members_of_research_group( $original_catalogue ) {

    $members_of_research_group = array();

    switch( $original_catalogue ) {

      case 'cardindex': # we'll change to using 'cardindex' when we move Selden End into Union
        $members_of_research_group[] = 'cofkkim';
        $members_of_research_group[] = 'cofkmiranda';
        break;

      case 'lister':
        $members_of_research_group[] = 'cofkannamarie';
        break;

      case 'comenius':
        $members_of_research_group[] = 'cofkkaterina';
        $members_of_research_group[] = 'cofkvladimir';
        $members_of_research_group[] = 'cofkiva';
        break;

      case 'hartlib':
        $members_of_research_group[] = 'cofkleigh';
        $members_of_research_group[] = 'cofkhoward';
        break;

      case 'lhwyd':
        $members_of_research_group[] = 'cofkhelen';
        $members_of_research_group[] = 'cofkrichard';
        break;

      case 'aubrey':
        $members_of_research_group[] = 'cofkkelsey';
        break;

      case 'wallis':
        $members_of_research_group[] = 'cofkkim';
        $members_of_research_group[] = 'cofkmiranda';
        $members_of_research_group[] = 'cofkphilip';
    }

    return $members_of_research_group;
  }
  #----------------------------------------------------------------------------------

  function proj_is_member_of_research_group( $original_catalogue ) {

    $username = $this->db_get_username();
    $members_of_research_group = $this->proj_get_members_of_research_group( $original_catalogue );

    if( in_array( $username, $members_of_research_group ))
      return TRUE;
    else
      return FALSE;
  }
  #----------------------------------------------------------------------------------

  function proj_is_member_of_same_research_group( $other_person ) {

    $catg_obj = new Catalogue( $this->db_connection );
    $catg_list = $catg_obj->get_lookup_list();

    $username = $this->db_get_username();

    foreach( $catg_list as $catg_row ) {
      extract( $catg_row, EXTR_OVERWRITE );

      if( $this->proj_is_member_of_research_group( $catalogue_code )) {  # current user is in this group
        $all_members = $this->proj_get_members_of_research_group( $catalogue_code );
        foreach( $all_members as $member ) {
          if( $member == $other_person ) {  # current user is in same group as other person
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }
  #----------------------------------------------------------------------------------

  function proj_get_possible_languages() {

    $statement = 'select language_code from ' . $this->proj_favourite_language_tablename()
               . ' union '
               . 'select language_code from ' . $this->proj_language_of_work_tablename()
               . ' union '
               . 'select language_code from ' . $this->proj_language_of_manifestation_tablename();
    $poss = $this->db_select_into_array( $statement );
    if( count( $poss ) < 1 ) {
      echo 'No languages have been selected for use with this project.' . LINEBREAK;
      return;
    }
    $langs = array();
    if( count( $poss ) > 0 ) {
      foreach( $poss as $row ) {
        extract( $row, EXTR_OVERWRITE );
        $langs[] = $language_code;
      }
    }
    return $langs;
  }
  #----------------------------------------------------------------------------------

  function proj_get_languages_used( $object_type, $id_value ) {

    switch( $object_type ) {
      case 'work':
      case 'manifestation':
        $keycol = $object_type . '_id';
        break;
      default:
        die( 'Invalid object type while retrieving languages.' . LINEBREAK );
    }

    $tablename_function = 'proj_language_of_' . $object_type . '_tablename';
    if( ! method_exists( $this, $tablename_function )) 
      die( 'Invalid function name while retrieving languages.' . LINEBREAK );
    $table_name = $this->$tablename_function();

    $statement = "select language_code, notes from $table_name where $keycol = '$id_value'";
    $used = $this->db_select_into_array( $statement );
    if( is_null( $used )) $used = array();
    return $used;
  }
  #----------------------------------------------------------------------------------

  function proj_save_languages( $object_type, $id_value ) {

    switch( $object_type ) {
      case 'work':
      case 'manifestation':
        $keycol = $object_type . '_id';
        break;
      default:
        die( 'Invalid object type while saving languages.' . LINEBREAK );
    }

    $tablename_function = 'proj_language_of_' . $object_type . '_tablename';
    if( ! method_exists( $this, $tablename_function )) 
      die( 'Invalid function name while saving languages.' . LINEBREAK );
    $table_name = $this->$tablename_function();

    $language_obj = new Language( $this->db_connection );
    $language_obj->save_languages_of_text( $table_name, $keycol, $id_value );
  }
  #----------------------------------------------------------------------------------

  function proj_get_field_label( $fieldname = NULL ) {
    return $this->db_get_default_column_label( $fieldname );
  }
  #----------------------------------------------------------------------------------

  function proj_multiple_compact_checkboxes( $all_possible_ids_and_descs,
                                             $selected_ids,
                                             $checkbox_fieldname,
                                             $list_label ) {

    $use_table = FALSE;
    $max_for_single_list = 30;
    $num_possible_ids_and_descs = count( $all_possible_ids_and_descs );

    if( $num_possible_ids_and_descs == 0 ) {
      echo LINEBREAK;
      echo $list_label . ' (no options found)';
      echo LINEBREAK;
      return;
    }

    if( $num_possible_ids_and_descs > $max_for_single_list ) {
      $use_table = TRUE;
      $num_columns = 4;
    }

    $list_class = $checkbox_fieldname . '_checkboxlist';
    $table_class = $checkbox_fieldname . '_checkboxtable';
    $selected_class = $checkbox_fieldname . '_selected';
    $script_name = 'changeDisplayOf' . $checkbox_fieldname;

    echo '<style type="text/css">'                                      . NEWLINE;
    echo '  ul.' . $list_class . ' {'                                   . NEWLINE;
    echo '    list-style-type: none; '                                  . NEWLINE;
    if( ! $use_table ) 
      echo '    margin-left: ' . WORK_FIELD_POS . 'px; '                . NEWLINE;
    echo '    padding-left: 0px;'                                       . NEWLINE;
    echo '  }'                                                          . NEWLINE;

    echo '  li.' . $selected_class . ' {'                               . NEWLINE;
    echo '    font-weight: bold;'                                       . NEWLINE;
    echo '  }'                                                          . NEWLINE;

    echo '  table.' . $table_class . ' {'                               . NEWLINE;
    echo '    margin-left: ' . WORK_FIELD_POS . 'px; '                  . NEWLINE;
    echo '    margin-top: 0px; '                                        . NEWLINE;
    echo '  }'                                                          . NEWLINE;

    echo '</style>'                                                     . NEWLINE;

    $script = '  function ' . $script_name . '( theCheckbox, listItemId ) { ' . NEWLINE
            . '    var theListItem = document.getElementById( listItemId ); ' . NEWLINE
            . '    if( theCheckbox.checked == true ) { '                      . NEWLINE
            . '      theListItem.className = "' . $selected_class . '"; '     . NEWLINE
            . '    } '                                                        . NEWLINE
            . '    else { '                                                   . NEWLINE
            . '      theListItem.className = ""; '                            . NEWLINE
            . '    } '                                                        . NEWLINE
            . '  } '                                                          . NEWLINE;
    html::write_javascript_function( $script );

    html::new_paragraph();
    html::span_start('class="workfield"');
    html::label( $list_label, $label_id = $checkbox_fieldname . 'list_label' );
    html::span_end();

    if( $use_table ) {
      html::table_start( 'class="' . $table_class . '"' );
      html::tablerow_start();
      html::tabledata_start();
      $curr_column = 1;
      $items_in_column = 0;
    }
    html::ulist_start( 'class="' . $list_class . '"' );

    foreach( $all_possible_ids_and_descs as $possible_id => $possible_desc ) {

      $li_id = $checkbox_fieldname . $possible_id . '_listitem';
      $li_parms = 'id="' . $li_id . '"';

      $chk_parms = 'onclick="' . $script_name . '( this, ' . "'$li_id'" . ' )"';

      $is_checked = FALSE;
      if( in_array( $possible_id, $selected_ids )) $is_checked = TRUE;
      if( $is_checked ) $li_parms .= ' class="' . $selected_class . '"';

      html::listitem_start( $li_parms );

      $this->proj_echo_safely_checkbox( 
                      $fieldname = $checkbox_fieldname . $possible_id, 
                      $label = $possible_desc, 
                      $is_checked, 
                      $value_when_checked = $possible_id,
                      $tabindex = 1, $input_instance = NULL,
                      $chk_parms ); 
      html::listitem_end();

      if( $use_table ) {
        $items_in_column++;
        if( $items_in_column >= $num_possible_ids_and_descs / $num_columns ) {
          if( $curr_column < $num_columns ) {  # don't start a new column after you have reached the last column
            html::ulist_end();
            html::tabledata_end();

            $items_in_column = 0;
            $curr_column++;

            html::tabledata_start();
            html::ulist_start( 'class="' . $list_class . '"' );
          }
        }
      }
    }

    if( $use_table ) {
      html::table_end();
      html::tablerow_end();
      html::tabledata_end();
    }
    html::ulist_end();

  }
  #-----------------------------------------------------
  # This version of the 'checkbox' function will do an 'echo safely' rather than a 'call htmlentities'

  function proj_echo_safely_checkbox( $fieldname, $label, $is_checked = NULL, $value_when_checked = 1, 
                                      $tabindex=1, $input_instance = NULL, $parms = NULL, $label_on_left = FALSE ) {

    $field_id = html::field_id_from_fieldname( $fieldname, $input_instance );

    echo NEWLINE;

    if( $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      $this->echo_safely( $label );
      echo '</label>';
    }

    echo '<input type="checkbox" name="' . $fieldname . '" id="' . $field_id . '" value="';
    $this->echo_safely( $value_when_checked );
    echo '"';
    if( $is_checked ) echo ' CHECKED ';
    if( $tabindex > 0 ) echo ' tabindex="' . $tabindex . '" ';
    if( $parms ) echo ' ' . $parms . ' ';
    echo ' />';

    if( ! $label_on_left ) {
      echo '<label for="' . $field_id . '">';
      $label = trim( $label );
      $this->echo_safely( $label );
      echo '</label>';
    }
    echo NEWLINE;
  }
  #-----------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'change_user':         # allow spaces as well as letters and numbers, 
      case 'creation_user':       # as freshly-imported records have 'Initial import' in username

        return $this->is_alphanumeric_or_blank( str_replace( ' ', '', $this->parm_value ), 
                                                $allow_underscores = TRUE );
        
      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
