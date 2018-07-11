# -*- coding: utf-8 -*-

def get_work_fields() :
	return [
		"work_id",
		"description",
		"date_of_work_as_marked",
		"original_calendar",
		"date_of_work_std",
		"date_of_work_std_gregorian",
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
		"uuid"
		#"edit_status",
		#"relevant_to_cofk",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user"
	]

# o=object, f=field, r=relationship
def get_work_csv_converter() :

	return [
		{ "f" : u"EMLO Letter ID Number",       "d" : { "o" : "work", "f" : "iwork_id" } },

		{ "f" : u"Year date",                   "d" : { "o" : "work", "f" : "date_of_work_std_year"} },
		{ "f" : u"Month date",                  "d" : { "o" : "work", "f" : "date_of_work_std_month"} },
		{ "f" : u"Day date",                    "d" : { "o" : "work", "f" : "date_of_work_std_day"} },
		{ "f" : u"Standard gregorian date",     "d" : { "o" : "work", "f" : "date_of_work_std_gregorian"} },
		{ "f" : u"Date is range (0=No; 1=Yes)", "d" : { "o" : "work", "f" : "date_of_work_std_is_range"} },
		{ "f" : u"Year 2nd date (range)",       "d" : { "o" : "work", "f" : "date_of_work2_std_year"} },
		{ "f" : u"Month 2nd date (range)",      "d" : { "o" : "work", "f" : "date_of_work2_std_month"} },
		{ "f" : u"Day 2nd date (range)",        "d" : { "o" : "work", "f" : "date_of_work2_std_day"} },
		{ "f" : u"Calendar of date provided "
				u"to EMLO (G=Gregorian; "
				u"JJ=Julian, year start 1 "
				u"January; JM=Julian, year "
				u"start March, U=Unknown)",         "d" : { "o" : "work", "f" : "original_calendar"} },
		{ "f" : u"Date as marked on letter",        "d" : { "o" : "work", "f" : "date_of_work_as_marked"} },
		{ "f" : u"Date uncertain (0=No; 1=Yes)",    "d" : { "o" : "work", "f" : "date_of_work_uncertain"} },
		{ "f" : u"Date approximate (0=No; 1=Yes)",  "d" : { "o" : "work", "f" : "date_of_work_approx"} },
		{ "f" : u"Date inferred (0=No; 1=Yes)",     "d" : { "o" : "work", "f" : "date_of_work_inferred"} },
		{ "f" : u"Notes on date",                   "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_date"} },

		{ "f" : u"Author",                          "d" :  { "o" : "person", "f" : "foaf_name", "r" : "created" } },
		{ "f" : u"Author EMLO ID",                  "d" : { "o" : "person", "f" : "iperson_id", "r" : "created" } },
		{ "f" : u"Author as marked in body/text "
				u"of letter",                       "d" : { "o" : "work", "f" : "authors_as_marked"} },
		{ "f" : u"Author inferred (0=No; 1=Yes)",   "d" : { "o" : "work", "f" : "authors_inferred"} },
		{ "f" : u"Author uncertain (0=No; 1=Yes)",  "d" : { "o" : "work", "f" : "authors_uncertain"} },
		{ "f" : u"Notes on Author in relation "
				u"to letter",                       "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_author"} },
		{ "f" : u"Recipient",                       "d" : { "o" : "person", "f" : "foaf_name", "r" : "was_addressed_to" } },
		{ "f" : u"Recipient EMLO ID",               "d" : { "o" : "person", "f" : "iperson_id", "r" : "was_addressed_to" } },
		{ "f" : u"Recipient as marked in body/text "
				u"of letter",                       "d" : { "o" : "work", "f" : "addressees_as_marked"} },
		{ "f" : u"Recipient inferred "
				u"(0=No; 1=Yes)",                   "d" : { "o" : "work", "f" : "addressees_inferred"} },
		{ "f" : u"Recipient uncertain "
				u"(0=No; 1=Yes)",                   "d" : { "o" : "work", "f" : "addressees_uncertain"} },
		{ "f" : u"Notes on Recipient in "
				u"relation to letter",              "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_addressee"} },

		{ "f" : u"Origin name",                     "d" : { "o" : "location", "f" : "location_name", "r" : "was_sent_from" } },
		{ "f" : u"Origin EMLO ID",                  "d" : { "o" : "location", "f" : "location_id", "r" : "was_sent_from" } },
		{ "f" : u"Origin as marked in body/text "
				u"of letter",                       "d" : { "o" : "work", "f" : "origin_as_marked"} },
		{ "f" : u"Origin inferred (0=No; 1=Yes)",   "d" : { "o" : "work", "f" : "origin_inferred"} },
		{ "f" : u"Origin uncertain (0=No; 1=Yes)",  "d" : { "o" : "work", "f" : "origin_uncertain"} },
		{ "f" : u"Notes on Origin in relation "
				u"to letter",                       "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_origin"} },
		{ "f" : u"Destination name",                "d" : { "o" : "location", "f" : "location_name", "r" : "was_sent_to" } },
		{ "f" : u"Destination EMLO ID",             "d" : { "o" : "location", "f" : "location_id", "r" : "was_sent_to" } },
		{ "f" : u"Destination as marked in "
				u"body/text of letter",             "d" : { "o" : "work", "f" : "destination_as_marked"} },
		{ "f" : u"Destination inferred "
				u"(0=No; 1=Yes)",                   "d" : { "o" : "work", "f" : "destination_inferred"} },
		{ "f" : u"Destination uncertain "
				u"(0=No; 1=Yes)",                   "d" : { "o" : "work", "f" : "destination_uncertain"} },
		{ "f" : u"Notes on Destination in "
				u"relation to letter",              "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_destination"} },

		{ "f" : u"Abstract",                        "d" : { "o" : "work", "f" : "abstract"} },
		{ "f" : u"Keywords",                        "d" : { "o" : "work", "f" : "keywords"} },
		{ "f" : u"Language(s)",                     "d" : { "o" : "work", "f" : "language_of_work"} },
		{ "f" : u"Incipit",                         "d" : { "o" : "work", "f" : "incipit"} },
		{ "f" : u"Explicit",                        "d" : { "o" : "work", "f" : "explicit"} },

		{ "f" : u"People mentioned",                "d" : { "o" : "person", "f" : "foaf_name", "r" : "mentions" } },
		{ "f" : u"EMLO IDs of people mentioned",    "d" : { "o" : "person", "f" : "iperson_id", "r" : "mentions" } },
		{ "f" : u"Notes on people mentioned",       "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to_people_mentioned_in_work"} },

		{ "f" : u"Original Catalogue name",         "d" : { "o" : "work", "f" : "original_catalogue" } },

		# Ignoring as would include other works (complicated work connections...)
		#{ "f" : "Letter in reply to", "d" : { "o" : "work-rel", "f" : "iwork_id", "r" : "" } },
		#{ "f" : "Letter answered by", "d" : { "o" : "work-rel", "f" : "iwork_id", "r" : "is_reply_to" } },

		{ "f" : "Matching letter(s) in alternative EMLO catalogue(s) (self reference also)", "d" : { "o" : "work-rel", "f" : "iwork_id", "r" : "matches" } },
		{ "f" : "Match id number", "d" : {} },

		# This will be a separate table... some how...
		# { "f" : "Related Resource descriptor", "d" : { "o" : "work", "f" : ""} },
		# { "f" : "Related Resource URL", "d" : { "o" : "work", "f" : ""} },

		{ "f" : u"Related Resource IDs "
				u"[er = number for link "
				u"to EMLO letter]",                 "d" : { "o" : "resource", "f" : "resource_id", "r" : "is_related_to"} },
		{ "f" : u"General notes for public "
				u"display",                         "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to"} },
		{ "f" : u"Editors' working notes",          "d" : { "o" : "work", "f" : "editors_notes"} },
		{ "f" : u"UUID",                            "d" : { "o" : "work", "f" : "uuid" } },
		{ "f" : u"EMLO URL",                        "d" : {} },
	]

