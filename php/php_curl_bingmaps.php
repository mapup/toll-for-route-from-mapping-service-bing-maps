<?php
//using googlemaps API

//from & to location..
$from = 'Dallas,TX';
$to = 'NewYork,NY';
$key = 'bings_api_key';

$url = 'http://dev.virtualearth.net/REST/v1/Routes?key='.$key.'&wayPoint.1='.$from.'&wayPoint.2='.$to.'&routeAttributes=routePath';

//connection..
$bings = curl_init();

curl_setopt($bings, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($bings, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($bings, CURLOPT_URL, $url);
curl_setopt($bings, CURLOPT_RETURNTRANSFER, true);

//getting response from binmapsapis..
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
$p_final = $data_bingmap['resourceSets']['0']['resources']['0']['routePath']['line']['coordinates'];

//polyline..
require_once(__DIR__.'/Polyline.php');
$polyline_bingmap = Polyline::encode($p_final);



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
				      "x-api-key: tollguru_api_key"),
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
$data = var_dump(json_decode($response, true));
print_r($data);
?>