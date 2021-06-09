<?php
// function that runs when shortcode is called
function map_shortcode_function() {
	
	// check if the submit button is triggered and the date is not null
	if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['selecteddate']!=null) {
		$selected_date = $_POST['selecteddate'];
	}
	else {
		
		// set today's date az default
		$selected_date = date("Y-m-d");
	}
	
	// WordPress database access abstraction class
	global $wpdb;
	
	// calling get_results function from $wpdb class to connect to database to get map pop up data
	$result = $wpdb->get_results("SELECT * FROM `COVIDDAILY` WHERE `date`='".$selected_date."'");
	
	// converting php array to json to use it based on value/key in js code
	$json_cache=json_encode($result);
	
	// calling get_results function from $wpdb class to connect to database to get map plot data
	$canadaplt = $wpdb->get_results("SELECT * FROM `COVIDDAILY` WHERE `provname`='Canada'");
	
	// converting php array to json to use it based on value/key in js code
	$json_canada=json_encode($canadaplt);
	
	// check if we have a data for selected date to show on the map
	if(empty($result) && $_SERVER["REQUEST_METHOD"] == "POST") {
		echo '<p style="color:red">No data founded for the selected date. Select a data before 2021-06-06.</p>';
	}
?>

<!-- including plotly js library -->
<script src='https://cdn.plot.ly/plotly-2.0.0.min.js'></script>

<!-- including google map js API -->
<script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
<script
      src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap&libraries=&v=weekly"
      async
    ></script>


<!-- Start of JS code for MAP -->
<script>
	
//Map initialization
let map;
function initMap() {
  map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 62.6399, lng: -99.5070 },
    zoom: 3,
	styles:[{"elementType":"geometry","stylers":[{"color":"#f5f5f5"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#616161"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#f5f5f5"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text.fill","stylers":[{"color":"#bdbdbd"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#eeeeee"}]},{"featureType":"poi","elementType":"labels.text.fill","stylers":[{"color":"#757575"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#e5e5e5"}]},{"featureType":"poi.park","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#ffffff"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#757575"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#dadada"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"color":"#616161"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"color":"#e5e5e5"}]},{"featureType":"transit.station","elementType":"geometry","stylers":[{"color":"#eeeeee"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#c9c9c9"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#9e9e9e"}]}]
  });
	
//loading map geojson file from public_html
map.data.loadGeoJson("/test/wp-content/themes/astra-child/canada_provinces.geojson")
	
//styling map after adding geojson to the map
map.data.setStyle(function() {
	return {
		fillColor: "#657976",
		strokeColor: "#ddecf0",
		strokeWeight: 1,
		fillOpacity:0.8,
	};
});
	
//defining infowindow to have pop up when mouse hovers on different regions
var infowindow = new google.maps.InfoWindow();
	
//mouseover handler
map.data.addListener("mouseover", (event) => {
	map.data.revertStyle();
	map.data.overrideStyle(event.feature, { fillOpacity:1 });
	
	var windowdata="";
	var PRUID = event.feature.getProperty('PRUID');
	var jsondata = <?=$json_cache; ?>;

	//console.log(jsondata)
	//loop over all provinces
	for(i=0; i<jsondata.length; i++){
		if (jsondata[i].ID == PRUID) {
			windowdata ='<p><b>Province: </b>'+jsondata[i].provname+'</p>'
			windowdata+='<p><b>Confirmed Cases: </b>'+jsondata[i].numconf+'</p>'
			windowdata+='<p><b>Death Cases: </b>'+jsondata[i].numdeaths+'</p>'
		}
		infowindow.setContent(windowdata);
		infowindow.setPosition(event.latLng);
		infowindow.setOptions({pixelOffset: new google.maps.Size(0,-30)});
		infowindow.open(map);
	}
});
	//mouseout handler
	map.data.addListener("mouseout", (event) => {
		map.data.revertStyle();
		infowindow.close(map);
	});


}
</script>
<!-- End of JS code for MAP -->

<?php
//include template file
include_once( 'covidmap-tpl.php' ); 
?>

<!-- Start of JS code for Plot -->
<script>
	
var jsoncanada = <?=$json_canada; ?>;
//console.log(jsoncanada)
let d = [];
let conf = [];
let death = [];
for (i=0; i<jsoncanada.length; i++){
	d.push(jsoncanada[i].date);
	conf.push(jsoncanada[i].numconf);
	death.push(jsoncanada[i].numdeaths);
}

//Trace 1 for Confirmed Cases -- line plot
var trace1 = {
x: d,
y: conf,
mode: 'line',
name: 'Confirmed',
marker: {
	color: '#547E70'
}
};

//Trace 2 for Death Cases -- line plot
var trace2 = {
x: d,
y: death,
mode: 'line',
name: 'Death'
};

var data = [trace1, trace2];

//plot layout
var layout = {
	title: {
    text:'Canada COVID-19 Status',
    xref: 'paper',
    x: 0.05,
  },
  legend: {
    y: 0.5
  },
	xaxis: {
    title: {
      text: 'Date'
    },
  },
  yaxis: {
    title: {
      text: 'Number of Cases'
    }
  }
};

//Creating a plot with defined data and layout
//plt --> graphDiv
Plotly.newPlot('plt', data, layout);

</script>
<!-- End of JS code for Plot -->


<?php
}
// register shortcode
add_shortcode('map_shortcode', 'map_shortcode_function');
