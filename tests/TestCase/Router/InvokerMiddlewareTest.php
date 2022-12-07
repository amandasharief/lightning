<?php declare(strict_types=1);

namespace Lightning\Test\Router;

use Nyholm\Psr7\Response;
use Lightning\Router\Route;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lightning\Router\Middleware\InvokerMiddleware;

class Foo
{
}

class PostsController
{
    protected ResponseInterface $response;
    protected ServerRequestInterface $request;

    public function index(ServerRequestInterface $serverRequestInterface): ResponseInterface
    {
        $response = new Response();

        $response->getBody()->write('ok');

        return $response;
    }
}

class DummyRequestHandler implements RequestHandlerInterface
{
    /**
    * Handles a request and produces a response.
    *
    * May call other collaborating code to generate the response.
    */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response(404, [], 'error');
    }
}

final class InvokerMiddlewareTest extends TestCase
{
    public function testProcess(): void
    {
        $route = new Route('get', '/articles/:id', [new PostsController(),'index']);
        $route->match('GET', '/articles/1234');

        $middleware = new InvokerMiddleware($route->getHandler());
        $request = new ServerRequest('GET', '/not-relevant');
        $response = $middleware->process($request, new DummyRequestHandler($request));
        $this->assertEquals('ok', (string) $response->getBody());
    }
}
