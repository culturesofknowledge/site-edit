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

csv_rows = tweaker.get_csv_data( "resources/croala/UPLOAD_CroALA_new_people_2017.3.29.csv" )
#csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

for csv_row in csv_rows:
	print( str(countdown) + " of " + str(count), ":", csv_row["PRIMARY NAME"] )

	person_id = tweaker.create_person_or_organisation(
		csv_row["PRIMARY NAME"],
		synonyms=csv_row["SYNONYMS"],
		aliases=csv_row["OCCUPATIONS, ROLES, and/or TITLES"],
		gender=csv_row["GENDER"],
		is_org=csv_row["IS ORGANISATION"],
		org_type=csv_row["ORGANIZATION TYPE"],
		birth_year=csv_row["BIRTH YEAR"],
		birth_inferred=csv_row["BIRTH YEAR INFERRED"],
		birth_uncertain=csv_row["BIRTH YEAR UNCERTAIN"],
		birth_approx=csv_row["BIRTH YEAR APPROX"],
		death_year=csv_row["DEATH YEAR"],
		death_inferred=csv_row["DEATH YEAR INFERRED"],
		death_uncertain=csv_row["DEATH YEAR UNCERTAIN"],
		death_approx=csv_row["DEATH YEAR APPROX"],
		flourished_year_1=csv_row["FLOURISHED YEAR 1"],
		flourished_year_2=csv_row["FLOURISHED YEAR 2"],
		flourished_range=csv_row["FLOURISHED IS RANGE"],
		editors_note=csv_row["EDITORS NOTES AND QUERIES"]
	)


	if csv_row["RELATED RESOURCE NAME 1"] :

		resource_id = tweaker.create_resource( csv_row["RELATED RESOURCE NAME 1"], csv_row["RELATED RESOURCE URL 1"] )
		tweaker.create_relationship( "cofk_union_person", person_id, "is_related_to", "cofk_union_resource", resource_id )

	if csv_row["RELATED RESOURCE NAME 2"] :

		resource_id = tweaker.create_resource( csv_row["RELATED RESOURCE NAME 2"], csv_row["RELATED RESOURCE URL 2"] )
		tweaker.create_relationship( "cofk_union_person", person_id, "is_related_to", "cofk_union_resource", resource_id )


	if csv_row["GENERAL NOTES ON PERSON"] :

		comment_id = tweaker.create_comment( csv_row["GENERAL NOTES ON PERSON"] )
		tweaker.create_relationship( "cofk_union_comment", comment_id, "refers_to", "cofk_union_person", person_id )


	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

