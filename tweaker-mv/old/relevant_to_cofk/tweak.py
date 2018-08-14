# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

from tweaker import tweaker

csv_file = "relevant_to_cofk/switched.csv"

# Change to actually see something
do_commit = True  # COMMIT changes - there's no going back after this...

# I am one of those weird people who loves this movie - Hudson Hawk, Swinging On A Star https://youtu.be/D8KvM3vZo0w (A comment in my code...)

if do_commit:
	print("COMMITTING changes to database!!!")
else:
	print("NOT committing changes to database")

csv_rows = tweaker.DatabaseTweaker.get_csv_data( csv_file )

# print( csv_rows )
# sys.exit()

# Create new ones (may be more than one per work)
count = 0
tweaker = None
for csv_row in csv_rows:

	if count % 50 == 0 :
		print( ".", end="" )
		sys.stdout.flush()

	if count % 1000 == 0 :

		if tweaker :
			tweaker.print_audit()
			tweaker.commit_changes(do_commit)
			tweaker.close()

		tweaker = tweaker.DatabaseTweaker( )
		tweaker.set_debug(False)


	work = tweaker.update_work( csv_row["iwork_id"], { "relevant_to_cofk" : "Y" } )

	count += 1

print("")

tweaker.print_audit()
tweaker.commit_changes(do_commit)

sys.exit()
