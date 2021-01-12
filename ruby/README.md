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

```ruby
# Source Details 
SOURCE = 'Dallas, TX'
# Destination Details 
DESTINATION = 'New York, NY'

# BING API KEY
KEY = ENV['BING_KEY']
BING_URL = "http://dev.virtualearth.net/REST/v1/Routes?key=#{KEY}&wayPoint.1=#{SOURCE}&wayPoint.2=#{DESTINATION}&routeAttributes=routePath"
RESPONSE = HTTParty.get(BING_URL).body
json_parsed = JSON.parse(RESPONSE)

# Extracting mapbox polyline from JSON
bing_coordinates_array = json_parsed['resourceSets'].map { |x| x['resources'] }.pop.map { |y| y['routePath']}.map {|z| z['line']}.pop['coordinates']
google_encoded_polyline = FastPolylines.encode(bing_coordinates_array)
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

```ruby
TOLLGURU_URL = 'https://dev.tollguru.com/v1/calc/route'
TOLLGURU_KEY = ENV['TOLLGURU_KEY']
headers = {'content-type' => 'application/json', 'x-api-key' => TOLLGURU_KEY}
body = {'source' => "mapbox", 'polyline' => google_encoded_polyline, 'vehicleType' => "2AxlesAuto", 'departure_time' => "2021-01-05T09:46:08Z"}
tollguru_response = HTTParty.post(TOLLGURU_URL,:body => body.to_json, :headers => headers)
```

The working code can be found in main.rb file.

## License
ISC License (ISC). Copyright 2020 &copy;TollGuru. https://tollguru.com/

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
