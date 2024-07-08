<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Event\ListenerProvider;

use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Lightning\EventDispatcher\ListenerProvider\PriorityListenerProvider;

class DummyEvent
{
}

class EventListener1
{
    public function __invoke(Event $event)
    {
    }
}

class EventListener2
{
    public function __invoke(Event $event)
    {
    }
}

class EventListener3
{
    public function __invoke(Event $event)
    {
    }
}

class EventListener4
{
    public function __invoke(Event $event)
    {
    }
}

class EventListener5
{
    public function __invoke(Event $event)
    {
    }
}

class PriorityListenerProviderTest extends TestCase
{
    public function testAddListener()
    {
        $provider = new PriorityListenerProvider();
        $listener = new EventListener();

        $this->assertInstanceOf(PriorityListenerProvider::class, $provider->add(DummyEvent::class, $listener));

        $prop = new ReflectionProperty($provider, 'listeners');
        $this->assertEquals([DummyEvent::class => [0 => [$listener]]], $prop->getValue($provider));

        $provider->add(DummyEvent::class, function (Event $event) {
        });
        $this->assertCount(2, $prop->getValue($provider)[DummyEvent::class][0]);
    }

    /**
     * @depends testAddListener
     */
    public function testGetListenersForEvent()
    {
        $provider = new PriorityListenerProvider();
        $listener = new EventListener();
        $event = new DummyEvent();

        $this->assertEmpty($provider->getListenersForEvent($event));

        $provider->add(DummyEvent::class, $listener);

        $this->assertEquals([$listener], $provider->getListenersForEvent($event));
    }

    /**
     * @depends testGetListenersForEvent
     */
    public function testRemoveListener()
    {
        $provider = new PriorityListenerProvider();
        $listener = new EventListener();
        $event = new DummyEvent();

        $anotherListener = function (Event $event) {
            echo 'hello';
        };

        // test remove not found
        $this->assertInstanceOf(PriorityListenerProvider::class, $provider->remove(DummyEvent::class, function (Event $event) {
            return 'foo';
        }));

        $provider->add(DummyEvent::class, $listener)->add(DummyEvent::class, $anotherListener);
        $this->assertCount(2, $provider->getListenersForEvent($event));

        $this->assertCount(
            1, $provider->remove(DummyEvent::class, $listener)->getListenersForEvent($event)
        );

        // test remove closure
        $this->assertCount(
            0, $provider->remove(DummyEvent::class, $anotherListener)->getListenersForEvent($event)
        );
    }

    /**
     * @depends testGetListenersForEvent
     */
    public function testAddListenerPriority()
    {
        $provider = new PriorityListenerProvider();

        $listener1 = new EventListener1();
        $listener2 = new EventListener2();
        $listener3 = new EventListener3();
        $listener4 = new EventListener4();
        $listener5 = new EventListener5();

        $provider->add(DummyEvent::class, $listener1, 0)
            ->add(DummyEvent::class, $listener2, -255)
            ->add(DummyEvent::class, $listener3, 255);

        $this->assertSame(
            [$listener3, $listener1, $listener2],
            $provider->getListenersForEvent(new DummyEvent())
        );

        // Test cleared sortedListeners
        $provider->add(DummyEvent::class, $listener4, -150);
        $this->assertSame(
            [$listener3, $listener1, $listener4, $listener2],
            $provider->getListenersForEvent(new DummyEvent())
        );

        $provider->add(DummyEvent::class, $listener5, 150);
        $this->assertSame(
            [$listener3, $listener5, $listener1, $listener4, $listener2],
            $provider->getListenersForEvent(new DummyEvent())
        );

        // Test cleared sorteed Listeners
        $provider->remove(DummyEvent::class, $listener4);
        $this->assertSame(
            [$listener3, $listener5, $listener1, $listener2],
            $provider->getListenersForEvent(new DummyEvent())
        );
    }
}
