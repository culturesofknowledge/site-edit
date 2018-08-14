from __future__ import print_function

__author__ = 'sers0034'

from tweaker import tweaker
from config import config

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


csv_file = "resources/beale_images/Beale_Image_jpeg_UPLOAD_2016.5.18.csv"
csv_rows = tweaker.get_csv_data( csv_file )  # [0:1]

image_url_base = 'https://databank.ora.ox.ac.uk/emlo/datasets/beale-images-2/images/'
thumbnails_url_base = 'https://databank.ora.ox.ac.uk/emlo/datasets/beale-images-2/thumbnails/'

# Update the work
for csv_row in csv_rows:
	print( ".", end="" )

	work = tweaker.get_work_from_iwork_id( csv_row["workid"] )

	manifestations_relations = tweaker.get_relationships( work["work_id"], "cofk_union_work", "cofk_union_manifestation" )

	if len(manifestations_relations) > 1 :
		raise Exception( "Too many manifestations for " + work["work_id"] )

	else :

		manifestation_id = manifestations_relations[0]["id_value"]

		image_url = image_url_base + csv_row["filename"] + ".jpg"
		thumbnails_url = thumbnails_url_base + "thumb." + csv_row["filename"] + ".jpg"

		# create an image.
		image_id = tweaker.create_image( image_url, int(csv_row["filename"][-1]) + 1, csv_row["credits"], thumbnail=thumbnails_url )

		# create relation
		tweaker.create_relationship( "cofk_union_image", image_id, "image_of", "cofk_union_manifestation", manifestation_id )


print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)