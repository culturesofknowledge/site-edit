
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Small number Export"
ipersonids = '914210,914211,914211,914213,914214'

command = "select person_id from cofk_union_person where iperson_id in (" + ipersonids + ")"
person_ids_response = e.select_all( command )
person_ids = [pid['person_id'] for pid in person_ids_response]

e.export_people( person_ids, folder )

