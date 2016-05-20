from __future__ import print_function

from tweaker import tweaker
from config import config


postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"
tweaker = tweaker.DatabaseTweaker( postgres_connection )


csv_file = "resources/roman_calendar/REUPLOAD_Corrected_Roman_dates_2016.5.17_final.csv"
csv_file = "resources/roman_calendar/DATES_reupload_no_dupes_2016.5.19.csv"
csv_rows = tweaker.get_csv_data( csv_file )


for csv_row in csv_rows :

	#work = tweaker.get_work_from_iwork_id( csv_row["EMLOID"] )

	max_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

	try:
		try:
			year = int( csv_row["YEAR"] )
		except ValueError:
			year = 1

		if year % 4 == 0 :
			max_month[1] = 29

		month = int( csv_row["MONTH"] )
		day = int( csv_row["DAY"] )

		if day > max_month[month-1] :
			print( csv_row["EMLOID"] )

	except ValueError :
		pass







