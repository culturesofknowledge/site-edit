<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("lib/user.php");
include("lib/html.php");
?>
<html>
<head>

<meta charset="utf-8"></meta>
<meta name="viewport" content="width=device-width, initial-scale=1.0"></meta>
<title>Prosopography : Add Activity</title>

<!-- third party scripts -->
<script src="../proforminc/jquery-1.11.1.min.js"></script>
<script src="../proforminc/jquery-ui-1.10.4/js/jquery-ui-1.10.4.js"></script>
<script src="../proforminc/select2-3.5.1/select2.js"></script>
<script src="../proforminc/f5/js/vendor/modernizr.js"></script>


<!-- third party css -->
<link rel="stylesheet" href="../proforminc/select2-3.5.1/select2.css" />
<link rel="stylesheet" href="../proforminc/f5/css/foundation.css" />
<link rel="stylesheet" href="../proforminc/f5/css/foundation-icons.css" />

<!-- proform css -->
<link rel="stylesheet" type="text/css" href="css/style.css" />

</head>
<body>

<div class="banner" id="pagebanner">
<?=bannerHTML()?>
<br/>
<h2>Add Prosopography Event</h2><br/>
</div>




	<form method="post" id="form1" name="form1" data-abide="ajax">
		<input type="hidden" name="counterRole" value="1" /> <input
			type="hidden" name="counterSP" value="1" /> <input type="hidden"
			name="activity_action" value="add" />
		
			
				<span class="resetForm button tiny">Reset
						</span> <span class="submitFormAddActivity button tiny">Save
					</span>  <span id="messages" />
			
			<fieldset id="fieldsetActivity">
				<legend>Activity</legend>
				<ul class="small-block-grid-4">
					<li>
						<div class="input-wrapper">
							<label>Activity Type <small>required</small></label> <input
								type="hidden" name="activity_type" class="Activity small-12" />
							<small class="error">Activity is a required field.</small>
						</div>
					</li>
					<li><label>Activity Name</label> <input class="small-12"
						name="activity_name"></input></li>
					<li><label>Activity Description</label> <textarea
							name="activity_description" class="small-12"></textarea></li>
					<li />
				</ul>
			</fieldset>
			<fieldset id="fieldsetPrimaryPerson">

				<legend>Primary Participant - Person</legend>
				<ul class="small-block-grid-2">
					<li>
						<div class="input-wrapper">
							<label>Person <small>required</small></label><input type="hidden"
								name="subject" class="Person PersonPrimary small-12" /> <small
								class="error">Person is a required field.</small>
						</div>
					</li>
				</ul>
				<fieldset  class="fieldsetRole">
				
				<legend>Roles and Relationships</legend><div class="divRole">
	<ul class="small-block-grid-4">       
      <li>
      	<label>Role</label>
      		<input type="hidden" name="subject_role_1" class="Role small-12"/>
	</li><li><br/>
			<span id="add" class="addRole button tiny" >Add</span>       
			<span id="add" class="removeRole button tiny" >Remove</span>
			</li></ul><div class="divRel"/>	
				
				</div>
				</fieldset>
			</fieldset>

			<!-- SECONDARY PARTICIPANT START -->

			<fieldset id="fieldsetSecondaryParticipant">
				<legend>Secondary Participant</legend>

				<fieldset class="divSP">
					<ul class="small-block-grid-4">

						<li><label>Entity Type</label> <select name="sp_entity_type_1"
							class="sp_entity_type">
								<option value="">Please Select...</option>
								<option value="Document">Document</option>
								<option value="Group">Group</option>
								<option value="Location">Location</option>
								<option value="Organisation">Organisation</option>
								<option value="Person">Person</option>
								<option value="Entity">Other entity type not specified</option>
								
						</select></li>
						<li><div class="divEntity" /></li>
						<li class="liRoleSP"><label>Role</label> <input type="hidden" name="sp_role_1"
								class="RoleSP small-12" /></li>
						<li><br />
						<span id="add" class="addEntitySP button tiny">Add</span>&nbsp;<span
							class="removeEntitySP button tiny">Remove</span></li>
					</ul>
					
					
				</fieldset>

			</fieldset>
		</fieldset>

		<!-- SECONDARY PARTICIPANT END -->


		<fieldset id="fieldsetTime">
			<legend>Time</legend>
			<label>Date type</label> <select name="date_type"
				class="SelectFilter small-2">
				<option value="">Please select...</option>
				<option value="Before">Before</option>
				<option value="After">After</option>
				<option value="Duration">Duration</option>
				<option value="Between">Between</option>
			</select> <br /> <br />
			<ul class="small-block-grid-4">

				<li><label for="date_from_year">Year From</label> <input
					name="date_from_year" type="text" class="small-6"
					placeholder="Enter year" pattern="^[0-9]{4}$" /> <small
					class="error">Year entered is not valid</small></li>
				<li><label for="date_from_month">Month From</label> <input
					name="date_from_month" type="hidden" class="small-6 Month" /></li>
				<li><label for="date_from_day">Day From</label> <input type="hidden"
					name="date_from_day" class="small-6 Day" /></li>
				<li><label for="date_from_uncertainty">Uncertainty</label> <input
					type="hidden" name="date_from_uncertainty"
					class="Uncertainty small-6" /></li>

				<li><label for="date_to_year">Year To</label> <input
					name="date_to_year" type="text" placeholder="Enter year"
					class="small-6" pattern="^[0-9]{4}$" /> <small class="error">Year
						entered is not valid</small></li>
				<li><label for="date_to_month">Month To</label> <input
					name="date_to_month" type="hidden" class="small-6 Month" /></li>
				<li><label for="date_to_day">Day To</label> <input type="hidden"
					name="date_to_day" class="small-6 Day" /></li>
				<li><label for="date_to_uncertainty">Uncertainty</label> <input
					type="hidden" name="date_to_uncertainty"
					class="Uncertainty small-6" /></li>
			</ul>
		</fieldset>

		<fieldset id='fieldsetLocation'>
			<legend>Location</legend>
