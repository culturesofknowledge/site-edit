<?php

include("lib/user.php");
?>

<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Textual Sources</title>

<!-- third party libraries -->
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
<h1>Browse All Textual Sources</h1>
	<form method="GET" id="form1" name="form1" data-abide="">
		<input type="hidden" name="source_id" value="" /> 
		<input type="hidden" name="source_action" value="delete"/>
		<fieldset>
			<fieldset class="">
			
			<span><label>Navigation</label>
		<span class="Browse button tiny ">Browse All Activities </span>
		 <span class="activityForm button tiny"> Add Activity </span> 
		 <span class="sourceForm button tiny disabled"> Browse All Textual Sources </span> 
					</span>
					<span
					class="addTextualSource button tiny"> Add Textual Source </span>
				 <span
					id="messages"></span>
			</fieldset>



			<fieldset id="TextualSources">
				<legend>Textual Sources</legend>
				<table id="summaryTable"></table>
			</fieldset>
		</fieldset>
	</form>


	<!-- MODAL WINDOW TO DISPLAY SELECTED REPORT -->
	<div id="myModal" class="reveal-modal" data-reveal>
		<div id="myModalContent"></div>
		<a class="close-reveal-modal">&#215;</a>
	</div>

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
<!-- DOCUMENT READY -->
$(document).ready(function() { 
	addSummaryTableSource();
});

</script>

</body>
</html>
