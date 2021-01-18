# [Bing Maps](https://www.bingmapsportal.com/)

### Get API key to access Bing Maps APIs (if you have an API key skip this)
#### Step 1: Login/Signup
* Create an account to access [Bing Maps Dev Center](https://www.bingmapsportal.com/)
* go to signup/login link https://www.bingmapsportal.com/
* you will need a Microsoft account to access Bing Maps API.
* you will need to agree to Micrsoft Bing Maps Platform's Terms of Service https://www.microsoft.com/maps/product/terms.html

#### Step 2: Creating the KEY
* Got to https://www.bingmapsportal.com/application
* You should see your key there.
* You can also create and application specific key.

With this in place, make a GET request: https://www.bingmapsportal.com/

### Note:
* we will be sending `routeAttributes` as `routePath`. Setting `routeAttributes` as `routePath` gives us a series of coordinates describing the whole path, which we later convert to encoded polyline.

```javascript
const request = require("request");
const polyline = require("polyline");

// Token from Bing Maps
const key = process.env.BING_MAPS_API_KEY
const tollguruKey = process.env.TOLLGURU_KEY

const source = 'Dallas, TX'
const destination = 'New York, NY';

const url = `http://dev.virtualearth.net/REST/v1/Routes?key=${key}&wayPoint.1=${source}&wayPoint.2=${destination}&routeAttributes=routePath`

const head = arr => arr[0];
const flatten = (arr, x) => arr.concat(x);

// JSON path "$..coordinates"
const getRoutePath = body => body.resourceSets
  .map(x => x.resources)
  .reduce(flatten)
  .map(x => x.routePath)
  .map(x => x.line.coordinates)
  .reduce(flatten)

const getPolyline = body => polyline.encode(getRoutePath(JSON.parse(body)));

const getRoute = (cb) => request(url, cb);

const handleRoute  = (cb) => (e, r, body) => console.log(getPolyline(body));

getRoute(handleRoute);
```

Note:

We extracted the polyline for a route from Bing Maps API

We need to send this route polyline to TollGuru API to receive toll information

## [TollGuru API](https://tollguru.com/developers/docs/)

### Get key to access TollGuru polyline API
* create a dev account to receive a free key from TollGuru https://tollguru.com/developers/get-api-key
* suggest adding `vehicleType` parameter. Tolls for cars are different than trucks and therefore if `vehicleType` is not specified, may not receive accurate tolls. For example, tolls are generally higher for trucks than cars. If `vehicleType` is not specified, by default tolls are returned for 2-axle cars. 
* Similarly, `departure_time` is important for locations where tolls change based on time-of-the-day.

the last line can be changed to following

```javascript

const tollguruUrl = 'https://dev.tollguru.com/v1/calc/route'

const handleRoute = (e, r, body) =>  {
  console.log(body)
  const _polyline = getPolyline(body);
  console.log(_polyline);
  request.post(
    {
      url: tollguruUrl,
      headers: {
        'content-type': 'application/json',
        'x-api-key': tollguruKey
      },
      body: JSON.stringify({
        source: "bing",
        polyline: _polyline,
        vehicleType: "2AxlesAuto",
        departure_time: "2021-01-05T09:46:08Z"
      })
    },
    (e, r, body) => {
      console.log(e);
      console.log(body)
    }
  )
};

getRoute(handleRoute);
```

The working code can be found in index.js file.

## License
ISC License (ISC). Copyright 2020 &copy;TollGuru. https://tollguru.com/

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
