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


csv_file = "resources/oldenburg_calendar/Oldenburg_calendar_correction_2016.5.17.csv"
csv_rows = tweaker.get_csv_data( csv_file )

# Update the work
for csv_row in csv_rows:
	print( ".", end="" )
	tweaker.update_work( csv_row["EMLOID"], { "original_calendar" : csv_row["Calendar"] } )


print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

