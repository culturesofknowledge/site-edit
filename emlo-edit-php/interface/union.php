<?php
/*******
get -r1.3 /home/burgess/scripts/sccs/cofk/php/s.union.php
********/
/*
 * Union catalogue menu
 * /home/burgess/scripts/sccs/cofk/php/s.union.php 1.3 2011/01/14 10:42:36
 * Author: Sushila Burgess
 *
 */

defined( "DEBUGGING" ) or define( "DEBUGGING", FALSE );

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Union Catalogue Editing Interface' );

define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

if( ! $database_type_set )
  define( 'CONSTANT_DATABASE_TYPE', 'live' );

if( ! $sourcedir_set )
  define( 'CONSTANT_SOURCEDIR', '/var/www/core/' );

$include_file = CONSTANT_SOURCEDIR . 'common_components.php';
require_once "$include_file";

$cofk = new Application_Entity;
$cofk->startup();

?>
