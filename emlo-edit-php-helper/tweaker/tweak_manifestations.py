from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
csv_file = "resources/Locke_1_printedcopy_tidy_REPLACE_2019.1.26.csv"
id_name = 'Manifestation [Letter] ID'

skip_first_row = False
debugging = False
restrict = 0


def row_process( tweaker, row ) :

	manifestation = tweaker.get_manifestation_from_manifestation_id( row[id_name] )

	if manifestation :

		tweaker.update_manifestation( row[id_name], {
			"printed_edition_details" : row['Printed copy details to replace']
		} )


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


