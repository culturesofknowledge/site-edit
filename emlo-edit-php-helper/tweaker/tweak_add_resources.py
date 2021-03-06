from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config
import sys

# Setup
csv_file = 'resources/de-witt/DeWitt_NAcatURLs_ToUpload_2018.11.25_resources.csv'
id_name = 'Work (Letter) ID'
skip_first_row = False
debugging = True


def row_process( tweaker, row ) :

	work = tweaker.get_work_from_iwork_id( row[id_name] )

	resource_id = tweaker.create_resource( row["Related Resource Descriptor"], row["Related Resource URL"] )

	tweaker.create_relationship( "cofk_union_work", work["work_id"], "is_related_to", "cofk_union_resource", resource_id )


def main() :

	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	tweaker.set_debug( debugging )

	if debugging:
		print( "Debug ON so no commit" )
		commit = False
	else :
		commit = do_commit()

	csv_rows = tweaker.get_csv_data( csv_file )

	if debugging:
		restrict = 1
		print( "Restricting rows to just", restrict)
		csv_rows = csv_rows[:restrict]

	count = countdown = len(csv_rows)
	for csv_row in csv_rows:

		if countdown == count and skip_first_row:
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


