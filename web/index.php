<?php

require('../vendor/autoload.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
  'monolog.logfile' => 'php://stderr',
));

$app->register(new JDesrosiers\Silex\Provider\CorsServiceProvider(), array(
    "cors.allowOrigin" => "*",
));

// Pusher
$pusher_app_id = getenv('PUSHER_APP_ID');
$pusher_app_key = getenv('PUSHER_APP_KEY');
$pusher_app_secret = getenv('PUSHER_APP_SECRET');

$pusher = new Pusher($pusher_app_key, $pusher_app_secret, $pusher_app_id);

// Pusher Logging
class PusherMonoLogger {
  function __construct($monolog) {
    $this->monolog = $monolog;
  }
  
  public function log( $msg ) {
    $this->monolog->addDebug(print_r($msg, true));
  }
}

$pusherMonoLogger = new PusherMonoLogger( $app['monolog'] );
$pusher->set_logger( $pusherMonoLogger );
$pusherMonoLogger->log($pusher);

// Routes
$app->get('/', function() {
  return new Response('Pusher PHP Auth Test', 200);
});

$app->post('/', function (Request $request) use($app, $pusher) {
  $channel_name = $request->get('channel_name');
  $socket_id = $request->get('socket_id');

  $auth = $pusher->socket_auth($channel_name, $socket_id);

  $jsonResponse = $app->json($auth, 200);
  return $jsonResponse;
});

$app->after($app['cors']);
$app->run();

?>
