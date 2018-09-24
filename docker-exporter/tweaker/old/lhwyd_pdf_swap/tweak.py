# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

import psycopg2
import psycopg2.extras

from tweaker import tweaker

csv_file = "lhwyd_pdf_swap/FINAL(v.2)_LhwydTranscriptions_WorkIDs_Descriptors_Files_ 2015.8.10-1.csv"
resource_url = "https://databank.ora.ox.ac.uk/emlo/datasets/lhwyd-transcripts/"

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

# delete PDF resource relationships
for csv_row in csv_rows:

	print( ".", end="" )
	sys.stdout.flush()

	# Get work
	work = tweaker.get_work_from_iwork_id( csv_row["iwork_id"] )

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
	work = tweaker.get_work_from_iwork_id( csv_row["iwork_id"] )

	# Create resource
	resource = {
		"name": csv_row["resource_name"],
		"url": resource_url + csv_row["resource_url"]
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


cur = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

for row in rows:
	#
	# Get work
	#

	data = row

	command = "SELECT * FROM cofk_union_work WHERE iwork_id=%s"
	command = cur.mogrify( command, (int(data["iwork_id"]),) )

	print( "* Selecting work:", command )

	cur.execute( command )
	work = cur.fetchone()


	#
	# INSERT relationship to work
	#
	command = "INSERT INTO cofk_union_relationship"
	command += " (left_table_name,left_id_value,relationship_type,right_table_name,right_id_value)"
	command += " VALUES "
	command += " ('cofk_union_manifestation',%s,'is_manifestation_of','cofk_union_work',%s)"

	command = cur.mogrify( command, ( manifestation_id, work['work_id'] ) )
	print( "* Relationship to work: ", command )

	if do_insert:
		cur.execute( command )
		#conn.commit()


	#
	# Create repository
	#
	if "repository_id" in data and data["repository_id"] != "":
		command = "INSERT INTO cofk_union_relationship"
		command += " (left_table_name,left_id_value,relationship_type,right_table_name,right_id_value)"
		command += " VALUES"
		command += " ('cofk_union_manifestation',%s,'stored_in','cofk_union_institution',%s)"

		command = cur.mogrify( command, ( manifestation_id, data['repository_id'] ) )

		print( "* Relationship to repository: ", command )

		if do_insert:
			cur.execute( command )
			#conn.commit()


	for comment_type in ["manifestation_notes","printed_edition_notes"] :


		if comment_type in data and data[comment_type] != "" :

			comment_id = '?' # Set a default so we can see something when not do_insert

			# Insert into comment
			command = "INSERT INTO cofk_union_comment"
			command += " (comment)"
			command += " VALUES"
			command += " (%s)"
			command += " returning comment_id"

			command = cur.mogrify( command, ( data[comment_type], ) )

			print ( "* Creating comment (for " + comment_type + ") : ", command )

			if do_insert:

				cur.execute( command )
				comment_id = cur.fetchone()[0]

			# link comment with manifestation
			command = "INSERT INTO cofk_union_relationship"
			command += " (left_table_name,left_id_value,relationship_type,right_table_name,right_id_value)"
			command += " VALUES"
			command += " ('cofk_union_comment',%s,'refers_to','cofk_union_manifestation',%s)"

			command = cur.mogrify( command, (comment_id, manifestation_id ) )

			print( "* Relationship to comment: ", command )

			if do_insert:
				cur.execute( command )

	if do_commit:

		conn.commit()
		pass