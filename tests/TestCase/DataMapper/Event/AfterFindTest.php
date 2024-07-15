<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper;

use PDO;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;
use Lightning\EventManager\EventManager;

use Lightning\QueryBuilder\QueryBuilder;
use Lightning\DataMapper\Event\AfterFind;
use Lightning\DataMapper\DataMapperInterface;
use Lightning\DataMapper\DataSource\DatabaseDataSource;
use Lightning\Test\TestCase\DataMapper\DataMapper\ArticleDataMapper;

final class AfterFindTest extends TestCase
{
    public function createEvent(): AfterFind
    {
        $dataSource = new DatabaseDataSource(new Pdo('sqlite::memory:'), new QueryBuilder());
        $dataMapper = new ArticleDataMapper($dataSource, new Hydrator(), new EventManager());

        return new AfterFind($dataMapper, ['foo' => 'bar']);
    }

    public function testGetDataMapper(): void
    {
        $this->assertInstanceOf(DataMapperInterface::class, $this->createEvent()->getDataMapper());
    }

    public function testGetResult(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->createEvent()->getResult());
    }

    /**
     * @depends testGetResult
     */
    public function testSetResult(): void
    {
        $this->assertEquals(['bar' => 'foo'], $this->createEvent()->setResult(['bar' => 'foo'])->getResult());
    }
}
