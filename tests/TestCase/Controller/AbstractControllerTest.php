<?php declare(strict_types=1);

namespace Lightning\Test\Controller;

use Nyholm\Psr7\Response;
use InvalidArgumentException;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\Event\AfterRender;
use Lightning\Controller\AbstractController;
use Lightning\Controller\Event\BeforeRender;
use Lightning\Controller\Event\AfterRedirect;
use Lightning\Controller\Event\BeforeRedirect;
use Lightning\TemplateRenderer\TemplateRenderer;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Lightning\TemplateRenderer\TemplateRendererInterface;
use Lightning\EventDispatcher\ListenerProvider\ListenerProvider;
use Lightning\Test\TestCase\Controller\TestApp\ArticlesController;
use Lightning\Controller\EventDispatcherAwareInterface as ControllerEventDispatcherAwareInterface;

class ApiController extends AbstractController implements ControllerEventDispatcherAwareInterface
{
    public function __construct(
        protected TemplateRendererInterface $view,
        protected EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct($view, $eventDispatcher);
    }

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): static
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function dispatchEvent(object $event): object
    {
        return $this->eventDispatcher->dispatch($event);
    }

    public function index(): ResponseInterface
    {
        return $this->render('articles/index', [
            'title' => 'Articles'
        ]);
    }

    public function indexJson(): ResponseInterface
    {
        return $this->renderJson(['status' => 'ok']);
    }

    public function old(): ResponseInterface
    {
        return $this->redirect('/new');
    }

    public function download(): ResponseInterface
    {
        return $this->renderFile(__DIR__ . '/TestApp/downloads/sample.xml');
    }

    public function createResponse(): ResponseInterface
    {
        return new Response();
    }
}

final class TestEventDispatcher implements EventDispatcherInterface
{
    private array $calledEvents = [];

    public function __construct(private ListenerProvider $listenerProvider)
    {
    }
    public function dispatch(object $event): object
    {
        foreach ($this->listenerProvider->getListenersForEvent($event) as $listener) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
            $listener($event);
            $this->calledEvents[] = $event::class;
        }

        return $event;
    }

    public function getListenerProvider(): ListenerProvider
    {
        return $this->listenerProvider;
    }
    public function getCalledEvents(): array
    {
        return $this->calledEvents;
    }
}

final class AbstractControllerTest extends TestCase
{
    public function testGetTemplateRenderer(): void
    {
        $controller = $this->createController();
        $this->assertInstanceOf(TemplateRendererInterface::class, $controller->getTemplateRenderer());
    }

    public function testRender(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $response = $controller->index();

        $this->assertEquals('<h1>Articles</h1>', (string) $response->getBody());
        $this->assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenderJson(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $response = $controller->status(['ok']);

        $this->assertEquals('["ok"]', (string) $response->getBody());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRedirect(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $response = $controller->old('/articles/new');
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/articles/new', $response->getHeaderLine('Location'));
    }

    public function testRenderFile(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $path = __DIR__ . '/TestApp/downloads/sample.xml';
        $response = $controller->download($path);

        $this->assertEquals(
            file_get_contents($path), (string) $response->getBody()
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('74', $response->getHeaderLine('Content-Length'));
        $this->assertEquals('attachment; filename="sample.xml"', $response->getHeaderLine('Content-Disposition'));
    }

    public function testSendFileWithRelativePath(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`/var/www/../file` is a relative path');

        $controller->download('/var/www/../file');
    }

    public function testSendFileDoesNotExist(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('`/somewhere/somefile` does not exist or is not a file');

        $controller->download('/somewhere/somefile');
    }

    public function testSendFileNoDownload(): void
    {
        $request = new ServerRequest('GET', '/articles/index');
        $controller = $this->createController();

        $path = __DIR__ . '/TestApp/downloads/sample.xml';
        $response = $controller->download($path, ['download' => false]);

        $this->assertEquals(
            file_get_contents($path), (string) $response->getBody()
        );

        $this->assertEquals('text/xml', $response->getHeaderLine('Content-Type'));
        $this->assertEquals('74', $response->getHeaderLine('Content-Length'));
        $this->assertEmpty($response->getHeaderLine('Content-Disposition'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBeforeRender(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->index();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender", "Lightning\Controller\Event\AfterRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRenderReturnResponse(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
                $event->setResponse(new Response());
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->index();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRenderJson(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->indexJson();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender", "Lightning\Controller\Event\AfterRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRenderJsonReturnResponse(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
                $event->setResponse(new Response());
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->indexJson();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRenderFile(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->download();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender", "Lightning\Controller\Event\AfterRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRenderFileReturnResponse(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRender::class, function (BeforeRender $event) {
                $event->setResponse(new Response());
            })
            ->add(AfterRender::class, function (AfterRender $event) {
            });

        $controller->download();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRender"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRedirect(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRedirect::class, function (BeforeRedirect $event) {
            })
            ->add(AfterRedirect::class, function (AfterRedirect $event) {
            });

        $controller->old();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRedirect", "Lightning\Controller\Event\AfterRedirect"],
            $eventDispatcher->getCalledEvents()
        );
    }

    public function testBeforeRedirectReturnResponse(): void
    {
        $eventDispatcher = new TestEventDispatcher(new ListenerProvider());
        $controller = $controller = new ApiController(
            new TemplateRenderer(__DIR__ .'/TestApp/templates'),
            $eventDispatcher
        );

        $eventDispatcher->getListenerProvider()
            ->add(BeforeRedirect::class, function (BeforeRedirect $event) {
                $event->setResponse(new Response());
            })
            ->add(AfterRedirect::class, function (AfterRedirect $event) {
            });

        $controller->old();

        $this->assertEquals(
            ["Lightning\Controller\Event\BeforeRedirect"],
            $eventDispatcher->getCalledEvents()
        );
    }

    private function createController(?EventDispatcherInterface $eventDispatcher = null): ArticlesController
    {
        $path = __DIR__ .'/TestApp/templates';

        return new ArticlesController(
            new TemplateRenderer($path, ['cachePath' => sys_get_temp_dir() .'/tr_tests']),
            $eventDispatcher
        );
    }
}
