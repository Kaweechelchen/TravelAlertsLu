<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class Twitter {

    static public function generateIssueTweets( $app, $issue, $line ) {

      $issue = '#' . $line . "\n" . $issue[ 'title' ] . "\n" . $issue[ 'description' ];

      $issue  = self::removeSpaces        ( $issue );
      $issue  = self::delayReadable       ( $issue );
      $issue  = self::dueToReadable       ( $issue );
      $issue  = self::shortenDate         ( $issue );
      $issue  = self::shortenTime         ( $issue );
      $issue  = self::departure           ( $issue );
      $issue  = self::tagTrain            ( $issue );
      $issue  = self::tagIssue            ( $issue );
      $issue  = self::includeTravelAlerts ( $issue );

      $tweets = self::splitToTweets       ( $issue );

      print_r( $tweets );

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

        $issue .= "\n@TER_Metz_Lux";

      }

      return $issue;

    }

    static public function departure( $issue ) {

      $departure_pattern = '/(scheduled )?(departure|dep|arrival|arr)? ((from|at)? )?([\p{L}\s]+)? at ((\d{1,2})(\.|:|h)(\d{2})(am|pm)?)/i';

      $rx_state   = 2;
      $rx_station = 5;
      $rx_hour    = 7;
      $rx_minutes = 9;

      if ( preg_match_all( $departure_pattern, $issue, $departureMatches, PREG_SET_ORDER ) ){

        foreach ( $departureMatches as $departureMatch) {

          $state = ucfirst( substr( $departureMatch[ $rx_state ], 0, 3 ) ) . '.';

          $station = ucfirst( $departureMatch[ $rx_station ] );

          $time = $departureMatch[ $rx_hour ] . ':' . $departureMatch[ $rx_minutes ];

          $issue = str_replace( $departureMatch[0], $state . ''. $station . ' ' . $time, $issue);

        }

      }

      return $issue;

    }

    static public function splitToTweets( $issue ) {

      if ( strlen( $issue ) < 140 ) {

        $tweets[] = $issue;

      } else {

        $issue = wordwrap( $issue, 135, 'NEW_TWEET');

        $tweets = explode( 'NEW_TWEET', $issue );

        foreach ( $tweets as $key => $tweet ) {

          if ( sizeof( $tweets ) > 1 ) {

            $tweets[ $key ] = $tweet . '(' . ( $key + 1 ) . '/' . sizeof( $tweets ) . ')' ;

          }

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

      $date_pattern = '/(Monday|Mon|Tuesday|Tue|Wednesday|Wed|Thursday|Thu|Friday|Fri|Saturday|Sat|Sunday|Sun)?((\ |,\ |,)(\d{1,2})(st|nd|rd|th)?)? (January|Jan|February|Feb|March|Mar|April|Apr|May|June|Jun|July|Jul|August|Aug|September|Sep|October|Oct|November|December|Dec)?(( )(\d{2,4}))?/i';

      $rx_dayOfWeek   = 1;
      $rx_dayOfMonth  = 4;
      $rx_month       = 6;
      $rx_year        = 9;

      if ( preg_match_all( $date_pattern, $issue, $dateMatches, PREG_SET_ORDER ) ){

        foreach ( $dateMatches as $dateMatch) {

          if ( sizeof( $dateMatch ) < 2 ) {
            continue;
          }

          // initialize date variables
          $dayOfMonth = $year  = '';
          $dayOfWeek = $monthName = ' ';

          // Day of week
          if ( array_key_exists( $rx_dayOfWeek, $dateMatch ) ) {
            if ( $dateMatch[ $rx_dayOfWeek ] != '' ) {
              $dayOfWeek = ucfirst( substr( $dateMatch[ $rx_dayOfWeek ], 0, 3 ) );
            }
          }

          // Day of month
          if ( array_key_exists( $rx_dayOfMonth, $dateMatch ) ) {
            if ( $dateMatch[ $rx_dayOfMonth ] == '' && array_key_exists( $rx_year, $dateMatch ) ) {
              $dayOfMonth .= $dateMatch[ $rx_year ];
            }
            $dayOfMonth .= $dateMatch[ $rx_dayOfMonth ];

            if ( $dateMatch[ $rx_dayOfWeek ] != '' ) {
              $dayOfWeek .= ' ';
            }
          }

          // Month
          if ( array_key_exists( $rx_month, $dateMatch ) ) {
            $monthName = '.' . ucfirst( substr( $dateMatch[ $rx_month ], 0, 3 ) );
          }

          // Space between day of month and month name
          if ( array_key_exists( $rx_month, $dateMatch ) && array_key_exists( $rx_year, $dateMatch ) ) {
            if ( $dateMatch[ $rx_month ] != '' && $dateMatch[ $rx_year ] != '' && $dateMatch[ $rx_dayOfWeek ] != '' ) {
              $monthName .= '\'';
            }
          }

          // Day of month
          if ( array_key_exists( $rx_dayOfMonth, $dateMatch ) ) {
            if ( $dateMatch[ $rx_dayOfMonth ] != '' && array_key_exists( $rx_year, $dateMatch ) ) {
              $year = ucfirst( substr( $dateMatch[ $rx_year ], -2 ) );
            }
          }


          $issue = str_replace(
            $dateMatch[0],
            $dayOfWeek . $dayOfMonth . $monthName . $year,
            $issue
          );

        }

      }

      return $issue;

    }

    static public function removeSpaces ( $issue ) {

      $punctuation_pattern = '(\. |, |! |\? |\: |\; )';

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

      $time_pattern = '/(\()?(\d{1,2})((\.|:|h)(\d{1,2})(am|pm)?(\))?)/';

      $rx_hour    = 2;
      $rx_minutes = 5;

      if ( preg_match_all( $time_pattern, $issue, $timeMatches, PREG_SET_ORDER ) ){

        foreach ( $timeMatches as $timeMatch) {

          $hour = $timeMatch[ $rx_hour ];

          $minutes  = '00';

          if ( array_key_exists( $rx_minutes, $timeMatch ) ) {
            if ( $timeMatch[ $rx_minutes ] != '' ) {
              $minutes = $timeMatch[ $rx_minutes ];
            }
          }

          $issue = str_replace(
            $timeMatch[0],
            $hour . ':' . $minutes,
            $issue
          );

        }

      }

      $time_pattern = '/(\()?(\d{1,2})(am|pm)(\))?/';

      $rx_hour    = 2;
      $rx_ampm    = 3;

      if ( preg_match_all( $time_pattern, $issue, $timeMatches, PREG_SET_ORDER ) ){

        foreach ( $timeMatches as $timeMatch) {

          if ( $timeMatch[ $rx_ampm ] == 'am' ) {
            $hour = $timeMatch[ $rx_hour ];
            if ( $hour == 12 ) {
              $hour = 0;
            }
          } else {
            $hour = $timeMatch[ $rx_hour ] + 12;
          }

          $issue = str_replace(
            $timeMatch[0],
            $hour . ':00',
            $issue
          );

        }

      }



      return $issue;

    }

  }
