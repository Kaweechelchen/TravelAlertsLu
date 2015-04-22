<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class IssueHelpers {

    static public function CFLData( $app ) {

        // Reading the XML from the CFL feed
        $CFLXMLData = simplexml_load_file( $app[ 'CFLXML' ], NULL, LIBXML_NOCDATA);

        $CFLXMLData   = json_decode(
            json_encode( $CFLXMLData ),
            true
        );

        if ( array_key_exists( 'item', $CFLXMLData[ 'channel' ] ) ) {

          foreach ( $CFLXMLData[ 'channel' ][ 'item' ] as $issue ) {

            if ( is_array( $issue ) ) {
              self::handleIssue( $app, $issue );
            } else {
              self::handleIssue( $app, $CFLXMLData[ 'channel' ][ 'item' ] );
              break;
            }

          }

          return true;

        } else {
          return false;
        }

    }

    static public function handleIssue( $app, $issue ) {

      if ( ! self::issueExisting( $app, $issue ) ) {

        $issueId = self::insertIssue( $app, $issue );

        $descriptionPattern = '/<br\ \/><br\ \/>\n(.*?) <br/s';

        preg_match(
          $descriptionPattern,
          $issue[ 'description' ],
          $descriptionMatches
        );

        $issue[ 'description' ] = $descriptionMatches[1];

        $description = html_entity_decode(str_replace( '<br />', "\n", $issue[ 'description' ] ), ENT_HTML5 || ENT_COMPAT);

        $description = self::fixSpelling( $description );

        $description = self::makeIssueReadable( $description );

        $wordsBySpace = explode( ' ', $description );

        foreach ($wordsBySpace as $wordBySpace) {
          $tmpWords = explode( "\n", $wordBySpace );

          foreach ($tmpWords as $key => $tmpWord) {
            $words[] = $tmpWord;

            if ( ($key + 1) < sizeof( $tmpWords ) ){
              $words[] = "\n";
            }

          }

        }

        $tweet = '';
        unset( $tweets );

        $previousWord = '';

        foreach ($words as $word) {

          if ( $word !== '' ) {

            if ( in_array( strtolower( $word ), $app[ 'wordsToTag' ] ) ) {
              $word = '#'.$word;
            }

            if ( strlen( $tweet ) + strlen( $word ) > 133 ) {
              $tweets[] = $tweet;
              $tweet = '';
            }

            if (
                 ( strlen( $tweet ) != 0 )
              && ( strpos($word, "\n" ) === false )
              && ( strpos($previousWord, "\n" ) === false )
            ) {
              $tweet .= ' ';
            }

            $tweet .= $word;
            $previousWord = $word;

          }

        }

        $tweets[] = $tweet;

        foreach ( $tweets as $key => $tweet) {

          if ( sizeof( $tweets ) > 1 ) {
            $tweet    = $tweet . ' (' . ( $key + 1 ) . '/' . sizeof( $tweets ) . ')';
          }

          if ( $key == 0 ) {
            $replyTo = self::lastRelatedTweetId( $app, $issue );
          } else {
            $replyTo = $tweetId;
          }

          $tweetId  = self::tweet( $app, $tweet, $replyTo );

          if ( $tweetId ){
            self::saveTweet( $app, $tweet, $issue[ 'guid' ], $tweetId, $issueId );
          }

        }

      }

    }

    static public function issueExisting( $app, $issue ) {

      if ( empty( $issue ) ) {
        return false;
      }

      $idQuery = 'SELECT id
        FROM  issues
        WHERE title       = ?
        AND   description = ?
        AND   pubDate     = ?
        AND   guid        = ?';

      $issueId = $app[ 'db' ]->fetchColumn(
        $idQuery,
        array(
          $issue[ 'title' ],
          $issue[ 'description' ],
          $issue[ 'pubDate' ],
          $issue[ 'guid' ]
        )
      );

      return $issueId;

    }

    static public function insertIssue( $app, $issue ) {

      if ( empty( $issue ) ) {
        return false;
      }

      $app['db']->insert(
        'issues',
        array(
          'title'       =>  $issue[ 'title'       ],
          'description' =>  $issue[ 'description' ],
          'pubDate'     =>  $issue[ 'pubDate'     ],
          'guid'        =>  $issue[ 'guid'        ]
        )
      );

      return $app['db']->lastInsertId();

    }

    static public function lastRelatedTweetId( $app, $issue ) {

      if ( empty( $issue ) ) {
        return false;
      }

      $tweetIdQuery = 'SELECT tweetId
        FROM      tweets
        WHERE     guid   = ?
        ORDER BY  id     DESC
        LIMIT     1';

      $tweetId = $app[ 'db' ]->fetchColumn(
        $tweetIdQuery,
        array(
          $issue[ 'guid' ]
        )
      );

      return $tweetId;

    }

    static public function tweet( $app, $tweet, $replyTo ) {

      $url = 'https://api.twitter.com/1.1/statuses/update.json';
      $requestMethod = 'POST';

      $postfields[ 'status' ] = $tweet;
      $postfields[ 'lat'    ] = 49.598666;
      $postfields[ 'long'   ] = 6.1330168;
      if ( $replyTo != 0 ) {
        $postfields[ 'in_reply_to_status_id' ] = $replyTo;
      }

      $twitterResult = json_decode(
        $app[ 'tw' ]->buildOauth( $url, $requestMethod )
                    ->setPostfields( $postfields )
                    ->performRequest()
      , true);

      if ( array_key_exists( 'id', $twitterResult ) ) {
        return $twitterResult[ 'id' ];
      } else {
        return false;
      }

    }

    static public function saveTweet( $app, $tweet, $guid, $tweetId, $issueId ) {

      $app['db']->insert(
        'tweets',
        array(
          'tweet'   =>  $tweet,
          'tweetId' =>  $tweetId,
          'issueId' =>  $issueId,
          'guid'    =>  $guid
        )
      );

      return $app['db']->lastInsertId();

    }

    static public function getIssues( $app, $limit = 0, $order = 'ASC' ) {

      $issuesQuery = 'SELECT *
        FROM      issues
        ORDER BY  id ' . $order;

      if ( $limit != 0) {
        $issuesQuery .= ' LIMIT ' . $limit;
      }

      $issues = $app[ 'db' ]->fetchAll(
        $issuesQuery
      );

      return $issues;

    }

    static public function getIssuesCleaned ( $app, $limit = 0, $order = 'ASC' ) {

      $issues = self::getIssues( $app, $limit, $order );

      $descriptionPattern = '/<br\ \/><br\ \/>\n(.*?) <br/s';

      foreach ( $issues as $issue ) {

        preg_match(
          $descriptionPattern,
          $issue[ 'description' ],
          $descriptionMatches
        );

        $newIssue[ 'description' ] = $descriptionMatches[1];

        $newIssue[ 'id' ]    = $issue[ 'id' ];
        $newIssue[ 'title' ] = $issue[ 'title' ];
        $newIssue[ 'date' ]  = strtotime( $issue[ 'pubDate' ] );

        $cleanIssues[] = $newIssue;

      }

      return $cleanIssues;

    }

    static public function fixSpelling ( $description ){

      $description = str_replace( 'a lots of', 'a lot of', $description);

      return $description;

    }

    static public function makeIssueReadable ( $description ){

      // Replacing strings with shortder ones
      $removeStrings = array(
        '. Delays and cancellations may still be expected',
        '. FURTHER INFORMATION AS SOON AS POSSIBLE',
        'Due to works, ',
        '(and vice versa) '
      );

      foreach ( $removeStrings as $needle ) {
        $description = str_replace( $needle, '', $description);
      }

      // Replacing strings with shortder ones
      $replaceStrings = array(
        'Due to failure of the signal box'  =>  'Signal failure',
        'Due to signalling problems'        =>  'Signal failure',
        'Due to catenary works, '           =>  'Catenary works: ',
        'the section of the line between'   =>  'the section between',
        'Luxemburg'                         =>  'Luxembourg',
        'plateform'                         =>  'platform',
        'train traffic'                     =>  'traffic',
        'train service'                     =>  'service',
        '–'                                 =>  "➡️",
        'SNCB'                              =>  "@SNCB",
        'SNCF'                              =>  "@SNCF",
        'DB'                                =>  "@DB_Bahn",
        ', '                                =>  "\n"
      );

      foreach ( $replaceStrings as $needle => $replacement ) {
        $description = str_replace( $needle, $replacement, $description);
      }

      $worksIn_pattern = '/Due to works in the train station of (.*?),/s';

      if ( preg_match( $worksIn_pattern, $description, $descriptionMatches ) ){

        $worksIn_city = $descriptionMatches[1];
        $description = str_replace( $descriptionMatches[0], '#' . $descriptionMatches[1] . ':', $description);

      }

      $worksIn_pattern = '/.*works in (.*?),/s';

      if ( preg_match( $worksIn_pattern, $description, $descriptionMatches ) ){

        $worksIn_city = $descriptionMatches[1];
        $description = str_replace( $descriptionMatches[0], '#' . $descriptionMatches[1] . ':', $description);

      }

      $solvedAtTime_pattern = '/.* has been solved( at ([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9])/s';

      if ( preg_match( $solvedAtTime_pattern, $description, $descriptionMatches ) ){

        $solvedAtTime = $descriptionMatches[1];
        $description = str_replace( $descriptionMatches[1], '', $description);

      }

      $delay_pattern = '/.*((a delay of )(.*?)( ([a-z,A-Z]*?) is expected on )(.*?) trains)/s';

      if ( preg_match( $delay_pattern, $description, $descriptionMatches ) ){

        $delay = str_replace(' ', '', $descriptionMatches[3]);
        $description = str_replace( $descriptionMatches[1], "\n#Delay:" . $delay . ' ' . $descriptionMatches[5], $description);

      }



      if ( strpos($description, ' TER'  ) !== false
        || strpos($description, 'Metz'  ) !== false
        || strpos($description, 'Longwy') !== false ){

        $description .= "\n/cc @TER_Metz_Lux";

      }

      // Uppercase first letter and trip "." at the end
      $description = ucfirst( trim( $description, "." ) );

      return $description;

    }

  }
