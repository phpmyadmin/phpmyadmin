<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for caching data in session
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';

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
        PMA_CommonFunctions::getInstance()->cacheSet('test_data', 5, true);
        PMA_CommonFunctions::getInstance()->cacheSet('test_data_2', 5, true);

        $this->assertTrue(PMA_CommonFunctions::getInstance()->cacheExists('test_data', true));
        $this->assertTrue(PMA_CommonFunctions::getInstance()->cacheExists('test_data_2', 'server'));
        $this->assertFalse(PMA_CommonFunctions::getInstance()->cacheExists('fake_data_2', true));
    }

    /**
     * Test if PMA_CommonFunctions::cacheGet does not return data for non existing caache entries
     */
    public function testCacheGet()
    {
        $GLOBALS['server'] = 'server';
        PMA_CommonFunctions::getInstance()->cacheSet('test_data', 5, true);
        PMA_CommonFunctions::getInstance()->cacheSet('test_data_2', 5, true);

        $this->assertNotNull(PMA_CommonFunctions::getInstance()->cacheGet('test_data', true));
        $this->assertNotNull(PMA_CommonFunctions::getInstance()->cacheGet('test_data_2', 'server'));
        $this->assertNull(PMA_CommonFunctions::getInstance()->cacheGet('fake_data_2', true));
    }

    /**
     * Test retrieval of cached data
     */
    public function testCacheSetGet()
    {
        $GLOBALS['server'] = 'server';
        PMA_CommonFunctions::getInstance()->cacheSet('test_data', 25, true);

        PMA_CommonFunctions::getInstance()->cacheSet('test_data', 5, true);
        $this->assertEquals(5, $_SESSION['cache']['server_server']['test_data']);
        PMA_CommonFunctions::getInstance()->cacheSet('test_data_3', 3, true);
        $this->assertEquals(3, $_SESSION['cache']['server_server']['test_data_3']);
    }

    /**
     * Test clearing cached values
     */
    public function testCacheUnSet()
    {
        $GLOBALS['server'] = 'server';
        PMA_CommonFunctions::getInstance()->cacheSet('test_data', 25, true);
        PMA_CommonFunctions::getInstance()->cacheSet('test_data_2', 25, true);

        PMA_CommonFunctions::getInstance()->cacheUnset('test_data', true);
        $this->assertArrayNotHasKey('test_data', $_SESSION['cache']['server_server']);
        PMA_CommonFunctions::getInstance()->cacheUnset('test_data_2', true);
        $this->assertArrayNotHasKey('test_data_2', $_SESSION['cache']['server_server']);
    }

    /**
     * Test clearing user cache
     */
    public function testClearUserCache()
    {
        $GLOBALS['server'] = 'server';
        PMA_CommonFunctions::getInstance()->cacheSet('is_superuser', 'yes', true);
        $this->assertEquals('yes', $_SESSION['cache']['server_server']['is_superuser']);

        PMA_CommonFunctions::getInstance()->clearUserCache();
        $this->assertArrayNotHasKey('is_superuser', $_SESSION['cache']['server_server']);
    }
}
?>
