from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config
import sys

csv_file = "<A CSV FILE>"
debugging = True

def main() :

	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	tweaker.set_debug( debugging )

	commit = do_commit()

	csv_rows = tweaker.get_csv_data( csv_file )

	if debugging:
		restrict = 5
		print( "Restricting rows to just", restrict)
		csv_rows = csv_rows[:restrict]

	count = countdown = len(csv_rows)

	skip_first_row = False
	for csv_row in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", csv_row["emloid"] )

		work = tweaker.get_work_from_iwork_id( csv_row["emloid"] )

		<do something>

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


