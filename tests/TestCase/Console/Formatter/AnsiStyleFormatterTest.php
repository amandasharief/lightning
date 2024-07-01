<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\ANSI;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Formatter\AnsiStyleFormatter;

final class AnsiStyleFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new AnsiStyleFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
        $this->assertEquals('Hello Amanda', $formatter->format('Hello {name}', ['name' => 'Amanda']));
        $this->assertEquals('Day 0', $formatter->format('Day {day}', ['day' => 0]));
    }

    public function testFormatNotArgs(): void
    {
        $formatter = new AnsiStyleFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
    }

    public function testNoAnsi(): void
    {
        $this->assertInstanceOf(AnsiStyleFormatter::class, (new AnsiStyleFormatter())->noAnsi());
    }

    public function testFormatTags(): void
    {
        $formatter = new AnsiStyleFormatter();
        $this->assertEquals(sprintf('Hello %sAmanda%s', ANSI::FG_GREEN, ANSI::RESET), $formatter->format('Hello <green>Amanda</green>'));
    }

    public function testFormatTagsNoColorInPlain(): void
    {
        $formatter = (new AnsiStyleFormatter())->noAnsi();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello <green>Amanda</green>'));
    }
}
