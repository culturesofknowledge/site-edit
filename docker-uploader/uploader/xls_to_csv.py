from __future__ import print_function

import sys, argparse, os.path, fnmatch
import xlrd

import csv, csv_unicode

#
# Defaults for CSV files
#
xslsheets = ['work', 'places',  'people', 'repositories', 'manifestation']
xslsheets_names = {'work' : ['works'], 'places' : ['place'], 'people':['person'], 'repositories':['repository'], 'manifestation':['manifestations'] }
outputcsv = {'work':'works.csv', 'places':'places.csv','people':'people.csv', 'repositories' : "repositories.csv", 'manifestation' : 'manifestations.csv'}
csvfields = {
	'work' : [
		'iwork_id',
		'date_of_work_as_marked',
		'original_calendar',
		'date_of_work_std_year',
		'date_of_work_std_month',
		'date_of_work_std_day',
		'date_of_work2_std_year',
		'date_of_work2_std_month',
		'date_of_work2_std_day',
		'date_of_work_std_is_range',
		'date_of_work_inferred',
		'date_of_work_uncertain',
		'date_of_work_approx',
		'notes_on_date_of_work',
		'author_names'	,
		'author_ids'	,
		'authors_as_marked',
		'authors_inferred'	,
		'authors_uncertain'	,
		'notes_on_authors'	,
		'addressee_names',
		'addressee_ids'	,
		'addressees_as_marked',
		'addressees_inferred',
		'addressees_uncertain',
		'notes_on_addressees',
		'origin_name'	,
		'origin_id'	,
		'origin_as_marked',
		'origin_inferred',
		'origin_uncertain',
		'destination_name',
		'destination_id',
		'destination_as_marked',
		'destination_inferred'	,
		'destination_uncertain'	,
		'abstract'	,
		'keywords'	,
		'language_id',
		'language_of_work',
		'hasgreek'	,
		'hasarabic'	,
		'hashebrew'	,
		'haslatin'	,
		'answererby',
		'incipit'	,
		'excipit'	,
		'notes_on_letter',
		'mention_id'	,
		'emlo_mention_id',
		'notes_on_people_mentioned',
		'editors_notes'	,
		'resource_name'	,
		'resource_url'	,
		'resource_details'
	],
	'places' : [
		'location_name',
		'location_id',
		'editors_notes'
	],
	'people' : [
		'primary_name',
		'iperson_id',
		'editors_notes'
	],

	'repositories' : [
		'institution_name',
		'institution_id',
		'institution_city',
		'institution_country'
	],

	'manifestation' : [
		'manifestation_id',
		'iwork_id',
		'manifestation_type',
		'repository_id',
		'repository_name',
		'id_number_or_shelfmark',
		'manifestation_notes',
		'manifestation_type_p',
		'printed_edition_details',
		'printed_edition_notes',
		'ms_translation',
		'printed_translation'
	]
}

debug = True

def get_xslcell_text( worksheet, row, col, name ) : #type, value ) :

	type = worksheet.cell_type(row, col )
	value = worksheet.cell_value( row, col )

	error = None

	# Cell Types: 0=Empty, 1=Text, 2=Number, 3=Date, 4=Boolean, 5=Error, 6=Blank
	if type == 0 or type == 1 or type == 6 : # empty
		return value, error

	if type == 3 :
		return value, error

	if type == 2 :

		try:
			num_str = str( value )

			if num_str[-1] == "0" and num_str[-2] == "." :
				# assume is int
				return str( int( value ) ), None

		except ValueError :
			error = "Error: Unable to convert number at row" + str(row) + ":col" + str(col) + " in worksheet " + name

		return str( value ), error

	if type == 5 :
		error = "Error: There is an Excel Cell Error in cell at row" + str(row) + ":col" + str(col) + " in worksheet " + name

		return None, error


