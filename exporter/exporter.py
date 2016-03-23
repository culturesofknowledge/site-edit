# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'matthew'
import os.path
import csv

import psycopg2
import psycopg2.extras

import exporter.objects

class Exporter:

	def __init__(self, postgres_connection, output_commands=False, output_results=False ):

		self.connection = psycopg2.connect( postgres_connection )  # e.g. "dbname='?' user='?' host='?' password='?'"
		self.cursor = self.connection.cursor( cursor_factory=psycopg2.extras.DictCursor )

		self.output_commands = output_commands
		self.output_results = output_results

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
			people_relations = self._get_relationships( self.names['work'], work_ids, self.names['person'] )
			self._pretty_print_relations("person",people_relations)

			person_ids = self._id_link_set_from_relationships(people_relations)
			people = self._get_people( person_ids )

			print( self._get_person_field( people[0], "foaf_name" ) )


			# Get Locations from works
			locations_relations = self._get_relationships( self.names['work'], work_ids, self.names['location'] )
			location_ids = self._id_link_set_from_relationships(locations_relations)
			locations = self._get_locations( location_ids )

			# Get comments associated with works
			comments_relations = self._get_relationships( self.names['work'], work_ids, self.names['comment'] )
			comment_ids = self._id_link_set_from_relationships(comments_relations)
			comments = self._get_comments( comment_ids )

			self._create_work_csv( works, people, people_relations, locations, locations_relations, comments, comments_relations, output_folder )


	def _create_work_csv(self, works, people, people_relations, locations, locations_relations, comments, comments_relations, folder ):

		work_csv = []

		work_converters = exporter.objects.get_work_csv_converter()

		work_csv_fields = []
		for conv in  work_converters :
			work_csv_fields.append( conv["f"] )

		for work in works :
			work_csv_row = {}

			work_id = self._get_work_field(work, "work_id")
			for work_converter in work_converters :

				csv_field = work_converter["f"]
				conv = work_converter["d"]

				obj = None
				if conv["o"] != "work" :
					obj_rels = None
					if conv["o"] == "location" :
						obj_rels = locations_relations.get(work_id, None)
						objs = locations
					elif conv["o"] == "person" :
						obj_rels = people_relations.get(work_id, None)
						objs = people
					elif conv["o"] == "comment" :
						obj_rels = comments_relations.get(work_id, None)
						objs = comments

					if obj_rels :
						# Get the first matching relation
						# TODO: For people mentioned we'll need to create a list of all possible :~(
						obj_rel = None
						for rel in obj_rels :
							if rel["r"] == conv["r"] :
								obj_rel = rel
								break

						if obj_rel :
							for obj_find in objs :
								if obj_find[0] == obj_rel["i"] :
									obj = obj_find
									break

				else :
					obj = work

				csv_value = ""
				if obj :
					csv_value = obj[conv["f"]]

				print (conv["f"], csv_value)
				work_csv_row[csv_field] = csv_value

			work_csv.append(work_csv_row)

		self._save_csv( work_csv, work_csv_fields, folder + "/work.csv" )

		#return work_csv


	def _get_relationships(self, object_name, object_ids, wanted_name ):
		"""
		Get all the relations between object_name and wanted_name,
			right-to-left AND left-to-right with an in ID objects_ids
		:param object_name: The objects we have
		:param object_ids:  A array/set of the ID's of the objects we have
		:param wanted_name: THe objects we want.
		:return: A set of the object ids we wanted
		"""

		wanted = {} # Looking like this:   { obj_id : [ { i(id) : wanted_id, r(relation): relationshiop_type }, ] }

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
					obj_id = relation['right_id_value']
					want_id = relation['left_id_value']
				else:
					obj_id = relation['left_id_value']
					want_id = relation['right_id_value']

				if obj_id not in wanted :
					wanted[obj_id] = []

				wanted[obj_id].append( {"i" : want_id, "r" : relation['relationship_type'] } )

				self.relationship_ids.append( str(relation['relationship_id']) )

			#print( relations )

		return wanted

	def _get_works(self, ids ):
		fields = exporter.objects.get_work_fields()

		works = self._get_objects( self.names['work'], ids, fields )

		self._pretty_print_objects( "works", works )

		return works

	def _get_work_field(self, person, field ):
		return self._get_object_field( person, field, exporter.objects.get_work_fields() )

	def _get_people(self, ids ):
		fields = exporter.objects.get_person_fields()

		people = self._get_objects( self.names['person'], ids, fields )

		self._pretty_print_objects( "people", people )

		return people

	def _get_person_field(self, person, field ):
		return self._get_object_field( person, field, exporter.objects.get_person_fields() )

	def _get_locations(self, ids ):
		fields = exporter.objects.get_location_fields()

		locations = self._get_objects( self.names['location'], ids, fields )

		self._pretty_print_objects( "locations", locations )

		return locations

	def _get_comments(self, ids ):

		fields = exporter.objects.get_comment_fields()

		comments = self._get_objects( self.names['comment'], ids, fields )

		self._pretty_print_objects( "comments", comments )

		return comments

	def _get_object_field(self,obj,field,all_fields):
		return obj[all_fields.index(field)]

	def _get_objects(self, object_type, object_ids, object_fields ):

		data = []

		if len( object_ids ) > 0 :

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
		self._output_commands( command )

		self.cursor.execute( command )

		if single:
			return self.cursor.fetchone()
		else:
			return self.cursor.fetchall()

	@staticmethod
	def _save_csv(rows, fields, filepos ):

		with open(filepos, 'w') as csvfile:
			writer = csv.DictWriter(csvfile, fieldnames=fields)
			writer.writeheader()
			for row in rows :
				writer.writerow( row )

	@staticmethod
	def _id_link_set_from_relationships(relationships ):
		ids = set()
		for obj in relationships :
			for link in relationships[obj] :
				ids.add( link["i"] )

		return ids


	@staticmethod
	def _empty(lst ):
		return lst is None or len(lst) == 0

	@staticmethod
	def _create_in(group):
		return "('" + "','".join(group) + "')"


	def _pretty_print_objects( self, name, objs ):
		if self.output_results:
			print( name, "(", len(objs), "):" )
			for i, obj in enumerate(objs) :
				print( " ", name[0] + str(i), ": ", obj)

	def _pretty_print_relations( self, name, rels ):
		if self.output_results:
			print( name, "relations(", len(rels), "):" )
			for i, obj in enumerate(rels) :
				print( " ", name[0] + "r" + str(i)," : ", obj + "(" + str(len( rels[obj] )) + ")", rels[obj])

	def _output_commands( self, *args ):
		if self.output_commands:
			print( " ".join( [ str(item) for item in args]) )
