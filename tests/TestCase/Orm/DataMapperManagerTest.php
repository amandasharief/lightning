<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\Orm;

use PHPUnit\Framework\TestCase;
use Lightning\Orm\DataMapperManager;

use Lightning\DataMapper\DataSourceInterface;
use Lightning\Orm\AbstractObjectRelationalMapper;
use Lightning\DataMapper\DataSource\MemoryDataSource;
use Lightning\Hydrator\Hydrator;
use Lightning\Orm\DataMapperFactory;

class DummyArticleEntity
{
    private ?int $id = null;
    private string $title;
    private string $body;
    private ?int $author_id = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;
    private ?object $author = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    public function setAuthorId(int $author_id): self
    {
        $this->author_id = $author_id;

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(?string $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?string $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getAuthor(): ?object
    {
        return $this->author;
    }

    public function setAuthor(?object $author): self
    {
        $this->author = $author;

        return $this;
    }
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
        $manager = new DataMapperManager(new DataMapperFactory(new MemoryDataSource(), new Hydrator()));

        $this->assertInstanceOf(
            DummyArticle::class, $manager->get(DummyArticle::class)
        );
    }

    /**
     * @depends testGet
     */
    public function testAdd(): void
    {
        
        $manager = new DataMapperManager(new DataMapperFactory(new MemoryDataSource(), new Hydrator()));
        $mapper = new DummyArticle(new MemoryDataSource(), new Hydrator(), $manager);

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
        $dataSource = new MemoryDataSource();
        
        $manager = new DataMapperManager(new DataMapperFactory($dataSource, new Hydrator()));

        $mapper = new DummyArticle($dataSource, new Hydrator(), $manager);

        $mapper->foo = 'bar'; // test its not being created

        $manager->add($mapper);

        $this->assertEquals(
          $mapper, $manager->get(DummyArticle::class)
        );
    }
}
