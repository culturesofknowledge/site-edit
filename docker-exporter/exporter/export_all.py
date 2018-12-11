__author__ = 'matthew'

from exporter.exporter import Exporter
from config import config

debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, debug_on, True )

command = "select work_id from cofk_union_work"

work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]

# TODO. Split up work...
e.export( work_ids[:20000], "all", parts_csvs=['work'] )
e.export( work_ids[20000:40000], "all2", parts_csvs=['work'] )
