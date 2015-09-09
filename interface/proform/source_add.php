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
<link rel="stylesheet" href="css/style.css"></link>

</head>
<body>

<div class="banner" id="pagebanner">

<a href="/interface/union.php" title="EMLO Edit home page"><img src="/interface/CofKLogo.png" alt="EMLO Edit" class="bannerlogo"></a><h1>Union Catalogue Editing Interface : Prosopography</h1>
<A href="/interface/union.php?menu_item_id=&sesstoken=9d7ed728b78b9071dfcd27bdbfaa4313" title="Main Menu" target="_self" tabindex="1">
Main Menu
</A>

 &bull; 
<A href="/interface/union.php?logout=1&sesstoken=9d7ed728b78b9071dfcd27bdbfaa4313" title="Log out of Union Catalogue Editing Interface" target="_self" tabindex="1">
Logout
</A>


 &bull; <a href="/interface/proform/activity_view.php" title="" >Prosopography Events</a>
 &bull; <a href="/interface/proform/activity_add.php" title="" >Add Event</a>

<br/>
<h2>Add Textual Source</h2><br/>

</div>



	<form method="GET" id="form1" name="form1" data-abide="">
		<input type="hidden" name="activity_id" value="" /> 
		<input type="hidden" name="source_action" value="add"/>

<br/>
					<span class="resetForm button tiny">Reset</span></span>
					<span class="submitFormAddTextualSource button tiny">Save</span></span>
					<span id="messages"></span>
		
			
<fieldset>
			
<div id="inputForm"></div>
			
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
	htmlInputFormSource("add");
});


</script>

</body>
</html>
