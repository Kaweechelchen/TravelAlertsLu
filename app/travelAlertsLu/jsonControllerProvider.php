<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class jsonControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get( '/', function( Application $app ) {

        $CFLData  = IssueHelpers::getIssues ( $app );
        return $app->json( $CFLData );

      });

      return $ctr;

    }

  }
