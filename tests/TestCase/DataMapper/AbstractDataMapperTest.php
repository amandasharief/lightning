<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper;

use PDO;
use ReflectionClass;
use BadMethodCallException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use Lightning\Utility\Collection;
use function Lightning\Dotenv\env;
use Lightning\Event\EventDispatcher;
use Lightning\DataMapper\QueryObject;

use Lightning\Event\ListenerRegistry;
use Lightning\Fixture\FixtureManager;
use Lightning\Test\Fixture\TagsFixture;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Test\PersistentPdoFactory;
use Lightning\Test\Fixture\ArticlesFixture;
use Lightning\DataMapper\AbstractDataMapper;
use Lightning\TestSuite\TestEventDispatcher;
use Lightning\DataMapper\DataSourceInterface;
use Lightning\TestSuite\EventDispatcherTestTrait;
use Lightning\DataMapper\DataSource\DatabaseDataSource;
use Lightning\Test\TestCase\DataMapper\Entity\TagEntity;
use Lightning\DataMapper\Exception\EntityNotFoundException;

final class ArticleEntity
{
    private ?int $id = null;

    private string $title;
    private string $body;
    private ?int $author_id = null;
    private ?string $created_at = null;
    private ?string $updated_at = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
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
        return $this->created_at ?: null;
    }

    public function setCreatedAt(string $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at ?: null;
    }

    public function setUpdatedAt(string $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     * @internal Should not do this never
     */
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
}

class Tag extends AbstractDataMapper
{
    protected $primaryKey = 'id';
    protected string $table = 'tags';
    protected array $fields = [
        'id', 'name','created_at','updated_at'
    ];
    protected string $entityClass = TagEntity::class;
}

class Article extends AbstractDataMapper
{
    protected $primaryKey = 'id';
    protected string $table = 'articles';
    protected array $fields = [
        'id', 'title','body','author_id','created_at','updated_at'
    ];

    protected string $entityClass = ArticleEntity::class;

    public function setProperty($property, $value)
    {
        $this->$property = $value;
    }

    public function getProperty($property)
    {
        return $this->$property;
    }

    protected array $called = [];
    protected ?string $stopOn = null;

    protected function wasCalled(string $method): void
    {
        $this->called[] = $method;
    }

    public function getCalled(): array
    {
        return $this->called;
    }

    public function stopOn(string $method): void
    {
        $this->stopOn = $method;
    }

    public function reset() : void 
    {
        $this->called = [];
        $this->stopOn = null;
    }

    /**
     * Before create hook
     */
    protected function beforeCreate(object $entity): bool
    {
        parent::beforeCreate($entity);

        $this->wasCalled('beforeCreate');

        return $this->stopOn === 'beforeCreate' ? false : true;
    }

    /**
     * After create hook
     */
    protected function afterCreate(object $entity): void
    {
        parent::afterCreate($entity);

        $this->wasCalled('afterCreate');
    }

    /**
     * Before update hook
     */
    protected function beforeUpdate(object $entity): bool
    {
        parent::beforeUpdate($entity);

        $this->wasCalled('beforeUpdate');

        return $this->stopOn === 'beforeUpdate' ? false : true;
    }

    /**
     * after update hook
     */
    protected function afterUpdate(object $entity): void
    {
        parent::afterUpdate($entity);

        $this->wasCalled('afterUpdate');
    }

    /**
     * Before save hook
     */
    protected function beforeSave(object $entity): bool
    {
        parent::beforeSave($entity);

        $this->wasCalled('beforeSave');

        return $this->stopOn === 'beforeSave' ? false : true;
    }

    /**
     * After save hook
     */
    protected function afterSave(object $entity): void
    {
        parent::afterSave($entity);
        $this->wasCalled('afterSave');
    }

    /**
     * Before delete hook
     */
    protected function beforeDelete(object $entity): bool
    {
        parent::beforeDelete($entity);

        $this->wasCalled('beforeDelete');

        return $this->stopOn === 'beforeDelete' ? false : true;
    }

    /**
     * after delete hook
     */
    protected function afterDelete(object $entity): void
    {
        parent::afterDelete($entity); // code cover friendly
        $this->wasCalled('afterDelete');
    }

    /**
     * before find hook
     */
    protected function beforeFind(QueryObject $query): bool
    {
        parent::beforeFind($query);// code cover friendly
        $this->wasCalled('beforeFind');

        return $this->stopOn === 'beforeFind' ? false : true;
    }

    /**
     * After find hook
     */
    protected function afterFind(Collection $collection, QueryObject $query): void
    {
        parent::afterFind($collection, $query); // code coverage friendly
        $this->wasCalled('afterFind');
    }
}

