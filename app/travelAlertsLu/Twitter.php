<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class Twitter {

    static public function generateIssueTweets( $app, $issue ) {

      $issue = $issue[ 'description' ];

      $issue  = self::delayReadable       ( $issue );
      $issue  = self::departure           ( $issue );
      $issue  = self::tagTrain            ( $issue );
      $issue  = self::tagIssue            ( $issue );
      $issue  = self::includeTravelAlerts ( $issue );

      $tweets = self::splitToTweets       ( $issue );

      echo '<pre>';

      print_r( $tweets );

      exit;

    }

    static public function tagTrain ( $issue ){

      return preg_replace_callback(
        "([A-Z]{2,3} \d{4})",
        function ( $matches ) {
          $train = str_replace( ' ', '', $matches[ 0 ] );
          return '#' . $train;
        },
        $issue
      );

    }

    static public function tagIssue( $issue ) {

      return preg_replace_callback(
        "/([\w]+)/u",
        "self::tagWord",
        $issue
      );

    }

    static public function tagWord( $word ) {

      $word = $word[ 0 ];

      if ( in_array( strtolower( $word ), $GLOBALS[ 'app' ][ 'wordsToTag' ] ) ) {

        return '#' . $word;

      } else {

        return $word;

      }

    }

    static public function includeTravelAlerts( $issue ) {

      if ( strpos( $issue, ' TER'       ) !== false
        || strpos( $issue, 'Metz'       ) !== false
        || strpos( $issue, 'Thionville' ) !== false
        || strpos( $issue, 'Longwy'     ) !== false ){

        $issue .= "\n/cc @TER_Metz_Lux";

      }

      return $issue;

    }

    static public function departure( $issue ) {

      $departure_pattern = '/((,)( )((scheduled )departure ((\d{1,2})(:|.)(\d{2}))))/s';

      if ( preg_match( $departure_pattern, $issue, $departureMatches ) ){

        $issue = str_replace( $departureMatches[1], ' dep.' . $departureMatches[7] . ':' . $departureMatches[9], $issue);

      }

      return $issue;

    }

    static public function splitToTweets( $issue ) {

      $issue = wordwrap( $issue, 134, 'NEW_TWEET');

      $tweets = explode( 'NEW_TWEET', $issue );

      foreach ( $tweets as $key => $tweet ) {

        if ( sizeof( $tweets ) > 1 ) {

          $tweets[ $key ] = $tweet . ' (' . ( $key + 1 ) . '/' . sizeof( $tweets ) . ')' ;

        }

      }

      return $tweets;

    }

    static public function delayReadable ( $issue ) {

      $delay_pattern = '/((,)?( )?((has )?a(n expected)? delay of (.*?) (minutes)?.*))/s';

      if ( preg_match( $delay_pattern, $issue, $delayMatches ) ){

        $issue = str_replace( $delayMatches[1], "\nDelay:" . $delayMatches[7], $issue);
        if ( strtolower( $delayMatches[8] ) == 'minutes' ) {
          $issue .= 'm';
        }

      }

      return $issue;

    }

  }
