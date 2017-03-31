# Slim Route Authentication Using JWT

This is a Token Authentication Middleware for Slim 3.0+ using JWT  
Route auth middleware and token retrieval is using dyorg slim-token-authentication. I only add .env and Firebase/JWT

    https://github.com/dyorg/slim-token-authentication

## JWT secret key

Put your JWT secret key in the .env file. In case you are using Auth0, copy your client secret in the .env file. Remember never to upload that .env to public repo if you put your Auth0 client secret there.
    
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
            // Redirec the to the login page here            
          }
       }
    });

    //access secured route using the token already in the header	
    $.ajax({
       type: "GET",
       url: 'localhost/myapp/api/testtoken',
       dataType: "json",
       success: function(data){
         //do something here with the json data from the API
       },
       error: function() {
       }
    });
