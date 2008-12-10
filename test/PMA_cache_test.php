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
     * cacheExists test
     */
    public function testCacheExists()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 5, true);

        $this->assertTrue(PMA_cacheExists('test_data', 'server'));
    }

    /**
     * cacheNotExists test
     */
    public function testCacheNotExists()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 5, true);

        $this->assertFalse(PMA_cacheExists('fake_data_2', true));
    }

    /**
     * cacheGet test
     */
    public function testCacheSetGet()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 25, true);

        $this->assertNotNull(PMA_cacheGet('test_data', true));
    }

    /**
     * cacheUnset test
     */
    public function testCacheUnSet()
    {
        $GLOBALS['server'] = 'server';
        PMA_cacheSet('test_data', 25, true);
        PMA_cacheUnset('test_data', true);
        $this->assertFalse(PMA_cacheExists('test_data', true));
    }
}
?>
