<?php
/*******
get -r1.4 /home/burgess/scripts/sccs/cofk/php/s.cardindex.php
********/
/*
 * Selden End card index menu
 * /home/burgess/scripts/sccs/cofk/php/s.cardindex.php 1.4 2011/02/10 12:54:55
 * Author: Sushila Burgess
 *
 */

define( 'CFG_PREFIX', 'cofk' );
define( 'CFG_SYSTEM_TITLE', 'Bodleian Card Catalogue Editing Interface' );

define( 'CULTURES_OF_KNOWLEDGE_MAIN_SITE', 'http://www.history.ox.ac.uk/cofk/' );

if( ! $database_type_set ) 
  define( 'CONSTANT_DATABASE_TYPE', 'live' );

if( ! $sourcedir_set )
  define( 'CONSTANT_SOURCEDIR', '/var/apache2/cgi-bin/aeolus/aeolus2/cofk/' );

$include_file = CONSTANT_SOURCEDIR . 'common_components.php';
require_once "$include_file";

$cofk = new Application_Entity;
$cofk->startup();

?>
