from exporter.exporter import Exporter
from config import config


postgres_connection = "dbname='" + config["dbname"] + "'" \
						+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
						+ " user='" + config["user"] + "' password='" + config["password"] + "'"

e = Exporter( postgres_connection, False, True )

# Find the person "Madame de Graffigny" :
# select person_id from cofk_union_person
# where iperson_id='910001'

person_id = 'cofk_union_person-iperson_id:000910001'

#select * from cofk_union_relationship
#where (left_table_name='cofk_union_person' and left_id_value='cofk_union_person-iperson_id:000910001' and right_table_name='cofk_union_work' ) or
#(right_table_name='cofk_union_person' and right_id_value='cofk_union_person-iperson_id:000910001' and left_table_name='cofk_union_work' )

command = "select right_id_value from cofk_union_relationship " \
			"where left_table_name='cofk_union_person' " \
			"and left_id_value='" + person_id + "' " \
			"and right_table_name='cofk_union_work'"

work_ids_left = e.select_all( command )
work_ids = [id['right_id_value'] for id in work_ids_left]

command = "select left_id_value from cofk_union_relationship " \
			"where right_table_name='cofk_union_person' " \
			"and right_id_value='" + person_id + "' " \
			"and left_table_name='cofk_union_work'"


work_ids_right = e.select_all( command )
work_ids.extend( [id['left_id_value'] for id in work_ids_right] )

# print (work_ids)
print( "Number of works: ", len(work_ids) )

e.export( work_ids, "voltaire-graffigny" )