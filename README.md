# Slim Route Authentication Using JWT

This is a Token Authentication Middleware for Slim 3.0+ using JWT  
Route auth middleware and token retrieval is using dyorg slim-token-authentication. I only add .env and Firebase/JWT

    https://github.com/dyorg/slim-token-authentication
    
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
