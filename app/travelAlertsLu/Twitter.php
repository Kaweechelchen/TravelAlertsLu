<?php

namespace travelAlertsLu;

class Twitter
{
    public static function broadcastTweets($app, $tweets, $guid)
    {
        foreach ($tweets as $key => $tweet) {
            if ($key == 0) {
                $replyTo = self::lastRelatedTweetId($app, $guid);
            } else {
                $replyTo = $tweetId;
            }

            $tweetId = self::tweet($app, $tweet, $replyTo);

            if ($tweetId) {
                self::saveTweet($app, $tweet, $guid, $tweetId);
            }
        }
    }

    public static function tweet($app, $tweet, $replyTo)
    {
        $url           = 'https://api.twitter.com/1.1/statuses/update.json';
        $requestMethod = 'POST';

        $postfields[ 'status' ] = $tweet;
        $postfields[ 'lat'    ] = 49.598666;
        $postfields[ 'long'   ] = 6.1330168;
        if ($replyTo !== false) {
            $postfields[ 'in_reply_to_status_id' ] = $replyTo;
        }

        if ($app[ 'debug' ]) {
            var_dump($tweet);
            $twitterResult = '';
        } else {
            $twitterResult = json_decode(
          $app[ 'tw' ]->buildOauth($url, $requestMethod)
                      ->setPostfields($postfields)
                      ->performRequest(), true);
        }

        if (array_key_exists('id', $twitterResult)) {
            return $twitterResult[ 'id' ];
        } else {
            return false;
        }
    }

    public static function saveTweet($app, $tweet, $guid, $tweetId)
    {
        $app['db']->insert(
            'tweets',
            array(
                'tweet'   => $tweet,
                'tweetId' => $tweetId,
                'guid'    => $guid,
            )
        );

        return $app['db']->lastInsertId();
    }

    public static function lastRelatedTweetId($app, $guid)
    {
        $tweetIdQuery = 'SELECT tweetId
        FROM      tweets
        WHERE     guid   = ?
        ORDER BY  id     DESC
        LIMIT     1';

        $tweetId = $app[ 'db' ]->fetchColumn(
            $tweetIdQuery,
            array(
                $guid,
            )
        );

        return $tweetId;
    }

    public static function getAll($app)
    {
        $getAllIssues = 'SELECT *
        FROM      tweets
        ORDER BY  id';

        $issues = $app[ 'db' ]->fetchAll(
            $getAllIssues
        );

        return $issues;
    }
}
