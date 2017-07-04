
from exporter.exporter import Exporter
from config import config

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "Small match Export"

# iworkids = ['8540']  # Just one match
# iworkids = ['600977']  # Two matches!
# iworkids = ['600977', '501026']
iworkids = ['500644', '500647', '600893', '600893', '500648', '500650']

command = "select work_id from cofk_union_work where iwork_id in ('" + "','".join(iworkids) + "')"
work_ids = e.select_all( command )
work_ids = [res['work_id'] for res in work_ids]

e.export( work_ids, folder )