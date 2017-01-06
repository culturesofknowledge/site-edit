<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose or add publication.
# Returns ABBREVIATION if there is one.
#====================================================================================

class Popup_Publication_Abbrev extends Popup_Publication {

  #----------------------------------------------------------------------------------

  function Popup_Publication_Abbrev( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent methods from 'Project' class.
    #-----------------------------------------------------
    $this->Popup_Publication( $db_connection );

    $this->return_abbrev = TRUE;
  }
  #----------------------------------------------------------------------------------

  function clear() {

    parent::clear();

    $this->return_abbrev = TRUE;
  }
  #----------------------------------------------------------------------------------

  function app_popup_get_default_function_name( $calling_field, $prefix = NULL ) {

    if( $prefix ) 
      $prefix .= 'abbrev';  # differentiate from the 'full details' version
    else
      $prefix = 'abbrevAeolus';

    return parent::app_popup_get_default_function_name( $calling_field, $prefix ); # different prefix from other version
  }
  #----------------------------------------------------------------------------------
}
?>
