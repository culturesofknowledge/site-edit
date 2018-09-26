// ignore the ingestname for now
var upload_id = ingestname;   //TODO generate a new id here to replace hard coded value.
var prefix="collect_";

importTab =  new Object();
importTab.works  =  db.getCollection( prefix + "works");
importTab.manifestations  =  db.getCollection( prefix + "manifestations");
importTab.people  =  db.getCollection( prefix + "people");
importTab.places  =  db.getCollection( prefix + "places");
importTab.repositories  =  db.getCollection( prefix + "repositories");

collectTab =  new Object();
collectTab.addressee  =  db.getCollection( prefix + "addressee");
collectTab.author  =  db.getCollection( prefix + "author");
collectTab.institution  =  db.getCollection( prefix + "institution");
collectTab.location  =  db.getCollection( prefix + "location");
collectTab.manifestation  =  db.getCollection( prefix + "manifestation");
collectTab.occupation_person  =  db.getCollection( prefix + "occupation_person");
collectTab.person  =  db.getCollection( prefix + "person");
collectTab.person_mentioned  =  db.getCollection( prefix + "person_mentioned");
collectTab.place_mentioned  =  db.getCollection( prefix + "place_mentioned");
collectTab.subject_work  =  db.getCollection( prefix + "subject_work");
collectTab.work  =  db.getCollection( prefix + "work");
collectTab.language_of_work  =  db.getCollection( prefix + "language_of_work");
collectTab.work_resource  =  db.getCollection( prefix + "work_resource");
collectTab.person_resource  =  db.getCollection( prefix + "person_resource");
collectTab.location_resource  =  db.getCollection( prefix + "location_resource");
collectTab.institution_resource  =  db.getCollection( prefix + "institution_resource");
collectTab.other  =  db.getCollection( prefix + "other");
collectTab.image_of_manif  =  db.getCollection( prefix + "image_of_manif");

var uploadTab = {};  // denotes an Object is being created
uploadTab.upload  =  db.getCollection("upload");

for(property in collectTab) {
  print(property + " = " + collectTab[property]);
	//collectTab[property].drop();						// 01
  collectTab[property].remove( { upload_id : upload_id  } );
}

// run("impKircher.sh");

importTab.works.ensureIndex( { iwork_id: 1 } );
importTab.manifestations.ensureIndex( { iwork_id: 1 } );
importTab.people.ensureIndex( { iperson_id: 1 } );
importTab.places.ensureIndex( { location_id: 1 } );
importTab.repositories.ensureIndex( { institution_id: 1 } );

//initialise variables
//var myArray = collectTab.works.distinct('iwork_id');
//		print( "tot=myArray.length[" + myArray.length+ "] = doc ids======================================\n");
var addressee_id =1000000;
var author_id = 1000000;
var person_id = 1000000;
var mentioned_id = 1000000;
var location_id = 1000000;
var language_of_work_id = 1;
var manifestation_id = 1;
var resource_id = 1000000;
var other_id = 1000000;
var mWork = {  };
var mAddressee_Of_Work = { };
var mAuthor_Of_Work = { };
var mLanguage_Of_Work = {  };
var mLocation = {  }; 
var mInstitution = { }; 
var mManifestation = {  };
var mWork_Resource = {  };
var mOccupation_Of_Person = {  };
var mPerson = {  };
var mPerson_Mentioned = {  };
var mOther = {  };
var mImage_of_manif = { };
var count = 0;
var imageidArr = [];
var imagefilenameArr = [];
var myJSON = "";

var binaryFields = [
	"date_of_work_std_is_range",
	"date_of_work_inferred",
	"date_of_work_uncertain",
	"date_of_work_approx",
	"authors_inferred",
	"authors_uncertain",
	"addressees_inferred",
	"addressees_uncertain",
	"destination_inferred",
	"destination_uncertain",
	"origin_inferred",
	"origin_uncertain"
];
	print("\n  = initUpload(ingestname)======================================================");

