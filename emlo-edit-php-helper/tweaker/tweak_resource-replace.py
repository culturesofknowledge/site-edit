from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

# Setup
csv_file = "resources/URLS_Agustin_replace_MW_2019.2.25a.csv"
id_name = "EMLO Letter ID Number"
skip_first_data_row = False

debugging = True
restrict = 5  # use 0 to restrict none.


def row_process( tweaker, row ) :

	work = tweaker.get_work_from_iwork_id( row[id_name] )

	if work:

		relationships = tweaker.get_relationships( work['work_id'], table_from="cofk_union_work", table_to="cofk_union_resource" )

		if len( relationships ) == 1:
			rel = relationships[0]
			resource = tweaker.get_resource_from_resource_id( rel['relationship_id'] )

			if resource :
				tweaker.update_resource( resource['resource_id'], {
					'resource_name' : row['URL descriptor'],
					'resource_url' : row['URL']
				})
		elif len( relationships) == 0:
			print( "ERROR: No resource found" )
			# TODO? Perhaps we should create one...?
		else :
			print( "ERROR: Too many resources," + str(len(relationships)) + ", don't know which to replace.")


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


