<?php
/**
 * tests for environment like OS, PHP, modules, ...
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;
use function constant;
use function preg_match;
use function version_compare;
use const PHP_VERSION;

/**
 * Environment tests
 */
class EnvironmentTest extends TestCase
{
    /**
     * Tests PHP version
     *
     * @return void
     */
    public function testPhpVersion()
    {
        $this->assertTrue(
            version_compare('7.1.3', PHP_VERSION, '<='),
            'phpMyAdmin requires PHP 7.1.3 or above'
        );
    }

    /**
     * Tests MySQL connection
     *
     * @return void
     */
    public function testMySQL()
    {
        try {
            $pdo = new PDO(
                'mysql:host=' . $GLOBALS['TESTSUITE_SERVER'] . ';port=' . $GLOBALS['TESTSUITE_PORT'],
                $GLOBALS['TESTSUITE_USER'],
                $GLOBALS['TESTSUITE_PASSWORD']
            );
            $this->assertNull(
                $pdo->errorCode(),
                'Error when trying to connect to database'
            );

            $pdo->exec('SHOW DATABASES;');
            $this->assertEquals(
                0,
                $pdo->errorCode(),
                'Error trying to show tables for database'
            );
        } catch (Throwable $e) {
            $this->markTestSkipped('Error: ' . $e->getMessage());
        }

        // Check id MySQL server is 5 version
        preg_match(
            '/^(\d+)?\.(\d+)?\.(\*|\d+)/',
            $pdo->getAttribute(constant('PDO::ATTR_SERVER_VERSION')),
            $version_parts
        );
        $this->assertEquals(5, $version_parts[1]);
    }
}