// generate the upload record 
if (ingestname != "") {
	var upload_rec = initUpload(ingestname);

	processInstitutions();
	processPlaces();
	processPeople(); // MATTT - Maybe try to disable this and see if it creates the stupid table of people mess...
	processWorks();
	processManifestations();
/**/
} else {
	print("\n  *********** No ingestname provided **************");
}
// We are done here =======================================================================
//collectTab.img2.find({},{documentid:true}).sort({documentid: 1}).limit(5).forEach( 

function initUpload(ingestname) {

	var mUpload = { };
	var upload_username = "cofkmat";
	var uploader_email = "cokbot@ox.ac.uk";

		// *****  Need to generate the new location here
	var newUpload = uploadTab.upload.findOne({upload_id : upload_id });
	if (! newUpload) {
		mUpload = {
			upload_id : upload_id,
			upload_username : upload_username,
			upload_status :  1,
			total_works : 0,
			works_accepted : 0,
			works_rejected : 0,
			uploader_email : uploader_email
		}
	} else {
		mUpload  =	newUpload ;
	}

	mUpload.upload_name = ingestname;
  mUpload.upload_description = ingestname ;
  mUpload.upload_timestamp = new Date();

	uploadTab.upload.update(
		{upload_id: mUpload.upload_id },
		mUpload, 
		{ upsert: true}
	);							// 04
	print("\n  =mUpload======================================================");
	printjson(  mUpload);
	return	mUpload ;
}

function initWorkVariables(workId) {
	// var myArray = collectTab.works.distinct('iwork_id');
	//	print( "tot=myArray.length[" + myArray.length+ "] = doc ids================================\n");
    //  mWork.iwork_id = myArray[i]; //"aa", bb"

	mWork = { iwork_id : workId, "upload_id" : upload_rec.upload_id };

	mAddressee_Of_Work = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };
	mAuthor_Of_Work    = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };
    mPerson_Mentioned  = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };

	mLanguage_Of_Work = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };
	mLocation = { location_id : 0, "upload_id" : upload_rec.upload_id };
	mInstitution = { "upload_id" : upload_rec.upload_id };
	mManifestation = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };
	mWork_Resource = { iwork_id : mWork.iwork_id, "upload_id" : upload_rec.upload_id };
	mOccupation_Of_Person = { occupation_of_person_id : 0, "upload_id" : upload_rec.upload_id };
	mPerson = { iperson_id : 0, "upload_id" : upload_rec.upload_id };
	mOther = { other_id : 0, "upload_id" : upload_rec.upload_id };
	mImage_of_manif = { manifestation_id : 0, "upload_id" : upload_rec.upload_id };


	print( "iwork_id " + mWork.iwork_id + " =initWorkVariables(workId)===========================\n");
	count = 0;
	imageidArr = [];
	imagefilenameArr = [];
	myJSON = "";
//	var myCursor = collectTab.img2.find({ documentid : mWork.iwork_id }).sort({imageid: 1});
//	print( "iwork_id " + myCursor.count() + " =cnt=============================================\n");
}

