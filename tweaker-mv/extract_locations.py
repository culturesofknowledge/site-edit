from __future__ import print_function

__author__ = 'sers0034'

from tweaker import tweaker
from config import config


postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"
tweaker = tweaker.DatabaseTweaker( postgres_connection )
tweaker.set_debug(False)


csv_file = "resources/locations/VossiusGJ_places_coordinates.csv"
csv_rows = tweaker.get_csv_data( csv_file )  # [0:1]


# Update the work
for csv_row in csv_rows:

	location = tweaker.get_location_from_location_id( csv_row["EMLO ID"] )

	print( str(location["location_id"]) + ",",
			'"' + location["location_name"] + '",',
			str(location["latitude"]) + ",",
			   str(location["longitude"]) + ","
	)