<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\ANSI;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Formatter\OutputFormatter;

final class OutputFormatterTest extends TestCase
{
    public function testEnableDisableansi(): void
    {
        $formatter = new OutputFormatter();
        $this->assertTrue($formatter->isAnsiEnabled());
        $this->assertFalse($formatter->enableAnsi(false)->isAnsiEnabled());
    }

    public function testFormat(): void
    {
        $formatter = new OutputFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
        $this->assertEquals('Hello Amanda', $formatter->format('Hello {name}', ['name' => 'Amanda']));
        $this->assertEquals('Day 0', $formatter->format('Day {day}', ['day' => 0]));
    }

    public function testFormatWithAnsi(): void
    {
        $formatter = new OutputFormatter();
        $this->assertEquals(ANSI::FG_BLUE . 'Title' . ANSI::RESET, $formatter->format(ANSI::FG_BLUE . 'Title' . ANSI::RESET));
    }

    public function testFormatWithAnsiStripped(): void
    {
        $formatter = (new OutputFormatter())->enableAnsi(false);

        $this->assertEquals('Title', $formatter->format(ANSI::FG_BLUE . 'Title' . ANSI::RESET));
        $this->assertEquals('Title', $formatter->format(ANSI::FG_BLUE . ANSI::BOLD . ANSI::BG_CYAN . 'Title' . ANSI::RESET));
    }
}
