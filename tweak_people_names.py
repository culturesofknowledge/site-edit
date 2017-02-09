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

csv_rows = tweaker.get_csv_data( "resources/dewitt/select_iperson_id__skos_altlabel__person.csv" )
#csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["iperson_id"] )

	updater = {}

	if ";" in csv_row["skos_altlabel"] :
		updater["skos_altlabel"] = "; ".join( csv_row["skos_altlabel"].split(";") )

	if ";" in csv_row["person_aliases"] :
		updater["person_aliases"] = "; ".join( csv_row["person_aliases"].split(";") )

	tweaker.update_person( csv_row["iperson_id"], updater, True )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

