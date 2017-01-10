<?php 

function bannerHTML(){
	
$html = 
<<<html
	<a href="/interface/union.php" title="EMLO Edit home page"><img src="https://emlo-edit.bodleian.ox.ac.uk/interface/CofKLogo.png" alt="EMLO Edit" class="bannerlogo"></a><h1>Union Catalogue Editing Interface : Prosopography</h1>
	<A href="/interface/union.php?menu_item_id=&sesstoken=9d7ed728b78b9071dfcd27bdbfaa4313" title="Main Menu" target="_self" tabindex="1">
	Main Menu
	</A>
	
	&bull;
	<A href="/interface/union.php?logout=1&sesstoken=9d7ed728b78b9071dfcd27bdbfaa4313" title="Log out of Union Catalogue Editing Interface" target="_self" tabindex="1">
	Logout
	</A>
	
	&bull; <a href="activity_view.php" title="" >Prosopography Events</a>
	&bull; <a href="activity_add.php" title="" >Add Event</a>
	
	
html;

return $html;

}







function summaryFilter(){
	
	$html =
<<<html
	
		
	<fieldset>
	<legend>Filters</legend>
		<p>Use the filters to restrict the list of events displayed.</p>
	<ul class="small-block-grid-1">
	<li><label>Primary Participant</label><input type="hidden" name="person_id"
			id="Person"  class="Person Filter" />
			<input type="hidden" name="filterPerson" value="" /> <input
			type="hidden" name="filterPersonText" value="" /></li>
	
			<li><label>Activity Type </label> <input type="hidden"
					name="activity_type" id="Activity" class="Activity Filter small-12" />
					<input type="hidden"  name="filterActivity" value="" />
		 <input		type="hidden" name="filterActivityText" value="" /> </li>
	
					<li>
					<label>Date Type</label>
				<!--	<input type="radio" name="filterDateType" value="After" />After
					<input type="radio" name="filterDateType" value="Before" />Before
					<input type="radio" name="filterDateType" value="Duration" />Duration
					<input type="radio" name="filterDateType" value="Between" />Between
					<input type="radio" name="filterDateType" value="" />All
	
						-->
		
		<select name="filterDateType">
					<option value="" >All date types</option>
					<option  value="After" >After</option>
					<option  value="Before" >Before</option>
					<option  value="Duration" >Duration</option>
					<option value="Between" >Between</option>
					
		
		</select>
<input type="hidden" name="filterDateTypeText" value=""/>
					<br/>
					<label>Year From</label>
					<input type="text" name="filterYearFrom" value=""/>
			<input type="hidden" name="filterYearFromText" value=""/>			
					<span id="spanYearTo">
					<label>Year To</label>
					<input type="text" name="filterYearTo" value=""/></span>
					<input type="hidden" name="filterYearToText" value=""/>
					<div id="slider-range"></div>
	
					</li>
					<li><label>Location</label>
					<input type="hidden" name="location_id" id="Location" class="Location Filter" />
					<br/>
					<br/>
					<input type="hidden" id="Location" name="filterLocation" value="" />
					<input type="hidden" id = "LocationText" name="filterLocationText" value=""/>
					</li>
					<li><label>Last Edited By</label>
					<input type="hidden" name="editor_id" id="Editor" class="Editor Filter" />
					<input type="hidden" id="Editor" name="filterEditor" value="" />
					<input type="hidden" id = "EditorText" name="filterEditorText" value=""/>
					
					</li>
					</ul>
		
		<span class="resetForm button tiny">Reset Filters</span> 
					</fieldset>			
html;

return $html;	
	
}



function summaryTable(){
	
$html = 
<<<html
<fieldset id="Activities">
	<legend>Events</legend>
	<div class="pagination-centered"></div>
	<table id="summaryTable"></table>
</fieldset>

html;

return $html;

}
?>
