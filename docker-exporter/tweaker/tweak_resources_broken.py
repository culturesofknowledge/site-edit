from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config
import sys

# Setup
csv_file = "resources/broken-resources/emlo-url-check-all-errors.csv"
id_name = 'id'
skip_first_row = False

debugging = False
restrict = 50

errors = [
	#'https://databank.ora.ox.ac.uk/',
	'http://cofk2.bodleian.ox.ac.uk/',
	'https://cofk2.bodleian.ox.ac.uk/',
	'http://sers018.sers.ox.ac.uk/'
]


def row_process( tweaker, row ) :

	resource = tweaker.get_resource_from_resource_id( row[id_name] )

	if resource :
		for error in errors :
			if resource['resource_url'].startswith( error ) :
				old_url = resource['resource_url']
				new_url = None

				# http://sers018.sers.ox.ac.uk/
				if old_url.startswith( "http://sers018.sers.ox.ac.uk/history/cofk/union.php?iwork_id=" ) :
					new_url = old_url.replace( 'http://sers018.sers.ox.ac.uk/history/cofk/union.php?iwork_id=', 'http://emlo.bodleian.ox.ac.uk/w/' )
					new_url += '?previous=sers018-union'

				if old_url.startswith( "http://sers018.sers.ox.ac.uk/history/cofk/selden_end.php?iwork_id=" ):
					new_url = old_url.replace( 'http://sers018.sers.ox.ac.uk/history/cofk/selden_end.php?iwork_id=', 'http://emlo.bodleian.ox.ac.uk/w/' )
					new_url += '?previous=sers018-selden'

				# http://cofk2.bodleian.ox.ac.uk/
				# https://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=942085
				# http://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=100456

				if old_url.startswith( 'https://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=' ) :
					new_url = old_url.replace( 'https://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=', 'http://emlo.bodleian.ox.ac.uk/w/' )
					new_url += '?previous=cofk2-https'

				if old_url.startswith( 'http://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=' ) :
					new_url = old_url.replace( 'http://cofk2.bodleian.ox.ac.uk/interface/union.php?iwork_id=', 'http://emlo.bodleian.ox.ac.uk/w/' )
					new_url += '?previous=cofk2-http'

				if new_url :
					pass  # print( "New: " + new_url + " (Old: " + old_url + " )" )
				else :
					print ("NOT CHANGED: " + old_url )


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
		print( "Restricting rows to just", restrict)
		csv_rows = csv_rows[:restrict]

	count = countdown = len(csv_rows)
	for csv_row in csv_rows:

		if countdown == count and skip_first_row:
			continue

		#print( str(countdown) + " of " + str(count), ":", csv_row[id_name] )

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


