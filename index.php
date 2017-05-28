<?php

/**
 * This is the minimum unit delegation to vote ratio calculator, part of the
 * Young Democrats of America (YDA) chartering and credentials process.  It
 * uses the Google Maps API to calculate the mileage between the site of the
 * convention and the state capital or largest city (whichever is furthest)
 * based on Section 370(b) of the YDA Charter.  If a state or territory is
 * overseas, the state will be allocated the maximum possible amount of votes.
 *
 * @author   Ricardo Alfaro <ralfaro@yda.org>
 * @license  http://www.opensource.org/licenses/mit-license.php  MIT License
 *
 * Written based on the 2016 YDA Charter and Bylaws:
 * 
 * Section 370(b) Minimum Unit Delegation to Vote Ratio; Mileage Formula. Chartered units 
 * shall be permitted to cast votes allocated under the provisions of the Charter and the 
 * Bylaws provided that each unit is represented by registered Delegates, present in person, 
 * whose aggregate number does not fall below the votes per delegate ratio established 
 * according to state driving mileage from each unit's territorial capitol or largest city, 
 * whichever is furthest, to the site of the National Convention as follows: One (1) to five 
 * hundred (500) miles, one (1) delegate for every two (2) votes; five hundred one (501) to 
 * one thousand (1000) miles, one (1) delegate for every three (3) votes; one thousand one 
 * (1001) to one thousand five hundred (1500) miles, one (1) delegate for every four (4) 
 * votes; one thousand five hundred one (1501) to two thousand five hundred (2500) miles, 
 * one (1) delegate for every five (5) votes; any distance in excess of two thousand five 
 * hundred (2500) miles, one (1) delegate for every six (6) votes. Not less than sixty (60) 
 * days prior to each National Convention, the Chair of the Standing Committee on Credentials 
 * shall prepare a chart derived by the mileage indicated in the current Rand McNally Atlas 
 * or comparable mapping standard, of each chartered unit's driving mileage from territorial 
 * capitol to convention site, along with the delegate to vote ratio derived therefrom.
*/

// Google Maps API Key
$google_key = '';

// Convention Site
// Please place here the full street address of the convention venue
$convention_venue = '13340 Dallas Pkwy, Dallas, TX 75240';

// Replace whitespace with + for Google API compliance
$convention_venue_compliant = str_replace(' ', '+', $convention_venue);

// Google Maps Geolocation API
$venue_geolocation = 'https://maps.googleapis.com/maps/api/geocode/json?address='.$convention_venue_compliant.'&key='.$key;
$venue_json = file_get_contents($venue_geolocation);
$json = json_decode($venue_json,true);

// Get the latitude and longitude of the venue
foreach($json['results'] as $venue_location)
{
	$latitude = $venue_location['geometry']['location']['lat'];
	$longitude = $venue_location['geometry']['location']['lng'];
}

$destination = $latitude.','.$longitude;

// Get city listing from internal JSON file
$city_listing = 'state_territory_list.json';
$city_listing_json = file_get_contents($city_listing);
$json = json_decode($city_listing_json,true);

function getMiles($i)
{
	return $i*0.000621371192;
}

function getDistance($origin,$destination,$key)
{
	$origin = str_replace(' ', '+', $origin);

	$get_distance = 'https://maps.googleapis.com/maps/api/directions/json?origin='.$origin.','.$city['abbr'].'&destination='.$destination.'&key='.$key;
	$distance_json = file_get_contents($get_distance);
	$json = json_decode($distance_json,true);

	if($json['status'] != "ZERO_RESULTS")
	{
		foreach($json['routes'] as $routes)
		{
			$distance = $routes['legs'][0]['distance']['value'];
			$miles = number_format(getMiles($distance),2);
		}
	}
	else
	{
		$miles = "Not available";
	}

	return $miles;
}

function getLargest($first_value,$second_value)
{
	if($first_value > $second_value)
	{
		$final_value = $first_value;
	}
	else
	{
		$final_value = $second_value;
	}
	return $final_value;
}

function getVotes($value)
{
	if($value == "Not available")
	{
		$votes = 6;
	}
	else
	{
		$value = str_replace(',', '', $value);

		if($value <= 500)
		{
			$votes = 2;
		}
		else if($value <= 1000)
		{
			$votes = 3;
		}
		else if($value <= 1500)
		{
			$votes = 4;
		}
		else if($value <= 2500)
		{
			$votes = 5;
		}
		else if($value > 2500)
		{
			$votes = 6;
		}
		else
		{
			$votes = 6;
		}
	}

	return $votes;
}



