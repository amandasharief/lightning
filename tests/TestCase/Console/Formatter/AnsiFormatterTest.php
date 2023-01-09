<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\ANSI;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Formatter\AnsiFormatter;

final class AnsiFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new AnsiFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
        $this->assertEquals('Hello Amanda', $formatter->format('Hello {name}', ['name' => 'Amanda']));
        $this->assertEquals('Day 0', $formatter->format('Day {day}', ['day' => 0]));
    }

    public function testFormatNotArgs(): void
    {
        $formatter = new AnsiFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
    }

    public function testNoAnsi(): void
    {
        $this->assertInstanceOf(AnsiFormatter::class, (new AnsiFormatter())->noAnsi());
    }

    public function testFormatAnsi(): void
    {
        $formatter = new AnsiFormatter();

        $this->assertEquals(
            sprintf('Hello %sAmanda%s', ANSI::FG_GREEN, ANSI::RESET),
            $formatter->format(sprintf('Hello %s{name}%s', ANSI::FG_GREEN, ANSI::RESET), ['name' => 'Amanda'])
        );
    }

    public function testFormatNoAnsi(): void
    {
        $formatter = (new AnsiFormatter())->noAnsi();
        $this->assertEquals(
            'Hello Amanda',
            $formatter->format(sprintf('Hello %s{name}%s', ANSI::FG_GREEN, ANSI::RESET), ['name' => 'Amanda'])

        );
    }
}
