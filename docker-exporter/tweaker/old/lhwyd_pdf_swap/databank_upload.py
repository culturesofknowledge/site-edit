# -*- coding: utf-8 -*-
from __future__ import print_function

__author__ = 'matthew'

"""
Uploading PDFs to databank

Run from "Database Tweak" folder with command: python -m lhwyd_pdf_swap.databank_upload.py
"""

import os.path
from tweaker import tweaker

csv_file = "lhwyd_pdf_swap/FINAL(v.2)_LhwydTranscriptions_WorkIDs_Descriptors_Files_ 2015.8.10-1.csv"
local_file_folder = "/data/temp/LhywdTranscripts"

tweaker = tweaker.DatabaseTweaker( )

rows = tweaker.get_csv_data( csv_file )

resources = []

count_all = 0
count_missing = 0


for row in rows:

	if count_all == 0:
		print( ",".join( row.keys() ) )

	count_all += 1
	path = os.path.join( local_file_folder, row['resource_url'] )
	if not os.path.exists( path ) :
		count_missing += 1


		print( ",".join( [ row[key] for key in row ] ) )

#		print( path + " does not exists!" )

#print ("Missing: " + str( count_missing ) + " all: " + str( count_all ))
#print ("Done")




