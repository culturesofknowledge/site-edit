from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( "resources/kircher/KIRCHER_shelfmarks_manifestation-notes_to_reUPLOAD_2017.2.2.csv" )
#csv_rows = csv_rows[:50]
count = countdown = len(csv_rows)

#print( "manifestaion_id,status,comment number,old comment(s),new comment")

for csv_row in csv_rows:

	print( str(countdown) + " of " + str(count), ":", csv_row['manid'] )

	#tweaker.update_manifestation( csv_row['manid'], { "id_number_or_shelfmark" : csv_row["id_number_or_shelfmark"]  })

	comment_relationships = tweaker.get_relationships(csv_row['manid'], "cofk_union_manifestation", "cofk_union_comment")

	# if len( comments ) > 0 :
	# 	comment1 = tweaker.get_comment_from_comment_id( comments[0]['id_value'] )
	# if len( comments ) > 1 :
	# 	comment2 = tweaker.get_comment_from_comment_id( comments[0]['id_value'] )
	#
	# if len(comments) <= 1 :
	# 	if csv_row['manifestation_notes'] != '' and len( comments ) == 1 :
	# 		# update
	# 		# tweaker.update_comment( comments[0]['id_value'], { "comment" : csv_row['manifestation_notes'] })
	# 		print( csv_row["manid"] + ",replace," + str(len(comments) ) + ',"' + comment1['comment'] + '","' + csv_row['manifestation_notes'] + '"')
	# 	elif csv_row['manifestation_notes'] != '' and len( comments ) == 0 :
	# 		# create new comment
	# 		print( csv_row["manid"] + ",add," + str(len(comments) ) + ',"","' + csv_row['manifestation_notes'] + '"')
	# 	elif csv_row['manifestation_notes'] == '' and len( comments ) == 1 :
	# 		# delete comment
	# 		print( csv_row["manid"] + ",remove," + str(len(comments) ) + ',"' + comment1['comment'] + '","' + csv_row['manifestation_notes'] + '"' )
	# else :
	# 	print( csv_row["manid"] + ",too many," + str(len(comments) ) + ',"' + comment1['comment'] + " && " + comment2['comment'] + '","' + csv_row['manifestation_notes'] + '"' )



	if len(comment_relationships) > 0 :
		tweaker.delete_relationship_via_relationship_id(comment_relationships[0]['relationship_id'])
		tweaker.delete_comment_via_comment_id(comment_relationships[0]['id_value'])

	if len(comment_relationships) > 1 :
		tweaker.delete_relationship_via_relationship_id(comment_relationships[1]['relationship_id'])
		tweaker.delete_comment_via_comment_id(comment_relationships[1]['id_value'])

	if csv_row['manifestation_notes'] != '' :

		comment_id = tweaker.create_comment( csv_row['manifestation_notes'] )

		tweaker.create_relationship( "cofk_union_comment", comment_id, "refers_to", "cofk_union_manifestation", csv_row['manid'] )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

