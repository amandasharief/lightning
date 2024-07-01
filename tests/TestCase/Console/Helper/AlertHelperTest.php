<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Helper\AlertHelper;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

final class AlertHelperTest extends TestCase
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

    public function testInfo(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->info('Header');

        $this->assertEquals("\e[44m\e[37m\e[1m Header \e[0m\n", $this->out->getContents());
        $this->assertEmpty($this->err->getContents());
    }

    public function testInfoWithSecondary(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->info('Complete', 'Secondary Header');

        $this->assertEquals("\e[44m\e[37m\e[1m Complete \e[0m Secondary Header\n", $this->out->getContents());
        $this->assertEmpty($this->err->getContents());
    }

    public function testSuccess(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->success('Header');

        $this->assertEquals("\e[42m\e[37m\e[1m Header \e[0m\n", $this->out->getContents());
        $this->assertEmpty($this->err->getContents());
    }

    public function testSuccessWithSecondary(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->success('Complete', 'Secondary Header');

        $this->assertEquals("\e[42m\e[37m\e[1m Complete \e[0m Secondary Header\n", $this->out->getContents());
        $this->assertEmpty($this->err->getContents());
    }

    public function testWarning(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->warning('Header');

        $this->assertEquals("\e[43m\e[37m\e[1m Header \e[0m\n", $this->err->getContents());
        $this->assertEmpty($this->out->getContents());
    }

    public function testWarningWithSecondary(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->warning('Complete', 'Secondary Header');

        $this->assertEquals("\e[43m\e[37m\e[1m Complete \e[0m Secondary Header\n", $this->err->getContents());
        $this->assertEmpty($this->out->getContents());
    }

    public function testError(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->error('Header');

        $this->assertEquals("\e[41m\e[37m\e[1m Header \e[0m\n", $this->err->getContents());
        $this->assertEmpty($this->out->getContents());
    }

    public function testErrorWithSecondary(): void
    {
        $helper = new AlertHelper(new Console($this->out, $this->err, $this->in));
        $helper->error('Complete', 'Secondary Header');

        $this->assertEquals("\e[41m\e[37m\e[1m Complete \e[0m Secondary Header\n", $this->err->getContents());
        $this->assertEmpty($this->out->getContents());
    }
}
