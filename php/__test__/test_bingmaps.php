<?php
$BING_API_KEY = getenv('BING_API_KEY'); // Token from Bing Maps
$BING_API_URL = 'http://dev.virtualearth.net/REST/v1/Routes';

$TOLLGURU_API_KEY = getenv('TOLLGURU_API_KEY'); // API key for Tollguru
$TOLLGURU_API_URL = 'https://apis.tollguru.com/toll/v2'; // Base URL for TollGuru Toll API
$POLYLINE_ENDPOINT = 'complete-polyline-from-mapping-service';

// Explore https://tollguru.com/toll-api-docs to get the best of all the parameters that tollguru has to offer
$request_parameters = array(
  "vehicle" => array(
    "type" => "2AxlesAuto",
  ),
  // Visit https://en.wikipedia.org/wiki/Unix_time to know the time format
  "departure_time" => "2021-01-05T09:46:08Z",
);

//from & to location..
function getPolyline($from, $to) {
  global $BING_API_URL, $BING_API_KEY;

  $url = $BING_API_URL.'?key='.$BING_API_KEY.'&wayPoint.1='.urlencode($from).'&wayPoint.2='.urlencode($to).'&routeAttributes=routePath';
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
    "source" => "bing",
    "polyline" => $polyline_bingmap,
    ...$request_parameters,
  );

  //json encoding source and polyline to send as postfields..
  $encode_postData = json_encode($postdata);

  curl_setopt_array($curl, array(
    CURLOPT_URL => $TOLLGURU_API_URL . "/" . $POLYLINE_ENDPOINT,
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
      "x-api-key: " . $TOLLGURU_API_KEY
    )));

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
