from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, debug_on )

command = "select work_id from cofk_union_work limit 20"

work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]

e.export( work_ids, "Small Number" )