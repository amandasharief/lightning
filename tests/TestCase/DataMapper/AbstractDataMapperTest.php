<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper;

use PDO;
use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Lightning\Hydrator\Hydrator;

use function Lightning\Dotenv\env;

use Lightning\Fixture\FixtureManager;
use Lightning\Test\Fixture\TagsFixture;
use Lightning\EventManager\EventManager;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Test\PersistentPdoFactory;
use Lightning\DataMapper\Event\AfterFind;
use Lightning\DataMapper\Event\AfterSave;
use Lightning\Hydrator\HydratorInterface;
use Lightning\DataMapper\Event\BeforeFind;
use Lightning\DataMapper\Event\BeforeSave;
use Lightning\DataMapper\Event\AfterCreate;
use Lightning\DataMapper\Event\AfterDelete;
use Lightning\DataMapper\Event\AfterUpdate;
use Lightning\Test\Fixture\ArticlesFixture;
use Lightning\DataMapper\Event\BeforeCreate;
use Lightning\DataMapper\Event\BeforeDelete;
use Lightning\DataMapper\Event\BeforeUpdate;
use Lightning\EventManager\EventManagerInterface;
use Lightning\Test\TestCase\DataMapper\Entity\Article;
use Lightning\Test\TestCase\DataMapper\Entity\PostTag;
use Lightning\DataMapper\DataSource\DatabaseDataSource;
use Lightning\DataMapper\DataSource\DataSourceInterface;
use Lightning\DataMapper\Exception\EntityNotFoundException;

use Lightning\Test\TestCase\DataMapper\DataMapper\ArticleDataMapper;
use Lightning\Test\TestCase\DataMapper\DataMapper\PostTagDataMapper;

final class AbstractDataMapperTest extends TestCase
{
    protected ?PDO $pdo;
    protected FixtureManager $fixtureManager;
    protected DatabaseDataSource $storage;
    protected Hydrator $hydrator;
    protected EventManager $eventManager;

    public function setUp(): void
    {
        $this->pdo = (new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->storage = new DatabaseDataSource($this->pdo, new QueryBuilder());
        $this->hydrator = new Hydrator();
        $this->eventManager = new EventManager();

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([
            ArticlesFixture::class,
            TagsFixture::class,
        ]);
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    public function testGetDataSource(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertInstanceOf(DataSourceInterface::class, $mapper->getDataSource());
    }

    public function testGetHydrator(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertInstanceOf(HydratorInterface::class, $mapper->getHydrator());
    }

    public function testGetEventManager(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertInstanceOf(EventManagerInterface::class, $mapper->getEventManager());
    }

    public function getPrimaryKey(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertEquals(['id'], $mapper->getPrimaryKey());
    }

    public function testCount(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertEquals(3, $mapper->count());
    }

    public function testCountBy(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->assertEquals(1, $mapper->count(['id' => 1000]));
        $this->assertEquals(0, $mapper->count(['id' => 1234]));
    }

    public function testDelete(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $first = $mapper->find();
        $this->assertTrue($mapper->delete($first));
        $this->assertEquals(2, $mapper->count()); // was deleted
        $this->assertFalse($mapper->delete($first)); // test delete fail
    }

    public function testFind(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->find();
        $this->assertEquals('Article #1', $entity->getTitle());
        $this->assertTrue($mapper->callIsPersisted($entity));
    }

    public function testFindCompositePrimaryKey(): void
    {
        $mapper = new PostTagDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->get([1000,2002]);
        $this->assertInstanceOf(PostTag::class, $entity);
        $this->assertEquals(1000, $entity->getPostId());
        $this->assertEquals(2002, $entity->getTagId());
    }

    public function testFindCompositePrimaryKeyException(): void
    {
        $mapper = new PostTagDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Invalid Primary Key / ID');

        $this->assertNull($mapper->get([1000])); // bad input check does not cause an error
    }

    public function testFindAll(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $results = $mapper->findAll();
        $this->assertEquals('Article #1', $results[0]->getTitle());
        $this->assertEquals('Article #2', $results[1]->getTitle());
        $this->assertEquals('Article #3', $results[2]->getTitle());
    }

    public function testFindAllOrder(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $results = $mapper->findAll([], ['order' => 'id DESC']);
        $this->assertEquals('Article #3', $results[0]->getTitle());
        $this->assertEquals('Article #2', $results[1]->getTitle());
        $this->assertEquals('Article #1', $results[2]->getTitle());
    }

    public function testFindAllBy(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $results = $mapper->findAll(['title !=' => 'Article #2']);
        $this->assertCount(2, $results);
        $this->assertEquals('Article #1', $results[0]->getTitle());
        $this->assertEquals('Article #3', $results[1]->getTitle());
    }

    public function testFindAllByOrder(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $results = $mapper->findAll(['title !=' => 'Article #2'],['order' => 'id DESC']);
        $this->assertCount(2, $results);
        $this->assertEquals('Article #3', $results[0]->getTitle());
        $this->assertEquals('Article #1', $results[1]->getTitle());
    }

    public function testFindBy(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->find(['title' => 'Article #2']);
        $this->assertEquals('Article #2', $entity->getTitle());
        $this->assertNull($mapper->find(['title' => 'Article #100']));
    }

    public function testGet(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->get(1000);
        $this->assertEquals('Article #1', $entity->getTitle());
        ;
    }

    public function testGetException(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $this->expectException(EntityNotFoundException::class);
        $this->expectExceptionMessage('Entity Not Found');

        $mapper->get(1234);
    }

    public function testSaveCreate(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->assertTrue($mapper->save($entity));
        $this->assertEquals(1003, $entity->getId());
    }

    public function testSaveUpdate(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->find();
        $entity->setTitle('foo');
        $this->assertTrue($mapper->save($entity));

        $entity = $mapper->find();
        $this->assertEquals('foo', $entity->getTitle());
    }

    public function testSaveUpdateFail(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->find();
        $entity->setId(1234);

        $this->assertFalse($mapper->save($entity));
    }

    public function testFindBeforeFindEventDispatch(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->eventManager->addListener(BeforeFind::class, function (BeforeFind $event) {
            $event->setQuery(['limit' => 2]);
        });
        $this->assertCount(2, $mapper->findAll());
    }

    public function testFindAfterFindEventDispatch(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->eventManager->addListener(AfterFind::class, function (AfterFind $event) {
            $result = $event->getResult();
            $this->assertCount(3, $result);
            unset($result[2]);
            $event->setResult($result);
        });
        $this->assertCount(2, $mapper->findAll());
    }

    public function testFindBeforeFindEventStopped(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $this->eventManager->addListener(BeforeFind::class, function (BeforeFind $event) {
            $event->stopPropagation();
        });

        $this->assertCount(0, $mapper->findAll());
    }

    public function testDeleteBeforeDeleteDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->find();

        $this->eventManager->addListener(BeforeDelete::class, function (BeforeDelete $event) {
            $this->assertTrue(true);
        });
        $mapper->delete($entity);
    }

    public function testDeleteAfterDeleteDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->find();

        $this->eventManager->addListener(AfterDelete::class, function (AfterDelete $event) {
            $this->assertTrue(true);
        });
        $mapper->delete($entity);
    }

