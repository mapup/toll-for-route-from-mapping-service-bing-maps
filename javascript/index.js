const request = require("request");
const polyline = require("polyline");

const BING_API_KEY = process.env.BING_MAPS_API_KEY // Token from Bing Maps
const BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

const TOLLGURU_API_KEY = process.env.TOLLGURU_API_KEY // API key for Tollguru
const TOLLGURU_API_URL = "https://apis.tollguru.com/toll/v2" // Base URL for TollGuru Toll API
const POLYLINE_ENDPOINT = "complete-polyline-from-mapping-service"

const source = 'Dallas, TX'
const destination = 'New York, NY';

const url = `${BING_API_URL}?key=${BING_API_KEY}&wayPoint.1=${source}&wayPoint.2=${destination}&routeAttributes=routePath`

const flatten = (arr, x) => arr.concat(x);

// JSON path "$..coordinates"
const getRoutePath = body => body.resourceSets
  .map(x => x.resources)
  .reduce(flatten)
  .map(x => x.routePath)
  .map(x => x.line.coordinates)
  .reduce(flatten)

const getPolyline = (body) => polyline.encode(getRoutePath(JSON.parse(body)));

const getRoute = (cb) => request(url, cb);

const handleRoute = (e, r, body) => {
  console.log(body)
  const _polyline = getPolyline(body);
  console.log(_polyline);
  request.post(
    {
      url: `${TOLLGURU_API_URL}/${POLYLINE_ENDPOINT}`,
      headers: {
        'content-type': 'application/json',
        'x-api-key': TOLLGURU_API_KEY
      },
      body: JSON.stringify({ source: "bing", polyline: _polyline })
    },
    (e, r, body) => {
      console.log(e);
      console.log(body)
    }
  )
};

getRoute(handleRoute);
