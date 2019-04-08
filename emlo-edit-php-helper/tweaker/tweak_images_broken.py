from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker

from config import config
# import sys

# Setup
csv_file = "resources/SELECT_t___FROM_public_cofk_union_image_.csv"
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

		update = {}

		for error in errors :

			if 'thumbnail' in image and image['thumbnail'] and image['thumbnail'].startswith( error ) :
				old_thumbnail_url = image['thumbnail']
				update['thumbnail'] = old_thumbnail_url.replace( error, 'https://emlo-edit.bodleian.ox.ac.uk/' )

				# print( "New: " + update['thumbnail'] + " old: " + old_thumbnail_url)

			if 'licence_url' in image and image['licence_url'] and image['licence_url'].startswith( error ) :
				old_licence_url = image['licence_url']
				update['licence_url'] = old_licence_url.replace( error, 'https://emlo-edit.bodleian.ox.ac.uk/' )

				# print( "New: " + update['licence_url'] + " old: " + old_licence_url)

		if update :
			tweaker.update_image( image['image_id'], update )

		#else :
		#	print ("NOT CHANGED" )


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

		if countdown % 100 == 0:
			print( str(countdown) + " of " + str(count) )

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


