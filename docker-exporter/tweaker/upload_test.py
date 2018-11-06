from __future__ import print_function
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
# Create upload
upload_id = uploader.create_upload(upload_name, 1 )

# Create person existing
uploader.create_person_existing( upload_id, upload_name, 22859 )

# Create person new
person_count = 1
uploader.create_person_new( upload_id,
							upload_name,
							person_count,
							"Wilcox, Mat",
							gender='M',
							alternative_names='Matthew'
							)

work_count = 1
uploader.create_work( upload_id,
							upload_name,
							work_count,
							date_of_work_std_year='1656'
							)

print()
uploader.print_audit()
uploader.commit_changes(do_commit)

print( "Fini" )

