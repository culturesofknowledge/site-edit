# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import psycopg2
import psycopg2.extras

import csv_unicode


class DatabaseTweaker:

	def __init__( self, connection, debug=False ):

		self.debug = False
		self.set_debug(debug)

		self.connection = self.cursor = None
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

		command = "SELECT * FROM cofk_union_work WHERE iwork_id=%s"
		command = self.cursor.mogrify( command, (int(iwork_id),) )

		if self.debug :
			print( "* SELECT work:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()

	def get_resource_from_resource_id( self, resource_id ):

		command = "SELECT * FROM cofk_union_resource WHERE resource_id=%s"
		command = self.cursor.mogrify( command, (resource_id,) )

		if self.debug :
			print( "* SELECT resource:", command )

		self.cursor.execute( command )
		return self.cursor.fetchone()


	def get_relationships(self, id_from, table_from=None, table_to=None ):

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


	def update_work(self, iwork_id, field_updates={} ):

		# Create a list with all the data in.
		fields = field_updates.keys()
		data = []

		for field in fields :
			data.append( field_updates[field] )  # Ensuring order preserved.

		data.append( int(iwork_id) )

		# Create command
		command = "UPDATE cofk_union_work "
		command += "SET "

		count = 1
		for field in fields :
			command += field + "=%s"
			if count != len(fields) :
				command += ", "
			count += 1

		command += " WHERE iwork_id=%s"

		command = self.cursor.mogrify( command, data )

		if self.debug :
			print( "* UPDATE work:", command )

		self.cursor.execute( command )

		self._audit_update("work")


	def delete_resource_via_resource_id( self, resource_id ):

		command = "DELETE FROM cofk_union_resource WHERE resource_id=%s"
		command = self.cursor.mogrify( command, (resource_id,) )

		self._print_command( "DELETE resource", command )
		self._audit_delete("resource")

		self.cursor.execute( command )


	def delete_relationship_via_relationship_id( self, relationship_id ):

		command = "DELETE FROM cofk_union_relationship WHERE relationship_id=%s"
		command = self.cursor.mogrify( command, (relationship_id,) )

		self._print_command( "DELETE relationship", command )
		self._audit_delete("relationship")

		self.cursor.execute( command )


	def create_resource(self, name, url, description="" ):

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


	def create_relationship(self, left_name, left_id, relationship_type, right_name, right_id ):

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


	def commit_changes( self, commit=False ):

		if commit :
			print( "Committing...", end="")
			self.connection.commit()
			print( "Done." )



	def print_audit(self, going_to_commit=True):
		print( "Audit:" )

		for deleting, number in self.audit["deletions"].iteritems() :
			if going_to_commit :
				print( "- Deleting " + str( number ) + " " + deleting )
			else :
				print( "- I would have deleted " + str( number ) + " " + deleting )

		if len( self.audit["deletions"] ) == 0 :
			print ( "- Nothing to delete")


		for inserting, number in self.audit["insertions"].iteritems() :
			if going_to_commit :
				print( "- Inserting " + str( number ) + " " + inserting )
			else :
				print( "- I would have inserted " + str( number ) + " " + inserting )

		if len( self.audit["insertions"] ) == 0 :
			print ( "- Nothing to insert")


		for updating, number in self.audit["updates"].iteritems() :
			if going_to_commit :
				print( "- Updating " + str( number ) + " " + updating )
			else :
				print( "- I would have updated " + str( number ) + " " + updating )

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