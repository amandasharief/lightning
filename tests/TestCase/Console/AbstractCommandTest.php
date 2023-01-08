<?php declare(strict_types=1);

namespace Lightning\Test\Console;

use Lightning\Console\ANSI;
use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Arguments;
use Lightning\Console\AbstractCommand;
use Lightning\Console\ConsoleArgumentParser;
use Lightning\Console\Exception\StopException;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

class NameCommand extends AbstractCommand
{
    protected string $name = 'name';

    protected function execute(Arguments $args): int
    {
        $console = $this->getConsole();
        $console->out(sprintf('Hello %s', $console->readLine('you')));

        return static::SUCCESS;
    }
}

class HelloCommand extends AbstractCommand
{
    protected string $name = 'hello';
    protected string $description = 'hello world';

    protected function initialize(): void
    {
        $this->addArgument('name', ['description' => 'name to use', 'default' => 'world']);
        $this->addOption('uppercase', ['description' => 'change name to uppercase', 'short' => 'u']);
        $this->addOption('abort', [
            'type' => 'boolean'
        ]);
    }

    public function getParser(): ConsoleArgumentParser
    {
        return $this->parser;
    }

    protected function execute(Arguments $args)
    {
        $console = $this->getConsole();

        if ($args->getOption('abort')) {
            $this->abort();
        }

        // test Add argument and addOption
        $name = $args->getArgument('name');
        if ($args->getOption('u')) {
            $name = strtoupper($name);
        }

        $console->out(sprintf('Hello %s', $args->getArgument('name')));
    }
}

final class AbstractCommandTest extends TestCase
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
        $command = new HelloCommand($this->console);
        $this->assertEquals('hello', $command->getName());
    }

    public function testGetDescription(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals('hello world', $command->getDescription());
    }

    public function testAddOption(): void
    {
        $command = new HelloCommand($this->console);
        $command->addOption('uppercase', ['description' => 'change name to uppercase', 'short' => 'u']);

        $this->assertEquals(
            'change name to uppercase',
            $command->getParser()->generateOptions()['-u,--uppercase']
        );
    }

    public function testAddArgument(): void
    {
        $command = new HelloCommand($this->console);
        $command->addArgument('name', ['description' => 'name to use', 'default' => 'world']);

        $this->assertEquals(
            'name to use (default: "world")',
            $command->getParser()->generateArguments()['name']
        );
    }

    public function testExit(): void
    {
        $command = new HelloCommand($this->console);

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('Command exited');
        $this->expectExceptionCode(AbstractCommand::SUCCESS);

        $command->exit();
    }

    public function testAbort(): void
    {
        $command = new HelloCommand($this->console);

        $this->expectException(StopException::class);
        $this->expectExceptionMessage('Command aborted');
        $this->expectExceptionCode(AbstractCommand::ERROR);

        $command->abort();
    }

    public function testRun(): void
    {
        $command = new HelloCommand($this->console);

        $this->assertEquals(AbstractCommand::SUCCESS, $command->run(['bin/console'], $this->console));
        $this->assertStringContainsString('Hello world', $this->stdout->getContents());
    }

    public function testRunCatchStopException(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals(AbstractCommand::ERROR, $command->run(['bin/console','--abort'], $this->console));
    }

    public function testDisplayHelp(): void
    {
        $command = new HelloCommand($this->console);
        $command->run(['bin/console', '-h'], $this->console);

        $yellow = ANSI::FG_YELLOW;
        $reset = ANSI::RESET;
        $green = ANSI::FG_GREEN;

        $expected = "hello world\n\n{$yellow}Usage:{$reset}\n  hello [options] [name]\n\n{$yellow}Arguments:{$reset}\n  {$green}name           {$reset}name to use (default: \"world\")\n\n{$yellow}Options:{$reset}\n  {$green}-h,--help      {$reset}Displays this help message\n  {$green}-u,--uppercase {$reset}change name to uppercase\n  {$green}--abort        {$reset}\n\n";

        $this->assertEquals($expected, $this->stdout->getContents());
    }
}
