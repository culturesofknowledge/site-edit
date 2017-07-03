
from exporter.exporter import Exporter
from config import config

from exporter.csv_unicode import get_csv_data

import csv

debug_on = True
postgres_connection = "dbname='" + config["dbname"] + "'" \
					+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
					+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

folder = "SSU Export"

data = csv.DictReader( open('resources/ssu/People_export_SSU_1.csv')  )
ipersonids = []

for dat in data :
	ipersonids.append( dat['iperson_id'] )

command = "select person_id from cofk_union_person where iperson_id in (" + ",".join(ipersonids) + ")"
person_ids_response = e.select_all( command )
person_ids = [pid['person_id'] for pid in person_ids_response]

e.export_people( person_ids, folder )

