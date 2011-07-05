<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for environment like OS, PHP, modules, ...
 *
 * @package phpMyAdmin-test
 */

/**
 *
 */
require_once 'config.sample.inc.php';

/**
 * @package phpMyAdmin-test
 */
class Environment_test extends PHPUnit_Framework_TestCase
{
    public function testPhpVersion()
    {
        $this->assertTrue(version_compare('5.2', phpversion(), '<='),
            'phpMyAdmin requires PHP 5.2 or above');
    }

    public function testMySQL()
    {
        global $cfg;

        foreach($cfg['Servers'] as $i=>$server){
            // Check config for the server
            if (!isset($server["host"])){
                $this->fail("Couldn't determine the host. Please check configuration for the server id: $i");
            }
            if (!isset($server["pmadb"])){
                $this->markTestSkipped(); // If DB is not specified there is no reason to check connect.
            }
            elseif(!isset($server["controluser"])){
                $this->fail("Please specify user for server $i and database ".$server["pmadb"]);
            }

            try{
                if (!isset($server["controlpass"])){
                    $pdo = new PDO("mysql:host=".$server["host"].";dbname=".$server["pmadb"], $server['controluser']);
                }
                else{
                    $pdo = new PDO("mysql:host=".$server["host"].";dbname=".$server["pmadb"], $server['controluser'], $server['controlpass']);
                }

                $this->assertNull($pdo->errorCode());

                //$pdo->beginTransaction();
                $test = $pdo->exec("SHOW TABLES;");
                //$pdo->commit();
                $this->assertEquals(0, $pdo->errorCode());
            }
            catch (Exception $e){
                $this->fail("Error: ".$e->getMessage());
            }

            // Check id MySQL server is 5 version
            preg_match("/^(\d+)?\.(\d+)?\.(\*|\d+)/", $pdo->getAttribute(constant("PDO::ATTR_SERVER_VERSION")), $version_parts);
            $this->assertEquals(5, $version_parts[1]);
        }
    }

    //TODO: Think about this test
//    public function testSession()
//    {
//        $this->markTestIncomplete();
//    }
}
?>
