<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\ANSI;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Formatter\StyleFormatter;

final class StyleOutputFormatterTest extends TestCase
{
    public function testFormat(): void
    {
        $formatter = new StyleFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
        $this->assertEquals('Hello Amanda', $formatter->format('Hello {name}', ['name' => 'Amanda']));
        $this->assertEquals('Day 0', $formatter->format('Day {day}', ['day' => 0]));
    }

    public function testFormatNotArgs(): void
    {
        $formatter = new StyleFormatter();
        $this->assertEquals('Hello Amanda', $formatter->format('Hello Amanda'));
    }

    public function testFormatTags(): void
    {
        $formatter = new StyleFormatter();
        $this->assertEquals(sprintf('Hello %sAmanda%s', ANSI::FG_GREEN, ANSI::RESET), $formatter->format('Hello <green>Amanda</green>'));
    }

    public function testFormatTagsNoColorInPlain(): void
    {
        $formatter = (new StyleFormatter())->enableAnsi(false);
        $this->assertEquals(sprintf('Hello Amanda', ANSI::FG_GREEN, ANSI::RESET), $formatter->format('Hello <green>Amanda</green>'));
    }

    public function testFormatStripsAnsiTagsFromPlain(): void
    {
        $formatter = (new StyleFormatter())->enableAnsi(false);
        $this->assertEquals('Hello Amanda', $formatter->format(sprintf('Hello %sAmanda%s', ANSI::FG_GREEN, ANSI::RESET)));
    }
}
