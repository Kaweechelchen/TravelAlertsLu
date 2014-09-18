<?php

    require_once('TwitterAPIExchange.php');

    $twitterSettings = array(
        'consumer_key'              => getenv('apikey'),
        'consumer_secret'           => getenv('apisecret'),
        'oauth_access_token'        => getenv('accesstoken'),
        'oauth_access_token_secret' => getenv('accesstokensecret')
    );

    /** URL for REST request, see: https://dev.twitter.com/docs/api/1.1/ **/
    $url = 'https://api.twitter.com/1.1/statuses/update.json';
    $requestMethod = 'POST';

    /** Perform a POST request and echo the response **/
    $twitter = new TwitterAPIExchange( $twitterSettings );

    $xml = simplexml_load_file( 'http://mobile.cfl.lu/bin/help.exe/enl?tpl=rss_feed_global' );

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

            $message = substr( $lines[ 2 ], 0, 140);

            if( ! exec('grep '.escapeshellarg( $lines[ 1 ] . ' ' . $message ). ' ./'.$file)) {

                /** POST fields required by the URL above. See relevant docs as above **/
                $postfields = array(
                        'status' => $message
                    );

                echo $twitter->buildOauth( $url, $requestMethod )
                             ->setPostfields( $postfields )
                             ->performRequest();

                file_put_contents(
                    $file,
                    $lines[ 1 ] . ' ' . $message.PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );

            }

            file_put_contents( 'allOk.txt', '0' );

            $count++;

        }

    }

    if ( $count == 0 && ( file_get_contents('allOk.txt') == 0 ) ) {

        $message = 'A good service has now resumed on all lines. If we are missing something, please give us a heads up';

        /** POST fields required by the URL above. See relevant docs as above **/
        $postfields = array(
                'status' => $message
            );

        echo $twitter->buildOauth( $url, $requestMethod )
                     ->setPostfields( $postfields )
                     ->performRequest();

        file_put_contents( 'allOk.txt', '1' );

    }
