from __future__ import print_function
import sys

import xlrd

exporter_path = '/usr/src/app/'
sys.path.append( exporter_path )
from exporter import objects
from tweaker import DatabaseTweaker


debugging = True
logger = None


def main( fileposition, config, log ) :

	global logger
	logger = log

	data, object_name = get_excel_data( fileposition )
	command = data[0]['Command']

	p( data )

	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	tweaker.set_debug( debugging )

	if command == "UPDATE" :
		update_tweak( data, object_name, tweaker )
	elif command == "DELETE" :
		raise Exception( "Sorry, delete is not implemented yet." )
	elif command == "CREATE" :
		raise Exception( "Sorry, create is not implemented yet." )

	tweaker.print_audit()
	tweaker.commit_changes(True)

	return "Changes completed successfully"


def update_tweak( data, object_name, tweaker ) :

	id_field = get_id_field( object_name )

	for row in data:
		update = {}

		for field in row :

			if field != id_field and field != "Command" :

				update[field] = tweaker.convert_field_type( object_name, field, row[field] )

		call_update( tweaker, object_name, row[id_field], update )


def call_update( tweaker, object_name, object_id, update ) :

	print_sql = debugging
	anonymous = False

	if object_name == "work" :
		tweaker.update_work_from_iwork( object_id, update, print_sql, anonymous )

	elif object_name == "person" :
		tweaker.update_person_from_iperson( object_id, update, print_sql, anonymous )

	elif object_name == "location" :
		tweaker.update_location( object_id, update, print_sql, anonymous )

	elif object_name == "manifestation" :
		tweaker.update_manifestation( object_id, update, print_sql, anonymous )

	elif object_name == "institution" :
		tweaker.update_institution( object_id, update, print_sql, anonymous )

	elif object_name == "resource" :
		tweaker.update_resource( object_id, update, print_sql, anonymous )


def get_excel_data( fileposition ) :

	workbook = xlrd.open_workbook( fileposition )
	worksheet = workbook.sheet_by_index(0)
	name = worksheet.name

	headings = []
	num_cols = worksheet.ncols
	for col_idx in range(0, num_cols):  # Iterate through columns
		cell_obj = worksheet.cell(0, col_idx)  # Get cell object by row, col
		headings.append( cell_obj.value )

	headings = convert_fieldnames(headings, name)

	data = []
	num_cols = worksheet.ncols   # Number of columns
	for row_idx in range(1, worksheet.nrows):    # Iterate through rows
		row = {}
		for col_idx in range(0, num_cols):  # Iterate through columns
			cell_obj = worksheet.cell(row_idx, col_idx)  # Get cell object by row, col
			row[headings[col_idx]] = cell_obj.value

		data.append( row )

	return data, name


def convert_fieldnames( headers, object_name ) :

	converter = None
	if object_name == "work" :
		converter = objects.get_work_csv_converter()

	new_headers = []
	for header in headers:

		new_header = None

		if header == "Command" :
			new_header = "Command"
		else :
			for conv in converter:
				if header == conv["f"] :
					new_header = conv["d"]["f"]
					break

		if new_header is None :
			message = 'Header "' + header + '" is not found.'
			p( message )
			raise Exception( message )
		else:
			new_headers.append( new_header )

	# p( headers )
	# p( new_headers )

	return new_headers


def get_id_field( object_name) :

	if object_name == "work" :
		return "iwork_id"
	elif object_name == "person" :
		return "iperson_id"
	elif object_name == "location" :
		return "location_id"
	elif object_name == "manifestation" :
		return "manifestation_id"
	elif object_name == "institution" :
		return "institution_id"
	elif object_name == "resource" :
		return "resource_id"


def get_converter( object_name) :

	if object_name == "work" :
		return objects.get_work_csv_converter()
	elif object_name == "person" :
		return objects.get_person_csv_converter()
	elif object_name == "location" :
		return objects.get_location_csv_converter()
	elif object_name == "manifestation" :
		return objects.get_manifestation_csv_converter()
	elif object_name == "institution" :
		return objects.get_institution_csv_converter()
	elif object_name == "resource" :
		return objects.get_resource_csv_converter()


def p( text ) :

	if logger :
		logger.info( text )
	else:
		print( text )


if __name__ == "__main__":

	config = dict(
		dbname="ouls",
		user="postgres",
		host="postgres",
		password="postgres",
		port="5432"
	)

	file_location = "/tweaker_data/excel_test_one_sheet_limited_fields-190517-132907/excel_test_one_sheet_limited_fields.xlsx"
	file_location = "/tweaker_data/excel_test_one_sheet_limited_fields_correct_catalogue-190520-144840/excel_test_one_sheet_limited_fields_correct_catalogue.xlsx"
	file_location = "/tweaker_data/excel_test_one_sheet_one_column-190520-192736/excel_test_one_sheet_one_column.xlsx"

	main( file_location, config, None )


