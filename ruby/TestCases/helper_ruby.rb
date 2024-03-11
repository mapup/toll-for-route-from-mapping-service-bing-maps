require 'HTTParty'
require 'json'
require "fast_polylines"
require 'cgi'

BING_API_KEY = ENV['BING_API_KEY'] # Token from Bing Maps
BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

TOLLGURU_API_KEY = ENV['TOLLGURU_API_KEY'] # API key for Tollguru
TOLLGURU_API_URL = 'https//api.tollguru.com/toll/v2' # Base URL for TollGuru Toll API
POLYLINE_ENDPOINT = 'complete-polyline-from-mapping-service'

# Explore https://tollguru.com/toll-api-docs to get the best of all the parameters that tollguru has to offer
request_parameters = {
  "vehicle": {
    "type": "2AxlesAuto",
  },
  # Visit https://en.wikipedia.org/wiki/Unix_time to know the time format
  "departure_time": "2021-01-05T09:46:08Z",
}

def get_toll_rate(from,to)
  # Source Details 
  source = from
  # Destination Details
  destination = to

  # GET Request to Bing for Polyline
  key = ENV['BING_KEY']
  bing_url = "#{BING_API_URL}?key=#{key}&wayPoint.1=#{CGI::escape(source)}&wayPoint.2=#{CGI::escape(destination)}&routeAttributes=routePath"
  response = HTTParty.get(bing_url).body
  json_parsed = JSON.parse(response)

  # Extracting mapbox polyline from JSON. HERE coordinates are encoded to google polyline
  bing_coordinates_array = json_parsed['resourceSets'].map { |x| x['resources'] }.pop.map { |y| y['routePath']}.map {|z| z['line']}.pop['coordinates']
  google_encoded_polyline = FastPolylines.encode(bing_coordinates_array)
  # Sending POST request to TollGuru
  headers = {'content-type': 'application/json', 'x-api-key': TOLLGURU_API_KEY}
  body = {
    'source': "bing",
    'polyline': google_encoded_polyline,
    **request_parameters,
  }
  tollguru_response = HTTParty.post("#{TOLLGURU_API_URL}/#{POLYLINE_ENDPOINT}",:body => body.to_json, :headers => headers)
  begin
    toll_body = JSON.parse(tollguru_response.body)    
    if toll_body["route"]["hasTolls"] == true
      return google_encoded_polyline,toll_body["route"]["costs"]["tag"], toll_body["route"]["costs"]["cash"] 
    else
      raise "No tolls encountered in this route"
    end
  rescue Exception => e
    puts e.message 
  end
end
