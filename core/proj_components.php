<?php
/*
 * PHP include files for Cultures of Knowledge database 
 * Subversion repository: https://damssupport.bodleian.ox.ac.uk/svn/cok/trunk/backend/php
 * Author: Sushila Burgess
 *
 */

require_once 'collection_defines.php';  # sets up collection-specific constants
require_once 'relationship_type_defines.php';

require_once 'project.php';

require_once 'work.php';
require_once 'editable_work.php';
require_once 'popup_work.php';

require_once 'relationship.php';

require_once 'person.php';
require_once 'popup_person.php';
require_once 'popup_organisation.php';

require_once 'location.php';
require_once 'popup_location.php';

require_once 'date_entity.php';
require_once 'language.php';

require_once 'manifestation.php';
require_once 'popup_manifestation.php';

require_once 'publication.php';
require_once 'popup_publication.php';
require_once 'popup_publication_abbrev.php';

require_once 'comment.php';
require_once 'resource.php';
require_once 'speed_entry_text.php';

require_once 'repository.php';
require_once 'image.php';

require_once 'document_type.php';
require_once 'drawer.php';
require_once 'catalogue.php';
require_once 'subject.php';
require_once 'role_category.php';
require_once 'org_type.php';
require_once 'org_subtype.php';
require_once 'nationality.php';

require_once 'audit_trail.php';
require_once 'audit_trail_table.php';
require_once 'audit_trail_column.php';

require_once 'html.php';
require_once 'menu.php';

#------------------------------------------
# Interface to offline data collection tool
#------------------------------------------
require_once 'collect_components.php';

#--------------------------------
# Branches for different projects
#--------------------------------
$system_prefix = Application_Entity::get_system_prefix();

if( $system_prefix == CULTURES_OF_KNOWLEDGE_SYS_PREFIX ) {
  require_once 'selden_work.php';
}
elseif( $system_prefix == IMPACT_SYS_PREFIX ) {
  require_once 'impt_components.php';
}

?>
