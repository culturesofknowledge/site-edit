from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
tweaker.set_debug(True)

csv_rows = tweaker.get_csv_data( "resources/wallis/Wallis_MATCHES_MW_2017.7.3.csv" )
#csv_rows = csv_rows[:3]
count = countdown = len(csv_rows)


def matches( emloIdMatches ) :
	"""
		a , b, c, d:
		a = b, c, d
		b = c, d
		c = d
	"""

	if len(emloIdMatches) < 2:
		return

	first = emloIdMatches[0]
	rest = emloIdMatches[1:]

	work_left = tweaker.get_work_from_iwork_id( first )
	for match in rest :
		# Get work
		work_right = tweaker.get_work_from_iwork_id( match )

		# Create relationship
		relationship = {
			"left_name" : "cofk_union_work",
			"left_id" : work_left["work_id"],
			"relationship_type" : "matches",
			"right_name" : "cofk_union_work",
			"right_id" : work_right["work_id"],
		}

		tweaker.create_relationship( **relationship )

	matches( rest )

for csv_row in csv_rows:
	print( str(count-countdown+1) + "/" + str(countdown) + " of " + str(count), ":", csv_row["Description"] )

	matches( [csv_row["Work ID"]] + csv_row["Match"].split(",") )

	countdown -= 1

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)

print( "Fini" )

