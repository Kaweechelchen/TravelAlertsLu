<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class scrapeControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get( '/', function( Application $app ) {

        $lineIssues = Issues::getLineIssues( $app );

        file_put_contents(
          'current.json',
          json_encode( $lineIssues )
        );

        $count = 0;

        foreach ( $lineIssues as $line => $issues ) {

          foreach ( $issues as $issue ) {

            $tweets[] = Storage::saveIssue( $app, $issue, $line );

            $count++;

          }

        }

        if ( $app[ 'debug' ] ) {

          return $app->json( $tweets );

        } else {

          return $count . ' issued saved';

        }

      });

      return $ctr;

    }

  }
