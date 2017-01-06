

/*
 * 
 * function addRelationship
 * 
 */


function addRelationship(obj){ 
	     
	 $(".SelectFilter").select2("destroy");
	 $(".Place").select2("destroy");
	 $(".Person").select2("destroy");
	 
	 
	 
	   $(obj).parent().parent().parent()
	   		.append(
	    // clone the row and insert it in the DOM
	    		$(obj).parent().parent().first().clone() 
	              
	        );
	    
	  $(".SelectFilter").select2();
	  $(".Person").select2();
	  $(".Place").select2();
	  
	  
	  
	  
	  
	   return false;
	}


/*
 * function removeRelationship
 * 
 * 
 */

function removeRelationship(obj){
	   
	    // do not delete relationship if there is only one relationship
	    if ($(obj).parent().parent().parent().children().length > 1){
	    	$(obj).parent().parent().remove();  // remove relationship assertions 
	    }
}





/*
 * 
 * function addEvent
 * 
 */

function addEvent(obj){ 
	 
	$("select.SelectFilter").select2("destroy");
	$("select.Person").select2("destroy");

	$(obj).parent().parent()
			.append(
	// clone the row and insert it in the DOM
			$(obj).parent().last().clone() 
	          
	    );


	$("select.SelectFilter").select2();
	$("select.Person").select2();
	return false;
	}



/*
 * function removeEvent
 * 
 * 
 */

function removeEvent(obj){
	   
	// do not delete relationship if there is only one relationship
    if ($(obj).parent().parent().children().length > 1){
    		$(obj).parent().remove();  // remove relationship assertions 
    }
}



function showContext(context){
	
	
// hide all fieldset elements
	$("fieldset").css("display","none");
	
// display selected fieldset element using id	
	$(context).css("display","inline");
	
}








function addSection(obj, par){ 
	
	
   var currentCount =  $(par).length; // number of sections
   var val1 = "_" + currentCount; // value to replace in input id
   var val2 = "_" + (Number(currentCount) + 1) ; // replacement value in input id
    
  //  var lastRepeatingGroup = $(sectionclass).last(); // get last section in this group
 //   var newSection = lastRepeatingGroup.clone(); // copy last section
    
    /*
    $('.repeatSectionBirth')
    	.children(".selectFilter")
    	 .select2("destroy")
         .end();
    */
    $(par).append(
                // clone the row and insert it in the DOM
                $(par).last().clone() 
              
        );
    
   
         
    
 //   newSection.insertAfter(lastRepeatingGroup); // insert copy after last section
    
    
    /*
    
    var appendOrReplace = (currentCount == 1) ? "append" : "replace"; // decide whether to append value to input id or replace id
    
    // iterate through each input field
    newSection.find("input").each(function (index, input) {
        updateInput(input, appendOrReplace, val1, val2); // update id of input and set value to empty string
    });
    
 // iterate through each select field
    newSection.find("select").each(function (index, input) {
        updateInput(input, appendOrReplace, val1, val2); // update id of input and set value to empty string
    });
    
    // iterate through each textarea field
    newSection.find("textarea").each(function (index, input) { 
        updateInput(input, appendOrReplace, val1, val2); // update id of input and set value to empty string
    });
    
    // update each label
    newSection.find("label").each(function (index, label) {
        var l = $(label);
        
         if (appendOrReplace == "append"){ 
            l.attr('for', l.attr('for').concat("_2"));
       } else {
            l.attr('for', l.attr('for').replace(val1, val2));
       }   
    });
    */
    
    
    return false;    
}


function updateInput(input, appendOrReplace, val1, val2){
    // update id of input 

       if (appendOrReplace == "append"){
            input.id = input.id.concat("_2");  
             input.name = input.name.concat("_2");
       } else {
            input.id = input.id.replace(val1, val2);
            input.name = input.name.replace(val1, val2);
       }
       

 // add autocomplete function to relationship field 
 
 
       if ((sectionclass = 'repeatSectionFamilyRelationships') && (input.id.search('FamilyRelationships-FamilyRole') != -1 )){     
            $(input).autocomplete({ source: relationships});     
        }
    
       
       
       
       
     $(input).val(""); // set value to empty string
     
     
         
   
   
}



function deleteSection(obj, sectionclass){
    
    var currentCount =  $(sectionclass).length;
    // do not delete section if there is only one section
    if (currentCount == 1){
   
        return false;
    }
    
    // remove fieldset element that contains this section
     $(obj).parent('p').parent('fieldset').remove();
    return false;
}


function showHideRemoveRole(){

	if ($(".divRole").length == 1){
 		$('.removeRole').hide();
 	} else {
 		$('.removeRole').show();
 	}
}


/*
ACTION TO TAKE WHEN ACTIVITY IS SELECTED
*/
      
