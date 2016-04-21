# -*- coding: utf-8 -*-
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

		self.reports = []
		self.limit_ouput = 10


	def export(self, work_ids, output_folder ):
		"""
			Create csv files associated with the workids (This can use a lot of memory... given a lot of works)

			:param work_ids: Work ids to collect information on and export
			:param output_folder: The place to export the files too.
			:return: nought.
		"""

		self.reports = []

		if not self._empty( work_ids ) :

			#work = "work"
			#location = "location"
			#person = "person"
			#comment = "comment"

			self.relationship_ids = []

			if not os.path.exists( output_folder ):
				os.makedirs( output_folder )

			work_ids = list( set(work_ids) )  # ensure unique

			# Get Works (sub works of works!)
			# Disabling this, it
			# work_ids = work_ids.union( self._get_relationships( self.names['work'], work_ids, self.names['work'] ) )


			# Works

			works = self._get_works( work_ids )

			# work - work relations
			# TODO: We'll need to handle these links (but only for those between works we already have...)
			# work_relations = self._get_relationships( self.names['work'], work_ids, self.names['work'] )

			# Get People from works
			people_relations = self._get_relationships( self.names['work'], work_ids, self.names['person'] )
			self._pretty_print_relations("person", people_relations)

			person_ids = self._id_link_set_from_relationships(people_relations)
			people = self._get_people( person_ids )

			# Get Locations from works
			locations_relations = self._get_relationships( self.names['work'], work_ids, self.names['location'] )
			location_ids = self._id_link_set_from_relationships(locations_relations)
			locations = self._get_locations( location_ids )

			# Get comments associated with works
			comments_relations = self._get_relationships( self.names['work'], work_ids, self.names['comment'] )
			comment_ids = self._id_link_set_from_relationships(comments_relations)
			comments = self._get_comments( comment_ids )

			# Get resources associated with works
			resource_relations_works = self._get_relationships( self.names['work'], work_ids, self.names['resource'] )
			resource_ids = self._id_link_set_from_relationships(resource_relations_works)
			resources_works = self._get_resources( resource_ids )


			# Add new resources so that exported data has links back to EMLO front end
			for work in works :

				work_id = work["work_id"]
				iwork_id = work["iwork_id"]  # Note this isn't "work_id" !
				#related_resource = self._get_work_field( work, "iwork_id" )

				new_resource = {
					"resource_id" : "er" + str(iwork_id),
					"resource_name": "Early Modern Letters Online",
					"resource_details" : "",
					"resource_url" :    "http://emlo.bodleian.ox.ac.uk/profile?iwork_id=" + str(iwork_id)
										#http://emlo.bodleian.ox.ac.uk/profile?iwork_id=30348
				}
				resources_works.append(new_resource)
				# { obj_id : [ { i(id) : wanted_id, r(relation): relationship_type }, ] }

				if work_id not in resource_relations_works :
					resource_relations_works[work_id] = []

				resource_relations_works[work_id].append( { "i" : new_resource["resource_id"], "r" : "is_related_to" } )

			self._create_work_csv( works, people, people_relations, locations, locations_relations, comments, comments_relations, resources_works, resource_relations_works, output_folder )


			# People

			# Get comments associated with people
			comments_relations = self._get_relationships( self.names['person'], work_ids, self.names['comment'] )
			comment_ids = self._id_link_set_from_relationships(comments_relations)
			comments = self._get_comments( comment_ids )

			# Get resources associated with people
			resource_relations_people = self._get_relationships( self.names['person'], person_ids, self.names['resource'] )
			resource_ids = self._id_link_set_from_relationships(resource_relations_people)
			resources_people = self._get_resources( resource_ids )

			self._create_person_csv( people, comments, comments_relations, resources_people, resource_relations_people, output_folder )


			# Locations

			# Get comments associated with locations
			comments_relations = self._get_relationships( self.names['location'], work_ids, self.names['comment'] )
			comment_ids = self._id_link_set_from_relationships(comments_relations)
			comments = self._get_comments( comment_ids )

			# Get resources associated with locations
			resource_relations_locations = self._get_relationships( self.names['location'], location_ids, self.names['resource'] )
			resource_ids = self._id_link_set_from_relationships(resource_relations_locations)
			resources_locations = self._get_resources( resource_ids )

			self._create_location_csv( locations, comments, comments_relations, resources_locations, resource_relations_locations, output_folder )


			# Manifestations

			# Get Manifestations from works
			manfestation_relations = self._get_relationships( self.names['work'], work_ids, self.names['manifestation'] )
			self._pretty_print_relations( "manifestation", manfestation_relations)

			manifestation_ids = self._id_link_set_from_relationships(manfestation_relations)
			manifestations = self._get_manifestations( manifestation_ids )


			# Get Relations of manuscripts to works (reverse of above relations - I could do it in code but this is easier(lazier))
			manfestation_work_relations = self._get_relationships( self.names['manifestation'], manifestation_ids, self.names['work'] )
			self._pretty_print_relations( "manifestation-work", manfestation_work_relations)


			# Get Institutions from manuscripts
			manifestation_institution_relations = self._get_relationships( self.names['manifestation'], manifestation_ids, self.names['institution'] )
			self._pretty_print_relations( "manifestation-institution", manifestation_institution_relations)

			institution_ids = self._id_link_set_from_relationships(manifestation_institution_relations)
			institutions = self._get_institutions( institution_ids )


			# Get comments associated with manifestations
			comments_relations = self._get_relationships( self.names['manifestation'], manifestation_ids, self.names['comment'] )
			comment_ids = self._id_link_set_from_relationships(comments_relations)
			comments = self._get_comments( comment_ids )

			self._create_manifestation_csv(manifestations, works, manfestation_work_relations, institutions, manifestation_institution_relations, comments, comments_relations, output_folder )



			# Institutions

			# Get resources associated with Institutions
			resource_relations_institutions = self._get_relationships( self.names['institution'], institution_ids, self.names['resource'] )
			resource_ids = self._id_link_set_from_relationships(resource_relations_institutions)
			resources_institutions = self._get_resources( resource_ids )

			self._create_institution_csv(institutions, resources_institutions, resource_relations_institutions, output_folder )


			# Resources

			resources = []
			resources.extend(resources_works)
			resources.extend(resources_people)
			resources.extend(resources_locations)
			resources.extend(resources_institutions)

			self._create_resource_csv(resources, output_folder )


		else:
			self.reports.append( "No works given!" )

		self._print_report()


	def _create_resource_csv(self, resources, folder ):

		self._create_csv( exporter.objects.get_resource_csv_converter(),
							resources,
							self.names['resource'],
							{},
							{},
							folder + "/resource.csv" )

	def _create_institution_csv(self, institutions, resources, resource_relations, folder ):

		related = {self.names['resource'] : resources }
		related_relations = {  self.names['resource'] : resource_relations }

		self._create_csv( exporter.objects.get_institution_csv_converter(),
							institutions,
							self.names['institution'],
							related,
							related_relations,
							folder + "/institution.csv" )


	def _create_manifestation_csv(self, manifestations, works, work_relations, institutions, institution_relations, comments, comment_relations, folder ):
		related = { self.names['work'] : works, self.names['institution'] : institutions, self.names['comment'] : comments }
		related_relations = { self.names['work'] : work_relations, self.names['institution'] : institution_relations, self.names['comment'] : comment_relations }

		self._create_csv( exporter.objects.get_manifestation_csv_converter(),
							manifestations,
							self.names['manifestation'],
							related,
							related_relations,
							folder + "/manifestation.csv" )

	def _create_location_csv(self, locations, comments, comments_relations, resources, resource_relations, folder ):

		related = { self.names['comment'] : comments, self.names['resource'] : resources }
		related_relations = { self.names['comment'] : comments_relations, self.names['resource'] : resource_relations }

		self._create_csv( exporter.objects.get_location_csv_converter(),
							locations,
							self.names['location'],
							related,
							related_relations,
							folder + "/location.csv" )

	def _create_person_csv(self, people, comments, comments_relations, resources, resource_relations, folder ):

		related = { self.names['comment'] : comments, self.names['resource'] : resources }
		related_relations = { self.names['comment'] : comments_relations, self.names['resource'] : resource_relations }

		self._create_csv( exporter.objects.get_person_csv_converter(),
							people,
							self.names['person'],
							related,
							related_relations,
							folder + "/person.csv" )


	def _create_work_csv(self, works, people, people_relations, locations, locations_relations, comments, comments_relations, resources, resource_relations, folder ):

		related = { self.names['person'] : people, self.names['location'] : locations, self.names['comment'] : comments, self.names['resource'] : resources }
		related_relations = { self.names['person'] : people_relations, self.names['location'] : locations_relations, self.names['comment'] : comments_relations, self.names['resource'] : resource_relations }

		self._create_csv( exporter.objects.get_work_csv_converter(),
								works,
								self.names['work'],
								related,
								related_relations,
								folder + "/work.csv" )

	def _create_csv(self, converters, objs, objs_type, related_list, related_relations_list, filename ):

		csv_fields = []
		for converter in converters :
			csv_fields.append( converter["f"] )

		objs_type_id = objs_type + "_id"

		csv_rows = []
		for obj in objs :
			csv_row = {}

			for converter in converters :

				csv_field = converter["f"]
				conv = converter["d"]

				csv_value = ""
				if conv["o"] != objs_type :

					conv_obj = conv["o"]

					# Find the matching objects
					obj_id = obj[objs_type_id]
					related_relations = related_relations_list[conv_obj].get(str(obj_id), None)

					if related_relations :

						relateds = related_list[conv_obj]
						conv_rel = conv["r"]
						conv_fie = conv["f"]

						# Get the matching relations
						obj_rel_list = []
						for rel in related_relations :
							if rel["r"] == conv_rel :
								obj_rel_list.append( rel )

						# Build a string of values
						if obj_rel_list :
							for obj_rel in obj_rel_list :
								obj_rel_id = str(obj_rel["i"])

								for obj_search in relateds :
									obj_search_id = obj_search[ conv_obj + "_id"]
									if str(obj_search_id) == obj_rel_id :

										if csv_value != "" and obj_search[conv_fie] != "" :
											csv_value += "; "

										csv_value += str(obj_search[conv_fie])

				else :
					csv_value = obj[conv["f"]]

				csv_row[csv_field] = csv_value

			csv_rows.append(csv_row)

		self._save_csv( csv_rows, csv_fields, filename )

		#return work_csv

	def _get_relationships(self, object_name, object_ids, wanted_name ):
		"""
		Get all the relations between object_name and wanted_name,
			right-to-left AND left-to-right with an in ID objects_ids
		:param object_name: The objects we have
		:param object_ids:  A array/set of the ID's of the objects we have
		:param wanted_name: THe objects we want.
		:return: A set of the object ids we wanted, Looking like this:   { obj_id : [ { i(id) : wanted_id, r(relation): relationship_type }, ] }
		"""

		wanted = {}  #

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
		return self._get_objects( self.names['work'], ids, exporter.objects.get_work_fields() )

	def _get_people(self, ids ):
		return self._get_objects( self.names['person'], ids, exporter.objects.get_person_fields() )

	def _get_locations(self, ids ):
		return self._get_objects( self.names['location'], ids, exporter.objects.get_location_fields() )

	def _get_manifestations(self, ids ):
		return self._get_objects( self.names['manifestation'], ids, exporter.objects.get_manifestation_fields() )

	def _get_institutions(self, ids ):
		return self._get_objects( self.names['institution'], ids, exporter.objects.get_institution_fields() )

	def _get_comments(self, ids ):
		return self._get_objects( self.names['comment'], ids, exporter.objects.get_comment_fields() )

	def _get_resources(self, ids ):
		return self._get_objects( self.names['resource'], ids, exporter.objects.get_resource_fields() )



	def _get_objects(self, object_type, object_ids, object_fields ):

		data = []

		count = len( object_ids )

		if count > 0 :

			offset = 0
			batch = 1000

			base_command = "SELECT " + ",".join(object_fields) + " FROM cofk_union_" + object_type + " WHERE " + object_type + "_id in "

			while offset < count :

				command = base_command + self._create_in( object_ids[offset:(offset + batch)] )
				data.extend( self.select_all( command ) )

				offset += batch

			self._pretty_print_objects( object_type + "s", data )

		self.reports.append( "Got " + str(len(data)) + " " + object_type )

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


	def _save_csv(self, rows, fields, filepos ):

		with open(filepos, 'w', encoding='utf-8-sig') as csvfile:  # utf8 with bom
			writer = csv.DictWriter(csvfile, dialect='excel', fieldnames=fields)

			writer.writeheader()

			for row in rows :
				writer.writerow( row )

		self.reports.append( 'Saving "' + filepos + '" with ' + str(len(rows)) + " rows" )


	@staticmethod
	def _id_link_set_from_relationships(relationships ):
		ids = set()
		for obj in relationships :
			for link in relationships[obj] :
				ids.add( link["i"] )

		return list(ids)


	@staticmethod
	def _empty(lst ):
		return lst is None or len(lst) == 0

	@staticmethod
	def _create_in(group):
		return "('" + "','".join(group) + "')"


	def _pretty_print_objects( self, name, objs ):
		if self.output_results:
			length = len(objs)
			print( name, "(", str(length), "):" )
			for i, obj in enumerate(objs) :
				print( " ", name[0] + str(i), ": ", obj)
				if i == self.limit_ouput :
					print( " ", str( length - self.limit_ouput ) + " more..." )
					break

	def _pretty_print_relations( self, name, rels ):
		if self.output_results:
			length = len(rels)
			print( name, "relations(", str(length), "):" )
			for i, obj in enumerate(rels) :
				print( " ", name[0] + "r" + str(i), " : ", obj + "(" + str(len( rels[obj] )) + ")", rels[obj])
				if i == self.limit_ouput :
					print( " ", str( length - self.limit_ouput ) + " more..." )
					break

	def _print_report(self):
		for report in self.reports :
			print( report )

	def _output_commands( self, *args ):
		if self.output_commands:
			print( " ".join( [ str(item) for item in args]) )

