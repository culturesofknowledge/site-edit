from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y" )
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( "resources/institutions/repo-location-data.csv" )
count = countdown = len(csv_rows)

for csv_row in csv_rows:

	print( str(countdown) + " of " + str(count), ":", csv_row["id"] )

	institution = tweaker.get_institution_from_institution_id( csv_row["id"] )

	if institution :
		update = {}

		if csv_row["address"]:
			update["address"] = csv_row["address"]

		if csv_row["lat"]:
			update["latitude"] = csv_row["lat"]
		if csv_row["long"]:
			update["longitude"] = csv_row["long"]

		if institution["institution_country"] == "England" :
			update["institution_country"] = "England, United Kingdom"


		if update :
			tweaker.update_institution( institution["institution_id"], update, True )
	else :
		print( csv_row["id"], " NOT FOUND" )

	count -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

