<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\ANSI;
use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Helper\StatusListHelper;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

final class StatusListHelperTest extends TestCase
{
    private OutputStreamStub $out;
    private OutputStreamStub $err;
    private InputStreamStub $in;

    public function setUp(): void
    {
        $this->out = new OutputStreamStub('php://memory');
        $this->err = new OutputStreamStub('php://memory');
        $this->in = new InputStreamStub('php://memory');
    }

    public function testItem(): void
    {
        $list = new StatusListHelper(new Console($this->out, $this->err, $this->in));
        $list->status('ok', 'Started Foo service');

        $this->assertStringContainsString("[\e[32m OK \e[0m] Started Foo service\n", $this->out->getContents());
    }

    public function testSetStatus(): void
    {
        $list = new StatusListHelper(new Console($this->out, $this->err, $this->in));
        $list->setStatus('warning', [ANSI::FG_RED]);
        $list->status('warning', 'TMP Directory is not writeable');
        $this->assertStringContainsString("[\e[31m WARNING \e[0m] TMP Directory is not writeable\n", $this->out->getContents());
    }

    public function testItems(): void
    {
        $list = new StatusListHelper(new Console($this->out, $this->err, $this->in), false);
        $list->setStatus('error', [ANSI::FG_RED]);
        $list->status('ok', 'Item #1');
        $list->status('ok', 'Item #2');
        $list->status('error', 'Item #3');

        $this->assertStringContainsString("[\e[32m OK \e[0m] Item #1\n", $this->out->getContents());
        $this->assertStringContainsString("[\e[32m OK \e[0m] Item #2\n", $this->out->getContents());
        $this->assertStringContainsString("[\e[31m ERROR \e[0m] Item #3\n", $this->out->getContents());
    }

    public function testAutopad(): void
    {
        $list = new StatusListHelper(new Console($this->out, $this->err, $this->in), true);
        $list->setStatus('error', [ANSI::FG_RED]);
        $list->status('ok', 'Item #1');
        $list->status('error', 'Item #2');

        $this->assertStringContainsString("[\e[32m  OK   \e[0m] Item #1\n", $this->out->getContents());
        $this->assertStringContainsString("[\e[31m ERROR \e[0m] Item #2\n", $this->out->getContents());
    }
}