final class AbstractDataMapperTest extends TestCase
{
    use EventDispatcherTestTrait;

    protected ?PDO $pdo;
    protected FixtureManager $fixtureManager;

    public function setUp(): void
    {
        $this->pdo = ( new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->storage = new DatabaseDataSource($this->pdo, new QueryBuilder());

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([
            ArticlesFixture::class,
            TagsFixture::class,
        ]);

        $this->setEventDispatcher(new TestEventDispatcher(new EventDispatcher(new ListenerRegistry())));
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    public function testGetDataSource(): void
    {
        $mapper = new Article($this->storage);

        $this->assertInstanceOf(DataSourceInterface::class, $mapper->getDataSource());
    }

    public function testCreateEntity(): void
    {
        $mapper = new Article($this->storage);

        $data = [
            'title' => 'test',
            'body' => 'none',
            'author_id' => 1234,
            'created_at' => '2022-10-18 09:40:45',
            'updated_at' => '2022-10-18 09:40:45',
        ];

        $entity = $mapper->createEntity($data);
        $this->assertInstanceOf(ArticleEntity::class, $entity);

        $this->assertEquals('test', $entity->getTitle());
        $this->assertEquals('none', $entity->getBody());
        $this->assertEquals(1234, $entity->getAuthorId());
        $this->assertEquals('2022-10-18 09:40:45', $entity->getCreatedAt());
        $this->assertEquals('2022-10-18 09:40:45', $entity->getUpdatedAt());
    }

    public function testCreateEntities(): void
    {
        $mapper = new Article($this->storage);

        $data = [
            'title' => 'test',
            'body' => 'none',
            'author_id' => 1234,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $result = $mapper->createEntities([$data,$data]);
        $this->assertInstanceOf(ArticleEntity::class, $result[0]);
        $this->assertInstanceOf(ArticleEntity::class, $result[1]);
    }

    public function testGetPrimaryKey(): void
    {
        $this->assertEquals(['id'], (new Article($this->storage))->getPrimaryKey());
    }

    public function testGet(): void
    {
        $mapper = new Article($this->storage);

        /** @var ArticleEntity $article */
        $article = $mapper->getBy(['id' => 1000]);

        $this->assertInstanceOf(ArticleEntity::class, $article);
        $this->assertSame('Article #1', $article->getTitle());
    }

    /**
     * Test that only selected fields are used
     */
    public function testGetFields(): void
    {
        $mapper = new Article($this->storage);
        $mapper->setProperty('fields', [
            'id', 'title','body','author_id'
        ]);
        /** @var ArticleEntity $article */
        $article = $mapper->getBy(['id' => 1000]);

        $this->assertNull($article->getCreatedAt());
        $this->assertNull($article->getUpdatedAt());
    }

    public function testGetNotFound(): void
    {
        $mapper = new Article($this->storage);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity Not Found');

        /** @var ArticleEntity $article */
        $mapper->getBy(['id' => 1234]);
    }

    public function testFindCount(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(3, $mapper->findCount());
        $this->assertEquals(['beforeFind'], $mapper->getCalled());
    }

    public function testFindCountHookCalled(): void
    {
        $mapper = new Article($this->storage);
        $mapper->findCount();
        $this->assertEquals(['beforeFind'], $mapper->getCalled());
    }

    public function testFindCountWithQuery(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(1, $mapper->findCountBy(['id' => 1000]));
        $this->assertEquals(0, $mapper->findCountBy(['id' => 1234]));
    }

    public function testFind(): void
    {
        $mapper = new Article($this->storage);
        $entity = $mapper->find(new QueryObject());
        $this->assertEquals('Article #1', $entity->getTitle());
        $this->assertTrue($mapper->isPersisted($entity));
    }

    public function testFindHookCalled(): void
    {
        $mapper = new Article($this->storage);
        $this->assertInstanceOf(ArticleEntity::class, $mapper->find(new QueryObject()));
        $this->assertEquals(['beforeFind','afterFind'], $mapper->getCalled());
    }

    public function testFindHookCalledAndCancelled(): void
    {
        $mapper = new Article($this->storage);
        $mapper->stopOn('beforeFind');
        $this->assertNull($mapper->find(new QueryObject()));
        $this->assertEquals(['beforeFind'], $mapper->getCalled());
    }

    public function testFindWithCondition(): void
    {
        $mapper = new Article($this->storage);
        $entity = $mapper->findBy(['id' => 1000]);
        $this->assertEquals('Article #1', $entity->getTitle());
    }

    public function testFindNoResult(): void
    {
        $mapper = new Article($this->storage);
        $this->assertNull($mapper->findBy(['id' => 1234]));
    }

    public function testFindAll(): void
    {
        $mapper = new Article($this->storage);
        $result = $mapper->findAll();
        $this->assertCount(3, $result);
    }

    public function testFindAllBy(): void
    {
        $mapper = new Article($this->storage);
        $this->assertCount(2, $mapper->findAllBy(['id !=' => 1000]));
    }

    public function testFindAllNoResults(): void
    {
        $mapper = new Article($this->storage);
        $items = $mapper->findAll();

        $this->assertEmpty($mapper->findAllBy(['id' => 123456789]));
    }

    public function testCreate(): void
    {
        $mapper = new Article($this->storage);

        $article = $mapper->createEntity([
            'title' => 'test',
            'body' => 'none',
            'author_id' => 1234,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertTrue($mapper->save($article));

        //
        $expected = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 1 : 1003;
        $this->assertEquals($expected, $article->getId());

        $this->assertEquals(['beforeSave','beforeCreate','afterCreate','afterSave'], $mapper->getCalled());
    }

    public function testCreateBeforeSaveHookCancelled(): void
    {
        $mapper = new Article($this->storage);

        $article = $mapper->createEntity([
            'title' => 'test',
            'body' => 'none',
            'author_id' => 1234,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $mapper->stopOn('beforeSave');
        $this->assertFalse($mapper->save($article));

        $this->assertEquals(['beforeSave'], $mapper->getCalled());
    }

    public function testBeforeCreateHookCancelled(): void
    {
        $mapper = new Article($this->storage);

        $article = $mapper->createEntity([
            'title' => 'test',
            'body' => 'none',
            'author_id' => 1234,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $mapper->stopOn('beforeCreate');
        $this->assertFalse($mapper->save($article));

        $this->assertEquals(['beforeSave','beforeCreate'], $mapper->getCalled());
    }

    public function testUpdate(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $mapper->reset();

        $article->setTitle('foo');
       
        $this->assertTrue($mapper->save($article));

        $this->assertEquals(['beforeSave','beforeUpdate','afterUpdate','afterSave'], $mapper->getCalled());
    }

    public function testUpdateBeforeSaveHookCancelled(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $mapper->reset();

        $mapper->stopOn('beforeSave');
        $this->assertFalse($mapper->save($article));

        $this->assertEquals(['beforeSave'], $mapper->getCalled());
    }

    public function testUpdateBeforeUpdateHookCancelled(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $mapper->reset();
        $mapper->stopOn('beforeUpdate');
        $this->assertFalse($mapper->save($article));

        $this->assertEquals(['beforeSave','beforeUpdate'], $mapper->getCalled());
    }

    public function testUpdateWithNoPrimaryKey(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(null);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Primary key `id` has no value');

        $mapper->save($article);
    }

    public function testUpdateFail(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);
        $this->assertFalse($mapper->save($article));
    }

    public function testSaveMany(): void
    {
        $mapper = new Article($this->storage);
        $entities = $mapper->findAll();
        foreach ($entities as $entity) {
            $entity->setUpdatedAt(date('Y-m-d H:i:s'));
        }
        $this->assertTrue($mapper->saveMany($entities));
    }

    public function testSaveManyFail(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);
        $this->assertFalse($mapper->save($article));

        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);
        $entities = $mapper->createCollection([$article]);
        $this->assertFalse($mapper->saveMany($entities));
    }

    public function testUpdateAll(): void
    {
        $mapper = new Article($this->storage);

        $this->assertEquals(2, $mapper->updateAll(new QueryObject(['id !=' => 1001]), ['author_id' => 1111]));
        $this->assertEquals(0, $mapper->updateAll(new QueryObject(['id' => 1234]), ['author_id' => 1111]));
    }

    public function testUpdateAllException(): void
    {
        $mapper = new Article($this->storage);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Data cannot be empty');

        $mapper->updateAll(new QueryObject(), []);
    }

    public function testUpdateAllBy(): void
    {
        $mapper = new Article($this->storage);

        $this->assertEquals(2, $mapper->updateAllBy(['id !=' => 1001], ['author_id' => 1111]));
    }

    public function testDeleteAll(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(2, $mapper->deleteAll(new QueryObject(['id !=' => 1001])));
        $this->assertEquals(0, $mapper->deleteAll(new QueryObject(['id' => 1234])));
    }

    public function testDeleteAllBy(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(2, $mapper->deleteAllBy(['id !=' => 1001]));
    }

    public function testDelete(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $mapper->reset();

        $this->assertTrue($mapper->delete($article));
        $this->assertEquals(['beforeDelete','afterDelete'], $mapper->getCalled());
    }

    public function testDeleteHookCancelled(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $mapper->reset();

        $mapper->stopOn('beforeDelete');
        $this->assertFalse($mapper->delete($article));
        $this->assertEquals(['beforeDelete'], $mapper->getCalled());
    }

    public function testDeleteFail(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);

        $this->assertFalse($mapper->delete($article));
    }

    public function testDeleteMany(): void
    {
        $mapper = new Article($this->storage);
        $articles = $mapper->findAll();

        $this->assertTrue($mapper->deleteMany($articles));
        $this->assertCount(0, $mapper->findAll());
    }

    public function testDeleteManyFail(): void
    {
        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);
        $this->assertFalse($mapper->delete($article));

        $mapper = new Article($this->storage);
        $article = $mapper->find();
        $article->setId(1234);
        $entities = $mapper->createCollection([$article]);
        $this->assertFalse($mapper->deleteMany($entities));
    }

    public function testFindList(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(
            [1000,1001,1002],
            $mapper->findList()
        );
    }

    public function testFindListWithNoPrimaryKey(): void
    {
        $mapper = new Article($this->storage);
        $reflection = new ReflectionClass($mapper);
        $property = $reflection->getProperty('primaryKey');
        $property->setAccessible(true);
        $property->setValue($mapper, ['article_id','author_id']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot determine primary key');
        $mapper->findList();
    }

    public function testFindListWithQuery(): void
    {
        $query = new QueryObject(['id !=' => 1001]);
        $mapper = new Article($this->storage);
        $this->assertEquals(
            [1000,1002],
            $mapper->findList($query)
        );
    }

    public function testFindListBy(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(
            [1000,1002],
            $mapper->findListBy(['id !=' => 1001])
        );
    }

    public function testFindListWithValues(): void
    {
        $mapper = new Article($this->storage);
        $this->assertEquals(
            [1000 => 'Article #1',1001 => 'Article #2',1002 => 'Article #3'],
            $mapper->findList(null, ['idField' => 'id','valueField' => 'title'])
        );
    }

    public function testFindListGrouped(): void
    {
        $mapper = new Article($this->storage);

        $mapper->updateAll(new QueryObject(), ['author_id' => 2000]);
        $mapper->updateAll(new QueryObject(['id !=' => 1001]), ['author_id' => 4000]);

        $expected = [
            4000 => [
                1000 => 'Article #1',
                1002 => 'Article #3'
            ],
            2000 => [
                1001 => 'Article #2'
            ]
        ];

        $this->assertEquals(
           $expected, $mapper->findList(null, ['valueField' => 'title','groupField' => 'author_id'])
        );
    }

    // public function testBeforeFindHookFail(): void
    // {
    //     $mapper = new Article($this->storage);

    //     $this->assertNull($mapper->find());
    //     $this->assertTrue($mapper->findAll()->isEmpty());
    //     $this->assertEquals([], $mapper->findList());
    //     $this->assertEquals(0, $mapper->findCount());
    // }

    // public function testBeforeCreateHookFail(): void
    // {
    //     $mapper = new Article($this->storage);

    //     $article = $mapper->createEntity([
    //         'title' => 'test',
    //         'body' => 'none',
    //         'author_id' => 1234,
    //         'created_at' => date('Y-m-d H:i:s'),
    //         'updated_at' => date('Y-m-d H:i:s'),
    //     ]);

    //     $this->assertFalse($mapper->save($article));
    // }

    // public function testBeforeSaveHookFail(): void
    // {
    //     $mapper = new Article($this->storage);

    //     $article = $mapper->createEntity([
    //         'title' => 'test',
    //         'body' => 'none',
    //         'author_id' => 1234,
    //         'created_at' => date('Y-m-d H:i:s'),
    //         'updated_at' => date('Y-m-d H:i:s'),
    //     ]);

    //     $this->assertFalse($mapper->save($article));
    // }

    // public function testBeforeDeleteHookFail(): void
    // {
    //     $mapper = new Article($this->storage);
    //     $mapper->registerHook('beforeDelete', 'hookFail');

    //     $article = $mapper->find();

    //     $this->assertFalse($mapper->delete($article));
    // }

    // public function testBeforeUpdateHookFail(): void
    // {
    //     $mapper = new Article($this->storage);
    //     $mapper->registerHook('beforeUpdate', 'hookFail');

    //     $article = $mapper->find();

    //     $this->assertFalse($mapper->update($article));
    // }
}
