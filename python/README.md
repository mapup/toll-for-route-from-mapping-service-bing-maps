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
* REQUEST should include 'routeAttributes' as 'routePath'. Setting 'routeAttributes' as 'routePath' gives us a series of coordinates describing the whole path, which we later convert to encoded polyline.

'''python

'''Fetching Polyline from bingmaps'''

# Token from Bing Maps and set this in the environment setting 

key = os.environ.get('BINGMAPS')
source = '251 West, IN-120, Fremont, IN 46737'
destination = '2323 Willowcreek Rd, Portage, IN 46368'

#Query Bing Maps with Key and Source-Destination coordinates
url = 'http://dev.virtualearth.net/REST/v1/Routes?key={a}&wayPoint.1={b}&wayPoint.2=${c}&routeAttributes=routePath'.format(a=key,b=source,c=destination)


#converting the response to json
response=requests.get(url).json()

#bingmap's does not give polyline directly rather provide coordinates of all the nodes  
temp=response['resourceSets'][0]['resources'][0]['routePath']['line']['coordinates']
#We will encode these coordinates using encode function from polyline module to generate polyline
polyline = Poly.encode(temp)
   

```

Note:

We extracted the polyline for a route from Bing Maps API

We need to send this route polyline to TollGuru API to receive toll information

## [TollGuru API](https://tollguru.com/developers/docs/)

### Get key to access TollGuru polyline API
* create a dev account to receive a [free key from TollGuru](https://tollguru.com/developers/get-api-key)
* suggest adding 'vehicleType' parameter. Tolls for cars are different than trucks and therefore if 'vehicleType' is not specified, may not receive accurate tolls. For example, tolls are generally higher for trucks than cars. If 'vehicleType' is not specified, by default tolls are returned for 2-axle cars. 
* Similarly, 'departure_time' is important for locations where tolls change based on time-of-the-day.

the last line can be changed to following

'''Python

'''Calling Tollguru API'''

#API key for Tollguru and set this in the environment setting 
Tolls_Key= os.environ.get('TOLLGURU')

#Tollguru querry url
Tolls_URL = 'https://dev.tollguru.com/v1/calc/route'

#Tollguru resquest parameters
headers = {
            'Content-type': 'application/json',
            'x-api-key': Tolls_Key
          }
params = {
            'source': "bingmaps",
            'polyline': polyline ,                      #  this is polyline that we fetched from the mapping service     
            'vehicleType': '2AxlesAuto',               
            'departure_time' : "2021-01-05T09:46:08Z"   
        }

#Requesting Tollguru with parameters
response_tollguru= requests.post(Tolls_URL, json=params, headers=headers).json()

#checking for errors or printing rates
if str(response_tollguru).find('message')==-1:
    print('\n The Rates Are ')
    #extracting rates from Tollguru response is no error
    #print(*response_tollguru['summary']['rates'].items(),end="\n\n")
    print(*response_tollguru['route']['costs'].items(),end="\n\n")
else:
    raise Exception(response_tollguru['message'])

The working code can be found in index.py file.

## License
ISC License (ISC). Copyright 2020 &copy;TollGuru. https://tollguru.com/

Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
