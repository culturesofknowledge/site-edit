from exporter.exporter import Exporter
from config import config

debug_on = True
folder = "Institutions All"

postgres_connection = "dbname='" + config["dbname"] + "'" \
					+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
					+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )


command = "select institution_id from cofk_union_institution LIMIT 100000"
institution_ids_response = e.select_all( command )
institution_ids = [str(pid['institution_id']) for pid in institution_ids_response]

#institution_ids = institution_ids[:50]
e.export_institutions( institution_ids, folder )