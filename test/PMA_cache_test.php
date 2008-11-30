<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for caching data in session
 *
 * @author Michal Biniek <michal@bystrzyca.pl>
 * @package phpMyAdmin-test
 * @version $Id: PMA_cache_test.php
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/common.lib.php';

/**
 * Test cache.
 *
 */
class PMA_cache_test extends PHPUnit_Framework_TestCase
{

    /**
     * temporary variable for globals array
     */

    protected $tmpGlobals;

    /**
     * temporary variable for session array
     */

    protected $tmpSession;

    /**
     * storing globals and session
     */
    public function setUp() {

        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;
        
    }

    /**
     * cacheExists test
     */

    public function testCacheExists() {
        $GLOBALS['server'] = 'server';
        $_SESSION['cache']['server_server'] = array('test_data'=>1, 'test_data_2'=>2);

        $this->assertTrue(PMA_cacheExists('test_data', true));
        $this->assertTrue(PMA_cacheExists('test_data_2', 'server'));
        $this->assertFalse(PMA_cacheExists('fake_data_2', true));
    }

    /**
     * cacheGet test
     */

    public function testCacheGet() {
        $GLOBALS['server'] = 'server';
        $_SESSION['cache']['server_server'] = array('test_data'=>1, 'test_data_2'=>2);

        $this->assertNotNull(PMA_cacheGet('test_data', true));
        $this->assertNotNull(PMA_cacheGet('test_data_2', 'server'));
        $this->assertNull(PMA_cacheGet('fake_data_2', true));
    }

    /**
     * cacheSet test
     */

    public function testCacheSet() {
        $GLOBALS['server'] = 'server';
        $_SESSION['cache']['server_server'] = array('test_data'=>1, 'test_data_2'=>2);

        PMA_cacheSet('test_data', 5, true);
        $this->assertEquals(5, $_SESSION['cache']['server_server']['test_data']);
        PMA_cacheSet('test_data_3', 3, true);
        $this->assertEquals(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    /**
     * cacheUnset test
     */

    public function testCacheUnSet() {
        $GLOBALS['server'] = 'server';
        $_SESSION['cache']['server_server'] = array('test_data'=>1, 'test_data_2'=>2);

        PMA_cacheUnset('test_data', true);
        $this->assertNull($_SESSION['cache']['server_server']['test_data']);
        PMA_cacheUnset('test_data_2', true);
        $this->assertNull($_SESSION['cache']['server_server']['test_data_2']);
    }
}
?>
