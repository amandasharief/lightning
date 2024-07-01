<?php declare(strict_types=1);

namespace Lightning\Test\Console;

use Lightning\Console\ANSI;
use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Arguments;
use Lightning\Console\AbstractCommand;
use Lightning\Console\ConsoleApplication;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

class FooCommand extends AbstractCommand
{
    protected string $name = 'foo';
    protected string $description = 'foo command';
    protected function initialize(): void
    {
        $this->addArgument('name', [
            'description' => 'name to use'
        ]);
    }
    protected function execute(Arguments $args)
    {
        $console = $this->getConsole();

        $console->out('foo:' .  $args->getArgument('name', 'none'));
    }
}

class BarCommand extends AbstractCommand
{
    protected string $name = 'bar';
    protected string $description = 'bar command';
    protected function execute(Arguments $args)
    {
        $console = $this->getConsole();
        $console->out('bar');
    }
}

final class ConsoleApplicationTest extends TestCase
{
    private ?OutputStreamStub $stdout = null;
    private ?OutputStreamStub  $stderr = null;
    private ?InputStreamStub $stdin = null;
    private ?Console $console = null;

    public function setUp(): void
    {
        $this->stdout = new OutputStreamStub('php://memory');
        $this->stderr = new OutputStreamStub('php://memory');
        $this->stdin = new InputStreamStub('php://memory');
        $this->console = new Console($this->stdout, $this->stderr, $this->stdin);
    }

    public function testGetName(): void
    {
        $app = new ConsoleApplication($this->console);
        $this->assertEquals('unkown', $app->getName());
    }

    /**
     * @depends testGetName
     */
    public function testSetName(): void
    {
        $app = new ConsoleApplication($this->console);
        $this->assertInstanceOf(ConsoleApplication::class, $app->setName('foo'));

        $this->assertEquals('foo', $app->getName());
    }

    public function testGetDescription(): void
    {
        $app = new ConsoleApplication($this->console);
        $this->assertEquals('', $app->getDescription());
    }

    /**
     * @depends testGetDescription
     */
    public function testSetDescription(): void
    {
        $app = new ConsoleApplication($this->console);
        $this->assertInstanceOf(ConsoleApplication::class, $app->setDescription('foo'));
        $this->assertEquals('foo', $app->getDescription());
    }

    public function testAdd(): void
    {
        $app = new ConsoleApplication($this->console);
        $this->assertInstanceOf(ConsoleApplication::class, $app->add(new FooCommand($this->console)));
    }

    /**
        * @depends testAdd
        */
    public function testDisplayHelp(): void
    {
        $app = new ConsoleApplication($this->console);
        $app->add(new FooCommand($this->console));
        $app->add(new BarCommand($this->console));
        $this->assertEquals(0, $app->run(['bin/foo'], $this->console));

        $yellow = ANSI::FG_YELLOW;
        $reset = ANSI::RESET;
        $green = ANSI::FG_GREEN;

        $expected = "{$yellow}Usage:{$reset}\n  unkown <command> [options] [arguments]\n\n{$yellow}Commands:{$reset}\n  {$green}foo     {$reset}foo command\n  {$green}bar     {$reset}bar command\n\n";

        $this->assertEquals($expected, $this->stdout->getContents());
    }

    /**
     * @depends testAdd
     */
    public function testRun(): void
    {
        $app = new ConsoleApplication($this->console);
        $app->add(new FooCommand($this->console));
        $this->assertEquals(0, $app->run(['bin/foo','foo'], $this->console));
        $this->assertStringContainsString('foo:none', $this->stdout->getContents());
    }

    /**
    * @depends testAdd
    */
    public function testRunWithArgs(): void
    {
        $app = new ConsoleApplication($this->console);
        $app->add(new FooCommand($this->console));
        $this->assertEquals(0, $app->run(['bin/foo','foo','bar'], $this->console));
        $this->assertStringContainsString('foo:bar', $this->stdout->getContents());
    }
}
