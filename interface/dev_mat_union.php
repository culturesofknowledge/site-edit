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

echo "DEVVVVVVVVVVVVVVV\n";

error_reporting(E_ALL);
ini_set("display_errors", 1);

define( 'CONSTANT_DATABASE_TYPE', 'dev' );
$database_type_set = TRUE;

define( 'CONSTANT_SOURCEDIR', '/var/www/html/' );
$sourcedir_set = TRUE;

require_once "union.php";

