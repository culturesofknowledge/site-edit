$(document).ready(function() { 
	    
	addDateFilters();
	//addSlider();
	addFilter("Person", "Primary Participant", "person_id", urlPersonAll, "json");
	addFilter("Activity", "Activity", "", "", "");
	addFilter("Location", "Location", "person_id", urlPlaceAll, "json");
	addFilter("Editor", "Editor", "", urlEditor, "json");
	addFilterPerson();
	addPagination();
	addSummaryTable();
	
});


// BUTTON ACTIONS

/* SELECT FILTER  */
$('body').on('change', '.Filter', function() {
	  
	var selectedId = ($(this).select2('data')) ? $(this).select2('data').id : "";
	var selectedText = ($(this).select2('data')) ? $(this).select2('data').text : "";
	
	var filterId = $(this).attr('id');
	
	var filterBox = eval('$("input[name=filter' + filterId   +']")');
	filterBox.val(selectedId);
  
	var filterBoxText = eval('$("input[name=filter' + filterId   +'Text]")');
	filterBoxText.val(selectedText);
	
	$(this).parents("form").submit();   
 });


/* DELETE FILTER */
$('body').on('click', '.deleteFilter', function() {
	
	$(".divFilter").remove();
	var filterId = $(this).attr('id');
	
	var filterValueId = eval('$("input[name=filter' + filterId   +']")');
		filterValueId.val("");
		
	var filterValueText = eval('$("input[name=filter' + filterId   +'Text]")');	
		filterValueText.val("");
	$('#form1').submit(); 
});


// VIEW RECORD
$('body').on('click', '.viewRecord', function(event){
	
	var id = event.target.id.substring(10); // extract record identifier from element id
	$("input[name='activity_id']").val(id);
	showRecord(id); // show record
});

// EDIT RECORD
$('body').on('click', '.editRecord', function(event){
	var id = event.target.id.substring(10); // extract record identifier from element id
	window.location.replace('activity_edit.php?id=' + id);
});

// DELETE RECORD
$('body').on('click', '.deleteRecord', function(event){
	var id = event.target.id.substring(12); // extract record identifier from element id
	if (confirm('Please confirm that you would like to delete this activity.')){
		
		// close modal window
		$('#myModal').foundation('reveal', 'close');
		$('#messages').append('<img src = "img/ajax-loader.gif" alt="Currently loading"  id = "loading"/>');
		    
		 // use ajax to delete selected activity 
	     $.ajax({
	            url: 'lib/activity_submit.php',
	            type: 'POST',
	            data: $('#form1').serialize(),
	            success: function(result){      	
	            	var message = "The activity has been deleted.";  	
	            	$('#response').remove();
	            	$('#loading').remove();
	            	$('#messages').append('<p id ="response">' + message + '</p>');
	            	$("#summaryTable tr").remove();
	            	addSummaryTable();
	               },
	               error: function(result){
	            	   $('#response').remove(); // remove messages
	            	   $('#loading').remove(); // remove loading image
	            	   $('#messages').append('<p id = "response">' + result + '</p>'); // add error message
	               }
	         });         
		// delete record
	} else {	
		// do nothing
	}
});


/* ADD TIME SLIDER   */
function addSlider(){
	
	var min = 1550;
	var max = 1750;
	var filterDateType = getParameterByName('filterDateType');
	
	var filterYearFrom = getParameterByName('filterYearFrom');
	var valFromUse =  (filterYearFrom == "") ? min : filterYearFrom;
	
	var filterYearTo = getParameterByName('filterYearTo');
	var valToUse =  (filterYearTo == "") ? max : filterYearTo;
	
	var arrValues = ((filterDateType == "Duration") || (filterDateType == "Between")) ? [valFromUse, valToUse] : [valFromUse, valToUse];
	var range = (filterYearTo == "") ? false: true;

	
  
$( "#slider-range" ).slider({
  range: range,
  min: min,
  max: max,
  values: arrValues ,
  slide: function( event, ui ) { 
	  $("input[name='filterYearFrom']").val(ui.values[0] ); 
	  $("input[name='filterYearTo']").val( ui.values[1] ); 
      },

change: function( event, ui ) {
	  $("#form1").submit();
	  }

});
	
	
}

