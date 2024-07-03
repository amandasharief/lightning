<?php

use Lightning\Router\RoutesInterface;
use Psr\Http\Message\ServerRequestInterface;
use Lightning\Http\Exception\NotFoundException;
use Nyholm\Psr7\Response;

return function (RoutesInterface $routes) {
    $routes->get('/status', function (ServerRequestInterface $request) {
        $response =  new Response();
        $response->getBody()->write(json_encode(['status'=>'OK']));
        return $response;
    });
};
