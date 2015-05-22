<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class apiControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get('/', function() use ($app) {

        return $app->redirect( '/api/current' );

      });

      $ctr->get( '/current', function( Application $app ) {

        return $app->json( Issues::getCurrent() );

      });

      return $ctr;

    }

  }
