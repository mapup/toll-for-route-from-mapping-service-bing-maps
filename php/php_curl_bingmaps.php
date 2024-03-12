<?php
$BING_API_KEY = getenv('BING_API_KEY'); // Token from Bing Maps
$BING_API_URL = 'http://dev.virtualearth.net/REST/v1/Routes';

$TOLLGURU_API_KEY = getenv('TOLLGURU_API_KEY'); // API key for Tollguru
$TOLLGURU_API_URL = 'https://apis.tollguru.com/toll/v2'; // Base URL for TollGuru Toll API
$POLYLINE_ENDPOINT = 'complete-polyline-from-mapping-service';

// From & To locations
$source = 'Philadelphia, PA';
$destination = 'New York, NY';

// Explore https://tollguru.com/toll-api-docs to get the best of all the parameters that tollguru has to offer
$request_parameters = array(
    "vehicle" => array(
        "type" => "2AxlesAuto",
    ),
    // Visit https://en.wikipedia.org/wiki/Unix_time to know the time format
    "departure_time" => "2021-01-05T09:46:08Z",
);

$url = $BING_API_URL . '?key=' . $BING_API_KEY . '&wayPoint.1=' . urlencode($source) . '&wayPoint.2=' . urlencode($destination) . '&routeAttributes=routePath';

// Connection
$bings = curl_init();

curl_setopt($bings, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($bings, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($bings, CURLOPT_URL, $url);
curl_setopt($bings, CURLOPT_RETURNTRANSFER, true);

// Getting response from BingMaps API
$response = curl_exec($bings);
$err = curl_error($bings);

curl_close($bings);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo "200 : OK\n";
}

// Extracting polyline from the JSON response
$data_bingmap = json_decode($response, true);
$p_final = $data_bingmap['resourceSets']['0']['resources']['0']['routePath']['line']['coordinates'];

// Polyline
require_once(__DIR__ . '/Polyline.php');
$polyline_bingmap = Polyline::encode($p_final);

// Using tollguru API
$curl = curl_init();

curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

$postdata = array(
  "source" => "bing",
  "polyline" => $polyline_bingmap,
  ...$request_parameters,
);

// JSON encoding source and polyline to send as postfields
$encode_postData = json_encode($postdata);

curl_setopt_array($curl, array(
  CURLOPT_URL => $TOLLGURU_API_URL . $POLYLINE_ENDPOINT,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",

  // Sending bingmap polyline to TollGuru
  CURLOPT_POSTFIELDS => $encode_postData,
  CURLOPT_HTTPHEADER => array(
    "content-type: application/json",
    "x-api-key: " . $TOLLGURU_API_KEY
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo "200 : OK\n";
}

// Response from TollGuru
$data = var_dump(json_decode($response, true));
print_r($data);
