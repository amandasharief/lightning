<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

final class ConsoleTest extends TestCase
{
    private OutputStreamStub $out;
    private OutputStreamStub $err;
    private InputStreamStub  $in;

    public function setUp(): void
    {
        $this->out = new OutputStreamStub('php://memory');
        $this->err = new OutputStreamStub('php://memory');
        $this->in = new InputStreamStub('php://memory');
    }

    public function testOut(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $console->out('This is a test');
        $this->assertStringContainsString('This is a test', $this->out->getContents());
        $this->assertStringNotContainsString('This is a test', $this->err->getContents());
    }

    public function testOutWithArgs(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $console->out('Hello %s', 'Amanda');
        $this->assertStringContainsString('Hello Amanda', $this->out->getContents());
        $this->assertStringNotContainsString('Hello Amanda', $this->err->getContents());
    }

    public function testError(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $console->error('This is a test');
        $this->assertStringContainsString('This is a test', $this->err->getContents());
        $this->assertStringNotContainsString('This is a test', $this->out->getContents());
    }

    public function testErrorWithArgs(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $console->error('Hello %s', 'Amanda');
        $this->assertStringContainsString('Hello Amanda', $this->err->getContents());
        $this->assertStringNotContainsString('Hello Amanda', $this->out->getContents());
    }

    /**
     * @todo no idea how to test stty -echo without messing arounbd
     */
    public function testReadPassword(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $this->in->setInput(['secret']);
        $this->assertEquals('secret', $console->readPassword());
    }

     /**
     * @todo no idea how to test stty -echo without messing arounbd
     */
    public function testReadPasswordWithMessage(): void
    {
        $console = new Console($this->out, $this->err, $this->in);
        $this->in->setInput(['secret']);
        $this->assertEquals('secret', $console->readPassword('Enter a password:'));
        $this->assertStringContainsString('Enter a password:', $this->out->getContents());
        $this->assertStringNotContainsString('Enter a password:', $this->err->getContents());
    }
}
