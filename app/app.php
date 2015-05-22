<?php

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    date_default_timezone_set('Europe/Luxembourg');

    require_once __DIR__.'/bootstrap.php';

    $app = new Silex\Application();

    $app->register(
        new Igorw\Silex\ConfigServiceProvider(
            __DIR__."/config/config.json"
        )
    );

    $twitterSettings = array(
      'consumer_key'              => $app[ 'tw.options' ][ 'consumer_key'               ],
      'consumer_secret'           => $app[ 'tw.options' ][ 'consumer_secret'            ],
      'oauth_access_token'        => $app[ 'tw.options' ][ 'oauth_access_token'         ],
      'oauth_access_token_secret' => $app[ 'tw.options' ][ 'oauth_access_token_secret'  ]
    );

    $app[ 'tw' ] = new TwitterAPIExchange( $twitterSettings );

    $app->register( new Silex\Provider\DoctrineServiceProvider(),
        array( $app['db.options'] )
    );

    // Provides Twig template engine
    $app->register(new Silex\Provider\TwigServiceProvider(), array(
        'twig.path' => __DIR__.'/views',
    ));

    $app->mount( '/', new travelAlertsLu\viewControllerProvider() );

    $app->mount( '/api', new travelAlertsLu\apiControllerProvider() );

    //$app->mount( '/api/1/', new travelAlertsLu\jsonControllerProvider() );

    $app->after(function ( Request $request, Response $response ) {

        $response->headers->set('Access-Control-Allow-Origin', '*');

    });

    return $app;
