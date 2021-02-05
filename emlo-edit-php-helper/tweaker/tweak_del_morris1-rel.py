from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
#csv_file = "resources/DeWitt_NA_URLsUPLOAD_MW_2019.1.21.csv"
csv_file = "resources/Morris_PeopMent_UPLOADED_2019.12.11_19_with_IDs_MISSING_DATA_2020.7.7b.csv"
id_name = 'EMLO PERSON ID'
COL_GEN_NOTE = 'GENERAL NOTES'
COL_REL_NAME = 'RELATED RESOURCE NAME'
COL_REL_URL = 'RELATED RESOURCE URL'

skip_first_data_row = False

debugging = True
restrict = 0  # use 0 to restrict none.


def row_process( tweaker, row ) :
	pers = tweaker.get_person_from_iperson_id(row[id_name])
	if not pers:
		return

        pers_id = pers[0]
	print("PERSON ID:", pers_id)
	rels = tweaker.get_relationships(pers_id,"cofk_union_person") 

	for rel in rels :
                print("RELATIONSHIP:", rel['relationship_id'])
		if rel['table_name'] == 'cofk_union_resource':
			print("RESOURCE:",rel['id_value'])
			tweaker.delete_relationship_via_relationship_id(rel['relationship_id'])
			print("DELETED RELATIONSHIP")
			tweaker.delete_resource_via_resource_id(rel['id_value'])
			print("DELETED RESOURCE")

#	#work = tweaker.get_work_from_iwork_id(row[id_name])
#	pers = tweaker.get_person_from_iperson_id(row[id_name])
#        print(pers[0])
#
#        if not pers:
#                return
#        
#        if row[COL_GEN_NOTE]:
#
#      		note_id = tweaker.create_comment(row[COL_GEN_NOTE] )
#
#	        tweaker.create_relationship_note_on_person( str(note_id), pers[0] )
#
##### Fix split by semicolon *** COL_GEN_NOTE also wrong!!
#        if row[COL_REL_URL]:
#
#		resource_id = tweaker.create_resource( row[COL_GEN_NOTE], row[COL_REL_URL] )
#
#		tweaker.create_relationship_person_resource( pers[0], resource_id )
#

def main() :

	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	tweaker.set_debug( debugging )

	if debugging:
		print( "Debug ON so no commit" )
		commit = False
	else :
		commit = do_commit()

	csv_rows = tweaker.get_csv_data( csv_file )

	if debugging and restrict:
		print( "Restricting rows to just", restrict)
		csv_rows = csv_rows[:restrict]

	count = countdown = len(csv_rows)
	for csv_row in csv_rows:

		if countdown == count and skip_first_data_row:
			continue

		print( str(countdown) + " of " + str(count), ":", csv_row[id_name] )

		row_process( tweaker, csv_row )

		countdown -= 1

	print()

	tweaker.print_audit()
	tweaker.commit_changes(commit)

	print( "Fini" )


def do_commit() :

	commit = ( raw_input("Commit changes to database (y/n): ") == "y")
	if commit:
		print( "COMMITTING changes to database." )
	else:
		print( "NOT committing changes to database." )

	return commit


if __name__ == '__main__':

	print( "Starting...")
	main()
	print( "...Finished")