def create_csvs( filename, output_folder, dont_skip_row_2=False ) :

	if debug:
		print( "Opening workbook " + filename )

	errors = []
	workbook = xlrd.open_workbook( filename )
	print()

	if not os.path.exists( output_folder ) :
		os.mkdir( output_folder )

	for sheet in xslsheets:

		worksheet = None

		if debug:
			print( "Opening sheet: " + sheet )

		possible_sheet_names = [sheet,sheet.capitalize()]
		for name in xslsheets_names[sheet] :
			possible_sheet_names.append( name )
			possible_sheet_names.append( name.capitalize() )


		for name in possible_sheet_names:

			try:
				worksheet = workbook.sheet_by_name( name )
			except xlrd.biffh.XLRDError:
				pass


		if worksheet is None :
			print( "Can't find worksheet '" + sheet + "' so creating empty csv file" )

		# Sometimes the reported number of columns is WAY more than the actual columns... COmmented this check out
		#if worksheet.ncols != len( csvfields[sheet] ) :
		#	print( "Too many columns detected in sheet:" + sheet + ". In Worksheet:" + str(worksheet.ncols) + " In CSV Field list:" + str(len( csvfields[sheet] ) ) )
		#	for row in xrange( row_start, worksheet.nrows ):
		#		print( row, worksheet.row(row) )


		#
		# Open CSV writer
		#
		csvfilename = os.path.join( output_folder, outputcsv[sheet] )
		csvfile = open( csvfilename, 'wb')
		writer = csv_unicode.UnicodeWriter( csvfile, dialect=None, delimiter=",", quoting=csv.QUOTE_MINIMAL, quotechar='"', doublequote=True )

		writer.writerow( csvfields[sheet] )

		if worksheet is not None :

			xlsfields = worksheet.row(0)

			row_start = 2
			if dont_skip_row_2 :
				row_start = 1

			for row in xrange( row_start, worksheet.nrows ):

				csvrow = []
				for col in xrange( len(csvfields[sheet]) ): #worksheet.ncols ):

					try:
						value, error = get_xslcell_text( worksheet, row, col, name )

						if error:
							csvrow.append( error )
							errors.append( error )
						else:
							csvrow.append( value )

					except IndexError:
						# If a column is missing from excel just add blank value (Sometimes there's editors notes, and sometimes there isn't...)
						csvrow.append("")

				writer.writerow( csvrow )

			print ("Extracted " + str(worksheet.nrows-row_start) + " rows from worksheet " + sheet )

		if debug:
			print ("Saving " + sheet + " to " + csvfilename )

		csvfile.close()


		print()

	return errors

if __name__ == "__main__":

	parser = argparse.ArgumentParser(description='Convert XLS document to CSV.', usage="xls_to_csv.py [<excel folder> <data folder>] [--excel_file <input excel file> --output_folder <output folder>]")

	# either pass in these two...:
	parser.add_argument( "excel_folder_name" , help='The folder name that Excel document exists in', type=str, default=None )
	parser.add_argument( "data_folder", help='The parent folder which contains the excel document and will contain the csv output', type=str, default=None )

	# or pass in these two...:
	parser.add_argument( "--excel_file" , help='The Excel document to convert (".xls" or ".xlsx")', type=str, default=None )
	parser.add_argument( "--output_folder", help = "The folder to write the worksheet csv's to.", type=str, default=None )

	parser.add_argument( "--dont_skip_row_2", default=False, help = "Don't skip the second row in all worksheets", type=bool )


	args = parser.parse_args()

	if args.excel_file and args.output_folder :
		filename = args.excel_file
		output_folder = args.output_folder

	elif args.excel_folder_name and args.data_folder :

		output_folder = os.path.join( args.data_folder, "csv", args.excel_folder_name )
		excel_folder = os.path.join( args.data_folder, "xls", args.excel_folder_name )

		excel_types = ( "*.xls", "*.xlsx" )
		files = os.listdir( excel_folder )
		excels = []
		for file in files :
			for type in excel_types :
				if fnmatch.fnmatch( file, type ) :
					excels.append( file )

		if len( excels ) == 1:
			filename = os.path.join( excel_folder, excels[0] )

			print ( "Excel File: " + filename )
			print( "Output Folder: " + output_folder)

		else:
			if len(excels) == 0 :
				print( "No excel files detected in folder " + excel_folder )
			elif len(excels) > 1 :
				print ( "Too many excel files in " + excel_folder + " - I don't know which one to use." )

			parser.print_help()

			sys.exit(1)

	else :
		print( "Error, wrong inputs" )
		parser.print_help()

		sys.exit(1)


	errors = create_csvs( filename, output_folder, args.dont_skip_row_2 )

	if len(errors) != 0 :
		print("Errors detected:")
		print("\n".join(errors))

