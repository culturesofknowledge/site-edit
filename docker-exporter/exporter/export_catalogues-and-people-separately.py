# encoding:utf8
__author__ = 'matthew'

import sys

from exporter.exporter import Exporter
from config import config

catalogues = [
	"Andreae, Johann Valentin",
	"Aubrey, John",
	"Bisterfeld, Johann Heinrich",
	"Bourignon, Antoinette",
	"Boyle, Robert",
	"Coccejus, Johannes",
	"Comenius, Jan Amos",
	"Descartes, René",
	"Dutch Church in London archive (Hessels)",
	"Franckenberg, Abraham von",
	"Hartlib, Samuel",
	"Jungius, Joachim",
	"Mersenne, Marin",
	"Opitz, Martin",
	"Peiresc, Nicolas-Claude Fabri de",
	"Permeier, Johann",
	"Reneri, Henricus",
	"Stuart, Elizabeth, Queen of Bohemia",
	"Ussher, James",
	"Wallis, John"
]

people = [
	#	people=oldenburg, henry
	{ "name" : "Oldenburg, Henry", "id" : "c573b65e-4315-4846-9d8d-9dda68f458e1" },
	#	people=mede,%20joseph
	{ "name" : "Mede, Joseph", "id" : "970a563c-13d6-421d-b5bd-85930c1420ba" },
	#	people=winthrop, john, 1588-1649
	{ "name" : "Winthrop, John, 1588-1649", "id" : "49b30640-ca7d-485a-b6a3-3d5469954f4c" },
	#	people=winthrop, john, 1606-1676
	{ "name" : "Winthrop, John, 1606-1676", "id" : "e65e7a09-ccd5-401e-b37f-fe26abe3d61d" },
	#	people=Worthington,%20John,%201618-1671
	{ "name" : "Worthington, John", "id" : "95702176-9154-47b7-b7a3-f1aa312b826d" },
]

debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
			+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
			+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

def export_collection( name, work_ids ) :
	print( "cat:", name, ", len:", len( work_ids ) )

	e.export( work_ids, name.replace(",", ""), export_folder='exports/singles/' )


for catalogue in catalogues :

	command = "select catalogue_code" \
			" from cofk_lookup_catalogue" \
			" where catalogue_name in ('" + catalogue + "')" \
			" order by catalogue_name"


	catalogue_code = e.select_all( command )
	if catalogue_code :
		catalogue_code = catalogue_code[0]['catalogue_code']

		# Get letters in catalouges
		command = "select work_id from cofk_union_work where original_catalogue in ('" + catalogue_code + "')"
		work_ids = e.select_all( command )
		work_ids = [id['work_id'] for id in work_ids]

		export_collection( catalogue, work_ids )


for person in people :

	command = "select person_id from cofk_union_person where uuid='" + person["id"] + "'"
	person_id = e.select_all( command )

	if person_id :

		person_id = person_id[0]['person_id']
		work_ids = []

		#  Get letters that mention, sent, recieved to/from/ cofk_import_ead-author:000010667  (That's "Chapone, Sarah")
		command = "select left_id_value from cofk_union_relationship where right_id_value='" + person_id + "' AND ( relationship_type='was_addressed_to' or relationship_type='mentions' )"
		work_ids_left = e.select_all( command )
		work_ids_left = [id['left_id_value'] for id in work_ids_left]

		command = "select right_id_value from cofk_union_relationship where left_id_value ='" + person_id + "' AND ( relationship_type='created'  )"
		work_ids_right = e.select_all( command )
		work_ids_right = [id['right_id_value'] for id in work_ids_right]

		work_ids.extend(work_ids_left)
		work_ids.extend(work_ids_right)

		export_collection( person['name'], work_ids )