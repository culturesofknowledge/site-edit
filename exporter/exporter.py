# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'matthew'

import os.path

import psycopg2
import psycopg2.extras

import csv_unicode
import objects


class Exporter:

	def __init__(self, postgres_connection, output=False ):

		self.connection = psycopg2.connect( postgres_connection )  # e.g. "dbname='ouls' user='postgres' host='localhost' password=''"
		self.cursor = self.connection.cursor( cursor_factory=psycopg2.extras.DictCursor )

		self.output_commands = output

		self.relationship_ids = None


	def export(self, work_ids, output_folder ):
		"""
			Create csv files associated with the workids

			:param work_ids: Work ids to collect information on and export
			:param output_folder: The place to export the files too.
			:return: nought.
		"""

		if not self._empty( work_ids ) :

			work = "work"
			location = "location"
			person = "person"

			self.relationship_ids = []

			work_ids = set(work_ids)  # ensure unique

			if not os.path.exists( output_folder ):
				os.makedirs( output_folder )

			# Get Works (sub works of works!)
			# Disabling this, it
			# work_ids = work_ids.union( self._get_relationships( work, work_ids, work ) )


			# Get Locations from works
			#location_ids = self._get_relationships( work, work_ids, location )

			# Get People from works
			person_ids = self._get_relationships( work, work_ids, person )



	def _get_relationships(self, object_name, object_ids, wanted_name ):
		"""
		Get all the relations between object_name and wanted_name,
			right-to-left AND left-to-right with an in ID objects_ids
		:param object_name: The objects we have
		:param object_ids:  A array/set of the ID's of the objects we have
		:param wanted_name: THe objects we want.
		:return: A set of the object ids we wanted
		"""

		wanted_ids = set()

		object_table = "cofk_union_" + object_name
		object_ids_in_query = self._in(object_ids)
		wanted_table = "cofk_union_" + wanted_name

		raw_command = "SELECT relationship_id,left_table_name,right_table_name,left_id_value,right_id_value FROM cofk_union_relationship"
		raw_command += " WHERE (left_table_name=%s AND right_table_name=%s AND left_id_value IN " + object_ids_in_query + ")"
		raw_command += " OR (left_table_name=%s AND right_table_name=%s AND right_id_value IN " + object_ids_in_query + ")"

		relations = self.select_all( raw_command, object_table, wanted_table, wanted_table, object_table )

		if len(relations) > 0 :

			for relation in relations :
				if relation['left_table_name'] == wanted_table:
					wanted_ids.add( relation['left_id_value'] )
				else:
					wanted_ids.add( relation['right_id_value'] )

				self.relationship_ids.append( str(relation['relationship_id']) )

			#print( relations )

			#wanted_ids

		return wanted_ids

	def get_people(self, ids ):
		fields = objects.get_people_fields()

		command = "SELECT " + ",".join(fields) + " FROM cofk_union_" + object_type + " WHERE " + object_type + "_id in " + self._in( object_ids )

		data = self.select_all( command )


	def select_one(self, raw_command, *args ):
		return self._select( True, raw_command, args )

	def select_all(self, raw_command, *args ):
		return self._select( False, raw_command, args )

	def _select( self, single, raw_command, args ):
		"""
		Make a selection from the _db
		:param raw_command:
		:param args:
		:return:
		"""

		command = self.cursor.mogrify( raw_command, args )

		if self.output_commands :
			self._output( command )

		self.cursor.execute( command )

		if single:
			return self.cursor.fetchone()
		else:
			return self.cursor.fetchall()


	@staticmethod
	def _output( *args ):
		print( " ".join( [ str(item) for item in args]) )

