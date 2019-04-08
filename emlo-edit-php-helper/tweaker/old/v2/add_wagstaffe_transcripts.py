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

csv_file = "resources/Wagstaffe PDFs/NEW_Bodleian Libraries Manuscript and Textual Editing Workshops_UPLOADS_2016.11.23.csv"
csv_rows = tweaker.get_csv_data( csv_file )

url_prefix = "https://databank.ora.ox.ac.uk/emlo/datasets/Wagstaffe/"

for csv_row in csv_rows :

	work = tweaker.get_work_from_iwork_id(csv_row["EMLO Work ID"])

	# Create resource
	resource = {
		"name": csv_row["URL descriptor"],
		"url": url_prefix + csv_row["NAME OF PDF for UPLOAD"]
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
