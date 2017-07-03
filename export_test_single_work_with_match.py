
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Single Export"

# iworkid = ['8540']  # Just one link
# iworkid = '600977'  # Two links!
iworkid = ['600977', '501026']

command = "select work_id from cofk_union_work where iwork_id in (" + ",".join(iworkid) + ")"
work_ids = e.select_all( command )
work_id = work_ids[0]['work_id']

e.export( [work_id], folder )