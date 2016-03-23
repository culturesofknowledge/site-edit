# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'matthew'
import os.path
import csv

import psycopg2
import psycopg2.extras

import exporter.objects

class Exporter:

	def __init__(self, postgres_connection, output=False ):

		self.connection = psycopg2.connect( postgres_connection )  # e.g. "dbname='?' user='?' host='?' password='?'"
		self.cursor = self.connection.cursor( cursor_factory=psycopg2.extras.DictCursor )

		self.output_commands = output

		self.relationship_ids = None

		self.names = {
			"work" : "work",
			"location" : "location",
			"person" : "person",
			"manifestation" : "manifestation",
			"institution" : "institution",
			"image" : "image",
			"resource" : "resource",
			"comment" : "comment",
			"relationship" : "relationship"
		}


	def export(self, work_ids, output_folder ):
		"""
			Create csv files associated with the workids (This can use a lot of memory... given a lot of works)

			:param work_ids: Work ids to collect information on and export
			:param output_folder: The place to export the files too.
			:return: nought.
		"""

		if not self._empty( work_ids ) :

			#work = "work"
			#location = "location"
			#person = "person"
			#comment = "comment"

			self.relationship_ids = []


			if not os.path.exists( output_folder ):
				os.makedirs( output_folder )

			work_ids = set(work_ids)  # ensure unique

			# Get Works (sub works of works!)
			# Disabling this, it
			# work_ids = work_ids.union( self._get_relationships( self.names['work'], work_ids, self.names['work'] ) )

			works = self._get_works( list(work_ids) )

			# Get People from works
			person_ids = self._get_relationships( self.names['work'], work_ids, self.names['person'] )
			people = self._get_people( person_ids )

			print( self._get_person_field( people[0], "foaf_name" ) )

			# Get Locations from works
			#location_ids = self._get_relationships( self.names['work'], work_ids, self.names['location'] )
			#locations = self._get_locations( location_ids )

			# Get comments associated with works
			#comment_ids = self._get_relationships( self.names['work'], work_ids, self.names['comment'] )
			#work_comments = self._get_comments( comment_ids )

	def _create_work_csv(self, people, locations, comments):
		#get_work_csv_converter
		pass

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
		object_ids_in_query = self._create_in(object_ids)
		wanted_table = "cofk_union_" + wanted_name

		raw_command = "SELECT relationship_id,left_table_name,right_table_name,left_id_value,right_id_value,relationship_type FROM cofk_union_relationship"
		raw_command += " WHERE (left_table_name=%s AND right_table_name=%s AND left_id_value IN " + object_ids_in_query + ")"
		raw_command += " OR (left_table_name=%s AND right_table_name=%s AND right_id_value IN " + object_ids_in_query + ")"

		relations = self.select_all( raw_command, object_table, wanted_table, wanted_table, object_table )

		if len(relations) > 0 :

			for relation in relations :

				if relation['left_table_name'] == wanted_table:
					want_id = relation['left_id_value']
				else:
					want_id = relation['right_id_value']

				wanted_ids.add( want_id )
				self.relationship_ids.append( str(relation['relationship_id']) )

			#print( relations )

			#wanted_ids

		return wanted_ids

	def _get_works(self, ids ):
		fields = exporter.objects.get_work_fields()

		works = self._get_objects( self.names['work'], ids, fields )

		print( "works: ", works )

		return works

	def _get_people(self, ids ):
		fields = exporter.objects.get_person_fields()

		people = self._get_objects( self.names['person'], ids, fields )

		print( "people: ", people )

		return people

	def _get_person_field(self, person, field ):
		return self._get_object_field( person, field, exporter.objects.get_person_fields() )

	def _get_locations(self, ids ):
		fields = exporter.objects.get_location_fields()

		locations = self._get_objects( self.names['location'], ids, fields )

		print( "locations: ", locations )

		return locations

	def _get_comments(self, ids ):

		fields = exporter.objects.get_comment_fields()

		comment = self._get_objects( self.names['comment'], ids, fields )

		print( "comments: ", comment )

		return comment

	def _get_object_field(self,obj,field,all_fields):
		return obj[all_fields.index(field)]

	def _get_objects(self, object_type, object_ids, object_fields ):

		command = "SELECT " + ",".join(object_fields) + " FROM cofk_union_" + object_type + " WHERE " + object_type + "_id in " + self._create_in( object_ids )

		data = self.select_all( command )

		return data


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
	def _empty(lst ):
		return lst is None or len(lst) == 0

	@staticmethod
	def _output( *args ):
		print( " ".join( [ str(item) for item in args]) )

	@staticmethod
	def _create_in(group):
		return "('" + "','".join(group) + "')"