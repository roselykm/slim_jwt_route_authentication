<?php
   ini_set("date.timezone", "Asia/Kuala_Lumpur");

   header('Access-Control-Allow-Origin: *');   

   //*
   // Allow from any origin
   if (isset($_SERVER['HTTP_ORIGIN'])) {
      // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
      // you want to allow, and if so:
      header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
      header('Access-Control-Allow-Credentials: true');
      header('Access-Control-Max-Age: 86400');    // cache for 1 day
   }

   // Access-Control headers are received during OPTIONS requests
   if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
         header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS");         

      if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
         header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

      exit(0);
   }
   //*/

   require_once 'vendor/autoload.php';

   use \Psr\Http\Message\ServerRequestInterface as Request;
   use \Psr\Http\Message\ResponseInterface as Response;

   //load environment variable
   $dotenv = new Dotenv\Dotenv(__DIR__);
   $dotenv->load();

   use Slim\App;
   use Slim\Middleware\TokenAuthentication;
   use Firebase\JWT\JWT;

   $config = [
      'settings' => [
         'displayErrorDetails' => true
      ]
   ];

   $app = new App($config);

   $authenticator = function($request, TokenAuthentication $tokenAuth){

      /**
         * Try find authorization token via header, parameters, cookie or attribute
         * If token not found, return response with status 401 (unauthorized)
      */
      $token = $tokenAuth->findToken($request);

      try {
         $tokenDecoded = JWT::decode($token, getenv('JWT_SECRET'), array('HS256'));
      }
      catch(Exception $e) {
         throw new \app\UnauthorizedException('Invalid Token');
      }
   };

   /**
     * Add token authentication middleware
     */
   $app->add(new TokenAuthentication([
        'path' => '/',
        'passthrough' => ['/ping', '/token'], //'/ping', /* or ['/api/auth', '/api/test'] */
        'authenticator' => $authenticator,
        'secure' => false
   ]));

   /**
     * Public route example
     */
   $app->get('/ping', function($request, $response){
      $output = ['msg' => 'It is a public area'];
      return $response->withJson($output, 200, JSON_PRETTY_PRINT);
   });

   $app->get('/token', function($request, $response){
      //create JWT token
      $date = date_create();
      $jwtIAT = date_timestamp_get($date);
      $jwtExp = $jwtIAT + (20 * 60); //expire after 20 minutes

      $jwtToken = array(
         "iss" => "rbk.net", //client key
         "iat" => $jwtIAT, //issued at time
         "exp" => $jwtExp, //expire
      );
      $token = JWT::encode($jwtToken, getenv('JWT_SECRET'));

      $data = array('token' => $token);
      return $response->withJson($data, 200)
                      ->withHeader('Content-type', 'application/json');
   });

   $app->patch('/auth/refresh', function($request, $response){
      //create new JWT token
      $date = date_create();
      $jwtIAT = date_timestamp_get($date);
      $jwtExp = $jwtIAT + (20 * 60); //expire after 20 minutes

      $jwtToken = array(
         "iss" => "rbk.net", //client key
         "iat" => $jwtIAT, //issued at time
         "exp" => $jwtExp, //expire
      );
      $token = JWT::encode($jwtToken, getenv('JWT_SECRET'));

      $data = array('token' => $token);
   
      return $response->withJson($data, 200)
                      ->withHeader('Content-type', 'application/json');
   });

   /**
     * Restrict route example
     * Our token is "usertokensecret"
     */
   $app->get('/restrict', function($request, $response){
      $output = ['msg' => 'It\'s a restrict area. Token authentication works!'];
      return $response->withJson($output, 200, JSON_PRETTY_PRINT);
   });

   $app->run();
