<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for caching data in session
 *
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
     * @var array temporary variable for globals array
     */
    protected $tmpGlobals;

    /**
     * @var array temporary variable for session array
     */
    protected $tmpSession;

    /**
     * storing globals and session
     */
    public function setUp()
    {
        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;
    }

    /**
     * Test if cached data is available after set
     */
    public function testCacheExists()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 5, true);
        PMA_cacheSet('test_data_2', 5, true);

        $this->assertTrue(PMA_cacheExists('test_data', true));
        $this->assertTrue(PMA_cacheExists('test_data_2', 'server'));
        $this->assertFalse(PMA_cacheExists('fake_data_2', true));
    }

    /**
     * Test if cacheGet does not return data for non existing caache entries
     */
    public function testCacheGet()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 5, true);
        PMA_cacheSet('test_data_2', 5, true);

        $this->assertNotNull(PMA_cacheGet('test_data', true));
        $this->assertNotNull(PMA_cacheGet('test_data_2', 'server'));
        $this->assertNull(PMA_cacheGet('fake_data_2', true));
    }

    /**
     * Test retrieval of cached data
     */
    public function testCacheSetGet()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 25, true);

        PMA_cacheSet('test_data', 5, true);
        $this->assertEquals(5, $_SESSION['cache']['server_server']['test_data']);
        PMA_cacheSet('test_data_3', 3, true);
        $this->assertEquals(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    /**
     * Test clearing cached values
     */
    public function testCacheUnSet()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 25, true);
        PMA_cacheSet('test_data_2', 25, true);

        PMA_cacheUnset('test_data', true);
        $this->assertArrayNotHasKey('test_data', $_SESSION['cache']['server_server']);
        PMA_cacheUnset('test_data_2', true);
        $this->assertArrayNotHasKey('test_data_2', $_SESSION['cache']['server_server']);
    }
}
?>
