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

//error_reporting( E_ERROR & E_WARNING & E_PARSE & E_CORE_ERROR & E_CORE_WARNING & E_COMPILE_ERROR & E_COMPILE_WARNING & E_USER_ERROR & E_USER_WARNING & E_USER_NOTICE & E_RECOVERABLE_ERROR );
error_reporting(E_ALL & ~E_STRICT & ~E_WARNING & ~E_NOTICE);
//error_reporting(E_ALL & ~E_STRICT);
ini_set("display_errors", 1);

define( 'CONSTANT_DATABASE_TYPE', 'docker' );
$database_type_set = TRUE;

define( 'CONSTANT_SOURCEDIR', '/var/www/html/core/' );
$sourcedir_set = TRUE;

include "database_access.php";
if( !defined('SPECIAL_DATABASE_USERNAME') || !defined('SPECIAL_DATABASE_PASSWORD')) {
    #<?php
    # define( 'SPECIAL_DATABASE_USERNAME','<SOMETHING_HERE>');
    # define( 'SPECIAL_DATABASE_PASSWORD','<SOMETHING_HERE>');
    exit("Mat, you need to define the username and password for database access!");
}

require_once "union.php";
