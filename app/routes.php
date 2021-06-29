<?php
    declare(strict_types=1);

    use Psr\Container\ContainerInterface;
    use Psr\Http\Message\ResponseInterface as Response;
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Slim\App;
    use Slim\Interfaces\RouteCollectorProxyInterface as Group;

    return function (App $app) {
        
        $app->get('/', function (Request $request, Response $response) {
            $response->getBody()->write('OK');
            return $response;
        });

    };
