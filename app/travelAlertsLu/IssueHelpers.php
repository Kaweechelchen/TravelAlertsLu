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

        $words = explode( ' ', $description );

        $tweet = '';
        unset( $tweets );

        foreach ($words as $word) {

          if ( $word != '' ) {

            if ( in_array( strtolower( $word ), $app[ 'wordsToTag' ] ) ) {
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

      $description = str_replace( 'Due to failure of the signal box', 'signal failure', $description);
      $description = str_replace( 'Due to signalling problems', 'signal failure', $description);
      $description = str_replace( '. Delays and cancellations may still be expected.', '', $description);
      $description = str_replace( '. FURTHER INFORMATION AS SOON AS POSSIBLE.', '', $description);
      $description = str_replace( 'train traffic', 'traffic', $description);

      return $description;

    }

  }