function addDateFilters(){
	
	var filterDateType = getParameterByName('filterDateType');
	var filterYearFrom = getParameterByName('filterYearFrom');
	var filterYearTo = getParameterByName('filterYearTo');
	
if (filterYearFrom != ""){
 	$("input[name='filterYearFrom']").val(filterYearFrom); 
	$("input[name='filterYearFromText']").val(filterYearFrom);
	


	addDateFilter('YearFrom', 'Year from', filterYearFrom );
}
	 if (filterYearTo != "") { 
		 $("input[name='filterYearTo']").val(filterYearTo); 
		 $("input[name='filterYearToText']").val(filterYearTo);
		addDateFilter('YearTo', 'Year to', filterYearTo );
}


	 if (filterDateType != "") { 
		 $("input[name='filterDateType']").val(filterDateType); 
		 $("input[name='filterDateTypeText']").val(filterDateType);
		addDateFilter('DateType', 'Date type', filterDateType );
}


	 if (filterDateType != "") { 
		 $("select[name='filterDateType']").val(filterDateType);
		 
		 switch(filterDateType) {
	    		case "After":
		
	  		case "Before":
				$("input[name='filterYearTo']").val('');
				$("input[name='filterYearToText']").val('');
	    			$("#spanYearTo").hide(); // hide year to input field
	    			break;
	    		case "Between":
	    		case "Duration":
	    			$("#spanYearTo").show(); // show filter year to input field
	    
	    			break;   
	    		default:
				$("input[name='filterYearTo']").val('');
				$("input[name='filterYearToText']").val('');
				$("#spanYearTo").hide();      
		}
	 };
	
}


function addDateFilter(objType, label, value){


	var spanFilter = eval('$("span.filter' + objType + '")');
  
  	var html =   
	'  <div class="small divFilter divFilter' + objType +'">'+ label +':' +
	'   <div class="row  collapse"> ' +
	'   <div class="small-10 columns">' +
	'     <input type="text" name= "f' + objType +'" readonly placeholder="' + value + '">' +	
	'   </div>' +
	'   <div class="small-2 columns">' +
	'     <div id="' + objType + '" class="button postfix deleteFilter">X</div>' +
	'   </div>' +
	'   </div>' ;
  

  		 $(spanFilter).html(html) 
  	


}


function addFilterPerson(){

var person_id = getParameterByName('person_id');

if (person_id != ''){
  	var html =   
	'  <div class="small divFilter divFilterPerson">Person:' +
	'   <div class="row  collapse"> ' +
	'   <div class="small-10 columns">' +
	'     <input type="text" id="fPerson" name= "fPerson" readonly placeholder="">' +	
	'   </div>' +
	'   <div class="small-2 columns">' +
	'     <div id="Person" class="button postfix deleteFilter">X</div>' +
	'   </div>' +
	'   </div>' ;
  
  		$('span.filterPerson').html(html) ;
		personName = getPersonName(person_id);
		$('#fPerson').attr('placeholder', personName);
		$('input[name="filterPersonText"]').val(personName);
		$('input[name="filterPerson"]').val(person_id);
}
}


function addFilter(objType, label, parameter , urlQuery, dataType){
	
	var filterId 	= "filter" + objType;
	var filterIdText 	= "filter" + objType + "Text";
	
	var selectedFilter = getParameterByName(filterId);
	var selectedFilterText = getParameterByName(filterIdText);
  	
	var inputFilter = eval('$("input[name=filter' + objType + ']")');
	var inputFilterText = eval('$("input[name=filter' + objType + 'Text]")');
	
	var spanFilter = eval('$("span.filter' + objType + '")');
	
	

	$(inputFilter).val(selectedFilter);
  	$(inputFilterText).val(selectedFilterText);
  
  	var html =   
	'  <div class="small divFilter divFilter' + objType +'">'+ label +':' +
	'   <div class="row  collapse"> ' +
	'   <div class="small-10 columns">' +
	'     <input type="text" name= "f' + objType +'" readonly placeholder="' + selectedFilterText + '">' +	
	'   </div>' +
	'   <div class="small-2 columns">' +
	'     <div id="' + objType + '" class="button postfix deleteFilter">X</div>' +
	'   </div>' +
	'   </div>' ;
  
  
  	/* */
  	 if (selectedFilter != "") {	 
  		 $(spanFilter).html(html) 
  		 $(inputFilter).val(selectedFilter);
  	 };
  
  	  
  // POPULATE FILTER SELECT BOX
  	 switch (objType){
  	 
  	 	case "Person":
  	 		addSelect2Person($(".Person"), "Select a person", urlQuery, dataType);
  	 		break;
  	 	case "Activity":
  	 		populateActivity("data/activityGroup.json", ".Activity")
  	 		break;
  	 	case "Location":
  		 	addSelect2Param($(".Location"), "Select a location", urlQuery, "item.emloid", "item.label", dataType);
  		 	break;	 	
  	 	case "Editor":
  		 	addSelect2($(".Editor"), "Select an editor", urlQuery, "item.id", "item.id", dataType);
  		 	break;
  	 }
		 
}


function parseLocation(location){
	var html = "";
	$.each(location, function(i, loc){
		html += loc.location_name + '     ';
	});
	return html;
}


