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

    $app->mount( '/json', new travelAlertsLu\jsonControllerProvider() );

    $app->get('/', function() use ($app) {
        return $app->redirect( '/json' );
    });

    $app->after(function ( Request $request, Response $response ) {

        $response->headers->set('Access-Control-Allow-Origin', '*');

    });

    return $app;
