# [Bing Maps](https://www.bingmapsportal.com/)

### Get API key to access Bing Maps APIs (if you have an API key skip this)
#### Step 1: Login/Signup
* Create an account to access [Bing Maps Dev Center](https://www.bingmapsportal.com/)
* go to [signup/login](https://www.bingmapsportal.com/)
* you will need a Microsoft account to access Bing Maps API.
* you will need to agree to [Microsoft Bing Maps Platform's Terms of Service](https://www.microsoft.com/maps/product/terms.html)

#### Step 2: Creating the KEY
* Got to https://www.bingmapsportal.com/application
* You should see your key there.
* You can also create an application specific key.

With this in place, make a GET request: https://www.bingmapsportal.com/

### Note:
* REQUEST should include `routeAttributes` as `routePath`. Setting `routeAttributes` as `routePath` gives us a series of coordinates describing the whole path, which we later convert to encoded polyline.
* Code to get the `polyline` can be found at https://github.com/emcconville/google-map-polyline-encoding-tool

```php

//using googlemaps API

//from & to location..
$from = 'Mainstr,Dallas,TX';
$to = 'Addison,TX';
$key = 'Ao9bE0ntV7wuRjyne1zyVZxQz4xZWQZu5oqB9tGO71je5UNO89zQe52hW81quvqr';

$url = 'http://dev.virtualearth.net/REST/v1/Routes?key='.$key.'&wayPoint.1='.$from.'&wayPoint.2='.$to.'&routeAttributes=routePath';
//connection..

$bings = curl_init();

curl_setopt($bings, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($bings, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($bings, CURLOPT_URL, $url);
curl_setopt($bings, CURLOPT_RETURNTRANSFER, true);

//getting response from googleapis..
$response = curl_exec($bings);
$err = curl_error($bings);

curl_close($bings);

if ($err) {
	  echo "cURL Error #:" . $err;
} else {
	  echo "200 : OK\n";
}

//extracting polyline from the JSON response..
$data_bingmap = json_decode($response, true);
$data_new = $data_bingmap['resourceSets'];
$new_data = $data_new['0'];
$pol_data = $new_data['resources'];
$pol_data_new = $pol_data['0'];
$p_data = $pol_data_new['routePath'];
$p_data_new = $p_data['line'];
$p_final = $p_data_new['coordinates'];

//polyline..
require_once(__DIR__.'/Polyline.php');
$polyline_bingmap = Polyline::encode($p_final);

```

Note:

We extracted the polyline for a route from Bing Maps API

We need to send this route polyline to TollGuru API to receive toll information

## [TollGuru API](https://tollguru.com/developers/docs/)

### Get key to access TollGuru polyline API
* create a dev account to receive a [free key from TollGuru](https://tollguru.com/developers/get-api-key)
* suggest adding `vehicleType` parameter. Tolls for cars are different than trucks and therefore if `vehicleType` is not specified, may not receive accurate tolls. For example, tolls are generally higher for trucks than cars. If `vehicleType` is not specified, by default tolls are returned for 2-axle cars. 
* Similarly, `departure_time` is important for locations where tolls change based on time-of-the-day.

the last line can be changed to following

```php

//using tollguru API..
$curl = curl_init();

curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);


$postdata = array(
	"source" => "gmaps",
	"polyline" => $polyline_bingmap
);

//json encoding source and polyline to send as postfields..
$encode_postData = json_encode($postdata);

curl_setopt_array($curl, array(
CURLOPT_URL => "https://dev.tollguru.com/v1/calc/route",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "POST",


//sending bingmap polyline to tollguru
CURLOPT_POSTFIELDS => $encode_postData,
CURLOPT_HTTPHEADER => array(
				      "content-type: application/json",
				      "x-api-key: 8hjbGhmFqP8HBQJ6NbMpT2FjRNhhtdgT"),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	  echo "cURL Error #:" . $err;
} else {
	  echo "200 : OK\n";
}

//response from tollguru..
var_dump(json_decode($response, true));
// $data = var_dump(json_decode($response, true));
//print_r($data);

```

The working code can be found in `php_curl_bingmaps.php` file.

## License
ISC License (ISC). Copyright 2020 &copy;TollGuru. https://tollguru.com/

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
