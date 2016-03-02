<?php

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

// Silex only displays Exception by default
// PHP errors
ErrorHandler::register();
// **Fatal** PHP errors
ExceptionHandler::register();

$app->get('/hello/{name}', function ($name) use ($app) {
    return 'Hello '.$app->escape($name);
});

$app->get('/error', function () use ($app) {
    unknown_function(); // fatal error, call to non existent function
    fopen('/tmp/does-not-exist'); // missing second argument, simple error
    return '';
});

$app->get('/exception', function () use ($app) {
    throw new Exception('Exception here');
});

$app->get('/', function () use ($app) {
    return 'Hello';
});

// set debug mode, to get stacktraces
$app['debug'] = true;

$app->run();
