require 'HTTParty'
require 'json'
require "fast_polylines"
# Source Details 
SOURCE = 'Dallas, TX'
# Destination Details
DESTINATION = 'New York, NY'

# GET Request to Bing for Polyline
KEY = ENV['BING_KEY']
BING_URL = "http://dev.virtualearth.net/REST/v1/Routes?key=#{KEY}&wayPoint.1=#{SOURCE}&wayPoint.2=#{DESTINATION}&routeAttributes=routePath"
RESPONSE = HTTParty.get(BING_URL).body
json_parsed = JSON.parse(RESPONSE)

# Extracting mapbox polyline from JSON. HERE coordinates are encoded to google polyline
bing_coordinates_array = json_parsed['resourceSets'].map { |x| x['resources'] }.pop.map { |y| y['routePath']}.map {|z| z['line']}.pop['coordinates']
google_encoded_polyline = FastPolylines.encode(bing_coordinates_array)
# Sending POST request to TollGuru
TOLLGURU_URL = 'https://dev.tollguru.com/v1/calc/route'
TOLLGURU_KEY = ENV['TOLLGURU_KEY']
headers = {'content-type' => 'application/json', 'x-api-key' => TOLLGURU_KEY}
body = {'source' => "mapbox", 'polyline' => google_encoded_polyline, 'vehicleType' => "2AxlesAuto", 'departure_time' => "2021-01-05T09:46:08Z"}
tollguru_response = HTTParty.post(TOLLGURU_URL,:body => body.to_json, :headers => headers)