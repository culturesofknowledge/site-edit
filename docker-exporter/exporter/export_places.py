
import csv
import re

from exporter.exporter import Exporter
from config import config
from exporter.excel_writer import ExcelWriter

debug_on = True
folder = "Places Export Analyse All"

postgres_connection = "dbname='" + config["dbname"] + "'" \
					+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
					+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )


command = "select location_id from cofk_union_location LIMIT 100000"
place_ids_response = e.select_all( command )
place_ids = [str(pid['location_id']) for pid in place_ids_response]

# place_ids = place_ids[:50]
# e.export_places( place_ids, folder )


def get_csv_data( filename ):

	rows = []

	with open( filename ) as file :
		csv_file = csv.DictReader( file, dialect=csv.excel )

		for row in csv_file:
			rows.append( row )

	return rows


# regex = re.compile("[\(\[].*?[\)\]]")
def name_clean(name) :

	# name = re.sub(regex, "", name)
	name = name.strip()

	return name


def save_csv( names, filename, headings ) :
	keys = names.keys()

	rows = []
	for key in keys :
		row = {
			headings[0]: names[key]['name'] if names[key]['name'] else ' empty ',
		}

		if len( headings ) > 1 :
			row[headings[1]] = names[key]['count']

		if len( headings ) > 2 :
			row[headings[2]] = key

		rows.append( row )

	rows = sorted( rows, key=lambda a: a[headings[0]] )

	e._save_csv(rows, headings, 'exports/' + folder + '/' + filename )




csv_data = get_csv_data( 'exports/' + folder + '/location.csv' )

print( "Location count: " + str( len(csv_data) ) )

# { "f" : "Room", "d" : { "o" : "location", "f" : "element_1_eg_room" } },
# { "f" : "Building", "d" : { "o" : "location", "f" : "element_2_eg_building" } },
# { "f" : "Street or parish", "d" : { "o" : "location", "f" : "element_3_eg_parish" } },
# { "f" : "Primary place name (city, town, village)", "d" : { "o" : "location", "f" : "element_4_eg_city" } },
# { "f" : "County, State, or Province", "d" : { "o" : "location", "f" : "element_5_eg_county" } },
# { "f" : "Country", "d" : { "o" : "location", "f" : "element_6_eg_country" } },
# { "f" : "Empire", "d" : { "o" : "location", "f" : "element_7_eg_empire" } },

rooms = dict()
buildings = dict()
parishs = dict()
towns = dict()
countys = dict()
countrys = dict()
empires = dict()


hierarchies = dict()  # dummy...


for row in csv_data:

	room = roomh = name_clean( row["Room"] )
	building = buildingh = name_clean( row["Building"] )
	parish = parishh = name_clean( row["Street or parish"] )
	town = townh = name_clean( row["Primary place name (city, town, village)"] )
	county = countyh = name_clean( row["County, State, or Province"] )
	country = countryh = name_clean( row["Country"] )
	empire = empireh = name_clean( row["Empire"] )

	if room:
		roomh = empire + ", " + country + ", " + county + ", " + town + ", " + parish + ", " + building + ", " + room
	if building:
		buildingh = empire + ", " + country + ", " + county + ", " + town + ", " + parish + ", " + building
	if parish:
		parishh = empire + ", " + country + ", " + county + ", " + town + ", " + parish
	if town:
		townh = empire + ", " + country + ", " + county + ", " + town
	if county:
		countyh = empire + ", " + country + ", " + county
	if country:
		countryh = empire + ", " + country
	#empireh = empire

	if roomh not in rooms :
		rooms[roomh] = { 'count': 1, 'name' : room }
	else :
		rooms[roomh]['count'] += 1

	if buildingh not in buildings:
		buildings[buildingh] = { 'count': 1, 'name' : building }
	else :
		buildings[buildingh]['count'] += 1

	if parishh not in parishs:
		parishs[parishh] = { 'count': 1, 'name' : parish }
	else :
		parishs[parishh]['count'] += 1

	if townh not in towns:
		towns[townh] = { 'count': 1, 'name' : town }
	else :
		towns[townh]['count'] += 1

	if countyh not in countys:
		countys[countyh] = { 'count': 1, 'name' : county }
	else :
		countys[countyh]['count'] += 1

	if countryh not in countrys:
		countrys[countryh] = { 'count': 1, 'name' : country }
	else :
		countrys[countryh]['count'] += 1

	if empireh not in empires:
		empires[empireh] = { 'count': 1, 'name' : empire }
	else :
		empires[empireh]['count'] += 1

	full = empire + ", " + country + ", " + county + ", " + town + ", " + parish + ", " + building + ", " + room
	if full not in hierarchies :
		pattern = str(int(empire is not '')) + str(int(country is not '')) + str(int(county is not '')) + str(int(town is not '')) + str(int(parish is not '')) + str(int(building is not '')) + str(int(room is not ''))
		hierarchies[full] = { 'count': 1, 'name' : pattern }
	else :
		hierarchies[full]['count'] += 1