function addSummaryTable(){
	/* add table containing summary of all activities */		
	 
	var limit = configLimit;
	var offset = (getParameterByName('offset') > 0) ? parseInt(getParameterByName('offset')) : 0;
	var person_id = getParameterByName('filterPerson');// PERSON
	if (person_id == "") {  person_id = getParameterByName('person_id') }
	var activity_type = getParameterByName('filterActivity');// ACTIVITY
	var location = getParameterByName('filterLocation');// LOCATION
	var dateType =  getParameterByName('filterDateType') ; // DATE
	var yearFrom = getParameterByName('filterYearFrom');
	var yearTo = getParameterByName('filterYearTo');	 
	var editor = getParameterByName('filterEditor');// EDITOR
	var url =  urlActivitySelect 
			+ "?filterEditor=" + editor 
			+ "&filterDateType=" + dateType 
			+ "&filterYearFrom=" + yearFrom 
			+ "&filterYearTo=" + yearTo 
			+ "&filterPerson=" + person_id 
			+ "&filterActivity=" + activity_type 
			+ "&filterLocation=" + location 
			+ "&limit=" + limit 
			+ "&offset=" + offset;
	
	var heading = summaryTableHeading();  
	$(heading).appendTo("#summaryTable");
	
	$.getJSON(url,function(result){
			$.each(result, function(i,data){
				var html = rowHTML(data);
				$(html).appendTo("#summaryTable");
			});			
		});
	}


function rowHTML(data){
	
	var activity_type_id = 	 data.activity_type_id;
	var primary_participant = (data.primary_person[0]) ? data.primary_person[0].foaf_name : "";
	var datec = new Date(data.change_timestamp);
	var location = parseLocation(data.location); // location info
	
	var row =
	'<tr><td><img src="img/view.png" data-reveal-id="myModal"'
	+ 'class="viewRecord" id="viewRecord' + data.id + '" '
	+ 'title="Click to view record in modal window"/></td>'
	+ '<td>' + primary_participant 	+ '</td>'
	+ '<td>' + activity_type_id + '</td>'
	+ '<td>' + data.date_type + '</td>'
	+ '<td>' + data.date_from_year + '</td>'
	+ '<td>' + data.date_to_year + '</td>'
	+ '<td>' + location	+ '</td>'							
	+ '</tr>';
	
	
	
	return row;
}


function addPagination(){

	var personId = getParameterByName('filterPerson');// PERSON
	if (personId == "") {  personId = getParameterByName('person_id') }
	

	var activityType = getParameterByName('filterActivity');// ACTIVITY
	var location = getParameterByName('filterLocation');// LOCATION
	var dateType =  getParameterByName('filterDateType') ; // DATE
	var yearFrom = getParameterByName('filterYearFrom');
	var yearTo = getParameterByName('filterYearTo');	 
	var editor = getParameterByName('filterEditor');	 // EDITOR


	var locationText = getParameterByName('filterLocationText');
	var limit = configLimit; // how many records to return
	var url = urlActivityCount + "?filterEditor=" + editor +"&filterDateType=" + dateType +"&filterYearFrom=" + yearFrom + "&filterYearTo=" + yearTo + "&filterPerson=" + personId + "&filterActivity=" + activityType + "&filterLocation=" + location;
	
	$.getJSON(url,function(result){
		
		var recordCount = parseInt(result[0].count); // total number of records
		var pageCount = parseInt(recordCount / limit) // total page count
		if (recordCount % limit > 0){ pageCount += 1;	}
		
		var offset 		= (getParameterByName('offset') > 0) ? parseInt(getParameterByName('offset')) : 0;
		var offsetPrev 	= ((offset - (limit)) > 0) ? (offset - limit) : 0;
		var offsetNext 	= offset + limit;
		var offsetEnd 	= ((parseInt(recordCount / limit)* limit));
		offsetEnd 		= (offsetEnd < 1) ? 1 : offsetEnd;
		var  offsetCurrentLabel = parseInt(offset / limit);
		 offsetCurrentLabel = (offsetCurrentLabel < 1) ? 1 : offsetCurrentLabel += 1;
		
		var offsetMinus10 = ((offset - (limit * 10)) > 0) ? (offset - (limit * 10)) : 0;

		var offsetPlus10 = ((offset + (limit * 10)) < offsetEnd) ? (offset + (limit * 10)) : offsetEnd;
		var filterURL = getFilterURL(); // construct filter url
		 
		var currentPage = offsetCurrentLabel;
		var totalPages = ~~(recordCount / limit) + 1;		

		var html = 
		'<p>' + recordCount + ' results (20 per page)<br/>'
		+ 'Page ' + offsetCurrentLabel + ' of ' + totalPages	 
		+ '</p><ul class="pagination">' 
		+  '<li class=""><a href="?' + filterURL +'&offset=0">First</a></li>';
		
		html += 
		'<li class="arrow"><a href="?' + filterURL +'&offset=' + offsetMinus10 +'">&lt;&lt;</a></li>'
		
		html +=  (offset > 0) ? 
		'<li class="arrow"><a href="?' + filterURL +'&offset='+ offsetPrev +'">' + (offsetCurrentLabel - 1) + '</a></li>' 
			: '';
		
		html +=
		'<li  class="current"><a href="?' + filterURL 
		+'&offset='+ offset  +'">'
		+ offsetCurrentLabel +'</a></li>' ;
			
		html += (offsetNext < recordCount) ? 
		'<li class="arrow"><a href="?' + filterURL +'&offset='+ offsetNext +'">' + (offsetCurrentLabel + 1) +'</a></li>' 
			: '';


		html += 
		'<li class="arrow"><a href="?' + filterURL +'&offset=' + offsetPlus10 +'">&gt;&gt;</a></li>'

					
			html += ' <li class="arrow"><a href="?' + filterURL 
				 	+ '&offset='+ offsetEnd +'">Last</a></li></ul>';
		
		$(html).appendTo(".pagination-centered");
	});
}



