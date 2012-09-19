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

/**
 * Environment tests
 *
 * @package PhpMyAdmin-test
 */
class Environment_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests PHP version
     *
     * @return void
     */
    public function testPhpVersion()
    {
        $this->assertTrue(
            version_compare('5.2', phpversion(), '<='),
            'phpMyAdmin requires PHP 5.2 or above'
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
                "mysql:host=" . TESTSUITE_SERVER . ";dbname=" . TESTSUITE_DATABASE,
                TESTSUITE_USER,
                TESTSUITE_PASSWORD
            );
            $this->assertNull(
                $pdo->errorCode(),
                "Error when trying to connect to database"
            );

            //$pdo->beginTransaction();
            $test = $pdo->exec("SHOW TABLES;");
            //$pdo->commit();
            $this->assertEquals(
                0,
                $pdo->errorCode(),
                'Error trying to show tables for database'
            );
        }
        catch (Exception $e) {
            $this->fail("Error: ".$e->getMessage());
        }

        // Check id MySQL server is 5 version
        preg_match(
            "/^(\d+)?\.(\d+)?\.(\*|\d+)/",
            $pdo->getAttribute(constant("PDO::ATTR_SERVER_VERSION")),
            $version_parts
        );
        $this->assertEquals(5, $version_parts[1]);
    }

    /**
     * Test of session handling
     *
     * @return void
     *
     * @todo Think about this test
     */
    public function testSession()
    {
        $this->markTestIncomplete();
    }
}
?>
