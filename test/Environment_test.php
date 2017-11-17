<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for environment like OS, PHP, modules, ...
 *
 * @package PhpMyAdmin-test
 */

/**
 *
 */
require_once 'config.sample.inc.php';

use PHPUnit\Framework\TestCase;

/**
 * Environment tests
 *
 * @package PhpMyAdmin-test
 */
class Environment_Test extends TestCase
{
    /**
     * Tests PHP version
     *
     * @return void
     */
    public function testPhpVersion()
    {
        $this->assertTrue(
            version_compare('5.5', phpversion(), '<='),
            'phpMyAdmin requires PHP 5.5 or above'
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
                "mysql:host=" . $GLOBALS['TESTSUITE_SERVER'],
                $GLOBALS['TESTSUITE_USER'],
                $GLOBALS['TESTSUITE_PASSWORD']
            );
            $this->assertNull(
                $pdo->errorCode(),
                "Error when trying to connect to database"
            );

            $pdo->exec("SHOW DATABASES;");
            $this->assertEquals(
                0,
                $pdo->errorCode(),
                'Error trying to show tables for database'
            );
        } catch (Exception $e) {
            $this->markTestSkipped("Error: " . $e->getMessage());
        }

        // Check id MySQL server is 5 version
        preg_match(
            "/^(\d+)?\.(\d+)?\.(\*|\d+)/",
            $pdo->getAttribute(constant("PDO::ATTR_SERVER_VERSION")),
            $version_parts
        );
        $this->assertEquals(5, $version_parts[1]);
    }
}
