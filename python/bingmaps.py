#Importing modules
import json
import requests
import polyline as Poly
import os


'''Fetching Polyline from bingmaps'''

# Token from Bing Maps

key = os.environ.get('AmAD8q8fIDP_sqNpKEMnx60AT3Gfy9O3L1jFkVIVbX4t2kn1aieFU3zd8nq6e-4t')
source = 'Dallas, TX'
destination = 'New York, NY'

#Query MapmyIndia with Key and Source-Destination coordinates
url = 'http://dev.virtualearth.net/REST/v1/Routes?key={a}&wayPoint.1={b}&wayPoint.2=${c}&routeAttributes=routePath'.format(a=key,b=source,c=destination)


#converting the response to json
response=requests.get(url).json()

temp=response['resourceSets'][0]['resources'][0]['routePath']['line']['coordinates']
polyline = Poly.encode(temp)

#checking for errors in response 
'''
if str(response).find('message')>-1:
    raise Exception("{}: {} , check latitude,longitude perhaps".format(response['code'],response['message']))
elif str(response).find('responsecode')>-1 and response['responsecode']=='401':
    raise Exception("{} {}".format(response['error_code'],response['error_description']))
else:
    #The response is a dict where Polyline is inside first element named "routes" , first element is a list , go to 1st element there
    #you will find a key named "geometry" which is essentially the Polyline 
    
    #Extracting polyline
    #polyline=response["routes"][0]['geometry'] 

'''





'''Calling Tollguru API'''

#API key for Tollguru
Tolls_Key= os.environ.get('J9L4QH37NQ7jqRQPND9fJPDHgJd8mptg')

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

