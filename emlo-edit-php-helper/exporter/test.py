__author__ = 'sers0034'

import unittest
import psycopg2

from exporter.exporter import Exporter
from config import config

class TestExport(unittest.TestCase) :

	def setUp(self):
		self.postgres_connection = "dbname='" + config["dbname"] + "'" \
														+ " host='" + config["host"] + "' port='" + config["port"] + "'" \
														+ " user='" + config["user"] + "' password='" + config["password"] + "'"

		self.output_commands = False
		self.output_results = True

	def tearDown(self):
		pass

	def test_010_database_connection(self):

		print( "*** database connection test ***" )

		self.assertRaises( psycopg2.OperationalError, Exporter, "dbname='' user='' host='' password=''" )

		#e = exporter.Exporter( self.postgres_connection )


	def test_020_select_single(self):

		print( "*** select single test ***" )

		e = Exporter( self.postgres_connection, self.output_commands, self.output_results )

		raw_command = "select iwork_id from cofk_union_work where work_id='cofk_import_ead-ead_c01_id:000013683'"
		value = e.select_one( raw_command )

		self.assertEqual( value[0], 13683 )

		raw_command = "select iwork_id from cofk_union_work where work_id=%s"
		value = e.select_one( raw_command, "cofk_import_ead-ead_c01_id:000013683" )

		self.assertEqual( value[0], 13683 )


	def test_030_select_all(self):

		print("*** select all test ***")

		e = Exporter( self.postgres_connection, self.output_commands, self.output_results )

		raw_command = "select iwork_id from cofk_union_work where work_id='cofk_import_ead-ead_c01_id:000013683' or work_id='cofk_import_ead-ead_c01_id:000013690'"
		value = e.select_all( raw_command )

		self.assertIsInstance( value, list )
		self.assertEqual( value[0]['iwork_id'], 13683 )  # TODO... is the order gurenteed? I doubt it...
		self.assertEqual( value[1]['iwork_id'], 13690 )

		raw_command = "select iwork_id from cofk_union_work where work_id=%s or work_id=%s"
		value = e.select_all( raw_command, "cofk_import_ead-ead_c01_id:000013683","cofk_import_ead-ead_c01_id:000013690" )

		self.assertIsInstance( value, list )
		self.assertEqual( value[0]['iwork_id'], 13683 )  # TODO... is the order guaranteed? I doubt it...
		self.assertEqual( value[1]['iwork_id'], 13690 )


	def test_040_get_work(self):

		print( "*** get work test ***")

		e = Exporter( self.postgres_connection, self.output_commands, self.output_results )
		work_id = 'cofk_import_hartlib-row_id:000003994'  # just some random work I chose.

		works = e._get_works( [work_id] )

		self.assertEqual( len(works), 1 )
		work = works[0]

		self.assertEqual( work[0], work_id )
		self.assertEqual( e._get_work_field(work, "work_id"), work_id )

		self.assertEqual( e._get_work_field(work, "original_catalogue"), "HARTLIB" )


	def test_050_create_csvs(self):

		print("*** create csvs test ***")

		e = Exporter( self.postgres_connection, self.output_commands, self.output_results )

		folder = "Single Export"
		e.export( ['cofk_import_ead-ead_c01_id:000033695'], folder )


if __name__ == "__main__":
	unittest.main()