function getPersonName(personId){
	var result = $.ajax({
	url: "https://emlo-edit.bodleian.ox.ac.uk/interface/proform/ws/person.php/?id=" + personId,
	async: false
}).responseText;

	value = jQuery.parseJSON(result);
	value = value[0].name;
	return value;

}

function getFilterURL(){
	
	var personId = getParameterByName('filterPerson');// PERSON
	if (personId == "") {  personId = getParameterByName('person_id') }
	var personText = getParameterByName('filterPersonText');

	if (personText == ""){
		// get name using person id
		personText = getPersonName(personId);
}

	var activityType = getParameterByName('filterActivity');// ACTIVITY
	var location = getParameterByName('filterLocation');// LOCATION
	var locationText = getParameterByName('filterLocationText');
	var dateType =  getParameterByName('filterDateType') ; // DATE
	var yearFrom = getParameterByName('filterYearFrom');
	var yearTo = getParameterByName('filterYearTo');	 
	var editor = getParameterByName('filterEditor');	 // EDITOR
	
	var arrFilters = {
			"activity"	:[{"name": "filterActivity", 	"id": activityType }],
			"person"	:[{	"name": "filterPerson", "id": personId, "text": personText }],
			"location"	:[{ "name": "filterLocation", "id": location, "text": locationText	}],
			"dateType"	:[{"name": "filterDateType", "id": dateType}],
			"yearFrom"	:[{"name": "filterYearFrom", "id": yearFrom}],
			"yearTo"	:[{"name": "filterYearTo",  "id": yearTo}],
			"editor"	:[{"name": "filterEditor", "id": editor}]			 
	 };
	
	
	 var filterURL = "";
	 
	 for (var f in arrFilters) {
		   filterURL += arrFilters[f][0].name + "=" + arrFilters[f][0].id + "&"; 
		   if (arrFilters[f][0].text != ''){
			   filterURL += (arrFilters[f][0].text) ? arrFilters[f][0].name + "Text=" + arrFilters[f][0].text + "&" : arrFilters[f][0].name + "Text=" + arrFilters[f][0].id + "&";
		   } 
		}
	
	return filterURL;
}





/* display a selected record, with buttons to edit or delete the record  */
function showRecord(activityId){
	
	
	
	
	// get selected activity record
	var url = urlActivitySelect + "?id=" + activityId;
	
	$.getJSON(url,function(data){
		$.each(data, function(i,data) {
			if (data.id == activityId) {
	            // found it...
					var html = htmlEditButtons(data.id);
					html += '<a class="close-reveal-modal">&#215;</a>';
					html += htmlActivity(data);
					html += htmlPrimaryPerson(data);
					html += htmlSecondaryParticipant(data);
					
					html += htmlTime(data);
					html += htmlLocation(data);
					html += htmlSource(data);
					html += htmlNotes(data);
					html += htmlRelatedActivity(data);
					html += '</div>';
	
					
					$("#myModalContent").html(html);
					
					addLabelsAll(data);
					return false; // stops the loop
	        }	
		});   
	});
}




function htmlEditButtons(id){
	var html 	= ' <div id="panel' + id 
				+ '" class="content">' 
				+ '<span class="edit button tiny editRecord" id="editRecord' + 	id 
				+ '" title="Edit this record"> Edit </span> <span title="Delete this record" class="' 
				+ ' button tiny deleteRecord" id="deleteRecord' + 
				+ id +'"> Delete </span>';
	return html;
}


function htmlPrimaryPerson(data){
	
	var primary_person = (data.primary_person[0]) ?  data.primary_person[0].foaf_name : "";
	
	var html = 	'<fieldset class="">' 
				+ '<legend>Primary Participant - Person</legend>' 
				+ '<ul class="small-block-grid-2">' 
				+ '<li><label>Person</label>'+ primary_person +'</li>' 
				+ '</ul>'
				+ htmlRole(data)
				+ '</fieldset>';
	
	return html;
}


