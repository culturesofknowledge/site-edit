from __future__ import print_function

import sys
import datetime
import time

from tweaker.tweaker import DatabaseTweaker
from config import config
import redis


print( str(datetime.datetime.today()) )

do_commit = ( input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )

"""
work id     = cofk_import_ead-ead_c01_id:000022136  ==>  cofk_union_work-cofk_import_ead-ead_c01_id:000022136
resource id = 100001  ==>  cofk_union_resource-100001

cofk_union_comment 54449
cofk_union_image 66961
cofk_union_institution 476
cofk_union_location 8219
cofk_union_manifestation 121881
cofk_union_person 26937
cofk_union_resource 88477
cofk_union_work 117791
"""

red = redis.Redis(db=1)

prefixes = [
	#"cofk_union_comment-",  # Done!
	#"cofk_union_image-",  # Done
	#"cofk_union_institution-",  # Done
	#"cofk_union_location-",  # Done
	#"cofk_union_manifestation-",  # Done
	#"cofk_union_person-",  # Done
	"cofk_union_resource-",
	#"cofk_union_work-"
]

for prefix in prefixes:

	selection = prefix+"*"
	print()
	print( "Selecting", selection )

	keys_string = []

	if prefix not in [
				"cofk_union_comment-"
				"cofk_union_image-",
				"cofk_union_institution-",
				"cofk_union_location-",
				"cofk_union_manifestation-",
				"cofk_union_person-"]:
		keys_string = red.keys( selection ).decode("utf-8")

	if len(keys_string) > 0 :

		begin = 0
		if prefix == "cofk_union_resource-" :

			table = 'cofk_union_resource'
			triggers = ['cofk_union_resource_trg_audit_del',
						'cofk_union_resource_trg_audit_ins',
						'cofk_union_resource_trg_audit_upd',
						'cofk_union_resource_trg_cascade03_del',
						'cofk_union_resource_trg_cascade03_ins',
						'cofk_union_resource_trg_cascade03_upd',
						'cofk_union_resource_trg_set_change_cols']

		keys = keys_string.split(" ")
		#keys = keys[:10]
		batch_count = 250

		key_count = len(keys)

		tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
		tweaker.set_debug(False)

		prefix_length = len(prefix)

		# Move through all keys in batch number.
		for batch in range( begin, key_count, batch_count ) :

			batch_keys = keys[batch:batch+batch_count]

			print( table, ":", batch, "of", key_count )

			tweaker.triggers_disable( table, triggers )

			# Move through this batches keys
			for batch_key in batch_keys :
				# print(batch_key)

				uuid = red.get( batch_key ).decode("utf-8")

				updater = {
					"uuid" : uuid
				}

				#if prefix == "cofk_union_comment-" :
				#	tweaker.update_comment( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_image-":
				#	tweaker.update_image( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_institution-":
				#	tweaker.update_institution( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_location-":
				#	tweaker.update_location( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_manifestation-":
				#	tweaker.update_manifestation( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_person-":
				#   tweaker.update_person( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_resource-":
				tweaker.update_resource( batch_key[prefix_length:], updater, anonymous=True )
				#elif prefix == "cofk_union_work-":
				#	tweaker.update_work( batch_key[prefix_length:], updater, anonymous=True )


			tweaker.triggers_enable( table, triggers )

			# tweaker.print_audit()
			tweaker.commit_changes(do_commit, quiet=True)

			time.sleep(1)

	else :
		print( "Nothing to do" )



# csv_rows = tweaker.get_csv_data( "resources/kircher/Kircher_DESTINATIONS_2017.2.27_forMW.csv" )
# csv_rows = csv_rows[:3]
# count = countdown = len(csv_rows)

# for csv_row in csv_rows:
# 	print( str(countdown) + " of " + str(count), ":", csv_row["EMLO Letter ID Number"] )

# 	countdown -= 1

print()

# tweaker.print_audit()
# tweaker.commit_changes(do_commit)


print( str(datetime.datetime.today()) )
print( "Fini" )