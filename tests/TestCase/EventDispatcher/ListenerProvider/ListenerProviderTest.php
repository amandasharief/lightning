<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Event\ListenerProvider;

use ReflectionProperty;
use PHPUnit\Framework\TestCase;
use Lightning\EventDispatcher\ListenerProvider\ListenerProvider;

class Event
{
}

class EventListener
{
    public function __invoke(Event $event)
    {
    }
}

class ListenerProviderTest extends TestCase
{
    public function testAddListener()
    {
        $provider = new ListenerProvider();
        $listener = new EventListener();

        $this->assertInstanceOf(ListenerProvider::class, $provider->add(Event::class, $listener));

        $prop = new ReflectionProperty($provider, 'listeners');
        $this->assertEquals([Event::class => [$listener]], $prop->getValue($provider));

        $provider->add(Event::class, function (Event $event) {
        });
        $this->assertCount(2, $prop->getValue($provider)[Event::class]);
    }

    /**
     * @depends testAddListener
     */
    public function testGetListenersForEvent()
    {
        $provider = new ListenerProvider();
        $listener = new EventListener();
        $event = new Event();

        $this->assertEmpty($provider->getListenersForEvent($event));

        $provider->add(Event::class, $listener);

        $this->assertEquals([$listener], $provider->getListenersForEvent($event));
    }

    /**
     * @depends testGetListenersForEvent
     */
    public function testRemoveListener()
    {
        $provider = new ListenerProvider();
        $listener = new EventListener();
        $event = new Event();

        $anotherListener = function (Event $event) {
            echo 'hello';
        };

        // test remove not found
        $this->assertInstanceOf(ListenerProvider::class, $provider->remove(Event::class, function (Event $event) {
            return 'foo';
        }));

        $provider->add(Event::class, $listener)->add(Event::class, $anotherListener);
        $this->assertCount(2, $provider->getListenersForEvent($event));

        $this->assertCount(
            1, $provider->remove(Event::class, $listener)->getListenersForEvent($event)
        );

        // test remove closure
        $this->assertCount(
            0, $provider->remove(Event::class, $anotherListener)->getListenersForEvent($event)
        );
    }
}
