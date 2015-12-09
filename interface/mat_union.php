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
