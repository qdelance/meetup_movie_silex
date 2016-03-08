<?php

use Meetup\MyObjectManager;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

$app->register(new Silex\Provider\FormServiceProvider());

// If you don't want to create your own form layout, it's fine: a default one will be used.
// But you will have to register the translation provider as the default form layout requires it.
// http://silex.sensiolabs.org/doc/providers/form.html#registering
$app->register(new Silex\Provider\TranslationServiceProvider());

// Simple service
$app['object_manager'] = $app->share(function ($app) {
    return new MyObjectManager($app['db']);
});

///////////////////////////////////////////////////////////////////////////////
// Registering routes

$app->get(
  '/movie/{id}/view',
  function ($id) use ($app) {

      $movie = $movies = $app['object_manager']->findMovieById($id);

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
  function (Request $request, $id) use ($app) {
      $mgr = $app['object_manager'];
      $movie = $mgr->findMovieById($id);
      if (null === $movie) {
          $app->abort(404, "No movie found");
      }
      $form = $app['form.factory']->createBuilder()->getForm();
      if ($form->handleRequest($request)->isValid()) {
          $mgr->removeMovie($movie['id']);
          $app['session']->getFlashBag()-> add('info', 'Object deleted '.$movie['title']);

          return $app->redirect($app["url_generator"]->generate('movie_list'));
      }

      // confirm page
      return $app['twig']->render('movie/movie_delete.html.twig', array('form' => $form->createView(), 'movie' => $movie));
  }
)->bind('movie_delete');

$app->match(
  '/movie/{id}/edit',
  function (Request $request, $id) use ($app) {
      $mgr = $app['object_manager'];
      $movie = $mgr->findMovieById($id);

      if (null === $movie) {
          $app->abort(404, "No movie found");
      }

      $types = $mgr->findAllTypes();
      foreach ($types as $type)
      {
          $typesChoice[$type['name']] = $type['id'];
      }
      $genres = $mgr->findAllGenres();
      foreach ($genres as $genre)
      {
          $genresChoice[$genre['name']] = $genre['id'];
      }

      $form = $app['form.factory']->createBuilder(FormType::class, $movie)
        ->add('title', TextType::class)
        ->add('type_id', ChoiceType::class, array('choices'  => $typesChoice, 'multiple' => false, 'required' => false))
        ->add('genres_id', ChoiceType::class, array('choices'  => $genresChoice, 'multiple' => true, 'required' => false))
        ->add('year', TextType::class, array('required' => false))
        ->add('releaseDate', TextType::class, array('required' => false))
        ->add('rating', TextType::class, array('required' => false))
        ->add('save', SubmitType::class, array('label' => 'Modify'))
        ->getForm();

      $form->handleRequest($request);

      if ($form->isValid()) {
          $data = $form->getData();

          $mgr->persistMovie($data);

          $app['session']->getFlashBag()->add('info', 'Object modified ' . $data['title']);

          // Redirect user to the list page
          $request = Request::create($app['url_generator']->generate('movie_list'));
          return $app->handle($request, HttpKernelInterface::SUB_REQUEST);
      }

      // display the form
      return $app['twig']->render('movie/movie_edit.html.twig', array('form' => $form->createView()));
  }
)->bind('movie_edit');

$app->match(
  '/movie/add',
  function (Request $request) use ($app) {
      $data = array(
        'title' => '',
        'type' => '',
        'year' => '',
        'releaseDate' => '',
        'rating' => ''
      );

      $mgr = $app['object_manager'];
      $types = $mgr->findAllTypes();
      foreach ($types as $type)
      {
          $typesChoice[$type['name']] = $type['id'];
      }
      $genres = $mgr->findAllGenres();
      foreach ($genres as $genre)
      {
          $genresChoice[$genre['name']] = $genre['id'];
      }

      $form = $app['form.factory']->createBuilder(FormType::class, $data)
        ->add('title', TextType::class)
        ->add('type_id', ChoiceType::class, array('choices'  => $typesChoice, 'multiple' => false, 'required' => false))
        ->add('genres_id', ChoiceType::class, array('choices'  => $genresChoice, 'multiple' => true, 'required' => false))
        ->add('year', TextType::class, array('required' => false))
        ->add('releaseDate', TextType::class, array('required' => false))
        ->add('rating', TextType::class, array('required' => false))
        ->add('save', SubmitType::class, array('label' => 'Add'))
        ->getForm();

      $form->handleRequest($request);

      if ($form->isValid()) {
          $data = $form->getData();

          $mgr = $app['object_manager'];
          $mgr->persistMovie($data);

          $app['session']->getFlashBag()->add('info', 'Object added ' . $data['title']);

          // Redirect user to the list page
          $request = Request::create($app['url_generator']->generate('movie_list'));
          return $app->handle($request, HttpKernelInterface::SUB_REQUEST);
      }

      // display the form
      return $app['twig']->render('movie/movie_edit.html.twig', array('form' => $form->createView()));
  }
)->bind('movie_add');

$app->get(
  '/movies/{page}',
  function ($page = 1) use ($app) {

      $count = $app['object_manager']->findMovieCount();
      $nbPage = ceil($count / MyObjectManager::NB_PER_PAGE);
      $movies = $app['object_manager']->findAllMoviesPaginated($page);

      return $app['twig']->render(
        'movie/movie_list.html.twig',
        array(
          'movies' => $movies,
          'page' => $page,
          'nbPages' => $nbPage
        )
      );
  }
)->bind('movie_list')->value('page', 1);

$app->get(
  '/contact',
  function () use ($app) {
      return 'Empty blank page';
  }
)->bind('core_contact');

$app->get(
  '/',
  function () use ($app) {
      // forward to /movies/list
      $subRequest = Request::create($app['url_generator']->generate('movie_list'));
      return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
  }
);

// set debug mode, to get stacktraces
$app['debug'] = true;

$app->run();
