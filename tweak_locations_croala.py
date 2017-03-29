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

csv_rows = tweaker.get_csv_data( "resources/croala/UPLOAD_CroALa_new_places_2017.3.29.csv" )
csv_rows = csv_rows[3:]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["TOWN, CITY, HAMLET, OR VILLAGE"] )

	place_id = tweaker.create_location(
		latitude=csv_row["LATITUDE"],
		longitude=csv_row["LONGITUDE"],
		location_synonyms=csv_row["SYNONYMS"].replace("; ", "\n"),

		element_1_eg_room=csv_row["ROOM"],
		element_2_eg_building=csv_row["BUILDING"],
		element_3_eg_parish=csv_row["STREET, PARISH, or DISTRICT"],
		element_4_eg_city=csv_row["TOWN, CITY, HAMLET, OR VILLAGE"],
		element_5_eg_county=csv_row["COUNTY, STATE, or PROVINCE"],
		element_6_eg_country=csv_row["COUNTRY"],
		element_7_eg_empire=csv_row["EMPIRE"],

		editors_note=csv_row["EDITORS' NOTES AND QUERIES"]
	)

	if csv_row["RELATED RESOURCE NAME"] :

		resource_id = tweaker.create_resource( csv_row["RELATED RESOURCE NAME"], csv_row["RELATED RESOURCE URL"] )
		tweaker.create_relationship( "cofk_union_location", place_id, "is_related_to", "cofk_union_resource", resource_id )


	if csv_row["GENERAL NOTES ON PLACE"] :

		comment_id = tweaker.create_comment( csv_row["GENERAL NOTES ON PLACE"] )
		tweaker.create_relationship( "cofk_union_comment", comment_id, "refers_to", "cofk_union_location", place_id )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

