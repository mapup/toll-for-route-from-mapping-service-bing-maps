require 'HTTParty'
require 'json'
require "fast_polylines"
require 'cgi'

def get_toll_rate(from,to)
    # Source Details 
    source = from
    # Destination Details
    destination = to

    # GET Request to Bing for Polyline
    key = ENV['BING_KEY']
    bing_url = "http://dev.virtualearth.net/REST/v1/Routes?key=#{key}&wayPoint.1=#{CGI::escape(source)}&wayPoint.2=#{CGI::escape(destination)}&routeAttributes=routePath"
    response = HTTParty.get(bing_url).body
    json_parsed = JSON.parse(response)

    # Extracting mapbox polyline from JSON. HERE coordinates are encoded to google polyline
    bing_coordinates_array = json_parsed['resourceSets'].map { |x| x['resources'] }.pop.map { |y| y['routePath']}.map {|z| z['line']}.pop['coordinates']
    google_encoded_polyline = FastPolylines.encode(bing_coordinates_array)
    # Sending POST request to TollGuru
    tollguru_url = 'https://dev.tollguru.com/v1/calc/route'
    tollguru_key = ENV['TOLLGURU_KEY']
    headers = {'content-type' => 'application/json', 'x-api-key' => tollguru_key}
    body = {'source' => "mapbox", 'polyline' => google_encoded_polyline, 'vehicleType' => "2AxlesAuto", 'departure_time' => "2021-01-05T09:46:08Z"}
    tollguru_response = HTTParty.post(tollguru_url,:body => body.to_json, :headers => headers)
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