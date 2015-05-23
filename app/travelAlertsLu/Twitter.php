<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class Twitter {

    static public function generateIssueTweets( $app, $issue ) {

      $issue = $issue[ 'description' ];

      $issue  = self::removeSpaces        ( $issue );
      $issue  = self::delayReadable       ( $issue );
      $issue  = self::dueToReadable       ( $issue );
      var_dump( $issue );
      $issue  = self::shortenDate         ( $issue );
      $issue  = self::shortenTime         ( $issue );
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
        "/((the|train|the train)?( ))?([A-Z]{2,3}( )?\d{4,})/i",
        function ( $matches ) {
          $train = str_replace( ' ', '', $matches[ 4 ] );
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

      $departure_pattern = '/(scheduled )?(dep(?:arture)?|arr(?:ival)?)( )?(from |at )?([\p{L}\s]+)?( at )?((\d{1,2})(\.|:|h)(\d{2})(am|pm)?)/i';

      if ( preg_match_all( $departure_pattern, $issue, $departureMatches, PREG_SET_ORDER ) ){

        foreach ( $departureMatches as $departureMatch) {

          $state = ucfirst( substr( $departureMatch[2], 0, 3 ) ) . '.';

          $station = ucfirst( $departureMatch[5] );

          $time = $departureMatch[7];

          $issue = str_replace( $departureMatch[0], $state . ''. $station . ' ' . $time, $issue);

        }

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

      $delay_pattern = '/((,)?( )?((has )?a(n expected)? delay of (.*?) (minutes)?.*))/i';

      if ( preg_match( $delay_pattern, $issue, $delayMatches ) ){

        $issue = str_replace( $delayMatches[1], "\nDelay:" . $delayMatches[7], $issue);
        if ( strtolower( $delayMatches[8] ) == 'minutes' ) {
          $issue .= 'm';
        }

      }

      return $issue;

    }

    static public function dueToReadable ( $issue ) {

      $dueTo_pattern = '/(((Due to )([\w\s]+)),)/i';

      if ( preg_match( $dueTo_pattern, $issue, $dueToMatches ) ){

        $issue = str_replace( $dueToMatches[1], $dueToMatches[4] . ':', $issue);

      }

      return $issue;

    }

    static public function shortenDate ( $issue ) {

      $date_pattern = '/(((?:Mon(?:day)?|Tue(?:sday)?|Wed(?:nesday)?|Thu(?:rsday)?|Fri(?:day)?|Sat(?:urday)?|Sun(?:day)?|Sun(?:day)?)(?!\p{L}))?(,)?( )?(\d{1,2})?(st|nd|rd)?( )((?:Jan(?:uary)?|Feb(?:ruary)?|Mar(?:ch)?|Apr(?:il)?|May?|Jun(?:e)?|Jul(?:y)?|Aug(?:ust)?|Sep(?:tember)?|Oct(?:ober)?|Nov(?:ember)?|Dec(?:ember)?)(?!\p{L}))( )?(\d{2,4})?)/i';

      if ( preg_match_all( $date_pattern, $issue, $dateMatches, PREG_SET_ORDER ) ){

        foreach ( $dateMatches as $dateMatch) {

          // initialize date variables
          $dayOfWeek = $dayOfMonth = $monthName = '';

          // Day of week
          if ( array_key_exists( 2, $dateMatch ) ) {
            $dayOfWeek = ucfirst( substr( $dateMatch[2], 0, 3 ) );
          }

          // Space between day of week and day of month
          if ( array_key_exists( 4, $dateMatch ) ) {
            $dayOfWeek .= ' ';
          }

          // Day of month
          if ( array_key_exists( 5, $dateMatch ) ) {
            if ( $dateMatch[5] == '' && array_key_exists( 10, $dateMatch ) ) {
              $dayOfMonth .= $dateMatch[10];
            }
            $dayOfMonth .= $dateMatch[5];
          }

          // Space between day of month and month name
          if ( array_key_exists( 7, $dateMatch ) ) {
            if ( array_key_exists( 8, $dateMatch ) ) {
              $dayOfMonth .= '.';
            } else {
              $dayOfMonth .= ' ';
            }
          }

          // Month
          if ( array_key_exists( 8, $dateMatch ) ) {
            $monthName = ucfirst( substr( $dateMatch[8], 0, 3 ) );
          }

          if ( $dateMatch[5] != '' ) {

            if ( array_key_exists( 9, $dateMatch ) ) {
              if ( array_key_exists( 10, $dateMatch ) ) {
                $monthName .= '\'';
              } else {
                $monthName .= ' ';
              }
            }

            // year
            if ( array_key_exists( 10, $dateMatch ) ) {
              $year = ucfirst( substr( $dateMatch[10], -2 ) );
            } else {
              $year = '';
            }
          }

          $issue = str_replace(
            $dateMatch[1],
            $dayOfWeek . $dayOfMonth . $monthName . $year,
            $issue
          );

        }

      }

      return $issue;

    }

    static public function removeSpaces ( $issue ) {

      $punctuation_pattern = '(\. |, |! |\? )';

      if ( preg_match_all( $punctuation_pattern, $issue, $punctuationMatches, PREG_SET_ORDER ) ){

        foreach ( $punctuationMatches as $punctuationMatch) {

          $issue = str_replace(
            $punctuationMatch[0],
            substr( $punctuationMatch[0], 0, 1 ),
            $issue
          );

        }

      }

      // remove . if there is one at the end
      $issue = trim( $issue, '.' );

      return $issue;

    }

    static public function shortenTime ( $issue ) {

      $time_pattern = '/((\()?(\d{1,2})((\.|:|h)(\d{2}))?(am|pm)((\)))?)/';

      if ( preg_match_all( $time_pattern, $issue, $timeMatches, PREG_SET_ORDER ) ){

        foreach ( $timeMatches as $timeMatch) {

          if ( strtolower( $timeMatch[7] ) == 'pm' ) {
            $hour = $timeMatch[3] + 12;
          } else {
            $hour = $timeMatch[3];
          }

          $hour     = $hour . ':';
          $minutes  = '00';

          if ( array_key_exists( 6, $timeMatch ) ) {
            if ( $timeMatch[6] != '' ) {
              $minutes = $timeMatch[6];
            }
          }

          $issue = str_replace(
            $timeMatch[0],
            $hour . $minutes,
            $issue
          );

        }

      }

      return $issue;

    }

  }
