__author__ = 'matthew'

import json
import sys
from shutil import copyfile
import os.path
import time

import smtplib
from email.mime.text import MIMEText

from exporter.exporter import Exporter
from config import config

debug_on = False
postgres_connection = \
	"dbname='" + config["dbname"] + "'" + \
	" host='" + config["host"] + "' port='" + config["port"] + "'" + \
	" user='" + config["user"] + "' password='" + config["password"] + "'"

path_to_watch = "/usr/src/app/exporter_data"
output_folder = "/usr/src/app/exports/"  # end with '/'


def process_file( file_pos ):  # file_pos e.g.  "/some/where/23443554.3345.json"

	e = Exporter( postgres_connection, False, debug_on )

	process_file_folder, process_file_name = os.path.split( file_pos )  # "/some/where" and "23443554.3345.json"
	process_file_names = process_file_name.split(".")  # "23443554" "3345" "json"

	output_name = process_file_names[0] + "." + process_file_names[1]

	output_folder_name = "auto_" + output_name
	output_filename = "excel_" + output_name + ".xlsx"

	output_pos = output_folder + "/" + output_folder_name + "/" + output_filename

	j = json.load( open( file_pos, "r" ) )

	command = "select work_id from cofk_union_work where iwork_id in ("
	command += ",".join(j["iworkids"])
	command += ")"

	work_ids = e.select_all( command )
	work_ids = [id['work_id'] for id in work_ids]

	error = None
	try :
		e.export( work_ids, output_folder_name, excel_output=output_pos )

	except Exception as ex:
		error = "Error: " + str(ex)

	if error:
		msg = MIMEText(
			"Hi, I'm sorry but there was an error exporting your data. " +
			"Someone will have to investigate it." +
			error
		)
		print "Export Error: ", error
	else :
		msg = MIMEText(
			"Hi, your export is now complete. " +
			"You can download it here: " +
			"http://emlo-edit.bodleian.ox.ac.uk/exports/" +
			output_folder_name + "/" + output_filename
		)
		print "Exported successfully"

	msg["Subject"] = "Your export"
	email_from = "cok_bot@emlo-edit.bodleian.ox.ac.uk"
	email_to = j['email']
	msg["From"] = email_from
	msg["To"] = email_to

	s = smtplib.SMTP('smtp.ox.ac.uk')
	s.sendmail( email_from, email_to, msg.as_string())
	s.quit()


# file_pos = sys.argv[1]
# process_file( file_pos )


print "Starting export folder watch"

before = dict([(f, None) for f in os.listdir (path_to_watch)])
while 1:

	# print "Checking for folder differences..."

	after = dict([(f, None) for f in os.listdir( path_to_watch )])
	added = [f for f in after if not f in before]
	# removed = [f for f in before if not f in after]

	if added:

		for file_position in added :
			if file_position.endswith( ".json") :
				print "Processing " + file_position
				process_file( path_to_watch + "/" + file_position )
				time.sleep(10)

	# if removed: print "Removed: ", ", ".join (removed)

	before = after

	time.sleep(10)

