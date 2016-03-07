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

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
  'dbs.options' => array (
    'mysql' => array(
      'driver'    => 'pdo_mysql',
      'host'      => 'localhost',
      'dbname'    => 'silex',
      'user'      => 'root',
      'password'  => '',
      'charset'   => 'utf8mb4',
    )
  ),
));

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

      $sql = "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id LIMIT 100";
      $movies = $app['db']->fetchAll($sql);

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

      $sql = "SELECT m.id, title, t.name as type, releaseDate, year, rating FROM movie m LEFT JOIN type t ON m.type_id=t.id WHERE m.id = ?";
      $movie = $app['db']->fetchAssoc($sql, array((int) $id));

      return $app['twig']->render(
        'movie/movie_view.html.twig',
        array(
          'movie' => $movie,
        )
      );
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
      return 'Hello, you\'re @ home';
  }
);

// set debug mode, to get stacktraces
$app['debug'] = true;

$app->run();
