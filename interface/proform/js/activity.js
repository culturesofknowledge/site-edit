$('body').on('change', '.sp_entity_type', function() { 
        
	   	var selectedValue = ($(this).val());    // selected activity			
		var obj = $(this).parents(".divSP").find(".divEntity");		
		var name = $(this).attr('name');
		var countSP = /[^_]*$/.exec(name)[0]; // determine index to use for input fields
	
		var htmlInput = '<input type="text" name="sp_entity_id_' + countSP + '" class="Secondary_Participant_Type small-12" />';
				
		switch (selectedValue){
	    	case "Person":	
	    		$(obj).html('<label>Person</label><input type="hidden" name="sp_entity_id_' + countSP + '"  class="Object Person small-12" />');	
	         	addSelect2Param($(this).parents(".divSP").find(".Object"), "Select a person", urlPersonAll, "item.emloid", "item.name", "json");
	         	break;
	         	
	         case "Organisation":		
	         	$(obj).html('<label>Organisation</label><input type="hidden" name="sp_entity_id_' + countSP + '"  class="Object Group small-12" />');	
	         	addSelect2Param($(this).parents(".divSP").find(".Object"), "Select an organisation", urlOrganisation, "item.emloid", "item.label", "json");
	         	break;
	         	
	         	
	         case "Group":		
		         	$(obj).html('<label>Group</label><input type="hidden" name="sp_entity_id_' + countSP + '"  class="Object Organisation small-12" />');	
		         	addSelect2Param($(this).parents(".divSP").find(".Object"), "Select a group", urlOrganisation, "item.emloid", "item.label", "json");
		         	break;	
	         	
	         case "Entity":
	         		$(obj).html('<label>Entity</label>' + htmlInput);	
	         	break;
	         		
	         case "Document":
	         	//	$(obj).html('<label>Document</label>' + htmlInput);	
	        	 
	        	 $(obj).html('<label>Document</label><input type="hidden" name="sp_entity_id_' + countSP + '"  class="Object Document small-12" />');	
		         	addSelect2Param($(this).parents(".divSP").find(".Object"), "Select a document", urlDocumentAll, "item.emloid", "item.label", "json");
		         	break;
	        	 
	        	 
	         	break;
	         	
	         	
	         	

	         	
	         case "Location":
	        	 $(obj).html('<label>Location</label><input type="hidden" name="sp_entity_id_' + countSP + '"  class="Object Location small-12" />');	
		         	addSelect2Param($(this).parents(".divSP").find(".Object"), "Select a location", urlPlaceAll, "item.emloid", "item.label", "json");
		         
	         	break;	
	         	
	         default:
	         		$(obj).html('<label>Entity</label>' + htmlInput);
	         	break;
	         	}
	         });



/* ADD SECONDARY PARTICIPANT */  
$('body').on('click', '.addEntitySP', function() {  
	var countSP = $("input[name='counterSP']").val();
	var counter = countSP + 1;
	$("input[name='counterSP']").val(counter);

	var htmlSecondaryParticipant =      		        	
	'<fieldset class="divSP">' +
		'<ul class="small-block-grid-4">' +			
			'<li><label>Entity Type</label> <select name="sp_entity_type_' + counter +'" class="sp_entity_type">' +
					'<option value="">Please Select...</option>' +
					'<option value="Document">Document</option>' +
					'<option value="Group">Group</option>' +
					'<option value="Location">Location</option>' +
					'<option value="Organisation">Organisation</option>' +
					'<option value="Person">Person</option>' +
					'<option value="Entity">Other entity type not specified</option>' +
					
		'	</select>' +
		'	</li>' +
		'	<li><div class="divEntity"/></li>' +
		'<li class="liRoleSP"><label>Role</label>' + 
 		'<input type="hidden" name="sp_role_' + counter +'" class="RoleSP small-12"/></li>' +	
		'	<li><br/><span  class="addEntitySP button tiny" >Add</span>&nbsp;<span class="removeEntitySP button tiny" >Remove</span></li>' +
		'</ul>' +
		'</fieldset>' ;

	$(this).parents("#fieldsetSecondaryParticipant").first().append(htmlSecondaryParticipant);	
	$(".removeRoleSP").show();  // hide the remove button for the role that has been added  - this will be shown if another role is added

	var obj = $(this).parents("#fieldsetSecondaryParticipant").find(".divSP").last();
	var selectedActivity = ($(".Activity").select2('data')) ? $(".Activity").select2('data').id : "";

	if (selectedActivity != ""){

		$.getJSON("data/activityRole.json", function(json) {  
 			$(obj).find('.RoleSP').select2({ data: json }); // by default load all the file into the role selection fields
 			var items = [];
 		// iterate through json file 
 			$.each(json, function(i, v) {
 			
 			// if the identifier of an activity type in the json file matches the selected value
 			// populate the role selection fields with the element and its child roles
 			 	if (v.id == selectedActivity) {      
 				 	var options = v.children;	
 				 	options.unshift({"id":"","text":""}); // prepend blank option
 				 	$(obj).find('.RoleSP').select2({ data:  options });
 			        return;
 			    } 
 			});
      });
	}

	showHideRemoveEntitySP();
	
});

/* REMOVE SECONDARY PARTICIPANT */
$('body').on('click', '.removeEntitySP', function() {  
	$(this).parents(".divSP").remove();	
	showHideRemoveEntitySP();
	
});



function showHideRemoveEntitySP(){

if ($(".divSP").length == 1){
	$('.removeEntitySP').hide();
} else {
	$('.removeEntitySP').show();
}
}
