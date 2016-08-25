from openpyxl import Workbook
import csv

class ExcelWriter:

	def convert(self, settings ):
		# Settings : {
		#       sheets : [
		#           {
		#			    filelocation: <csv filename>,
		#               sheetname: <sheet name>
		#           }
		#       ]
		#       outputname : <output xlsx name
		#   }
		wb = Workbook()
		first = True
		for sheet in settings["sheets"]:

			if first:
				ws = wb.active
				first = False
			else :
				ws = wb.create_sheet()

			ws.title = sheet["sheetname"]

			with open(sheet["filelocation"], encoding="utf-8") as f:
				csv_reader = csv.reader(f)
				row = 1
				for csv_row in csv_reader:
					col = 1
					for cell in csv_row :
						c = ws.cell(row=row, column=col)
						c.value = cell

						col += 1

					row += 1

			wb.save(settings["outputname"])


if __name__ == "__main__":

	ew = ExcelWriter()
	ew.convert( {
		"sheets" : [
			{
				"filelocation" : "Test/work.csv",
				"sheetname" : "Works"
			},
			{
				"filelocation" : "Test/person.csv",
				"sheetname" : "People"
			},
			{
				"filelocation" : "Test/location.csv",
				"sheetname" : "Locations"
			},
			{
				"filelocation" : "Test/manifestation.csv",
				"sheetname" : "Manifestations"
			},
			{
				"filelocation" : "Test/institution.csv",
				"sheetname" : "Institutions"
			},
			{
				"filelocation" : "Test/resource.csv",
				"sheetname" : "Resources"
			}
		],
		"outputname" : "Test/test1.xlsx"
	})

