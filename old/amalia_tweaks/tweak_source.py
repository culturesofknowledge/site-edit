# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

from tweaker import tweaker

csv_file = "amalia_tweaks/Credit_changes_Solms-Braunfels,Amalia_catalogue_2015.12.17.csv"


# Change to actually see something
do_commit = True  # COMMIT changes - there's no going back after this...

if do_commit:
	print("COMMITTING changes to database!!!")
else:
	print("NOT committing changes to database")


tweaker = tweaker.DatabaseTweaker( )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( csv_file )

# print( csv_rows )

# Update original calendar
print( "Running", end="")
for csv_row in csv_rows:

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work = tweaker.get_work_from_iwork_id( csv_row["iwork_id"] )

	tweaker.update_work( work['iwork_id'], { "accession_code" : csv_row["source"] } )


print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)
