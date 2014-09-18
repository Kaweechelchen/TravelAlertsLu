<?php

    $xml = simplexml_load_file( 'https://getcontents.herokuapp.com/?url=http%3A%2F%2Fmobile.cfl.lu%2Fbin%2Fhelp.exe%2Fenl%3Ftpl%3Drss_feed_global' );

    $data   = json_decode(json_encode( $xml ), true);

    $count = 0;

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

        $file = 'knownProblems.txt';

        if ( ! strpos( strtolower( $title ), 'work:' ) ) {

            $message = substr( $lines[ 2 ], 0, 135) . ' #cfl';

            if( ! exec('grep '.escapeshellarg( $lines[ 1 ] . ' ' . $message ). ' ./'.$file)) {

                echo $message . PHP_EOL;
                file_put_contents(
                    $file,
                    $lines[ 1 ] . ' ' . $message.PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );

            }

            $count++;

        }

    }

    if ( $count == 0 ) {
        echo "A good service has now resumed on all the linees. If not, please give us a heads up #cfl";
    }
