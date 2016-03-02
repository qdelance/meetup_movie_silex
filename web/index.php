<?php

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

require_once __DIR__.'/../vendor/autoload.php';

// Silex only displays Exception by default
// PHP errors
ErrorHandler::register();
// **Fatal** PHP errors
ExceptionHandler::register();

$app = new Silex\Application();

///////////////////////////////////////////////////////////////////////////////
// Registering providers

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(
  new Silex\Provider\TwigServiceProvider(),
  array(
    'twig.path' => __DIR__.'/../views',
  )
);

///////////////////////////////////////////////////////////////////////////////
// Registering routes

$app->get(
  '/hello/{name}',
  function ($name) use ($app) {
      return 'Hello '.$app->escape($name);
  }
);

$app->get(
  '/hello/{name}',
  function ($name) use ($app) {
      return 'Hello '.$app->escape($name);
  }
);

$app->get(
  '/movies/list',
  function () use ($app) {
      $movies = [
        [
          'id' => 1,
          'title' => 'Batman vs Superman',
          'type' => 'Movie',
          'genres' => ['action'],
          'releaseDate' => '',
          'year' => '1998',
          'rating' => 3.4,
        ],
        [
          'id' => 2,
          'title' => 'Tous ensemble',
          'type' => 'Movie',
          'genres' => ['drama', 'comedy'],
          'releaseDate' => '',
          'year' => '2016',
          'rating' => 8.9,
        ],
        [
          'id' => 3,
          'title' => 'Demain',
          'type' => 'Movie',
          'genres' => ['documentary'],
          'releaseDate' => '',
          'year' => '2015',
          'rating' => 9.2,
        ],
      ];

      return $app['twig']->render(
        'movie/movie_list.html.twig',
        array(
          'movies' => $movies,
        )
      );
  }
)->bind('movie_list');

$app->get(
  '/movie/{id}/view',
  function ($id) use ($app) {
      return 'Movie View '.$id;
  }
)->bind('movie_view');

$app->match(
  '/movie/{id}/delete',
  function ($id) use ($app) {
      return 'Movie Delete '.$id;
  }
)->bind('movie_delete');

$app->match(
  '/movie/{id}/edit',
  function ($id) use ($app) {
      return 'Movie Edit '.$id;
  }
)->bind('movie_edit');

$app->match(
  '/movie/add',
  function () use ($app) {
      return 'Movie Add';
  }
)->bind('movie_add');

$app->get(
  '/movies',
  function () use ($app) {
      // forward to /movies/list
      $subRequest = Request::create('/movies/list', 'GET');

      return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
  }
)->bind('movie_home');

$app->get(
  '/contact',
  function () use ($app) {
      return 'Empty blank page';
  }
)->bind('core_contact');

$app->get(
  '/',
  function () use ($app) {
      return 'Hello';
  }
);

// set debug mode, to get stacktraces
$app['debug'] = true;

$app->run();
