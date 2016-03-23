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

	def tearDown(self):
		pass

	def test_database_connection(self):

		self.assertRaises( psycopg2.OperationalError, Exporter, "dbname='' user='' host='' password=''" )

		#e = exporter.Exporter( self.postgres_connection )


	def test_select_single(self):

		e = Exporter( self.postgres_connection )

		raw_command = "select iwork_id from cofk_union_work where work_id='cofk_import_ead-ead_c01_id:000013683'"
		value = e.select_one( raw_command )

		self.assertEqual( value[0], 13683 )

		raw_command = "select iwork_id from cofk_union_work where work_id=%s"
		value = e.select_one( raw_command, "cofk_import_ead-ead_c01_id:000013683" )

		self.assertEqual( value[0], 13683 )


	def test_select_all(self):

		e = Exporter( self.postgres_connection )

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


if __name__ == "__main__":
	unittest.main()