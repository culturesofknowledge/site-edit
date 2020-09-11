from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config
import sys

# Column names used in import spreadsheet
COL_NAME="PRIMARY NAME TO BE ADDED TO EMLO"
COL_SYN="SYNONYMS"
COL_OCC="OCCUPATIONS, ROLES, and/or TITLES"
COL_GEND="GENDER"
COL_ISORG="IS ORGANIZATION"
COL_ORGTYPE="ORGANIZATION TYPE"
COL_YR="BIRTH YEAR "
COL_YR_INF="BIRTH YEAR INFERRED"
COL_YR_UNCERT="BIRTH YEAR UNCERTAIN"
COL_YR_APPROX="BIRTH YEAR APPROX"
COL_DYR="DEATH YEAR"
COL_DYR_INF="DEATH YEAR INFERRED"
COL_DYR_UNCERT="DEATH YEAR UNCERTAIN"
COL_DYR_APPROX="DEATH YEAR APPROX."
COL_FYR1="FLOURISHED YEAR 1"
COL_FYR2="FLOURISHED YEAR 2"
COL_FYR_RANGE="FLOURISHED IS RANGE "
COL_GEN_NOTE="GENERAL NOTES ON PERSON"
COL_ED_NOTE="EDITORS' NOTES AND QUERIES"
COL_RELRES="RELATED RESOURCE NAME"
COL_RELRES_URL="RELATED RESOURCE URL "


def main() :

	do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
	if do_commit:
		print( "COMMITTING changes to database." )
	else:
		print( "NOT committing changes to database." )


	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	# tweaker.set_debug(True)

	people = []
	places = []
	works = []

        csv_rows = tweaker.get_csv_data("resources/Morris_Batch2_BulkUPLOAD_2020.7.13-UPLOAD.csv")
	count = countdown = len(csv_rows)

	# Create people
	#
	print( "Create People" )
	skip_first_row = False
	for csv_row in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", csv_row[COL_NAME] )

		person_id = tweaker.create_person_or_organisation(
                        csv_row[COL_NAME],
                        csv_row[COL_SYN],
			csv_row[COL_OCC],
			csv_row[COL_GEND],
			is_org=csv_row[COL_ISORG],
			org_type=csv_row[COL_ORGTYPE],
			birth_year=csv_row[COL_YR],
			birth_month=None,
			birth_day=None,
			birth_inferred=csv_row[COL_YR_INF],
			birth_uncertain=csv_row[COL_YR_UNCERT],
			birth_approx=csv_row[COL_YR_APPROX],
			death_year=csv_row[COL_DYR],
			death_month=None,
			death_day=None,
			death_inferred=csv_row[COL_DYR_INF],
			death_uncertain=csv_row[COL_DYR_UNCERT],
			death_approx=csv_row[COL_DYR_APPROX],
			flourished_year_1=csv_row[COL_FYR1],
			flourished_year_2=csv_row[COL_FYR2],
			flourished_range=csv_row[COL_FYR_RANGE],
			editors_note=csv_row[COL_ED_NOTE]
		)

		people.append( {
			"id": person_id,
			"primary_name" : csv_row[COL_NAME]
		} )
                
                print("CREATED: "+ str(count-countdown+1) + " " + person_id + " "
                       + csv_row[COL_NAME])

                # Add resource links
                if (csv_row[COL_RELRES_URL]):
                        res = csv_row[COL_RELRES_URL].split(';')
                        if csv_row[COL_RELRES]:
                                resnames = csv_row[COL_RELRES].split(';')
                        else:
                                resnames = []
                        # Not all entries have names
                        resnames.extend([None] * len(res))
                        for rind in range(len(res)):
                                res_id = tweaker.create_resource(resnames[rind],
                                                                 res[rind])
                                tweaker.create_relationship( "cofk_union_person", person_id, "is_related_to", "cofk_union_resource", res_id )
                                print("  RESOURCE: " + csv_row[COL_NAME]
                                      + ", " + res[rind])

		countdown -= 1

	print()

	tweaker.print_audit()
	tweaker.commit_changes(do_commit)

	print( "Fini" )


def standardise_name( name) :
	name = name.replace("RSEL_", "" )  # remove if there
	name = "RSEL_" + name.strip()  # strip space and add back on
	return name


def get_work_id_from_csv_id( works, csv_id ) :

	for work in works:
		if work['csv_id'] == csv_id:
			return work['id']

	print( "Error csv_id " + csv_id + " not found!")
	return None


def get_person_id_from_primary_name( people, name ) :

	name_lower = name.lower()
	for person in people:
		if person['primary_name'] == name_lower:
			return person['id']

	print( "Error Name " + name_lower + " not found!")
	return None


def get_location_id_from_location_name( places, name ) :

	name_lower = name.lower()
	for place in places:
		if place['location_name'] == name_lower:
			return place['id']

	print( "Error Name " + name_lower + " not found!")
	return None




if __name__ == '__main__':

	print( "Starting main()")
	main()
	print( "Finished main()")


