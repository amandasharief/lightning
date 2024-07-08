<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Event;

use PHPUnit\Framework\TestCase;
use Lightning\EventDispatcher\EventDispatcher;
use Psr\EventDispatcher\ListenerProviderInterface;
use Lightning\EventDispatcher\AbstractStoppableEvent;

class Event extends AbstractStoppableEvent
{
    public function __construct(private ?string $message = null)
    {
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}

class TestListenerProvider implements ListenerProviderInterface
{
    protected array $listeners = [];

    public function add(string $eventName, callable $callable): static
    {
        $this->listeners[$eventName][] = $callable;

        return $this;
    }

    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners[$event::class] ?? [];
    }
}

final class EventDispatcherTest extends TestCase
{
    public function testDispatch(): void
    {
        $provider = new TestListenerProvider();
        $eventDispatcher = new EventDispatcher(($provider));
        $event = new Event();

        $provider->add(Event::class, function (Event $event) {
            $this->assertTrue(true);
        });

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
    }

    public function testDispatchMultiple(): void
    {
        $provider = new TestListenerProvider();
        $eventDispatcher = new EventDispatcher(($provider));
        $event = new Event();

        $provider->add(Event::class, function (Event $event) {
            $this->assertTrue(true);
        });

        $provider->add(Event::class, function (Event $event) {
            $this->assertTrue(true);
        });

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
    }

    public function testDispatchStopEvent(): void
    {
        $provider = new TestListenerProvider();
        $eventDispatcher = new EventDispatcher(($provider));
        $event = new Event();

        $provider->add(Event::class, function (Event $event) {
            $this->assertTrue(true);
            $event->stopPropagation();
        });

        // Fire another event with a fail to catch issues
        $provider->add(Event::class, function (Event $event) {
            $this->assertTrue(false);
        });

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
    }

    public function testGetListenerProvider(): void
    {
        $eventDispatcher = new EventDispatcher(new TestListenerProvider());
        $this->assertInstanceOf(ListenerProviderInterface::class, $eventDispatcher->getListenerProvider());
    }

    public function testConfigure(): void
    {
        $provider = new TestListenerProvider();
        $eventDispatcher = new EventDispatcher(($provider));
        $event = new Event();

        $result = $eventDispatcher->configure(function (TestListenerProvider $provider) {
            $provider->add(Event::class, [$this, 'onEvent']);
        });

        $this->assertInstanceOf(EventDispatcher::class, $result);

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
        $this->assertEquals('ok', $event->getMessage());
    }

    public function onEvent(Event $event): void
    {
        $event->setMessage('ok');
    }
}
