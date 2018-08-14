# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

"""
	Creating links between works

	python -m connect_amalia_works.tweak
"""

import sys

import psycopg2
import psycopg2.extras

from tweaker import tweaker

csv_file = "connect_amalia_works/Amalia_URLS_links_Working_filtered_2105.12.4.csv"

# Change to actually see something
do_commit = True  # COMMIT changes - there's no going back after this...

if do_commit:
	print("COMMITTING changes to database!!!")
else:
	print("NOT committing changes to database")


tweaker = tweaker.DatabaseTweaker( )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( csv_file )
# csv_rows = csv_rows[:5]

#print( csv_rows )
#sys.exit()

# Create new relationships
for csv_row in csv_rows:

	if csv_row["left_work_id"] in ['943703','943705','943707','943708']: # manually added
		continue

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work_left = tweaker.get_work_from_iwork_id( csv_row["left_work_id"] )
	work_right = tweaker.get_work_from_iwork_id( csv_row["right_work_id"] )

	# Create relationship
	relationship = {
		"left_name" : "cofk_union_work",
		"left_id" : work_left["work_id"],
		"relationship_type" : "matches",
		"right_name" : "cofk_union_work",
		"right_id" : work_right["work_id"],
	}
	tweaker.create_relationship( **relationship )


print("")

tweaker.print_audit(do_commit)
tweaker.commit_changes(do_commit)

sys.exit()
