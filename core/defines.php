<?php
/*
 * Sets database name for use in DBQuery
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/aeolus/php
 * Author: Sushila Burgess
 *
 */

if( CONSTANT_DATABASE_TYPE == 'live' ) {     # set by initial file called from Apache document root
  define('CONSTANT_DATABASE_NAME', 'ouls');
  define( 'DATABASE_HOST','localhost:5432' );
}
elseif( CONSTANT_DATABASE_TYPE == 'test' ){
  define( 'CONSTANT_DATABASE_NAME', 'test' );
  define( 'DATABASE_HOST','localhost:5432' );
}
elseif( CONSTANT_DATABASE_TYPE == 'dev' ) {
  define( 'CONSTANT_DATABASE_NAME', 'test' );
  define( 'DATABASE_HOST','localhost:5432' );
}
elseif( CONSTANT_DATABASE_TYPE == 'docker' ) {
  define( 'CONSTANT_DATABASE_NAME', 'ouls' );
  define( 'DATABASE_HOST','emlo-edit-postgres-with-emlo:5432' );
}
else
  die( 'Invalid input detected in defines.php' );


define( 'CONSTANT_MINIMAL_USER',  'minimal' );

define( 'SUPERVISOR_ROLE_CODE', 'super' );
define( 'SUPERVISOR_ROLE_ID',   -1      );

?>
