from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
csv_file = "resources/Morris_Batch2_BulkUPLOAD_2020.7.13-UPLOAD-withIDs.csv"
id_name = 'ID'
COL_GEN_NOTE="GENERAL NOTES ON PERSON"

skip_first_data_row = False

debugging = True
restrict = 0  # use 0 to restrict none.


def row_process( tweaker, row ) :

	pers = tweaker.get_person_from_iperson_id(row[id_name])
        print(pers[0])
	if pers :

                if row[COL_GEN_NOTE]:

        		note_id = tweaker.create_comment(row[COL_GEN_NOTE] )

		        tweaker.create_relationship_note_on_person( str(note_id), pers[0] )


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


