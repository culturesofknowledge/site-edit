__author__ = 'matthew'

import json
import sys
from shutil import copyfile
import os.path

import smtplib
from email.mime.text import MIMEText

from exporter.exporter import Exporter
from config import config

debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
                      + " host='" + config["host"] + "' port='" + config["port"] + "'" \
                      + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

file_pos = sys.argv[1]

file_folder, file_name = os.path.split( file_pos )
names = file_name.split(".")

output_name = names[0] + "." + names[1]
output_filename =  output_name + ".xlsx";
output_pos = file_folder + "/" + output_filename

j = json.load( open( file_pos, "r" ) )

command = "select work_id from cofk_union_work where iwork_id in ("
command += ",".join(j["iworkids"])
command += ")"

work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]

e.export( work_ids, "auto_" + output_name, excel_output=output_pos )

copyfile( output_pos, "/srv/www/exports/" + output_filename )

msg = MIMEText("Hi, your export is now complete. You can download it here: http://emlo-edit.bodleian.ox.ac.uk/exports/" + output_filename )
msg["Subject"] = "Your export"
email_from = "cok_bot@emlo-edit.bodleian.ox.ac.uk"
email_to = j['email']
msg["From"] = email_from
msg["To"] = email_to

s = smtplib.SMTP('localhost')
s.sendmail( email_from, email_to, msg.as_string())
s.quit()
