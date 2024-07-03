<?php declare(strict_types=1);

namespace Lightning\Test\TemplateRender;

use PHPUnit\Framework\TestCase;
use Lightning\TemplateRenderer\TemplateRenderer;
use Lightning\TemplateRenderer\Exception\TemplateRendererException;

final class TemplateRendererTest extends TestCase
{
    private string $cachedPath;

    public function setUp(): void
    {
        $this->cachedPath = sys_get_temp_dir() . '/' . uniqid();
        mkdir($this->cachedPath);
    }

    public function testSetGetPath(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals('/tmp', $templateRenderer->setPath('/tmp')->getPath());
    }

    public function testWithPath(): void
    {
        $templateRenderer = new TemplateRenderer('/tmp1', ['cachePath' => $this->cachedPath]);

        $this->assertEquals('/tmp', $templateRenderer->withPath('/tmp')->getPath());
        $this->assertEquals('/tmp1', $templateRenderer->getPath());
    }

    public function testSetGetFileExtension(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['fileExtension' => null]);
        $this->assertNull($templateRenderer->getFileExtension());
        $this->assertEquals('ctp', $templateRenderer->setFileExtension('ctp')->getFileExtension());
    }

    public function testWithFileExtension(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['fileExtension' => null]);
        $this->assertEquals('php', $templateRenderer->withFileExtension('php')->getFileExtension());
        $this->assertNull($templateRenderer->getFileExtension());
    }

    public function testRender(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<h1>OK 200</h1>', $templateRenderer->render('test_render')
        );
    }

    public function testRenderWithoutExtension(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['fileExtension' => null]);

        $this->assertEquals(
            '<h1>OK 200</h1>', $templateRenderer->render('test_render.php')
        );
    }

    public function testRenderInAnotherDirectory(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<p>OK 200</p>', $templateRenderer->render('other/test_render')
        );
    }

    public function testRenderWithData(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<h1>Articles #1234 : Article Title</h1><p>This is an article body</p>',
            $templateRenderer->render('test_render_with_data', ['id' => 1234, 'title' => 'Article Title','body' => 'This is an article body'])
        );
    }

    public function testRenderWithDataEscape(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<h1>Articles #2034 : O&#039;Reilly?</h1><p>&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;</p>',
            $templateRenderer->render('test_render_with_data', ['id' => 2034, 'title' => "O'Reilly?",'body' => "<a href='test'>Test</a>"])
        );
    }

    public function testRenderWithinTemplate(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);
        $this->assertEquals(
            "<script src=\"application.js\"></script><h1>Render Within Template<h1>\n<link rel=\"stylesheet\" href=\"application.css\">", $templateRenderer->render('test_render_within_template')
        );
    }

  /**
     * 1. Wrapped in Div  layout
     * 2. Load JS script
     * 3. Heading
     * 4. CSS
     *
     * @return void
     */
    public function testRenderWithinTemplateExtend(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            "<div class=\"content\"><script src=\"application.js\"></script><h1>Render Within Template<h1>\n<link rel=\"stylesheet\" href=\"application.css\"></div>", $templateRenderer->render('test_render_within_template_extend')
        );
    }

    /**
     * This test uses a template which extends another template then tries to render another template which extends. Since
     * multiple inherhitance is not supported, the correct behavior should be that the last
     *
     * @return void
     */
    public function testRenderWithinTemplateExtendFail(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->expectException(TemplateRendererException::class);
        $this->expectExceptionMessage('Template Renderer does not support multiple inheritance, `extend` can only be called once.');
        $templateRenderer->render('test_render_within_template_extend_fail');
    }

    public function testRenderWithinExtendedTemplate(): void 
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<h1>Render template with extend</h1>\n<script src="application.js"></script><h1>Home</h1><link rel="stylesheet" href="application.css">',
            $templateRenderer->render('test_render_within_extended')
        );
 
    }

    public function testRenderExtends(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            "<!doctype html>\n<html lang=\"en\">\n  <head>\n    <title>Web Application</title>\n  </head>\n  <body>\n    <h1>Home</h1>  \n  </body>\n</html>",
            $templateRenderer->render('index')
        );
    }

    public function testRenderFileNotFound(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->expectException(TemplateRendererException::class);
        $this->expectExceptionMessage('Template `foo.php` not found');

        $templateRenderer->render('foo');
    }

    public function testRenderInvalidPath(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['fileExtension' => null]);

        $this->expectException(TemplateRendererException::class);
        $this->expectExceptionMessage('Template `users/foo` not found');

        $templateRenderer->render('users/foo');
    }

    public function testSetGetAttribute(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);
        $this->assertNull($templateRenderer->get('foo'));
        $this->assertEquals('bar', $templateRenderer->set('foo', 'bar')->get('foo'));
    }

    public function testRenderWithAttribute(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $this->assertEquals(
            '<h1>accepted 202</h1>',
            $templateRenderer
                ->set('status', 'accepted')
                ->set('statusCode', 202)
                ->render('status')
        );

        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        // Tests attributes are overwritten if param is set the same
        $this->assertEquals(
            '<h1>Already Reported 208</h1>',
            $templateRenderer
                ->set('status', 'accepted')
                ->set('statusCode', 202)
                ->render('status',
                    ['status' => 'Already Reported','statusCode' => 208])
        );
    }

    public function testCompile(): void
    {
        $templateRenderer = new TemplateRenderer(__DIR__ .'/views', ['cachePath' => $this->cachedPath]);

        $compiledFilename = $this->cachedPath . '/' . md5(__DIR__ .'/views/test_render_with_data.php') . '.php';

        $templateRenderer->render('test_render_with_data', ['id' => 1234, 'title' => 'Article Title','body' => 'This is an article body']);

        $this->assertEquals(
            '<h1>Articles #<?= $id ?> : <?= $this->escape($title) ?></h1><p><?= $this->escape($body) ?></p>',
            file_get_contents($compiledFilename)
        );
    }
}
