<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use PHPUnit\Framework\TestCase;
use Lightning\Console\OutputStream;

final class OutputStreamTest extends TestCase
{
    public function testWrite(): void
    {
        $tmp = tempnam('/tmp', 'test');
        $stream = new OutputStream( $tmp );
        $fp = fopen($tmp, 'rw');

        $this->assertEquals(14, $stream->write('This is a test'));

        rewind($fp);
        $this->assertEquals('This is a test', stream_get_contents($fp));
        fclose($fp);
    }
}
