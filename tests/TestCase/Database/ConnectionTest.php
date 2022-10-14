<?php declare(strict_types=1);

namespace Lightning\Test\Database;

use PDO;
use Exception;
use PDOException;
use PHPUnit\Framework\TestCase;
use Lightning\Database\Statement;
use function Lightning\Dotenv\env;
use Lightning\Database\Connection;
use Lightning\Test\PersistentPdoFactory;
use Lightning\Fixture\FixtureManager;
use Lightning\Test\Fixture\TagsFixture;
use Lightning\QueryBuilder\QueryBuilder;
use Lightning\Test\Fixture\ArticlesFixture;

final class ConnectionTest extends TestCase
{

    private ?PDO $pdo;
    private ?Connection $connection;

    public function setUp(): void
    {
        $this->pdo = ( new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([
            ArticlesFixture::class,
            TagsFixture::class
        ]);

    }

    public function tearDown(): void 
    {
        unset($this->pdo);
        if(isset($this->connection)){
            $this->connection->disconnect();
        }
    }


    private function createConnection(): Connection
    {
        $this->connection = new Connection(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $this->connection->connect();
        return $this->connection;
    }

    public function testGetPdo(): void
    {
        $connection = $connection = new Connection(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $this->assertNull($connection->getPdo());
        $connection->connect();
        $this->assertInstanceOf(PDO::class,$connection->getPdo());
        $connection->disconnect();
    }


    public function testIsConnected(): void 
    {
        $connection = $connection = new Connection(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $this->assertFalse($connection->isConnected());

        
        $connection->connect();
        $this->assertTrue($connection->isConnected());

        $connection->disconnect();
        $this->assertFalse($connection->isConnected());
    }

    public function testGetDriver(): void
    {
        $this->assertContains($this->createConnection()->getDriver(), ['mysql','sqlite','pgsql']);
    }


    public function testPrepare(): void
    {
        $connection = $this->createConnection();
        $this->assertInstanceOf(Statement::class, $connection->execute('SELECT * FROM articles'));
    }

    public function testPrepareQuery(): void
    {
        $connection = $this->createConnection();
        $query = (new QueryBuilder())->select(['*'])->from('articles');
        $this->assertInstanceOf(Statement::class, $connection->execute($query));
    }

    public function testInTransaction(): void
    {
        $connection = $this->createConnection();
        $this->assertFalse($connection->inTransaction());
    }

    public function testBeginTransactionTest(): void
    {
        $connection = $this->createConnection(true);
        $this->assertTrue($connection->beginTransaction());
        $this->assertTrue($connection->inTransaction());
        $this->assertFalse($connection->beginTransaction());


        $connection->rollback();
    }

    public function testCommitTransactionTest(): void
    {
        $connection = $this->createConnection(true);

        $this->assertFalse($connection->commit());
        $this->assertFalse($connection->inTransaction());

        $this->assertTrue($connection->beginTransaction());
        $this->assertTrue($connection->commit());
        $this->assertFalse($connection->inTransaction());

        $connection->rollback();
    }

    public function testRollbackTransactionTest(): void
    {
        $connection = $this->createConnection(true);
        $this->assertFalse($connection->rollback());

        $connection->beginTransaction();

        $this->assertTrue($connection->rollback());
    }

    public function testExecute(): void
    {
        $connection = $this->createConnection(true);

        $statement = $connection->execute('SELECT * FROM articles');
        $this->assertInstanceOf(Statement::class, $statement);
        $this->assertEquals('SELECT * FROM articles', $statement->getQueryString());
    }

    public function testExecuteWithParams(): void
    {
        $connection = $this->createConnection(true);

        $statement = $connection->execute('SELECT * FROM articles WHERE id = ?', [1000]);
        $this->assertEquals('SELECT * FROM articles WHERE id = ?', $statement->getQueryString());
        $this->assertCount(1, $statement->fetchAll());
    }

    public function testTransaction(): void
    {
        $connection = $this->createConnection();

        $connection->transaction(function (Connection $connection) {
            $connection->execute('UPDATE articles SET title = ? WHERE id = 1000', ['foo']);
        });

        $this->assertEquals(
            'foo',
            $connection->execute('SELECT title FROM articles WHERE id = ?', [1000])->fetchColumn(0)
        );
    }

    /**
     * @depends testTransaction
     */
    public function testTransactionRollback(): void
    {
        $connection = $this->createConnection();

        try {
            $connection->transaction(function (Connection $connection) {
                $connection->execute('UPDATE articles SET title = ? WHERE id = 1000', ['foo']);

                throw new Exception('Undo');
            });
        } catch (Exception $exception) {
        }

        $this->assertEquals(
            'Article #1',
            $connection->execute('SELECT title FROM articles WHERE id = ?', [1000])->fetchColumn(0)
        );
    }

    /**
    * @depends testTransaction
    */
    public function testTransactionRollbackCancel(): void
    {
        $connection = $this->createConnection();

        $connection->transaction(function (Connection $connection) {
            $connection->execute('UPDATE articles SET title = ? WHERE id = 1000', ['foo']);

            return false;
        });

        $this->assertEquals(
            'Article #1',
            $connection->execute('SELECT title FROM articles WHERE id = ?', [1000])->fetchColumn(0)
        );
    }

  

    public function testExecuteError(): void
    {
        $this->expectException(PDOException::class);
        $this->createConnection()->execute('SELECT * FROM foo');
    }

    public function testInsertWithPlaceHolders(): void
    {
        $connection = $this->createConnection();

        $statement = $connection->execute('INSERT INTO tags (name,created_at,updated_at) VALUES ( ? , ? , ?)', [
            'test',
            '2021-10-31 14:30:00',
            '2021-10-31 14:30:00'
        ]);

        $this->assertEquals(1, $statement->rowCount());
        $expected = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 1 : 2003;
        $this->assertEquals((string) $expected, $connection->getLastInsertId());
    }

    public function testInsertWithNamedPlaceHolders(): void
    {
        $connection = $this->createConnection();

        $statement = $connection->execute('INSERT INTO tags (name,created_at,updated_at) VALUES ( :name,:created_at, :updated_at)', [
            'name' => 'test',
            'created_at' => '2021-10-31 14:30:00',
            'updated_at' => '2021-10-31 14:30:00'
        ]);

        $this->assertEquals(1, $statement->rowCount());
        $expected = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 1 : 2003;
        $this->assertEquals((string) $expected, $connection->getLastInsertId());
    }

    public function testInsert(): void
    {
        $connection = $this->createConnection();

        $this->assertTrue(
            $connection->insert('tags', [
                'name' => 'new',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])
        );
        switch ($connection->getDriver()) {
            case 'pgsql':
                $this->assertEquals(1, $connection->getLastInsertId());
            break;
            case 'mysql':
            case 'sqlite':
                $this->assertEquals(2003, $connection->getLastInsertId());
            break;
        }
    }



    public function testUpdate(): void
    {
        $connection = $this->createConnection();

        $this->assertEquals(3,
            $connection->update('articles', [
                'title' => 'all names',
            ])
        );

        $this->assertEquals(1,
        $connection->update('articles', [
            'title' => 'just this one',
        ], ['id' => 1000])
    );
    }

    public function testDelete(): void
    {
        $connection = $this->createConnection();

        $this->assertEquals(3,
            $connection->delete('tags')
        );

        $this->assertEquals(1,
            $connection->delete('articles', ['id' => 1000])
        );
    }

    public function testAutoconnect(): void 
    {

        $connection = $this->createConnection();
        $count = $connection->execute('SELECT COUNT(*) AS count FROM users')->fetchColumn();
        $this->assertEquals(3,$count);
    }

    public function testSerialize(): void 
    {

        $connection = $this->createConnection();
        $connection->connect();
        $this->assertTrue($connection->isConnected());
        $string = serialize($connection);
        $connection2 = unserialize($string);

        $this->assertFalse($connection2->isConnected());
    }
}
