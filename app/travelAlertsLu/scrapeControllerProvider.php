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

        //$lineIssues = Issues::getCurrent( $app );

        $count = 0;

        foreach ( $lineIssues as $line => $issues ) {

          foreach ( $issues as $issue ) {

            Storage::saveIssue( $app, $issue, $line );

            $count++;

          }

        }

        return $count . ' issued saved';

      });

      return $ctr;

    }

  }
