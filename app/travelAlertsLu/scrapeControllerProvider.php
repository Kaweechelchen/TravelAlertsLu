<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class scrapeControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get( '/', function( Application $app ) {

        echo '<pre>';

        $lineIssues = ScrapeHelpers::getData( $app );

        $storage = StorageHelpers::saveIssues( $app, $lineIssues );

        //print_r( $storage );

        return false;

      });

      return $ctr;

    }

  }
