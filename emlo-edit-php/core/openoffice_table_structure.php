<?php
/*
 * PHP class to return structure of tables within the OpenOffice data collection tool
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */


class OpenOffice_Table_Structure {

  #-----------------------------------------------------

  function OpenOffice_Table_Structure() { 
  }
  #-----------------------------------------------------

  function addressee() { 
    return array (
      'addressee_id'       =>   'integer' ,
      'iperson_id'         =>   'integer' ,
      'iwork_id'           =>   'integer'
    );
  }
  #-----------------------------------------------------

  function author() { 
    return array (
      'author_id'          =>   'integer' ,
      'iperson_id'         =>   'integer' ,
      'iwork_id'           =>   'integer' 
    );
  }
  #-----------------------------------------------------

  function institution() { 
    return array (
      'institution_id'              =>   'integer',
      'institution_name'            =>   'text',
      'institution_city'            =>   'text',
      'institution_country'         =>   'text'
    );
  }
  #-----------------------------------------------------

  function location() { 
    return array (
      'location_id'            =>  'integer',
      'location_name'          =>  'text',
      'element_1_eg_room'      =>  'text',
      'element_2_eg_building'  =>  'text',
      'element_3_eg_parish'    =>  'text',
      'element_4_eg_city'      =>  'text',
      'element_5_eg_county'    =>  'text',
      'element_6_eg_country'   =>  'text',
      'element_7_eg_empire'    =>  'text',
      'notes_on_place'         =>  'text',
      'editors_notes'          =>  'text'
    );
  }
  #-----------------------------------------------------

  function manifestation() { 
    return array (
      'manifestation_id'        => 'integer',
      'iwork_id'                => 'integer',
      'manifestation_type'      => 'text',
      'repository_id'           => 'integer',
      'id_number_or_shelfmark'  => 'text',
      'printed_edition_details' => 'text',
      'manifestation_notes'     => 'text',
      'image_filenames'         => 'text'
    );
  }
  #-----------------------------------------------------

  function occupation_of_person() { 
    return array(
      'occupation_of_person_id' => 'integer',
      'iperson_id'              => 'integer',
      'occupation_id'           => 'integer'
    );
  }
  #-----------------------------------------------------

  function person_mentioned() { 
    return array(
      'mention_id' =>   'integer',
      'iperson_id' =>   'integer',
      'iwork_id'   =>   'integer'
    );
  }
  #-----------------------------------------------------

  function person() { 
    return array(
      'iperson_id'             =>   'integer' ,
      'primary_name'           =>   'text',
      'alternative_names'      =>   'text',
      'roles_or_titles'        =>   'text',
      'gender'                 =>   'text',
      'is_organisation'        =>   'text',
      'organisation_type'      =>   'integer',
      'date_of_birth_year'     =>   'integer',
      'date_of_birth_month'    =>   'integer',
      'date_of_birth_day'      =>   'integer',
      'date_of_birth_is_range' =>   'integer',
      'date_of_birth2_year'    =>   'integer',
      'date_of_birth2_month'   =>   'integer',
      'date_of_birth2_day'     =>   'integer',
      'date_of_birth_inferred' =>   'integer',
      'date_of_birth_uncertain'=>   'integer',
      'date_of_birth_approx'   =>   'integer',
      'date_of_death_year'     =>   'integer',
      'date_of_death_month'    =>   'integer',
      'date_of_death_day'      =>   'integer',
      'date_of_death_is_range' =>   'integer',
      'date_of_death2_year'    =>   'integer',
      'date_of_death2_month'   =>   'integer',
      'date_of_death2_day'     =>   'integer',
      'date_of_death_inferred' =>   'integer',
      'date_of_death_uncertain'=>   'integer',
      'date_of_death_approx'   =>   'integer',
      'flourished_year'        =>   'integer',
      'flourished_month'       =>   'integer',
      'flourished_day'         =>   'integer',
      'flourished_is_range'    =>   'integer',
      'flourished2_year'       =>   'integer',
      'flourished2_month'      =>   'integer',
      'flourished2_day'        =>   'integer',
      'notes_on_person'        =>   'text',
      'editors_notes'          =>   'text'
    );
  }
  #-----------------------------------------------------

