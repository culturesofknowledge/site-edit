from tweaker import DatabaseTweaker


class DatabaseTweakerCollect(DatabaseTweaker):

	def __init__( self, connection=None, debug=False ):

		DatabaseTweaker.__init__( self, connection, debug )
		self.user = "cofkbot"
		self.user_email = self.user + "@ai.ox.ac.uk"
		self._id = "CokBot Uploader" #"5be3118e0160af3800a2f9b3" #

		self.upload_id = None
		self.upload_name = None
		self.id_count_start = 2000000
		self.id_count = self.id_count_start

	def next_id(self):
		self.id_count += 1
		return self.id_count

	def start_upload(self, name, work_count ):

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
				"returning upload_id"

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

		self.upload_id = self.cursor.fetchone()[0]
		self.upload_name = name
		self.id_count = self.id_count_start

		return self.upload_id


	def create_person_existing(self, iperson_id ):

		person = self.get_person_from_iperson_id( iperson_id )

		if not person:
			print( "Error. Don't lie to me, that person(" + str(iperson_id) + ") does not exist!")

		else :

			command = "INSERT INTO cofk_collect_person" \
					" (_id,upload_id,upload_name,iperson_id,union_iperson_id,person_id,primary_name)" \
					" VALUES " \
					" (%s,%s,%s,%s,%s,%s,%s)"

			command = self.cursor.mogrify( command, (
				self._id,
				self.upload_id,
				self.upload_name,
				iperson_id,
				iperson_id,
				person['person_id'],
				person['foaf_name']
			) )

			self._print_command( "CREATE collect person", command )
			self._audit_insert( "collect person" )

			self.cursor.execute( command )


	def create_person_new( self, primary_name,
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

							editors_notes='',

							flourished_day=None,
							flourished_month=None,
							flourished_year=None,
							flourished_is_range=0,
							flourished2_day=None,
							flourished2_month=None,
							flourished2_year=None,

							gender='',
							is_organisation="",
							notes_on_person='',
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

		person_upload_id = self.next_id()
		command = self.cursor.mogrify( command, (
			self._id,
			self.upload_id,
			self.upload_name,
			person_upload_id,
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

		return person_upload_id

	""" Template
		def create_?( self, ):
	
			command = "INSERT INTO cofk_collect_person" \
				" (" \
					"_id" \
					",upload_id"\
				")" \
				" VALUES " \
				" (%s,%s)"
	
			command = self.cursor.mogrify( command, (
				self._id,
				self.upload_id
			) )
	
			self._print_command( "CREATE collect person", command )
			self._audit_insert( "collect person" )
	
			self.cursor.execute( command )
	
			
	"""

	def create_work( self,
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
		upload_status = 1
		excipit = explicit

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

		work_upload_id = self.next_id()
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
			work_upload_id,
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
			self.upload_id,
			self.upload_name,
			upload_status,
			work_id
		) )

		self._print_command( "CREATE collect work", command )
		self._audit_insert( "collect work" )

		self.cursor.execute( command )

		return work_upload_id

	def create_location_existing( self, location_id ):

		location = self.get_location_from_location_id( location_id )

		if not location:
			print( "Error. Don't lie to me, that location(" + str(location_id) + ") does not exist!")

		else :

			command = "INSERT INTO cofk_collect_location" \
						" (_id,upload_id,upload_name,location_id,union_location_id,location_name)" \
						" VALUES " \
						" (%s,%s,%s,%s,%s,%s)"


			command = self.cursor.mogrify( command, (
				self._id,
				self.upload_id,
				self.upload_name,
				location_id,
				location_id,
				location['location_name']
			) )

			self._print_command( "CREATE collect location", command )
			self._audit_insert( "collect location" )

			self.cursor.execute( command )


	def create_location_new( self,
		editors_notes=None,
		element_1_eg_room='',
		element_2_eg_building='',
		element_3_eg_parish='',
		element_4_eg_city='',
		element_5_eg_county='',
		element_6_eg_country='',
		element_7_eg_empire='',
		latitude=None,
		location_synonyms=None,
		longitude=None,
		notes_on_place=None,
		):

		union_location_id = None
		location_name = [
			element_1_eg_room,
			element_2_eg_building,
			element_3_eg_parish,
			element_4_eg_city,
			element_5_eg_county,
			element_6_eg_country,
			element_7_eg_empire
		]

		location_name = ", ".join([element for element in location_name if element.strip() ])

		command = "INSERT INTO cofk_collect_location" \
				" (" \
					"_id" \
					",editors_notes" \
					",element_1_eg_room" \
					",element_2_eg_building" \
					",element_3_eg_parish" \
					",element_4_eg_city" \
					",element_5_eg_county" \
					",element_6_eg_country" \
					",element_7_eg_empire" \
					",latitude" \
					",location_id" \
					",location_name" \
					",location_synonyms" \
					",longitude" \
					",notes_on_place" \
					",union_location_id" \
					",upload_id" \
					",upload_name" \
				")" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)"

		location_upload_id = self.next_id()

		command = self.cursor.mogrify( command, (
			self._id,
			editors_notes,
			element_1_eg_room,
			element_2_eg_building,
			element_3_eg_parish,
			element_4_eg_city,
			element_5_eg_county,
			element_6_eg_country,
			element_7_eg_empire,
			latitude,
			location_upload_id,
			location_name,
			location_synonyms,
			longitude,
			notes_on_place,
			union_location_id,
			self.upload_id,
			self.upload_name
		) )

		self._print_command( "CREATE collect location", command )
		self._audit_insert( "collect location" )

		self.cursor.execute( command )

		return location_upload_id

	def link_addressee( self,
						iperson_id,
						iwork_id,
						notes_on_addressee=None ):

		addressee_id = self.next_id()

		command = "INSERT INTO cofk_collect_addressee_of_work" \
				" (" \
					"_id" \
					",addressee_id"\
					",iperson_id"\
					",iwork_id"\
					",notes_on_addressee" \
					",upload_id" \
				")" \
				" VALUES " \
				" (%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			addressee_id,
			iperson_id,
			iwork_id,
			notes_on_addressee,
			self.upload_id
		) )

		self._print_command( "CREATE collect addressee link", command )
		self._audit_insert( "collect addressee link" )

		self.cursor.execute( command )

	def link_author( self,
			iperson_id,
			iwork_id,
			notes_on_author=None ):

		author_id = self.next_id()

		command = "INSERT INTO cofk_collect_author_of_work" \
				  " (" \
				  "_id" \
				  ",author_id" \
				  ",iperson_id" \
				  ",iwork_id" \
				  ",notes_on_author" \
				  ",upload_id" \
				  ")" \
				  " VALUES " \
				  " (%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			author_id,
			iperson_id,
			iwork_id,
			notes_on_author,
			self.upload_id
		) )

		self._print_command( "CREATE collect author link", command )
		self._audit_insert( "collect author link" )

		self.cursor.execute( command )

	def link_destination( self,
			location_id,
			iwork_id,
			notes_on_destination=None ):

		destination_id = self.next_id()

		command = "INSERT INTO cofk_collect_destination_of_work" \
				  " (" \
				  "_id" \
				  ",destination_id" \
				  ",location_id" \
				  ",iwork_id" \
				  ",notes_on_destination" \
				  ",upload_id" \
				  ")" \
				  " VALUES " \
				  " (%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			destination_id,
			location_id,
			iwork_id,
			notes_on_destination,
			self.upload_id
		) )

		self._print_command( "CREATE collect destination link", command )
		self._audit_insert( "collect destination link" )

		self.cursor.execute( command )


	def link_origin( self,
			location_id,
			iwork_id,
			notes_on_origin=None ):

		origin_id = self.next_id()

		command = "INSERT INTO cofk_collect_origin_of_work" \
				  " (" \
				  "_id" \
				  ",origin_id" \
				  ",location_id" \
				  ",iwork_id" \
				  ",notes_on_origin" \
				  ",upload_id" \
				  ")" \
				  " VALUES " \
				  " (%s,%s,%s,%s,%s,%s)"

		command = self.cursor.mogrify( command, (
			self._id,
			origin_id,
			location_id,
			iwork_id,
			notes_on_origin,
			self.upload_id
		) )

		self._print_command( "CREATE collect origin link", command )
		self._audit_insert( "collect origin link" )

		self.cursor.execute( command )
