from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker

from config import config
# import sys

# Setup
csv_file = "resources/images/select_image_id_from_cofk_union_image_wh.csv"
id_name = 'image_id'
skip_first_row = False

debugging = False
restrict = 50

errors = [
	#'https://databank.ora.ox.ac.uk/',
	'http://cofk2.bodleian.ox.ac.uk/',
	'https://cofk2.bodleian.ox.ac.uk/',
]


def row_process( tweaker, row ) :

	image = tweaker.get_image_from_image_id( row[id_name] )

	if image :
		for error in errors :
			if image['image_filename'].startswith( error ) :
				old_url = image['image_filename']
				new_url = ''

				if old_url.startswith( "http://cofk2.bodleian.ox.ac.uk/" ) :
					new_url = old_url.replace( 'http://cofk2.bodleian.ox.ac.uk/', 'https://emlo-edit.bodleian.ox.ac.uk/' )
					new_url += '?previous=cofk2-http'

				if new_url is not '' :
					# print( "New: " + new_url + " (Old: " + old_url + " )" )

					tweaker.update_image( image['image_id'], {
						'image_filename' : new_url
					} )

					pass
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


