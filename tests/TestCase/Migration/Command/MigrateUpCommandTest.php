<?php declare(strict_types=1);

namespace App\Command;

use PDO;
use PHPUnit\Framework\TestCase;

use function Lightning\Dotenv\env;

use Lightning\Migration\Migration;
use Lightning\Fixture\FixtureManager;
use Lightning\Test\PersistentPdoFactory;
use Lightning\Test\Fixture\MigrationsFixture;

use Lightning\Migration\Command\MigrateUpCommand;
use Lightning\Console\TestSuite\ConsoleIntegrationTestTrait;
use Lightning\Console\TestSuite\TestConsole;

final class MigrateUpCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected ?PDO $pdo;
    protected string $migrationFolder;

    protected FixtureManager $fixtureManager;
    protected Migration $migration;

    public function setUp(): void
    {
        // Create Connection
        $this->pdo = ( new PersistentPdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $this->fixtureManager = new FixtureManager($this->pdo);
        $this->fixtureManager->load([MigrationsFixture::class]);
        $this->migration = new Migration($this->pdo, dirname(__DIR__). '/migrations/' . $driver);

        $this->fixtureManager->truncate('migrations'); // reset db

        $this->pdo->query('DROP TABLE IF EXISTS posts_m');
        $this->pdo->query('DROP TABLE IF EXISTS articles_m');

        $migration = new Migration($this->pdo, dirname(__DIR__). '/migrations/' . $driver);
        $command = new MigrateUpCommand(new TestConsole(), $migration);
        $this->setupIntegrationTesting($command);
    }

    public function tearDown(): void
    {
        unset($this->pdo);
    }

    public function testMigrate(): void
    {
        $this->execute();
        $this->assertExitSuccess();

        $this->assertOutputContains('Running migration <info>Initial Setup</info>');
        $this->assertOutputContains('Running migration <info>Add Index Posts</info>');
        $this->assertOutputContains('Ran 2 migration(s)');
    }

    public function testMigrateNothing(): void
    {
        $migration = new Migration($this->pdo, dirname(__DIR__). '/migrations/' . $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $command = new MigrateUpCommand($this->createTestConsole(), $migration);
        $command->run([]);

        $this->execute();
        $this->assertExitSuccess();
        $this->assertOutputContains('Ran 0 migration(s)');
    }
}
