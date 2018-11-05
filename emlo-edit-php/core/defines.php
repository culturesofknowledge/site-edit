<?php
/*
 * Sets database name for use in DBQuery
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

if( CONSTANT_DATABASE_TYPE == 'live' ) {     # set by initial file called from Apache document root
	define( 'CONSTANT_DATABASE_NAME', 'ouls' );
	define( 'DATABASE_HOST','postgres' );
}
elseif( CONSTANT_DATABASE_TYPE == 'test' ) {     # set by initial file called from Apache document root
	define( 'CONSTANT_DATABASE_NAME', 'oulstestdata' );
	define( 'DATABASE_HOST','postgres' );
}
elseif( CONSTANT_DATABASE_TYPE == 'royalsociety' ) {     # set by initial file called from Apache document root
	define( 'CONSTANT_DATABASE_NAME', 'royalsociety' );
	define( 'DATABASE_HOST','postgres' );
}
else {
	die('Invalid input detected in defines.php');
}

define( 'CONSTANT_MINIMAL_USER',  'minimal' );
define( 'SUPERVISOR_ROLE_CODE', 'super' );
define( 'SUPERVISOR_ROLE_ID',   -1      );

