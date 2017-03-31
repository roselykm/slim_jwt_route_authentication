# Slim Route Authentication Using JWT

This is a Token Authentication Middleware for `Slim 3.0+ using JWT`  
Route auth middleware and token retrieval is using `dyorg slim-token-authentication`. I only add `dotenv` and `Firebase/JWT`

    https://github.com/dyorg/slim-token-authentication

## JWT secret key

Put your JWT secret key in the .env file. In case you are using `Auth0`, copy your `client secret` in the .env file. Remember never to upload that .env to public repo if you put your Auth0 client secret there.
    
    //.env file
    JWT_SECRET="jwt secret key"
    
    //load environment variable
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();

## Route authentication middleware

       $authenticator = function($request, TokenAuthentication $tokenAuth){

          /**
             * Try find authorization token via header, parameters, cookie or attribute
             * If token not found, return response with status 401 (unauthorized)
          */
          $token = $tokenAuth->findToken($request);

          //validate/decode JWT token using the secret key
          try {
             $tokenDecoded = JWT::decode($token, getenv('JWT_SECRET'), array('HS256'));
          }
          catch(Exception $e) {
             throw new \app\UnauthorizedException('Invalid Token');
          }
       };
       
## Path (protected route) Passthrough (public route)

You can configure which routes require/not require authentication, setting it on `path` and `passthrough` option. In case you clone the files into `api` folder, using the following configuration, all route using `api/....` are protected except `api/ping` and `api/token`

       $app->add(new TokenAuthentication([
            'path' => '/',
            'passthrough' => ['/ping', '/token'], 
            'authenticator' => $authenticator
       ]));
       
## Sample route in the `__DIR__\index.php` file

       //publc route
       $app->get('/ping', function($request, $response){
          $output = ['msg' => 'It is a public area'];
          return $response->withJson($output, 200, JSON_PRETTY_PRINT);
       });

       //public route for API testing
       //get token and use the JWT token in POSTMAN or using curl
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

       //protected route for refreshing nearly expired token
       //to refresh token, valid token is required
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
         */
       $app->get('/restrict', function($request, $response){
          $output = ['msg' => 'It\'s a restrict area. Token authentication works!'];
          return $response->withJson($output, 200, JSON_PRETTY_PRINT);
       });

## Frontend application

Setup your ajax and sessionStorage as the following for token header setting and token invalid redirect

    //set authorization token in header
    //sessionStorage.token is set either from Auth0 login, OAUTH
    //or from API login with username/password returning a token (SSL)
    //
    //set JWT token
    sessionStorage.token = token_from_OAUTH2_AUTH0_OR_REST_API;
    //
    //set the jquery ajax global header
    $.ajaxPrefilter(function( options, oriOptions, jqXHR ) {
       jqXHR.setRequestHeader("Authorization", "Bearer " + sessionStorage.token);
    }); 

    //invalid token redirect
    $.ajaxSetup({
       statusCode: {
          401: function(){
            //clear session data, jwt token etc
            localStorage.clear();     
            // Redirec to login page here            
          }
       }
    });

    //access secured route using the token already in the header	
    $.ajax({
       type: "GET",
       url: 'localhost/myapp/api/restrict',
       dataType: "json",
       success: function(data){
         //do something here with the json data from the API
       },
       error: function() {
       }
    });

## Demo, uploaded to `heroku`

# Pinging the server
GET https://afternoon-forest-18431.herokuapp.com/ping

# Getting jwt token for testing
GET https://afternoon-forest-18431.herokuapp.com/token

# Test the authenticated route. Using Postman or curl
curl -X GET -H "Authorization: Bearer a_long_token_appears_here" "https://afternoon-forest-18431.herokuapp.com/restrict"

# Refresh token - route authentication
curl -X PATCH -H "Authorization: Bearer a_long_token_appears_here" "https://afternoon-forest-18431.herokuapp.com/auth/refresh"
