<?php declare(strict_types=1);

namespace Lightning\Test\ServiceObject;

use PHPUnit\Framework\TestCase;
use Lightning\ServiceObject\Result;

final class ResultTest extends TestCase
{
    public function testIsSuccess(): void
    {
        $this->assertTrue((new Result(true, []))->isSuccess());
        $this->assertFalse((new Result(false, []))->isSuccess());
    }

    public function testHasData(): void
    {
        $this->assertTrue((new Result(true, ['foo' => 'bar']))->hasData());
        $this->assertFalse((new Result(true, []))->hasData());
    }

    public function testGetData(): void
    {
        $result = new Result(true, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $result->getData());
        $this->assertEmpty((new Result(true, []))->getData());
    }

    public function testGet(): void
    {
        $result = new Result(true, ['foo' => 'bar']);
        $this->assertEquals('bar', $result->get('foo'));
        $this->assertNull($result->get('bar'));
    }

    public function testToString(): void
    {
        $this->assertEquals(
            '{"success":true,"data":{"foo":"bar"}}',
            (string) new Result(true, ['foo' => 'bar'])
        );
    }

    public function testJsonSerializeable(): void
    {
        $result = new Result(true, ['foo' => 'bar']);
        $this->assertEquals(
            ['success' => true,'data' => ['foo' => 'bar']],
            $result->jsonSerialize()
        );
    }

    public function testToArray(): void
    {
        $result = new Result(true, ['foo' => 'bar']);

        $this->assertEquals(
            [
                'success' => true,
                'data' => [
                    'foo' => 'bar'
                ]
            ],
            $result->toArray()
        );
    }
}
