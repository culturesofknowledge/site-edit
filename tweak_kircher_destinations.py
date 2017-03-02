from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(True)

csv_rows = tweaker.get_csv_data( "resources/kircher/Kircher_DESTINATIONS_2017.2.27_forMW.csv" )
# csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["EMLO Letter ID Number"] )

	work = tweaker.get_work_from_iwork_id( csv_row["EMLO Letter ID Number"] )

	updater = {
		"destination_inferred" : csv_row["Destination inferred (0=No; 1=Yes)"]
	}
	tweaker.update_work( work["iwork_id"], updater )

	tweaker.create_relationship( "cofk_union_work", work["work_id"], "was_sent_to", "cofk_union_location", csv_row["EMLO Place ID"] )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )