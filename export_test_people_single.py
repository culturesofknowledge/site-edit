
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Single Export"
#work_id = 'cofk_import_ead-ead_c01_id:000017739'  # 'cofk_import_hartlib-row_id:000003994'
#work_id = 'cofk_import_wallis_works-letter_id:000000784'  # with work-resource link
#work_id = 'cofk_import_aubrey-row_id:000000690'  # with person-resource link
#work_id = 'cofk_import_ead-ead_c01_id:000026011'  # With location-resource link

ipersonid = '914210'
command = "select person_id from cofk_union_person where iperson_id=" + ipersonid
person_ids = e.select_all( command )
person_id = person_ids[0]['person_id']

e.export_people( [person_id], folder )