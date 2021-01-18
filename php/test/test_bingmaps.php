<?php
//using bingsmap API

//from & to location..
function getPolyline($from, $to){
$key = 'bingmap_api_key';

$url = 'http://dev.virtualearth.net/REST/v1/Routes?key='.$key.'&wayPoint.1='.$from.'&wayPoint.2='.$to.'&routeAttributes=routePath';
//connection..

$bings = curl_init();

curl_setopt($bings, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($bings, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($bings, CURLOPT_URL, $url);
curl_setopt($bings, CURLOPT_RETURNTRANSFER, true);

//getting response from bingmapapis..
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
$p_bingmap = Polyline::encode($p_final);

return $p_bingmap;
}

//testing starts here...
require_once(__DIR__.'/test_location.php');
foreach ($locdata as $item) {
$polyline_bingmap = getPolyline($item['from'], $item['to']);

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
// var_dump(json_decode($response, true));
$data = json_decode($response, true);

$tag = $data['route']['costs']['tag'];
$cash = $data['route']['costs']['cash'];

$dumpFile = fopen("dump.txt", "a") or die("unable to open file!");
fwrite($dumpFile, "from =>");
fwrite($dumpFile, $item['from'].PHP_EOL);
fwrite($dumpFile, "to =>");
fwrite($dumpFile, $item['to'].PHP_EOL);
fwrite($dumpFile, "polyline =>".PHP_EOL);
fwrite($dumpFile, $polyline_bingmap.PHP_EOL);
fwrite($dumpFile, "tag =>");
fwrite($dumpFile, $tag.PHP_EOL);
fwrite($dumpFile, "cash =>");
fwrite($dumpFile, $cash.PHP_EOL);
fwrite($dumpFile, "*************************************************************************".PHP_EOL);

echo "tag = ";
print_r($data['route']['costs']['tag']);
echo "\ncash = ";
print_r($data['route']['costs']['cash']);
echo "\n";
echo "**************************************************************************\n";
}
?>