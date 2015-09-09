


/* FORM ACTIONS */

/* REMOVE MESSAGES WHEN PERSON CLICKS ON INPUT FORM*/
$('#form1').focusin(function(){	$('#response').remove();});

/* RESET FORM BUTTON ACTIONS*/
$('body').on('click', '.resetForm', function(){	resetForm();});
$('body').on('click', '.resetFormEdit', function(){ 	window.location.reload();});



$('body').on('click', '.updateActivity', function() {   	 updateDbAddActivity();	});
$('body').on('click', '.editTextualSource', function() {      sourceSubmit("", "");	});
$('body').on('click', '.submitFormAddActivity', function() {   	updateDbAddActivity(); });


/* FORM ELEMENT ACTIONS */
$('body').on('click', '.remove', function() {	$(this).parents("ul").first().remove();  });
  













/* FUNCTIONS */


function addLabelsAll(data){
	addLabels(urlDocumentAll, ".DocumentLabel", "emloid", ["label"]);
}
	

function addLabels(url, classNamePrefix, id, label){

	 $.getJSON(url,function(itemAll){
			$.each(itemAll, function(i,item){
					var className = classNamePrefix + eval("item." + id);
										
					if ($(className)[0]){
						jQuery(className).each(function() {
							var strLabel = ""
								for (i = 0; i < label.length; i++) {strLabel += eval("item." + label[i] ) + " ";}
							$(this).text(strLabel);
							
							});
						}
				});	
	 	});
}


function validateFormActivity(){	 
	 /* check if required field contain a value
	  required fields = person (input name = subject, class name = PersonPrimary) and activity type
	 */
	 var countError = 0; // counter to track number of errors
	
	 // if value not set for person display error message
	 if ( $(".PersonPrimary").first().select2('data') == null){ 
		 $(".PersonPrimary").first().parents('.input-wrapper').addClass("error");
		 countError += 1;
		 
	 }
	// if value not set for activity type display error message
	 if ($(".Activity").first().select2('data') == null) { 
		 $(".Activity").first().parents('.input-wrapper').addClass("error");
		 countError += 1;
	 }
	 
	 
	 if (countError > 0 ){
		 // if any errors exist, do not submit the form 
		 return false;
		 
	 
	 } 
	 
	
	
}







function sourceSubmit(message, action){
	showLoading();
	
    $.ajax({
           url: 'lib/source_submit.php',
           type: 'POST',
           data: $('#form1').serialize(),
           
           success: function(result){
           		removeMessages();
           		removeLoading();
           		addMessages(message);
           		sourceSubmitAdditionalActions(action);

           		
           	//	console.log(result);
              },
              
           error: function(result){
          		removeMessages();
           		removeLoading();
           		addMessages(result); 
           		}
        });         
	
}



function sourceSubmitAdditionalActions(action){
	
	switch (action){
		
		case "delete":
			$("#summaryTable tr").remove();
			addSummaryTableSource();
			break;
	}
	
}


function updateDbAddActivity(){
	
	if (validateFormActivity() == false){
		return;
	};
	showLoading();
	
	$.ajax({
           url: 'lib/activity_submit.php',
           type: 'POST',
           data: $('#form1').serialize(),
           
           success: function(result){
  	
           		
           		removeMessages();
           		removeLoading();
           		
           		var message = updateDbAddActivityMessage();
           		addMessages(message);
           		
           		
           		
           		resetFormFields();
           		console.log(result);
              },
              
              error: function(result){
          
            	  removeMessages();
           	   	  removeLoading();
           	   	  addMessages(result);
           	   console.log(result);
           	      }   
        });       
}



function updateDbAddActivityMessage(){
	var activityLabel = ($(".Activity").select2('data')) ?  $(".Activity").select2('data').text : "";
    
	var message = "The <b>" + activityLabel + "</b> activity has been added."
	
	return message;
}




function showLoading(){
	$('#messages').append('<img src = "img/ajax-loader.gif" alt="Currently loading"  id = "loading"/>');
	   
}

function removeLoading(){
	  $('#loading').remove(); // remove loading image
}


function removeMessages(){
	$('#response').remove(); // remove messages
}


function addMessages(message){
	$('#messages').append('<p id = "response">' + message + '</p>'); // add error message
    
	
}

function resetFormFields(){
	
    /* reset most of the form fields keeping values for primary person and textual sources  */
    
    $('#form1').trigger("reset"); // reset form fields
    $(".Activity").select2("val", ""); // reset form fields using select2
    
    /* reset HTML for form sections */
    $("#fieldsetLocation").html(htmlLocation); 
    $("#fieldsetRole").remove();
    $("#fieldsetActivity").after(htmlRoles);
    $("#fieldsetTime").html(htmlTime);
    
    
  // init location field 
   addSelect2Param($(".Location"), "Select a location", urlPlaceAll, "item.emloid", "item.label", "json");
    
    // init date fields
   $(".Month").select2({data:arrayMonth});         
   $(".Day").select2({data:arrayDay});
   $(".Uncertainty").select2({data:arrayUncertain});
	
}



function resetForm(){
	var location = document.location.protocol + '//' + document.location.hostname + document.location.pathname;
	window.location.replace(location); 
}


function formatCamelCase(str){
	return str
	.replace(/([A-Z])/g, ' $1')
	.replace(/^./, function(str){ return str.toUpperCase()});
}

//source http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
function getParameterByName(name) {
	var name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
	 results = regex.exec(location.search);
	return (results == null) ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}





/* NAVIGATION BUTTONS 
$('body').on('click', '.activityForm', function() {		window.location.replace('activity_add.php') 	});	
$('body').on('click', '.sourceForm', function() { 	window.location.replace('source_view.php') 	});	
$('body').on('click', '.addTextualSource', function() { 	window.location.replace('source_add.php') });	
$('body').on('click', '.activityForm', function() { 	window.location.replace('activity_add.php')});	
$('body').on('click', '.Browse', function() {   window.location.replace('activity_view.php');});

*
*function addLabelsAll(data){
//	addLabels(urlPersonAll, ".Personlabel", "emloid", ["name", "date"]);
//	addLabels(urlOrganisation, ".OrganisationLabel", "emloid", ["label"]);
	addLabels(urlDocumentAll, ".DocumentLabel", "emloid", ["label"]);
//	addLabels(urlTextualSource, ".TextualSourceLabel", "emloid", ["label"]);	
//	addLabels(urlPlaceAll, ".LocationLabel", "emloid", ["label"]);
}
*/

