
import os.path

from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, debug_on )

folder = "Single Export"
e.export( ['cofk_import_hartlib-row_id:000003994'], folder )