function changeActivity(obj){    	 
	var selectedValue = $(obj).val();    

   	updateRole(selectedValue, ".Role"); // insert role list dependent on activity selected
   	updateRole(selectedValue, ".RoleSP"); // insert role list dependent on activity selected
   			 
   	$(".fieldsetRole").show(); // show primary participant roles
   	$(".liRoleSP").show(); // show secondary participant roles	
   	$(".divRel").empty(); // remove relationship input fields
   	
  	showHideRemoveRole(); 
 
}
      
      /* UPDATE THE ROLE LIST BASED ON SELECTED ACTIVITY TYPE  */
 function updateRole(selectedValue, selector){
     	 
     	// read activityRole.json file containing association between activity type and roles
      	$.getJSON("data/activityRole.json", function(json) {  
      		
      		// by default load all the file into the role selection fields
      		$(selector).select2({ data: json });
      		
      		var items = [];
      		
      		// iterate through json file 
      		$.each(json, function(i, v) {
      			
      			// if the identifier of an activity type in the json file matches the selected value
      			// populate the role selection fields with the element and its child roles
      			 if (v.id == selectedValue) {      
      				 
      				 	var options = v.children;	
      				 	options.unshift({"id":"","text":""}); // prepend blank option
      				 	$(selector).select2({ data:  options });
      			        return;
      			    } 
      			});
           });
      }
      
      
  	// select2 where query parameter is appended to the url e.g. /person/query
    function addSelect2(obj, placeholder, purl, pid, ptext, pdatatype, initLabel ){
    	
    	  $(obj).select2(
      			 {
               	    placeholder: placeholder,
               	    minimumInputLength: 0,
               	    ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
               	        url: function (term) {
               	            return  purl.concat(term)     
               	        } ,
               	        dataType: pdatatype,
               	        data : function (term) {
               	            return {
               	                term: term
               	            }; },
               	       results: function (data) {
               	    	   
               	       var results = [];
               	          $.each(data, function(index, item){
               	            results.push({
               	              id: eval(pid),
               	              text: eval(ptext) 
               	            });
               	          });
               	          return {
               	              results: results
               	          };  
               	       }},
               	       
               	       
               	       
               	    	   	initSelection : function (element, callback) {
               	    	   		var data = {id: element.val(), text: initLabel};
               	    	   		callback(data);
               	    	   			
               	       		}
      			 });
    }
    
    
     	 
    /*  select2 with restricted search using a query parameter q */
    function addSelect2Param(obj, placeholder, purl, pid, ptext, pdatatype ){
       	
    	
    	
    	
     	  $(obj).select2(
       			 {
                	    placeholder: placeholder,
                	    minimumInputLength: 3,
                	    ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                	        url: purl ,
                	        dataType: pdatatype,
                	        data : function (term) {
                	            return {
                	                q:term
                	            }; },
                	        
                	            
 
                	       results: function (data) {
                	    	   
                	       var results = [];
                	          $.each(data, function(index, item){
                	            results.push({
                	              id: eval(pid),
                	              text: eval(ptext) 
                	            });                 	            
                	            
                	          });
                	          
                	        
                	          
                	          return {
                	              results: results
                	             
                	          };  
                	       }}}
     	  );
     	
     	
     }
    
    
    /* select2  
    function addSelect2Person(obj, placeholder, purl, pdatatype ){
       	
     	  $(obj).select2(
       			 {
                	    placeholder: placeholder,
                	    minimumInputLength: 1,
                	    ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                	        url: function (term) {
                	            return  purl.concat(term)     
                	        } ,
                	        dataType: pdatatype,
                	        data : function (term) {
                	            return {
                	                term: term
                	            }; },
                	       results: function (data) {
                	    	   
                	       var results = [];
                	          $.each(data, function(index, item){
                	            results.push({
                	              id: eval("item.emloid"),
                	              text: eval("item.name") + " " +  eval("item.date") 
                	            });
                	          });
                	          return {
                	              results: results
                	          };  
                	       }}
       			 
       			 
       			 });
     	
     	
     }
     */
    
    
    function addSelect2Person(obj, placeholder, purl, pdatatype ){
       	
   	  $(obj).select2(
     			 {
              	    placeholder: placeholder,
              	    minimumInputLength: 3,
              	    ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
              	    	 url: purl ,
             	        dataType: pdatatype,
             	        data : function (term) {
             	            return {
             	                q:term
             	            }; },
             	        
              	       results: function (data) {
              	    	   
              	    	 
              	    	   
              	       var results = [];
              	          $.each(data, function(index, item){
              	            results.push({
              	              id: eval("item.emloid"),
              	              text: eval("item.name") + " " +  eval("item.date") 
              	            });
              	          });
              	          
              	       
              	          
              	          return {
              	              results: results
              	          };  
              	       }}
     			 
     			 });
   }
    
    
    
    
    function personText(person_id, elementId){
 		var returnValue = "";
 		$.getJSON(urlPersonAll + "?id=" + person_id,function(urlPersonAll){
 			
 					$.each(urlPersonAll, function(i,person){
 							if (person.emloid == person_id) { returnValue = person.name; }
 							});	
 					
 					$(elementId).val(returnValue);	
 		});
 	}









