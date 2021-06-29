<?php
    declare(strict_types=1);
    
    namespace App\Application\Middleware;
    
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Server\MiddlewareInterface as Middleware;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
    use App\Infrastructure\Environment;
    
    class TokenMiddleWare implements Middleware
    {
        /**
         * @param Request $request
         * @param RequestHandler $handler
         * @return Response
         */
        public function process(Request $request, RequestHandler $handler): Response
        {
            if (Environment::getValue("API_TOKEN_ENABLED", false)) {
                $apiToken = Environment::getValue("API_TOKEN", false);
                if (!$apiToken) {
                    throw new \Exception("Missing Api token in config");
                }
                
                $token = $this->getTokenFromRequest($request);
                if (!$token) {
                    header("HTTP/1.1 403 Forbidden");
                    exit;
                } elseif ($token !== $apiToken) {
                    header("HTTP/1.1 401 Unauthorized");
                    exit;
                }
            }
            return $handler->handle($request);
        }
        
        /**
         * @param Request $request
         * @return false|mixed|string
         */
        public function getTokenFromRequest(Request $request)
        {
            $token = $request->getHeader("api_token");
            if (!$token) {
                $params = $request->getQueryParams();
                $token = $params["api_token"] ?? false;
            } else {
                $token = $token[0];
            }
            return $token;
        }
    }