<?php
/*
 * Upload from offline data collection tool
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'EMLO data collection tool' );

define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

if( ! $database_type_set ) 
  define( 'CONSTANT_DATABASE_TYPE', 'live' );

if( ! $sourcedir_set )
  define( 'CONSTANT_SOURCEDIR', '/var/apache2/cgi-bin/aeolus/aeolus2/cofk/' );

$include_file = CONSTANT_SOURCEDIR . 'common_components.php';
require_once "$include_file";

$include_file = CONSTANT_SOURCEDIR . 'collect_components.php';
require_once "$include_file";

$collection_tool = new Contribution;
$collection_tool->startup();

?>
