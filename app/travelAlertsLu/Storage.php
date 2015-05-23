<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class Storage {

    static public function saveIssue( $app, $line, $issue ) {

      $issueId = self::getIssueId( $app, $line, $issue );

      if ( $issueId ) {

        return $issueId;

      } else {

        return self::insertIssue( $app, $line, $issue );

      }

    }

    static public function getIssueId( $app, $line, $issue ) {

      // Prepare the statement to check if there already is an entry in the
      // database for the current issue
      $idQuery = 'SELECT id
        FROM  issues
        WHERE line        = ?
        AND   title       = ?
        AND   description = ?
        AND   pubDate    >= ?
        AND   guid        = ?';

      // Check for an id of the given issue in the database
      $issueId = $app[ 'db' ]->fetchColumn(
        $idQuery,
        array(
          $line,
          $issue[ 'title'       ],
          $issue[ 'description' ],
  (int) ( $issue[ 'pubDate'     ] - $app[ 'secondsToDiffer' ] ),
          $issue[ 'guid'        ]
        )
      );

      // return the id of the issue we found in the database
      // The value of it will be false if no id could be found for the issue
      return $issueId;

    }

    static public function insertIssue( $app, $line, $issue ) {

      // insert the issue to the database if no id was found for it (aka. new
      // issue)
      $app['db']->insert(
        'issues',
        array(
          'line'        =>  $line,
          'title'       =>  $issue[ 'title'       ],
          'description' =>  $issue[ 'description' ],
          'pubDate'     =>  $issue[ 'pubDate'     ],
          'guid'        =>  $issue[ 'guid'        ]
        )
      );

      twitter::generateIssueTweets( $app, $issue );

      // return the id of the issue that we've just inserted
      return $app['db']->lastInsertId();

    }

  }
