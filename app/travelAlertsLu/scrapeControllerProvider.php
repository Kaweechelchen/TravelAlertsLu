<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class scrapeControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get( '/', function( Application $app ) {

        $data = ScrapeHelpers::getData( $app );

        echo '<pre>';

        print_r( $data );

        return false;

      });

      return $ctr;

    }

  }