function htmlSecondaryParticipant(data){
	var primary_person = (data.primary_person[0]) ? data.primary_person[0].person_id : "";
	var html 	= 	'<fieldset>' 
				+ 	'<legend>Secondary Participant </legend>' ;

	$.each(data.role_in_activity, function(i,d){
		// iterate through each role in activity ignoring primary person
		if ((d.entity_id == primary_person) && (d.entity_type == 'Person')) 
		{} 
		else {
		  html 	+=	'<fieldset>' 
				+ '<ul class="small-block-grid-3">' 
				+ '<li><label>Entity Type</label> ' + d.entity_type + '</li>' 
				+ '<li><label>Label</label>' + d.entity_name +'</li>' 
				+ '<li><label>Role</label>' + d.role_id + '</li>' 
				+ '</ul>' 
				+ '</fieldset>';
			}
	});
			
	html += '</fieldset>';	
	return html;
}


function htmlActivity(data){
	
	var activity_type_id = data.activity_type_id;
	var activity_name = data.activity_name;
	var activity_description = data.activity_description;
	
	var html 	= '<fieldset class="">' 
				+ '<legend>Activity</legend>' 
				+ '<ul class="small-block-grid-4">' 
				+ '<li><label>Activity Type</label>'+ activity_type_id +' </li>' 
				+ '<li><label>Activity Name</label>'+ activity_name +'</li>' 
				+ '<li><label>Activity Description</label>'+ activity_description +'</li>' 
				+ '<li/></ul></fieldset>' ;
	
	return html;
}


function htmlRelatedActivity(data){
	var html = '<fieldset class="fieldsetRelatedActivity">' 
		 + '<legend>Related Activities</legend>' 
		 + '<div class="divRelatedActivity" >' ;

$.each(data.related_activity, function(i,d){	
	html +='<span   title="Click to view a related event" class="viewRecord linkRelatedActivity" id="viewRecord' + d.related_activity_id + '"  >' + d.activity_type_id + ' ' +  d.activity_name +' ' + d.date_from_year +'</span>&nbsp;&nbsp;&nbsp;     ';
	});	
	html += '</div></fieldset>' ;
	
	
return html;
	
	
	
}


function htmlRole(data){
	var html = '<fieldset class="fieldsetRole">' 
			 + '<legend>Roles and Relationship</legend>' 
			 + '<div class="divRole" >' ;
	
	$.each(data.role_in_activity, function(i,d){	
		if (d.entity_id == data.primary_person[0].person_id) {
			html += '<ul class="small-block-grid-3"><li><label>Role</label>' + d.role_id + '</li></ul>';
			html += htmlRelations(data, d.entity_id, d.role_id);	
			}
		});	
		html += '</div></fieldset>' ;
	return html;
}


function htmlRelations(data, personid, roleid){
	
	var html = '<fieldset class="fieldsetRole"><legend>Relationships</legend><div class="divRole" >';
	/* RELATONSHIPS */
	$.each(data.relationship, function(i,d){
		/* if the subject type is a person or organisation or document or group there will only be an identifier in the db - need html to replace id with text value*/
		if (  ((personid == d.subject_id ) && (d.subject_type == 'Person')) || ((d.subject_type == 'Group') && (d.subject_role_id == roleid)) || ((d.subject_type == 'Organisation') && (d.subject_role_id == roleid)) || ((d.subject_type == 'Document') && (d.subject_role_id == roleid)) ){
			html +=
			'<ul class="small-block-grid-4">' +
				'' +
				'<li><label>Relationship Type</label>' + d.relationship_id + '</li>' +
				'<li><label>Entity Type</label>' + d.object_type + ' </li>' +
				'<li><label>Label</label><span class="' + d.object_type + 'label'+  d.object_id  +'"/>'  + '</li>' +
				' <li><label>Role</label>' + d.object_role_id + '</li>' +
				
				'</ul>';
			}
	});
	
	html += '</div></fieldset>';
	
	return html;
	
	
}




/* TIME */
function htmlTime(data){
	
	var date_type = (data.date_type != 0)? data.date_type : "";	
	
	
	
	var date_from_year =  data.date_from_year;
	var date_from_month =  $.grep(arrayMonth, function(e){ return e.id == data.date_from_month; });
	var date_from_month_text = (date_from_month[0]) ? date_from_month[0].text : date_from_month;	
	var date_from_day =  data.date_from_day;
	var date_to_year = data.date_to_year;
	var date_to_month =   $.grep(arrayMonth, function(e){ return e.id == data.date_to_month; });
	var date_to_month_text = (date_to_month[0]) ? date_to_month[0].text : date_to_month;	
	var date_to_day =  data.date_to_day;
	


	
	
	html =		'<fieldset><legend>Time</legend><label>Date type</label> '+date_type+' <br />' +
				'<br /><ul class="small-block-grid-4">' +
'				<li><label for="date_from_year">Year From</label>'+date_from_year+' </li>' +
'				<li><label for="date_from_month">Month From</label>'+ date_from_month_text +' </li>' +
'				<li><label for="date_from_day">Day From</label>'+ date_from_day +' </li>' +
'				<li><label for="date_from_uncertainty">Uncertainty</label>'+data.date_from_uncertainty+' </li>' +
'				<li><label for="date_to_year">Year To</label> '+date_to_year+'</li>' +
'				<li><label for="date_to_month">Month To</label>'+ date_to_month_text +'</li>' +
'				<li><label for="date_to_day">Day To</label>'+date_to_day +' </li>' +
'				<li><label for="date_to_uncertainty">Uncertainty</label> '+data.date_to_uncertainty +'</li>' +
'			</ul></fieldset>';
	return html;
}



