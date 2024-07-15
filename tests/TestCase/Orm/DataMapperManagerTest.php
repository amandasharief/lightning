<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Orm;

use PDO;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;

use Lightning\Orm\DataMapperFactory;
use Lightning\Orm\DataMapperManager;
use Lightning\EventManager\EventManager;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Orm\AbstractObjectRelationalMapper;
use Lightning\DataMapper\DataSource\DatabaseDataSource;

class DummyArticleEntity
{
    private ?int $id = null;
    private string $title;
    private string $body;
    private ?int $author_id = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private ?object $author = null;
}

class DummyArticle extends AbstractObjectRelationalMapper
{
    protected string $table = 'articles';

    public function createEntity(): object
    {
        return new DummyArticleEntity();
    }
}

final class DataMapperManagerTest extends TestCase
{
    public function testGet(): void
    {
        $dataSource = new DatabaseDataSource(new Pdo('sqlite::memory:'), new QueryBuilder());
        $manager = new DataMapperManager(new DataMapperFactory($dataSource, new Hydrator(), new EventManager()));

        $this->assertInstanceOf(
            DummyArticle::class, $manager->get(DummyArticle::class)
        );
    }

    /**
     * @depends testGet
     */
    public function testAdd(): void
    {
        $dataSource = new DatabaseDataSource(new Pdo('sqlite::memory:'), new QueryBuilder());
        $manager = new DataMapperManager(new DataMapperFactory($dataSource, new Hydrator(), new EventManager()));
        $mapper = new DummyArticle($dataSource, new Hydrator(), new EventManager(), $manager);

        $this->assertInstanceOf(
            DataMapperManager::class, $manager->add($mapper)
        );

        $this->assertSame($mapper, $manager->get(DummyArticle::class));
    }

    /**
     * @depends testAdd
     */
    public function testGetExisting(): void
    {
        $dataSource = new DatabaseDataSource(new Pdo('sqlite::memory:'), new QueryBuilder());
        $manager = new DataMapperManager(new DataMapperFactory($dataSource, new Hydrator(), new EventManager()));

        $mapper = new DummyArticle($dataSource, new Hydrator(), new EventManager(), $manager);
        $id = spl_object_id($mapper);

        $this->assertEquals($id, spl_object_id($manager->add($mapper)->get(DummyArticle::class)));
    }
}
