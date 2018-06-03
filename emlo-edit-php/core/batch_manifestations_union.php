<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Make Manifestation Batches: Union database' );
define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

define( 'CONSTANT_DATABASE_TYPE', 'live' );
define( 'BATCH_SIZE', 5000 );

require_once "common_components.php";
require_once "proj_components.php";

#-------------------------------------------------------------------------

$db_connection = new DBQuery ( 'postgres' );
$cofk = new Project( $db_connection );

$filename = 'manifestation_batches.txt';
$handle = fopen( $filename, 'w' );

#-------------------------------------------------------------------------

$statement = 'select min( manifestation_id ) from cofk_union_manifestation';
$first_id = $cofk->db_select_one_value( $statement );
$next_id = $first_id;
$last_id = $first_id;

$i = 0;
while( $next_id ) {
  $statement = 'select min( manifestation_id ) from cofk_union_manifestation'
             . " where manifestation_id > '$next_id'";
  $next_id = $cofk->db_select_one_value( $statement );
  if( $next_id ) {
    $i++;
    if( $i % BATCH_SIZE == 0 ) {
      $data = "$first_id $last_id" . NEWLINE;
      #echo $data;
      fwrite( $handle, $data );
      $first_id = $next_id;
    }
    $last_id = $next_id;
  }
}
$data = "$first_id $last_id" . NEWLINE;
#echo $data;
fwrite( $handle, $data );

fclose( $handle );
echo "Finished batching up $i manifestations" .  NEWLINE;