function htmlLocation(data){
	var html = '<fieldset><legend>Location</legend>';
	$.each(data.location, function(i,d){
		html +=	'<ul class="small-block-grid-2"><li><label for="location">Location</label>' + d.location_name +'</li><li><br /></li></ul>' ;});
	html += '</fieldset>';
	return html;
}


function htmlSource(data){
	var html =	'<fieldset><legend>Textual Source</legend>';
	$.each(data.assertion, function(i,d){
		html +=	
			'<ul class="small-block-grid-2"><li><label for="source_id_1">Textual Source</label>' +  d.fullBibliographicDetails + '</li>' +
			'<li><label for="source_details">Source Details</label>' + d.source_description +  '</li><li><br/></li></ul>';
	});
	html += '</fieldset>' ;
	return html;
}


function htmlNotes(data){
	html = 
		'<fieldset><legend>Data Source</legend><ul class="small-block-grid-2">' +
		'<li><label >Contributing Authors</label> '+data.notes_used+'</li>' +
		'</ul></fieldset>' + 
		'<fieldset><legend>Additional Notes</legend><ul class="small-block-grid-2">' +
		'<li><label for="additional_notes">Additional Notes</label>'+data.additional_notes+'</li>' +
		'</ul></fieldset>';
	return html;
}


function divData(data){	
	var activity_type_id = 	 data.activity_type_id;
	var activity_name = data.activity_name;
	var activity_description = data.activity_description;
	var date_from_year = (data.date_from_year != 0)? data.date_from_year : "";
	var date_from_month = (data.date_from_month != 0)? data.date_from_month : "";
	var date_from_day = (data.date_from_day != 0)? data.date_from_day : "";
	
	var date_to_year = (data.date_to_year != 0)? data.date_to_year : "";
	var date_to_month = (data.date_to_month != 0)? data.date_to_month : "";
	var date_to_day = (data.date_to_day != 0)? data.date_to_day : "";
		
var div_data =

'	<dd class="accordion-navigation">' +
' <a href="#panel' +data.id +'"><b>' +data.text +'</b>       last updated:'+ data.change_timestamp +'</a>' +
' <div id="panel' +data.id +'" class="content">' +
'<fieldset class="">' +
'<legend>Activity</legend>' +
'<ul class="small-block-grid-4">' +
	'<li><label>Activity Type</label>'+ activity_type_id +' </li>' +
	'<li><label>Activity Name</label>'+ activity_name +'</li>' +
	'<li><label>Activity Description</label>'+ activity_description +'</li>' +
	'<li/>' +
'</ul>' +
'</fieldset>' ;


div_data +=
'<fieldset class="fieldsetRole">' +
'	<legend>Roles</legend>' +
'	<div class="divRole" >';


$.each(data.role_in_activity, function(i,d){

div_data +=
			'<ul class="small-block-grid-3">' +
				'<li>' +
				'<label>Entity Type</label>' + d.entity_type + 
				'</li><li>' +
				'<label>Entity Id</label>' + d.entity_id +
				' </li>' +
				' <li><label>Role</label>' + d.role_id +
				'</li>' +
				'</ul>';
	});
	
	
div_data += '</div></fieldset>' ;
div_data +=
'<fieldset class="fieldsetRelationship">' +
'	<legend>Relationships</legend>' +
'	<div class="divRelationship" >';



$.each(data.relationship, function(i,d){

div_data +=
	'<ul class="small-block-grid-3">' +
		'<li>' +
		'<label>Relationship Type</label>' + d.relationship_id + 
		'</li><li>' +
		'<label>Entity Type</label>' + d.object_type +
		' </li>' +
		' <li><label>Entity</label>' + d.object_id +
		
		'</li>' +
		'</ul>';
});

div_data += '</div></fieldset>' ;



div_data +=		'<fieldset>' +
'		<legend>Time</legend>' +
'		<label>Date type</label>  <br />' +
'		<br />' +
'		<ul class="small-block-grid-4">' +

'				<li><label for="date_from_year">Year From</label>'+date_from_year+' </li>' +
'				<li><label for="date_from_month">Month From</label>'+date_from_month+' </li>' +
'				<li><label for="date_from_day">Day From</label>'+ date_from_day +' </li>' +
'				<li><label for="date_from_uncertainty">Uncertainty</label>'+data.date_from_uncertainty+' </li>' +
'				<li><label for="date_to_year">Year To</label> '+date_to_year+'</li>' +
'				<li><label for="date_to_month">Month To</label>'+date_to_month+'</li>' +
'				<li><label for="date_to_day">Day To</label>'+date_to_day +' </li>' +
'				<li><label for="date_to_uncertainty">Uncertainty</label> '+data.date_to_uncertainty +'</li>' +
'			</ul>' +
'		</fieldset>' +

'		<fieldset>' +
'			<legend>Location</legend>';


$.each(data.location, function(i,d){

div_data +=	
'<ul class="small-block-grid-1">' +
'				<li><label for="location">' + d.location_id +'</label> </li>' +
		
'			</ul>' ;

});


div_data += '	</fieldset>' +

'		<fieldset>' +
'			<legend>Source</legend>';


$.each(data.assertion, function(i,d){

div_data +=	
'	<ul class="small-block-grid-4">' +
'				<li><label for="source_id_1">Textual Source</label>' +  d.source_id + '</li>' +
'				<li><label for="source_details">Source Details</label>' + d.source_description +  '</li>' +

'				<li><br/>' +
'				</li>' +
'			</ul>';

});



div_data += '	</fieldset>' +
'		<fieldset>' +
'			<legend>Notes</legend>' +

'			<ul class="small-block-grid-4">' +
'				<li><label for="notes_used">Whose notes did you use?</label> '+data.notes_used+'</li>' +
'				<li><label for="additional_notes">Additional Notes</label>'+data.additional_notes+'</li>' +

'				<li></li>' +

'				<li></li>' +
'			</ul>' +
'</fieldset>' 
'</div>' +
'</dd>';

return div_data;
	
}

