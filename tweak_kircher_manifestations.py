from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(False)

csv_rows = tweaker.get_csv_data( "resources/kircher/KIRCHER_shelfmarks_manifestation-notes_to_reUPLOAD_2017.2.2-1.csv" )
#csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)

print( "manifestaion_id,status,comment number,old comment(s),new comment")

for csv_row in csv_rows:

	#print( str(countdown) + " of " + str(count), ":", csv_row['manid'] )

	#tweaker.update_manifestation( csv_row['manid'], { "id_number_or_shelfmark" : csv_row["id_number_or_shelfmark"]  })

	comments = tweaker.get_relationships( csv_row['manid'], "cofk_union_manifestation", "cofk_union_comment")

	if len( comments ) > 0 :
		comment1 = tweaker.get_comment_from_comment_id( comments[0]['id_value'] )
	if len( comments ) > 1 :
		comment2 = tweaker.get_comment_from_comment_id( comments[0]['id_value'] )

	if len(comments) <= 1 :
		if csv_row['manifestation_notes'] != '' and len( comments ) == 1 :
			# update
			# tweaker.update_comment( comments[0]['id_value'], { "comment" : csv_row['manifestation_notes'] })
			print( csv_row["manid"] + ",replace," + str(len(comments) ) + ',"' + comment1['comment'] + '","' + csv_row['manifestation_notes'] + '"')
		elif csv_row['manifestation_notes'] != '' and len( comments ) == 0 :
			# create new comment
			print ( csv_row["manid"] + ",add," + str(len(comments) ) + ',"","' + csv_row['manifestation_notes'] + '"')
		elif csv_row['manifestation_notes'] == '' and len( comments ) == 1 :
			# delete comment
			print ( csv_row["manid"] + ",remove," + str(len(comments) ) + ',"' + comment1['comment'] + '","' + csv_row['manifestation_notes'] + '"' )
	else :
		print ( csv_row["manid"] + ",too many," + str(len(comments) ) + ',"' + comment1['comment'] + " && " + comment2['comment'] + '","' + csv_row['manifestation_notes'] + '"' )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

