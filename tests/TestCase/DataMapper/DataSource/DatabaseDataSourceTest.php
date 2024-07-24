<?php declare(strict_types=1);

namespace Lightning\Test\DataMapper\DataSource;

use PDO;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;

use InvalidArgumentException;

use PHPUnit\Framework\TestCase;

use function Lightning\Dotenv\env;

use Lightning\Fixture\FixtureManager;
use Lightning\QueryBuilder\QueryBuilder;

use Lightning\Test\PersistentPdoFactory;
use Lightning\Test\Fixture\AuthorsFixture;
use Lightning\Test\Fixture\ArticlesFixture;
use Lightning\DataMapper\DataSource\DatabaseDataSource;
use Lightning\DataMapper\Exception\DataSourceException;

class FileLogger implements LoggerInterface
{
    use LoggerTrait;

    public function __construct(private string $path)
    {
    }

    public function log($level, string|\Stringable $message, array $context = [])
    {
        file_put_contents($this->path, implode(',', [$level,$message, json_encode($context)]) ."\n", FILE_APPEND);
    }

    public function getLog(): string
    {
        return file_get_contents($this->path);
    }

    public function clearLog(): bool
    {
        if (file_exists($this->path)) {
            return unlink($this->path);
        }

        return false;
    }
}

final class DatabaseDataSourceTest extends TestCase
{
    protected ?PDO $pdo;
    protected FixtureManager $fixtureManager;
    protected FileLogger $logger;

    protected function setUp(): void
    {
        // Create Connection
        $this->pdo = (new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([ArticlesFixture::class,AuthorsFixture::class]);

        $this->logger = new FileLogger(sys_get_temp_dir() . '/DataSourceTest.log');
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    private function createDataSource(): DatabaseDataSource
    {
        return new DatabaseDataSource($this->pdo, new QueryBuilder());
    }

    public function testCount(): void
    {
        $storage = $this->createDataSource();
        $this->assertEquals(3, $storage->count('articles'));
        $this->assertEquals(2, $storage->count('articles', ['criteria' => ['id !=' => 1000]]));
    }

    /**
     * @depends testCount
     */
    public function testCreate(): void
    {
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();
        $records = $storage->read('articles');
        $this->assertCount(3, $records);
    }

    public function testReadConditions(): void
    {
        $storage = $this->createDataSource();

        $records = $storage->read('articles', ['criteria' => ['id !=' => 1000]]);
        $this->assertCount(2, $records);
    }

    public function testReadJoins(): void
    {
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();

        $records = $storage->read('articles', ['order' => [
            'id' => 'DESC'
        ]]);
        $this->assertEquals(1002, $records[0]['id']);
    }

    public function testReadGroup(): void
    {
        $storage = $this->createDataSource();

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

        $expected = [
            0 =>
             [
                 'count' => 1,
                 'author_id' => 2000,
             ],
            1 =>
             [
                 'count' => 2,
                 'author_id' => 2001, // It worked
             ],
            2 =>
             [
                 'count' => 1,
                 'author_id' => 2002,
             ],
        ];

        $this->assertEquals($expected, $records);
    }

    public function testReadHaving(): void
    {
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();

        $records = $storage->read('articles', [
            'limit' => 1
        ]);
        $this->assertCount(1, $records);
        $this->assertEquals(1000, $records[0]['id']);
    }

    public function testLimitOffset(): void
    {
        $storage = $this->createDataSource();

        $records = $storage->read('articles', [
            'limit' => 1,
            'offset' => 1
        ]);
        $this->assertCount(1, $records);
        $this->assertEquals(1001, $records[0]['id']);
    }

    public function testUpdate(): void
    {
        $storage = $this->createDataSource();

        $this->assertEquals(1, $storage->update('articles', ['title' => 'foo'], ['criteria' => ['id' => 1000]]));

        $records = $storage->read('articles', ['order' => ['id ASC']]);

        $this->assertEquals('foo', $records[0]['title']);
        $this->assertNotEquals('foo', $records[1]['title']);
        $this->assertNotEquals('foo', $records[2]['title']);
    }

    public function testUpdateAll(): void
    {
        $storage = $this->createDataSource();

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
        $storage = $this->createDataSource();

        $this->assertEquals(1, $storage->delete('articles', ['criteria' => ['id' => 1000]]));
        $this->assertEquals(2, $storage->count('articles'));
    }

    /**
    * @depends testCount
    */
    public function testDeleteAll(): void
    {
        $storage = $this->createDataSource();

        $this->assertEquals(3, $storage->delete('articles'));
        $this->assertEquals(0, $storage->count('articles'));
    }

    public function testExecuteLogged(): void
    {
        $this->logger->clearLog();

        $dataSource = $this->createDataSource();
        $dataSource->setLogger($this->logger);
        // $dataSource->getPDO()->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);

        $dataSource->read('articles', ['criteria' => ['id !=' => 1000]]);
        $this->assertStringContainsString('debug,SELECT * FROM articles WHERE articles.id <> 1000,[]', $this->logger->getLog());
    }

    /**
     * The exception is thrown during the prepare statement, not execute, so this test disables exception mode
     * to ensure that it is tested if use has this disabled
     */
    public function testExecutePrepareDoesNotReturnStatement(): void
    {
        $this->logger->clearLog();

        if($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'){
            $this->markTestSkipped('This test does not work with pgsql driver');
        }
    
        $dataSource = $this->createDataSource();
        $dataSource->setLogger($this->logger);
        $dataSource->getPDO()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT); // disable exceptions throwing

        $this->expectException(DataSourceException::class);
        $dataSource->execute('SELECT * FROM pink_potatoes');
    }

    /**
     * @depends testExecutePrepareDoesNotReturnStatement
     */
    public function testExceptionWasLogged(): void
    {
        $dataSource = $this->createDataSource();
        $dataSource->setLogger($this->logger);

        $this->assertStringContainsString('error,no such table: pink_potatoes,[]', $this->logger->getLog());
    }

    public function testGetPDO(): void
    {
        $this->assertInstanceOf(PDO::class, $this->createDataSource()->getPDO());
    }

    public function testGetQueryBuilder(): void
    {
        $this->assertInstanceOf(QueryBuilder::class, $this->createDataSource()->getQueryBuilder());
    }

    public function testSetGetLogger(): void
    {
        $dataSource = $this->createDataSource();
        $this->assertNull($dataSource->getLogger());
        $this->assertInstanceOf(LoggerInterface::class, $dataSource->setLogger($this->logger)->getLogger());
    }
}
