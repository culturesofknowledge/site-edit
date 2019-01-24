
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Single Export"
ipersonid = '914210'

command = "select person_id from cofk_union_person where iperson_id=" + ipersonid
person_ids = e.select_all( command )
person_id = person_ids[0]['person_id']

e.export_people( [person_id], folder )

