import csv

from openpyxl import Workbook
from openpyxl.styles import Font


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
				row_count = 1

				col_font = Font(size=12,bold=True)
				for csv_row in csv_reader:
					col_count = 1
					for cell in csv_row :
						if row_count == 1:
							column = ws.column_dimensions["A"]

						c = ws.cell(row=row_count, column=col_count)

						if row_count == 1 :
							c.font = col_font

						c.value = cell

						col_count += 1

					row_count += 1

			# Adjust widths of columns
			dims = {}
			for row in ws.rows:
				for cell in row:
					if cell.value:
						dims[cell.column] = max((dims.get(cell.column, 0), len(cell.value)))
			for col, value in dims.items():
				ws.column_dimensions[col].width = value * 1.1  # Add a few more ;)

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

