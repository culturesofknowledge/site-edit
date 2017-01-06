<?php

# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
# Used for popup selection screens where the user needs to choose or add organisation
#====================================================================================

class Popup_Organisation extends Popup_Person {

  #----------------------------------------------------------------------------------

  function Popup_Organisation( &$db_connection ) {

    #-----------------------------------------------------
    # Check we have got a valid connection to the database
    # and pick up parent CofK methods.
    #-----------------------------------------------------
    $this->Popup_Person( $db_connection );
  }
  #----------------------------------------------------------------------------------

  function app_popup_get_search_table() {  # from application entity

    if( $this->menu_method_name == 'select_by_first_letter_of_name' )
      return $this->proj_organisation_viewname_from_table();
    else
      return $this->proj_organisation_viewname_from_view();
  }
  #-----------------------------------------------------


  function gender_field() {

    html::hidden_field( 'gender', '' ); 
  }
  #-----------------------------------------------------


  function org_field() {

    html::hidden_field( 'is_organisation', 'Y' ); 
    $this->is_organisation = 'Y'; 
  }
  #-----------------------------------------------------
}
?>
