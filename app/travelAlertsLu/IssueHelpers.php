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

        echo '<pre>';

        foreach ( $CFLXMLData[ 'channel' ][ 'item' ] as $issue ) {

          var_dump($issue);

          if ( ! self::issueExisting( $app, $issue ) ) {

            $issueId = self::insertIssue( $app, $issue );

            $descriptionPattern = '/<br\ \/><br\ \/>\n(.*?) <br/s';

            preg_match(
              $descriptionPattern,
              $issue[ 'description' ],
              $descriptionMatches
            );

            $issue[ 'description' ] = $descriptionMatches[1];

            $description = $issue[ 'description' ];

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

              if ( $key != 0 ) {
                $replyTo = $tweetId;
              } else {
                $replyTo = 0;
              }

              $tweetId  = self::tweet( $app, $tweet, $replyTo );
              self::saveTweet( $app, $tweet, $issue[ 'guid' ], $tweetId, $issueId );
            }

          }

        }

        return true;

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

    static public function lastRelatedIssue( $app, $issue ) {

      if ( empty( $issue ) ) {
        return false;
      }

      $idQuery = 'SELECT id
        FROM      issues
        AND       guid   = ?
        ORDER BY  id     DESC
        LIMIT     1';

      $issueId = $app[ 'db' ]->fetchColumn(
        $idUQery,
        array(
          $issue[ 'guid' ]
        )
      );

      return $issueId;

    }

    static public function tweet( $app, $tweet, $replyTo ) {

      $twitterSettings = array(
          'consumer_key'              => $app[ 'twitter' ][ 'consumer_key'               ],
          'consumer_secret'           => $app[ 'twitter' ][ 'consumer_secret'            ],
          'oauth_access_token'        => $app[ 'twitter' ][ 'oauth_access_token'         ],
          'oauth_access_token_secret' => $app[ 'twitter' ][ 'oauth_access_token_secret'  ]
      );

      $url = 'https://api.twitter.com/1.1/statuses/update.json';
      $requestMethod = 'POST';

      /** Perform a POST request and echo the response **/
      $twitter = new TwitterAPIExchange( $twitterSettings );

      $postfields[ 'status' ] = $tweet;
      if ( $replyTo != 0 ) {
        $postfields[ 'in_reply_to_status_id' ] = $replyTo;
      }

      $twitterResult = json_decode(
        $twitter->buildOauth( $url, $requestMethod )
                 ->setPostfields( $postfields )
                 ->performRequest()
      , true);

      return $twitterResult[ 'id' ];

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

  }