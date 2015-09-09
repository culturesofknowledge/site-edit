<?php
# Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
# Author: Sushila Burgess
#====================================================================================
#------------------------------
# Work and person relationships
#------------------------------
define( 'RELTYPE_PERSON_CREATOR_OF_WORK', 'created' );               # creator on left, work on right
define( 'RELTYPE_PERSON_SENDER_OF_WORK', 'sent' );                   # sender on left, work on right
define( 'RELTYPE_PERSON_SIGNATORY_OF_WORK', 'signed' );              # signatory on left, work on right

# Extra author-type roles added for IMPAcT (could be seen as offensive to use the term 'creator')
define( 'RELTYPE_PERSON_AUTHOR_OF_WORK', 'was_author_of_work' );
define( 'RELTYPE_PERSON_COPYIST_OF_WORK', 'was_copyist_of_work' );
define( 'RELTYPE_PERSON_COMMENTATOR_ON_WORK', 'was_commentator_on_work' );
define( 'RELTYPE_PERSON_TRANSLATOR_OF_WORK', 'was_translator_of_work' );
define( 'RELTYPE_PERSON_GLOSSIST_OF_WORK', 'was_glossist_of_work' ); 

# New relationships for IMPAcT, 30 Nov 2011
define( 'RELTYPE_PERSON_WAS_DEDICATEE_OF_WORK', 'was_dedicatee_of' );
define( 'RELTYPE_PERSON_WAS_PATRON_OF_WORK', 'was_patron_of' );

define( 'RELTYPE_PERSON_MEMBER_OF_ORG', 'member_of' );               # member on left, organisation on right
define( 'RELTYPE_PERSON_MEMBER_OF_NISBA', 'member_of' );               # member on left, nisba on right (IMPAcT only)
define( 'RELTYPE_PERSON_MEMBER_OF_NATIONALITY', 'member_of' );               # member on left, nationality on right
define( 'RELTYPE_PERSON_MEMBER_OF_ROLE_CATEGORY', 'member_of' ); # member on left, role category on right

define( 'RELTYPE_PERSON_HAD_AFFILIATION_OF_TYPE', 'had_affiliation_of_type' ); # person on left, 
                                                                               # type of affiliation on right

define( 'RELTYPE_PERSON_WAS_IN_LOCATION', 'was_in_location' );       # person on left, place on right
define( 'RELTYPE_PERSON_WAS_BORN_IN_LOCATION', 'was_born_in_location' ); # person on left, place on right
define( 'RELTYPE_PERSON_DIED_AT_LOCATION', 'died_at_location' );         # person on left, place on right

define( 'RELTYPE_PERSON_SPOUSE_OF_PERSON', 'spouse_of' );  # people on both sides of symmetrical relationship
define( 'RELTYPE_PERSON_SIBLING_OF_PERSON', 'sibling_of' );
define( 'RELTYPE_PERSON_RELATIVE_OF_PERSON', 'relative_of' );
define( 'RELTYPE_PERSON_FRIEND_OF_PERSON', 'friend_of' );
define( 'RELTYPE_PERSON_ACQUAINTANCE_OF_PERSON', 'acquaintance_of' );
define( 'RELTYPE_PERSON_COLLEAGUE_OF_PERSON', 'colleague_of' );
define( 'RELTYPE_PERSON_COLLABORATED_WITH_PERSON', 'collaborated_with' );
define( 'RELTYPE_PERSON_WAS_A_BUSINESS_ASSOCIATE_OF_PERSON', 'was_a_business_associate_of' );
define( 'RELTYPE_PERSON_UNSPECIFIED_REL_TO_PERSON', 'unspecified_relationship_with' );

define( 'RELTYPE_PERSON_PARENT_OF_CHILD', 'parent_of' ); # people on both sides, 
define( 'RELTYPE_PERSON_TAUGHT_STUDENT', 'taught' );     # but relationship differs depending on direction
define( 'RELTYPE_PERSON_EMPLOYED_WORKER', 'employed' );

# New relationships for IMPAcT, 30 Nov 2011
define( 'RELTYPE_PERSON_WAS_PATRON_OF_PERSON', 'was_patron_of' );

define( 'RELTYPE_WORK_ADDRESSED_TO_PERSON', 'was_addressed_to' );    # work on left, addressee on right
define( 'RELTYPE_WORK_INTENDED_FOR_PERSON', 'intended_for' );        # work on left, intended recipient on right
define( 'RELTYPE_WORK_MENTIONS_PERSON', 'mentions' );                # work on left, person mentioned on right

