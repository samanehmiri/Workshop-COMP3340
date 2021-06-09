<div class="row">
	<div class="col-2">
		<form method="post" action="">
			<h5>Select Date:</h5>
			<input class="date" type="date" id="selecteddate" name="selecteddate" value="<?php echo date('Y-m-d', strtotime($selected_date)); ?>" />
			<input class="btn" type="submit" value="Search">
		</form>
	</div>
	<div class="col-8" id="map"></div>
	<div class="col-10" id="plt"></div>
</div>
