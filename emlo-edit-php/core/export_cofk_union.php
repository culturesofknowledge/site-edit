#!/usr/bin/php
<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Export Union database' );
define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

define( 'CONSTANT_DATABASE_TYPE', 'live' );
# define( 'CONSTANT_DATABASE_TYPE', 'test' );

defined( "DEBUGGING" ) or define( "DEBUGGING", FALSE );

require_once "common_components.php";
require_once "proj_components.php";
require_once "export_cofk_union_funcs.php";

# $php_file = $argv[0]; // file
$table = trim($argv[1]); // table

if( $table ) {

	$header_required    = trim($argv[2]); // output headers
	$next_id            = trim($argv[3]); // start id
	$last_id            = trim($argv[4]); // end id

	if( !$next_id ) die( 'Invalid first ID' );
	if( !$last_id ) die( 'Invalid last ID' );

	echo "Starting to process $table from $next_id to $last_id...";


	$db_connection = new DBQuery ( 'postgres' );
	$cofk = new Project( $db_connection );
	$db = new DBEntity( $db_connection );

	$users = array();

	$statement = 'select username, forename, surname from cofk_users';
	$us = $cofk->db_select_into_array( $statement );
	foreach( $us as $u ) {
		$username = NULL; $forename = NULL; $surname = NULL;
		extract( $u, EXTR_OVERWRITE );

		if( $username == 'cofksuper' ) {
			$person_name = 'SysAdmin';
		}
		else {
			$person_name = trim($forename) . ' ' . trim($surname);
		}
		$users[ "$username" ] = $person_name;
	}
	$users[ 'postgres' ] = 'SysAdmin';


	if( $table == 'cofk_union_person' ) {
		$person_obj = new Person($db_connection);
	}

	$colinfo = $db->db_list_columns( $table );

	$export_id = $id = str_replace( 'cofk_union_', '', $table ) . '_id';
	if( $table == 'cofk_union_work' ||  $table == 'cofk_union_person' ) {
		$id = 'i' . $id;
	}


	$where_clause = '';
	if( $table == 'cofk_union_work' ) {
		$where_clause = get_work_exclusion_clause();
		$reason = 'work marked for deletion or hidden';

		$cats = array();
		$statement = 'select * from cofk_lookup_catalogue';
		$catalogues = $cofk->db_select_into_array( $statement );
		foreach( $catalogues as $row ) {
			$catcode = $row[ 'catalogue_code' ];
			$cats[ "$catcode" ] = $row[ 'catalogue_name' ];
		}
		$cats[ 'unspecified' ] = 'No catalogue specified';
	}
	elseif( $table == 'cofk_union_image' ) {
		$where_clause = get_image_exclusion_clause();
		$reason = 'image flagged as non-displayable (e.g. non-Bodleian Lister image)';
	}


	$filename = $table . '.csv';
	if( $header_required ) {
		$handle = fopen($filename, 'w');
	}
	else {
		$handle = fopen($filename, 'a');
	}

	#-------------------------------------------------------------------------

	$i = 0;
	while( $next_id ) {

		$unpublished = FALSE;

		$statement = "select * from $table where $id = '$next_id'";

		if( $table == 'cofk_union_work' ) {
			$reason = 'work marked for deletion or hidden';
		}
		elseif( $table == 'cofk_union_manifestation' ) {
			$where_clause = get_manifestation_exclusion_clause( $next_id );
			$reason = 'work marked for deletion or hidden';
		}
		elseif( $table == 'cofk_union_image' ) {
			$reason = 'image flagged as non-displayable (e.g. non-Bodleian Lister image)';
		}
		else {
			$reason = '';
		}

		$statement_with_exclude = $statement . $where_clause;
		$result = $cofk->db_select_into_array( $statement_with_exclude );

		if( count( $result ) == 0 ) {
			// Not published (or possible missing ID for some reason)

			$unpublished = TRUE;

			// Get the record again... but without exclusion. (This could likely be done better with a clever sql statement...)
			$result = $cofk->db_select_into_array( $statement );

			if( $table == 'cofk_union_image' ) {
				// $statement = "select image_filename from cofk_union_image where image_id = $next_id";
				// $rejected_image = $cofk->db_select_one_value( $statement );
				// $reason = "Non-displayable image, e.g. non-Bodleian Lister image: $rejected_image" . NEWLINE;
				$reason = "Non-displayable image, e.g. non-Bodleian Lister image: " . $result['image_filename'] . NEWLINE;
			}

			if( !$reason ) {
				$reason = "No such ID: $next_id";
			}
		}

		# For images we now need to check that it is not an image of a work marked for deletion or hidden.
		# We don't need to re-check this for relationships because the 'manifestation' side of the check should deal with it.
		if( $table == 'cofk_union_image' ) {
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
				$unpublished = TRUE;
				$reason = 'work marked for deletion or hidden';
			}
		}

		# Similarly I suppose we should exclude comments that are no longer attached to a valid work
		if( $table == 'cofk_union_comment') {
			if( !$unpublished ) {
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

					$invalid_work = $cofk->db_select_one_value($statement);
					if ($invalid_work) {
						$unpublished = TRUE;
						$reason = 'work marked for deletion or hidden';
					}
				}
		}

		$row = NULL;
		if( true ) {
			$row = $result[ 0 ];

			if( $table == 'cofk_union_relationship' ) {
				$relationship_type = $left_table_name = $right_table_name = NULL;
				extract( $row, EXTR_OVERWRITE );

				if( !$unpublished ) {
					$unpublished = is_unknown_reltype($relationship_type,  # some types not set up in front end yet
							$left_table_name,
							$right_table_name);

					if ($unpublished) {
						$reason = "Unknown combination: $left_table_name $relationship_type $right_table_name" . NEWLINE;
					}
				}

				if( true ) {
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
						else {
							continue;
						}

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

						$selected = $cofk->db_select_one_value( $statement );

						if( $$rel_table == 'cofk_union_image' && $selected ) {
							if( ! $cofk->string_starts_with( $selected, 'http' )) {
								$selected = IMAGE_URL_START . $selected;
							}
							if( $cofk->proj_link_is_broken( $selected )) {
								$unpublished = TRUE;
								$reason = 'Link to file ' . $selected . ' is broken.';
								break;
							}
						}

						elseif( $$rel_table == 'cofk_union_resource' && $selected ) {
							if( $cofk->string_starts_with( $selected, 'http' )) {
								if( $cofk->proj_link_is_broken( $selected )) {
								  $unpublished = TRUE;
								  $reason = 'Link to file ' . $selected . ' is broken.';
								  break;
								}
							}
						}

						if( ! $selected ) {
							if( $$rel_table != 'cofk_union_resource' ) {
								# this could be a resource without a link, e.g. just page nos.
								$unpublished = TRUE;
							}

							break;
						}
					}
				}
			}
		}

		if( true ) {
			$i++;

			$header = '';
			$data = '';
			extract( $row, EXTR_OVERWRITE );

			#if( $i % 100 == 0 ) {
			#	echo $table . ' id:' . $row[$id] . ' count:' . $i . NEWLINE;
			#}

			if( $i == 1 && $header_required ) {  # write out header row

				$firstcol = TRUE;
				foreach( $row as $colname => $colvalue ) {
					 if( omit_col( $table, $colname )) { # Some extra columns have been added to the database, so we must omit
						 continue;                         # these until the front end ingest procedure has been updated to match
					 }

					if( ! $firstcol ) {
						$header .= ',';
					}
					$firstcol = FALSE;
					$header .= $cofk->csv_field( $colname );
				}

				if( $table == 'cofk_union_person' or $table == 'cofk_union_location' ) {
					$header .= ',' . $cofk->csv_field( 'sent_count' );
					$header .= ',' . $cofk->csv_field( 'recd_count' );
					$header .= ',' . $cofk->csv_field( 'mentioned_count' );
				}
				elseif( $table == 'cofk_union_institution' ) {
					$header .= ',' . $cofk->csv_field( 'document_count' );
				}

				$header .= ',' . $cofk->csv_field( "published" );

				$header .= CARRIAGE_RETURN . NEWLINE;
				fwrite( $handle, $header );
			}

			$firstcol = TRUE;
			foreach( $row as $colname => $colvalue ) {
				if( omit_col( $table, $colname )) { # Some extra columns have been added to the database, so we must omit
					continue;                         # these until the front end ingest procedure has been updated to match
				}

				if( ! $firstcol ) {
					$data .= ',';
				}
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

				if( $colname == $export_id ) {
					$colvalue = $table . '-' . $colvalue;
				}

				elseif( $table == 'cofk_union_relationship' ) {
					if( $colname == 'left_id_value' ) {
						$colvalue = $left_table_name . '-' . $colvalue;
					}
					elseif( $colname == 'right_id_value' ){
						$colvalue = $right_table_name . '-' . $colvalue;
					}
					elseif( $colname == 'relationship_type' ){
						$colvalue = 'cofk_union_relationship_type-' . $colvalue;
					}
				}

				elseif( $table == 'cofk_union_image' && $colname == 'image_filename' ) {
					if( ! $cofk->string_starts_with( $colvalue, 'http' )) {
						$colvalue = IMAGE_URL_START . $colvalue;
					}
					if( $cofk->proj_link_is_broken( $colvalue )) {
						$unpublished = TRUE;
						$reason = 'Link to file ' . $colvalue . ' is broken.';
					}
					if( $cofk->string_starts_with( $colvalue, IMAGE_URL_START  )) {
						# Having checked link isn't broken, now convert back to a relative path so that the front end can manipulate it.
						# Except I think we may as well link to uploaded images in situ on back end.
						if( ! $cofk->string_contains_substring( $colvalue, '/uploaded/' )) {
							$colvalue = substr( $colvalue, strlen( IMAGE_URL_START ) - 1 );
							# Keep trailing slash from IMAGE_URL_START as front end wants something like /lhwyd/xxx.jpg or /aubrey/yyy.jpg
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
							$unpublished = TRUE;
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
								." where document_type_code = '$colvalue'";
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
						if( trim( $colvalue ) == '' ) {
							$colvalue = 'unspecified';
						}
						$colvalue = decode_catalogue( $colvalue, $cats );
					}
				}

				elseif( $table == 'cofk_union_person' ) {
					if( $colname == 'foaf_name' ) {  # add dates to name
						$colvalue = $person_obj->get_person_desc_from_id( $next_id, $using_integer_id = TRUE );
					}
				}

				if( $colname == 'creation_user' || $colname == 'change_user' ) {
					$colvalue = decode_user($colvalue, $users);
				}

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

			elseif( $table == 'cofk_union_institution' ) {
				$document_count = 0;

				$statement = "select left_id_value as mfn from cofk_union_relationship where relationship_type = 'stored_in'"
						. " and left_table_name = 'cofk_union_manifestation' and right_table_name = 'cofk_union_institution'"
						. " and right_id_value = '$next_id'";

				$docs = $cofk->db_select_into_array( $statement );

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
							. " )"; # editors "hide" cards by setting date to this

					$invalid_work = $cofk->db_select_one_value( $statement );

					if( ! $invalid_work ) {
						$document_count++;
					}
				}

				$data .= ',';
				$data .= $cofk->csv_field( $document_count );
			}

			// published column.
			if( $unpublished ) {
				$data .= ',' . '0';
			}
			else {
				$data .= ',' . '1';
			}

			$data .= CARRIAGE_RETURN . NEWLINE;
		}

		// MATTT: This outputs way to much information...
		#if( $reason && $reason != 'work marked for deletion or hidden' ) {
		#	echo 'Skipping row ' . $next_id . ' from table ' . $table . ": $reason" . NEWLINE;
		#}

		fwrite( $handle, $data );

		$statement = "select min( $id ) from $table where $id > '$next_id' ";
		$statement .= " and $id <= '$last_id'";

		$next_id = $cofk->db_select_one_value( $statement );
	}

	#-------------------------------------------------------------------------

	fclose( $handle );
	$rows = $row = NULL;

	echo " Finished $i";
}