// FILTER DATE ACTIONS - SUBMIT FORM WHEN VALUE CHANGES
$('body').on('change', 'select[name="filterDateType"]', function() {  $(this).parents("form").submit();   });
$('body').on('change', 'input[name="filterYearFrom"]', function() {	 $(this).parents("form").submit();   });
$('body').on('change', 'input[name="filterYearTo"]', function() {  $(this).parents("form").submit();   });


/* FILTER PERSON */
$('body').on('change', '.Person', function() {
	var personId = $('#Person').select2('data').id;
	var url = urlActivitySelect + "subject=" +  $('#Person').select2('data').id;
	var div_data = "";
		
	$.getJSON(urlActivitySelect,function(data){
			$.each(data, function(i,data){
				if (personId != '') {
					$.each(data.role_in_activity, function(i, v) {
				    	if ((v.entity_type == 'Person') && (v.entity_id == personId)) { div_data += divData(data);}
					});
				} else { div_data += divData(data);	}
				
				$(".accordion-navigation").remove();
				$(div_data).appendTo("#accordian");
			});
		});
	return false;
});







function populateActivity(file, element){
		 $.getJSON(file, function(json) {  
	    	 $(element).select2({ 
	    			data:json,
         	  });
	     });
	}






function summaryTableHeading(){
	
	var html =
		'<tr>' +
		'<th>View/ Edit Record</th>' +
		'<th>Primary Participant</th>' +
		'<th>Activity / Relationship Type</th>' +
		'<th>Date Type</th>' +
		'<th>Year From</th>' +
		'<th>Year To</th>' +
		'<th>Location</th>' +
		'</tr>';
	
	return html;
	
	
}


/* TO DELETE */
/* FILTER LOCATION 
$('body').on('change', '.Location', function() {
	  
	var selectedLocation = ($(".Location").select2('data')) ? $(".Location").select2('data').id : "";
	var selectedLocationText = ($(".Location").select2('data')) ? $(".Location").select2('data').text : "";
		
	$("input[name='filterLocation']").val(selectedLocation);
	$("input[name='filterLocationText']").val(selectedLocationText);
	  
	$(this).parents("form").submit();   
 });


*/

/* FILTER EDITOR
$('body').on('change', '.Editor', function() {
	  
	var selected = ($(".Editor").select2('data')) ? $(".Editor").select2('data').id : "";
	
	$("input[name='filterEditor']").val(selected);
  
	$(this).parents("form").submit();   
 });

 */

//submit form if any of the filters are changed

/*
$('body').on('change', '.Person', function() {	
	// set values of hidden input fields and submit form  
	var selectedPerson = ($(".Person").select2('data')) ? $(".Person").select2('data').id : "";
	var selectedPersonText = ($(".Person").select2('data')) ? $(".Person").select2('data').text : "";
	  
		$("input[name='filterPerson']").val(selectedPerson);
		$("input[name='filterPersonText']").val(selectedPersonText);
	  
	    $(this).parents("form").submit();   
 });
	  */

