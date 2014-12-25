level2js
========

information API for Level2

##API

- **[<code>GET</code>](https://level2js.herokuapp.com/json)**

### Wordpress posts
- **[<code>GET</code> wp](https://level2js.herokuapp.com/json/wp)**
- **[<code>GET</code> wp/:amount](https://level2js.herokuapp.com/json/wp/1)**

### Busses
- **[<code>GET</code> bus](https://level2js.herokuapp.com/json/bus)**
- **[<code>GET</code> bus/:amount](https://level2js.herokuapp.com/json/bus/1)**

### Events from the wiki
- **[<code>GET</code> eve](https://level2js.herokuapp.com/json/eve)**
- **[<code>GET</code> eve/:amount](https://level2js.herokuapp.com/json/eve/1)**

### Twitter statuses
- **[<code>GET</code> twi](https://level2js.herokuapp.com/json/twi)**
- **[<code>GET</code> twi/:amount](https://level2js.herokuapp.com/json/twi/1)**



## Example

    https://level2js.herokuapp.com/json

##Installation on your own heroku instance

1. Clone this repository using `git clone git@github.com:Kaweechelchen/level2js.git`
* Get an [Heroku account](https://id.heroku.com/signup)
* install the [heroku toolbelt](https://toolbelt.heroku.com/)
* create a new heoku app using `heroku create`
* Create a [new Twitter application](https://apps.twitter.com/app/new) and get API keys
* [Get an APPID](http://openweathermap.org/my) from OpenWeatherMap
* set the environment variables for your app
<pre>
    heroku config:set apikey=API_KEY_GOES_HERE
    heroku config:set apisecret=API_SECRET_GOES_HERE
    heroku config:set accesstoken=ACCESS_TOKEN_GOES_HERE
    heroku config:set accesstokensecret=ACCESS_TOKEN_SECRET_GOES_HERE
    heroku config:set weatherapikey=OpenWeatherMap_Key_GOES_HERE
</pre>
* push the code `git push heroku master`

## Twitter API handler

I got some code from [James Mallison](https://github.com/j7mbo) https://github.com/j7mbo/twitter-api-php

## License
Copyright (c) 2014 [Thierry Degeling](https://twitter.com/Kaweechelchen)
Licensed under the MIT license.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

