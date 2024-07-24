<?php declare(strict_types=1);

namespace Lightning\Test\Router;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Lightning\Router\RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ControllerMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write($request->getUri()->getPath());

        return $response;
    }
}

class BeforeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = new ServerRequest('GET', '/login');

        return $handler->handle($request);
    }
}

class AfterMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response->getBody()->write('AFTER');

        return $response;
    }
}

final class RequestHandlerTest extends TestCase
{
    public function testHandle(): void
    {
        $request = new ServerRequest('GET', '/home');
        $requestHandler = new RequestHandler([new ControllerMiddleware()]);
        $response = $requestHandler->handle($request);
        $this->assertEquals('/home', $response->getBody()->__toString());
    }

    public function testWithBeforeMiddleware(): void
    {
        $request = new ServerRequest('GET', '/home');
        $requestHandler = new RequestHandler([new BeforeMiddleware(), new ControllerMiddleware()]);
        $response = $requestHandler->handle($request);
        $this->assertEquals('/login', $response->getBody()->__toString());
    }

    public function testWithAfterMiddleware(): void
    {
        $request = new ServerRequest('GET', '/home');
        $requestHandler = new RequestHandler([new AfterMiddleware(), new ControllerMiddleware()]);
        $response = $requestHandler->handle($request);
        $this->assertEquals('/homeAFTER', $response->getBody()->__toString());
    }

    public function testWithBeforeAndAfterMiddleware(): void
    {
        $request = new ServerRequest('GET', '/home');
        $requestHandler = new RequestHandler([new BeforeMiddleware(), new AfterMiddleware(), new ControllerMiddleware()]);
        $response = $requestHandler->handle($request);
        $this->assertEquals('/loginAFTER', $response->getBody()->__toString());
    }
}
