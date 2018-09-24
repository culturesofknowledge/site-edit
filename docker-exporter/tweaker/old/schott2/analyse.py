# -*- coding: utf-8 -*-
from __future__ import print_function, unicode_literals

__author__ = 'matthew'

"""
Looking for PDFs associated with works

Run from "Database Tweak" folder with command: python -m schott_transcripts_swap.analyse

"""

from tweaker import tweaker

csv_file = "schott2/Schott_UPLOAD_Details_2016.4.27.csv"
id_column="id"


tweaker = tweaker.DatabaseTweaker( )

rows = tweaker.get_csv_data( csv_file )

resources = []

print( "iwork_id,resource_name,resource_url" )
for row in rows:

    iwork_id = row[id_column]
    work = tweaker.get_work_from_iwork_id( iwork_id )

    relationships = tweaker.get_relationships( work['work_id'], "cofk_union_work", "cofk_union_resource" )
    # print(len(relationships),relationships)

    pdf_found = []
    transcript_found = False

    for rel in relationships:

        resource = tweaker.get_resource_from_resource_id( rel["id_value"] )

        if resource["resource_url"].find(".pdf") != -1 :
            pdf_found.append( resource["resource_url"] )

            #if resource["resource_name"].find("Transcript") != -1:
            #    transcript_found = True

            print( iwork_id,',"', resource["resource_name"],'","',resource["resource_url"],'"', sep='' )


    # if len(pdf_found) == 0 :
    #     pass  # print ( "Work id " + iwork_id + " does not have a pdf " + "https://emlo-edit.bodleian.ox.ac.uk/interface/union.php?iwork_id=" + iwork_id)
    # else :
    #     if len(pdf_found) > 1 :
    #         print ( "Work id " + iwork_id + " has " + str(len(pdf_found)) + " pdfs " + "https://emlo-edit.bodleian.ox.ac.uk/interface/union.php?iwork_id=" + iwork_id )
    #         for pdf in pdf_found :
    #             print( pdf )
    #
    #     if not transcript_found :
    #         print ( "Work id " + iwork_id + " does not have \"Transcript\" " + "https://emlo-edit.bodleian.ox.ac.uk/interface/union.php?iwork_id=" + iwork_id )

