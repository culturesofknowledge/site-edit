# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

from tweaker import tweaker

csv_file = "schott_transcripts_swap/Matt_Upload_Schott_to_end_1656_2015.12.6.csv"
resource_url = "https://databank.ora.ox.ac.uk/emlo/datasets/schott-vegelin-transcriptions/"

# Change to actually see something
do_commit = True  # COMMIT changes - there's no going back after this...

# I am one of those weird people who loves this movie - Hudson Hawk, Swinging On A Star https://youtu.be/D8KvM3vZo0w (A comment in my code...)

if do_commit:
	print("COMMITTING changes to database!!!")
else:
	print("NOT committing changes to database")


tweaker = tweaker.DatabaseTweaker( )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( csv_file )

# print( csv_rows )
# sys.exit()

# delete PDF resource relationships
for csv_row in csv_rows:

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work = tweaker.get_work_from_iwork_id( csv_row["Work ID"] )

	# Get relationships
	relationships = tweaker.get_relationships( work['work_id'], "cofk_union_work", "cofk_union_resource" )

	for relationship in relationships:

		resource = tweaker.get_resource_from_resource_id( relationship["id_value"] )

		if resource["resource_url"].find(".pdf") != -1 :

			tweaker.delete_resource_via_resource_id( resource["resource_id"] )
			tweaker.delete_relationship_via_relationship_id( relationship["relationship_id"] )


# Create new ones (may be more than one per work)
for csv_row in csv_rows:

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work = tweaker.get_work_from_iwork_id( csv_row["Work ID"] )

	# Create resource
	resource = {
		"name": csv_row["URL descriptor"],
		"url": resource_url + csv_row["NEW NAME OF FILE"]
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