/* FILTER ACTIVITY 
$('body').on('change', '.Activity', function() {
	var selectedActivity = ($(".Activity").select2('data')) ? $(".Activity").select2('data').id : "";
	var selectedActivityText = ($(".Activity").select2('data')) ? $(".Activity").select2('data').text : "";
		
	$("input[name='filterActivity']").val(selectedActivity);
	$("input[name='filterActivityText']").val(selectedActivityText);
	  
	$(this).parents("form").submit();   
 });
 
 
 
 
 
 
 $('body').on('click', '.deleteFilterLocation', function() {
	  $(".divFilterLocation").remove();	  
	  $("input[name='filterLocation']").val("");
	  $("input[name='filterLocationText']").val("");
	 	$('#form1').submit(); 
});


$('body').on('click', '.deleteFilterEditor', function() {
	  $(".divFilterEditor").remove();	  
	  $("input[name='filterEditor']").val("");
	 	$('#form1').submit(); 
});



$('body').on('click', '.deleteFilterActivity', function() {
	  $(".divFilterActivity").remove();	  
	  $("input[name='filterActivity']").val("");
	  $("input[name='filterActivityText']").val("");
	 	$('#form1').submit(); 
});


$('body').on('click', '.deleteFilterPerson', function() {
	$(".divFilterPerson").remove();
	$("input[name='filterPerson']").val("");
	$("input[name='filterPersonText']").val("");
	

	$('#form1').submit(); 
	
});


*/



/*

// ACTIVITY	 
var selectedActivity = getParameterByName('filterActivity');    	 
	var selectedActivityText = getParameterByName('filterActivityText');
	var htmlActivity = 
'  <div class="small divFilter divFilterActivity">Activity: ' +
'   <div class="row collapse">' +
'   <div class="small-10 columns">' +
'     <input type="text"  readonly placeholder="' + selectedActivity + '">' +
'   </div>' +
'   <div class="small-2 columns">' +
'     <div id="Activity" class="button postfix deleteFilter">X</div>' +
'   </div>' +
'   </div>';


if (selectedActivity != "") { $(".filterActivity").html(htmlActivity)};

$("input[name='filterActivity']").val(selectedActivity);
$("input[name='filterActivityText']").val(selectedActivityText);


// activity type
populateActivity("data/activityGroup.json", ".Activity")

// date
*/



/*
// LOCATION
var selectedLocation = getParameterByName('filterLocation');
var selectedLocationText = getParameterByName('filterLocationText');
	
$("input[name='filterLocation']").val(selectedLocation);
	$("input[name='filterLocationText']").val(selectedLocationText);

	var htmlLocation = 
	  '  <div class="small divFilter divFilterLocation">Location: ' +
  '   <div class="row collapse">' +
  '   <div class="small-10 columns">' +
  '     <input type="text" name= "ctlFilterLocation" readonly placeholder="' + selectedLocationText + '">' +
  '   </div>' +
  '   <div class="small-2 columns">' +
  '     <div id="Location" class="button postfix deleteFilter deleteFilterLocation">X</div>' +
  '   </div>' +
  '   </div>' ;

	// if a location has been selected, display in a text input box
	 if (selectedLocation != "") { $(".filterLocation").html(htmlLocation) };

// POPULATE FILTER SELECT BOX
addSelect2Param($(".Location"), "Select a location", urlPlaceAll, "item.emloid", "item.label", "json");
  
  
  
  
  
  
  
  	// EDITOR
	var selectedEditor = getParameterByName('filterEditor');
	$("input[name='filterEditor']").val(selectedEditor);
	
	
	var htmlEditor = 
	  	  '  <div class="small divFilter divFilterEditor">Editor: ' +
		  '   <div class="row collapse">' +
		  '   <div class="small-10 columns">' +
		  '     <input type="text" name= "ctlFilterEditor" readonly placeholder="' + selectedEditor + '">' +
		  '   </div>' +
		  '   <div class="small-2 columns">' +
		  '     <div id="Editor" class="button postfix deleteFilter deleteFilterEditor">X</div>' +
		  '   </div>' +
		  '   </div>' ;
	
	 if (selectedEditor != "") { $(".filterEditor").html(htmlEditor) };
	
	addSelect2($(".Editor"), "Select an editor", urlEditor, "item.id", "item.id", "json");
 	
  
  /*
		$.getJSON(urlPersonAll + "?id=" + person_id,function(personAll){		
					$.each(personAll, function(i,person){
							if (person.emloid == person_id) { 					
								var selectedPersonText = person.name + ' ' + person.date;			
								$("input[name='ctlFilterPerson']").val(selectedPersonText);
								$("input[name='filterPersonText']").val(selectedPersonText);
							}
					});	
		});
	  */

  




