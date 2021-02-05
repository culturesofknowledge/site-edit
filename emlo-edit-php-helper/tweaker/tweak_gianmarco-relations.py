from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
#csv_file = "resources/Morris_PeopMent_UPLOADED_2019.12.11_19_with_IDs_MISSING_DATA_2020.7.7b.csv"
csv_file = "resources/NewPeople_UPLOAD_GianmarcoNicoletti_5144-5571_2020.5.26-with-IDs.csv"
id_name = 'ID'
COL_GEN_NOTE = 'GENERAL NOTES ON PERSON'
COL_RELRES_NAME = 'RELATED RESOURCE NAME'
COL_RELRES_URL = 'RELATED RESOURCE URL '

skip_first_data_row = False

debugging = True
restrict = 0  # use 0 to restrict none.


def row_process( tweaker, row ) :

	#work = tweaker.get_work_from_iwork_id(row[id_name])
	pers = tweaker.get_person_from_iperson_id(row[id_name])
        print(pers[0])

        if not pers:
                return
        
        if row[COL_GEN_NOTE]:
      		note_id = tweaker.create_comment(row[COL_GEN_NOTE] )

	        tweaker.create_relationship_note_on_person( str(note_id), pers[0] )
                print("  GENERAL NOTE: " + str(note_id))


        # Add resource links
        if (row[COL_RELRES_URL]):
                res = row[COL_RELRES_URL].split(';')
                if row[COL_RELRES_NAME]:
                        resnames = row[COL_RELRES_NAME].split(';')
                else:
                        resnames = []
                # Not all entries have names
                resnames.extend([None] * len(res))
                for rind in range(len(res)):
                        res_id = tweaker.create_resource(resnames[rind],
                                                         res[rind])
                        #tweaker.create_relationship( "cofk_union_person", person_id, "is_related_to", "cofk_union_resource", res_id )
		        tweaker.create_relationship_person_resource( pers[0], res_id )
                        print("  RESOURCE: " + str(res_id) + " " + res[rind])



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


