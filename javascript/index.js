const request = require("request");
const polyline = require("polyline");

const BING_API_KEY = process.env.BING_API_KEY
const BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

const TOLLGURU_API_KEY = process.env.TOLLGURU_API_KEY
const TOLLGURU_API_URL = "https://apis.tollguru.com/toll/v2"
const POLYLINE_ENDPOINT = "complete-polyline-from-mapping-service"

const source = 'Philadelphia, PA'
const destination = 'New York, NY';

// Explore https://tollguru.com/toll-api-docs to get the best of all the parameters that tollguru has to offer
const requestParameters = {
  "vehicle": {
    "type": "2AxlesAuto",
  },
  // Visit https://en.wikipedia.org/wiki/Unix_time to know the time format
  "departure_time": "2021-01-05T09:46:08Z",
}

const url = `${BING_API_URL}?key=${BING_API_KEY}&${new URLSearchParams({
  'wayPoint.1': source,
  'wayPoint.2': destination,
  routeAttributes: 'routePath'
}).toString()}`

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
      body: JSON.stringify({
        source: "bing",
        polyline: _polyline,
        ...requestParameters,
      })
    },
    (e, r, body) => {
      console.log(e);
      console.log(body)
    }
  )
};

getRoute(handleRoute);
