<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class PrepareTweets {

    static public function generateTweets( $app, $issue, $line ) {

      if ( self::checkTitleRelevance( $issue[ 'title' ] ) ){
        $title  = $issue[ 'title' ] . "\n";
      } else {
        $title = '';
      }

      $issue = $issue[ 'description' ];

      var_dump($issue);
      var_dump(self::dueToReadable( $issue ));
      exit;

      if ( self::dueToReadable( $issue ) ) {

        $issue  = self::HighlightDirection  ( $issue );
        $issue  = self::dueToReadable       ( $issue );
        $issue  = str_ireplace('Luxembourg', 'LUX', $issue);

      } else {

        $issue = $title . $issue;
        $issue  = self::replaceCFLStrings   ( $issue );
        $issue  = self::removeCFLStrings    ( $issue );
        $issue  = self::removeSpaces        ( $issue );
        $issue  = self::delayReadable       ( $issue );
        $issue  = self::shortenDate         ( $issue );
        $issue  = self::shortenTime         ( $issue );
        $issue  = self::departure           ( $issue );
        $issue  = self::tagTrain            ( $issue );

      }

      //$issue  = self::tagIssue            ( $issue );

      if ( $line == 'CFL90' ) {
        $issue  = self::includeTravelAlerts ( $issue );
      }

      $issue = trim($issue);

      if ( $line != 'global' ) {
        $issue = $issue . "\n#" . $line;
      }

      $tweets = self::splitToTweets       ( $issue );

      return $tweets;

    }

    static public function HighlightDirection ( $issue ) {

      $stationsPattern = '/(?P<start>.*?(?:Kautenbach|Dommeldange|Wiltz|Rumelange|Arlon|Volmerange-les-Mines|Bettembourg|Rodange|Luxembourg|Ettelbruck|Diekirch|Trier(?:-Hbf)?|Liers|Mulhouse(?:-Ville)?|Metz(?:-Ville)?|Longwy|Thionville|Troisvierges|Bruxelles-Midi|Wasserbillig|Athus|Basel(?:-SBB)|Virton|Gouvy|KOBLENZ HBF \(Germany\)|Strasbourg|Kleinbettingen))-(?P<end>.*)/is';

      if ( preg_match( $stationsPattern, $issue, $stationsMatches ) ){
        return $stationsMatches['start'] . '→' . $stationsMatches['end'];
      } else {
        return $issue;
      }

    }

    static public function checkTitleRelevance( $title ){


      // Check if the title includes one of these sentences which makes it
      // irrelevant
      $irrelevantSTrings = array(
        'Delay of train ',
        'Train delayed ',
        'Cancellation of train ',
        'Trains delayed Disturbances'

      );

      foreach ( $irrelevantSTrings as $pattern ) {
        if ( preg_match( '/' . $pattern . '/i', $title ) ){
          return false;
        }
      }

      return true;

    }

    static public function tagTrain ( $issue ){

      return preg_replace_callback(
        "/(the|train|the train)?(\ |\,|\(|\.|\))?([A-Z]{2,3}( )?\d{3,5})/i",
        function ( $matches ) {
          $train = str_replace( ' ', '', $matches[ 3 ] );
          return ' #' . $train;
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

      $departure_pattern = '/(scheduled )?(departure|dep|arrival|arr) (((from|at)? )?([A-Z][\p{L}\s-]+) at )?((\d{1,2})(\.|:|h)(\d{2})(am|pm)?)/i';

      $rx_state     = 2;
      $rx_station   = 6;
      $rx_station2  = 14;
      $rx_hour      = 8;
      $rx_minutes   = 10;

      if ( preg_match_all( $departure_pattern, $issue, $departureMatches, PREG_SET_ORDER ) ){

        foreach ( $departureMatches as $departureMatch) {

          $state = ucfirst( substr( $departureMatch[ $rx_state ], 0, 3 ) ) . '.';

          if ( $departureMatch[ $rx_station ] == '' ) {
            $station = ucfirst( $departureMatch[ $rx_station2 ] );
          } else {
            $station = ucfirst( $departureMatch[ $rx_station ] );
          }

          $time = $departureMatch[ $rx_hour ] . ':' . $departureMatch[ $rx_minutes ];

          $issue = str_replace( $departureMatch[0], $state . ' '. $station . ' ' . $time, $issue);

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

      $delay_pattern = '/((,)?( )?((has )?a(n expected)? delay of (\d+-)?(\d+) (minutes)?.*))/i';

      if ( preg_match( $delay_pattern, $issue, $delayMatches ) ){

        $issue = str_replace( $delayMatches[1], "\ndelay: " . $delayMatches[8], $issue);
        if ( strtolower( $delayMatches[9] ) == 'minutes' ) {
          $issue .= 'm';
        }

      }

      return $issue;

    }

    static public function dueToReadable ( $issue ) {

      $dueTo_pattern = '/(?:(?:due to )(?P<description>disturbances on the network of (?:SNCB|SCNB|SNCF|DB)?|traffic problems|a lack of equipment|delays from the previous train|a breakdown|the cancellation of (?:the|train|the train)?(?:\ |\,|\(|\.|\))(?P<train2>(?:[A-Z]{2,3})?[\s]*\d{2,5})(?: [\(\)\-\.\s[:alpha:]]+)?|(?:a technical problem on the railway(?: )?crossing|signalling problems|an overload|operational problems|an emergency operation) in ([\(\)\-\.\s[:alpha:]]+)?(?: station)?|(?:a )?last minute modification of(?: the)? staff planning|delays from the train(?:\ |\,|\(|\.|\))(?P<train4>([A-Z]{2,3})?[\s]*\d{2,5}))|)[\s]*, (?:the|train|the train)?(?:\ |\,|\(|\.|\))(?P<train>(?:[A-Z]{2,3})?[\s]*\d{2,5}) \((?P<track>[\→\(\)\-\.\s[:alpha:]]+),?(?: originally)?(?: scheduled)? (?P<schedule_time_of>arrival|departure)(?: at| in)?(?: (?P<schedule_departure>[\(\)\-\.\s[:alpha:]]+))?[\s]*(?P<schedule_time>\d{1,2}(?::|.)\d{1,2})?(?:[\s]+in)?(?: (?P<schedule_loctaion>[\(\)\-\.\s[:alpha:]]+))?[\s]*,?(?: scheduled)? ?(?:(?P<schedule_time_of2>arrival|departure)(?: (?P<schedule_time2>\d{1,2}(?::|.)\d{1,2}))?(?: at| in)?(?: (?P<schedule_arrival>[\(\)\-\.\s[:alpha:]]+))?( )?(?P<schedule_time3>\d{1,2}(?::|.)\d{1,2})?)?\) ?(?P<reason>is cancelled|(?:has|drives with|will continue with) a(?:n expected)? ?(?:delay of )?(?P<delay>(?:[0-9]+-.)*[0-9]+) (?P<delay_unit>minutes|hours)?|runs only between (?P<running_between_dep>[\(\)\-\s[:alpha:]]+) and (?P<running_between_arr>[\(\)\-\s[:alpha:]]+)|runs(?: exceptionally)? with a lower capacity today)/i';

      if ( preg_match( $dueTo_pattern, $issue, $dueToMatches ) ){

        // TrainID departure-destination
        $issue  = '🚂 #' . str_replace( ' ', '', $dueToMatches['train'] ) . ' ' . $dueToMatches['track'];

        // Arrival|Departure: TIME
        $issue .= "\n" . ucfirst($dueToMatches['schedule_time_of']) . ' ' . ltrim( $dueToMatches['schedule_time'], '0' );

        if ( strtolower($dueToMatches['reason']) == 'is cancelled' ) {

          $issue .= "\nis cancelled";

        } elseif ( strpos( strtolower($dueToMatches['reason']), 'lower capacity') ) {

          $issue .= "\nlower capacity";

        } elseif ( $dueToMatches['running_between_dep'] != '' && $dueToMatches['running_between_arr'] != '' ) {

          $issue .= "\nonly runs btwn " . $dueToMatches['running_between_dep'] . " 'n " . $dueToMatches['running_between_arr'];

        } else {

          if ( substr( $dueToMatches['delay_unit'], 0, 3 ) == 'min' ) {
            $delay_unit = '’';
          } else {
            $delay_unit = ' ' . $dueToMatches['delay_unit'];
          }

          // Delay AMOUNT TIME-UNIT
          $issue .= "\nDelay: " . $dueToMatches['delay'] . $delay_unit;
        }

        return $issue;

      } else {
        return false;
      }

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
      $issue = trim( $issue, ' ' );

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

    public static function replaceCFLStrings( $issue ) {

      // Replacing strings with shortder ones
      $replaceStrings = array(
        '(Lux[\p{L}]+)'                                     =>  'LUX',
        'p[\p{L}]+form'                                     =>  'platform',
        'Due to failure of the signal box'                  =>  'Signal failure',
        'Due to signalling problems'                        =>  'Signal failure',
        'Due to catenary works, '                           =>  'Catenary works:',
        'Due to operational problems'                       =>  'operational pbs.',
        'the section of the line between'                   =>  'section between',
        'train traffic'                                     =>  'traffic',
        'train service'                                     =>  'service',
        '–'                                                 =>  '➡️',
        'Trains will be replaced by buses'                  =>  'bus replacement',
        'CFL inform their customers that due to'            =>  'due to',
        'strike action'                                     =>  'strike',
        'Due to a technical problem with the railway track' =>  'track problems'
        );

      foreach ( $replaceStrings as $pattern => $replacement ) {
        $issue = preg_replace(
          '/' . $pattern . '/i',
          $replacement,
          $issue
        );
      }

      return $issue;

    }

    public static function removeCFLStrings( $issue ) {

      // Replacing strings with shortder ones
      $removeStrings = array(
        'Delays and cancellations may still be expected',
        'FURTHER INFORMATION AS SOON AS POSSIBLE',
        'Due to works, ',
        'completely ',
        '(\()?and vice versa(\))?( )?'
      );

      foreach ( $removeStrings as $pattern ) {
        $issue = preg_replace(
          '/' . $pattern . '/i',
          '',
          $issue
        );
      }

      return $issue;

    }

  }
