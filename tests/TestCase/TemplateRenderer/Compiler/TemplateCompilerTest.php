<?php declare(strict_types=1);

namespace Lightning\Test\TemplateRender\Compiler;

use PHPUnit\Framework\TestCase;
use Lightning\TemplateRenderer\Compiler\TemplateCompiler;

final class TemplateCompilerTest extends TestCase
{
    public function testEcho(): void
    {
        $this->assertStringContainsString(
            '<?= $this->escape($fruit) ?>', $this->getCompiledContent()
        );
    }

    public function testFor(): void
    {
        $compiled = $this->getCompiledContent();

        $this->assertStringContainsString('<?php for ($i = 1; $i <= 10; $i++): ?>', $compiled);
        $this->assertStringContainsString('<?php endfor; ?>', $compiled);
    }

    public function testForEach(): void
    {
        $compiled = $this->getCompiledContent();

        $this->assertStringContainsString('<?php foreach ([\'apples\',\'orange\',\'kiwi\'] as $fruit): ?>', $compiled);
        $this->assertStringContainsString('<?php endforeach; ?>', $compiled);
    }

    public function testIf(): void
    {
        $compiled = $this->getCompiledContent();

        $this->assertStringContainsString('<?php if (count($records) > 1): ?>', $compiled);
        $this->assertStringContainsString('<?php elseif (count($records) === 1): ?>', $compiled);
        $this->assertStringContainsString('<?php else: ?>', $compiled);
        $this->assertStringContainsString('<?php endif; ?>', $compiled);
    }

    public function testWhile(): void
    {
        $compiled = $this->getCompiledContent();

        $this->assertStringContainsString('<?php while ($i <= 10): ?>', $compiled);
        $this->assertStringContainsString('<?php endwhile; ?>', $compiled);
    }

    protected function getCompiledContent(): string
    {
        return file_get_contents((new TemplateCompiler())->compile(dirname(__DIR__) . '/templates/compile.php'));
    }
}