stats = dict()
stats["Locations"] = { 'count': len( csv_data ), 'name' : '0 All Locations' }
stats["Empires"] = { 'count': len(empires), 'name' : '7 Empires' }
stats["Countries"] = { 'count': len(countrys), 'name' : '6 Countries' }
stats["Counties"] = { 'count': len(countys), 'name' : '5 Counties' }
stats["Towns"] = { 'count': len(towns), 'name' : '4 Towns' }
stats["Parishes"] = { 'count': len(parishs), 'name' : '3 Parishes' }
stats["Buildings"] = { 'count': len(buildings), 'name' : '2 Buildings' }
stats["Rooms"] = { 'count': len(rooms), 'name' : '1 Rooms' }

save_csv( rooms, 'room.csv', ['Room name', 'Used count', 'Empire, Country, County, Town, Parish, Building, Room'] )
save_csv( buildings, 'building.csv', ['Building name', 'Used count', 'Empire, Country, County, Town, Parish, Building'] )
save_csv( parishs, 'parish.csv', ['Parish name', 'Used count', 'Empire, Country, County, Town, Parish'] )
save_csv( towns, 'town.csv', ['Town name', 'Used count', 'Empire, Country, County, Town'] )
save_csv( countys, 'county.csv', ['County name', 'Used count', 'Empire, Country, County'] )
save_csv( countrys, 'country.csv', ['Country name', 'Used count', 'Empire, Country'] )
save_csv( empires, 'empire.csv', ['Empire name', 'Used count'] )
save_csv( hierarchies, 'hierarchy.csv', ['Pattern', 'Repeated', 'Name'] )
save_csv( stats, 'stats.csv', ['Stats', 'Count'] )

ew = ExcelWriter()
settings = {
	"sheets" : [
		{"filelocation":'exports/' + folder + '/location.csv','sheetname':'location'},
		{"filelocation":'exports/' + folder + '/resource.csv','sheetname':'resource'},
		{"filelocation":'exports/' + folder + '/room.csv','sheetname':'room'},
		{"filelocation":'exports/' + folder + '/building.csv','sheetname':'building'},
		{"filelocation":'exports/' + folder + '/parish.csv','sheetname':'parish'},
		{"filelocation":'exports/' + folder + '/town.csv','sheetname':'town'},
		{"filelocation":'exports/' + folder + '/county.csv','sheetname':'county'},
		{"filelocation":'exports/' + folder + '/country.csv','sheetname':'country'},
		{"filelocation":'exports/' + folder + '/empire.csv','sheetname':'empire'},
		{"filelocation":'exports/' + folder + '/hierarchy.csv','sheetname':'hierarchy'},
		{"filelocation":'exports/' + folder + '/stats.csv','sheetname':'stats'}
	],
	"outputname" : 'exports/' + folder + '/places-analysis.xlsx'
}

ew.convert( settings )

