





function inputFormTextualSourceEdit(){	
	// display simple input form by iterating over data fields;
	var id = getParameterByName("id");
	
	if (id == ""){ 
		$("#messages").html("<p class='alert-box alert'>An identifier is required. Please click on the 'Browse All Textual Sources' button to continue.</p>")
		return false;
		}
	
	htmlInputFormSource("edit");		
}




function htmlInputFormSource(action){
	

	 var inputFields = "";
	              
	 switch(action){
	 
	 	case "edit":
	 		
	 		var id = getParameterByName("id");
	 		
	 		$("input[name='source_id']").val(id);
	 		
	 		
	 		var url = urlTextualSourceSearch + "?id=" + id;

	 		$.getJSON(url,function(data)
	 				{
	 			$.each(data, function(i,data) 
	 					{
	 						if (data.id == id)
	 							{
	 								$.each(fieldTextualSource, function(i, fieldName) 
	 										{ 
	 											var value = eval("data." + fieldName);
	 											switch(fieldName){
	 												case "id":
	 												case "creation_timestamp":
	 												case "creation_user":
	 													inputFields += "<input type='hidden' name='"+ fieldName + "' value='" + value + "'/>";
	 													break;
	 			 		
	 												default:	
	 													inputFields += "<li><label>" + formatCamelCase(fieldName) + "</label><input type='text'  name='" + fieldName + "' value='" + value + "'/></li>";
	 												}
	 										});
	 							}

	 				var html = '<ul>' + inputFields + '</ul>';	
	 				$("#inputForm").html(html); 
	 					});
	 				});
	 		
	 		break;
	 		
	 	case "add":
	 		
	 		 $.each(fieldTextualSource, function(i, fieldName) { 	
	 			 switch(fieldName){
	 			 	case "id":
	 			 	case "creation_timestamp":
	 			 	case "creation_user":
	 			 		
	 			 		inputFields += "<input type='hidden' name='"+ fieldName + "' value=''/>";
	 			 		break;
	 			 		
	 			 	default:	
	 			 		inputFields += "<li><label>" + formatCamelCase(fieldName) + "</label><input type='text' name='" + fieldName + "' value=''/></li>";
	 			 }
	 		 });
	 		
	 		var html = '<ul>' + inputFields + '</ul>';	
	 		
	 	
	 		
	 		$("#inputForm").html(html); 
	 		break;
	 
	 } 
}



$('body').on('click', '.submitFormAddTextualSource', function() {   
	
	sourceSubmit("The textual source has been added.", "add");
	
	});



<!-- VIEW SELECTED RECORD -->
$('body').on('click', '.viewRecord', function(event){
	var id = event.target.id.substring(10); // extract record identifier from element id
	$("input[name='source_id']").val(id);
	showRecordSource(id); // show record
});


$('body').on('click', '.resetForm', function(){
	 resetForm();
	 
} );


//go to textual source input form
//$('body').on('click', '.sourceForm', function() { window.location.replace('source_view.html') });	


/* display a selected record, with buttons to edit or delete the record  */
function showRecordSource(id){
	// get selected activity record
	var url = urlTextualSourceSearch + "?id=" + id;
	
	$.getJSON(url,function(data){
		$.each(data, function(i,data) {
			if (data.id == id) {
				
				// display record in summary
	            // found it...
					var html = "<div>";
					html += htmlEditButtons(id);
					html += "<table>";
					$.each(data, function(key, value) { 
					    html += (key != "id") ?"<tr><th>" + formatCamelCase(key) + "</th><td>" + value + "</td></tr>" : "";
					});
	
					html += '</table></div>';
					
					$("#myModalContent").html(html);   
					
					return false; // stops the loop
	        }	
		});   
	});
}


function htmlEditButtons(id){	
	var html = 
		' <div id="panel' + id + '" class="content">' +
		'<span><label>Actions</label>' +
		'<span class="edit button tiny editRecord" id="editRecord' + id + '" title="Edit this record"> Edit Textual Source </span> ' +
		'<span title="Delete this record" class=" button tiny deleteTextualSource" id="deleteRecord' + id +'"> Delete Textual Source </span></span>';	
	return html;
}


function addSummaryTableSource(){
	/* add table containing summary of all activities */	
	var url =  urlTextualSourceSearch;	
	var counter = 1;
	$.getJSON(url,function(data){
			$.each(data, function(i,data){
				var html = "";
				if (counter == 1) {
					html += "<tr><th>View</th>";
					$.each(data, function(key, value) { 
						html += (key != 'id') ? "<th>" + formatCamelCase(key) + "</th>": "";
					});
					html += "</tr>";
				}
				
				
				html += "<tr>";
				html += '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<img  src="img/view.png" data-reveal-id="myModal" class="viewRecord" id="viewRecord' + data.id + '" title="Click to view record in modal window"/>';
				
				var counterCol = 1
				
				var classRow = ((counter % 2) == 1) ? "odd" : "even";
				
				$.each(data, function(key, value) { 
					
					html += (key != 'id') ? "<td class='" + classRow  + "'>" + value + "</td>" : "";
				
				counterCol++;
				});
				html += "</tr>";
														
				$(html).appendTo("#summaryTable");
				
				counter ++;	
			});
		});
	}
	
	
//edit record
$('body').on('click', '.editRecord', function(event){
	var id = event.target.id.substring(10); // extract record identifier from element id
	window.location.replace('source_edit.php?id=' + id);
});


$('body').on('click', '.deleteTextualSource', function(event){
	var id = event.target.id.substring(12); // extract record identifier from element id
	$("input[name='source_id']").val(id);
	
	if (confirm('Please confirm that you would like to confirm this textual source.')){

		$('#myModal').foundation('reveal', 'close');
		
		sourceSubmit("The textual source has been deleted.", "delete");
		
	} 
		
});