?>
<!DOCTYPE html>
<html>
	<head>
	  <!-- Latest compiled and minified CSS -->
	  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

	  <!-- Optional theme -->
	  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

	  <!-- Latest compiled and minified JavaScript -->
	  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    <style>
      #map {
        height: 200px;
        width: 100%;
       }
       #footer {
	    background-color: #34375f;
	    color: #f3f3f3;
	    padding-top: 15px;
	    padding-bottom: 5px;
       }
    </style>
  	</head>
  	<body>
		<div class="container">
			<div align="center">
			<h2>YOUNG DEMOCRATS OF AMERICA<br/>MINIMUM UNIT DELEGATION TO VOTE RATIO</h2>
			</div>
			<div id="map"></div>
	  		<div align="center">
	  		<?php // Show venue location, latitude and longitude
		  		echo('<h3>Convention site:'.$convention_venue.'</h3>');
		  		echo('<p class="lead">Latitude: '.$latitude.' Longitude:'.$longitude.'</p>'); ?>
	  		</div>
	  		<table class="table table-striped">
		  		<tr>
			  		<td><strong>State</strong></td>
			  		<td><strong>Capital</strong></td>
			  		<td><strong>Distance from Capital</strong></td>
			  		<td><strong>Largest City</strong></td>
			  		<td><strong>Distance from Largest City</strong></td>
			  		<td><strong>Largest Distance</strong></td>
			  		<td><strong>Vote Ratio</strong></td>
		  		</tr>
			<?php
			
			foreach($json['listing'] as $state)
			{
				$capital = $state['capital'].','.$state['abbr'];
				$from_capital = getDistance($capital,$destination,$google_key);

				if($state['capital'] != $state['largest_city'])
				{
					$capital = $state['capital'].','.$state['abbr'];
					$from_capital = getDistance($capital,$destination,$google_key);

					$largest_city = $state['largest_city'].','.$state['abbr'];
					$from_largest_city = getDistance($largest_city,$destination,$google_key);

					$origin_value = getLargest($from_capital,$from_largest_city);
				}
				else
				{
					$from_largest_city = $from_capital;
					$origin_value = $from_capital;
				}
				echo('<tr>');
				echo('<td>'.$state['state'].'</td>');
				echo('<td>'.$state['capital'].'</td>');
				echo('<td>'.$from_capital.'</td>');
				echo('<td>'.$state['largest_city'].'</td>');
				echo('<td>'.$from_largest_city.'</td>');
				echo('<td>'.$origin_value.'</td>');
				echo('<td align="center"><strong>'.getVotes($origin_value).'</strong></td>');
				echo('</tr>');
			} ?>
	  		</table>
		</div>
	  	<div id="footer">
		  	<div class="container">
			<p><strong>Legal base and details:</strong><br/>
			If a state or territory is overseas, the state will be allocated the maximum possible amount of votes (not drivable according to Google Maps).  Largest city is based on the 2010 United States Census.  All distances are in miles.</p>
			<p><strong>YDA Charter, Article III - Section 370(b) Minimum Unit Delegation to Vote Ratio; Mileage Formula</strong><br/>
				Chartered units shall be permitted to cast votes allocated under the provisions of the Charter and the Bylaws provided that each unit is represented by registered Delegates, present in person, whose aggregate number does not fall below the votes per delegate ratio established according to state driving mileage from each unit's territorial capitol or largest city, whichever is furthest, to the site of the National Convention as follows: One (1) to five hundred (500) miles, one (1) delegate for every two (2) votes; five hundred one (501) to one thousand (1000) miles, one (1) delegate for every three (3) votes; one thousand one (1001) to one thousand five hundred (1500) miles, one (1) delegate for every four (4) votes; one thousand five hundred one (1501) to two thousand five hundred (2500) miles, one (1) delegate for every five (5) votes; any distance in excess of two thousand five hundred (2500) miles, one (1) delegate for every six (6) votes. Not less than sixty (60) days prior to each National Convention, the Chair of the Standing Committee on Credentials shall prepare a chart derived by the mileage indicated in the current Rand McNally Atlas or comparable mapping standard, of each chartered unit's driving mileage from territorial capitol to convention site, along with the delegate to vote ratio derived therefrom.</p>
		</div>
		
		<script>
		function initMap() {
        var uluru = {lat: <?php echo($latitude); ?>, lng: <?php echo($longitude); ?>};
        var map = new google.maps.Map(document.getElementById('map'), {
          zoom: 15,
          center: uluru
        });
        var marker = new google.maps.Marker({
          position: uluru,
          map: map
        });
      	}
    	</script>
		<script async defer
			src="https://maps.googleapis.com/maps/api/js?key=<?php echo($google_key); ?>&callback=initMap">
    	</script>
	</body>
</html>