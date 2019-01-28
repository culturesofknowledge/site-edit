from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(True)

csv_rows = tweaker.get_csv_data( "resources/kircher/APUG_Gregorian_calendar_2017.1.31_MW.csv" )
# csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["Work (Letter) ID"] )

	# work = tweaker.get_work_from_iwork_id( csv_row["EMLO Letter ID Number"] )

	updater = {
		"original_calendar" : csv_row["Calendar to add"]
	}

	tweaker.update_work( csv_row["Work (Letter) ID"], updater )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )