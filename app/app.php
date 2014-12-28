<?php

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    require_once __DIR__.'/bootstrap.php';

    $app = new Silex\Application();

    $app->register(
        new Igorw\Silex\ConfigServiceProvider(
            __DIR__."/config/config.json"
        )
    );

    $app->register( new Silex\Provider\DoctrineServiceProvider(),
        array( $app['db.options'] )
    );

    $app->mount( '/scrape', new travelAlertsLu\scrapeControllerProvider() );

    $app->mount( '/api/1/', new travelAlertsLu\jsonControllerProvider() );

    $app->get('/', function() use ($app) {
        return $app->redirect( '/api/1' );
    });

    $app->after(function ( Request $request, Response $response ) {

        $response->headers->set('Access-Control-Allow-Origin', '*');

    });

    return $app;
