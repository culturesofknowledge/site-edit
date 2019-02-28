# -*- coding: utf-8 -*-
from __future__ import print_function
import sys
import csv
from datetime import datetime

import psycopg2
import psycopg2.extras

#import csv_unicode


class DatabaseTweaker:

	@classmethod
	def tweaker_from_connection( cls, dbname, host, port, user, password, debug=None ):
		postgres_connection = "dbname='" + dbname + "'" \
						+ " host='" + host + "' port='" + port + "'" \
						+ " user='" + user + "' password='" + password + "'"

		dt = cls( postgres_connection, debug )

		return dt

	def __init__( self, connection=None, debug=False ):

		self.debug = False
		self.set_debug(debug)

		self.connection = self.cursor = None
		if connection:
			self.connect_to_postres(connection)  # e.g. "dbname='<HERE>' user='<HERE>' host='<HERE>' password='<HERE>'"

		self._reset_audit()
		self.user = "cofkbot"

	def _reset_audit(self):
		self.audit = {
			"deletions" : {},
			"insertions" : {},
			"updates" : {}
		}

	def set_debug(self, debug):
		self.debug = debug
		if debug:
			print( "Debug ON - printing SQL" )

	def connect_to_postres(self, connection):

		try:
			self.connection = psycopg2.connect( connection )
		except:
			print( "ERROR: I am unable to connect to the database" )
			sys.exit(1)
		else:
			if self.debug :
				print( "Connected to database..." )

			self.cursor = self.connection.cursor(cursor_factory=psycopg2.extras.DictCursor)

	def close(self):

		self.connection.close()
		self.connection = self.cursor = None

	@staticmethod
	def get_csv_data( filename ):

		rows = []

		with open( filename ) as file :
			csv_file = csv.DictReader( file, dialect=csv.excel )

			for row in csv_file:
				rows.append( row )

		return rows

	def get_work_from_iwork_id( self, iwork_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_work WHERE iwork_id=%s"
		command = self.cursor.mogrify( command, (int(iwork_id),) )

		if self.debug :
			print( "* SELECT work:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_resource_from_resource_id( self, resource_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_resource WHERE resource_id=%s"
		command = self.cursor.mogrify( command, (resource_id,) )

		if self.debug :
			print( "* SELECT resource:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()


	def get_image_from_image_id( self, image_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_image WHERE image_id=%s"
		command = self.cursor.mogrify( command, (image_id,) )

		if self.debug :
			print( "* SELECT image:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_comment_from_comment_id( self, comment_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_comment WHERE comment_id=%s"
		command = self.cursor.mogrify( command, (comment_id,) )

		if self.debug :
			print( "* SELECT comment:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_institution_from_institution_id( self, institution_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_institution WHERE institution_id=%s"
		command = self.cursor.mogrify( command, (institution_id,) )

		if self.debug :
			print( "* SELECT institution:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_location_from_location_id( self, location_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_location WHERE location_id=%s"
		command = self.cursor.mogrify( command, (location_id,) )

		if self.debug :
			print( "* SELECT location:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_person_from_iperson_id( self, iperson_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_person WHERE iperson_id=%s"
		command = self.cursor.mogrify( command, (iperson_id,) )

		if self.debug :
			print( "* SELECT person:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_manifestation_from_manifestation_id( self, manifestation_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_manifestation WHERE manifestation_id=%s"
		command = self.cursor.mogrify( command, (manifestation_id,) )

		if self.debug :
			print( "* SELECT manifestation:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()


	def get_relationships(self, id_from, table_from=None, table_to=None ):
		"""
		Use "table_from" and "table_to" to limit the results.

		:param id_from: Get relationships for this object
		:param table_from: limit to these type of tables on left (e.g. "cofk_union_work")
		:param table_to: limit to these type of tables on right (e.g. "cofk_union_work")
		:return: Use the returned "table_name" and "id_value" which represent the thing that is connected to the given id (id_from).
		"""
		self.check_database_connection()

		command = "SELECT * FROM cofk_union_relationship"

		if table_from :
			command += " WHERE ((left_id_value=%s and left_table_name=%s)"
			command += " or (right_id_value=%s and right_table_name=%s))"
			values = [ id_from, table_from, id_from, table_from ]
		else:
			command += " WHERE ((left_id_value=%s or right_id_value=%s))"
			values = [ id_from, id_from ]

		if table_to :
			command += " and (left_table_name=%s or right_table_name=%s)"
			values.extend( [ table_to, table_to ] )

		command = self.cursor.mogrify( command, values )

		if self.debug :
			print( "* SELECT relationships:", command )

		self.cursor.execute( command )

		results = self.cursor.fetchall()

		# Tweak the returns so we can see what is related without having to know if it's on the left or the right!

		simple_results = []
		for result in results:
			# in some cases (i guess...) something could be related to itself, in this case the

			simple_result = dict(result)
			# Repeat the relation in own variables (otherwise it's not clear if you should take the right or left...)
			simple_result["table_name"] = result['left_table_name'] if table_from == result['right_table_name'] else result['right_table_name']
			simple_result["id_value"] = result['left_id_value'] if id_from == result['right_id_value'] else result['right_id_value']

			simple_results.append(simple_result)
			# simple_results.append( {
			# 	"relationship_id" : result['relationship_id'],
			#
			# 	"table_name" : result['left_table_name'] if table_from == result['right_table_name'] else result['right_table_name']
			# 	"id_value" : result['left_id_value'] if id_from == result['right_id_value'] else result['right_table_name'],
			#
			# 	"relationship_type" : result["relationship_type"]
			#
			# })

		return simple_results

	def update_person(self, person_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( person_id, field_updates, "cofk_union_person", "person_id", "person", print_sql, anonymous )


	def update_person_from_iperson(self, iperson_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( iperson_id, field_updates, "cofk_union_person", "iperson_id", "person", print_sql, anonymous )


	def update_work(self, work_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( work_id, field_updates, "cofk_union_work", "work_id", "work", print_sql, anonymous )


	def update_work_from_iwork(self, iwork_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( iwork_id, field_updates, "cofk_union_work", "iwork_id", "work", print_sql, anonymous )

	def update_manifestation(self, manifestation_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( manifestation_id, field_updates, "cofk_union_manifestation", "manifestation_id", "manifestation", print_sql, anonymous)


	def update_comment(self, comment_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( comment_id, field_updates, "cofk_union_comment", "comment_id", "comment", print_sql, anonymous )


	def update_resource(self, resource_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( resource_id, field_updates, "cofk_union_resource", "resource_id", "resource", print_sql, anonymous )


	def update_institution(self, institution_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( institution_id, field_updates, "cofk_union_institution", "institution_id", "institution", print_sql, anonymous)


	def update_image(self, image_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( image_id, field_updates, "cofk_union_image", "image_id", "image", print_sql, anonymous)


	def update_location(self, location_id, field_updates={}, print_sql=False, anonymous=False ):

		self._update( location_id, field_updates, "cofk_union_location", "location_id", "location", print_sql, anonymous)


	def _update( self, type_id, field_updates, update_table, where_field, type_name, print_sql=False, anonymous=False ):
		self.check_database_connection()

		# Create a list with all the data in.
		fields = list(field_updates.keys())
		data = []

		for field in fields :
			data.append( field_updates[field] )  # Ensuring order preserved.

		if not anonymous and "change_user" not in fields :
			fields.append( "change_user" )
			data.append( self.user )

		data.append( type_id )  # For where field

		# Create command
		command = "UPDATE " + update_table + " "
		command += "SET "

		count = 1
		for field in fields :
			command += field + "=%s"
			if count != len(fields) :
				command += ", "
			count += 1

		command += " WHERE " + where_field + "=%s"

		command = self.cursor.mogrify( command, data )

		if self.debug or print_sql:
			print( "* UPDATE " + type_name + ":", command )

		self.cursor.execute( command )

		self._audit_update( type_name )


	def delete_resource_via_resource_id( self, resource_id ):

		self.check_database_connection()

		command = "DELETE FROM cofk_union_resource WHERE resource_id=%s"
		command = self.cursor.mogrify( command, (resource_id,) )

		self._print_command( "DELETE resource", command )
		self._audit_delete("resource")

		self.cursor.execute( command )


	def delete_relationship_via_relationship_id( self, relationship_id ):

		self.check_database_connection()

		command = "DELETE FROM cofk_union_relationship WHERE relationship_id=%s"
		command = self.cursor.mogrify( command, (relationship_id,) )

		self._print_command( "DELETE relationship", command )
		self._audit_delete("relationship")

		self.cursor.execute( command )

	def delete_comment_via_comment_id( self, comment_id ):

		self.check_database_connection()

		command = "DELETE FROM cofk_union_comment WHERE comment_id=%s"
		command = self.cursor.mogrify( command, (comment_id,) )

		self._print_command( "DELETE comment", command )
		self._audit_delete("comment")

		self.cursor.execute( command )

	def create_resource(self, name, url, description="" ):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_resource" \
					" (resource_name,resource_url,resource_details,creation_user,change_user)" \
					" VALUES " \
					" ( %s,%s,%s,%s,%s)" \
					" returning resource_id"

		command = self.cursor.mogrify( command, ( name, url, description, self.user, self.user ) )

		self._print_command( "INSERT resource", command )
		self._audit_insert( "resource" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]


	def create_comment(self, comment ):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_comment" \
					" (comment,creation_user,change_user)" \
					" VALUES " \
					" ( %s,%s,%s)" \
					" returning comment_id"

		command = self.cursor.mogrify( command, ( comment, self.user, self.user ) )

		self._print_command( "INSERT comment", command )
		self._audit_insert( "comment" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]


	def create_image(self, filename, display_order, image_credits, can_be_displayed='Y', thumbnail=None ):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_image" \
					" (image_filename,display_order,credits,can_be_displayed,thumbnail,licence_url,creation_user,change_user)" \
					" VALUES " \
					" ( %s,%s,%s,%s,%s,%s,%s,%s)" \
					" returning image_id"

		command = self.cursor.mogrify( command, ( filename, display_order, image_credits, can_be_displayed, thumbnail, "http://cofk2.bodleian.ox.ac.uk/culturesofknowledge/licence/terms_of_use.html", self.user, self.user ) )

		self._print_command( "INSERT image", command )
		self._audit_insert( "image" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]

	def create_manifestation(self,
			manifestation_id,
			manifestation_type,
			printed_edition_details=None,
			id_number_or_shelfmark=None
		):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_manifestation" \
					" (manifestation_id,manifestation_type,id_number_or_shelfmark,printed_edition_details,creation_user,change_user)" \
					" VALUES " \
					" ( %s,%s,%s,%s,%s,%s)" \
					" returning manifestation_id"

		command = self.cursor.mogrify( command, (
			manifestation_id,
			manifestation_type,
			id_number_or_shelfmark,
			printed_edition_details,
			self.user,
			self.user ) )

		self._print_command( "INSERT manifestation", command )
		self._audit_insert( "manifestation" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]

	def create_relationship(self, left_name, left_id, relationship_type, right_name, right_id ):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_relationship" \
					" (left_table_name,left_id_value, relationship_type, right_table_name, right_id_value,creation_user,change_user)" \
					" VALUES " \
					" (%s, %s, %s, %s, %s,%s,%s)"\
					" returning relationship_id"

		command = self.cursor.mogrify( command, ( left_name, left_id, relationship_type, right_name, right_id, self.user, self.user ) )

		self._print_command( "INSERT relationship", command )
		self._audit_insert( "relationship" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]

	# Created / author / authored
	def create_relationship_created(self, person_id, work_id ):
		self.create_relationship('cofk_union_person', person_id, 'created', 'cofk_union_work', work_id )

	# addressed / sent
	def create_relationship_addressed_to(self, work_id, person_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'was_addressed_to', 'cofk_union_person', person_id )

	def create_relationship_mentions(self, work_id, person_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'mentions', 'cofk_union_person', person_id )

	# sent to / destination
	def create_relationship_was_sent_to(self, work_id, location_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'was_sent_to', 'cofk_union_location', location_id )

	# sent from / origin
	def create_relationship_was_sent_from(self, work_id, location_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'was_sent_from', 'cofk_union_location', location_id )

	def create_relationship_mentions_place(self, work_id, location_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'mentions_place', 'cofk_union_location', location_id )


	def create_relationship_work_resource(self, work_id, resource_id ):
		self.create_relationship( 'cofk_union_work', work_id, 'is_related_to', 'cofk_union_resource', resource_id )


	def create_relationship_note_on_work_route(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'route', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_date(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_date', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_author(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_author', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_origin(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_origin', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_destination(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_destination', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_generally(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_people_mentioned(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_people_mentioned_in_work', 'cofk_union_work', work_id )

	def create_relationship_note_on_work_addressee(self, comment_id, work_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to_addressee', 'cofk_union_work', work_id )


	def create_relationship_work_reply_to(self, work_reply_id, work_id ):
		self.create_relationship( 'cofk_union_work', work_reply_id, 'is_reply_to', 'cofk_union_work', work_id )

	def create_relationship_note_manifestation(self, comment_id, manifestation_id ):
		self.create_relationship( 'cofk_union_comment', comment_id, 'refers_to', 'cofk_union_manifestation', manifestation_id )

	def create_relationship_manifestation_in_repository(self, manifestation_id, repository_id ):
		self.create_relationship( 'cofk_union_manifestation', manifestation_id, 'stored_in', 'cofk_union_institution', repository_id )

	def create_relationship_manifestation_of_work(self, manifestation_id, work_id ):
		self.create_relationship( 'cofk_union_manifestation', manifestation_id, 'is_manifestation_of', 'cofk_union_work', work_id )


	def create_work(self, work_id_end,
			abstract=None,
			accession_code=None,
			addressees_as_marked=None,
			addressees_inferred=0,
			addressees_uncertain=0,
			authors_as_marked=None,
			authors_inferred=0,
			authors_uncertain=0,
			date_of_work2_std_day=None,
			date_of_work2_std_month=None,
			date_of_work2_std_year=None,
			date_of_work_approx=0,
			date_of_work_as_marked=None,
			date_of_work_inferred=0,
			date_of_work_std_day=None,
			date_of_work_std_is_range=0,
			date_of_work_std_month=None,
			date_of_work_std_year=None,
			date_of_work_uncertain=0,
			description=None,
			destination_as_marked=None,
			destination_inferred=0,
			destination_uncertain=0,
			edit_status='',
			editors_notes=None,
			explicit=None,
			incipit=None,
			keywords=None,
			language_of_work=None,
			origin_as_marked=None,
			origin_inferred=0,
			origin_uncertain=0,
			original_calendar=None,
			original_catalogue=None,
			ps=None,
			relevant_to_cofk='Y',
			work_is_translation=0,
			work_to_be_deleted=0
	):
		change_user = self.user
		creation_user = self.user

		work_id_base = "work_" + datetime.strftime( datetime.now(), "%Y%m%d%H%M%S%f" ) + "_" # e.g work_20181108182143954875_
		work_id = work_id_base + work_id_end

		addressees_inferred = self.get_int_value(addressees_inferred, 0)
		addressees_uncertain = self.get_int_value(addressees_uncertain, 0)

		authors_inferred = self.get_int_value(authors_inferred, 0)
		authors_uncertain = self.get_int_value(authors_uncertain, 0)

		date_of_work2_std_day = self.get_int_value(date_of_work2_std_day)
		date_of_work2_std_month = self.get_int_value(date_of_work2_std_month)
		date_of_work2_std_year = self.get_int_value(date_of_work2_std_year)

		date_of_work_std_day = self.get_int_value(date_of_work_std_day)
		date_of_work_std_month = self.get_int_value(date_of_work_std_month)
		date_of_work_std_year = self.get_int_value(date_of_work_std_year)

		date_of_work_approx = self.get_int_value(date_of_work_approx, 0)
		date_of_work_inferred = self.get_int_value(date_of_work_inferred, 0)
		date_of_work_std_is_range = self.get_int_value(date_of_work_std_is_range, 0)
		date_of_work_uncertain = self.get_int_value(date_of_work_uncertain, 0)

		destination_inferred = self.get_int_value(destination_inferred, 0)
		destination_uncertain = self.get_int_value(destination_uncertain, 0)

		origin_inferred = self.get_int_value(origin_inferred, 0)
		origin_uncertain = self.get_int_value(origin_uncertain, 0)

		work_is_translation = self.get_int_value(work_is_translation, 0)
		work_to_be_deleted = self.get_int_value(work_to_be_deleted, 0)

		date_of_work_std = self.get_date_string(date_of_work_std_year, date_of_work_std_month, date_of_work_std_day ),
		date_of_work_std_gregorian = date_of_work_std,

		command = "INSERT INTO cofk_union_work" \
				" (" \
					"abstract"\
					",accession_code"\
					",addressees_as_marked"\
					",addressees_inferred"\
					",addressees_uncertain"\
					",authors_as_marked"\
					",authors_inferred"\
					",authors_uncertain"\
					",change_user"\
					",creation_user"\
					",date_of_work2_std_day"\
					",date_of_work2_std_month"\
					",date_of_work2_std_year"\
					",date_of_work_approx"\
					",date_of_work_as_marked"\
					",date_of_work_inferred"\
					",date_of_work_std"\
					",date_of_work_std_day"\
					",date_of_work_std_gregorian"\
					",date_of_work_std_is_range"\
					",date_of_work_std_month"\
					",date_of_work_std_year"\
					",date_of_work_uncertain"\
					",description"\
					",destination_as_marked"\
					",destination_inferred"\
					",destination_uncertain"\
					",edit_status"\
					",editors_notes"\
					",explicit"\
					",incipit"\
					",keywords"\
					",language_of_work"\
					",origin_as_marked"\
					",origin_inferred"\
					",origin_uncertain"\
					",original_calendar"\
					",original_catalogue"\
					",ps"\
					",relevant_to_cofk"\
					",work_id"\
					",work_is_translation"\
					",work_to_be_deleted"\
				")" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			abstract,
			accession_code,
			addressees_as_marked,
			addressees_inferred,
			addressees_uncertain,
			authors_as_marked,
			authors_inferred,
			authors_uncertain,
			change_user,
			creation_user,
			date_of_work2_std_day,
			date_of_work2_std_month,
			date_of_work2_std_year,
			date_of_work_approx,
			date_of_work_as_marked,
			date_of_work_inferred,
			date_of_work_std,
			date_of_work_std_day,
			date_of_work_std_gregorian,
			date_of_work_std_is_range,
			date_of_work_std_month,
			date_of_work_std_year,
			date_of_work_uncertain,
			description,
			destination_as_marked,
			destination_inferred,
			destination_uncertain,
			edit_status,
			editors_notes,
			explicit,
			incipit,
			keywords,
			language_of_work,
			origin_as_marked,
			origin_inferred,
			origin_uncertain,
			original_calendar,
			original_catalogue,
			ps,
			relevant_to_cofk,
			work_id,
			work_is_translation,
			work_to_be_deleted
		) )

		self._print_command( "CREATE work", command )
		self._audit_insert( "work" )

		self.cursor.execute( command )

		return work_id

	def create_person_or_organisation(
			self,
			primary_name,
			synonyms=None,
			aliases=None,
			gender=None,
			is_org=None,
			org_type=None,
			birth_year=None,
			birth_month=None,
			birth_day=None,
			birth_inferred=0,
			birth_uncertain=0,
			birth_approx=0,
			death_year=None,
			death_month=None,
			death_day=None,
			death_inferred=0,
			death_uncertain=0,
			death_approx=0,
			flourished_year_1=None,
			flourished_year_2=None,
			flourished_range=0,
			editors_note=''
		):

		birth_year = self.get_int_value(birth_year)
		birth_month = self.get_int_value(birth_month)
		birth_day = self.get_int_value(birth_day)

		birth_approx = self.get_int_value(birth_approx, 0)
		birth_inferred = self.get_int_value(birth_inferred, 0)
		birth_uncertain = self.get_int_value(birth_uncertain, 0)

		death_year = self.get_int_value(death_year)
		death_month = self.get_int_value(death_month)
		death_day = self.get_int_value(death_day)

		death_approx = self.get_int_value(death_approx, 0)
		death_inferred = self.get_int_value(death_inferred, 0)
		death_uncertain = self.get_int_value(death_uncertain, 0)

		flourished_year_1 = self.get_int_value(flourished_year_1)
		flourished_year_2 = self.get_int_value(flourished_year_2)
		flourished_range = self.get_int_value(flourished_range, 0)

		## is_org = self.get_int_value(is_org, 0)
		if is_org == 1 or is_org == '1' or is_org == 'Y' or is_org == 'y' :
			is_org = 'Y'
		else :
			is_org = ''

		if is_org == 'Y' :
			org_type = self.get_int_value(org_type)

		if org_type == '' :
			org_type = None

		date_of_birth = None
		if birth_year or birth_month or birth_day :
			date_of_birth = self.get_date_string(birth_year, birth_month, birth_day )

		date_of_death = None
		if death_year or death_month or death_day :
			date_of_death = self.get_date_string(death_year, death_month, death_day )

		flourished = None
		if flourished_year_1 :
			flourished = self.get_date_string( flourished_year_1 )
		elif flourished_year_2 :
			flourished = self.get_date_string( flourished_year_2 )


		if synonyms :
			synonyms = "; ".join( synonyms.split("\n") )

		if aliases :
			aliases = "; ".join( aliases.split("\n") )

		if gender == 'm' or gender == 'M' :
			gender = 'M'
		elif gender == 'f' or gender == 'F' :
			gender = 'F'
		else :
			gender = ''

		self.check_database_connection()

		# Get next available ID.
		command = "select nextval('cofk_union_person_iperson_id_seq'::regclass);"
		self.cursor.execute( command )
		iperson_id = self.cursor.fetchone()[0]
		person_id = "cofk_union_person-iperson_id:000" + str(iperson_id)

		command = "INSERT INTO cofk_union_person" \
			" (" \
				"person_id,iperson_id,"\
				"foaf_name,skos_altlabel,person_aliases," \
				"date_of_birth_year,date_of_birth_month,date_of_birth_day," \
				"date_of_birth_inferred,date_of_birth_uncertain,date_of_birth_approx," \
				"date_of_birth," \
				"date_of_death_year,date_of_death_month,date_of_death_day," \
				"date_of_death_inferred,date_of_death_uncertain,date_of_death_approx," \
				"date_of_death," \
				"gender," \
				"is_organisation,organisation_type," \
				"flourished_year,flourished2_year,flourished_is_range," \
				"flourished," \
				"editors_notes," \
				"creation_user,change_user" \
			" )" \
			" VALUES " \
			" (" \
				"%s,%s," \
				"%s,%s,%s," \
				"%s,%s,%s," \
				"%s,%s,%s," \
				"%s," \
				"%s,%s,%s," \
				"%s,%s,%s," \
				"%s," \
				"%s," \
				"%s,%s," \
				"%s,%s,%s," \
				"%s," \
				"%s," \
				"%s,%s" \
			")" \
			" returning person_id"

		command = self.cursor.mogrify( command, (
			person_id,
			iperson_id,
			primary_name,
			synonyms,
			aliases,
			birth_year,
			birth_month,
			birth_year,
			birth_inferred,
			birth_uncertain,
			birth_approx,
			date_of_birth,
			death_year,
			death_month,
			death_year,
			death_inferred,
			death_uncertain,
			death_approx,
			date_of_death,
			gender,
			is_org,
			org_type,
			flourished_year_1,
			flourished_year_2,
			flourished_range,
			flourished,
			editors_note,
			self.user,
			self.user ) )

		self._print_command( "INSERT person", command )
		self._audit_insert( "person" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]

	def create_location(self,
						latitude=None,
						longitude=None,
						location_synonyms=None,
						editors_note=None,
						element_1_eg_room='',
						element_2_eg_building='',
						element_3_eg_parish='',
						element_4_eg_city='',
						element_5_eg_county='',
						element_6_eg_country='',
						element_7_eg_empire=''
					):

		location_list = []
		for value in [element_1_eg_room,
						element_2_eg_building,
						element_3_eg_parish,
						element_4_eg_city,
						element_5_eg_county,
						element_6_eg_country,
						element_7_eg_empire	] :
			if value is not None and value != '' :
				location_list.append(value)

		location_name = ", ".join(location_list)

		command = "INSERT INTO cofk_union_location" \
				" (" \
					"location_name," \
					"latitude,longitude," \
					"location_synonyms," \
					"element_1_eg_room,element_2_eg_building," \
					"element_3_eg_parish,element_4_eg_city," \
					"element_5_eg_county,element_6_eg_country," \
					"element_7_eg_empire," \
					"editors_notes," \
					"creation_user,change_user" \
				" )" \
				" VALUES " \
				" (" \
					"%s," \
					"%s,%s," \
					"%s," \
					"%s,%s," \
					"%s,%s," \
					"%s,%s," \
					"%s," \
					"%s," \
					"%s,%s" \
				")" \
				" returning location_id"

		command = self.cursor.mogrify( command, (
			location_name,
			latitude, longitude,
			location_synonyms,
			element_1_eg_room, element_2_eg_building,
			element_3_eg_parish, element_4_eg_city,
			element_5_eg_county, element_6_eg_country,
			element_7_eg_empire,
			editors_note,
			self.user,
			self.user ) )

		self._print_command( "INSERT location", command )
		self._audit_insert( "location" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]

	def get_languages_from_code(self, code ):
		### print( tweaker.get_languages_from_code( "lat;fra;eng" ) )
		### English, French, Latin
		###
		codes = code.split(";")
		where = []
		for code in codes :
			where.append( "code_639_3='" + code + "'")

		command = "SELECT language_name from  iso_639_language_codes" \
				" where " + " OR ".join( where )

		self.cursor.execute( command )

		languages = []
		for lang in self.cursor:
			languages.append( lang['language_name'] )

		return ", ".join(languages)


	@staticmethod
	def get_int_value(value, default=None):
		if value is not None and value != '' :
			return int(value)

		return default

	@staticmethod
	def get_date_string(year=None, month=None, day=None) :

		# Switch to numbers
		year = int(year) if year is not None else 9999
		month = int(month) if month is not None else 12

		if day is None :
			if month in [1, 3, 5, 7, 8, 10, 12] :
				day = 31
			elif month == 2 :
				day = 28  # should we look for leap years?
			else :
				day = 30

		year = str(year)

		if month < 10 :
			month = "0" + str(month)
		else :
			month = str(month)

		if day < 10 :
			day = "0" + str(day)
		else :
			day = str(day)

		return year + "-" + month + "-" + day


	def triggers_enable(self, table_name, triggers=[] ):

		command = "ALTER TABLE " + table_name + " "

		triggers_string = []
		for trigger in triggers :
			triggers_string.append( "ENABLE TRIGGER " + trigger )

		command += ",".join( triggers_string )
		command += ";"

		command = self.cursor.mogrify( command, ( table_name, ) )
		self._print_command( "ENABLE Triggers", command )
		self.cursor.execute( command )

	def triggers_disable(self, table_name, triggers=[] ):

		command = "ALTER TABLE " + table_name + " "

		triggers_string = []
		for trigger in triggers :
			triggers_string.append( "DISABLE TRIGGER " + trigger )

		command += ",".join( triggers_string )
		command += ";"

		command = self.cursor.mogrify( command, ( table_name, ) )
		self._print_command( "DISABLE Triggers", command )
		self.cursor.execute( command )


	def calendar_julian_to_calendar_gregorian(self, day, month, year ):
		# day = 1 to max_length
		# month = 1 to 12
		# year = a number...

		max_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]

		if year % 4 == 0:
			max_month[1] = 29

		# Julian calendar change
		diff_days = 10
		if year > 1700 :
			diff_days = 11
		elif year == 1700 and month > 2 :
			diff_days = 11
		elif year == 1700 and month == 2 and day == 29 :
			diff_days = 11

		# get new date
		new_day = day + diff_days
		new_month = month
		new_year = year
		if new_day > max_month[month-1] :
			new_day = new_day % max_month[month-1]
			new_month += 1
			if new_month > 12:
				new_month = 1
				new_year += 1

		return {
			"d" : new_day,
			"m": new_month,
			"y": new_year
		}


	def commit_changes( self, commit=False, quiet=False ):

		self.check_database_connection()

		if commit :
			self.connection.commit()
		else :
			self.connection.rollback()
			self._reset_audit()

		if not quiet :

			if commit:
				print( "Committing...", end="")
				print( "Done." )
			else :
				print( "NOT commiting... ", end="")
				print("Rolled back.")


	def check_database_connection(self):
		if not self.database_ok() :
			raise psycopg2.DatabaseError("Database not connected")


	def database_ok(self):
		return self.connection and self.cursor


	def print_audit(self, going_to_commit=True):
		print( "Audit:" )

		for deleting, number in iter( self.audit["deletions"].items() ) :
			if going_to_commit :
				print( "- Deleting " + str( number ) + " " + deleting + "(s)" )
			else :
				print( "- I would have deleted " + str( number ) + " " + deleting + "(s)" )

		if len( self.audit["deletions"] ) == 0 :
			print ( "- Nothing to delete")


		for inserting, number in iter( self.audit["insertions"].items() ) :
			if going_to_commit :
				print( "- Inserting " + str( number ) + " " + inserting + "(s)" )
			else :
				print( "- I would have inserted " + str( number ) + " " + inserting + "(s)" )

		if len( self.audit["insertions"] ) == 0 :
			print ( "- Nothing to insert")


		for updating, number in iter( self.audit["updates"].items() ) :
			if going_to_commit :
				print( "- Updating " + str( number ) + " " + updating + "(s)" )
			else :
				print( "- I would have updated " + str( number ) + " " + updating + "(s)" )

		if len( self.audit["updates"] ) == 0 :
			print ( "- Nothing to update")


		if not going_to_commit :
			print( "- Not commiting changes." )

	def _audit_update(self, updated):

		if updated not in self.audit["updates"] :
			self.audit["updates"][updated] = 1
		else :
			self.audit["updates"][updated] += 1

	def _audit_delete(self, deleted):

		if deleted not in self.audit["deletions"] :
			self.audit["deletions"][deleted] = 1
		else :
			self.audit["deletions"][deleted] += 1

	def _audit_insert(self, inserted):

		if inserted not in self.audit["insertions"] :
			self.audit["insertions"][inserted] = 1
		else :
			self.audit["insertions"][inserted] += 1

	def _print_command(self, name, command ):

		if self.debug :
			print( " *", name + ":", command )