__author__ = 'matthew'
import csv

from exporter.exporter import Exporter
from config import config
from exporter.exporter import excel_writer

debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
					  + " host='" + config["host"] + "' port='" + config["port"] + "'" \
					  + " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, output_commands=debug_on, output_results=False )

command = "select work_id from cofk_union_work"

work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]

work_count = len( work_ids )
work_count = 30000
batch = 5000
start = 0

parts = [
	'work',
	'person',
	'location',
	#'institution',
	#'manifestation',
	#'resource'
]

folders = []
for num in range( 0, work_count, batch ) :
	print( "Batching " + str(num) + ":" + str(num + batch) )

	e = Exporter( postgres_connection, False, False )
	folder_name = "all_" + str(num/batch)

	folders.append(folder_name)
	e.export( work_ids[num:num+batch], folder_name, create_excel=False , parts_csvs=parts )


combined_folder = 'exports/all_combined'
csv_datas = [
	{'file' : 'work.csv', 'key' : 'EMLO Letter ID Number'},
	{'file' : 'person.csv', 'key' : 'EMLO Person ID'},
	{'file' : 'location.csv', 'key' : 'Place ID'},
	#{'file' : 'institution.csv', 'key' : 'Repository ID'},
	#{'file' : 'manifestation.csv', 'key' : 'Work (Letter) ID'},
	#{'file' : 'resource.csv', 'key' : 'Resource ID' }
]

print "Collecting together..."
for csv_data in csv_datas :

	csv_file = csv_data['file']
	csv_key = csv_data['key']

	writer = None
	first_file = True
	ids = []
	for folder in folders:

		with open( "exports/" + folder + "/" + csv_file, "r" ) as f:
			csv_reader = csv.DictReader(f)

			if first_file :
				first_file = False

				if writer is None :
					csvfile = open(combined_folder + "/" + csv_file, 'w')
					writer = csv.DictWriter(csvfile, dialect='excel', fieldnames=csv_reader.fieldnames)
					writer.writeheader()

				for csv_row in csv_reader:

					ids.append( csv_row[csv_key] )
					writer.writerow( csv_row )

			else :

				for csv_row in csv_reader:
					id = csv_row[csv_key]

					if id not in ids :
						writer.writerow( csv_row )
						ids.append( id )

	csvfile.close()

settings = {
	'sheets' : [
		{ 'filelocation': combined_folder + '/work.csv', 'sheetname': 'Work' },
		{ 'filelocation': combined_folder + '/person.csv', 'sheetname': 'People' },
		{ 'filelocation': combined_folder + '/location.csv', 'sheetname': 'Locations' },
		#{ 'filelocation': combined_folder + '/institution.csv', 'sheetname': 'Institutions' },
		#{ 'filelocation': combined_folder + '/manifestation.csv', 'sheetname': 'Manifestations' },
		#{ 'filelocation': combined_folder + '/resource.csv', 'sheetname': 'Resources' },

	],
	'outputname' : combined_folder + '/combined.xls'
}

print "Combining to Excel..."
ew = excel_writer.ExcelWriter()
ew.convert(settings, skip_styles=True)
