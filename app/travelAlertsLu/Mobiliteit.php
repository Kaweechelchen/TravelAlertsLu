<?php

namespace travelAlertsLu;

class Mobiliteit
{
    public static function getDepartures($app, $lat, $long, $stations, $departures)
    {
        $closest_stations = json_decode(
            file_get_contents("https://busproxy.herokuapp.com/around/$lat/$long"),
            true
        );

        for ($i = 0; $i < $stations; ++$i) {
            $station = $closest_stations[ 'features' ][ $i ];

            $id   = $station[ 'properties' ][ 'mobiliteitid' ];
            $name = $station[ 'properties' ][ 'text' ];

            $station_live = json_decode(
                file_get_contents("https://mob-262682527644900632.herokuapp.com/api/1/$id/$departures"),
                true
            );

            $journeys_live = $station_live[ 'journeys' ];

            foreach ($journeys_live as $key => $journey_live) {
                if (preg_match('/<span>(.*)<(\/)?span>/i', $journey_live[ 'destination' ], $destinationMatches)) {
                    $journey[ 'destination' ] = $destinationMatches[ 1 ];
                } else {
                    $journey[ 'ddestination' ] = $journey_live[ 'destination' ];
                }

                $journey[ 'departure'      ] = $journey_live[ 'departure' ];
                $journey[ 'departure_mins' ] = $journey_live[ 'departure' ] - date('U');
                $journey[ 'delay'          ] = $journey_live[ 'delay' ];

                $result[ $name ][] = $journey;
            }
        }

        return $result;
    }
}
