<?php declare(strict_types=1);

namespace Lightning\Test\ServiceObject;

use PHPUnit\Framework\TestCase;
use Lightning\Arguments\Arguments;
use Lightning\ServiceObject\Result;
use Lightning\ServiceObject\AbstractServiceObject;

class ServiceObject extends AbstractServiceObject
{
    private bool $initialized = false;

    protected function initialize(): void
    {
        parent::initialize();
        $this->initialized = true;
    }
    public function execute(Arguments $args): Result
    {
        return new Result(true, [
            'args' => $args,
            'initialized' => $this->initialized
        ]);
    }
}

final class AbstractServiceObjectTest extends TestCase
{
    public function testDispatch()
    {
        $result = (new ServiceObject())->dispatch(['foo' => 'bar']);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->get('initialized'));
        $this->assertInstanceOf(Arguments::class, $result->get('args'));
    }
}
