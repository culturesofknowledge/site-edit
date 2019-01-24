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


csv_file = "resources/boyle _credits/Credit_to_reupload_2016.10.31.csv"
csv_rows = tweaker.get_csv_data( csv_file )


# ERROR: cannot alter type of a column used by a view or rule
# Detail: rule _RETURN on view cofk_union_work_view depends on column "accession_code"


countdown = len(csv_rows)
for csv_row in csv_rows:
	print( countdown, ":", csv_row["EMLO Letter ID Number"] )

	work = tweaker.get_work_from_iwork_id( csv_row["EMLO Letter ID Number"] )

	field_updates = {
		"accession_code" : csv_row["Credit line to be inserted"]
	}

	tweaker.update_work( work["iwork_id"], field_updates=field_updates )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

