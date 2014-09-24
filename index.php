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

    // Counting the potential alerts to be able to notify our lovely followers in case everything's up and running again ;)
    $count = 0;

    // This is where we store stuff that has already been tweeted, do avoid repeating messages
    // Yes... it's a flatfile, no database needed
    $knownTextsFile = 'knownProblems.txt';

    $statusFile = 'allOk.txt';

    // Checking if a string hasn't been tweeted yet, if it's been tweeted before the function will retuern true
    function notYetPublished( $text ) {

        return ! exec( 'grep '.escapeshellarg( $text ). ' ./' . $GLOBALS[ 'knownTextsFile' ] );

    }

    // function to store the current state of all the tracks, if everything is alright then there should be a one in this file
    // This is to be able to know if we have already told the world that everything is alright
    function setStatus( $newState ) {

        file_put_contents(
            $GLOBALS[ 'statusFile' ],
            (string) $newState
        );

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

        if ( ! strpos( strtolower( $title ), 'work:' ) ) {

            $message = substr( $lines[ 2 ], 0, 140);

            if( notYetPublished( $dateTime . ' ' . $message ) ) {

                /** POST fields required by the URL above. See relevant docs as above **/
                $postfields = array(
                        'status' => $message
                    );

                echo $twitter->buildOauth( $url, $requestMethod )
                             ->setPostfields( $postfields )
                             ->performRequest();

                file_put_contents(
                    $knownTextsFile,
                    $lines[ 1 ] . ' ' . $message.PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );

                setStatus( 0 );

            }

            $count++;

        }

    }

    if ( $count == 0 && ( file_get_contents( $statusFile ) == 0 ) ) {

        $everythingOkAgain = array(

            'A good service has been resumed on all lines at ' . date( "G:i" ) . '. If we are missing something, just give us a heads up',
            date( "G:i" ) . ': Hurray, a good service is operating on all the lines :D Send us updates if we missed something',
            'Just letting you know that everything has been up and running from ' . date( "G:i" ) . ' on ;) We <3 updates, send us yours. Have a good day',
            'You might like to hear that all the issues have been solved at ' . date( "G:i" ) . '. Help us being more accurate by sending us updates',
            'As far as we know, from ' . date( "G:i" ) . ' on, trains should get you to your desired destination on time. Tell us if that\'s not the case',
            'Once apon a time all trains were operating at full speed... OMG! thats\'s what you should be experienceinging from ' . date( "G:i" ) . ' on!',
            'Customer service update at ' . date( "G:i" ) . ': All trains should arrive on time =] oh and btw: if you like this service, please tell your friends ;)'

        );

        $message = $everythingOkAgain[array_rand($everythingOkAgain)];

        /** POST fields required by the URL above. See relevant docs as above **/
        $postfields = array(
                'status' => $message
            );

        echo $twitter->buildOauth( $url, $requestMethod )
                     ->setPostfields( $postfields )
                     ->performRequest();

        setStatus( 1 );

    }
