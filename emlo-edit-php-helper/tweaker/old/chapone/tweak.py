# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

import psycopg2
import psycopg2.extras

from tweaker import tweaker

csv_file = "chapone/chapone.csv"
resource_url = "https://databank.ora.ox.ac.uk/emlo/datasets/Chapone/"  # must end with slash

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
# sys.exit()

# Create new ones (may be more than one per work)
for csv_row in csv_rows:

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work = tweaker.get_work_from_iwork_id( csv_row["WorkId"] )

	# Create resource
	resource = {
		"name": csv_row["descriptor"],
		"url": resource_url + csv_row["FILE"]
	}
	resource_id = tweaker.create_resource( **resource )

	# Create relationship
	relationship = {
		"left_name" : "cofk_union_work",
		"left_id" : work["work_id"],
		"relationship_type" : "is_related_to",
		"right_name" : "cofk_union_resource",
		"right_id" : resource_id
	}
	tweaker.create_relationship( **relationship )


print("")

tweaker.print_audit()
tweaker.commit_changes(do_commit)
sys.exit()
