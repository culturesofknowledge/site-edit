<?php
/*******
get -r1.1 /home/burgess/scripts/sccs/cofk/php/s.dev_union.php
 ********/
/*
 * Union catalogue menu - DEVELOPMENT
 * /home/burgess/scripts/sccs/cofk/php/s.dev_union.php 1.1 2010/08/27 14:54:33
 * Author: Sushila Burgess
 *
 */

error_reporting(E_ALL & ~E_STRICT & ~E_WARNING);
ini_set("display_errors", 1);

define( 'CONSTANT_DATABASE_TYPE', 'docker' );
$database_type_set = TRUE;

define( 'CONSTANT_SOURCEDIR', '/var/www/html/' );
$sourcedir_set = TRUE;

include CONSTANT_SOURCEDIR . "database_access.php";
if( !defined('SPECIAL_DATABASE_USERNAME') || !defined('SPECIAL_DATABASE_PASSWORD')) {
    exit("Mat, you need to define the username for database access!");
}

require_once "union.php";
