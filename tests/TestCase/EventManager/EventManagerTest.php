<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\EventManager;

use PHPUnit\Framework\TestCase;
use Lightning\EventManager\EventManager;
use Psr\EventDispatcher\StoppableEventInterface;

class Event implements StoppableEventInterface
{
    public int $count = 0;
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}

class AnotherEvent extends Event
{
}

class MyListener
{
    public function __invoke(Event $event): void
    {
    }
}

class EventManagerTest extends TestCase
{
    public function testAddListener(): void
    {
        $eventManager = new EventManager();
        $this->assertInstanceOf(EventManager::class, $eventManager->addListener(Event::class, new MyListener()));
        $this->assertTrue($eventManager->hasListeners(Event::class));
    }

    /**
     * @depends testAddListener
     */
    public function testRemoveListener(): void
    {
        $eventManager = new EventManager();
        $eventManager->addListener(Event::class, new MyListener());
        $this->assertInstanceOf(EventManager::class, $eventManager->removeListener(Event::class, new MyListener()));
        $this->assertFalse($eventManager->hasListeners(Event::class));
    }

    public function testHasListeners(): void
    {
        $eventManager = new EventManager();
        $this->assertFalse($eventManager->hasListeners(Event::class));
        $this->assertTrue($eventManager->addListener(Event::class, new MyListener())->hasListeners(Event::class));
    }

    public function testDispatch(): void
    {
        $eventManager = new EventManager();
        $event = new Event();

        $eventManager->addListener(Event::class, function (Event $event) {
            $event->count++;
        });

        $this->assertEquals($event, $eventManager->dispatch($event));
        $this->assertEquals(1, $event->count);
    }

    public function testDispatchMultiple(): void
    {
        $eventManager = new EventManager();
        $event = new Event();

        $eventManager->addListener(Event::class, function (Event $event) {
            $event->count++;
        });
        $eventManager->addListener(Event::class, function (Event $event) {
            $event->count++;
        });

        $this->assertEquals($event, $eventManager->dispatch($event));
        $this->assertEquals(2, $event->count);
    }

    public function testDispatchStopEvent(): void
    {
        $eventManager = new EventManager();
        $event = new Event();

        $eventManager->addListener(Event::class, function (Event $event) {
            $event->count++;
        });
        $eventManager->addListener(Event::class, function (Event $event) {
            $event->stopPropagation();
            $event->count++;
        });
        $eventManager->addListener(Event::class, function (Event $event) {
            $event->count++;
        });

        $this->assertEquals($event, $eventManager->dispatch($event));
        $this->assertEquals(2, $event->count);
    }
}
