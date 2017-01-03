from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y" )
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug( True )

csv_rows = tweaker.get_csv_data( "resources/keplar/Kepler_vols.13-15_replace_URLs_toREUPLOAD_2016.12.21.csv" )
count = countdown = len( csv_rows )

for csv_row in csv_rows:
	print( str( countdown ) + " of " + str( count ), ":", csv_row["Resource ID"] )

	# resource = tweaker.get_resource_from_resource_id( csv_row["Resource ID"] )

	update = {
		'resource_name': csv_row['Resource Name'],
		'resource_url': csv_row['Resource URL']
	}

	tweaker.update_resource( csv_row["Resource ID"], update )


	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )
