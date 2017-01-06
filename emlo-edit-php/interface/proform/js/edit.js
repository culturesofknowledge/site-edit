 
     $(document).ready(function() { 
    	 var activity_id = getParameterByName('id');
    	 
		// get activity record as json file and load data into input form
		$.getJSON(urlActivitySelect + "?id=" + activity_id,function(data){
			$.each(data, function(i,data){
					
				
				addHiddenFields(data);
				
				addPrimaryPerson(data);
				
				addSecondaryParticipant(data);
				
				addActivity(data);

				
				
				addDates(data);
				
				addLocations(data);
				
				addTextualSources(data);
				
				addNotes(data);
				
				addLabelsAll(data);

				
				// if there is only one role, then do not show the remove role button
				if ($(".divRole").length == 1){ $(".removeRole").hide();}
					
				});	
			});
     	});  

  
     
     $('body').on('click', '.removeEntitySP', function() {	
    		$(this).parents(".divSP").first().remove();  });
    	  

     
     
    function addHiddenFields(data){
    	
    	 var activity_id = getParameterByName('id');
    	
    	// set value of hidden field that will be used to delete activity 
		$("input[name='activity_id']").val(activity_id);
		
		$("input[name='creation_timestamp']").val(data.creation_timestamp);
		
		$("input[name='creation_user']").val(data.creation_user);
		
    }
     
     
  
	function addPrimaryPerson(data){
   	  var personid = data.primary_person[0].person_id;
   	  var elementid = "#fieldsetPrimaryPerson";
   	  var url = urlPersonAll + "?id=" + personid;
   		
   	  $.getJSON(url,function(personAll){
   					$.each(personAll, function(i,person){
   							if (person.emloid == personid) { returnValue = person.name; return;}
   							});
   	 		var html = 
   			 '<legend>Primary Participant - Person</legend><ul class="small-block-grid-2"><li><label>Person <small>required</small></label>' +
   			 '<input type="hidden" name="subject" value="' + personid + '"/><input type="text" readonly name="subjectval"  id="PersonPrimary" class="Person PersonPrimary small-12" value="' + returnValue + '"/>' +
   			 '</li><li><br/><span class="button tiny removePrimaryPerson">Remove</span>' +
   			 '</li></ul>' +
   			 
   			'<fieldset id="fieldsetRole" class="fieldsetRole">' +
				'<legend>Roles and Relationships</legend>' +
			'</fieldset>' ;
   			 
   			$(elementid).html(html);
   			
   			addRoles(data);
   		 });	
     }
  
	
	 /*  used in edit.html to remove the readonly primary person field, and replace it with a select box */
    $('body').on('click', '.removePrimaryPerson', function() {	
   	 
   		var html = 
   	 	'<ul class="small-block-grid-2">'+
   	 		'<li>'+
   	 		'<div class="input-wrapper">'+
   	 			'<label>Person <small>required</small></label>'+
   	 			'<input type="hidden" name="subject" value=""/><input type="hidden" name="subjectval"  class="Person PersonPrimary small-12" />'+
   	 			'<small class="error">Person is a required field.</small>'+
   	 			'</div>'+
   	 		'</li>'+
			'</ul>';
   	 
   	 
   		 $(this).parents("fieldset").append(html);
   		 
   		 $(this).parents("ul").first().remove(); 
   		 
   		 addSelect2Person($(".PersonPrimary"), "Select a person", urlPersonAll, "json");
   		    	
	   
 });
	
	
	 
	 /*
	 the value of the primary person was not included in the submitted form data, so explicitly setting it on change in value of the drop down
	 */
	 
    $('body').on('change', "input[name='subjectval']", function() {	
    
    	var value = $("input[name='subjectval']").select2('data').id;
    	
    	$("input[name='subject']").val(value);
    	
    	   
     });
	 
	  
  
  function addActivity(data){
	  
	  
	// populate Activity field with list of activities from activityGroup.json file 
      $.getJSON("data/activityGroup.json", function(json) {            	 
     	 $(".Activity").select2({  data:json  });
    
	// POPULATE ACTIVITY DESCRIPTION 
		$(".Activity").select2("val", data.activity_type_id);
		$("input[name='activity_name']").val(data.activity_name);
		$("textarea[name='activity_description']").val(data.activity_description);
	  
  });
  
  }

  function addDates(data){
	  
	  
	  $(".Month").select2({data:arrayMonth});         
      $(".Day").select2({data:arrayDay});
      $(".Uncertainty").select2({data:arrayUncertain});
	  
	// POPULATE DATE VALUES
		$(".DateType").val(data.date_type);
		$("input[name='date_from_year']").val(data.date_from_year);
		$("input[name='date_from_month']").select2("val", data.date_from_month);
		$("input[name='date_from_day']").select2("val", data.date_from_day);
		$("input[name='date_from_uncertainty']").select2("val", data.date_from_uncertainty);
		
		$("input[name='date_to_year']").val(data.date_to_year);
		$("input[name='date_to_month']").select2("val", data.date_to_month);
		$("input[name='date_to_day']").select2("val", data.date_to_day);
		$("input[name='date_to_uncertainty']").select2("val", data.date_to_uncertainty);
	  
  }
  
  function addTextualSources(data){
	  
	// POPULATE TEXTUAL SOURCES
		addSelect2Param($(".TextualSource"), "Select a textual source", urlTextualSource, "item.emloid", "item.label", "json");

	  var htmlTextualSource = "";
	  
	// iterate through each assertion in the dataset
		$.each(data.assertion, function(i,assertion){		
			var count = i + 2;	// start counter at 2
			
		// source description
			sourceDetailValue = assertion.source_description;
			source_id = assertion.source_id;
			 
			// define HTML for each textual source
			htmlTextualSource =  
 			'<ul class="small-block-grid-4 textSourceEntry">' +
 			'<li><label>Textual Source</label><input type="hidden" name="source_id_' + count +  '" value="' + source_id + '"/>  <textarea  readonly name="source_text_' + count +  '" class="input_source_id_' + count +  '  TextualSource small-12" >' + source_id + '</textarea></li>' +
 			'<li><label>Source Details</label><input type="text" name="source_details_' + count + '" class="small-block-grid-4" readonly value="' + sourceDetailValue + '"/>' +
 			'</li><li>' +
 			'<span class="remove button tiny">Remove</span>' +
 			'</li></ul>' ;
 		
			// append HTML to textual source section
			$('.addSource').parents("fieldset").first().append(htmlTextualSource); 
			
			// GET TEXT LABEL FOR THE TEXTUAL SOURCE AND POPULATE READONLY TEXTAREA 
			 getTextualSourceText(source_id, '.input_source_id_' + count );
		});
	  
  }
  

  function addNotes(data){
	// POPULATE NOTES 
		$("input[name='notes_used']").val(data.notes_used);
		$("textarea[name='additional_notes']").val(data.additional_notes);
	  
  }
  

  
 function addLocations(data){
	 
	 addSelect2Param($(".Location"), "Select a location", urlPlaceAll, "item.emloid", "item.label", "json");
	 
	// POPULATE LOCATION
		$.each(data.location, function(i,location){		
			var count = i + 2;	// start counter at 2
			
			var locationid = location.location_id;

			html = 
    			'<ul class="small-block-grid-4 AddedLocation"><li>' +
    			'<label>Location</label><input type="text" name="location_' + count + '" class="location_' + count + ' columns small-10" readonly value=""/>' +
    			'<input type="hidden" name="location_id_' + count + '" value="' + locationid + '"/></li><li>' +
    			'<br/><span class="remove button tiny ">Remove</span>' +
    			'</li></ul>' ;
			
			// append HTML to textual source section
			$('.addLocation').parents("fieldset").first().append(html); 	
			
			 getLocationText(locationid, '.location_' + count);
		}); 
  } 
  
 
 
 function addSecondaryParticipant(data){
	 

		var numberSP = data.role_in_activity.length  + 1;
		$("input[name='counterSP']").val(numberSP);
		var counter = 1;
		
		var primaryPerson = (data.primary_person[0].person_id) ? data.primary_person[0].person_id : ""; // id of primary person
		
		$.each(data.role_in_activity, function(i,role){
			counter++;
			if ((role.entity_id == primaryPerson) && (role.entity_type = "Person")){} else {
					

				// do not display if the combination of entity_type, entity_id and role exist in relationship
				var role_id = role.role_id;
				var entity_id = role.entity_id;
				var entity_type = role.entity_type;
				var relExists = 0;
				
				$.each(data.relationship, function(i,rel){
				
					if ((rel.object_id == entity_id) && (rel.object_type == entity_type) && (rel.object_role_id == role_id))
					// do not display
						relExists = 1;
				});
				
				
				if (relExists != 1){
				html =
					'<fieldset class="divSP">' +
				'<ul class="small-block-grid-4">' +

					'<li><label>Entity Type</label> ' + role.entity_type +
					'<input type="hidden" name="sp_entity_type_' + counter +'" value="' + role.entity_type + '"/></li>' +
				'	<li><label>Label</label><input type="hidden" name="sp_entity_id_' + counter +'" value="' + role.entity_id + '"/><span class="'+ role.entity_type + 'Label' + role.entity_id + '"/></li>' +
				'	<li><label>Role</label><input type="hidden" name="sp_role_' + counter +'" value="' + role.role_id + '"/>' + role.role_id + '</li>' +
				'	<li><br />' +
				'	<span class="removeEntitySP button tiny">Remove</span></li>' +
				'</ul>' +
			'</fieldset>';
					
				
				} else {
					html =
						'<fieldset class="divSP">' +
					'<ul class="small-block-grid-4">' +

						'<li><label>Entity Type</label> ' + role.entity_type +
						'</li>' +
					'	<li><label>Label</label><span class="'+ role.entity_type + 'Label' + role.entity_id + '"/></li>' +
					'	<li><label>Role</label>' + role.role_id + '</li>' +
					'	<li><br />' +
					'	<span class="removeEntitySP button tiny">Remove</span></li>' +
					'</ul>' +
				'</fieldset>';
						
					
					
				}
				$("#fieldsetSecondaryParticipant").last().append(html);	
			}
		});
		
		
		var selectedActivity = data.activity_type_id;
		updateRole(selectedActivity, ".RoleSP"); // display selected role in selectbox
 }
 
 
 
function addRoles(data){

	
	// set counterRole hidden field
	var numberRoles = data.role_in_activity.length  + 1;
	$("input[name='counterRole']").val(numberRoles);
	
	
	// if activity defined then show blank role
	var cnt = 1;
	
	if (data.activity_type != ""){
		// define html to append
		htmlRole = '<div class="divRole" id="divRole' + cnt +'" >' + divRoleHTML + '</div>' ;
		$("#fieldsetRole").last().append(htmlRole);	 // append html to role section
		var selectedValue = data.activity_type_id; // selected activity identifiier
		updateRole(selectedValue, ".Role"); // display only roles relevant to selected activity
	}
	
	// has a person been selected
	var primaryPerson = (data.primary_person[0].person_id) ? data.primary_person[0].person_id : ""; // id of primary person
	
	if (primaryPerson != ""){
		cnt = 2;
	// iterate through each role 
		$.each(data.role_in_activity, function(i,role){
			if ((role.entity_id == primaryPerson) && (role.entity_type = "Person")){
				htmlRole = 
					'<div class="divRole" id="divRole' + cnt +'" ><ul class="small-block-grid-4">' +
					'<li><label>Role</label><input type="hidden" name="subject_role_' + cnt +'" class="Role small-12" value="' + role.role_id + '" /></li>' +
					'<li><br /> <span class="addRole button tiny">Add</span> <span class="removeRole button tiny">Remove</span></li></ul><div class="divRel">' +
					'<p>Describe the relationships that correspond to the person having the selected role in this activity:</p>' ;
		
				$("#fieldsetRole").last().append(htmlRole);		
				//updateRole(role.role_id, ".Role"); // display selected role in selectbox
				
				addRelationships(data, cnt, role.role_id); // add relationships associated with this role
				$("#fieldsetRole").last().append('</div>'); // append closing div tage to role fieldset 
				cnt += 1;
			}
		});
	}
}


function addRelationships(data, countRole, roleid){
	
	// display default form for relationship type plus readonly lines for existing relationships	
	var htmlRel =
		'<ul class="small-block-grid-5" id="ulRel'+ countRole +'">' +
		'<li><label>Relationship Type</label><input type="hidden" name="relationship_' + countRole + '_1" class="Relationship small-12"/></li>' +
		'<li><label>Entity Type</label><select name="object_type_' + countRole + '_1" class="small-12 Object_Type" >' +
		'<option value="">Please Select...</option>' +
		'<option value="Document" >Document</option>' + 
		'<option value="Group" >Group</option>' + 
		'<option value="Location" >Location</option>' +
		'<option value="Organisation" >Organisation</option>' +
		'<option value="Person" >Person</option>' +
		'<option value="Entity">Other entity type not specified</option>' +
		'</select></li>' +
		'<li><div class="divEntity"/></li>' +
		'<li><div class="divRole2"/></li>' +
		'<li><br/><span id="add" class="addRelationship button tiny" >Add</span></li></ul>' ;
	
	$("#divRole" + countRole).append(htmlRel);
	
	$.getJSON("data/roleRelationship.json", function(json2) { 
		var items = [];
		$.each(json2, function(i, v) {
			 if (v.id == roleid) {      
				 	$("#ulRel" + countRole).find(".Relationship").select2({ data:  [v] });
			        return; } 
		});
  });
	
	countRel = 2;
	
	// iterate through each relationship
	$.each(data.relationship, function(i,rel){
		var htmlRel = "";
		if ((rel.subject_role_id == roleid))	{
			var relationship 	= rel.relationship_id;
			var object_type 	= rel.object_type;		
			var object_role 	= getObjectRole(data, rel.object_id, rel.object_type) ;
			var object_id 		= rel.object_id;
			var object_text 	= getObjectText(rel.object_id, rel.object_type, '#object_text' + countRole + '_' + countRel);
			
			// generate html to append to roles and relationship section
       	htmlRel += 
        	    	'<ul class="small-block-grid-5 relationshipCollection">' +
        	    	'<li><label>Relationship Type</label><input name="relationship_' + countRole + '_' + countRel + '" type="text" class="small-block-grid-5" readonly value="' + relationship + '"/></li>' +
        	    	'<li><label>Entity Type</label><input name="object_type_' + countRole + '_' + countRel + '" type="text" class="small-block-grid-5" readonly value="' + object_type + '"/></li>' +
        	    	'<li><label>Label</label><input name="object_' + countRole + '_' + countRel + '" type="hidden" value="' + object_id + '"/><input id="object_text' + countRole + '_' + countRel + '" name="object_text' + countRole + '_' + countRel + '" type="text" class="small-block-grid-5" readonly /></li>' +
        	    	'<li><label>Role</label><input name="object_role_' + countRole + '_' + countRel + '" type="text" class="small-block-grid-5" readonly value="' + object_role + '"/></li>' +
        	    	'<li><span class="remove button tiny">Remove</span></li></ul>' ;
        	  		
        	/*
        	htmlRel += 
    	    	'<ul class="small-block-grid-5 relationshipCollection">' +
    	    	'<li><label>Relationship Type</label><input name="relationship_' + countRole + '_' + countRel + '" type="text" class="small-block-grid-5" readonly value="' + relationship + '"/></li>' +
    	    	'<li><label>Entity Type</label>' + object_type + '</li>' +
    	    	'<li><label>Label</label><span class="' + object_type + 'Label' + object_id + '"/></li>' +
    	    	'<li><label>Role</label>' + object_role + '</li>' +
    	    	'<li><span class="remove button tiny">Remove</span></li></ul>' ;
    	    		
        	*/
        	
        	
			htmlRel += (countRole == 1) ? "<hr/>" : ""; // add horizontal rule after relationship if this is the first role
	
			$("#divRole" + countRole).append(htmlRel);
			countRel += 1; // increment the relationship counter
			
			} // end of check of whether the role id == primary person's role id 
	}); // end of loop through the relationships	

}

	// get text label using id and type
  function getObjectText(object_id, object_type, elementId){
	  
		switch (object_type){
		
			case "Document":
				//$(elementId).val(object_id);
				
				documentText(object_id, elementId);
				
			break;
			
			case "Organisation":
				organisationText(object_id, elementId);
			break;
			
			case "Person":
				personText(object_id, elementId);
			break;
			
			case "Entity":
				$(elementId).val(object_id);
			break;	
			
			default:		
				$(elementId).val(object_id);
			break;
		
		}
		
  }
  
  // returned the role of the object in a relationship - in the activity json file returned by query.php
function getObjectRole(data, object_id, object_type){
	 var returnValue = "";
	  $.each(data.role_in_activity, function(i,role){	  
		  if ((role.entity_type == object_type) && (role.entity_id == object_id)){
			  	  returnValue = role.role_id; } 
		  });
	  return returnValue;
  }  
  
function organisationText(object_id, elementId){
	var returnValue = "";
	$.getJSON(urlOrganisation + "?id=" + object_id,function(orgAll){
		
				$.each(orgAll, function(i,org){
						if (org.emloid == object_id) { returnValue = org.label; }
						});	
				
				$(elementId).val(returnValue);	
	});
}


function documentText(object_id, elementId){
	var returnValue = "";
	$.getJSON(urlDocumentAll + "?id=" + object_id,function(orgAll){
		
				$.each(orgAll, function(i,org){
						if (org.emloid == object_id) { returnValue = org.label; }
						});	
				
				$(elementId).val(returnValue);	
	});
}

function getLocationText(id, elementId){

	var url = urlPlaceAll + "?id=" + id; // retrieve json file for selected textual source

	var returnValue = "";
	
	 $.getJSON(url,function(locationAll){
				$.each(locationAll, function(i,location){
						if (location.emloid == id) { 	returnValue = location.label;}
						});
				
				$(elementId).val(returnValue);
				
				});	
	
}




// get text label for textual source
function getTextualSourceText(sourceid, elementid){
	
	var url = urlTextualSource + "?id=" + sourceid; // retrieve json file for selected textual source
	
	var returnValue = "";
	
	 $.getJSON(urlTextualSource,function(sourceAll){
				$.each(sourceAll, function(i,source){
						if (source.emloid == sourceid) { 	returnValue = source.label;}
						});
				$(elementid).val(returnValue);
				});		 
}







//POPULATE ROLES AND RELATIONSHIPS
/*
$.each(data.relationship, function(i,relation){
		
});

*/
//read activityRole.json file containing association between activity type and roles
	/*
	$.getJSON("data/roleRelationship.json", function(json2) {  
		

		var items = [];
		
		// iterate through json file 
		$.each(json2, function(i, v) {
			
			// if the identifier of an activity type in the json file matches the selected value
			// populate the role selection fields with the element and its child roles
			 if (v.id == selectedValue) {      
				 
				 	$(obj).parents(".divRole").find(".divRel").html(html);
				 	$(obj).parents(".divRole").find(".Relationship").select2({ data:  [ v ] });
			        return;
			    } 
			});

  });*/