<?php
    
    namespace App\Domain;
    
    use Psr\Container\ContainerInterface;
    use App\Domain\Oauth2\TokenType\CustomBearer;
    
    class Oauth2
    {
        /**
         * @var mixed|oauth2
         */
        public static $oauth2;
        
        /**
         * @var ContainerInterface
         */
        public function __construct(ContainerInterface $container)
        {
            self::$oauth2 = $container->get('oauth2');
        }
    
        /**
         * @return mixed|oauth2
         */
        public static function getConnection()
        {
            return self::$oauth2;
        }
    
        /**
         * @return \OAuth2\Response
         */
        public static function verifyToken(): \OAuth2\Response
        {
            
            //Override the tokentype with our own custom bearer so we can use json content type in requests..
            $bearer = new CustomBearer([
                'token_param_name' => 'access_token',
                'token_bearer_header_name' => 'Bearer'
            ]);
            $server = new \OAuth2\Server(self::$oauth2, [], [], [], $bearer);
            $response = new \OAuth2\Response();

            if (!$token = $server->verifyResourceRequest(\OAuth2\Request::createFromGlobals(), $response)) {
                $response->send();
                die;
            }
            
            return $response;
        }
    
        /**
         * @return mixed|string|null
         */
        public static function generateToken()
        {
            $server = new \OAuth2\Server(self::$oauth2);
            $server->addGrantType(new \OAuth2\GrantType\ClientCredentials(self::$oauth2));
            $token = $server->handleTokenRequest(\OAuth2\Request::createFromGlobals());
            return $token->getResponseBody('json') ?? null;
        }
    
        /**
         *
         */
        public static function getToken()
        {
            // Pass a storage object or array of storage objects to the OAuth2 server class
            $server = new \OAuth2\Server(self::$oauth2);
            
            // Add the "Client Credentials" grant type (it is the simplest of the grant types)
            $server->addGrantType(new \OAuth2\GrantType\ClientCredentials(self::$oauth2));
            
            //Handle a request for an OAuth2.0 Access Token and send the response to the client
            $server->handleTokenRequest(\OAuth2\Request::createFromGlobals())->send();
            
            die;
        }
    }