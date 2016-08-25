from exporter.exporter import Exporter
from exporter.excel_writer import ExcelWriter
from config import config

debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, debug_on )

data = [

	{
		"name" : "AnhaltDessau",
		"personid" : 'cofk_union_person-iperson_id:000913001'
	},
	{
		"name" : "Brunswick-Luneburg",
		"personid" : 'cofk_union_person-iperson_id:000904075'
	},
	{
		"name" : "MaryII",
		"personid" : 'cofk_import_ead-recipient:000029735'
	},
	{
		"name" : "OranjeNassau",
		"personid" : 'cofk_union_person-iperson_id:000904559'
	},
	{
		"name" : "SolmsBraunfels",
		"personid" : 'cofk_import_ead-author:000000632'
	},
	{
		"name" : "StuartMary",
		"personid" : 'cofk_import_ead-author:000033232'
	},
]

for d in data:
	print( "Exporting " + d["name"] + "..." )

	command = "select left_id_value as work_id from cofk_union_relationship " + \
		"where right_id_value = '" + d["personid"] + "' AND " + \
		"left_table_name='cofk_union_work' " + \
		"UNION " + \
		"select right_id_value as work_id from cofk_union_relationship " + \
		"where left_id_value = '" + d["personid"] + "' AND " + \
		"right_table_name='cofk_union_work';" 

	work_ids = e.select_all( command )
	work_ids = [id['work_id'] for id in work_ids]

	outputfolderName = "Wive" + d["name"]
	e.export( work_ids, outputfolderName )

	outputfolder = "exports/" + outputfolderName
	ew = ExcelWriter()
	ew.convert( {
		"sheets" : [
			{
				"filelocation" : outputfolder + "/work.csv",
				"sheetname" : "Works"
			},
			{
				"filelocation" : outputfolder + "/person.csv",
				"sheetname" : "People"
			},
			{
				"filelocation" : outputfolder + "/location.csv",
				"sheetname" : "Locations"
			},
			{
				"filelocation" : outputfolder + "/manifestation.csv",
				"sheetname" : "Manifestations"
			},
			{
				"filelocation" : outputfolder + "/institution.csv",
				"sheetname" : "Institutions"
			},
			{
				"filelocation" : outputfolder + "/resource.csv",
				"sheetname" : "Resources"
			}
		],
		"outputname" : outputfolder + "/" + outputfolderName + ".xlsx"
	})