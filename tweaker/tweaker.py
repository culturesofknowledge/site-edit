# -*- coding: utf-8 -*-
from __future__ import print_function
import sys

import psycopg2
import psycopg2.extras

import csv_unicode


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

		self.audit = {
			"deletions" : {},
			"insertions" : {},
			"updates" : {}
		}

		self.user = "CokBot"

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
		csv_file = csv_unicode.UnicodeReader( open( filename, "rb" ) )

		rows = []
		names = None

		for row in csv_file:

			if names is None:
				names = [name.strip() for name in row]
			else:
				rows.append( dict( zip( names, [item.strip() for item in row] ) ) )

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

	def get_comment_from_comment_id( self, comment_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_comment WHERE comment_id=%s"
		command = self.cursor.mogrify( command, (comment_id,) )

		if self.debug :
			print( "* SELECT comment:", command )

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

	def get_manifestation_from_manifestation_id( self, manifestation_id ):

		self.check_database_connection()

		command = "SELECT * FROM cofk_union_manifestation WHERE manifestation_id=%s"
		command = self.cursor.mogrify( command, (manifestation_id,) )

		if self.debug :
			print( "* SELECT manifestation:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()


	def get_relationships(self, id_from, table_from=None, table_to=None ):

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

	def update_person(self, iperson_id, field_updates={}, print_sql=False ):

		self._update( iperson_id, field_updates, "cofk_union_person", "iperson_id", "person", print_sql )


	def update_work(self, iwork_id, field_updates={}, print_sql=False ):

		self._update( iwork_id, field_updates, "cofk_union_work", "iwork_id", "work", print_sql )

		# self.check_database_connection()
		#
		# # Create a list with all the data in.
		# fields = field_updates.keys()
		# data = []
		#
		# for field in fields :
		# 	data.append( field_updates[field] )  # Ensuring order preserved.
		#
		# data.append( int(iwork_id) )
		#
		# # Create command
		# command = "UPDATE cofk_union_work "
		# command += "SET "
		#
		# count = 1
		# for field in fields :
		# 	command += field + "=%s"
		# 	if count != len(fields) :
		# 		command += ", "
		# 	count += 1
		#
		# command += " WHERE iwork_id=%s"
		#
		# command = self.cursor.mogrify( command, data )
		#
		# if self.debug or print_sql:
		# 	print( "* UPDATE work:", command )
		#
		# self.cursor.execute( command )
		#
		# self._audit_update("work")


	def update_manifestation(self, manifestation_id, field_updates={} ):

		self._update( manifestation_id, field_updates, "cofk_union_manifestation", "manifestation_id", "manifestation")

		# self.check_database_connection()
		#
		# # Create a list with all the data in.
		# fields = field_updates.keys()
		# data = []
		#
		# for field in fields :
		# 	data.append( field_updates[field] )  # Ensuring order preserved.
		#
		# data.append( manifestation_id )
		#
		# # Create command
		# command = "UPDATE cofk_union_manifestation "
		# command += "SET "
		#
		# count = 1
		# for field in fields :
		# 	command += field + "=%s"
		# 	if count != len(fields) :
		# 		command += ", "
		# 	count += 1
		#
		# command += " WHERE manifestation_id=%s"
		#
		# command = self.cursor.mogrify( command, data )
		#
		# if self.debug :
		# 	print( "* UPDATE manifestation:", command )
		#
		# self.cursor.execute( command )
		#
		# self._audit_update("manifestation")


	def update_comment(self, comment_id, field_updates={} ):

		self._update( comment_id, field_updates, "cofk_union_comment", "comment_id", "comment" )

		# self.check_database_connection()
		#
		# # Create a list with all the data in.
		# fields = field_updates.keys()
		# data = []
		#
		# for field in fields :
		# 	data.append( field_updates[field] )  # Ensuring order preserved.
		#
		# data.append( comment_id )
		#
		# # Create command
		# command = "UPDATE cofk_union_comment "
		# command += "SET "
		#
		# count = 1
		# for field in fields :
		# 	command += field + "=%s"
		# 	if count != len(fields) :
		# 		command += ", "
		# 	count += 1
		#
		# command += " WHERE comment_id=%s"
		#
		# command = self.cursor.mogrify( command, data )
		#
		# if self.debug :
		# 	print( "* UPDATE comment:", command )
		#
		# self.cursor.execute( command )
		#
		# self._audit_update("comment")

	def update_resource(self, resource_id, field_updates={} ):

		self._update( resource_id, field_updates, "cofk_union_resource", "resource_id", "resource" )

	def _update( self, type_id, field_updates, update_table, where_field, type_name, print_sql=False ):
		self.check_database_connection()

		# Create a list with all the data in.
		fields = field_updates.keys()
		data = []

		for field in fields :
			data.append( field_updates[field] )  # Ensuring order preserved.

		if "change_user" not in fields :
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

	def create_manifestation(self, manifestation_id, manifestation_type, printed_edition_details ):

		self.check_database_connection()

		command = "INSERT INTO cofk_union_manifestation" \
					" (manifestation_id,manifestation_type,printed_edition_details,creation_user,change_user)" \
					" VALUES " \
					" ( %s,%s,%s,%s,%s)" \
					" returning manifestation_id"

		command = self.cursor.mogrify( command, (
			manifestation_id,
			manifestation_type,
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

		is_org = self.get_int_value(is_org, 0)
		org_type = self.get_int_value(org_type)


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

	def get_int_value( self, value, default=None ):
		if value is not None and value != '' :
			return int(value)

		return default

	def get_date_string( self, year=None, month=None, day=None ) :

		year = year or 9999
		month = month or 12
		day = day or 31

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


	def commit_changes( self, commit=False ):

		self.check_database_connection()

		if commit :
			print( "Committing...", end="")
			self.connection.commit()
			print( "Done." )
		else :
			print( "Ordered NOT to commit anything")


	def check_database_connection(self):
		if not self.database_ok() :
			raise psycopg2.DatabaseError("Database not connected")

	def database_ok(self):
		return self.connection and self.cursor


	def print_audit(self, going_to_commit=True):
		print( "Audit:" )

		for deleting, number in self.audit["deletions"].iteritems() :
			if going_to_commit :
				print( "- Deleting " + str( number ) + " " + deleting + "(s)" )
			else :
				print( "- I would have deleted " + str( number ) + " " + deleting + "(s)" )

		if len( self.audit["deletions"] ) == 0 :
			print ( "- Nothing to delete")


		for inserting, number in self.audit["insertions"].iteritems() :
			if going_to_commit :
				print( "- Inserting " + str( number ) + " " + inserting + "(s)" )
			else :
				print( "- I would have inserted " + str( number ) + " " + inserting + "(s)" )

		if len( self.audit["insertions"] ) == 0 :
			print ( "- Nothing to insert")


		for updating, number in self.audit["updates"].iteritems() :
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