require 'HTTParty'
require 'json'
require "fast_polylines"
require 'cgi'


BING_API_KEY = ENV['BING_API_KEY'] # Token from Bing Maps
BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

TOLLGURU_API_KEY = ENV['TOLLGURU_API_KEY'] # API key for Tollguru
TOLLGURU_API_URL = 'https//api.tollguru.com/toll/v2' # Base URL for TollGuru Toll API
POLYLINE_ENDPOINT = 'complete-polyline-from-mapping-service'

SOURCE = 'Dallas, TX'
DESTINATION = 'New York, NY'

# GET Request to Bing for Polyline
RESPONSE = HTTParty.get("#{BING_API_URL}?key=#{BING_API_KEY}&wayPoint.1=#{CGI::escape(SOURCE)}&wayPoint.2=#{CGI::escape(DESTINATION)}&routeAttributes=routePath").body
json_parsed = JSON.parse(RESPONSE)

# Extracting mapbox polyline from JSON. HERE coordinates are encoded to google polyline
bing_coordinates_array = json_parsed['resourceSets'].map { |x| x['resources'] }.pop.map { |y| y['routePath']}.map {|z| z['line']}.pop['coordinates']
google_encoded_polyline = FastPolylines.encode(bing_coordinates_array)

# Sending POST request to TollGuru
headers = {'content-type' => 'application/json', 'x-api-key' => TOLLGURU_API_KEY}
body = {'source' => "mapbox", 'polyline' => google_encoded_polyline, 'vehicleType' => "2AxlesAuto", 'departure_time' => "2021-01-05T09:46:08Z"}
tollguru_response = HTTParty.post("#{TOLLGURU_API_URL}/#{POLYLINE_ENDPOINT}",:body => body.to_json, :headers => headers)
