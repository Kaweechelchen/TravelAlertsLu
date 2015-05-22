<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class StorageHelpers {

    static public function saveIssues( $app, $lineIssues ) {

      foreach ( $lineIssues as $line => $issues ) {

        foreach ( $issues as $issue ) {

          $issueId = self::getIssueId( $app, $issue );

          var_dump( $issue );

          if ( $issueId ) {

            return $issueId;

          } else {

            return self::insertIssue( $app, $issue );

          }

        }

      }

    }


    static public function getIssueId( $app, $issue ) {

      // Prepare the statement to check if there already is an entry in the
      // database for the current issue
      $idQuery = 'SELECT id
        FROM  issues
        WHERE title       = ?
        AND   description = ?
        AND   pubDate     = ?
        AND   guid        = ?';

      // Check for an id of the given issue in the database
      $issueId = $app[ 'db' ]->fetchColumn(
        $idQuery,
        array(
          $issue[ 'title' ],
          $issue[ 'description' ],
          $issue[ 'pubDate' ],
          $issue[ 'guid' ]
        )
      );

      // return the id of the issue we found in the database
      // The value of it will be false if no id could be found for the issue
      return $issueId;

    }

    static public function insertIssue( $app, $issue ) {

      // insert the issue to the database if no id was found for it (aka. new
      // issue)
      $app['db']->insert(
        'issues',
        array(
          'title'       =>  $issue[ 'title'       ],
          'description' =>  $issue[ 'description' ],
          'pubDate'     =>  $issue[ 'pubDate'     ],
          'guid'        =>  $issue[ 'guid'        ]
        )
      );

      // return the id of the issue that we've just inserted
      return $app['db']->lastInsertId();

    }

  }
