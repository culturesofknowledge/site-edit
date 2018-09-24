from __future__ import print_function

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

# csv_file = "resources/roman_calendar/REUPLOAD_Corrected_Roman_dates_2016.5.17_final.csv"
csv_file = "resources/roman_calendar/DATES_reupload_no_dupes_2016.5.19.csv"
csv_rows = tweaker.get_csv_data( csv_file )


for csv_row in csv_rows :

	print( ".", end="" )

	errors = []

	if int(csv_row["EMLOID"]) not in errors :
		work = tweaker.get_work_from_iwork_id( csv_row["EMLOID"] )

		# Update work
		update = {
			"original_calendar" : csv_row["CALENDAR"],
			"date_of_work_std_year" : None,
			"date_of_work_std_month" : None,
			"date_of_work_std_day" : None,
		}

		try:
			year = int( csv_row["YEAR"] )
		except ValueError :
			year = 0

		try:
			month = int( csv_row["MONTH"] )
		except ValueError :
			month = 0

		try :
			day = int( csv_row["DAY"] )
		except:
			day = 0

		if year:
			update["date_of_work_std_year"] = year
		if month :
			update["date_of_work_std_month"] = month
		if day :
			update["date_of_work_std_day"] = day


		if year and month and day :

			if csv_row["CALENDAR"] == "JJ" or csv_row["CALENDAR"] == "JM" :
				greg_cal = tweaker.calendar_julian_to_calendar_gregorian( day, month, year )
			else :
				greg_cal = { "d" : day, "m" : month, "y" : year }

		else :
			if not year :
				year = 9999
			if not month :
				month = 12
			if not day :
				max_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
				day = max_month[month-1]

			greg_cal = { "d" : day, "m" : month, "y" : year }


		year = str(year)
		month = str(month)
		if len(month) == 1:
			month = "0" + month
		day = str(day)
		if len(day) == 1:
			day = "0" + day

		update["date_of_work_std"] = year + "-" + month + "-" + day


		year = str(greg_cal["y"])
		month = str(greg_cal["m"])
		if len(month) == 1:
			month = "0" + month
		day = str(greg_cal["d"])
		if len(day) == 1:
			day = "0" + day

		update["date_of_work_std_gregorian"] = year + "-" + month + "-" + day

		if csv_row["INFERRED"] == "1" :
			update["date_of_work_inferred"] = "1"
		if csv_row["UNCERTAIN"] == "1" :
			update["date_of_work_uncertain"] = "1"
		if csv_row["APPROX"] == "1" :
			update["date_of_work_approx"] = "1"

		if csv_row["EDITORNOTES"] :
			if work["editors_notes"]:
				update["editors_notes"] = work["editors_notes"] + "\n" + csv_row["EDITORNOTES"]
			else :
				update["editors_notes"] = csv_row["EDITORNOTES"]

		tweaker.update_work( csv_row["EMLOID"], update )


		if csv_row["DATENOTES"] :

			comment_id = tweaker.create_comment( csv_row["DATENOTES"] )

			tweaker.create_relationship('cofk_union_comment', comment_id, "refers_to_date", "cofk_union_work", work["work_id"])

print()

tweaker.print_audit()
tweaker.commit_changes(do_commit)