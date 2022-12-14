<?php declare(strict_types=1);

namespace Lightning\Test\TestCase\MessageQueue;

use PDO;
use PHPUnit\Framework\TestCase;
use function Lightning\Dotenv\env;

use Lightning\Fixture\FixtureManager;
use Lightning\Test\Fixture\MessageQueueFixture;

use Lightning\MessageQueue\DatabaseMessageQueue;

class DatabaseMessage
{
    public function __construct(protected string $body)
    {
    }

    public function getBody(): string
    {
        return $this->body;
    }
}

final class DatabaseMessageQueueTest extends TestCase
{
    protected $fixtureManager;
    private ?PDO $pdo;

    public function setUp(): void
    {
        $this->pdo = new PDO(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([MessageQueueFixture::class]);
    }

    public function tearDown(): void 
    {
        unset($this->pdo);
    }

    private function createMessageQueue(): DatabaseMessageQueue
    {
        return new DatabaseMessageQueue($this->pdo, 'queue');
    }

    public function testSend()
    {
        $this->assertTrue(
            $this->createMessageQueue()->send('default', 'foo')
        );
    }

    /**
     * @depends testSend
     */
    public function testReceive()
    {
        $queue = $this->createMessageQueue();
        $queue->send('default', 'foo');

        $this->assertEquals('foo', $queue->receive('default'));
        $this->assertNull($queue->receive('default'));
    }

    /**
     * @depends testSend
     */
    public function testReceiveDelayed()
    {
        $queue = $this->createMessageQueue();
        $queue->send('delayed', 'foo', 2);
        $this->assertNull($queue->receive('delayed'));
        sleep(2);
        $this->assertEquals('foo', $queue->receive('default'));
        $this->assertNull($queue->receive('delayed'));
    }
}
