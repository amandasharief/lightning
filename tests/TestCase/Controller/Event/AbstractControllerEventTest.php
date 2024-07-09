<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Controller\Event;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Lightning\Controller\AbstractController;
use Lightning\TemplateRenderer\TemplateRenderer;
use Lightning\Controller\Event\AbstractControllerEvent;

class Controller extends AbstractController
{
    public function createResponse(): ResponseInterface
    {
        return new Response();
    }
}

class AnotherController extends Controller
{
}

class Event extends AbstractControllerEvent
{
}

final class AbstractControllerEventTest extends TestCase
{
    public function testGetController(): void
    {
        $event = new Event(new Controller(new TemplateRenderer('/tmp')));
        $this->assertInstanceOf(AbstractController::class, $event->getController());
    }

    public function testGetResponse(): void
    {
        $event = new Event(new Controller(new TemplateRenderer('/tmp')));
        $this->assertNull($event->getResponse());

        $response = new Response();
        $event = new Event(new Controller(new TemplateRenderer('/tmp')), $response);

        $this->assertSame($response, $event->getResponse());
    }

    /**
     * @depends testGetResponse
     */
    public function testSetResponse(): void
    {
        $event = new Event(new Controller(new TemplateRenderer('/tmp')));

        $this->assertInstanceOf(Event::class, $event->setResponse(new Response()));
        $this->assertInstanceOf(ResponseInterface::class, $event->getResponse());
    }

    public function testStopPropagation(): void
    {
        $event = new Event(new Controller(new TemplateRenderer('/tmp')));
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}
