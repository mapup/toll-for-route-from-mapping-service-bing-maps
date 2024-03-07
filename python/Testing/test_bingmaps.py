import requests
import polyline as poly
import os

BING_API_KEY = os.environ.get("BING_API_KEY")
BING_API_URL = "http://dev.virtualearth.net/REST/v1/Routes"

TOLLGURU_API_KEY = os.environ.get("TOLLGURU_API_KEY")
TOLLGURU_API_URL = "https://apis.tollguru.com/toll/v2"
POLYLINE_ENDPOINT = "complete-polyline-from-mapping-service"

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
        "source": "bing",
        "polyline": polyline,  # this is the encoded polyline that we made
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


"""Testing"""
# Importing Functions
from csv import reader, writer
import time

temp_list = []
with open("testCases.csv", "r") as f:
    csv_reader = reader(f)
    for count, i in enumerate(csv_reader):
        # if count>2:
        #   break
        if count == 0:
            i.extend(
                (
                    "Input_polyline",
                    "Tollguru_Tag_Cost",
                    "Tollguru_Cash_Cost",
                    "Tollguru_QueryTime_In_Sec",
                )
            )
        else:
            try:
                polyline = get_polyline_from_bing_maps(i[1], i[2])
                i.append(polyline)
            except:
                i.append("Routing Error")

            start = time.time()
            try:
                rates = get_rates_from_tollguru(polyline)
            except:
                i.append(False)
            time_taken = time.time() - start
            if rates == {}:
                i.append((None, None))
            else:
                try:
                    tag = rates["tag"]
                except:
                    tag = None
                try:
                    cash = rates["cash"]
                except:
                    cash = None
                i.extend((tag, cash))
            i.append(time_taken)
        # print(f"{len(i)}   {i}\n")
        temp_list.append(i)

with open("testCases_result.csv", "w") as f:
    writer(f).writerows(temp_list)

"""Testing Ends"""
