from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from tweaker.uploader import DatabaseTweakerCollect
from config import config

do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
if do_commit:
	print( "COMMITTING changes to database." )
else:
	print( "NOT committing changes to database." )


uploader = DatabaseTweakerCollect.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
uploader.set_debug(True)

upload_name = "Mats Upload"
person_existing_id = 22859
location_existing_id = 2749

# Start and create upload
uploader.start_upload( upload_name, 1 )

# Create person existing
uploader.create_person_existing( person_existing_id )
# Create person new
person_id = uploader.create_person_new( "Wilcox, Mat",
							gender='M',
							alternative_names='Matthew'
							)

# create existing location
uploader.create_location_existing( location_existing_id )
# create new location
location_id = uploader.create_location_new(
	element_6_eg_country='United Kingdom',
	element_4_eg_city="Oxford" )

# Create a work
work_id = uploader.create_work( date_of_work_std_year='1656',
	destination_id=location_existing_id,
	origin_id=location_id )

# set author
uploader.link_author( person_id, work_id )
# set addressee
uploader.link_addressee( person_existing_id, work_id )

# set origin
uploader.link_origin( location_id, work_id )
# set destination
uploader.link_destination( location_existing_id, work_id )

print()
uploader.print_audit()
uploader.commit_changes(do_commit)

print( "Fini" )

