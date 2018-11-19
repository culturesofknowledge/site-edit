from __future__ import print_function
from tweaker.tweaker import DatabaseTweaker
from config import config
import sys

def main() :

	do_commit = ( raw_input("Commit changes to database (y/n): ") == "y")
	if do_commit:
		print( "COMMITTING changes to database." )
	else:
		print( "NOT committing changes to database." )


	tweaker = DatabaseTweaker.tweaker_from_connection( config["dbname"], config["host"], config["port"], config["user"], config["password"] )
	# tweaker.set_debug(True)

	people = []
	places = []
	works = []

	original_catalogue = 'ELRS'
	repository_id = 135  # The Royal Society

	csv_rows = tweaker.get_csv_data( "resources/royal_society/people.csv" )
	# csv_rows = csv_rows[:5]
	count = countdown = len(csv_rows)

	# Create people
	#
	print( "Create People" )
	skip_first_row = True
	for csv_row in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", csv_row["primary_name"] )

		name_clean = standardise_name( csv_row["primary_name"])
		name_tweaked = name_clean.replace("RSEL_", "$RSEL ")
		person_id = tweaker.create_person_or_organisation(
			name_tweaked,
			editors_note=csv_row["editors_notes"]
		)

		people.append( {
			"id": person_id,
			"primary_name" : name_clean.lower()
		} )

		countdown -= 1

	csv_rows = tweaker.get_csv_data( "resources/royal_society/places.csv" )
	# csv_rows = csv_rows[:5]
	count = countdown = len(csv_rows)

	# Create Places
	#
	print( "Create Places" )
	skip_first_row = True
	for csv_row in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", csv_row["location_name"] )

		name_clean = standardise_name( csv_row["location_name"])
		name_tweaked = name_clean.replace("RSEL_", "$RSEL ")
		place_id = tweaker.create_location(
			element_4_eg_city=name_tweaked
		)

		places.append( {
			"id": place_id,
			"location_name" : name_clean.lower()
		} )

		countdown -= 1


	csv_rows = tweaker.get_csv_data( "resources/royal_society/works.csv" )
	# csv_rows = csv_rows[:5]
	count = countdown = len(csv_rows)

	# Create Works
	#
	print( "Create Works" )
	skip_first_row = True
	for cr in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", cr["iwork_id"] )

		languages = None
		if cr['language_id'] :
			languages = tweaker.get_languages_from_code( cr['language_id'] )

		work_id = tweaker.create_work(
			cr["iwork_id"],
			abstract=cr['abstract'],
			accession_code='The Royal Society, London, 9 November 2018',
			addressees_as_marked=cr['addressees_as_marked'],
			addressees_inferred=cr['addressees_inferred'],
			addressees_uncertain=cr['addressees_uncertain'],
			authors_as_marked=cr['authors_as_marked'],
			authors_inferred=cr['authors_inferred'],
			authors_uncertain=cr['authors_uncertain'],
			date_of_work2_std_day=cr['date_of_work2_std_day'],
			date_of_work2_std_month=cr['date_of_work2_std_month'],
			date_of_work2_std_year=cr['date_of_work2_std_year'],
			date_of_work_approx=cr['date_of_work_approx'],
			date_of_work_as_marked=cr['date_of_work_as_marked'],
			date_of_work_inferred=cr['date_of_work_inferred'],
			date_of_work_std_day=cr['date_of_work_std_day'],
			date_of_work_std_is_range=cr['date_of_work_std_is_range'],
			date_of_work_std_month=cr['date_of_work_std_month'],
			date_of_work_std_year=cr['date_of_work_std_year'],
			date_of_work_uncertain=cr['date_of_work_uncertain'],

			destination_as_marked=cr['destination_as_marked'],
			destination_inferred=cr['destination_inferred'],
			destination_uncertain=cr['destination_uncertain'],

			editors_notes=cr['editors_notes'],
			explicit=cr['excipit'],
			incipit=cr['incipit'],
			keywords=cr['keywords'],
			language_of_work=languages,
			origin_as_marked=cr['origin_as_marked'],
			origin_inferred=cr['origin_inferred'],
			origin_uncertain=cr['origin_uncertain'],
			original_calendar=cr['original_calendar'],
			original_catalogue=original_catalogue
		)

		works.append( {
			"id": work_id,
			"csv_id": cr["iwork_id"],
		} )


		# author_ids
		# author_names
		if cr["author_names"]:
			person_id = get_person_id_from_primary_name( people, standardise_name( cr["author_names"]))
			tweaker.create_relationship_created( person_id, work_id )

		# addressee_ids
		# addressee_names
		if cr["addressee_names"] :
			person_id = get_person_id_from_primary_name( people, standardise_name( cr["addressee_names"]))
			tweaker.create_relationship_addressed_to( work_id, person_id,  )

		# mention_id
		# emlo_mention_id
		if cr["mention_id"]:
			person_id = get_person_id_from_primary_name( people, standardise_name( cr["mention_id"] ))
			tweaker.create_relationship_mentions( work_id, person_id,  )


		# origin_id
		# origin_name
		if cr["origin_name"] :
			location_id = get_location_id_from_location_name( places, standardise_name( cr["origin_name"] ))
			tweaker.create_relationship_was_sent_from( work_id, location_id )

		# destination_id
		# destination_name
		if cr["destination_name"] :
			location_id = get_location_id_from_location_name( places, standardise_name( cr["destination_name"] ))
			tweaker.create_relationship_was_sent_to( work_id, location_id )

		# resource_name
		# resource_url
		# resource_details
		if cr["resource_name"] and cr["resource_url"] :
			resource_id = tweaker.create_resource( cr['resource_name'], cr['resource_url'], cr['resource_details'] )
			tweaker.create_relationship_work_resource( work_id, resource_id )

		# answererby
		if cr["answererby"] :
			# tweaker.create_relationship_work_reply_to
			print( "Error: not handled answererby yet, which is the reply!?")

		# notes_on_date_of_work
		if cr['notes_on_date_of_work'] :
			comment_id = tweaker.create_comment( cr['notes_on_date_of_work'] )
			tweaker.create_relationship_note_on_work_date( comment_id, work_id )

		# notes_on_addressees
		if cr['notes_on_addressees'] :
			comment_id = tweaker.create_comment( cr['notes_on_addressees'] )
			tweaker.create_relationship_note_on_work_addressee( comment_id, work_id )

		# notes_on_letter
		if cr['notes_on_letter'] :
			comment_id = tweaker.create_comment( cr['notes_on_letter'] )
			tweaker.create_relationship_note_on_work_generally( comment_id, work_id )

		# notes_on_people_mentioned
		if cr['notes_on_people_mentioned'] :
			comment_id = tweaker.create_comment( cr['notes_on_people_mentioned'] )
			tweaker.create_relationship_note_on_work_people_mentioned( comment_id, work_id )

		# notes_on_authors
		if cr['notes_on_authors'] :
			comment_id = tweaker.create_comment( cr['notes_on_authors'] )
			tweaker.create_relationship_note_on_work_author( comment_id, work_id )


		countdown -= 1

	csv_rows = tweaker.get_csv_data( "resources/royal_society/manifestations.csv" )
	#csv_rows = csv_rows[:5]
	count = countdown = len(csv_rows)

	# Create Works
	#
	print( "Create Manifestations" )

	skip_first_row = True
	man_id_end = 'abcdefghijklmnopqrstuvwxyz'
	work_id_manfestations = {}

	for cr in csv_rows:

		if skip_first_row:
			skip_first_row = False
			continue

		print( str(countdown) + " of " + str(count), ":", cr["manifestation_id"] )

		work_id = get_work_id_from_csv_id( works, cr['iwork_id'] )

		if work_id not in work_id_manfestations :
			work_id_manfestations[work_id] = 0
		else :
			work_id_manfestations[work_id] += 1

			if work_id_manfestations[work_id] >= len(man_id_end) :

				print( "Error: need more man id generation space")
				sys.exit()

		manifestation_id = work_id + "-" + man_id_end[work_id_manfestations[work_id]]
		tweaker.create_manifestation( manifestation_id,
			cr['manifestation_type'],
			id_number_or_shelfmark=cr['id_number_or_shelfmark']
		)

		# repository_id
		tweaker.create_relationship_manifestation_in_repository( manifestation_id, repository_id)

		# iwork_id
		tweaker.create_relationship_manifestation_of_work( manifestation_id, work_id )

		# manifestation_notes
		if cr['manifestation_notes'] :
			comment_id = tweaker.create_comment( cr['manifestation_notes'] )
			tweaker.create_relationship_note_manifestation( comment_id, manifestation_id )


		countdown -= 1

	print()

	tweaker.print_audit()
	tweaker.commit_changes(do_commit)

	print( "Fini" )


def standardise_name( name) :
	name = name.replace("RSEL_", "" )  # remove if there
	name = "RSEL_" + name.strip()  # strip space and add back on
	return name


def get_work_id_from_csv_id( works, csv_id ) :

	for work in works:
		if work['csv_id'] == csv_id:
			return work['id']

	print( "Error csv_id " + csv_id + " not found!")
	return None


def get_person_id_from_primary_name( people, name ) :

	name_lower = name.lower()
	for person in people:
		if person['primary_name'] == name_lower:
			return person['id']

	print( "Error Name " + name_lower + " not found!")
	return None


def get_location_id_from_location_name( places, name ) :

	name_lower = name.lower()
	for place in places:
		if place['location_name'] == name_lower:
			return place['id']

	print( "Error Name " + name_lower + " not found!")
	return None




if __name__ == '__main__':

	print( "Starting main()")
	main()
	print( "Finished main()")


