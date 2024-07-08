<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Event;

use PHPUnit\Framework\TestCase;
use Lightning\EventDispatcher\AbstractStoppableEvent;

class TestEvent extends AbstractStoppableEvent
{
}

class TestAbstractStoppableEvent extends TestCase
{
    public function testStopPropagation(): void
    {
        $event = new TestEvent();
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }
}
