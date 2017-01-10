<?php

include("lib/user.php");
include("lib/html.php");
?>
<html>
<head>
<meta charset="utf-8"></meta>
<meta name="viewport" content="width=device-width, initial-scale=1.0"></meta>
<title>Prosopography Input Form</title>

<!-- third party scripts -->
<script src="../proforminc/jquery-1.11.1.min.js"></script>
<script src="../proforminc/jquery-ui-1.10.4/js/jquery-ui-1.10.4.js"></script>
<script src="../proforminc/select2-3.5.1/select2.js"></script>
<script src="../proforminc/f5/js/vendor/modernizr.js"></script>


<!-- third party css -->
<link rel="stylesheet" href="../proforminc/select2-3.5.1/select2.css"></link>
<link rel="stylesheet" href="../proforminc/f5/css/foundation.css"></link>
<link rel="stylesheet" href="../proforminc/f5/css/foundation-icons.css"></link>

<!-- proform css -->
<link rel="stylesheet" href="css/style.css"/>

</head>
<body>
<div class="banner" id="pagebanner">
<?=bannerHTML()?>
<br/>
<h2>Edit Prosopography Event</h2><br/>
</div>



<div class="divForm"></div>

	<form method="POST" id="form1" name="form1" data-abide="">
		<input type="hidden" name="activity_id" value="" />
		<input type="hidden" name="counterRole" value=""/>
		<input type="hidden" name="counterSP" value=""/>
		<input type="hidden" name="activity_action" value="edit"/>
		<input type="hidden" name="creation_timestamp" value=""/>
		<input type="hidden" name="creation_user" value=""/>
		
		
		<fieldset><!--
			<fieldset class="fieldsetActions">
				<span><label>Navigation</label> <span
					class="Browse button tiny">Browse All Activities </span> <span
					class="activityForm button tiny"> Add Activity </span>
					<span class=" button tiny disabled"> Edit Activity
				</span>
					 <span
					class="sourceForm button tiny"> Browse All Textual Sources </span>

					<span class="addTextualSource button tiny"> Add Textual
						Source </span>  </span> <span><label>Actions</label> <span
					class="resetFormEdit button tiny"> Reset Form </span> <span
					class="updateActivity button tiny">Submit Form </span> </span> <span
					id="messages" />

			</fieldset>
			  -->
			<fieldset id="fieldsetActivity" class="">
				<legend>Activity</legend>
				<ul class="small-block-grid-4">
					<li>
						<div class="input-wrapper">
							<label>Activity Type <small>required</small></label> <input
								type="hidden" name="activity_type" class="Activity small-12" /><small
								class="error">Activity is a required field.</small>
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
			
			
			</fieldset>
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
			
			<fieldset id="fieldsetTime">
				<legend>Time</legend>
				<label>Date type</label> <select name="date_type"
					class="SelectFilter DateType small-2">
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
					<li><label for="date_from_day">Day From</label> <input
						type="hidden" name="date_from_day" class="small-6 Day" /></li>
					<li><label for="date_from_uncertainty">Uncertainty</label> <input
						type="hidden" name="date_from_uncertainty"
						class="Uncertainty small-6" /></li>

					<li><label for="date_to_year">Year To</label> <input
						name="date_to_year" type="text" placeholder="Enter year"
						class="small-6" pattern="^[0-9]{4}$" /> <small class="error">Year
							entered is not valid</small></li>
					<li><label for="date_to_month">Month To</label> <input
						name="date_to_month" type="hidden" class="small-6 Month" /></li>
					<li><label for="date_to_day">Day To</label> <input
						type="hidden" name="date_to_day" class="small-6 Day" /></li>
					<li><label for="date_to_uncertainty">Uncertainty</label> <input
						type="hidden" name="date_to_uncertainty"
						class="Uncertainty small-6" /></li>
				</ul>
			</fieldset>

			<fieldset id='fieldsetLocation'>
				<legend>Location</legend>
<p>If the location does not appear in the selection, you can
						add a location to the selection using the EMLO Edit <a
						target="_new"
						href="https://emlo-edit.bodleian.ox.ac.uk/interface/union.php?menu_item_id=39">
							"Add New Place" </a> form.</p>
				<ul class="small-block-grid-4">
					<li><label for="location">Location </label> <input
						type="hidden" name="location_id_1" class="Location small-12"
						value="" /></li>
					<li><br /> <span class="addLocation button tiny">Add</span>&nbsp;</li>

					
				</ul>

			</fieldset>

			<fieldset>
				<legend>Textual Source</legend>
				<p>If the textual source does not appear in the selection, you can
						add a source to the selection using the <a
						target="_new"
						href="source_add.php">
							"Add Textual Source" </a> form.</p>
				<ul class="small-block-grid-4">
					<li><label for="source_id_1">Textual Source</label> <input
						type="hidden" name="source_id_1" class="TextualSource small-12"
						value="" /></li>
					<li><label for="source_details_1">Source Details</label> <textarea
							id="sourceDetails" name="source_details_1"
							class="sourceDetail small-12"></textarea></li>

					<li><br /> <span id="add" class="addSource button tiny">Add</span></li>
					
				</ul>

			</fieldset>
			<fieldset>
				<legend>Data Source</legend>

				<ul class="small-block-grid-2">
					<li><label for="notes_used">Contributing Authors</label> <input
						name="notes_used" class=" small-12"></input></li>

				</ul>
			</fieldset>
			<fieldset>
				<legend>Additional Notes</legend>

				<ul class="small-block-grid-2">

					<li><label for="additional_notes">Additional Notes</label> <textarea
							name="additional_notes" class=" small-12"></textarea></li>

				</ul>
			</fieldset>
		</fieldset>
	</form>
	

	
<!-- proform scripts -->
	<script src="js/common.js"></script>
	<script src="js/form.js"></script>
	<script src="js/family.js"></script>
	<script src="js/config.js"></script>
	<script src="js/input.js"></script>
	<script src="js/edit.js"></script>
	<script src="js/activity.js"></script>
	
	
	<script src="../proforminc/f5/js/foundation/foundation.js"></script>
	<script src="../proforminc/f5/js/foundation/foundation.abide.js"></script>

	<script> $(document).foundation();</script>






</body>
</html>
