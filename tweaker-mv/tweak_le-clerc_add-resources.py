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

csv_rows = tweaker.get_csv_data( "resources/le-clerc/LeClerc_Olschki_RelRes_ADD_2018.1.20.csv" )
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["Work ID"] )

	work = tweaker.get_work_from_iwork_id( csv_row["Work ID"] )

	resource_id = tweaker.create_resource( csv_row["Related Resources Descriptor"], csv_row["Related Resources URL"] )

	tweaker.create_relationship( "cofk_union_work", work["work_id"], "is_related_to", "cofk_union_resource", resource_id )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

