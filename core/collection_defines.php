<?php
/*
 * Collection-specific setup for Cultures of Knowledge and Impact databases
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

#--------------------------------------------------------------------------------------------------
# Within a project we may or may not have subsidiary collection information,
# e.g. in Cultures of Knowledge, we might be in the Union catalogue (work table 'cofk_union_work')
# or in a temporary import database (e.g. work table 'cofk_aubrey_work'), 
# whereas in IMPAcT we won't normally be in a sub-catalogue at all (work table 'impt_work').
# However in any project we might be in the 'import from spreadsheet' application.
# We can identify the sub-catalogue within the main project via the name of the data-entry app.
#--------------------------------------------------------------------------------------------------
$script_name = $_SERVER[ 'SCRIPT_NAME' ];

if( Application_Entity::string_contains_substring( $script_name, 'cardindex.php' )) {
  define( 'PROJ_COLLECTION_CODE', 'union' );  # Bodleian card index data (a.k.a. Selden End catalogue) is now in Union.
                                              # However, we'll still allow it to be edited separately via cardindex.php
  define( 'PROJ_SUB_COLLECTION', 'cardindex' );
  define( 'PROJ_COLLECTION_WORK_CLASS', 'selden_work' );
}

elseif( Application_Entity::string_contains_substring( $script_name, 'union.php' )) {
  define( 'PROJ_COLLECTION_CODE', 'union' );
  define( 'PROJ_SUB_COLLECTION', '' );
  define( 'PROJ_COLLECTION_WORK_CLASS', 'editable_work' );
}

elseif( Application_Entity::string_contains_substring( $script_name, 'collect.php' )) {
  define( 'PROJ_COLLECTION_CODE', 'collect' );
  define( 'PROJ_SUB_COLLECTION', '' );
  define( 'PROJ_COLLECTION_WORK_CLASS', '' ); # data collection tool uses different classes/methods
}

elseif( Application_Entity::string_contains_substring( $script_name, 'impact.php' )) {
  define( 'PROJ_COLLECTION_CODE', '' );
  define( 'PROJ_SUB_COLLECTION', '' );
  define( 'PROJ_COLLECTION_WORK_CLASS', 'impt_work' );
}

else
  die( 'Unknown catalogue.' . LINEBREAK );

?>
