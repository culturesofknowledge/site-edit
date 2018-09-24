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


csv_file = "resources/bayle_manifestations/INGEST_Bayle_1-7_print_manifestation_2016.9.28.csv"
csv_rows = tweaker.get_csv_data( csv_file )

for csv_row in csv_rows:
	print( ".", end="" )

	work = tweaker.get_work_from_iwork_id( csv_row["iwork_id"] )

	relationships = tweaker.get_relationships(work["work_id"], "cofk_union_work", "cofk_union_manifestation" )

	letters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i' , 'j' , 'k' ,'l','m','n','p','q','r','s','t','u','v','w','x','y','z']
	pos = len(relationships)

	man_id = "W" + csv_row["iwork_id"] + "-" + letters[pos]
	duplicate = tweaker.get_manifestation_from_manifestation_id( man_id )

	while duplicate :
		pos += 1
		man_id = "W" + csv_row["iwork_id"] + "-" + letters[pos]
		duplicate = tweaker.get_manifestation_from_manifestation_id( man_id )
		print( man_id )

	man_id = tweaker.create_manifestation( man_id, csv_row["manifestation_type_p"], csv_row["printed_edition_details"] )

	tweaker.create_relationship( "cofk_union_manifestation", man_id, "is_manifestation_of", "cofk_union_work", work["work_id"])


print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

