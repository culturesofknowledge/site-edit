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

csv_rows = tweaker.get_csv_data( "resources/bayle_manifestations/UPLOAD_Bayle_8_print_manifestations_2016.12.22.csv" )
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["iwork_id"] )

	work = tweaker.get_work_from_iwork_id( csv_row["iwork_id"] )

	relationships = tweaker.get_relationships(work["work_id"], "cofk_union_work", "cofk_union_manifestation" )

	letters = "abcdefghijklmnopqrstuvwxyz"
	pos = len(relationships)

	man_id = "W" + csv_row["iwork_id"] + "-" + letters[pos]
	duplicate = tweaker.get_manifestation_from_manifestation_id( man_id )

	if duplicate :
		while duplicate :
			pos += 1
			man_id = "W" + csv_row["iwork_id"] + "-" + letters[pos]
			duplicate = tweaker.get_manifestation_from_manifestation_id( man_id )

			#print( "Clash. New:", man_id )

	man_id = tweaker.create_manifestation( man_id, csv_row["manifestation_type_p"], csv_row["printed_edition_details"] )

	tweaker.create_relationship( "cofk_union_manifestation", man_id, "is_manifestation_of", "cofk_union_work", work["work_id"])

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

