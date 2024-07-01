<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Helper\InputHelper;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

class Formatter
{
    public function format(string $format, mixed ...$args): string
    {
        return sprintf($format, ...$args);
    }
}

class Helper
{
    public function __construct(public Formatter $formatter)
    {
    }

    public function output(string $message, mixed ...$args): static
    {
        $output = $this->formatter->format($message);

        return $this;
    }
}

final class InputHelperTest extends TestCase
{
    private OutputStreamStub $out;
    private OutputStreamStub $err;
    private InputStreamStub $in;

    public function setUp(): void
    {
        $this->out = new OutputStreamStub('php://memory');
        $this->err = new OutputStreamStub('php://memory');
        $this->in = new InputStreamStub('php://memory');
    }

    public function testAsk(): void
    {
        $helper = new InputHelper(new Console($this->out, $this->err, $this->in));

        $this->in->setInput(['bar']);
        $this->assertEquals('bar', $helper->ask('What is foo?'));
        $expected = "What is foo?\n> ";
        //dd($this->out->getContents());
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testAskWithDefault(): void
    {
        $helper = new InputHelper(new Console($this->out, $this->err, $this->in));

        $this->in->setInput(['']);
        $this->assertEquals('default', $helper->ask('What is foo?', 'default'));
        $expected = "What is foo? [default]\n> ";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testChoice(): void
    {
        $helper = new InputHelper(new Console($this->out, $this->err, $this->in));

        $this->in->setInput(['bar']);
        $this->assertEquals('bar', $helper->askChoice('What is foo?', ['foo','bar']));
        $expected = "What is foo? (foo/bar)\n> ";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testChoiceWithDefault(): void
    {
        $helper = new InputHelper(new Console($this->out, $this->err, $this->in));

        $this->in->setInput(['bar']);
        $this->assertEquals('bar', $helper->askChoice('What is foo?', ['foo','bar'], 'foo'));
        $expected = "What is foo? (foo/bar) [foo]\n> ";

        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    /**
     * @todo spent hours had phpunit errors, this needs to be tested properly
     */
    public function testAskSecret(): void
    {
        $helper = new InputHelper(new Console($this->out, $this->err, $this->in));

        $this->in->setInput(['bar']);
        $this->assertEquals('bar', $helper->askSecret('What is your password?'));
        $expected = "What is your password?\n>";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }
}
