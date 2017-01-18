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

define( 'CONSTANT_SOURCEDIR', '/var/www/html/core/' );
$sourcedir_set = TRUE;

echo "Database connection is: <b>" . strtoupper(CONSTANT_DATABASE_TYPE) . "</b>";

require_once "union.php";
