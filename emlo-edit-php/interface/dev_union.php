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

//header('Location: union.php');

error_reporting(E_ALL & ~E_STRICT & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);
//error_reporting(E_ALL & ~E_STRICT);
ini_set("display_errors", 1);
define( "DEBUGGING", TRUE );

//define( 'CONSTANT_DATABASE_TYPE', 'test' );
define( 'CONSTANT_SOURCEDIR', '/var/www/core/' );

echo "Database connection is: <b>" . strtoupper(CONSTANT_DATABASE_TYPE) . "</b>";

require_once "union.php";

