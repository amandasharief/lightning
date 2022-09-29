<?php declare(strict_types=1);

namespace Lightning\Test\Http\Exception;

use Exception;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Lightning\Http\Exception\NotFoundException;
use Lightning\Http\ExceptionHandler\ErrorRenderer;
use Lightning\Http\Exception\NotImplementedException;
use Lightning\Http\ExceptionHandler\ExceptionHandlerMiddleware;

class MockRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $target = $request->getRequestTarget();

        if ($target === '/404' || $target === '/api/404') {
            throw new NotFoundException();
        }

        if ($target === '/501') {
            throw new NotImplementedException();
        }

        if ($target === '/other') {
            throw new Exception('Foo is not bar');
        }

        return new Response();
    }
}

final class ExceptionHandlerMiddlewareTest extends TestCase
{
    private string $path;

    public function setUp(): void
    {
        $this->path = __DIR__ .'/template/';
    }

    public function testItWorks(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
        $request = new ServerRequest('GET', '/not-important');
        $request = $request->withHeader('Accept','text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8');

        $response = $middleware->process($request, new MockRequestHandler());

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test400ErrorHtml(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
    
        $request = new ServerRequest('GET', '/404');
        $request = $request->withHeader('Accept','text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8');
        $response = $middleware->process($request, new MockRequestHandler());

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-type'));
        $this->assertEquals(
            '{"error":{"code":404,"message":"Not Found","hasRequest":true,"hasException":true}}',
           (string) $response->getBody()
        );
    }

    public function test501ErrorHtml(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
    
        $request = new ServerRequest('GET', '/501');
        $request = $request->withHeader('Accept','text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8');
        $response = $middleware->process($request, new MockRequestHandler());


        $this->assertEquals(501, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-type'));
        $this->assertEquals(
            '{"error":{"code":501,"message":"Not Implemented","custom":true}}',
           (string) $response->getBody()
        );
    }

    public function test500ErrorHtml(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
    
        $request = new ServerRequest('GET', '/other');
        $request = $request->withHeader('Accept','text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8');
        $response = $middleware->process($request, new MockRequestHandler());

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-type'));
        $this->assertEquals(
            '{"error":{"code":500,"message":"Internal Server Error","custom":true}}',
           (string) $response->getBody()
        );
    }

    public function testJsonError(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
        $request = new ServerRequest('GET', '/404');
        $request = $request->withAddedHeader('Accept', 'application/json');

        $response = $middleware->process($request, new MockRequestHandler());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-type'));
        $this->assertEquals(
            '{"error":{"code":404,"message":"Not Found"}}',
           (string) $response->getBody()
        );
    }

    public function testJsonErrorDetectRest(): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
        $request = new ServerRequest('GET', '/api/404');

        $response = $middleware->process($request, new MockRequestHandler());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-type'));
        $this->assertEquals(
            '{"error":{"code":404,"message":"Not Found"}}',
           (string) $response->getBody()
        );
    }

    public function xmlTypeProvider(): array
    {
        return [
            ['text/xml'],
            ['application/xml']

        ];
    }

    /**
     * @dataProvider xmlTypeProvider
     *
     * @return void
     */
    public function testXmlError(string $contentType): void
    {
        $middleware = new ExceptionHandlerMiddleware($this->path, new ErrorRenderer(), new Psr17Factory());
        $request = new ServerRequest('GET', '/404');
        $request = $request->withAddedHeader('Accept', $contentType);

        $response = $middleware->process($request, new MockRequestHandler());
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/xml', $response->getHeaderLine('Content-type'));

        $expected = <<< XML
        <?xml version="1.0" encoding="UTF-8"?>
        <error>
           <code>404</code>
           <message>Not Found</message>
        </error>
        XML;

        $this->assertEquals(
            $expected,
           (string) $response->getBody()
        );
    }
}
