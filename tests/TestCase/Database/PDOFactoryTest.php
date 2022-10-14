<?php declare(strict_types=1);

namespace Lightning\Test\Database;

use Lightning\Database\PdoFactory;
use PDO;
use PHPUnit\Framework\TestCase;
use function Lightning\Dotenv\env;



final class PdoFactoryTest extends TestCase
{
    public function testCreate()
    {
        $pdo = ( new PdoFactory())->create(env('DB_DSN'), env('DB_USERNAME'), env('DB_PASSWORD'));
        $this->assertInstanceOf(PDO::class, $pdo);
        $this->assertFalse($pdo->getAttribute(PDO::ATTR_PERSISTENT));

        // PHP throws error when trying to get this on SQLLite.  Driver does not support this function: driver does not support that attribute
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $this->assertEquals(0, $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES)); // Strange behavior for false
        }

        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));

        $pdo = null;
    }
}