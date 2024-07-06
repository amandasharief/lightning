<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Event;

use Lightning\Event\Event;
use PHPUnit\Framework\TestCase;
use Lightning\Event\EventDispatcher;

final class EventDispatcherTest extends TestCase
{
    public function testAddListener(): void
    {
        $eventDispatcher = new EventDispatcher();

        $handler = function (Event $event) {
            $this->assertTrue(true);
        };

        $this->assertInstanceOf(EventDispatcher::class, $eventDispatcher->addListener(Event::class, $handler));
        $this->assertCount(1, $eventDispatcher->getListenersForEvent(new Event()));
    }

    public function testAddMultipleListener(): void
    {
        $eventDispatcher = new EventDispatcher();

        $this->assertInstanceOf(EventDispatcher::class, $eventDispatcher->addListener(Event::class, function (Event $event) {
            $this->assertTrue(true);
        }));


        $this->assertInstanceOf(EventDispatcher::class, $eventDispatcher->addListener(Event::class, function (Event $event) {
            $this->assertTrue(true);
        }));

        $this->assertCount(2, $eventDispatcher->getListenersForEvent(new Event()));
    }


    public function testGetListenersForEvent(): void
    {
        $eventDispatcher = new EventDispatcher();

        $handler = function (Event $event) {
            $this->assertTrue(true);
        };

        $this->assertEquals(
            [$handler],
            $eventDispatcher->addListener(Event::class, $handler)->getListenersForEvent(new Event())
        );
    }

    /**
     * @depends testAddListener
     */
    public function testRemoveListener(): void
    {
        $eventDispatcher = new EventDispatcher();

        $handler = function (Event $event) {
            $this->assertTrue(true);
        };

        $this->assertInstanceOf(EventDispatcher::class, $eventDispatcher->addListener(Event::class, $handler));
        $this->assertCount(1, $eventDispatcher->getListenersForEvent(new Event()));
        $this->assertInstanceOf(EventDispatcher::class, $eventDispatcher->removeListener(Event::class, $handler));
        $this->assertCount(0, $eventDispatcher->getListenersForEvent(new Event()));
    }

    public function testDispatch(): void
    {
        $eventDispatcher = new EventDispatcher();
        $event = new Event();

        $eventDispatcher->addListener(Event::class, function (Event $event) {
            $this->assertTrue(true);
        });

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
    }

    public function testDispatchStopEvent(): void
    {
        $eventDispatcher = new EventDispatcher();
        $event = new Event();

        $eventDispatcher->addListener(Event::class, function (Event $event) {
            $event->stopPropagation();
            $this->assertTrue(true);
        });

        $this->assertEquals($event, $eventDispatcher->dispatch($event));
    }
}
