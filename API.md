# API endpoints

## Issues on the tracks
If you only want to use the issues that have not been resolved yet use:

- **[<code>GET</code> api/current](http://travelalerts.lu/api/current/)**

To get a list of ALL the issues that have happened since we started storing them in a database use:

- **[<code>GET</code> api/issues](http://travelalerts.lu/api/issues/)**

If you're only interested in the issues that are happening on a single line, use:

- **[<code>GET</code> api/issues/line/:LINE](http://travelalerts.lu/api/issues/line/60/)**

## Tweets

The main idea was to tweet all the issues, and we're still doing so on [@TravelAlertsLu](https://twitter.com/TravelAlertsLu)
If for whatever reason you want to have access to all these tweets, we're storing them in a database too, including the tweet id.

- **[<code>GET</code> api/tweets](http://travelalerts.lu/api/tweets/)**

## Departures of trains/busses around a GPS position

If you want to know the upcoming departures of trains/busses around a GPS position, use:

- **[<code>GET</code> api/departures/:LAT/:LONG/:STATIONS/:DEPARTURES](http://travelalerts.lu/api/departures/49.61/6.12/1/5)**

Where <code>:STATIONS</code> is the amount of stations you want the API to return

and <code>:DEPARTURES</code> is the amount of departures/station you want the API to return