  function place_mentioned() { 
    return array(
      'mention_id'  => 'integer',
      'location_id' => 'integer',
      'iwork_id'    => 'integer' 
    );
  }
  #-----------------------------------------------------

  function subject_of_work() { 
    return array(
      'subject_of_work_id' =>   'integer',
      'iwork_id'           =>   'integer',
      'subject_id'         =>   'integer'
    );
  }
  #-----------------------------------------------------

  function language_of_work() { 
    return array(
      'language_of_work_id' =>   'integer',
      'iwork_id'            =>   'integer',
      'language_code'       =>   'text'
    );
  }
  #-----------------------------------------------------

  function work() { 
    return array(
      'iwork_id'                  =>   'integer',
      'date_of_work_as_marked'    =>   'text',
      'original_calendar'         =>   'text',
      'date_of_work_std_year'     =>   'integer',
      'date_of_work_std_month'    =>   'integer',
      'date_of_work_std_day'      =>   'integer',
      'date_of_work2_std_year'    =>   'integer',
      'date_of_work2_std_month'   =>   'integer',
      'date_of_work2_std_day'     =>   'integer',
      'date_of_work_std_is_range' =>   'integer',
      'date_of_work_inferred'     =>   'integer',
      'date_of_work_uncertain'    =>   'integer',
      'date_of_work_approx'       =>   'integer',
      'notes_on_date_of_work'     =>   'text',
      'authors_as_marked'         =>   'text',
      'authors_inferred'          =>   'integer',
      'authors_uncertain'         =>   'integer',
      'notes_on_authors'          =>   'text',
      'addressees_as_marked'      =>   'text',
      'addressees_inferred'       =>   'integer',
      'addressees_uncertain'      =>   'integer',
      'notes_on_addressees'       =>   'text',
      'origin_id'                 =>   'integer',
      'origin_as_marked'          =>   'text',
      'origin_inferred'           =>   'integer',
      'origin_uncertain'          =>   'integer',
      'destination_id'            =>   'integer',
      'destination_as_marked'     =>   'text',
      'destination_inferred'      =>   'integer',
      'destination_uncertain'     =>   'integer',
      'abstract'                  =>   'text',
      'keywords'                  =>   'text',
      'language_of_work'          =>   'text',
      'incipit'                   =>   'text',
      'excipit'                   =>   'text',
      'notes_on_letter'           =>   'text',
      'notes_on_people_mentioned' =>   'text',
      'editors_notes'             =>   'text'
    );
  }
  #-----------------------------------------------------

  function contributor() { 
    return array(
      'contributor_id'    => 'integer',
      'contributor_name'  => 'text',
      'contributor_email' => 'text'
    );
  }
  #-----------------------------------------------------

  function work_resource() { 
    return array(
      resource_id        =>       'integer',
      iwork_id           =>       'integer',
      resource_name      =>       'text',
      resource_details   =>       'text',
      resource_url       =>       'text'
    );
  }
  #-----------------------------------------------------

  function person_resource() { 
    return array(
      resource_id         =>      'integer',
      iperson_id          =>      'integer',
      resource_name       =>      'text',
      resource_details    =>      'text',
      resource_url        =>      'text'
    );
  }
  #-----------------------------------------------------

  function location_resource() { 
    return array(
      resource_id         =>      'integer',
      location_id         =>      'integer',
      resource_name       =>      'text',
      resource_details    =>      'text',
      resource_url        =>      'text'
    );
  }
  #-----------------------------------------------------

  function institution_resource() { 
    return array(
      resource_id         =>      'integer',
      institution_id      =>      'integer',
      resource_name       =>      'text',
      resource_details    =>      'text',
      resource_url        =>      'text'
    );
  }
  #-----------------------------------------------------
}
?>
