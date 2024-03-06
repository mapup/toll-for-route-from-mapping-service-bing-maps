# Importing modules
import requests
import polyline as poly
import os

BING_API_KEY = os.environ.get("BING_API_KEY") # Token from Bing Maps
BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

TOLLGURU_API_KEY = os.environ.get("TOLLGURU_API_KEY")  # API key for Tollguru
TOLLGURU_API_KEY = "https://apis.tollguru.com/toll/v2"  # Base URL for TollGuru Toll API
POLYLINE_ENDPOINT = "complete-polyline-from-mapping-service"


# Fetching Polyline from bingmaps
def get_polyline_from_bing_maps(source, destination):
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


# Calling Tollguru API
def get_rates_from_tollguru(polyline):
    # Tollguru resquest parameters
    headers = {"Content-type": "application/json", "x-api-key": TOLLGURU_API_KEY}
    params = {
        # Explore https://tollguru.com/developers/docs/ to get best of all the parameter that tollguru has to offer
        "source": "bing",
        "polyline": polyline,  # this is the encoded polyline that we made
        "vehicleType": "2AxlesAuto",  #'''Visit https://tollguru.com/developers/docs/#vehicle-types to know more options'''
        "departure_time": "2021-01-05T09:46:08Z",  #'''Visit https://en.wikipedia.org/wiki/Unix_time to know the time format'''
    }
    # Requesting Tollguru with parameters
    response_tollguru = requests.post(
        f"{TOLLGURU_API_KEY}/{POLYLINE_ENDPOINT}",
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
    # Step 1 :Provide Source and Destination
    source = "Dallas, TX"
    destination = "New York, NY"

    # Step 2 : Get Polyline from Bing
    polyline_from_bing = get_polyline_from_bing_maps(source, destination)

    # Step 3 : Get rates from Tollguru
    rates_from_tollguru = get_rates_from_tollguru(polyline_from_bing)

    # Print the rates of all the available modes of payment
    if rates_from_tollguru == {}:
        print("The route doesn't have tolls")
    else:
        print(f"The rates are \n {rates_from_tollguru}")
