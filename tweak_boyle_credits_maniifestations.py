from __future__ import print_function

__author__ = 'sers0034'

from tweaker import tweaker
from config import config

commit = raw_input("Commit changes to database (y/n): ")
do_commit = (commit == "y")
if do_commit:
	print("COMMITTING changes to database.")
else:
	print("NOT committing changes to database.")


postgres_connection = "dbname='" + config["dbname"] + "'" \
	+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
	+ " user='" + config["user"] + "' password='" + config["password"] + "'"

tweaker = tweaker.DatabaseTweaker( postgres_connection )
tweaker.set_debug(False)


csv_file = "resources/boyle _credits/Manifestations to reupload_2016.10.31.csv"
csv_rows = tweaker.get_csv_data( csv_file )


countdown = len(csv_rows)
for csv_row in csv_rows:
	print( countdown, ":", csv_row["Manifestation [Letter] ID"] )

	man = tweaker.get_manifestation_from_manifestation_id( csv_row["Manifestation [Letter] ID"] )

	field_updates = {
		"printed_edition_details" : csv_row["Printed copy details"]
	}

	tweaker.update_manifestation( man["manifestation_id"], field_updates=field_updates )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

