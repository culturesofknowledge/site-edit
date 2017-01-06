<?php
/*
 * PHP class for displaying audit trail of Cultures of Knowledge or Impact databases
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'lookup_table.php';

class Audit_Trail_Column extends Lookup_Table {

  #------------
  # Properties 
  #------------

  #-----------------------------------------------------

  function Audit_Trail_Column( &$db_connection ) { 

    $project = new Project( $db_connection );

    $this->Lookup_Table( $db_connection, 
                         $lookup_table_name = $project->proj_audit_trail_column_viewname(), 
                         $id_column_name    = 'dummy_id', 
                         $desc_column_name  = 'changed_field' ); 
  }
  #-----------------------------------------------------
}
?>
