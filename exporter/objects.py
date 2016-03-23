# -*- coding: utf-8 -*-

def get_work_fields() :
	return [
		"work_id",
		"description",
		"date_of_work_as_marked",
		"original_calendar",
		"date_of_work_std",
		"date_of_work_std",
		"date_of_work_std_year",
		"date_of_work_std_month",
		"date_of_work_std_day",
		"date_of_work2_std_year",
		"date_of_work2_std_month",
		"date_of_work2_std_day",
		"date_of_work_std_is_range",
		"date_of_work_inferred",
		"date_of_work_uncertain",
		"date_of_work_approx",
		"authors_as_marked",
		"addressees_as_marked",
		"authors_inferred",
		"authors_uncertain",
		"addressees_inferred",
		"addressees_uncertain",
		"destination_as_marked",
		"origin_as_marked",
		"destination_inferred",
		"destination_uncertain",
		"origin_inferred",
		"origin_uncertain",
		"abstract",
		"keywords",
		"language_of_work",
		"work_is_translation",
		"incipit",
		"explicit",
		"ps",
		"original_catalogue",
		"accession_code",
		"work_to_be_deleted",
		"iwork_id",
		"editors_notes",
		#"edit_status",
		#"relevant_to_cofk",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user"
	]

# o=object, f=field, r=relationship
def get_work_csv_converter() :

	translation = [
		{ "f" : "EMLO ID Number", "d" : { "o" : "work", "f" : "iwork_id" } },
		  { "f" : "Year date", "d" : { "o" : "work", "f" : "date_of_work_std_year"} },
		  { "f" : "Month date", "d" : { "o" : "work", "f" : "date_of_work_std_month"} },
		  { "f" : "Day date", "d" : { "o" : "work", "f" : "date_of_work_std_day"} },
		  { "f" : "Date is range", "d" : { "o" : "work", "f" : "date_of_work_std_is_range"} },
		  { "f" : "Year 2nd date (range)", "d" : { "o" : "work", "f" : "date_of_work2_std_year"} },
		  { "f" : "Month 2nd date (range)", "d" : { "o" : "work", "f" : "date_of_work2_std_month"} },
		  { "f" : "Day 2nd date (range)", "d" : { "o" : "work", "f" : "date_of_work2_std_day"} },
		  { "f" : "Calendar of date provided to EMLO", "d" : { "o" : "work", "f" : "original_calendar"} },
		  { "f" : "Date as marked on letter", "d" : { "o" : "work", "f" : "date_of_work_as_marked"} },
		  { "f" : "Date uncertain", "d" : { "o" : "work", "f" : "date_of_work_uncertain"} },
		  { "f" : "Date approximate", "d" : { "o" : "work", "f" : "date_of_work_approx"} },
		  { "f" : "Date inferred", "d" : { "o" : "work", "f" : "date_of_work_inferred"} },
		  { "f" : "Notes on date", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_date"} },

		  { "f" : "Author", "d" :  { "o" : "person", "f" : "foaf_name", "r" : "created" } },
		  { "f" : "Author ID", "d" : { "o" : "person", "f" : "iperson_id", "r" : "created" } },
		  { "f" : "Author as marked", "d" : { "o" : "work", "f" : "authors_as_marked"} },
		  { "f" : "Author inferred", "d" : { "o" : "work", "f" : "authors_inferred"} },
		  { "f" : "Author uncertain", "d" : { "o" : "work", "f" : "authors_uncertain"} },
		  { "f" : "Notes on Author re. Letter", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_author"} },
		  { "f" : "Recipient", "d" : { "o" : "person", "f" : "foaf_name", "r" : "was_addressed_to" } },
		  { "f" : "Recipient ID", "d" : { "o" : "person", "f" : "iperson_id", "r" : "was_addressed_to" } },
		  { "f" : "Recipient as marked", "d" : { "o" : "work", "f" : "addressees_as_marked"} },
		  { "f" : "Recipient inferred", "d" : { "o" : "work", "f" : "addressees_inferred"} },
		  { "f" : "Recipient uncertain", "d" : { "o" : "work", "f" : "addressees_uncertain"} },
		  { "f" : "Notes on Recipient re. Letter", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_addressee"} },

		  { "f" : "Origin name", "d" : { "o" : "location", "f" : "location_name","r" : "was_sent_from" } },
		  { "f" : "Origin ID", "d" : { "o" : "location", "f" : "location_id","r" : "was_sent_from" } },
		  { "f" : "Origin as marked", "d" : { "o" : "work", "f" : "origin_as_marked"} },
		  { "f" : "Origin inferred", "d" : { "o" : "work", "f" : "origin_inferred"} },
		  { "f" : "Origin uncertain", "d" : { "o" : "work", "f" : "origin_uncertain"} },
		  { "f" : "Destination name", "d" : { "o" : "location", "f" : "location_name","r" : "was_sent_to" } },
		  { "f" : "Destination ID", "d" : { "o" : "location", "f" : "location_id","r" : "was_sent_to" } },
		  { "f" : "Destination as marked", "d" : { "o" : "work", "f" : "destination_as_marked"} },
		  { "f" : "Destination inferred", "d" : { "o" : "work", "f" : "destination_inferred"} },
		  { "f" : "Destination uncertain", "d" : { "o" : "work", "f" : "destination_uncertain"} },

		  { "f" : "Abstract", "d" : { "o" : "work", "f" : "abstract"} },
		  { "f" : "Keywords", "d" : { "o" : "work", "f" : "keywords"} },
		  { "f" : "Language(s)", "d" : { "o" : "work", "f" : "language_of_work"} },
		  { "f" : "Incipit", "d" : { "o" : "work", "f" : "incipit"} },
		  { "f" : "Explicit", "d" : { "o" : "work", "f" : "explicit"} },

		# Complicated...
		#{ "f" : "People mentioned", "d" : { "o" : "work", "f" : ""} },
		#{ "f" : "EMLO ID people mentioned", "d" : { "o" : "work", "f" : ""} },
		#{ "f" : "Notes on people mentioned", "d" : { "o" : "work", "f" : ""} },

		# Ignoring as would include other works (complicated work connections...)
		#{ "f" : "Letter in reply to", "d" : { "o" : "work", "f" : ""} },
		#{ "f" : "Letter answered by", "d" : { "o" : "work", "f" : ""} },
		#{ "f" : "Matching letter(s) in alternative EMLO catalogue(s)", "d" : { "o" : "work", "f" : ""} },

		# This will be a separate table... some how...
		#{ "f" : "Related Resource descriptor", "d" : { "o" : "work", "f" : ""} },
		#{ "f" : "Related Resource URL", "d" : { "o" : "work", "f" : ""} },
		  { "f" : "General notes for public display", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to"} },
		  { "f" : "Editors’ notes", "d" : { "o" : "work", "f" : "editors_notes"} },

	]

	return translation

def get_person_fields() :

	fields = [
		"person_id",
		"foaf_name",
		"skos_altlabel",
		"skos_hiddenlabel",
		"person_aliases",
		"date_of_birth_year",
		"date_of_birth_month",
		"date_of_birth_day",
		"date_of_birth",
		"date_of_birth_inferred",
		"date_of_birth_uncertain",
		"date_of_birth_approx",
		"date_of_death_year",
		"date_of_death_month",
		"date_of_death_day",
		"date_of_death",
		"date_of_death_inferred",
		"date_of_death_uncertain",
		"date_of_death_approx",
		"gender",
		"is_organisation",
		"iperson_id",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"editors_notes",
		"further_reading",
		"organisation_type",
		"date_of_birth_calendar",
		"date_of_birth_is_range",
		"date_of_birth2_year",
		"date_of_birth2_month",
		"date_of_birth2_day",
		"date_of_death_calendar",
		"date_of_death_is_range",
		"date_of_death2_year",
		"date_of_death2_month",
		"date_of_death2_day",
		"flourished",
		"flourished_calendar",
		"flourished_is_range",
		"flourished_year",
		"flourished_month",
		"flourished_day",
		"flourished2_year",
		"flourished2_month",
		"flourished2_day",
	]

	return fields


def get_location_fields() :
	fields = [
		"location_id",
		"location_name",
		"latitude",
		"longitude",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"location_synonyms",
		"editors_notes",
		"element_1_eg_room",
		"element_2_eg_building",
		"element_3_eg_parish",
		"element_4_eg_city",
		"element_5_eg_county",
		"element_6_eg_country",
		"element_7_eg_empire"
	]

	return fields

def get_comment_fields() :
	fields = [
		"comment_id",
		"comment",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user"
	]

	return fields