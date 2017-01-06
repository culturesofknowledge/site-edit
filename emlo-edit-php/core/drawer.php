<?php
/*
 * PHP class to provide dropdown list of drawers in Selden End card index.
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Drawer extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Drawer( &$db_connection ) { 

    $project = new Project( $db_connection );
    $table_name = $project->proj_drawer_tablename();
    $project = NULL;

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $table_name,
                         $id_column_name    = 'drawer_id', 
                         $desc_column_name  = 'drawer' ); 
  }
  #-----------------------------------------------------
}
?>