    /**
     * @depends testDeleteBeforeDeleteDispatched
     * @depends testDeleteAfterDeleteDispatched
     * @depends testDelete
     *
     * @return void
     */
    public function testDeleteBeforeDeleteStopped(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = $mapper->find();

        $this->eventManager->addListener(BeforeDelete::class, function (BeforeDelete $event) {
            $event->stopPropagation();
        });

        $this->eventManager->addListener(AfterDelete::class, function (AfterDelete $event) {
            $this->assertTrue(false);
        });

        $mapper->delete($entity);

        $this->assertNotNull($mapper->find());
    }

    public function testSaveBeforeSaveDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(BeforeSave::class, function (BeforeSave $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testSaveBeforeSaveStopped(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(BeforeSave::class, function (BeforeSave $event) {
            $event->stopPropagation();
        });

        $this->assertFalse($mapper->save($entity));
    }

    public function testSaveAfterSaveDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(AfterSave::class, function (AfterSave $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testCreateBeforeCreateDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(BeforeCreate::class, function (BeforeCreate $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testCreateAfterCreateDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(AfterCreate::class, function (AfterCreate $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testCreateBeforeCreateStopped(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);
        $entity = new Article();

        $entity->setTitle('test')
            ->setBody('---')
            ->setAuthorId(2000)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setUpdatedAt(date('Y-m-d H:i:s'));

        $this->eventManager->addListener(BeforeCreate::class, function (BeforeCreate $event) {
            $event->stopPropagation();
        });

        $this->assertFalse($mapper->save($entity));
    }

    public function testUpdateBeforeUpdateDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->find();
        $entity->setTitle('foo');

        $this->eventManager->addListener(BeforeUpdate::class, function (BeforeUpdate $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testUpdateAfterUpdateDispatched(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->find();
        $entity->setTitle('foo');

        $this->eventManager->addListener(AfterUpdate::class, function (AfterUpdate $event) {
            $this->assertTrue(true);
        });

        $mapper->save($entity);
    }

    public function testUpdateBeforeUpdateStopped(): void
    {
        $mapper = new ArticleDataMapper($this->storage, $this->hydrator, $this->eventManager);

        $entity = $mapper->find();
        $entity->setTitle('foo');

        $this->eventManager->addListener(BeforeUpdate::class, function (BeforeUpdate $event) {
            $event->stopPropagation();
        });

        $this->assertFalse($mapper->save($entity));
    }
}
