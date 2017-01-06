<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("lib/user.php");
include("lib/html.php");
?>

<html>
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Prosopography Input Form</title>

<!-- THIRD PARTY SCRIPTS -->
<script src="//code.jquery.com/jquery-1.10.2.js"></script>
<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>
<script src="../proforminc/jquery-1.11.1.min.js"></script>
<script src="../proforminc/jquery-ui-1.10.4/js/jquery-ui-1.10.4.js"></script>
<script src="../proforminc/select2-3.5.1/select2.js"></script>
<script src="../proforminc/f5/js/vendor/modernizr.js"></script>

<!-- THIRD PARTY CSS -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
<link rel="stylesheet" href="../proforminc/select2-3.5.1/select2.css"/>
<link rel="stylesheet" href="../proforminc/f5/css/foundation.css"/>
<link rel="stylesheet" href="../proforminc/f5/css/foundation-icons.css"/>

<!-- PROFORM CSS -->
<link rel="stylesheet" type="text/css" href="css/style.css"/>

</head>
<body>
<div class="banner" id="pagebanner">
<?=bannerHTML()?>
<br/>
<h2>Prosopography Events</h2><br/>
</div>
<form method="GET" id="form1" name="form1" data-abide="">
	<input type="hidden" name="activity_id" value="" />
	<input type="hidden" name="action" value="deleteRecord" />
	<input type="hidden" name="activity_action" value="delete"/>
	
	<span id="messages"></span>
	<fieldset>
	<legend>Active Filters</legend>
	
	<span id="activeFilters">
	
	<span class="filterPerson"></span>
	<span class="filterActivity"></span>
	<span class="filterLocation"></span>
	<span class="filterEditor"></span>
	
	
	
	</span></fieldset>
	<div class="row">
	<div class="small-1 columns"><?=summaryFilter() ?></div>
	<div class="small-10 columns"><?=summaryTable() ?></div>
	</div>
	
	
	

	</form>
	
	
	<!-- MODAL WINDOW TO DISPLAY SELECTED REPORT -->
	<div id="myModal" class="reveal-modal" data-reveal="">
		<div id="myModalContent"></div>
		
	</div>

	<!-- proform scripts -->
	<script src="js/config.js"></script>
	<script src="js/common.js"></script>
	<script src="js/form.js"></script>
	<script src="js/summary.js"></script>


	<!-- third party scripts -->
	<script src="../proforminc/f5/js/foundation/foundation.js"></script>
	<script src="../proforminc/f5/js/foundation/foundation.abide.js"></script>
	<script src="../proforminc/f5/js/foundation.min.js"></script>
	<script> $(document).foundation();</script>


</body>
</html>
