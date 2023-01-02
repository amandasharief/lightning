<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use PHPUnit\Framework\TestCase;
use Lightning\Console\InputStream;

final class InputStreamTest extends TestCase
{
    public function testRead(): void
    {
        $tmp = tempnam('/tmp', 'test');
        $stream = new InputStream($tmp);

        $fp = fopen($tmp, 'w');
        fwrite($fp, 'foo');
        $this->assertEquals('foo', $stream->read());
        fclose($fp);
    }

    public function testReadNoResult(): void
    {
        $stream = new InputStream('php://memory');
        $this->assertNull($stream->read());
    }
}
