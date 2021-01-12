#Importing modules
import json
import requests
import polyline as Poly
import os


'''Fetching Polyline from bingmaps'''

# Token from Bing Maps

key = os.environ.get('bingMaps')
source = 'Dallas, TX'
destination = 'New York, NY'

#Query MapmyIndia with Key and Source-Destination coordinates
url = 'http://dev.virtualearth.net/REST/v1/Routes?key={a}&wayPoint.1={b}&wayPoint.2=${c}&routeAttributes=routePath'.format(a=key,b=source,c=destination)


#converting the response to json
response=requests.get(url).json()

#bingmap's does not give polyline directly rather provide coordinates of all the nodes  
temp=response['resourceSets'][0]['resources'][0]['routePath']['line']['coordinates']
#We will encode these coordinates using encode function from polyline module to generate polyline
polyline = Poly.encode(temp)

#checking for errors in response      #'''TODO check for errors in bingmap response


'''Calling Tollguru API'''

#API key for Tollguru
Tolls_Key= os.environ.get('tollguru')

#Tollguru querry url
Tolls_URL = 'https://dev.tollguru.com/v1/calc/route'

#Tollguru resquest parameters
headers = {
            'Content-type': 'application/json',
            'x-api-key': Tolls_Key
          }
params = {
            'source': "mapmyindia",
            'polyline': polyline ,                      #  this is polyline that we fetched from the mapping service     
            'vehicleType': '2AxlesAuto',                #'''TODO - Need to provide users a slist of acceptable values for vehicle type'''
            'departure_time' : "2021-01-05T09:46:08Z"   #'''TODO - Specify time formats'''
        }

#Requesting Tollguru with parameters
response_tollguru= requests.post(Tolls_URL, json=params, headers=headers).json()

#checking for errors or printing rates
if str(response_tollguru).find('message')==-1:
    print('\n The Rates Are ')
    #extracting rates from Tollguru response is no error
    print(*response_tollguru['summary']['rates'].items(),end="\n\n")
else:
    raise Exception(response_tollguru['message'])

