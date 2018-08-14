from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( "resources/euler/EULER_4_Related_Resources_add_2018.6.5.csv" )
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["EMLO Letter ID Number"] )

	work = tweaker.get_work_from_iwork_id( csv_row["EMLO Letter ID Number"] )

	resource_id = tweaker.create_resource( csv_row["Related resource descriptor"], csv_row["URL"] )

	tweaker.create_relationship( "cofk_union_work", work["work_id"], "is_related_to", "cofk_union_resource", resource_id )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