define( 'RELTYPE_LATER_WORK_REPLIES_TO_EARLIER_WORK', 'is_reply_to' );  # original letter on left, reply on right
define( 'RELTYPE_LATER_WORK_MENTIONS_EARLIER_WORK', 'mentions_work' );  # mentioning work on left, mentioned on right
define( 'RELTYPE_LATER_WORK_COMMENTS_ON_EARLIER_WORK', 'is_commentary_on' );
define( 'RELTYPE_LATER_WORK_CONTINUES_EARLIER_WORK', 'is_continuation_of' );
define( 'RELTYPE_LATER_WORK_SUMMARISES_EARLIER_WORK', 'is_summary_of' );
define( 'RELTYPE_LATER_WORK_TRANSLATES_EARLIER_WORK', 'is_translation_of' );
define( 'RELTYPE_LATER_WORK_IS_GLOSS_ON_EARLIER_WORK', 'is_gloss_on' );
define( 'RELTYPE_LATER_WORK_IS_IJAZA_FOR_EARLIER_WORK', 'is_ijaza_for' );
define( 'RELTYPE_LATER_WORK_IS_VERSIFICATION_OF_EARLIER_WORK', 'is_versification_of' );

define( 'RELTYPE_WORK_DEALS_WITH_SUBJECT', 'deals_with' ); # work (left) deals with subject (right)

define( 'RELTYPE_WORK_SENT_FROM_PLACE', 'was_sent_from' );  # letter on left, place on right
define( 'RELTYPE_WORK_SENT_TO_PLACE',   'was_sent_to' );    # letter on left, place on right
define( 'RELTYPE_WORK_MENTIONS_PLACE', 'mentions_place' );  # work on left, place mentioned on right

define( 'RELTYPE_COMMENT_REFERS_TO_ENTITY', 'refers_to' );
define( 'RELTYPE_COMMENT_REFERS_TO_AUTHOR', 'refers_to_author' );
define( 'RELTYPE_COMMENT_REFERS_TO_ADDRESSEE', 'refers_to_addressee' );
define( 'RELTYPE_COMMENT_REFERS_TO_DATE', 'refers_to_date' );
define( 'RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COPYING', 'refers_to_place_of_copying' );
define( 'RELTYPE_COMMENT_REFERS_TO_TITLE', 'refers_to_title' );
define( 'RELTYPE_COMMENT_REFERS_TO_TYPE_OF_WORK', 'refers_to_type_of_work' );
define( 'RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COMPOSITION', 'refers_to_place_of_composition' );
define( 'RELTYPE_COMMENT_REFERS_TO_PEOPLE_MENTIONED_IN_WORK', 'refers_to_people_mentioned_in_work' ); # comment on left, 
                                                                                                      # *WORK* on right
define( 'RELTYPE_COMMENT_REFERS_TO_PLACES_MENTIONED_IN_WORK', 'refers_to_places_mentioned_in_work' ); # comment on left, 
                                                                                                      # *WORK* on right
define( 'RELTYPE_COMMENT_REFERS_TO_WORKS_MENTIONED_IN_WORK', 'refers_to_works_mentioned_in_work' );

# New relationships for IMPAcT, 30 Nov 2011
define( 'RELTYPE_COMMENT_REFERS_TO_TITLE_OF_WORK', 'refers_to_title' );
define( 'RELTYPE_COMMENT_REFERS_TO_PLACE_OF_COMPOSITION_OF_WORK', 'refers_to_place_of_composition' );
define( 'RELTYPE_COMMENT_REFERS_TO_INCIPIT_OF_WORK', 'refers_to_incipit' );
define( 'RELTYPE_COMMENT_REFERS_TO_EXCIPIT_OF_WORK', 'refers_to_excipit' );
define( 'RELTYPE_COMMENT_REFERS_TO_COLOPHON_OF_WORK', 'refers_to_colophon' );
define( 'RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_WORK', 'refers_to_dedicatees' );
define( 'RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_WORK', 'refers_to_patrons' );
define( 'RELTYPE_COMMENT_REFERS_TO_BIBLIOGRAPHY_OF_WORK', 'refers_to_bibliography' );
define( 'RELTYPE_COMMENT_REFERS_TO_BASIS_TEXTS_OF_WORK', 'refers_to_basis_texts_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_COMMENTARIES_ON_WORK', 'refers_to_commentaries_on' );

define( 'RELTYPE_COMMENT_REFERS_TO_COPYIST_OF_MANIF', 'refers_to_copyist' );
define( 'RELTYPE_COMMENT_REFERS_TO_CODEX_OF_MANIF', 'refers_to_codex' );
define( 'RELTYPE_COMMENT_REFERS_TO_CONTENTS_OF_MANIF', 'refers_to_contents' );
define( 'RELTYPE_COMMENT_REFERS_TO_TEACHER_OF_MANIF', 'refers_to_teacher_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_STUDENT_OF_MANIF', 'refers_to_student_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_ANNOTATOR_OF_MANIF', 'refers_to_annotator' );
define( 'RELTYPE_COMMENT_REFERS_TO_PLACE_STUDIED_OF_MANIF', 'refers_to_place_studied' );
define( 'RELTYPE_COMMENT_REFERS_TO_INCIPIT_OF_MANIF', 'refers_to_incipit' );
define( 'RELTYPE_COMMENT_REFERS_TO_EXCIPIT_OF_MANIF', 'refers_to_excipit' );
define( 'RELTYPE_COMMENT_REFERS_TO_DEDICATEES_OF_MANIF', 'refers_to_dedicatees' );
define( 'RELTYPE_COMMENT_REFERS_TO_PATRONS_OF_MANIF', 'refers_to_patrons' );
define( 'RELTYPE_COMMENT_REFERS_TO_FORMER_OWNERS_OF_MANIF', 'refers_to_former_owners' );
define( 'RELTYPE_COMMENT_REFERS_TO_ENDOWERS_OF_MANIF', 'refers_to_endowers' );
define( 'RELTYPE_COMMENT_REFERS_TO_ENDOWEES_OF_MANIF', 'refers_to_endowees' );

