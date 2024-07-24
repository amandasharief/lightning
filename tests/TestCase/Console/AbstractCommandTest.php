<?php declare(strict_types=1);

namespace Lightning\Test\Console;

use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Arguments;
use Lightning\Console\ConsoleArgumentParser;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\AbstractCommand as Command;
use Lightning\Console\TestSuite\OutputStreamStub;

class NameCommand extends Command
{
    protected string $name = 'name';

    protected function execute(Arguments $args): int
    {
        $console = $this->getConsole();
        $console->out(sprintf('Hello %s', $console->in('you')));

        return Command::SUCCESS;
    }
}

class HelloCommand extends Command
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
        $this->addOption('exit', [
            'type' => 'boolean'
        ]);
    }

    public function getParser(): ConsoleArgumentParser
    {
        return $this->parser;
    }

    protected function execute(Arguments $args): int
    {
        $console = $this->getConsole();

        if ($args->getOption('abort')) {
            $this->abort();
        }

        if ($args->getOption('exit')) {
            $this->exit();
        }

        // test Add argument and addOption
        $name = $args->getArgument('name');
        if ($args->getOption('u')) {
            $name = strtoupper($name);
        }

        $console->out(sprintf('Hello %s', $args->getArgument('name')));

        return Command::SUCCESS;
    }
}

final class AbstractCommandTest extends TestCase
{
    private ?OutputStreamStub $stdout = null;
    private ?OutputStreamStub $stderr = null;
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
        $this->assertEquals(Command::SUCCESS, $command->run(['bin/console', '-h'], $this->console));

        $this->assertStringContainsString(
            "\e[32m-u,--uppercase \e[0mchange name to uppercase\n",
            $this->stdout->getContents()
        );
    }

    public function testAddArgument(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals(Command::SUCCESS, $command->run(['bin/console', '-h'], $this->console));

        $this->assertStringContainsString(
            "\e[32mname           \e[0mname to use (default: \"world\")\n",
            $this->stdout->getContents()
        );
    }

    public function testExit(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals(Command::SUCCESS, $command->run(['bin/console', '-exit'], $this->console));
    }

    public function testAbort(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals(Command::ERROR, $command->run(['bin/console', '-abort'], $this->console));
    }

    public function testRun(): void
    {
        $command = new HelloCommand($this->console);

        $this->assertEquals(Command::SUCCESS, $command->run(['bin/console'], $this->console));
        $this->assertStringContainsString('Hello world', $this->stdout->getContents());
    }

    public function testRunCatchStopException(): void
    {
        $command = new HelloCommand($this->console);
        $this->assertEquals(Command::ERROR, $command->run(['bin/console','--abort'], $this->console));
    }

    public function testDisplayHelp(): void
    {
        $command = new HelloCommand($this->console);
        $command->run(['bin/console', '-h'], $this->console);

        $expected = "hello world\n\n\e[33mUsage:\e[0m\n  hello [options] [name]\n\n\e[33mArguments:\e[0m\n  \e[32mname           \e[0mname to use (default: \"world\")\n\n\e[33mOptions:\e[0m\n  \e[32m-h,--help      \e[0mDisplays this help message\n  \e[32m-u,--uppercase \e[0mchange name to uppercase\n  \e[32m--abort        \e[0m\n  \e[32m--exit         \e[0m\n\n";

        $this->assertEquals($expected, $this->stdout->getContents());
    }
}
