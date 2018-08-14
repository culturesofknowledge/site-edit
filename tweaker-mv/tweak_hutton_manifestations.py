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


csv_file = "resources/hutton_manifestations/Hutton_manifestation_changes_toMW_2016.11.24a.csv"
csv_rows = tweaker.get_csv_data( csv_file )

for csv_row in csv_rows:
	print( ".", end="" )

	manifestation = tweaker.get_manifestation_from_manifestation_id( csv_row["Manifestation [Letter] ID"])

	update = {
		'printed_edition_details': csv_row['Printed copy details'],
		'id_number_or_shelfmark': csv_row['Shelfmark and pagination']
	}

	tweaker.update_manifestation( manifestation["manifestation_id"], update )

	if csv_row['Notes on manifestation'] != '' :
		relationships = tweaker.get_relationships( manifestation["manifestation_id"], "cofk_union_manifestation", "cofk_union_comment")

		if len(relationships) == 0 :
			print( "MISSING A NOTE", csv_row["iwork_id"], csv_row["Manifestation [Letter] ID"] )

		elif len(relationships) > 1 :
			print(  csv_row["iwork_id"], csv_row["Manifestation [Letter] ID"], "Too many" )

		else :
			comment = tweaker.get_comment_from_comment_id( relationships[0]["id_value"])

			update = {
				"comment" : csv_row["Notes on manifestation"]
			}
			tweaker.update_comment( comment["comment_id"], update )



print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

