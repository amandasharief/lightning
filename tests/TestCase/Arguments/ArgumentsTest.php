<?php declare(strict_types=1);

namespace Lightning\Test\Arguments;

use PHPUnit\Framework\TestCase;
use Lightning\Arguments\Arguments;
use Lightning\Arguments\Exception\UnkownArgumentException;

final class ArgumentsTest extends TestCase
{
    public function testHas(): void
    {
        $Arguments = new Arguments(['foo' => 'bar']);
        $this->assertTrue($Arguments->has('foo'));
        $this->assertFalse($Arguments->has('bar'));
    }

    public function testGet(): void
    {
        $this->assertEquals(
            'bar',
            (new Arguments(['foo' => 'bar']))->get('foo')
        );
    }

    public function testGetException(): void
    {
        $this->expectException(UnkownArgumentException::class);
        $this->expectExceptionMessage('Unkown argument `foo`');
        (new Arguments())->get('foo');
    }

    public function testSet(): void
    {
        $Arguments = new Arguments();
        $this->assertInstanceOf(Arguments::class, $Arguments->set('foo', 'bar'));
        $this->assertEquals('bar', $Arguments->get('foo'));
    }

    public function testUnset(): void
    {
        $Arguments = new Arguments(['foo' => 'bar']);
        $this->assertEquals('bar', $Arguments->get('foo'));
        $this->assertInstanceOf(Arguments::class, $Arguments->unset('foo', 'bar'));

        $this->expectException(UnkownArgumentException::class);
        $this->expectExceptionMessage('Unkown argument `foo`');

        $Arguments->get('foo');
    }

    public function testGetArray(): void
    {
        $this->assertEquals(
            ['foo' => 'bar'],
            (new Arguments(['foo' => 'bar']))->toArray()
        );
    }
}
