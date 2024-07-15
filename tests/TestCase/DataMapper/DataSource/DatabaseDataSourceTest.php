<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper\DataSource;

use PDO;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use function Lightning\Dotenv\env;

use Lightning\Fixture\FixtureManager;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Test\PersistentPdoFactory;
use Lightning\Test\Fixture\AuthorsFixture;

use Lightning\Test\Fixture\ArticlesFixture;
use Lightning\DataMapper\DataSource\DatabaseDataSource;

final class DatabaseDataSourceTest extends TestCase
{
    protected ?PDO $pdo;
    protected FixtureManager $fixtureManager;

    protected function setUp(): void
    {
        // Create Connection
        $this->pdo = (new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([ArticlesFixture::class,AuthorsFixture::class]);
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    private function createStorage(): DatabaseDataSource
    {
        return new DatabaseDataSource($this->pdo, new QueryBuilder());
    }

    public function testCount(): void
    {
        $storage = $this->createStorage();
        $this->assertEquals(3, $storage->count('articles'));
        $this->assertEquals(2, $storage->count('articles', ['criteria' => ['id !=' => 1000]]));
    }

    /**
     * @depends testCount
     */
    public function testCreate(): void
    {
        $storage = $this->createStorage();

        $article = [
            'title' => 'Article #' . time(),
            'body' => 'A new article',
            'author_id' => 1234,
            'created_at' => '2021-10-05 19:49:00',
            'updated_at' => '2021-10-05 19:49:00',
        ];

        $this->assertEquals(1003, $storage->create('articles', $article));
        $this->assertEquals(4, $storage->count('articles'));
    }

    public function testRead(): void
    {
        $storage = $this->createStorage();
        $records = $storage->read('articles');
        $this->assertCount(3, $records);
    }

    public function testReadConditions(): void
    {
        $storage = $this->createStorage();

        $records = $storage->read('articles', ['criteria' => ['id !=' => 1000]]);
        $this->assertCount(2, $records);
    }

    public function testReadJoins(): void
    {
        $storage = $this->createStorage();

        $options = ['fields' => [
            'id',
            'title',
            'authors.name'
        ],
            'joins' => [
                [
                    'table' => 'authors',
                    'conditions' => [
                        'articles.author_id = authors.id'
                    ]
                ]
            ]];

        $records = $storage->read('articles', $options);

        $this->assertEquals('Claire', $records[1]['name']);
    }

    public function testReadJoinsNoTable(): void
    {
        $storage = $this->createStorage();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Join configuration array is missing `table`');

        $options = [
            'joins' => [
                [
                    'conditions' => [
                        'articles.author_id = authors.id'
                    ]
                ]
            ]
        ];

        $storage->read('articles', $options);
    }

    public function testReadJoinsInvalidJoin(): void
    {
        $storage = $this->createStorage();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid join type `foo`');

        $storage->read('articles', ['joins' => [
            [
                'table' => 'something',
                'type' => 'FOO',

            ]
        ]]);
    }

    public function testReadOrder(): void
    {
        $storage = $this->createStorage();

        $records = $storage->read('articles', ['order' => [
            'id' => 'DESC'
        ]]);
        $this->assertEquals(1002, $records[0]['id']);
    }

    public function testReadGroup(): void
    {
        $storage = $this->createStorage();

        // create a new record to test group is working
        $storage->create('articles', [
            'title' => 'Article #4',
            'body' => 'foo',
            'author_id' => 2001,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $options = [
            'fields' => ['COUNT(id) AS count','author_id'],
            'order' => ['author_id ASC'],
            'group' => [
                'author_id'
            ]
        ];
        $records = $storage->read('articles', $options);

        $this->assertEquals(1, $records[0]['count']);
        $this->assertEquals(2, $records[1]['count']); // It worked
        $this->assertEquals(1, $records[2]['count']);
    }

    public function testReadHaving(): void
    {
        $storage = $this->createStorage();

        // create a new record to test group is working
        $storage->create('articles', [
            'title' => 'Article #4',
            'body' => 'foo',
            'author_id' => 2001,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $records = $storage->read('articles', [
            'fields' => [
                'author_id','COUNT(id)'
            ],
            'group' => [
                'author_id'
            ],
            'having' => [
                'count(id) > 1'
            ]
        ]);

        $this->assertCount(1, $records);
        $this->assertEquals(2001, $records[0]['author_id']);
    }

    public function testLimit(): void
    {
        $storage = $this->createStorage();

        $records = $storage->read('articles', [
            'limit' => 1
        ]);
        $this->assertCount(1, $records);
        $this->assertEquals(1000, $records[0]['id']);
    }

    public function testLimitOffset(): void
    {
        $storage = $this->createStorage();

        $records = $storage->read('articles', [
            'limit' => 1,
            'offset' => 1
        ]);
        $this->assertCount(1, $records);
        $this->assertEquals(1001, $records[0]['id']);
    }

    public function testUpdate(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(1, $storage->update('articles', ['title' => 'foo'], ['criteria' => ['id' => 1000]]));

        $records = $storage->read('articles', ['order' => ['id ASC']]);

        $this->assertEquals('foo', $records[0]['title']);
        $this->assertNotEquals('foo', $records[1]['title']);
        $this->assertNotEquals('foo', $records[2]['title']);
    }

    public function testUpdateAll(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(3, $storage->update('articles', ['title' => 'foo']));

        $records = $storage->read('articles');

        $this->assertEquals('foo', $records[0]['title']);
        $this->assertEquals('foo', $records[1]['title']);
        $this->assertEquals('foo', $records[2]['title']);
    }

    /**
     * @depends testCount
     */
    public function testDelete(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(1, $storage->delete('articles', ['criteria' => ['id' => 1000]]));
        $this->assertEquals(2, $storage->count('articles'));
    }

    /**
    * @depends testCount
    */
    public function testDeleteAll(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(3, $storage->delete('articles'));
        $this->assertEquals(0, $storage->count('articles'));
    }
}
