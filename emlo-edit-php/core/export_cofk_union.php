<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Export Union database' );
define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

define( 'CONSTANT_DATABASE_TYPE', 'live' );

defined( "DEBUGGING" ) or define( "DEBUGGING", FALSE );

require_once "common_components.php";
require_once "proj_components.php";

#-------------------------------------------------------------------------

function is_unknown_reltype( $relationship_type, $left_table_name, $right_table_name ) {

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

function decode_user( $username ) {

  global $users;
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
  $where_clause .= " and date_of_work_std != '1900-01-01'"; # the editors "hide" irrelevant cards by setting date to this
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
                 . " or w.date_of_work_std = '1900-01-01' ))"; # editors "hide" irrelevant cards by setting date to this

  return $where_clause;
}
#-------------------------------------------------------------------------

function omit_col( $table_name, $colname ) { # Some extra columns have been added to the database, so we must omit
                                             # these until the front end ingest procedure has been updated to match
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

#-------------------------------------------------------------------------

$db_connection = new DBQuery ( 'postgres' );
$cofk = new Project( $db_connection );
$db = new DBEntity( $db_connection );

global $users;
$users = array();

$statement = 'select username, forename, surname from cofk_users';
$us = $cofk->db_select_into_array( $statement );
foreach( $us as $u ) {
  extract( $u, EXTR_OVERWRITE );
  if( $username == 'cofksuper' )
    $person_name = 'SysAdmin';
  else
    $person_name = trim( $forename ) . ' ' . trim( $surname );
  $users[ "$username" ] = $person_name;
}
$users[ 'postgres' ] = 'SysAdmin';


$table = getenv( 'COFK_TABLE_TO_EXPORT' );
$header_required = getenv( 'COFK_WRITE_CSV_HEADER' );

if( $table ) {
  echo "Starting to process $table" . NEWLINE;

  if( $table == 'cofk_union_person' )
    $person_obj = new Person( $db_connection );

  $colinfo = $db->db_list_columns( $table );

  $id = str_replace( 'cofk_union_', '', $table );
  $id .= '_id';
  if( $table == 'cofk_union_relationship_type' ) $id = 'relationship_code';
  $export_id = $id;
  if( $table == 'cofk_union_work' ||  $table == 'cofk_union_person' ) 
    $id = 'i' . $id;

  $next_id = getenv( 'COFK_FIRST_ID_IN_TABLE' );
  $last_id = getenv( 'COFK_LAST_ID_IN_TABLE' );

  $next_id = trim( $next_id );
  $last_id = trim( $last_id );

  if( ! $next_id ) die( 'Invalid first ID' );
  if( ! $last_id ) die( 'Invalid last ID' );

  $where_clause = '';
  if( $table == 'cofk_union_work' ) {
    $where_clause = get_work_exclusion_clause();
    $reason = 'work marked for deletion or hidden';

    $cats = array();
    $statement = 'select * from cofk_lookup_catalogue';
    $catalogues = $cofk->db_select_into_array( $statement );
    foreach( $catalogues as $row ) {
      $catcode = $row[ 'catalogue_code' ];
      $catname = $row[ 'catalogue_name' ];
      $cats[ "$catcode" ] = $catname;
    }
    $cats[ 'unspecified' ] = 'No catalogue specified';

  }
  elseif( $table == 'cofk_union_image' ) {
    $where_clause = get_image_exclusion_clause();
    $reason = 'image flagged as non-displayable (e.g. non-Bodleian Lister image)';
  }

  $filename = $table . '.csv';
  echo $filename . NEWLINE;

  $handle = fopen( $filename, 'a' );

  #-------------------------------------------------------------------------

  $i = 0;
  while( $next_id ) {

    $statement = "select * from $table where $id = '$next_id'";

    $skip_row = FALSE;

    if( $table == 'cofk_union_work' ) 
      $reason = 'work marked for deletion or hidden';
    elseif( $table == 'cofk_union_image' ) 
      $reason = 'image flagged as non-displayable (e.g. non-Bodleian Lister image)';
    else
      $reason = '';

    if( $table == 'cofk_union_manifestation' ) {
      $where_clause = get_manifestation_exclusion_clause( $next_id );
      $reason = 'work marked for deletion or hidden';
    }

    $statement .= $where_clause;
    $result = $cofk->db_select_into_array( $statement );
    if( count( $result ) != 1 ) {
      $skip_row = TRUE;
      if( $table == 'cofk_union_image' ) {
        $statement = "select image_filename from cofk_union_image where image_id = $next_id";
        $rejected_image = $cofk->db_select_one_value( $statement );
        $reason = "Non-displayable image, e.g. non-Bodleian Lister image: $rejected_image" . NEWLINE;
      }
      if( ! $reason )
        $reason = "No such ID: $next_id";
    }

    # For images we now need to check that it is not an image of a work marked for deletion or hidden.
    # We don't need to re-check this for relationships because the 'manifestation' side of the check should deal with it.
    if( $table == 'cofk_union_image' && ! $skip_row ) {
      $statement = 'select right_id_value from cofk_union_relationship'
                 . " where left_table_name = 'cofk_union_image' "
                 . " and left_id_value = '$next_id' "
                 . " and relationship_type = 'image_of' "
                 . " and right_table_name = 'cofk_union_manifestation'";
      $manifestation_id = $cofk->db_select_one_value( $statement );

      $invalid_work = '';

      $statement = 'select w.work_id from cofk_union_relationship r, cofk_union_work w '
      . " where r.left_table_name = 'cofk_union_manifestation' "
      . " and r.left_id_value = '$manifestation_id'"
      . " and r.relationship_type = 'is_manifestation_of' "
      . " and r.right_table_name = 'cofk_union_work' "
      . " and r.right_id_value = w.work_id "
      . " and (w.work_to_be_deleted = 1  "
      . " or w.original_catalogue not in (SELECT catalogue_code FROM cofk_lookup_catalogue WHERE publish_status = 1)"
      . " or w.date_of_work_std = '1900-01-01')";

      $invalid_work = $cofk->db_select_one_value( $statement );
      if( $invalid_work ) {
        $skip_row = TRUE;
        $reason = 'work marked for deletion or hidden';
      }
    }

    # Similarly I suppose we should exclude comments that are no longer attached to a valid work
    if( $table == 'cofk_union_comment' && ! $skip_row ) {
      $invalid_work = '';

      $statement = 'select w.work_id from cofk_union_relationship r, cofk_union_work w '
                 . " where left_table_name = 'cofk_union_comment' "
                 . " and left_id_value = '$next_id' "
                 . " and r.relationship_type like 'refers_to%' "
                 . " and r.right_table_name = 'cofk_union_work' "
                 . " and r.right_id_value = w.work_id "
                 . " and (w.work_to_be_deleted = 1  "
                 . " or w.original_catalogue not in (SELECT catalogue_code FROM cofk_lookup_catalogue WHERE publish_status = 1)"
                 . " or w.date_of_work_std = '1900-01-01')";

      $invalid_work = $cofk->db_select_one_value( $statement );
      if( $invalid_work ) {
        $skip_row = TRUE;
        $reason = 'work marked for deletion or hidden';
      }
    }

    if( ! $skip_row ) {
      $row = $result[ 0 ];

      if( $table == 'cofk_union_relationship' ) {
        extract( $row, EXTR_OVERWRITE );

        $skip_row = is_unknown_reltype( $relationship_type,  # some types not set up in front end yet
                                        $left_table_name, 
                                        $right_table_name );
        if( $skip_row )
          $reason = "Unknown combination: $left_table_name $relationship_type $right_table_name" . NEWLINE;

        if( ! $skip_row ) {
          $sides = array( 'left_table_name'  => 'left_id_value', 
                          'right_table_name' => 'right_id_value' );
          foreach( $sides as $rel_table => $rel_id ) {
            if( $$rel_table == 'cofk_union_image' ) {
              $selection = 'image_filename';
              $keycol_in_rel_table = 'image_id';
            }
            elseif( $$rel_table == 'cofk_union_work' ) {
              $selection = 'iwork_id';
              $keycol_in_rel_table = 'work_id';
            }
            elseif( $$rel_table == 'cofk_union_manifestation' ) {
              $selection = 'manifestation_id';
              $keycol_in_rel_table = 'manifestation_id';
            }
            elseif( $$rel_table == 'cofk_union_resource' ) {
              $selection = 'resource_url';
              $keycol_in_rel_table = 'resource_id';
            }
            else
              continue;
            
            $statement = "select $selection from " . $$rel_table . " where $keycol_in_rel_table::varchar = ";
            $statement .= "( select $rel_id from cofk_union_relationship ";
            $statement .= "  where relationship_id = $next_id ) ";
            if( $$rel_table == 'cofk_union_work' ) {
              $statement .= get_work_exclusion_clause();
              $reason = 'Work marked for deletion: ID ' . $$rel_id;
            } 
            elseif( $$rel_table == 'cofk_union_image' ) {
              $statement .= get_image_exclusion_clause();
              $reason = 'Non-displayable image e.g. non-Bodleian Lister image: ID ' . $$rel_id;
            } 
            elseif( $$rel_table == 'cofk_union_manifestation' ) {
              $statement .= get_manifestation_exclusion_clause( $$rel_id );
              $reason = 'Manifestation of work marked for deletion or hidden: ID ' . $$rel_id;
            } 

            #echo $statement . NEWLINE . NEWLINE;
            $selected = $cofk->db_select_one_value( $statement );

            if( $$rel_table == 'cofk_union_image' && $selected ) {
              if( ! $cofk->string_starts_with( $selected, 'http' )) {
                $selected = IMAGE_URL_START . $selected;
              }
              if( $cofk->proj_link_is_broken( $selected )) {
                $skip_row = TRUE;
                $reason = 'Link to file ' . $selected . ' is broken.';
                break;
              }
            }

            elseif( $$rel_table == 'cofk_union_resource' && $selected ) {
              if( $cofk->string_starts_with( $selected, 'http' )) {
                if( $cofk->proj_link_is_broken( $selected )) {
                  $skip_row = TRUE;
                  $reason = 'Link to file ' . $selected . ' is broken.';
                  break;
                }
              }
            }

            if( ! $selected ) {
              if( $$rel_table != 'cofk_union_resource' ) # this could be a resource without a link, e.g. just page nos.
                $skip_row = TRUE;
              break;
            }
          }
        }
      }
    }

    if( ! $skip_row ) {
      $i++;

      $header = '';
      $data = '';
      extract( $row, EXTR_OVERWRITE );

	    if( $i % 100 == 0 ) {
		    echo $table . ' id:' . $row[$id] . ' count:' . $i . NEWLINE;
	    }

      if( $i == 1 && $header_required ) {  # write out header row
        $firstcol = TRUE;
        foreach( $row as $colname => $colvalue ) {
          if( omit_col( $table, $colname )) { # Some extra columns have been added to the database, so we must omit
            continue;                         # these until the front end ingest procedure has been updated to match
          }
          if( ! $firstcol ) $header .= ',';
          $firstcol = FALSE;
          $header .= $cofk->csv_field( $colname );
        }

        if( $table == 'cofk_union_person' or $table == 'cofk_union_location' ) {
          $header .= ',';
          $header .= $cofk->csv_field( 'sent_count' );
          $header .= ',';
          $header .= $cofk->csv_field( 'recd_count' );
          $header .= ',';
          $header .= $cofk->csv_field( 'mentioned_count' );
        }
        if( $table == 'cofk_union_institution' ) {
          $header .= ',';
          $header .= $cofk->csv_field( 'document_count' );
        }

        $header .= CARRIAGE_RETURN . NEWLINE;
        fwrite( $handle, $header );
      }

      $firstcol = TRUE;
      foreach( $row as $colname => $colvalue ) {
        if( omit_col( $table, $colname )) { # Some extra columns have been added to the database, so we must omit
          continue;                         # these until the front end ingest procedure has been updated to match
        }
        if( ! $firstcol ) $data .= ',';
        $firstcol = FALSE;

        $is_numeric = FALSE;
        $is_date = FALSE;
        foreach( $colinfo as $crow ) {
          if( $crow[ 'column_name' ] == $colname ) {
            $is_numeric = $crow[ 'is_numeric' ];
            $is_date = $crow[ 'is_date' ];
            break;
          }
        }

        if( $colname == $export_id ) 
          $colvalue = $table . '-' . $colvalue;

        elseif( $table == 'cofk_union_relationship' ) {
          if( $colname == 'left_id_value' )
            $colvalue = $left_table_name . '-' . $colvalue;
          elseif( $colname == 'right_id_value' )
            $colvalue = $right_table_name . '-' . $colvalue;
          elseif( $colname == 'relationship_type' )
            $colvalue = 'cofk_union_relationship_type-' . $colvalue;
        }

        elseif( $table == 'cofk_union_image' && $colname == 'image_filename' ) {
          if( ! $cofk->string_starts_with( $colvalue, 'http' )) {
            $colvalue = IMAGE_URL_START . $colvalue;
          }
          if( $cofk->proj_link_is_broken( $colvalue )) {
            $skip_row = TRUE;
            $reason = 'Link to file ' . $colvalue . ' is broken.';
          }
          if( $cofk->string_starts_with( $colvalue, IMAGE_URL_START  )) { # Having checked link isn't broken,
                                                                          # now convert back to a relative path
                                                                          # so that the front end can manipulate it.

            # Except I think we may as well link to uploaded images in situ on back end.
            if( ! $cofk->string_contains_substring( $colvalue, '/uploaded/' )) {
              $colvalue = substr( $colvalue, strlen( IMAGE_URL_START ) - 1 );  # Keep trailing slash from IMAGE_URL_START
                                                                               # as front end wants something like
                                                                               # /lhwyd/xxx.jpg or /aubrey/yyy.jpg
            }
          }
        }

        elseif( $table == 'cofk_union_image' && $colname == 'thumbnail' ) {
          if( $cofk->string_starts_with( $colvalue, IMAGE_URL_START  )) {
            if( ! $cofk->string_contains_substring( $colvalue, '/uploaded/' )) {
              $colvalue = substr( $colvalue, strlen( IMAGE_URL_START ) - 1 );
            }
          }
        }

        elseif( $table == 'cofk_union_image' && $colname == 'display_order' ) {
          # Concatenate display order number and filename, in case there is a duplicate display order.
          $colvalue = str_pad( $colvalue, 4, '0', STR_PAD_LEFT ) . ' ' . $row[ 'image_filename' ];
        }

        elseif( $table == 'cofk_union_resource' && $colname == 'resource_url' ) {
          if( $cofk->string_starts_with( $colvalue, 'http' )) {
            if( $cofk->proj_link_is_broken( $colvalue )) {
              $skip_row = TRUE;
              $reason = 'Link to file ' . $colvalue . ' is broken.';
            }
          }
        }

        elseif( $table == 'cofk_union_resource' && $colname == 'resource_name' ) {
          if( $colvalue == 'Selden End card' ) {
            $colvalue = 'Bodleian card catalogue';
          }
        }

        elseif( $table == 'cofk_union_work' && $colname == 'accession_code' ) {
          if( $cofk->string_starts_with( $colvalue, 'Selden End EAD import' )) {
            $colvalue = str_replace( 'Selden End EAD import', 'Bodleian card catalogue bulk import', $colvalue );
          }
        }

        elseif( $table == 'cofk_union_manifestation' ) {
          if( $colname == 'manifestation_type' ) {
            $statement = 'select document_type_desc from cofk_lookup_document_type'
                       . " where document_type_code = '$colvalue'";
            $colvalue = $cofk->db_select_one_value( $statement );
          }
          elseif( $colname == 'manifestation_creation_calendar' ) {
            $colvalue = decode_calendar( $colvalue );
          }
        }

        elseif( $table == 'cofk_union_work' ) {
          if( $colname == 'original_calendar' ) {
            $colvalue = decode_calendar( $colvalue );
          }
          elseif( $colname == 'date_of_work_std_gregorian' ) {  # sometimes have added 11 days to a Dec 9999 date
            if( $cofk->string_starts_with( $colvalue, '10000-' )) {
              $colvalue = str_replace( '10000-', '9999-', $colvalue );
            }
          }
          elseif( $colname == 'original_catalogue' ) {
            if( trim( $colvalue ) == '' )
              $colvalue = 'unspecified';
            $colvalue = decode_catalogue( $colvalue, $cats );
          }
        }

        elseif( $table == 'cofk_union_person' ) {
          if( $colname == 'foaf_name' ) {  # add dates to name
            $colvalue = $person_obj->get_person_desc_from_id( $next_id, $using_integer_id = TRUE );
          }
        }

        if( $colname == 'creation_user' || $colname == 'change_user' )
          $colvalue = decode_user( $colvalue );

        #--------------------------------------------------
        # Write one field's worth of data into the CSV file
        #--------------------------------------------------
        $data .= $cofk->csv_field( $colvalue );
      }

      #----------------------------------------
      # Add extra data at the start of the line
      #----------------------------------------
      if( $table == 'cofk_union_person' or $table == 'cofk_union_location' ) {
        if( $table == 'cofk_union_person' ) {
          $id_name_in_view = 'person_id';
          $id_value_in_view = "'" . $row[ 'person_id' ] . "'";
        }
        else {
          $id_name_in_view = $id;
          $id_value_in_view = "'" . $next_id . "'";
        }

        $extra_view = $table . '_sent_view';

        $statement = "select count(*) as sent from $extra_view where $id_name_in_view = $id_value_in_view";
        $colvalue = $cofk->db_select_one_value( $statement );
        $data .= ',';
        $data .= $cofk->csv_field( $colvalue );

        $extra_view = $table . '_recd_view';

        $statement = "select count(*) as recd from $extra_view where $id_name_in_view = $id_value_in_view";
        $colvalue = $cofk->db_select_one_value( $statement );
        $data .= ',';
        $data .= $cofk->csv_field( $colvalue );

        $extra_view = $table . '_mentioned_view';

        $statement = "select count(*) as mentioned from $extra_view where $id_name_in_view = $id_value_in_view";
        $colvalue = $cofk->db_select_one_value( $statement );
        $data .= ',';
        $data .= $cofk->csv_field( $colvalue );
      }

      if( $table == 'cofk_union_institution' ) {
        $document_count = 0;
        echo 'Institution ID ' . $next_id . NEWLINE;

        $statement = "select left_id_value as mfn from cofk_union_relationship where relationship_type = 'stored_in'"
                   . " and left_table_name = 'cofk_union_manifestation' and right_table_name = 'cofk_union_institution'"
                   . " and right_id_value = '$next_id'";

        $docs = $cofk->db_select_into_array( $statement );        
        echo 'Raw total for number of documents is ' . strval( count( $docs )) . ' but this may need weeding out.';
        echo NEWLINE;

        # We had better exclude manifestations of works that are marked for deletion or hidden.
        foreach( $docs as $doc ) {
          $manifestation_id = $doc[ 'mfn' ];
          $invalid_work = '';

          $statement = 'select w.work_id from cofk_union_relationship r, cofk_union_work w'
                     . " where left_table_name = 'cofk_union_manifestation' "
                     . " and left_id_value = '$manifestation_id' "
                     . " and relationship_type = 'is_manifestation_of' "
                     . " and right_table_name = 'cofk_union_work' "
                     . " and right_id_value = w.work_id "
                     . " and ( w.work_to_be_deleted = 1 "
                     . " or w.original_catalogue not in (SELECT catalogue_code FROM cofk_lookup_catalogue WHERE publish_status = 1)"
                     . " or w.date_of_work_std = '1900-01-01' )"; # editors "hide" cards by setting date to this
          $invalid_work = $cofk->db_select_one_value( $statement );

          if( ! $invalid_work )
            $document_count++;
        }
        echo "Weeded total: $document_count" . NEWLINE;

        $data .= ',';
        $data .= $cofk->csv_field( $document_count );
      }

      $data .= CARRIAGE_RETURN . NEWLINE;
    }

    if( $skip_row ) {
      // MATTT: This outputs way to much information...
      //echo NEWLINE . 'Skipping row ' . $next_id . ' from table ' . $table  . ": $reason" . NEWLINE;
    }
    else
      fwrite( $handle, $data );

    $statement = "select min( $id ) from $table where $id > '$next_id' ";
    $statement .= " and $id <= '$last_id'";
    $next_id = $cofk->db_select_one_value( $statement );
  }

  #-------------------------------------------------------------------------

  fclose( $handle );
  $rows = NULL;
  $row = NULL;
  echo NEWLINE;

  echo "Finished $table up to ID '$last_id'." .  NEWLINE;
}
?>

