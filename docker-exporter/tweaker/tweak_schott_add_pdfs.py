from __future__ import print_function
import os.path
__author__ = 'sers0034'

from tweaker import tweaker
from config import config

print()
print( "Running " + os.path.basename( __file__) )
commit = raw_input("Commit changes to database (y/n): ")

do_commit = (commit == "y")
if do_commit:
	print("COMMITTING changes to database.")
else:
	print("NOT committing changes to database.")


postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

tweaker = tweaker.DatabaseTweaker( postgres_connection )
tweaker.set_debug(False)

work = tweaker.get_work_from_iwork_id("957636")

# Create resource
resource = {
	"name": "Transcription by Thomas E. Conlon and Hans-Joachim Vollrath",
	"url": "https://databank.ora.ox.ac.uk/emlo/datasets/Schott/Transcription_v2_SCHOTT_FrederickIII_1656.4.25.pdf"
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


# Create resource
resource = {
	"name": "Translation by Thomas E. Conlon and Hans-Joachim Vollrath",
	"url": "https://databank.ora.ox.ac.uk/emlo/datasets/Schott/Translation_V2_SCHOTT_FrederickIII_1656.4.25.pdf"
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


tweaker.print_audit()
tweaker.commit_changes(do_commit)
