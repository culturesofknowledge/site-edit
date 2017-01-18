<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================


class Relationship extends Project {

  #----------------------------------------------------------------------------------

  function Relationship( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    #-----------------------------------------------------
    $this->Project( $db_connection );
    $this->set_relationship_fields();
  }
  #----------------------------------------------------------------------------------

  function set_relationship( $relationship_id = NULL ) {

    $this->clear();
    if( ! $relationship_id ) return FALSE;

    $statement = 'select * from ' . $this->proj_relationship_tablename()
               . " where relationship_id = '$relationship_id'";
    $this->db_select_into_properties( $statement );

    return $this->relationship_id;
  }
  #----------------------------------------------------------------------------------

  function clear() {
    parent::clear();
    $this->set_relationship_fields();
  }
  #----------------------------------------------------------------------------------

  function set_relationship_fields() {

    $settings = array();
    $system_prefix = $this->get_system_prefix();  # some variations between settings for different projects

    #------
    # Works
    #------
    $settings[ FIELDSET_AUTHOR_SENDER ] = array(  # original Cultures of Knowledge version of authors/senders of letters
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_CREATOR_OF_WORK   => 'Creator',
                                  RELTYPE_PERSON_SENDER_OF_WORK    => 'Sender',
                                  RELTYPE_PERSON_SIGNATORY_OF_WORK => 'Signatory' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

    # Author-type roles for IMPAcT, avoiding display of the word 'Creator' as this could be offensive.
    $settings[ FIELDSET_AUTHOR ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_AUTHOR_OF_WORK      => 'Author',
                                  RELTYPE_PERSON_COPYIST_OF_WORK     => 'Copyist',
                                  RELTYPE_PERSON_COMMENTATOR_ON_WORK => 'Commentator',
                                  RELTYPE_PERSON_TRANSLATOR_OF_WORK  => 'Translator',
                                  RELTYPE_PERSON_GLOSSIST_OF_WORK    => 'Glossist' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ FIELDSET_ADDRESSEE ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_WORK_ADDRESSED_TO_PERSON => 'Recipient',
                                  RELTYPE_WORK_INTENDED_FOR_PERSON => 'Intended recipient' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_PEOPLE_MENTIONED ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_WORK_MENTIONS_PERSON => 'Mentioned' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_PLACES_MENTIONED ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_WORK_MENTIONS_PLACE => 'Mentioned' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_WORKS_MENTIONED ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_LATER_WORK_MENTIONS_EARLIER_WORK => 'Mentioned' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_EARLIER_WORK_ANSWERED_BY_THIS ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_LATER_WORK_REPLIES_TO_EARLIER_WORK => 'Reply to' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_LATER_WORK_ANSWERING_THIS ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_LATER_WORK_REPLIES_TO_EARLIER_WORK => 'Reply to' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

      $settings[ FIELDSET_MATCHING_WORK ] = array(
          'left_table_name' => $this->proj_work_tablename(),
          'reltypes'        => array( RELTYPE_MATCHING_WORK => 'Matches' ),
          'right_table_name'=> $this->proj_work_tablename(),
          'side_to_get'     => 'both'
      );

    $settings[ FIELDSET_ORIGIN ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_WORK_SENT_FROM_PLACE => 'Origin' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    if( $system_prefix == IMPACT_SYS_PREFIX ) # On IMPAcT we're using the 'origin' field for 'Place of composition'
      $settings[ FIELDSET_ORIGIN ][ 'reltypes' ][ RELTYPE_WORK_SENT_FROM_PLACE ] = 'Place of composition';

    $settings[ FIELDSET_DESTINATION ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_WORK_SENT_TO_PLACE => 'Destination' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_WORK_DISCUSSED ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_LATER_WORK_COMMENTS_ON_EARLIER_WORK => 'Commentary',
                                  RELTYPE_LATER_WORK_CONTINUES_EARLIER_WORK   => 'Continuation',
                                  RELTYPE_LATER_WORK_IS_GLOSS_ON_EARLIER_WORK => 'Gloss',
                                  RELTYPE_LATER_WORK_IS_IJAZA_FOR_EARLIER_WORK=> 'Ijaza',
                                  RELTYPE_LATER_WORK_SUMMARISES_EARLIER_WORK  => 'Summary',
                                  RELTYPE_LATER_WORK_TRANSLATES_EARLIER_WORK  => 'Translation',
                                  RELTYPE_LATER_WORK_IS_VERSIFICATION_OF_EARLIER_WORK => 'Versification' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ FIELDSET_WORK_DISCUSSING ] = array( 
      'left_table_name' => $this->proj_work_tablename(),
      'reltypes'        => array( RELTYPE_LATER_WORK_COMMENTS_ON_EARLIER_WORK => 'Commentary',
                                  RELTYPE_LATER_WORK_CONTINUES_EARLIER_WORK   => 'Continuation',
                                  RELTYPE_LATER_WORK_IS_GLOSS_ON_EARLIER_WORK => 'Gloss',
                                  RELTYPE_LATER_WORK_IS_IJAZA_FOR_EARLIER_WORK=> 'Ijaza',
                                  RELTYPE_LATER_WORK_SUMMARISES_EARLIER_WORK  => 'Summary',
                                  RELTYPE_LATER_WORK_TRANSLATES_EARLIER_WORK  => 'Translation',
                                  RELTYPE_LATER_WORK_IS_VERSIFICATION_OF_EARLIER_WORK => 'Versification' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ FIELDSET_PATRONS_OF_WORK ] = array( 
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_PATRON_OF_WORK => 'Patron' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ FIELDSET_DEDICATEES_OF_WORK ] = array( 
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_DEDICATEE_OF_WORK => 'Dedicatee' ),
      'right_table_name'=> $this->proj_work_tablename(),
      'side_to_get'     => 'left'
    );

    #-------------------------
    # People and organisations
    #-------------------------
    $settings[ 'orgs_of_which_member' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_MEMBER_OF_ORG => 'Membership of organisations' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'members_of_org' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_MEMBER_OF_ORG => 'Membership of organisations' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'person_locations' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_IN_LOCATION => 'Known geographical locations' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'place_of_birth' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_BORN_IN_LOCATION => 'Place of birth' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'place_of_death' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_DIED_AT_LOCATION => 'Place of death' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'person_to_person_equal' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_UNSPECIFIED_REL_TO_PERSON => 'Unspecified relationship',
                                  RELTYPE_PERSON_ACQUAINTANCE_OF_PERSON => 'Acquaintance',
                                  RELTYPE_PERSON_WAS_A_BUSINESS_ASSOCIATE_OF_PERSON => 'Business associate',
                                  RELTYPE_PERSON_COLLABORATED_WITH_PERSON => 'Collaborator',
                                  RELTYPE_PERSON_COLLEAGUE_OF_PERSON => 'Colleague',
                                  RELTYPE_PERSON_FRIEND_OF_PERSON => 'Friend',
                                  RELTYPE_PERSON_RELATIVE_OF_PERSON => 'Relative',
                                  RELTYPE_PERSON_SIBLING_OF_PERSON => 'Sibling',
                                  RELTYPE_PERSON_SPOUSE_OF_PERSON => 'Spouse' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'both'
    );

    $settings[ 'parent' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_PARENT_OF_CHILD => 'Parent' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'child' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_PARENT_OF_CHILD => 'Child' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'employer' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_EMPLOYED_WORKER => 'Employer' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'employee' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_EMPLOYED_WORKER => 'Employee' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'teacher' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_TAUGHT_STUDENT => 'Teacher' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'student' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_TAUGHT_STUDENT => 'Student' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'patron_of_person' ] = array( 
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_PATRON_OF_PERSON => 'Patron' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'protegee_of_person' ] = array( 
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_PATRON_OF_PERSON => 'Protegee' ),
      'right_table_name'=> $this->proj_person_tablename(),
      'side_to_get'     => 'right'
    );


    #---------------
    # Manifestations
    #---------------
    $settings[ 'former_owner' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_OWNED_MANIF => 'Former owners' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'place_of_copying' ] = array(
      'left_table_name' => $this->proj_manifestation_tablename(),
      'reltypes'        => array( RELTYPE_MANIF_WAS_COPIED_AT_PLACE => 'Place of copying' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'scribe_hand' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_HANDWROTE_MANIF        => 'Handwrote',
                                  RELTYPE_PERSON_PARTLY_HANDWROTE_MANIF => 'Partly handwrote' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'enclosure_to_this' ] = array(
      'left_table_name' => $this->proj_manifestation_tablename(),
      'reltypes'        => array( RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF => 'Enclosures' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'enclosing_this' ] = array(
      'left_table_name' => $this->proj_manifestation_tablename(),
      'reltypes'        => array( RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF => 'Enclosures' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'right'
    );

    $settings[ 'patrons_of_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_ASKED_FOR_COPYING_OF_MANIF => 
                                  'Asked for manuscript to be copied',
                                  RELTYPE_PERSON_WAS_PATRON_OF_MANIF => 'Patron' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'dedicatees_of_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_DEDICATEE_OF_MANIF => 'Dedicatee' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'endower_of_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_ENDOWER_OF_MANIF => 'Endower' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'endowee_of_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_WAS_ENDOWEE_OF_MANIF => 'Endowee' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'annotated_by' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_ANNOTATED_MANIF => 'Annotated manuscript' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'taught_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_TAUGHT_TEXT_OF_MANIF => 'Taught text' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'studied_manif' ] = array(
      'left_table_name' => $this->proj_person_tablename(),
      'reltypes'        => array( RELTYPE_PERSON_STUDIED_TEXT_OF_MANIF => 'Studied text' ),
      'right_table_name'=> $this->proj_manifestation_tablename(),
      'side_to_get'     => 'left'
    );

    $settings[ 'where_manif_was_studied' ] = array(
      'left_table_name' => $this->proj_manifestation_tablename(),
      'reltypes'        => array( RELTYPE_MANIF_WAS_STUDIED_IN_PLACE => 'Studied at location' ),
      'right_table_name'=> $this->proj_location_tablename(),
      'side_to_get'     => 'right'
    );


    $this->settings = $settings;
  }
  #----------------------------------------------------------------------------------

  function get_all_relationship_field_settings() {
    return $this->settings;
  }
  #----------------------------------------------------------------------------------

  function settings_exist_for_field( $fieldname ) {

    if( ! $fieldname ) return FALSE;
    
    return in_array( $fieldname, array_keys( $this->settings ));
  }
  #----------------------------------------------------------------------------------

  function get_relationship_field_setting( $fieldname, $element = NULL ) {

    if( ! $fieldname ) return NULL;
    if( ! $this->settings_exist_for_field( $fieldname )) return NULL;
    
    $setting = $this->settings[ "$fieldname" ];

    if( $element ) { # e.g. left table name, right table name etc.
      return $setting[ "$element" ];
    }

    return $setting;
  }
  #----------------------------------------------------------------------------------

  function list_rels_decoded( $known_table, # Known table and ID could be on either left or right 
                              $known_id,    # If same table is on both sides you get BOTH directions of relationship

                              $required_table = '%',     # '%' for any table
                              $required_reltype = '%',   # '%' for any relationship type

                              $html_output = 0 ) {

    if( ! $known_table ) return NULL;
    if( ! $known_id ) return NULL;

    if( ! $required_table ) $required_table = '%';
    if( ! $required_reltype ) $required_reltype = '%';

    $function_name = $this->proj_database_function_name( 'list_rels_decoded', $include_collection_code = TRUE );
    $statement = "select $function_name( "
               . " '$required_table', "
               . " '$required_reltype', "
               . " '$known_table', "
               . " '$known_id',"
               . " $html_output )";

    $result = $this->db_select_one_value( $statement );
    return $result;
  }
  #----------------------------------------------------------------------------------

  function get_other_side_for_this_on_left( $this_table, $this_id, $reltype = NULL, $other_table = NULL,
                                            $order_col = NULL ) {

    return $this->get_other_side_for_this( $this_table, $this_id, $reltype, $other_table,
                                           $order_col, $this_side = 'left' );
  }
  #----------------------------------------------------------------------------------

  function get_other_side_for_this_on_right( $this_table, $this_id, $reltype = NULL, $other_table = NULL,
                                            $order_col = NULL ) {

    return $this->get_other_side_for_this( $this_table, $this_id, $reltype, $other_table,
                                           $order_col, $this_side = 'right' );
  }
  #----------------------------------------------------------------------------------

  function get_other_side_for_this_on_both_sides( $this_table, $this_id, $reltype = NULL, $other_table = NULL,
                                                  $order_col = NULL ) {

    return $this->get_other_side_for_this( $this_table, $this_id, $reltype, $other_table,
                                           $order_col, $this_side = 'both' );
  }
  #----------------------------------------------------------------------------------

  function get_other_side_for_this( $this_table, $this_id = NULL, $reltype = NULL, $other_table = NULL,
                                    $order_col = NULL, $this_side = NULL ) {

    if( ! $this_table || ! $this_id ) return NULL;

    if( ! $order_col ) 
      $order_col = 'relationship_type, relationship_valid_from, relationship_valid_till, relationship_id';

    if( $this_side == 'left' )
      $sides_to_get = array( 'right' );
    elseif( $this_side == 'right' )
      $sides_to_get = array( 'left' );
    else
      $sides_to_get = array( 'left', 'right' );

    $statement = '';
    $side_count = 0;
    foreach( $sides_to_get as $side_to_get ) {
      $side_count++;
      if( $side_count > 1 ) $statement .= ' union ';

      if( $side_to_get == 'left' )
        $known_side = 'right';
      else
        $known_side = 'left';

      $statement .= " select '$known_side' as this_side, relationship_type, "
                 .  " ${side_to_get}_table_name as other_table_name, "
                 .  " ${side_to_get}_id_value as other_id_value, "
                 .  ' relationship_id, relationship_valid_from, relationship_valid_till '
                 .  ' from ' . $this->proj_relationship_tablename()
                 .  " where ${known_side}_table_name = '$this_table' "
                 .  " and ${known_side}_id_value = '$this_id' ";

      if( $reltype ) $statement .= " and relationship_type = '$reltype' ";

      if( $other_table ) $statement .= " and ${side_to_get}_table_name = '$other_table' ";
    }

    $statement .= " order by $order_col";

    $other_side = $this->db_select_into_array( $statement );

    if( is_null( $other_side )) $other_side = array();
    return $other_side;
  }
  #----------------------------------------------------------------------------------

  function change_id_value( $old_table_name, $old_id_value, $new_id_value, $display_status_msg = FALSE ) {

    if( ! $old_table_name ) return;
    if( ! $old_id_value ) return;
    if( ! $new_id_value ) return;

    #----------------
    # Left-hand side
    #----------------
    $statement = 'select relationship_id, right_table_name as other_table, right_id_value as other_id '
               . ' from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '$old_table_name' "
               . " and left_id_value = '$old_id_value' ";
    $rels_this_on_left = $this->db_select_into_array( $statement );

    #----------------
    # Right-hand side
    #----------------
    $rels = null;

    $statement = 'select relationship_id, left_table_name as other_table, left_id_value as other_id '
               . ' from ' . $this->proj_relationship_tablename()
               . " where right_table_name = '$old_table_name' "
               . " and right_id_value = '$old_id_value' ";
    $rels_this_on_right = $this->db_select_into_array( $statement );

    #-----------
    # Both sides
    #-----------
    $relcount = count( $rels_this_on_left ) + count( $rels_this_on_right );
    if( $relcount < 1 ) return;

    echo 'Preparing to delete: ' . $this->get_decode_for_one_id( $old_table_name, $old_id_value ) . LINEBREAK;
    echo "$relcount data relationships will be affected as a result of this deletion." . LINEBREAK . LINEBREAK;
    flush();

    $rels_both_sides = array( 'left_id_value' => $rels_this_on_left, 
                              'right_id_value' => $rels_this_on_right );

    $i = 0;
    foreach( $rels_both_sides as $other_column_name => $rels ) {
      if( is_array( $rels )) {
        foreach( $rels as $row ) {
          $i++;
          extract( $row, EXTR_OVERWRITE );
          if( $display_status_msg ) {
            $old_decode = $this->get_decode_for_one_id( $other_table, $other_id );

            echo "Knock-on effect $i of $relcount: updating details of " 
                 . $this->get_table_desc( $other_table ) . ': ';
            $this->echo_safely( $old_decode ); 
            echo LINEBREAK;
            flush();
          }

          $statement = 'update ' . $this->proj_relationship_tablename()
                     . " set $other_column_name = '$new_id_value' "
                     . " where relationship_id = $relationship_id";
          $this->db_run_query( $statement );

          if( $display_status_msg ) {
            $new_decode = $this->get_decode_for_one_id( $other_table, $other_id );

            if( $new_decode != $old_decode ) {
              $this->echo_safely( 'Now: ' . $this->get_decode_for_one_id( $other_table, $other_id )); 
              echo LINEBREAK; 
            }
            echo LINEBREAK;

            $anchor_name = 'deleted' . $relationship_id . '_' . $old_id_value;
            HTML::anchor( $anchor_name );
            $script = 'window.location.href = "' . $_SERVER['PHP_SELF'] . '" + "#' . $anchor_name . '"';
            HTML::write_javascript_function( $script );
            flush();
          }
        }
      }
    }
  }
  #----------------------------------------------------------------------------------

  function get_decode_for_one_id( $table_name, $id_value ) {

    $function_name = $this->proj_database_function_name( 'decode', $include_collection_code = TRUE );
    $statement = "select $function_name( '$table_name', '$id_value' )";
    $decode = $this->db_select_one_value( $statement );
    return $decode;
  }
  #----------------------------------------------------------------------------------

  function get_table_desc( $table_name ) {

    $cut = $this->collection_prefix . '_';

    $decode = $table_name;

    $decode = str_replace( $cut, '', $decode );
    $decode = str_replace( '_', ' ', $decode );

    return $decode;
  }
  #----------------------------------------------------------------------------------

  function save_rels_for_field_type( $field_type, $known_id_value ) {

    $left_table_name  = $this->get_relationship_field_setting( $field_type, $element = 'left_table_name' );
    $right_table_name = $this->get_relationship_field_setting( $field_type, $element = 'right_table_name' );
    $unknown_side     = $this->get_relationship_field_setting( $field_type, $element = 'side_to_get' );

    if( ! $left_table_name || ! $right_table_name || ! $known_id_value || ! $unknown_side )
      die( 'Too few or invalid details of data relationship were supplied.' );

    $rels_from_post = $this->read_rels_for_field_type( $field_type );

    foreach( $rels_from_post as $rel ) {

      $existing_relationship_id = NULL;
      $left_id_value = '';
      $right_id_value = '';
      $start_date = NULL;
      $end_date = NULL;
      $symmetrical = FALSE; # e.g. a relationship like 'friend' where either value could be on either side

      extract( $rel, EXTR_OVERWRITE );

      if( $unknown_side == 'left' ) {
        $left_id_value = $id_value_from_post;
        $right_id_value = $known_id_value;
      }

      elseif( $unknown_side == 'right' ) {
        $left_id_value = $known_id_value;
        $right_id_value = $id_value_from_post;
      }

      elseif( $unknown_side == 'both' ) { #symmetrical relationships like 'friend' where value could be on either side
        $symmetrical = TRUE;

        $left_id_value = $known_id_value;  # We need to check both ways round, whether it's a save or a delete. 
        $right_id_value = $id_value_from_post;

        $existing_relationship_id = $this->get_relationship_id( $left_table_name, $left_id_value,
                                                                $relationship_type,
                                                                $right_table_name, $right_id_value );
        if( ! $existing_relationship_id ) { # check the other way round
          $right_id_value = $known_id_value;
          $left_id_value = $id_value_from_post;

          $existing_relationship_id = $this->get_relationship_id( $left_table_name, $left_id_value,
                                                                  $relationship_type,
                                                                  $right_table_name, $right_id_value );
        }
      }
      else
        die( 'Too few or invalid details of data relationship were supplied.' );

      if( ! $symmetrical ) {  # check whether there is already an existing record
        $existing_relationship_id = $this->get_relationship_id( $left_table_name, $left_id_value,
                                                                $relationship_type,
                                                                $right_table_name, $right_id_value );
      }

      if( $action == 'save' ) {  # check if we need to insert or update
        if( $existing_relationship_id ) 
          $action = 'update';
        else
          $action = 'insert';
      }

      if( $action == 'insert' ) {
        $this->insert_relationship(  $left_table_name, $left_id_value, 
                                     $relationship_type,
                                     $right_table_name, $right_id_value,
                                     $start_date, $end_date );
      }
      elseif( $action == 'update' ) {
        $this->update_relationship_dates_by_primary_key( $existing_relationship_id, $start_date, $end_date );
      }
      elseif( $action == 'delete' && $existing_relationship_id ) {
        $this->delete_relationship_by_primary_key(  $existing_relationship_id );
      }
    }
  }
  #----------------------------------------------------------------------------------
  # Fieldnames are different in cases where there is only one entry (e.g. person's birthplace):
  # have to look at 'new' field to see if old value should be blanked out.

  function save_single_rel_for_field_type( $field_type, $known_id_value ) {
    #------------------------------------------
    # Look up table names and relationship type
    #------------------------------------------
    $settings = $this->get_relationship_field_setting( $field_type );
    extract( $settings, EXTR_OVERWRITE );

    if( ! $left_table_name || ! $right_table_name || ! $reltypes || ! $side_to_get || ! $known_id_value )
      die( 'Too few or invalid details of data relationship were supplied.' );

    if( $side_to_get != 'left' && $side_to_get != 'right' ) # this function is not suitable for symmetrical 
                                                            # relationships like 'friend' or 'collaborator'
      die( 'Invalid details were supplied for data relationship.' );

    #--------------
    # Get new value
    #--------------
    $fieldname = $this->proj_new_id_fieldname_from_fieldset_name( $field_type );

    $this->name_of_html_id_from_post = $fieldname;
    $new_value = $this->read_post_parm( $fieldname );

    #--------------
    # Get old value
    #--------------
    if( $side_to_get == 'left' ) 
      $known_side = 'right';
    else 
      $known_side = 'left';

    $funcname = "get_other_side_for_this_on_${known_side}";
    $this_table = "${known_side}_table_name";
    $other_table = "${side_to_get}_table_name";
    foreach( $reltypes as $reltype => $label ) {
      $relationship_type = $reltype;
      break; # just get one relationship type - we're only expecting one here
    }

    $existing = $this->$funcname( $$this_table, $known_id_value, $relationship_type, $$other_table );
    $old_value = '';
    if( count( $existing ) > 0 ) {
      foreach( $existing as $row ) {
        $old_value = $row[ 'other_id_value' ];
        break;  # we're only expecting one value, e.g. one birthplace per person
      }
    }

    #--------------------------------------------
    # Delete the old value and insert the new one
    #--------------------------------------------
    if( $new_value != $old_value ) {

      if( $side_to_get == 'left' ) {
        $left_id_value = $old_value;
        $right_id_value = $known_id_value;
      }
      else  {
        $right_id_value = $old_value;
        $left_id_value = $known_id_value;
      }

      #-------
      # Delete
      #-------
      if( $old_value ) {  # delete old entry (we'll assume there is only one of each)

        $this->delete_relationship( $left_table_name,
                                    $left_id_value,
                                    $relationship_type,
                                    $right_table_name,
                                    $right_id_value );
      }

      if( $side_to_get == 'left' ) 
        $left_id_value = $new_value;
      else 
        $right_id_value = $new_value;

      #-------
      # Insert
      #-------
      if( $new_value ) {  # insert new entry (we'll assume there is only one of each)

        $this->insert_relationship( $left_table_name,
                                    $left_id_value,
                                    $relationship_type,
                                    $right_table_name,
                                    $right_id_value );
      }
    }
  }
  #-----------------------------------------------------

  function read_rels_for_field_type( $field_type ) {

    $rels_from_post = array();
    $enough_parms = TRUE;
    $this->name_of_html_id_from_post = NULL;
    $this->name_of_start_date_from_post = NULL;
    $this->name_of_end_date_from_post = NULL;

    if( ! $field_type )   # this will be something like 'author_sender'
      $enough_parms = FALSE;

    $relevant_relationship_types = $this->get_relationship_field_setting( $field_type, $element = 'reltypes' );

    if( ! is_array( $relevant_relationship_types )) 
      $enough_parms = FALSE;

    if( count( $relevant_relationship_types ) < 1 ) 
      $enough_parms = FALSE;

    if( ! $enough_parms ) 
      return $rels_from_post;

    #-----------

    # Work out the fieldnames to check, based on the code for the field group such as "Author/sender".
    $entity_field_root = $this->proj_id_fieldname_from_fieldset_name( $field_type );

    # Use the first relationship type from the list as the default type when creating a new relationship.
    foreach( $relevant_relationship_types as $relationship_code => $label ) {
      $default_relationship_code = $relationship_code;
      break;
    }

    # See if any of the relevant fieldnames have been passed to us via POST.
    foreach( $_POST as $post_key => $post_value ) {

      if( ! $this->is_html_id( $post_key )) die( 'Invalid input.' );

      # Clear out values for start/end dates of the relationship.
      $start_date = NULL;
      $end_date = NULL;
      $start_date_fieldname = NULL;
      $end_date_fieldname = NULL;
      $this->name_of_start_date_from_post = NULL;
      $this->name_of_end_date_from_post = NULL;
      
      # Read ID values for a particular field group, such as 'Author/sender' or 'Person-to-person relationships'.
      # A field group may contain several relationship types, e.g. 'Author/sender' group has author, sender, signatory.
      # Then get individual relationship types within this field group, and optionally start/end dates for each type.
      #-----------------------
      # Existing relationships
      #-----------------------
      #----------------------------------------------------------------------------
      # Unfortunately we have some records where the ID value contains a full stop.
      # PHP automatically converts this to underscore when it comes in from POST.
      #----------------------------------------------------------------------------
      if( $this->post_parm_is_existing_rel_id( $post_key, $post_value, $entity_field_root )) {

        # Read the ID of the person, the repository or whatever, that forms one side of the relationship.
        $this->name_of_html_id_from_post = $post_key;
        $entity_field_value = $this->read_post_parm( $post_key ); # go through standard validation
        $rels_from_post[ "$post_key" ] = array( 'id_value' => $entity_field_value );

        # Read the 'deletion checkbox' to see if this person etc should be totally deleted from the field group.
        $deletion_checkbox_fullname = 'delete_' . $post_key;
        $this->name_of_html_id_from_post = $deletion_checkbox_fullname;
        $deletion_checkbox_value = $this->read_post_parm( $deletion_checkbox_fullname );
        $rels_from_post["$post_key"]['delete'] = $deletion_checkbox_value;

        $stripped_value = str_replace( '.', '_', $entity_field_value );

        foreach( $relevant_relationship_types as $relationship_code => $label ) {
          $rel_checkbox_fullname = $this->proj_make_rel_checkbox_name( $field_type, 
                                                                       $relationship_code,
                                                                       $stripped_value );
          $this->name_of_html_id_from_post = $rel_checkbox_fullname;
          $rel_checkbox_value = $this->read_post_parm( $rel_checkbox_fullname );

          # Get start/end dates of existing relationship if entered.
          $start_date_fieldname = $this->proj_start_fieldname_from_id_fieldname( $post_key, $relationship_code );
          $this->name_of_start_date_from_post = $start_date_fieldname; # used by 'validate parm'
          $start_date = $this->read_post_parm( $start_date_fieldname );
          if( $start_date && $this->is_integer( $start_date )) $start_date = '01/01/' . $start_date;

          $end_date_fieldname = $this->proj_end_fieldname_from_id_fieldname( $post_key, $relationship_code );
          $this->name_of_end_date_from_post = $end_date_fieldname; # used by 'validate parm'
          $end_date = $this->read_post_parm( $end_date_fieldname );
          if( $end_date && $this->is_integer( $end_date )) $end_date = '31/12/' . $end_date;

          $rels_from_post["$post_key"]["$relationship_code"] = array( 'reltype' => $rel_checkbox_value,
                                                                      'start_date' => $start_date,
                                                                      'end_date' => $end_date );
        }
      }

      #-----------------
      # New relationship
      #-----------------
      elseif( $this->post_parm_is_new_rel_id( $post_key, $field_type )) {
        $this->name_of_html_id_from_post = $post_key;
        $entity_field_value = $this->read_post_parm( $post_key ); # go through standard validation
        if( $entity_field_value ) {

          # Get start/end dates of new relationship if entered.
          $start_date_fieldname = $this->proj_start_fieldname_from_id_fieldname( $post_key );
          $this->name_of_start_date_from_post = $start_date_fieldname; # used by 'validate parm'
          $start_date = $this->read_post_parm( $start_date_fieldname );
          if( $start_date && $this->is_integer( $start_date )) $start_date = '01/01/' . $start_date;

          $end_date_fieldname = $this->proj_end_fieldname_from_id_fieldname( $post_key );
          $this->name_of_end_date_from_post = $end_date_fieldname; # used by 'validate parm'
          $end_date = $this->read_post_parm( $end_date_fieldname );
          if( $end_date && $this->is_integer( $end_date )) $end_date = '31/12/' . $end_date;

          $rels_from_post[ "$post_key" ] = array( 'id_value' => $entity_field_value );
          $rels_from_post[ "$post_key" ]["$default_relationship_code"] = array( 'reltype' => $default_relationship_code,
                                                                                'start_date' => $start_date,
                                                                                'end_date' => $end_date );
        }
      }

      $this->name_of_html_id_from_post = NULL;
    }

    #-----------------------------------------------------
    # Now arrange the data in a more easily processed form
    #-----------------------------------------------------
    $rels_for_field_type = array();

    foreach( $rels_from_post as $fieldgroup_name => $fieldgroup_details ) {

      $id_value = $fieldgroup_details[ 'id_value' ];
      $delete = $fieldgroup_details[ 'delete' ];
    
      foreach( $fieldgroup_details as $key => $data ) {
        if( $key == 'id_value' ) continue; # we've already picked up some details (delete checkbox and, e.g., person ID)
        if( $key == 'delete' ) continue;   # so we only really want to look at relationship type and dates now

        $relationship_type = $key;

        # Now we come to the innermost array, which contains relationship type and start/end dates
        foreach( $data as $relkey => $relval ) {
      
          if( $relkey == 'start_date' ) 
            $start_date = $relval;
          elseif( $relkey == 'end_date' )
            $end_date = $relval;
          else 
            $rel_enabled = $relval; # if the relevant checkbox was not ticked, this will be blank
        }
      
        $action = 'save'; # could be insert or update
        if( $delete ) $action = 'delete';
        if( ! $rel_enabled ) $action = 'delete';

        $rels_for_field_type[] = array( 'action'            => $action,
                                       'id_value_from_post' => $id_value,
                                       'relationship_type'  => $relationship_type,
                                       'start_date'         => $start_date,
                                       'end_date'           => $end_date );
      }
    }

    return $rels_for_field_type;
  }
  #----------------------------------------------------------------------------------

  function post_parm_is_existing_rel_id( $post_key, $post_value, $entity_field_root ) {

    if( $post_key == $entity_field_root . str_replace( '.', '_', $post_value )) 
      return TRUE;
    else
      return FALSE;
  }
  #----------------------------------------------------------------------------------

  function post_parm_is_new_rel_id( $post_key, $field_type ) {

    if( $post_key == $this->proj_new_id_fieldname_from_fieldset_name( $field_type )) 
      return TRUE;
    else
      return FALSE;

  }
  #----------------------------------------------------------------------------------

  function get_relationship_id(  $left_table_name, $left_id_value,
                                 $relationship_type,
                                 $right_table_name, $right_id_value ) {

    $where_clause = $this->get_where_clause_for_one_rel( $left_table_name, $left_id_value,
                                                         $relationship_type,
                                                         $right_table_name, $right_id_value );

    $statement = 'select relationship_id from ' . $this->proj_relationship_tablename()
               . " where $where_clause";

    $existing_relationship = $this->db_select_one_value( $statement );
    return $existing_relationship;
  }
  #----------------------------------------------------------------------------------

  function insert_relationship(  $left_table_name, $left_id_value,
                                 $relationship_type,
                                 $right_table_name, $right_id_value,
                                 $start_date = NULL, $end_date = NULL ) {

    $existing_relationship = $this->get_relationship_id( $left_table_name, $left_id_value,
                                                         $relationship_type,
                                                         $right_table_name, $right_id_value );
    if( $existing_relationship ) return;

    if( $start_date )
      $start_date = "'$start_date'::date";
    else
      $start_date = 'null';

    if( $end_date )
      $end_date = "'$end_date'::date";
    else
      $end_date = 'null';

    $statement = 'insert into ' . $this->proj_relationship_tablename()
               . ' ( left_table_name, left_id_value, relationship_type, right_table_name, right_id_value, '
               . '   relationship_valid_from, relationship_valid_till ) '
               . ' values ( '
               . " '$left_table_name', '$left_id_value', '$relationship_type', '$right_table_name', '$right_id_value',"
               . " $start_date, $end_date )";

    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function delete_relationship(  $left_table_name, $left_id_value,
                                 $relationship_type,
                                 $right_table_name, $right_id_value ) {

    $where_clause = $this->get_where_clause_for_one_rel( $left_table_name, $left_id_value,
                                                         $relationship_type,
                                                         $right_table_name, $right_id_value );

    $statement = 'delete from ' . $this->proj_relationship_tablename()
               . " where $where_clause";

    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function delete_relationship_by_primary_key(  $relationship_id ) {

    $statement = 'delete from ' . $this->proj_relationship_tablename()
               . " where relationship_id = $relationship_id";

    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function update_relationship_dates_by_primary_key(  $relationship_id, $start_date, $end_date ) {

    if( $start_date ) {
      $start_date = "'$start_date'::date";
      $start_where = "coalesce( relationship_valid_from, '9999-12-31'::date ) != $start_date";
    }
    else {
      $start_date = 'null';
      $start_where = 'relationship_valid_from is not null';
    }

    if( $end_date ) {
      $end_date = "'$end_date'::date";
      $end_where = "coalesce( relationship_valid_till, '9999-12-31'::date ) != $end_date";
    }
    else {
      $end_date = 'null';
      $end_where = 'relationship_valid_till is not null';
    }

    $statement = 'update ' . $this->proj_relationship_tablename() . ' set '
               . " relationship_valid_from = $start_date, "
               . " relationship_valid_till = $end_date "
               . " where relationship_id = $relationship_id "
               . " and ( $start_where or $end_where )";

    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function get_where_clause_for_one_rel( $left_table_name, $left_id_value, 
                                         $relationship_type, 
                                         $right_table_name, $right_id_value ) {

    if( ! $left_table_name || ! $left_id_value || ! $relationship_type || ! $right_table_name || ! $right_id_value )
      die( 'Relationship type and both sides of data relationship must be supplied.' );

    $where_clause = " left_table_name       = '$left_table_name' "
                  . " and left_id_value     = '$left_id_value' "
                  . " and relationship_type = '$relationship_type' "
                  . " and right_table_name  = '$right_table_name' "
                  . " and right_id_value    = '$right_id_value' ";

    return $where_clause;
  }
  #----------------------------------------------------------------------------------

  function value_exists_on_left( $table_name, $id_value ) {

    $statement = 'select relationship_id from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '$table_name' and left_id_value = '$id_value'";

    $existing_relationship = $this->db_select_one_value( $statement );
    return $existing_relationship;
  }
  #----------------------------------------------------------------------------------

  function value_exists_on_right( $table_name, $id_value ) {

    $statement = 'select relationship_id from ' . $this->proj_relationship_tablename()
               . " where right_table_name = '$table_name' and right_id_value = '$id_value'";

    $existing_relationship = $this->db_select_one_value( $statement );
    return $existing_relationship;
  }
  #----------------------------------------------------------------------------------

  function value_exists_on_either_side( $table_name, $id_value ) {

    $existing = $this->value_exists_on_left( $table_name, $id_value );
    if( $existing ) return $existing;

    $existing = $this->value_exists_on_right( $table_name, $id_value );
    return $existing;
  }
  #----------------------------------------------------------------------------------

  function delete_all_rels_for_id( $table_name, $id_value ) {

    $statement = 'delete from ' . $this->proj_relationship_tablename()
               . " where left_table_name = '$table_name' and left_id_value = '$id_value'";
    $this->db_run_query( $statement );

    $statement = 'delete from ' . $this->proj_relationship_tablename()
               . " where right_table_name = '$table_name' and right_id_value = '$id_value'";
    $this->db_run_query( $statement );
  }
  #----------------------------------------------------------------------------------

  function validate_parm( $parm_name ) {  # overrides parent method

    switch( $parm_name ) {

      case 'relationship_id':
        if( $this->parm_value == 'null' )
          return TRUE;
        else
          return $this->is_integer( $this->parm_value );

      case 'left_id_value':
      case 'right_id_value':
      case 'left_table_name':
      case 'right_table_name':

        if( $this->parm_value == NULL )
          return TRUE;
        else
          return $this->is_html_id( $this->parm_value );

      case "$this->name_of_html_id_from_post":
        if( $this->parm_value == '' ) return TRUE;
        if( $this->is_integer( $this->parm_value )) return TRUE;
        return $this->is_html_id( $this->parm_value );

      case "$this->name_of_start_date_from_post":
      case "$this->name_of_end_date_from_post":
        if( $this->parm_value == '' ) return TRUE;
        if( $this->is_integer( $this->parm_value ) && strlen( $this->parm_value ) <= strlen( 'yyyy' )) 
          return TRUE;  # they may have entered just year
        return $this->is_dd_mm_yyyy( $this->parm_value );

      default:
        return parent::validate_parm( $parm_name );
    }
  }
  #-----------------------------------------------------
}
?>
