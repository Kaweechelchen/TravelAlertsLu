<?php

    require_once('TwitterAPIExchange.php');

    date_default_timezone_set( 'Europe/Luxembourg' );

    // Getting the keys to access the Twitter API
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

    // Load the data from CFL
    $data = simplexml_load_file( 'http://mobile.cfl.lu/bin/help.exe/enl?tpl=rss_feed_global' );

    // getting rid of the awful "SimpleObject" stuff in the XML
    $data   = json_decode(
        json_encode( $data ),
        true
    );

    // This is where we store stuff that has already been tweeted, do avoid repeating messages
    // Yes... it's a flatfile, no database needed
    $knownTextsFile = 'knownProblems.txt';

    // Checking if a string hasn't been tweeted yet, if it's been tweeted before the function will retuern true
    function notYetPublished( $text ) {

        return ! exec( 'grep '.escapeshellarg( $text ). ' ./' . $GLOBALS[ 'knownTextsFile' ] );

    }

    // looping through all the messages in the XML
    foreach( $data[ 'channel' ][ 'item' ] as $travelAlert ) {

        // Getting rid of stupid html tags
        $description = str_replace(
            '<br>',
            '',
            $travelAlert[ 'description' ]
        );

        // putting the title of a notification in a variable
        $title = $travelAlert[ 'title' ];

        // Separating the different lines from each other
        $lines = explode(
            "\n",
            $description
        );

        // storing the timestamp in a variable
        $dateTime = $lines[ 1 ];

        $wordsToHashTag = array (

            'cancelled', 'train', 'luxembourg', 'bus', 'strike', 'esch', 'delay', 'delays', 'esch/alzette', 'breakdown', 'longwy', 'metz', 'obstacle', 'technical', 'mersch', 'ettelbruck', 'bettembourg'

        );

        if ( ! strpos( strtolower( $title ), 'work:' ) ) {

            $words = explode( ' ', $lines[ 2 ] );
            $tweet = '';
            unset( $tweets );

            foreach ($words as $word) {

                if ( $word != '' ) {

                    if ( in_array( strtolower( $word ), $wordsToHashTag ) ) {

                        $word = '#'.$word;

                    }

                    if ( strlen( $tweet ) + strlen( $word ) > 133 ) {

                        $tweets[] = $tweet;
                        $tweet = '';

                    }

                    if ( strlen( $tweet ) != 0 ) {

                        $tweet .= ' ';

                    }

                    $tweet .= $word;

                }

            }

            $tweets[] = $tweet;

            if ( sizeof( $tweets) > 1 ) {

                foreach ($tweets as $key => $tweet) {

                    $tweets[ $key ] = $tweet . ' (' . ( $key + 1 ) . '/' . sizeof( $tweets ) . ')';

                }

            }

            if( notYetPublished( $dateTime . ' ' . $lines[ 2 ] ) ) {

                foreach ($tweets as $tweet) {

                    /** POST fields required by the URL above. See relevant docs as above **/
                    $postfields = array(
                        'status' => $tweet
                    );

                    echo $twitter->buildOauth( $url, $requestMethod )
                                 ->setPostfields( $postfields )
                                 ->performRequest();

                }

                file_put_contents(
                    $knownTextsFile,
                    $dateTime . ' ' . $lines[ 2 ].PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );

            }

        }

    }
