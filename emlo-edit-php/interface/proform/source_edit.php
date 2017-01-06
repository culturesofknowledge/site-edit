<?php

include("lib/user.php");
?>
<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Prosopography Input Form</title>

<!-- third party libraries -->
<script src="../proforminc/jquery-1.11.1.min.js"></script>
<script src="../proforminc/jquery-ui-1.10.4/js/jquery-ui-1.10.4.js"></script>
<script src="../proforminc/select2-3.5.1/select2.js"></script>
<script src="../proforminc/f5/js/vendor/modernizr.js"></script>


<!-- proform scripts -->
<script src="./js/form.js"></script>
<script src="./js/family.js"></script>

<!-- third party css -->
<link rel="stylesheet" href="../proforminc/select2-3.5.1/select2.css"></link>
<link rel="stylesheet" href="../proforminc/f5/css/foundation.css"></link>
<link rel="stylesheet" href="../proforminc/f5/css/foundation-icons.css"></link>

<!-- proform css -->
<link rel="stylesheet" href="css/style.css"/>

</head>
<body>
	<h1>Edit Textual Source</h1>

	<form method="GET" id="form1" name="form1" data-abide="">
	<input type="hidden" name="source_action" value="edit"/>
	<input type="hidden" name="source_id" value=""/>
		<fieldset>
			<fieldset id="fieldsetActions">
				<span><label>Navigation</label> <span
					class="Browse button tiny ">Browse All Activities </span> <span
					class="activityForm button tiny"> Add Activity </span> <span
					class="sourceForm button tiny">Browse All Textual Sources</span> <span
					class="addTextualSource button tiny ">Add Textual
						Source</span>
						
						<span
					class="disabled button tiny ">Edit Textual
						Source</span>
						 <span><label>Actions</label> <span
						class="resetFormEdit button tiny"> Reset Form </span> <span
						class="editTextualSource button tiny"> Save Textual Source
					</span> </span> <span id="messages"></span>
			</fieldset>
			<fieldset id="inputForm"></fieldset>
	</form>

	<!-- proform scripts -->
	<script src="js/config.js"></script>
	<script src="js/common.js"></script>
	<script src="js/source.js"></script>
	
	<!-- third party scripts -->
	<script src="../proforminc/f5/js/foundation/foundation.js"></script>
	<script src="../proforminc/f5/js/foundation/foundation.abide.js"></script>
	<script src="../proforminc/f5/js/foundation.min.js"></script>
	<script> $(document).foundation();</script>

	<script>

$(document).ready(function() { 
	inputFormTextualSourceEdit();
});

</script>

</body>
</html>
