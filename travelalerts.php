<?php

    $xml    = simplexml_load_file( 'https://getcontents.herokuapp.com/?url=http%3A%2F%2Fmobile.cfl.lu%2Fbin%2Fhelp.exe%2Fenl%3Ftpl%3Drss_feed_global' );
    $data   = json_decode(json_encode( $xml ), true);

    foreach( $data[ 'channel' ][ 'item' ] as $travelAlert ) {

        $description = str_replace(
            '<br>',
            '',
            $travelAlert[ 'description' ]
        );

        $lines = explode(
            "\n",
            $description
        );

        $stringsToRemove = array(
            'Due to railway-works, ',
            'Due to railway works on line , ',
            'Due to railway works on ',

        );

        // removing strings from the array just before
        $information = str_replace(
            $stringsToRemove,
            '',
            $lines[ 2 ]
        );

        $dateTime = $lines[ 1 ];

        date_default_timezone_set( "Europe/London" );

        $utsStart = strtotime( substr( $dateTime, 0, 15 ) );

        switch ( strlen( $lines[ 1 ] ) ) {

            case 24:    $utsEnd = strtotime( substr( $dateTime, 0, 10 ) . ' ' . substr( $dateTime, 19, 5 ) );
                        $date = date( "j.n G\h", $utsStart ) . '-' . date( "G\h", $utsEnd );
                        break;

            case 29:    $utsEnd = strtotime( substr( $dateTime, 19, 10 ) );
                        $date = date( "j.n", $utsStart ) . '-' . date( "j.n", $utsEnd );
                        break;

            case 35:    $utsEnd = strtotime( substr( $dateTime, 19, 10 ) . ' ' . substr( $dateTime, 30, 5 ) );
                        $date = date( "j.n G\h", $utsStart ) . '-' . date( "j.n G\h", $utsEnd );
                        break;

            default:    echo "no";
                        break;

        }

        $tweet =substr(
            $date . ' ' . $information,
            0,
            135
        ) . ' #cfl';
        echo $tweet.PHP_EOL;

    }