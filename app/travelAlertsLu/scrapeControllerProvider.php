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

            $issue['title'] = 'Operational problems - Delay of train RE 5135';

            $issue['description'] = 'Due to a lack of equipment, train RB 6805 (Luxembourg-Rodange, scheduled departure 05:20 in Luxembourg) runs exceptionally with a lower capacity today.';

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
