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

	translation = {
		"EMLO ID Number" : { "o" : "work", "f" : "iwork_id" },
		"Year date" : { "o" : "work", "f" : "date_of_work_std_year"},
		"Month date" : { "o" : "work", "f" : "date_of_work_std_month"},
		"Day date" : { "o" : "work", "f" : "date_of_work_std_day"},
		"Date is range" : { "o" : "work", "f" : "date_of_work_std_is_range"},
		"Year 2nd date (range)" : { "o" : "work", "f" : "date_of_work2_std_year"},
		"Month 2nd date (range)" : { "o" : "work", "f" : "date_of_work2_std_month"},
		"Day 2nd date (range)" : { "o" : "work", "f" : "date_of_work2_std_day"},
		"Calendar of date provided to EMLO" : { "o" : "work", "f" : "original_calendar"},
		"Date as marked on letter" : { "o" : "work", "f" : "date_of_work_as_marked"},
		"Date uncertain" : { "o" : "work", "f" : "date_of_work_uncertain"},
		"Date approximate" : { "o" : "work", "f" : "date_of_work_approx"},
		"Date inferred" : { "o" : "work", "f" : "date_of_work_inferred"},
		"Notes on date" : { "o" : "comment", "f" : "comment", "r" : "refers_to_date"},

		"Author" :  { "o" : "person", "f" : "foaf_name", "r" : "created" },
		"Author ID" : { "o" : "person", "f" : "iperson_id", "r" : "created" },
		"Author as marked" : { "o" : "work", "f" : "authors_as_marked"},
		"Author inferred" : { "o" : "work", "f" : "authors_inferred"},
		"Author uncertain" : { "o" : "work", "f" : "authors_uncertain"},
		"Notes on Author re. Letter" : { "o" : "comment", "f" : "comment", "r" : "refers_to_author"},
		"Recipient" : { "o" : "person", "f" : "foaf_name", "r" : "was_addressed_to" },
		"Recipient ID" : { "o" : "person", "f" : "iperson_id", "r" : "was_addressed_to" },
		"Recipient as marked" : { "o" : "work", "f" : "addressees_as_marked"},
		"Recipient inferred" : { "o" : "work", "f" : "addressees_inferred"},
		"Recipient uncertain" : { "o" : "work", "f" : "addressees_uncertain"},
		"Notes on Recipient re. Letter" : { "o" : "comment", "f" : "comment", "r" : "refers_to_addressee"},

		"Origin name" : { "o" : "location", "f" : "location_name","r" : "was_sent_from" },
		"Origin ID" : { "o" : "location", "f" : "location_id","r" : "was_sent_from" },
		"Origin as marked" : { "o" : "work", "f" : "origin_as_marked"},
		"Origin inferred" : { "o" : "work", "f" : "origin_inferred"},
		"Origin uncertain" : { "o" : "work", "f" : "origin_uncertain"},
		"Destination name" : { "o" : "location", "f" : "location_name","r" : "was_sent_to" },
		"Destination ID" : { "o" : "location", "f" : "location_id","r" : "was_sent_to" },
		"Destination as marked" : { "o" : "work", "f" : "destination_as_marked"},
		"Destination inferred" : { "o" : "work", "f" : "destination_inferred"},
		"Destination uncertain" : { "o" : "work", "f" : "destination_uncertain"},

		"Abstract" : { "o" : "work", "f" : "abstract"},
		"Keywords" : { "o" : "work", "f" : "keywords"},
		"Language(s)" : { "o" : "work", "f" : "language_of_work"},
		"Incipit" : { "o" : "work", "f" : "incipit"},
		"Explicit" : { "o" : "work", "f" : "explicit"},

		# Complicated...
		#"People mentioned" : { "o" : "work", "f" : ""},
		#"EMLO ID people mentioned" : { "o" : "work", "f" : ""},
		#"Notes on people mentioned" : { "o" : "work", "f" : ""},

		# Ignoring as would include other works (complicated work connections...)
		#"Letter in reply to" : { "o" : "work", "f" : ""},
		#"Letter answered by" : { "o" : "work", "f" : ""},
		#"Matching letter(s) in alternative EMLO catalogue(s)" : { "o" : "work", "f" : ""},

		# This will be a separate table... some how...
		#"Related Resource descriptor" : { "o" : "work", "f" : ""},
		#"Related Resource URL" : { "o" : "work", "f" : ""},
		"General notes for public display" : { "o" : "work", "f" : ""},
		"Editors’ notes" : { "o" : "work", "f" : "editors_notes"},

	}

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