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
       
### Path (protected route) Passthrough (public route

You can configure which routes require/not require authentication, setting it on `path` and `passthrough` option. In case you clone the files into `api` folder, using the following configuration, all route using `api/....` are protected except `api/ping` and `api/token`

       $app->add(new TokenAuthentication([
            'path' => '/',
            'passthrough' => ['/ping', '/token'], 
            'authenticator' => $authenticator
       ]));