def get_person_fields() :

	return [
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
		"uuid"
	]


# o=object, f=field, r=relationship
def get_person_csv_converter() :

	return [
		{ "f" : "EMLO Person ID", "d" : { "o" : "person", "f" : "iperson_id" } },
		{ "f" : "Person primary name in EMLO", "d" : { "o" : "person", "f" : "foaf_name" } },
		{ "f" : "Synonyms", "d" : { "o" : "person", "f" : "skos_altlabel" } },
		# { "f" : "Synonyms Other", "d" : { "o" : "person", "f" : "skos_hiddenlabel" } },
		{ "f" : "Roles/Titles", "d" : { "o" : "person", "f" : "person_aliases" } },
		{ "f" : "Gender", "d" : { "o" : "person", "f" : "gender" } },
		{ "f" : "Is Organization (Y=yes;black=no)", "d" : { "o" : "person", "f" : "is_organisation" } },
		{ "f" : "Birth year", "d" : { "o" : "person", "f" : "date_of_birth_year" } },
		{ "f" : "Death year", "d" : { "o" : "person", "f" : "date_of_death_year" } },
		{ "f" : "Fl. year 1", "d" : { "o" : "person", "f" : "flourished_year" } },
		{ "f" : "Fl. year 2", "d" : { "o" : "person", "f" : "flourished2_year" } },
		{ "f" : "Fl. year is range (0=No; 1=Yes)", "d" : { "o" : "person", "f" : "flourished_is_range" } },
		{ "f" : "General notes on person", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to"} },
		{ "f" : "Editors' working notes", "d" : { "o" : "person", "f" : "editors_notes" } },
		#{ "f" : "Related Resource Name(s)", "d" : { "o" : "person", "f" : "" } },
		#{ "f" : "Related Resource URL(s)", "d" : { "o" : "person", "f" : "" } },
		{ "f" : "Related Resource IDs", "d" : { "o" : "resource", "f" : "resource_id", "r" : "is_related_to"} },
		{ "f" : u"UUID", "d" : { "o" : "person", "f" : "uuid" } },
		{ "f" : u"EMLO URL",                        "d" : {} },
	]


def get_location_fields() :
	return [
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
		"element_7_eg_empire",
		"uuid"
	]


# o=object, f=field, r=relationship
def get_location_csv_converter() :

	return [
		{ "f" : "Place ID", "d" : { "o" : "location", "f" : "location_id" } },
		{ "f" : "Place name", "d" : { "o" : "location", "f" : "location_name" } },
		{ "f" : "Room", "d" : { "o" : "location", "f" : "element_1_eg_room" } },
		{ "f" : "Building", "d" : { "o" : "location", "f" : "element_2_eg_building" } },
		{ "f" : "Street or parish", "d" : { "o" : "location", "f" : "element_3_eg_parish" } },
		{ "f" : "Primary place name (city, town, village)", "d" : { "o" : "location", "f" : "element_4_eg_city" } },
		{ "f" : "County, State, or Province", "d" : { "o" : "location", "f" : "element_5_eg_county" } },
		{ "f" : "Country", "d" : { "o" : "location", "f" : "element_6_eg_country" } },
		{ "f" : "Place name synonyms", "d" : { "o" : "location", "f" : "location_synonyms" } },
		{ "f" : "Coordinates: Latitude", "d" : { "o" : "location", "f" : "latitude" } },
		{ "f" : "Coordinates: Longitude", "d" : { "o" : "location", "f" : "longitude" } },
		#{ "f" : "Related resource name", "d" : { "o" : "location", "f" : "" } },
		#{ "f" : "Related resource URL", "d" : { "o" : "location", "f" : "" } },
		{ "f" : "Related Resource IDs", "d" : { "o" : "resource", "f" : "resource_id", "r" : "is_related_to"} },
		{ "f" : "General notes on place", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to" } },
		{ "f" : "Editors' working notes", "d" : { "o" : "location", "f" : "editors_notes" } },
		{ "f" : "UUID", "d" : { "o" : "location", "f" : "uuid" } },
		{ "f" : u"EMLO URL",                        "d" : {} },

	]


def get_manifestation_fields() :
	return [
		"manifestation_id",
		"manifestation_type",
		"id_number_or_shelfmark",
		"printed_edition_details",
		"paper_size",
		"paper_type_or_watermark",
		"number_of_pages_of_document",
		"number_of_pages_of_text",
		"seal",
		"postage_marks",
		"endorsements",
		"non_letter_enclosures",
		"manifestation_creation_calendar",
		"manifestation_creation_date",
		"manifestation_creation_date_gregorian",
		"manifestation_creation_date_year",
		"manifestation_creation_date_month",
		"manifestation_creation_date_day",
		"manifestation_creation_date_inferred",
		"manifestation_creation_date_uncertain",
		"manifestation_creation_date_approx",
		"manifestation_is_translation",
		"language_of_manifestation",
		"address",
		"manifestation_incipit",
		"manifestation_excipit",
		"manifestation_ps",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"manifestation_creation_date2_year",
		"manifestation_creation_date2_month",
		"manifestation_creation_date2_day",
		"manifestation_creation_date_is_range",
		"manifestation_creation_date_as_marked",
		"uuid"
	]

def get_manifestation_csv_converter() :
	return [
		{ "f" : "Work (Letter) ID", "d" : { "o" : "work", "f" : "iwork_id", "r" : "is_manifestation_of" } },
		{ "f" : "Manifestation [Letter] ID", "d" : { "o" : "manifestation", "f" : "manifestation_id" } },
		{ "f" : "Manifestation type", "d" : { "o" : "manifestation", "f" : "manifestation_type" } },
		{ "f" : "Repository name", "d" : { "o" : "institution", "f" : "institution_name", "r" : "stored_in" } },
		{ "f" : "Repository ID", "d" : { "o" : "institution", "f" : "institution_id", "r" : "stored_in" } },
		{ "f" : "Shelfmark and pagination", "d" : { "o" : "manifestation", "f" : "id_number_or_shelfmark" } },
		{ "f" : "Printed copy details", "d" : { "o" : "manifestation", "f" : "printed_edition_details" } },
		{ "f" : "Notes on manifestation", "d" : { "o" : "comment", "f" : "comment", "r" : "refers_to" } },
		{ "f" : u"UUID", "d" : { "o" : "manifestation", "f" : "uuid" } },
	]

def get_institution_fields() :
	return [
		"institution_id",
		"institution_name",
		"institution_synonyms",
		"institution_city",
		"institution_city_synonyms",
		"institution_country",
		"institution_country_synonyms",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"editors_notes",
		"uuid"
	]

def get_institution_csv_converter() :
	return [
		#{ "f" : "Manifestation ID",   "d" : { "o" : "institution", "f" : "" } },
		#{ "f" : "Work [Letter] ID",   "d" : { "o" : "institution", "f" : "" } },
		{ "f" : "Repository ID",        "d" : { "o" : "institution", "f" : "institution_id" } },
		{ "f" : "Repository Name",      "d" : { "o" : "institution", "f" : "institution_name" } },
		{ "f" : "Repository City",      "d" : { "o" : "institution", "f" : "institution_city" } },
		{ "f" : "Repository Country",   "d" : { "o" : "institution", "f" : "institution_country" } },
		{ "f" : "Related Resource IDs", "d" : { "o" : "resource",    "f" : "resource_id", "r" : "is_related_to"} },
		{ "f" : "UUID",                 "d" : { "o" : "institution", "f" : "uuid" } },
		{ "f" : u"EMLO URL",                        "d" : {} },
	]

def get_comment_fields() :

	return [
		"comment_id",
		"comment",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"uuid"
	]



def get_resource_fields() :

	return [
		"resource_id",
		"resource_name",
		"resource_details",
		"resource_url",
		#"creation_timestamp",
		#"creation_user",
		#"change_timestamp",
		#"change_user",
		"uuid"
	]

def get_resource_csv_converter() :
	return [
		{ "f" : "Resource ID", "d" : { "o" : "resource", "f" : "resource_id" } },
		{ "f" : "Resource Name", "d" : { "o" : "resource", "f" : "resource_name" } },
		{ "f" : "Resource Details", "d" : { "o" : "resource", "f" : "resource_details" } },
		{ "f" : "Resource URL", "d" : { "o" : "resource", "f" : "resource_url" } },
		{ "f" : "UUID", "d" : { "o" : "resource", "f" : "uuid" } },
	]
