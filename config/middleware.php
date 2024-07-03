<?php

use Lightning\Router\Router;
use Lightning\Http\Cookie\Cookies;
use Psr\Container\ContainerInterface;
use Lightning\Http\Session\SessionInterface;
use Lightning\Translator\TranslatorInterface;
use Lightning\Http\ExceptionHandler\ErrorRenderer;
use Lightning\Http\Cookie\Middleware\CookieMiddleware;
use Lightning\Http\Middleware\CsrfProtectionMiddleware;
use Lightning\Http\Session\Middleware\SessionMiddleware;
use Lightning\Translator\Middleware\LocaleSetterMiddleware;
use Lightning\Http\ExceptionHandler\ExceptionHandlerMiddleware;

return function (Router $router, ContainerInterface $container) {
    // $router->middleware(new ExceptionHandlerMiddleware(
    //     __DIR__ . '/../app/View/error', new ErrorRenderer(), new Psr17Factory(), $container->get(LoggerInterface::class)
    //     )
    // );
    $router->middleware(new SessionMiddleware($container->get(SessionInterface::class)));
    $router->middleware(new CookieMiddleware($container->get(Cookies::class)));
    $router->middleware(new LocaleSetterMiddleware($container->get(TranslatorInterface::class)));
    $router->middleware(new CsrfProtectionMiddleware($container->get(SessionInterface::class)));
};