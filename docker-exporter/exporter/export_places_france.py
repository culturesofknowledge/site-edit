
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
					+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
					+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Places France Export"

command = "select location_id from cofk_union_location where element_6_eg_country='France' LIMIT 100000 "
place_ids_response = e.select_all( command )
place_ids = [str(pid['location_id']) for pid in place_ids_response]
print len(place_ids)

e.export_places( place_ids, folder )

