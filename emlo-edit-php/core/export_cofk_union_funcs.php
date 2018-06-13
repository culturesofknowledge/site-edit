<?php

function is_unknown_reltype( $relationship_type, $left_table_name, $right_table_name ) {

	return false; // The front should know everything.


  $is_unknown = TRUE;

  switch( $relationship_type ) {

    case 'created':
    case 'died_at_location':
    case 'enclosed_in':
    case 'formerly_owned':
    case 'handwrote':
    case 'image_of':
    case 'is_manifestation_of':
    case 'is_related_to':
    case 'is_reply_to':
    case 'matches':
    case 'member_of':
    case 'mentions':
    case 'mentions_place':
    case 'mentions_work':
    case 'parent_of':
    case 'refers_to':
    case 'refers_to_addressee':
    case 'refers_to_author':
    case 'refers_to_date':
    case 'refers_to_receipt_date':
    case 'refers_to_people_mentioned_in_work':
    case 'relative_of':
    case 'sibling_of':
    case 'spouse_of':
    case 'stored_in':
    case 'unspecified_relationship_with':
    case 'was_addressed_to':
    case 'was_born_in_location':
    case 'was_in_location':
    case 'was_sent_from':
    case 'was_sent_to':
    case 'refers_to_origin':
    case 'refers_to_destination':
    case 'route':

      $is_unknown = FALSE;
      break;
  }

  # Some relationship types are only known to the front end in particular combinations of tables
  if( $relationship_type == 'member_of' && $right_table_name == 'cofk_union_role_category' )
    $is_unknown = TRUE;

  elseif( $left_table_name == 'cofk_union_institution' and $relationship_type == 'is_related_to' 
          and $right_table_name == 'cofk_union_resource' )
    $is_unknown = TRUE;

  elseif( $left_table_name == 'cofk_union_image' and $relationship_type == 'image_of' 
          and $right_table_name != 'cofk_union_manifestation' )
    $is_unknown = TRUE;

  return $is_unknown;
}
#-------------------------------------------------------------------------

function decode_catalogue( $catalogue_code, $cats ) {
  return $cats[ "$catalogue_code" ];
}

#-------------------------------------------------------------------------

function decode_user( $username, $users ) {

  if( array_key_exists( $username, $users ))
    return $users[ "$username" ];
  else
    return $username;
}
#-------------------------------------------------------------------------

function decode_calendar( $calendar_code ) {

  switch( $calendar_code ) {

    case 'JM':
      return 'Julian (start of year: 25 Mar)';

    case 'JJ':
      return 'Julian (start of year: 01 Jan)';

    case 'G':
      return 'Gregorian';

    case 'O':
      return 'Other';

    default:
      return 'Unknown';
  }
}

#-------------------------------------------------------------------------

function get_work_exclusion_clause() {
  $where_clause  = " and work_to_be_deleted != 1 ";
  $where_clause .= " and original_catalogue in (SELECT catalogue_code FROM cofk_lookup_catalogue WHERE publish_status = 1)";

  return $where_clause;
}
#-------------------------------------------------------------------------

function get_image_exclusion_clause() {
  $where_clause  = " and can_be_displayed != 'N'";
  return $where_clause;
}
#-------------------------------------------------------------------------

function get_manifestation_exclusion_clause( $manifestation_id ) {

  $where_clause  = " and '$manifestation_id' not in "
                 . ' (select left_id_value from cofk_union_relationship r, cofk_union_work w'
                 . " where left_table_name = 'cofk_union_manifestation' "
                 . " and left_id_value = '$manifestation_id' "
                 . " and relationship_type = 'is_manifestation_of' "
                 . " and right_table_name = 'cofk_union_work' "
                 . " and right_id_value = w.work_id "
                 . " and ( w.work_to_be_deleted = 1 "
                 . " or w.original_catalogue not in (SELECT catalogue_code FROM cofk_lookup_catalogue WHERE publish_status = 1)"
                 . " ))";

  return $where_clause;
}
#-------------------------------------------------------------------------

function omit_col( $table_name, $colname ) { # Some extra columns have been added to the database, so we must omit
                                             # these until the front end ingest procedure has been updated to match

	return FALSE;

  $skip_col = FALSE;

  if( $table_name == 'cofk_union_person' ) {
    switch( $colname ) {
      case 'editors_notes':
      case 'other_details_summary':
      case 'organisation_type':
      case 'date_of_birth_calendar':
      case 'date_of_birth_is_range':
      case 'date_of_birth2_year':
      case 'date_of_birth2_month':
      case 'date_of_birth2_day':
      case 'date_of_death_calendar':
      case 'date_of_death_is_range':
      case 'date_of_death2_year':
      case 'date_of_death2_month':
      case 'date_of_death2_day':
      case 'flourished':
      case 'flourished_calendar':
      case 'flourished_is_range':
      case 'flourished_year':
      case 'flourished_month':
      case 'flourished_day':
      case 'flourished2_year':
      case 'flourished2_month':
      case 'flourished2_day':

        $skip_col = TRUE;
        break;
    }
  }
  elseif( $table_name == 'cofk_union_location' ) {
    switch( $colname ) {
      case 'editors_notes':
      case 'element_1_eg_room':
      case 'element_2_eg_building':
      case 'element_3_eg_parish':
      case 'element_4_eg_city':
      case 'element_5_eg_county':
      case 'element_6_eg_country':
      case 'element_7_eg_empire':
        $skip_col = TRUE;
        break;
    }
  }
  elseif( $table_name == 'cofk_union_manifestation' ) {
    switch( $colname ) {
      case 'manifestation_creation_date2_year':
      case 'manifestation_creation_date2_month':
      case 'manifestation_creation_date2_day':
      case 'manifestation_creation_date_is_range':
      case 'manifestation_creation_date_as_marked':
        $skip_col = TRUE;
        break;
    }
  }
  elseif( $table_name == 'cofk_union_image' ) {
    switch( $colname ) {
      case 'can_be_displayed':
      case 'licence_details':
      case 'licence_url':
        $skip_col = TRUE;
        break;
    }
  }
  elseif( $table_name == 'cofk_union_institution' ) {
    switch( $colname ) {
      case 'editors_notes':
        $skip_col = TRUE;
        break;
    }
  }

  return $skip_col;
}
