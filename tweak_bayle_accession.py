from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( "resources/bayle/Bayle_SOURCEofRECORD_to_correct_2017.3.25.csv" )
#csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["Work ID"] )

	updater = {
		"accession_code" : csv_row["Source of record"]
	}

	tweaker.update_work_from_iwork( csv_row["Work ID"], updater )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