define( 'RELTYPE_COMMENT_REFERS_TO_LOCATIONS_OF_PERSON', 'refers_to_locations_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_RELATIONSHIPS_OF_PERSON', 'refers_to_relationships_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_AFFILIATIONS_OF_PERSON', 'refers_to_affiliations_of' );
define( 'RELTYPE_COMMENT_REFERS_TO_MEMBERS_OF_ORG', 'refers_to_members_of' );

define( 'RELTYPE_ENTITY_HAS_RESOURCE', 'is_related_to' );
define( 'RELTYPE_WORK_FINDING_AID_FOR_WORK', 'is_finding_aid_for' );

#----------------------------
# Manifestation relationships
#----------------------------
define( 'RELTYPE_MANIFESTATION_IS_OF_WORK', 'is_manifestation_of' ); # manifestation on left, work on right

define( 'RELTYPE_MANIF_STORED_IN_REPOS', 'stored_in' );                  # manifestation on left, repository on right
define( 'RELTYPE_INNER_MANIF_ENCLOSED_IN_OUTER_MANIF', 'enclosed_in' );  # manifestation on left  = enclosed
                                                                #                        on right = enclosing
define( 'RELTYPE_PERSON_OWNED_MANIF', 'formerly_owned' );           # owner on left, manifestation on right
define( 'RELTYPE_PERSON_HANDWROTE_MANIF', 'handwrote' );                 # scribe on left, manifestation on right
define( 'RELTYPE_PERSON_PARTLY_HANDWROTE_MANIF', 'partly_handwrote' );   # scribe on left, manifestation on right
define( 'RELTYPE_IMAGE_IS_OF_MANIF', 'image_of' );                  # image on left, manifestation on right

define( 'RELTYPE_MANIF_WAS_COPIED_AT_PLACE', 'copied_at_place' ); # manifestation on left, location on right

# New relationships for IMPAcT, 30 Nov 2011
define( 'RELTYPE_PERSON_WAS_DEDICATEE_OF_MANIF', 'was_dedicatee_of' );
define( 'RELTYPE_PERSON_WAS_PATRON_OF_MANIF', 'was_patron_of' );
define( 'RELTYPE_PERSON_WAS_ENDOWER_OF_MANIF', 'was_endower_of' );
define( 'RELTYPE_PERSON_WAS_ENDOWEE_OF_MANIF', 'was_endowee_of' );
define( 'RELTYPE_PERSON_ASKED_FOR_COPYING_OF_MANIF', 'asked_for_copying_of' );
define( 'RELTYPE_PERSON_TAUGHT_TEXT_OF_MANIF', 'taught_text' );
define( 'RELTYPE_PERSON_STUDIED_TEXT_OF_MANIF', 'studied_text' );
define( 'RELTYPE_PERSON_ANNOTATED_MANIF', 'annotated' );
define( 'RELTYPE_MANIF_WAS_STUDIED_IN_PLACE', 'was_studied_in_place' );

#--------------------------------------
# Other images, not just manifestations
#--------------------------------------
define( 'RELTYPE_IMAGE_IS_OF_ENTITY', 'image_of' ); # image on left, person, place, manifestation etc on right

#==================================================================================================================
# Names of 'relationship fieldsets': groups of fields that may contain checkboxes for multiple relationship types
#==================================================================================================================
#------
# Works
#------
define( 'FIELDSET_AUTHOR_SENDER', 'author_sender' );  # the Cultures of Knowledge version of author
define( 'FIELDSET_ADDRESSEE', 'addressee' );
define( 'FIELDSET_EARLIER_WORK_ANSWERED_BY_THIS', 'earlier_work_answered_by_this' );
define( 'FIELDSET_LATER_WORK_ANSWERING_THIS', 'later_work_answering_this' );

define( 'FIELDSET_AUTHOR', 'author' );                # the IMPAcT version of author
define( 'FIELDSET_PATRONS_OF_WORK', 'patrons_of_work' );
define( 'FIELDSET_DEDICATEES_OF_WORK', 'dedicatees_of_work' );
define( 'FIELDSET_WORK_DISCUSSED', 'work_discussed' );
define( 'FIELDSET_WORK_DISCUSSING', 'work_discussing' );

define( 'FIELDSET_ORIGIN', 'origin' );
define( 'FIELDSET_DESTINATION', 'destination' );

define( 'FIELDSET_PEOPLE_MENTIONED', 'people_mentioned' );
define( 'FIELDSET_PLACES_MENTIONED', 'places_mentioned' );
define( 'FIELDSET_WORKS_MENTIONED', 'works_mentioned' );


?>
