<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Router\Middleware;

use Lightning\Router\ControllerInterface;
use Nyholm\Psr7\Response;
use Lightning\Router\Route;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lightning\Router\Middleware\InvokerMiddleware;
use Psr\Http\Message\RequestInterface;

class Foo
{
}

class PostsController
{
    public function index(ServerRequestInterface $serverRequestInterface): ResponseInterface
    {
        $response = new Response();

        $response->getBody()->write('ok');

        return $response;
    }
}
class ArticlesController implements ControllerInterface
{
    protected ?ResponseInterface $response = null;
    protected ServerRequestInterface $request;

    protected array $called = [];

    public function index(ServerRequestInterface $serverRequestInterface): ResponseInterface
    {
        $response = new Response();

        $response->getBody()->write('ok');

        return $response;
    }

    public function beforeFilter(ServerRequestInterface $request): ?ResponseInterface
    {
        $this->called[] = 'beforeFilter';

        return $this->response;
    }

    public function afterFilter(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->called[] = 'afterFilter';

        return $response;
    }

    public function getCalled(): array 
    {
        return $this->called;
    }

    public function setResponse(ResponseInterface $response) : void
    {
        $this->response = $response;
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
        $callable = [new PostsController(),'index'];
        $request = new ServerRequest('GET', '/posts/index');
       
        $response = (new InvokerMiddleware($callable))->process($request, new DummyRequestHandler($request));
        $this->assertEquals('ok', (string) $response->getBody());
    }

    public function testBeforeFilterAfterFilter(): void
    {
        $controller = new ArticlesController();
        $callable = [$controller,'index'];
        $request = new ServerRequest('GET', '/posts/index');
       
        $response = (new InvokerMiddleware($callable))->process($request, new DummyRequestHandler($request));
        $this->assertEquals('ok', (string) $response->getBody());

        $this->assertEquals(['beforeFilter','afterFilter'],$controller->getCalled());
    }

    public function testBeforeFilterReturnResponse(): void
    {
        $controller = new ArticlesController();
        $callable = [$controller,'index'];
        $request = new ServerRequest('GET', '/posts/index');

        $response =new Response();
        $response->getBody()->write('changed');
        $controller->setResponse($response);

       
        $response = (new InvokerMiddleware($callable))->process($request, new DummyRequestHandler($request));
        $this->assertEquals('changed', (string) $response->getBody());

        $this->assertEquals(['beforeFilter'],$controller->getCalled());
    }
  
}