<p>If the location does not appear in the selection, you can add a
					location to the selection using the EMLO Edit <a target="_new"
					href="/interface/union.php?menu_item_id=39">
						"Add New Place" </a> form.</p>
			<ul class="small-block-grid-3">
				<li><label for="location">Location </label> <input type="hidden"
					name="location_id_1" class="Location small-12" value="" /></li>
				<li><br /> <span class="addLocation button tiny">Add</span>&nbsp;</li>

			
			</ul>

		</fieldset>

		<fieldset id="fieldsetTextualSource">
			<legend>Textual Source</legend>
			<p>If the textual source does not appear in the selection, you can add a
					textual source to the selection using the <a target="_new"
					href="/interface/proform/source_add.php">
						"Add Textual Source" </a> form.</p>
			<ul class="small-block-grid-3">
				<li><label for="source_id_1">Textual Source</label> <input
					type="hidden" name="source_id_1" class="TextualSource small-12"
					value="" /><br/><br/><span class="TextualSourceText small-12"/></li>
				<li><label for="source_details_1">Source Details</label> <textarea
						id="sourceDetails" name="source_details_1"
						class="TextualSourceDetail small-12"></textarea></li>

				<li><br/> <span id="add" class="addSource button tiny">Add</span></li>
				
			</ul>
		</fieldset>
		<fieldset id="fieldsetDataSource">
			<legend>Data Source</legend>

			<ul class="small-block-grid-2">
				<li><label for="notes_used">Contributing Authors</label> <input
					name="notes_used" id="sourceDetails" class=" small-12" /></li>
			</ul>
		</fieldset>
		<fieldset id="fieldsetAdditionalNotes">
			<legend>Additional Notes</legend>
			<ul class="small-block-grid-2">

				<li><label for="additional_notes">Additional Notes</label> <textarea
						name="additional_notes" id="sourceDetails" class=" small-12"></textarea></li>
			</ul>
		</fieldset>
		</fieldset>
		
		
		<span><label></label> <span class="resetForm button tiny">Reset
				</span> <span class="submitFormAddActivity button tiny">Save
						</span>
		
		
	</form>

	<!-- proform scripts -->
	<script src="js/config.js"></script>
	<script src="js/common.js"></script>
	<script src="js/form.js"></script>
	<script src="js/family.js"></script>
	<script src="js/input.js"></script>
	<script src="js/activity.js"></script>
	
	<!-- third party scripts -->
	<script src="../proforminc/f5/js/foundation/foundation.js"></script>
	<script src="../proforminc/f5/js/foundation/foundation.abide.js"></script>
	<script> $(document).foundation();

     $(document).ready(function() { 
    	 $(".SelectFilter").select2(); 
         
         addSelect2Param($(".Location"), "Select a location", urlPlaceAll, "item.emloid", "item.label", "json");
         addSelect2Person($(".PersonPrimary"), "Select a person", urlPersonAll, "json");
         addSelect2Param($(".TextualSource"), "Select a textual source", urlTextualSource, "item.emloid", "item.label", "json");
         
         $(".Month").select2({data:arrayMonth});         
         $(".Day").select2({data:arrayDay});
         $(".Uncertainty").select2({data:arrayUncertain});

        // populate Activity field with list of activities from activityGroup.json file 
         $.getJSON("data/activityGroup.json", function(json) {            	 
        	 $(".Activity").select2({ data:json })
         });

       
         showHideRemoveEntitySP();  


		$(".fieldsetRole").hide();
		$(".liRoleSP").hide();
     
     });   
     
    


   
    


  
      
</script>





</body>
</html>
