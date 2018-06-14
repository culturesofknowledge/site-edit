<?php

function is_unknown_reltype( $relationship_type, $left_table_name, $right_table_name ) {
	return false; // The front should know everything.
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

function omit_col( $table_name, $colname ) {
	return false;
}
