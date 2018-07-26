# encoding:utf8
__author__ = 'matthew'

from exporter.exporter import Exporter
from config import config

catalogues = [

	"Andreae, Johann Valentin",
	"Anhalt-Dessau, Henriette Amalia von",
	"Ashmole, Elias",
	"Aubrey, John",
	"Barlaeus, Caspar", # 5
	"Bacon, Anne",
	"Baxter, Richard",
	"Bayle, Pierre",
	"Beale, Robert",
	"Beeckman, Isaac", # 10
	"Talbot, Elizabeth [Bess of Hardwick]",
	"Bernegger, Matthias",
	"Beverland, Hadriaan",
	"Bisterfeld, Johann Heinrich",
	"Bodleian card catalogue", # 15
	"Bodleian Student Editions",
	"Böhme, Jacob",
	"Bourignon, Antoinette",
	"Brahe, Tycho",
	"Braunschweig-Wolfenbüttel, Sophia Hedwig von",
	"Brienne Collection",
	## ACTUALLY A PERSON... "Chapone, Sarah",
]
debug_on = False
postgres_connection = "dbname='" + config["dbname"] + "'" \
			+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
			+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, debug_on )

command = "select catalogue_code" \
		" from cofk_lookup_catalogue" \
		" where catalogue_name in ('" + "','".join(catalogues) + "')" \
		" order by catalogue_name"


catalogue_codes = e.select_all( command )
catalogue_codes = [id['catalogue_code'] for id in catalogue_codes]

# Get letters in catalouges
command = "select work_id from cofk_union_work where original_catalogue in ('" + "','".join(catalogue_codes) + "')"
work_ids = e.select_all( command )
work_ids = [id['work_id'] for id in work_ids]


#  Get letters that mention, sent, recieved to/from/ cofk_import_ead-author:000010667  (That's "Chapone, Sarah")
command = "select left_id_value from cofk_union_relationship where right_id_value='cofk_import_ead-author:000010667' AND ( relationship_type='was_addressed_to' or relationship_type='mentions' )"
work_ids_left = e.select_all( command )
work_ids_left = [id['left_id_value'] for id in work_ids_left]

command = "select right_id_value from cofk_union_relationship where left_id_value ='cofk_import_ead-author:000010667' AND ( relationship_type='created'  )"
work_ids_right = e.select_all( command )
work_ids_right = [id['right_id_value'] for id in work_ids_right]

work_ids.extend(work_ids_left)
work_ids.extend(work_ids_right)

e.export( work_ids, "catalogue-selection" )