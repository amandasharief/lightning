<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use RuntimeException;
use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Arguments;
use Lightning\Console\AbstractCommand;

use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;
use Lightning\Console\TestSuite\ConsoleIntegrationTestTrait;

class DummyCommand extends AbstractCommand
{
    protected string $name = 'hello';
    protected string $description = 'hello world';

    public function __construct(Console $console, private ?object $object = null)
    {
        parent::__construct($console);
    }

    protected function initialize(): void
    {
        $this->addOption('display', [
            'default' => 'hello world',
            'type' => 'string'
        ]);

        $this->addOption('abort', [
            'type' => 'boolean'
        ]);
    }

    protected function execute(Arguments $args)
    {
        $console = $this->getConsole();

        if ($this->object) {
            $console->out('object:' . get_class($this->object));
        }

        if ($args->hasOption('display')) {
            $console->out($args->getOption('display'));
        }

        if ($args->getOption('abort')) {
            $this->abort(3);
        }

        $console->out('Hello world');
        $console->error('an error has occured');

        return self::SUCCESS;
    }
}

final class ConsoleIntegrationTestTraitTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    public function testGetConsoleException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Integration testing not Setup');
        $this->getTestConsole();
    }

    public function testGetTestConsole(): void
    {
        $console = $this->createTestConsole();
        $this->setupIntegrationTesting(new DummyCommand($console));

        $this->assertInstanceOf(OutputStreamStub::class, $this->stdout);
        $this->assertInstanceOf(OutputStreamStub::class, $this->stderr);
        $this->assertInstanceOf(InputStreamStub::class, $this->stdin);
    }

    public function testExitSuccesss(): void
    {
        $command = new DummyCommand($this->createTestConsole());
        $this->setupIntegrationTesting($command);

        $this->assertTrue($this->execute());
        $this->assertExitSuccess();
    }

    public function testExitError(): void
    {
        $command = new DummyCommand($this->createTestConsole());
        $this->setupIntegrationTesting($command);

        $this->assertFalse($this->execute(['--abort']));
        $this->assertExitError();
    }

    public function testExitCode(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute(['--abort']);

        $this->assertExitCode(3);
    }

    public function testOutputContains(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertOutputContains('hello world');
    }

    /**
     * @depends testOutputContains
     */
    public function testOutputNotContains(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertOutputNotContains('This is a test');
    }

    public function testOutputEmpty(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->assertOutputEmpty();
    }

    public function testOutputNotEmpty(): void
    {
        $command = new DummyCommand($this->createTestConsole());
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertOutputNotEmpty();
    }

    public function testOutputMatchesRegularExpression(): void
    {
        $command = new DummyCommand($this->createTestConsole());
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertOutputMatchesRegularExpression('/hello world/');
    }

    public function testOutputDoesNotMatchRegularExpression(): void
    {
        $command = new DummyCommand($this->createTestConsole());
        $this->setupIntegrationTesting($command);

        $this->assertOutputDoesNotMatchRegularExpression('/hello world/');
    }

    public function testErrorContains(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertErrorContains('an error has occured');
    }

    /**
     * @depends testErrorContains
     */
    public function testErrorNotContains(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->assertErrorNotContains('an error has occured');
    }

    public function testErrorEmpty(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->assertErrorEmpty();
    }

    public function testErrorNotEmpty(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertErrorNotEmpty();
    }

    public function testErrorMatchesRegularExpression(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->execute();

        $this->assertErrorMatchesRegularExpression('/an error has occured/');
    }

    public function testErrorDoesNotMatchRegularExpression(): void
    {
        $console = $this->createTestConsole();
        $command = new DummyCommand($console);
        $this->setupIntegrationTesting($command);

        $this->assertErrorDoesNotMatchRegularExpression('/an error has occured/');
    }
}
