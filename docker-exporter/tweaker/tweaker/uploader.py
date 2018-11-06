from tweaker import DatabaseTweaker


class DatabaseTweakerCollect(DatabaseTweaker):

	def __init__( self, connection=None, debug=False ):

		DatabaseTweaker.__init__( self, connection, debug )

		self.user_email = "cokbot@fake.ox.ac.uk"
		self._id = "CokBot Uploader"

	def create_upload(self, name, work_count ):

		self.check_database_connection()

		total_works = work_count
		upload_description = name
		# upload_id
		upload_name = name
		upload_status = 1
		# upload_timestamp
		upload_username = self.user
		uploader_email = self.user_email
		works_accepted = 0
		works_rejected = 0

		command = "INSERT INTO cofk_collect_upload" \
				" (_id,total_works,upload_description,upload_name,upload_status,upload_username,uploader_email,works_accepted,works_rejected)" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s)" \
				" returning upload_id"

		command = self.cursor.mogrify( command, (
			self._id,
			total_works,
			upload_description,
			upload_name,
			upload_status,
			upload_username,
			uploader_email,
			works_accepted,
			works_rejected,
		) )

		self._print_command( "CREATE collect upload", command )
		self._audit_insert( "collect upload" )

		self.cursor.execute( command )
		return self.cursor.fetchone()[0]


	def create_person_existing(self, upload_id, upload_name, iperson_id ):

		person = self.get_person_from_iperson_id( iperson_id )

		if not person:
			print( "Don't lie to me. They don't exist!")

		else :

			command = "INSERT INTO cofk_collect_person" \
					" (_id,upload_id,upload_name,iperson_id,union_iperson_id,person_id,primary_name)" \
					" VALUES " \
					" (%s,%s,%s,%s,%s,%s,%s)"

			command = self.cursor.mogrify( command, (
				self._id,
				upload_id,
				upload_name,
				iperson_id,
				iperson_id,
				person['person_id'],
				person['foaf_name']
			) )

			self._print_command( "CREATE collect person", command )
			self._audit_insert( "collect person" )

			self.cursor.execute( command )


	def create_person_new( self, upload_id, upload_name, iperson_number, primary_name,
							alternative_names=None,

							date_of_birth_day=None,
							date_of_birth_month=None,
							date_of_birth_year=None,
							date_of_birth_approx=0,
							date_of_birth_inferred=0,
							date_of_birth_uncertain=0,
							date_of_birth_is_range=0,
							date_of_birth2_day=None,
							date_of_birth2_month=None,
							date_of_birth2_year=None,

							date_of_death_day=None,
							date_of_death_month=None,
							date_of_death_year=None,
							date_of_death_approx=0,
							date_of_death_inferred=0,
							date_of_death_is_range=0,
							date_of_death_uncertain=0,
							date_of_death2_year=None,
							date_of_death2_day=None,
							date_of_death2_month=None,

							editors_notes=None,

							flourished_day=None,
							flourished_month=None,
							flourished_year=None,
							flourished_is_range=0,
							flourished2_day=None,
							flourished2_month=None,
							flourished2_year=None,

							gender='',
							is_organisation="",
							notes_on_person=None,
							organisation_type=None,
							roles_or_titles=None ):


		command = "INSERT INTO cofk_collect_person" \
				" (" \
					"_id,upload_id,upload_name,iperson_id,primary_name" \
					",alternative_names" \
					",date_of_birth_day,date_of_birth_month,date_of_birth_year" \
					",date_of_birth_approx,date_of_birth_inferred,date_of_birth_uncertain" \
					",date_of_birth_is_range" \
					",date_of_birth2_day,date_of_birth2_month,date_of_birth2_year" \
 					\
					",date_of_death_day,date_of_death_month,date_of_death_year" \
					",date_of_death_approx,date_of_death_inferred,date_of_death_uncertain" \
					",date_of_death_is_range" \
					",date_of_death2_day,date_of_death2_month,date_of_death2_year" \
					\
					",editors_notes" \
					\
					",flourished_day,flourished_month,flourished_year" \
					",flourished_is_range" \
					",flourished2_day,flourished2_month,flourished2_year" \
					\
					",gender" \
					",is_organisation" \
					",notes_on_person" \
					",organisation_type" \
					",roles_or_titles" \
				")" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			upload_id,
			upload_name,
			iperson_number,
			primary_name,
			alternative_names,

			date_of_birth_day,
			date_of_birth_month,
			date_of_birth_year,
			date_of_birth_approx,
			date_of_birth_inferred,
			date_of_birth_uncertain,
			date_of_birth_is_range,
			date_of_birth2_day,
			date_of_birth2_month,
			date_of_birth2_year,

			date_of_death_day,
			date_of_death_month,
			date_of_death_year,
			date_of_death_approx,
			date_of_death_inferred,
			date_of_death_is_range,
			date_of_death_uncertain,
			date_of_death2_year,
			date_of_death2_day,
			date_of_death2_month,

			editors_notes,

			flourished_day,
			flourished_month,
			flourished_year,
			flourished_is_range,
			flourished2_day,
			flourished2_month,
			flourished2_year,

			gender,
			is_organisation,
			notes_on_person,
			organisation_type,
			roles_or_titles
			) )

		self._print_command( "CREATE collect person", command )
		self._audit_insert( "collect person" )

		self.cursor.execute( command )

	""" Template
		def create_?( self, upload_id, upload_name,
								):
	
			command = "INSERT INTO cofk_collect_person" \
					  " (_id" \
					
					  ")" \
					  " VALUES " \
					  " (%s,%s)"
	
			command = self.cursor.mogrify( command, (
				_id,
			) )
	
			self._print_command( "CREATE collect person", command )
			self._audit_insert( "collect person" )
	
			self.cursor.execute( command )
	
	"""
	def create_work( self, upload_id, upload_name, iwork_id,
		abstract=None,
		accession_code=None,
		addressees_as_marked=None,
		addressees_inferred=0,
		addressees_uncertain=0,
		authors_as_marked=None,
		authors_inferred=0,
		authors_uncertain=0,
		date_of_work2_approx=0,
		date_of_work2_inferred=0,
		date_of_work2_std_day=None,
		date_of_work2_std_month=None,
		date_of_work2_std_year=None,
		date_of_work2_uncertain=0,
		date_of_work_approx=0,
		date_of_work_as_marked=None,
		date_of_work_inferred=0,
		date_of_work_std_day=None,
		date_of_work_std_is_range=0,
		date_of_work_std_month=None,
		date_of_work_std_year=None,
		date_of_work_uncertain=0,
		destination_as_marked=None,
		destination_id=None,
		destination_inferred=0,
		destination_uncertain=0,
		editors_notes=None,
		excipit=None,
		explicit=None,
		incipit=None,
		keywords=None,
		language_of_work=None,
		mentioned_as_marked=None,
		mentioned_inferred=0,
		mentioned_uncertain=0,
		notes_on_addressees=None,
		notes_on_authors=None,
		notes_on_date_of_work=None,
		notes_on_destination=None,
		notes_on_letter=None,
		notes_on_origin=None,
		notes_on_people_mentioned=None,
		notes_on_place_mentioned=None,
		origin_as_marked=None,
		origin_id=None,
		origin_inferred=0,
		origin_uncertain=0,
		original_calendar='',
		place_mentioned_as_marked=None,
		place_mentioned_inferred=0,
		place_mentioned_uncertain=0
						   ):

		union_iwork_id = None
		work_id = None
		upload_status=1

		command = "INSERT INTO cofk_collect_work" \
				" (" \
					"_id," \
					"abstract," \
					"accession_code," \
					"addressees_as_marked," \
					"addressees_inferred," \
					"addressees_uncertain," \
					"authors_as_marked," \
					"authors_inferred," \
					"authors_uncertain," \
					"date_of_work2_approx," \
					"date_of_work2_inferred," \
					"date_of_work2_std_day," \
					"date_of_work2_std_month," \
					"date_of_work2_std_year," \
					"date_of_work2_uncertain," \
					"date_of_work_approx," \
					"date_of_work_as_marked," \
					"date_of_work_inferred," \
					"date_of_work_std_day," \
					"date_of_work_std_is_range," \
					"date_of_work_std_month," \
					"date_of_work_std_year," \
					"date_of_work_uncertain," \
					"destination_as_marked," \
					"destination_id," \
					"destination_inferred," \
					"destination_uncertain," \
					"editors_notes," \
					"excipit," \
					"explicit," \
					"incipit," \
					"iwork_id," \
					"keywords," \
					"language_of_work," \
					"mentioned_as_marked," \
					"mentioned_inferred," \
					"mentioned_uncertain," \
					"notes_on_addressees," \
					"notes_on_authors," \
					"notes_on_date_of_work," \
					"notes_on_destination," \
					"notes_on_letter," \
					"notes_on_origin," \
					"notes_on_people_mentioned," \
					"notes_on_place_mentioned," \
					"origin_as_marked," \
					"origin_id," \
					"origin_inferred," \
					"origin_uncertain," \
					"original_calendar," \
					"place_mentioned_as_marked," \
					"place_mentioned_inferred," \
					"place_mentioned_uncertain," \
					"union_iwork_id," \
					"upload_id," \
					"upload_name," \
					"upload_status," \
					"work_id" \
				")" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			abstract,
			accession_code,
			addressees_as_marked,
			addressees_inferred,
			addressees_uncertain,
			authors_as_marked,
			authors_inferred,
			authors_uncertain,
			date_of_work2_approx,
			date_of_work2_inferred,
			date_of_work2_std_day,
			date_of_work2_std_month,
			date_of_work2_std_year,
			date_of_work2_uncertain,
			date_of_work_approx,
			date_of_work_as_marked,
			date_of_work_inferred,
			date_of_work_std_day,
			date_of_work_std_is_range,
			date_of_work_std_month,
			date_of_work_std_year,
			date_of_work_uncertain,
			destination_as_marked,
			destination_id,
			destination_inferred,
			destination_uncertain,
			editors_notes,
			excipit,
			explicit,
			incipit,
			iwork_id,
			keywords,
			language_of_work,
			mentioned_as_marked,
			mentioned_inferred,
			mentioned_uncertain,
			notes_on_addressees,
			notes_on_authors,
			notes_on_date_of_work,
			notes_on_destination,
			notes_on_letter,
			notes_on_origin,
			notes_on_people_mentioned,
			notes_on_place_mentioned,
			origin_as_marked,
			origin_id,
			origin_inferred,
			origin_uncertain,
			original_calendar,
			place_mentioned_as_marked,
			place_mentioned_inferred,
			place_mentioned_uncertain,
			union_iwork_id,
			upload_id,
			upload_name,
			upload_status,
			work_id
		) )

		self._print_command( "CREATE collect work", command )
		self._audit_insert( "collect work" )

		self.cursor.execute( command )



