<?php

namespace travelAlertsLu;

class Storage
{
    public static function saveIssue($app, $issue, $line)
    {
        $issueId = self::getIssueId($app, $issue, $line);

        if ($issueId && $app[ 'debug' ] == false) {
            return $issueId;
        } else {
            return self::insertIssue($app, $issue, $line);
        }
    }

    public static function getIssueId($app, $issue, $line)
    {

        // Prepare the statement to check if there already is an entry in the
        // database for the current issue
        $idQuery = 'SELECT id
            FROM  issues
            WHERE title       = ?
            AND   description = ?
            AND   pubDate    >= ?
            AND   guid        = ?';

        // Check for an id of the given issue in the database
        $issueId = $app[ 'db' ]->fetchColumn(
            $idQuery,
            array(
                $issue[ 'title' ],
                $issue[ 'description' ],
                (int) ($issue[ 'pubDate' ] - $app[ 'secondsToDiffer' ]),
                $issue[ 'guid'        ],
            )
        );

        // return the id of the issue we found in the database
        // The value of it will be false if no id could be found for the issue
        return $issueId;
    }

    public static function insertIssue($app, $issue, $line)
    {
        $tweets = PrepareTweets::generateTweets($app, $issue, $line);

        if ($app[ 'debug' ]) {
            return $tweets;
        } else {

            // insert the issue to the database if no id was found for it (aka. new
            // issue)
            $app['db']->insert(
                'issues',
                array(
                    'line'        => $line,
                    'title'       => $issue[ 'title'       ],
                    'description' => $issue[ 'description' ],
                    'pubDate'     => $issue[ 'pubDate'     ],
                    'start'       => $issue[ 'start'       ],
                    'end'         => $issue[ 'end'         ],
                    'guid'        => $issue[ 'guid'        ],
                )
            );

            Twitter::broadcastTweets($app, $tweets, $issue[ 'guid' ]);

            // return the id of the issue that we've just inserted
            return $app['db']->lastInsertId();
        }
    }
}
