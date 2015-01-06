<?php

  namespace travelAlertsLu;

  use Silex\Application;
  use Silex\ControllerProviderInterface;

  class viewControllerProvider implements ControllerProviderInterface {

    public function connect ( Application $app ) {

      $ctr = $app['controllers_factory'];

      $ctr->get( '/', function( Application $app ) {

        $CFLData  = IssueHelpers::getIssuesCleaned ( $app, 15, 'DESC' );
        return $app['twig']->render(
          'base.twig',
          array(
            'issues' => $CFLData
          )
        );

      });

      return $ctr;

    }

  }
