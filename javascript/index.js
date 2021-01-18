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

//const handleRoute  = (cb) => (e, r, body) => console.log(getPolyline(body));
//getRoute(handleRoute);

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
      body: JSON.stringify({ source: "bing", polyline: _polyline })
    },
    (e, r, body) => {
      console.log(e);
      console.log(body)
    }
  )
};

getRoute(handleRoute);
