<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper;

use PDO;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;
use Lightning\EventManager\EventManager;

use Lightning\QueryBuilder\QueryBuilder;
use Lightning\DataMapper\Event\BeforeFind;
use Lightning\DataMapper\DataMapperInterface;
use Lightning\DataMapper\DataSource\DatabaseDataSource;

use Lightning\Test\TestCase\DataMapper\DataMapper\ArticleDataMapper;

final class BeforeFindTest extends TestCase
{
    public function createEvent(): BeforeFind
    {
        $dataSource = new DatabaseDataSource(new Pdo('sqlite::memory:'), new QueryBuilder());
        $dataMapper = new ArticleDataMapper($dataSource, new Hydrator(), new EventManager());

        return new BeforeFind($dataMapper, ['foo' => 'bar']);
    }
    public function testStopPropagation(): void
    {
        $event = $this->createEvent();
        $this->assertFalse($event->isPropagationStopped());

        $event->stopPropagation();

        $this->assertTrue($event->isPropagationStopped());
    }

    public function testGetDataMapper(): void
    {
        $this->assertInstanceOf(DataMapperInterface::class, $this->createEvent()->getDataMapper());
    }

    public function testGetQuery(): void
    {
        $this->assertEquals(['foo' => 'bar'], $this->createEvent()->getQuery());
    }

    /**
     * @depends testGetQuery
     */
    public function testSetQuery(): void
    {
        $this->assertEquals(['bar' => 'foo'], $this->createEvent()->setQuery(['bar' => 'foo'])->getQuery());
    }
}