function capitaliseFirstLetter(string){
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function trueFalse(string){
	return (/^[1Yy]+$/.test(string.charAt(0).toUpperCase())) ? 1 : 0 ;
}

function trimProperties(theDoc){
	for(var key in theDoc) {
		if( typeof theDoc[key] === 'string' ) {
			theDoc[key] = theDoc[key].trim();
		}
	}
}

function binaryProperties(theDoc){
	for(var key in theDoc) {
		if( binaryFields.indexOf(key) != -1 ) {
			if( typeof theDoc[key] === 'string' ) {
				theDoc[key] = (theDoc[key]) ? trueFalse(theDoc[key]) : 0;
			}
		}
	}
}

// *****  Can generate the new person here
// *****  or retrieve new person previously generated
// trim the input values to remove extraneous white space RG 2014-01-26

function getPersonId(id_emlo_in, name_in, seq) {
	var getPerson;
  var id_emlo = id_emlo_in.trim();
  var name = name_in.trim();
	var mPerson = { iperson_id : 0, "upload_id" : upload_rec.upload_id };
	if (id_emlo != "") {
    getPerson = collectTab.person.findOne({"upload_id" : upload_rec.upload_id,iperson_id :	id_emlo });
	} else {
    getPerson = collectTab.person.findOne({"upload_id" : upload_rec.upload_id,primary_name: name });
	}
	if (! getPerson) {
		if (id_emlo != "") {
			mPerson.iperson_id  =	id_emlo ;  //if id_emlo_recipient not null)
		} else {
			mPerson.iperson_id  =	person_id++ ;  //if id_emlo_recipient null)
		}
		mPerson.union_iperson_id  =	id_emlo ;  
		mPerson.primary_name =	name ;  
		collectTab.person.insert(mPerson);	// 07
		getPerson = mPerson;
		print("\n  =getmPerson========================================================");
		printjson(  mPerson);
	}
	return getPerson.iperson_id;
}
function getPlace(id_emlo, name) {
	var getLocation;
	var mLocation = { location_id : 0, "upload_id" : upload_rec.upload_id };
	if (id_emlo != "") {
    getLocation = collectTab.location.findOne({"upload_id" : upload_rec.upload_id,location_id :	id_emlo });
	} else {
    getLocation = collectTab.location.findOne({"upload_id" : upload_rec.upload_id,location_name: name });
	}
	if (! getLocation) {
		if (id_emlo != "") {
			mLocation.location_id  =	id_emlo ;  //if id_emlo_recipient not null)
		} else {
			mLocation.location_id  =	location_id++ ;  //if id_emlo_recipient null)
		}
		mLocation.union_location_id  =	id_emlo ;  
		mLocation.location_name =	name ;  
		collectTab.location.insert(mLocation);	// 07
		getLocation = mLocation;
		print("\n  =mLocation========================================================");
		printjson(  mLocation);
	}
	return getLocation.location_id;
}

function getInstitution(theDoc) {
	var getInstitution;

	print("\n  =mtheDoc========================================================");
	printjson (theDoc);

	if (theDoc.institution_id ) {
        getInstitution = collectTab.institution.findOne({"upload_id" : upload_rec.upload_id,"institution_id" : theDoc.institution_id });
	} else {
        getInstitution = collectTab.institution.findOne({"upload_id" : upload_rec.upload_id,"institution_name" : theDoc.institution_name });
	}

	if (! getInstitution) {

		/*var repos = db.institution.findOne(
            {"upload_id" : upload_rec.upload_id, "institution_id" : theDoc.institution_id },
			{ "institution_id": 1, "institution_name": 1, "institution_city": 1, "institution_country": 1}
		);

		if (repos) {
			// we have a result
				printjson (repos);
				getInstitution =	repos ;
				getInstitution.union_institution_id = getInstitution.institution_id;
		} else*/ {
			// we don't
				getInstitution =	theDoc ;
                delete getInstitution._id;
		}

		getInstitution.upload_id = upload_rec.upload_id;  
		collectTab.institution.insert(getInstitution);	// 07
		print("\n  =mInstitution========================================================");
		printjson( getInstitution);
	}

	return getInstitution.institution_id ;
}

function processWorks() {
	var sCursor = importTab.works.find({ }).limit(0);
	print("\n"+ sCursor.size() + "  = processWorks() ================================================");
	sCursor.forEach( 
		function(myDoc) { 
			print("\n  =sCursor==========================================================");
			printjson (myDoc);
			trimProperties(myDoc);
			binaryProperties(myDoc);
			myDoc.upload_id = upload_rec.upload_id;
			initWorkVariables(myDoc.iwork_id);
//			printjson (myDoc);
			
			processAuthor(myDoc);
			processAddressee(myDoc);
			processMentioned(myDoc)
			processWorkLocations(myDoc);
			processWorkLanguage(myDoc);
			processWorkResource(myDoc);
			processWorkOther(myDoc);
//			printjson (myDoc);
			collectTab.work.insert(myDoc);	// 
		}
	);
}

function processManifestations() {
	var sCursor = importTab.manifestations.find({ }).limit(0);
	print("\n"+ sCursor.size() + "  = processManifestations() =======================================");
	sCursor.forEach( 
		function(myDoc) { 
			print("\n  =sCursor==========================================================");
			printjson (myDoc);
			myDoc.upload_id = upload_rec.upload_id;
			myDoc.manifestation_id =	manifestation_id++  ;
			myDoc.manifestation_type =	myDoc.manifestation_type.trim() ;
			initWorkVariables(myDoc.iwork_id);
//			printjson (myDoc);
			if (myDoc.repository_id) {
				var repo = { institution_id : myDoc.repository_id };
				repo.institution_name = myDoc.repository_name;
				myDoc.repository_id = getInstitution(repo);
			}
			delete myDoc.repository_name;
			if (myDoc.manifestation_type_p) {
				myDoc.manifestation_type =	myDoc.manifestation_type_p.trim() ;
				myDoc.manifestation_notes += " " + myDoc.printed_edition_notes.trim() ;
			}
			delete myDoc.manifestation_type_p;
			delete myDoc.printed_edition_notes;
			delete myDoc.ms_translation;
			delete myDoc.printed_translation;

			print("\n  =mManifestation=================================================");
			trimProperties(myDoc);
			printjson (myDoc);
			collectTab.manifestation.insert(myDoc);	// 
		}
	);
}

function processAuthor(myDoc) {
	if( myDoc.author_names || myDoc.author_ids ){

		var id_emlo = String(myDoc.author_ids).split(";");
		var name    = myDoc.author_names.split(";");

		if (name.length != id_emlo.length ) {

			if (name.length > id_emlo.length ) {

				for (k = id_emlo.length; k<name.length;k++){
                    id_emlo[k] = "";
                }
			} else {

				for (k = name.length; k < id_emlo.length;k++){
                    name[k] = "";
                }
			}
		}
		for (j=0;j<id_emlo.length;j++){
			if (name[j].length > 0 || id_emlo[j].length > 0 ) {
				mAuthor_Of_Work.author_id =	author_id++ ;
				mAuthor_Of_Work.iperson_id =	getPersonId(id_emlo[j], name[j], j) ;
				collectTab.author.insert(mAuthor_Of_Work);					// 02
				print("\n  =mAuthor_Of_Work================================================");
				printjson(  mAuthor_Of_Work);
			}
		}
	}
	delete myDoc.author_names;
	delete myDoc.author_ids;
}

function processAddressee(myDoc) {
	if( myDoc.addressee_names || myDoc.addressee_ids ){

		var id_emlo = String(myDoc.addressee_ids).split(";");
		var name    = myDoc.addressee_names.split(";");

		if (name.length != id_emlo.length ) {

			if (name.length > id_emlo.length ) {
				for (k = id_emlo.length; k<name.length;k++){ id_emlo[k] = ""; }
			} else {
				for (k = name.length; k < id_emlo.length;k++){ name[k] = "";}
			}
		}

		for (j=0;j<id_emlo.length;j++){

			if (name[j].length > 0 || id_emlo[j].length > 0 ) {

				mAddressee_Of_Work.addressee_id =	addressee_id++ ;
				mAddressee_Of_Work.iperson_id =	getPersonId(id_emlo[j], name[j], j) ;

				collectTab.addressee.insert(mAddressee_Of_Work);		// 01

				print("\n  =mAddressee_Of_Work=============================================");
				printjson(  mAddressee_Of_Work);
			}
		}
	}
	delete myDoc.addressee_names;
	delete myDoc.addressee_ids;
}

function processMentioned(myDoc) {

	if( myDoc.emlo_mention_id ){

		var id_emlo = String(myDoc.emlo_mention_id).split(";");

		for (j=0;j<id_emlo.length;j++){

			if ( id_emlo[j].length > 0 ) {

				mPerson_Mentioned.mention_id =	mentioned_id++ ;
				mPerson_Mentioned.iperson_id =	getPersonId(id_emlo[j], "", j) ;

				collectTab.person_mentioned.insert(mPerson_Mentioned);		// 01

				print("\n  =mPerson_Mentioned=============================================");
				printjson(  mPerson_Mentioned);
			}
		}
	} else {
			// TODO  Need to also handle myDoc.mention_id
	}
	delete myDoc.mention_id;
	delete myDoc.emlo_mention_id;
}

function processWorkLocations(myDoc) {
	if( myDoc.origin_id || myDoc.origin_name ){
		myDoc.origin_id = getPlace(myDoc.origin_id,myDoc.origin_name);
	}
	delete myDoc.origin_name;
	
	if( myDoc.destination_id || myDoc.destination_name ){
		myDoc.destination_id = getPlace(myDoc.destination_id,myDoc.destination_name);
	}
	delete myDoc.destination_name;
}

function processWorkLocation(loc_id ,loc_name) {
	//TODO delete this replaced by getPlace
	if (loc_id  != "") {
		mLocation.location_id  =	loc_id ;  
		mLocation.union_location_id  =	loc_id ;  
		mLocation.location_name=	loc_name ;  
	} else {
		// *****  Need to generate the new location here
		var newLocation = collectTab.location.findOne({"upload_id" : upload_rec.upload_id,location_name: loc_name });
		if (! newLocation) {
			mLocation.location_name=	loc_name ; //(needed if id_emlo_origin null) 
			mLocation.location_id  =	location_id++ ;  
		} else {
			mLocation  =	newLocation ;
		}
	}
	collectTab.location.update(
		{location_id: mLocation.location_id },
		mLocation, 
		{ upsert: true}
	);							// 04
	print("\n  =mLocation======================================================");
	printjson(  mLocation);
	return	mLocation.location_id ;
}

function processWorkLanguage(myDoc) {
	var langArray = [];
	var i = 0;

	if (myDoc.language_id) {
        var lang_list = myDoc.language_id.toLowerCase().split(";"),
            j = 0;

        for (; j < lang_list.length; j++) {
            langArray[i++] = lang_list[j]
        }
	}
	if (myDoc.hasgreek) {
		langArray[i++] = myDoc.hasgreek.toLowerCase();
	}
	if (myDoc.hasarabic) {
		langArray[i++] = myDoc.hasarabic.toLowerCase();
	}
	if (myDoc.hashebrew) {
		langArray[i++] = myDoc.hashebrew.toLowerCase();
	}
	if (myDoc.haslatin) {
		langArray[i++] = myDoc.haslatin.toLowerCase();
	}

	myDoc.language_of_work = "";
	print("\n  =processWorkLanguage==============================================");
	print("\n" + myDoc.language_of_work +"==============================================");

	for (var i=0; i<langArray.length; ++i) {
		print("\n langArray["+i+"] = " +langArray[i]);
		var langCode = db['language-all'].findOne({"language_code" : langArray[i] });
		printjson(langCode);
		if (langCode) {
				// we have a result
            myDoc.language_of_work += ( i > 0) ? "," : "";
			myDoc.language_of_work +=	langCode.language_name ;
			mLanguage_Of_Work.language_code =	langCode.language_code ;
//			mLanguage_Of_Work.language_of_work_id =	i;
			mLanguage_Of_Work.language_of_work_id =	language_of_work_id++ ;
			collectTab.language_of_work.insert(mLanguage_Of_Work);				// 12
			print("\n  =mLanguage_Of_Work==============================================");
			printjson(mLanguage_Of_Work);
		} else {
				// we don't
				// mLanguage_Of_Work.language_code =	langArray[i] ;
			print("\n  =mLanguage_Of_Work== not found  ================================");
			print("\n "+	langArray[i].substr(0,3)) ;
		}
	}
	
  print("\n" + myDoc.language_of_work +"==============================================");
	delete myDoc.language_id;
//  delete myDoc.language_of_work;
  delete myDoc.hasgreek;
  delete myDoc.hasarabic;
  delete myDoc.hashebrew;
  delete myDoc.haslatin;
 
}

function processWorkResource(myDoc) {
	if( myDoc.resource_name || myDoc.resource_url ){
		mWork_Resource.resource_id= resource_id++;
		mWork_Resource.resource_name=myDoc.resource_name;
    mWork_Resource.resource_details=myDoc.resource_details;
		mWork_Resource.resource_url=myDoc.resource_url;
		collectTab.work_resource.insert( mWork_Resource);		// 13
		print("\n  =mWork_Resource=================================================");
		printjson(  mWork_Resource);
	}
	delete myDoc.resource_name;
  delete myDoc.resource_url;
  delete myDoc.resource_details;
}

function processWorkOther(myDoc) {
	if( myDoc.source_of_data ){
		myDoc.accession_code = myDoc.source_of_data;
		print("\n  =source_of_data=================================================");
		printjson(  myDoc.accession_code );
	}
	
	if( myDoc.catalogue_name ){
    if( myDoc.editors_notes ){
      myDoc.editors_notes += " ";
    }
    //    myDoc.editors_notes += myDoc.catalogue_name;
  }
  
  if( myDoc.ehost_id ){
    mOther.other_id= other_id++;
    mOther.this_id=myDoc.iwork_id;
    mOther.this_type="iwork_id";
    mOther.external_name=myDoc.ehost_id;
    //mOther.external_details=myDoc.external_details;
    //mOther.external_url=myDoc.external_url;
    collectTab.other.insert( mOther);   // 13
    print("\n  =mOther=================================================");
    printjson(  mOther);
  }
  
  delete myDoc.catalogue_name;
	delete myDoc.source_of_data;
	delete myDoc.answererby;
  delete myDoc.ehost_id;

}

function processPeople() {
	var sCursor = importTab.people.find({ });
	print("\n"+ sCursor.size() + "  = processPeople() ======================================================");
	sCursor.forEach( 
		function(myDoc) { 
			print("\n  =sCursor==========================================================");
			myDoc.upload_id = upload_rec.upload_id;
			printjson (myDoc);
			processPerson(myDoc);
			//	collectTab.person.insert(myDoc);	// 
		}
	);
}

function processPerson(myDoc) {
	if( myDoc.primary_name || myDoc.iperson_id ){
		var id_emlo = String(myDoc.iperson_id).split(";");
		var name    = myDoc.primary_name.split(";");
		if (name.length != id_emlo.length ) {
			if (name.length > id_emlo.length ) {
				for (k = id_emlo.length; k<name.length;k++){ id_emlo[k] = ""; }
			} else {
				for (k = name.length; k < id_emlo.length;k++){ name[k] = "";}
			}
		}
		for (j=0;j<id_emlo.length;j++){
			if (name[j].length > 0 || id_emlo[j].length > 0 ) {
				print("\n  =mPerson=============================================");
				var personId = getPersonId(id_emlo[j], name[j], j) ;
			}
		}
	}
}

function processPlaces() {
			var sCursor = importTab.places.find({ });
	print("\n"+ sCursor.size() + "  = processPlaces() ======================================================");
			sCursor.forEach( 
				function(myDoc) { 
					print("\n  =sCursor==========================================================");
					myDoc.upload_id = upload_rec.upload_id;
					printjson (myDoc);
					getPlace(myDoc.location_id,myDoc.location_name);
//					collectTab.location.insert(myDoc);	// 
				}
			);
}

function processInstitutions() {
	var sCursor = importTab.repositories.find({ });
	print("\n"+ sCursor.size() + "  = processInstitutions() ======================================================");
	sCursor.forEach( 
		function(myDoc) { 
			print("\n  =sCursor==========================================================");
//					myDoc.upload_id = upload_rec.upload_id;
			printjson (myDoc);
			getInstitution(myDoc);
//					collectTab.institution.insert(myDoc);	// 
		}
	);
}
