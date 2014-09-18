<?php

    $xml    = simplexml_load_file( 'https://getcontents.herokuapp.com/?url=http%3A%2F%2Fmobile.cfl.lu%2Fbin%2Fhelp.exe%2Fenl%3Ftpl%3Drss_feed_global' );
    $data   = json_decode(json_encode( $xml ), true);

    foreach( $data[ 'channel' ][ 'item' ] as $travelAlert ) {

        $description = str_replace(
            '<br>',
            '',
            $travelAlert[ 'description' ]
        );
        $title = $travelAlert[ 'title' ];

        $lines = explode(
            "\n",
            $description
        );

        if ( ! strpos( strtolower( $title ), 'work:' ) ) {

            echo substr( $lines[ 2 ], 0, 135) . ' #cfl' . PHP_EOL;

        }

    }