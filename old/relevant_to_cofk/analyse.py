# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'sers0034'

import sys

import psycopg2
import psycopg2.extras

try:
	conn = psycopg2.connect("dbname='' user='' host='' password=''")
except:
	print( "I am unable to connect to the database" )
else:
	print( "Connected to database..." )


do_insert = True
do_commit = True


cur = conn.cursor(cursor_factory=psycopg2.extras.DictCursor)

command = "select iwork_id from cofk_union_work where relevant_to_cofk = 'N'"
command = cur.execute( command )

results = cur.fetchall()

print ( "iwork_id,relevant_to_cofk" )
for result in results :
	print ( str(result['iwork_id']) + ",N" )
