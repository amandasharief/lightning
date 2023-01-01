<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Cache;

use Lightning\Cache\NullCache;

use PHPUnit\Framework\TestCase;

final class NullCacheTest extends TestCase
{
    public function testGet(): void
    {
        $cache = new NullCache();
        $this->assertEquals('bar', $cache->get('foo', 'bar'));
    }

    public function testSet(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->set('foo', 'bar'));
    }

    public function testHas(): void
    {
        $cache = new NullCache();
        $cache->set('foo', 'bar');
        $this->assertFalse($cache->has('foo'));
    }

    public function testDelete(): void
    {
        $cache = new NullCache();
        $this->assertFalse($cache->delete('foo'));
    }

    public function testClear(): void
    {
        $cache = new NullCache();
        $this->assertTrue($cache->clear());
    }
}
