import requests
import polyline as poly
import os

BING_API_KEY = os.environ.get("BING_API_KEY")
BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

TOLLGURU_API_KEY = os.environ.get("TOLLGURU_API_KEY")
TOLLGURU_API_URL = "https://apis.tollguru.com/toll/v2"
POLYLINE_ENDPOINT = "complete-polyline-from-mapping-service"

source = "Philadelphia, PA"
destination = "New York, NY"

# Explore https://tollguru.com/toll-api-docs to get best of all the parameter that tollguru has to offer
request_parameters = {
    "vehicle": {
        "type": "2AxlesAuto",
    },
    # Visit https://en.wikipedia.org/wiki/Unix_time to know the time format
    "departure_time": "2021-01-05T09:46:08Z",
}


def get_polyline_from_bing_maps(source, destination):
    """Fetching Polyline from bingmaps"""

    # Query bing with Key and Source-Destination coordinates
    url = f"{BING_API_URL}?key={BING_API_KEY}&wayPoint.1={source}&wayPoint.2=${destination}&routeAttributes=routePath"
    # converting the response to json
    response = requests.get(url).json()
    # bingmap's does not give polyline directly rather provide coordinates of all the nodes
    temp = response["resourceSets"][0]["resources"][0]["routePath"]["line"][
        "coordinates"
    ]
    # We will encode these coordinates using encode function from polyline module to generate polyline
    polyline_from_bing = poly.encode(temp)
    return polyline_from_bing


def get_rates_from_tollguru(polyline):
    """Calling Tollguru API"""

    # Tollguru resquest parameters
    headers = {"Content-type": "application/json", "x-api-key": TOLLGURU_API_KEY}
    params = {
        **request_parameters,
        "polyline": polyline,  # this is the encoded polyline that we made
        "source": "bing",
    }
    # Requesting Tollguru with parameters
    response_tollguru = requests.post(
        f"{TOLLGURU_API_URL}/{POLYLINE_ENDPOINT}",
        json=params,
        headers=headers,
        timeout=200,
    ).json()
    # print(response_tollguru)
    # checking for errors or printing rates
    if str(response_tollguru).find("message") == -1:
        return response_tollguru["route"]["costs"]
    else:
        raise Exception(response_tollguru["message"])


if __name__ == "__main__":
    # Step 1 : Get Polyline from Bing
    polyline_from_bing = get_polyline_from_bing_maps(source, destination)

    # Step 2 : Get rates from Tollguru
    rates_from_tollguru = get_rates_from_tollguru(polyline_from_bing)

    # Step 3 : Print the rates of all the available modes of payment
    if rates_from_tollguru == {}:
        print("The route doesn't have tolls")
    else:
        print(f"The rates are \n {rates_from_tollguru}")
