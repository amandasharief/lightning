<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Console;

use ReflectionClass;
use Lightning\Console\ANSI;
use Lightning\Console\Console;
use PHPUnit\Framework\TestCase;
use Lightning\Console\Helper\ProgressBarHelper;
use Lightning\Console\TestSuite\InputStreamStub;
use Lightning\Console\TestSuite\OutputStreamStub;

final class ProgressBarHelperTest extends TestCase
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

    public function testGetStyle(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));

        $this->assertEquals(ANSI::FG_BLUE . ANSI::BOLD, $pb->getStyle());
    }

    /**
     * @depends testGetStyle
     */
    public function testSetStyle(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));

        $this->assertEquals(ANSI::FG_CYAN . ANSI::BOLD, $pb->setStyle([ANSI::FG_CYAN, ANSI::BOLD])->getStyle());
    }

    public function testGetEmptyStyle(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));

        $this->assertEquals(ANSI::FG_WHITE, $pb->getEmptyStyle());
    }

    /**
     * @depends testGetEmptyStyle
     */
    public function testSetEmptyStyle(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));

        $this->assertEquals(ANSI::FG_MAGENTA . ANSI::BOLD, $pb->setEmptyStyle([ANSI::FG_MAGENTA, ANSI::BOLD])->getEmptyStyle());
    }

    public function testGetBarCharacter(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $this->assertEquals('█', $pb->getBarCharacter());
    }

    /**
     * @depends testGetBarCharacter
     */
    public function testSetBarCharacter(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $this->assertEquals('x', $pb->setBarCharacter('x')->getBarCharacter());
    }

    public function testGetEmptyBarCharacter(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $this->assertEquals('█', $pb->getEmptyBarCharacter());
    }

    /**
     * @depends testGetEmptyBarCharacter
     */
    public function testSetEmptyBarCharacter(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $this->assertEquals('x', $pb->setEmptyBarCharacter('x')->getEmptyBarCharacter());
    }

    public function testSetValue(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $pb->setValue(0);
        // dd($this->out->getContents());
        $expected = "\r \e[34m\e[1m\e[0m\e[37m██████████████████████████████████████████████████\e[0m \e[34m\e[1m  0%\e[0m";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testSetValuePlain(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        (new ReflectionClass($pb))
            ->getProperty('color')
            ->setValue($pb, false);
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setValue(0);

        // dd($this->out->getContents());
        $expected = "\r ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ [   0% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testSetValueMid(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $pb->setValue(50);
        //dd($this->out->getContents());
        $expected = "\r \e[34m\e[1m█████████████████████████\e[0m\e[37m█████████████████████████\e[0m \e[34m\e[1m 50%\e[0m";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testSetValueMidPlain(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        (new ReflectionClass($pb))
            ->getProperty('color')
            ->setValue($pb, false);
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setValue(50);
        // dd($this->out->getContents());
        $expected = "\r █████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░ [  50% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testSetValueComplete(): void
    {
        $pb = new ProgressBarHelper(new Console($this->out, $this->err, $this->in));
        $pb->setValue(100);

        $expected = "\r \e[34m\e[1m██████████████████████████████████████████████████\e[0m\e[37m\e[0m \e[34m\e[1m100%\e[0m";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testSetValueCompletePlain(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        (new ReflectionClass($pb))
            ->getProperty('color')
            ->setValue($pb, false);
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setValue(100);

        $expected = '██████████████████████████████████████████████████ [ 100% ]';
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testStart(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);

        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->start();

        $expected = "\r ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ [   0% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testStartWithArg(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);

        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setMaximum(10)->start(5);

        $expected = "\r █████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░ [  50% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testStartWithArgs(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);
        
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->start(5, 25);

        $expected = "\r ██████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ [  20% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testIncrement(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);
        
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setMaximum(10)->start()->increment();

        $expected = "\r █████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ [  10% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testIncrementIncrement(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);
        
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->setMaximum(10)->start()->increment(5);

        $expected = "\r █████████████████████████░░░░░░░░░░░░░░░░░░░░░░░░░ [  50% ]";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }

    public function testComplete(): void
    {
        $console = (new Console($this->out, $this->err, $this->in));

        $pb = new ProgressBarHelper($console);

        /**
         * @todo Think about no color where to be, i dont want in stream or console but makes it hard to test
         */
        $property = (new ReflectionClass($pb))->getProperty('color');
        $property->setAccessible(true);
        $property->setValue($pb, false);
        
        $pb->setEmptyBarCharacter('░'); // fix due code being in constructor

        $pb->start(10)->complete();

        $expected = "\r ██████████████████████████████████████████████████ [ 100% ]\n";
        $this->assertStringContainsString($expected, $this->out->getContents());
    }
}
