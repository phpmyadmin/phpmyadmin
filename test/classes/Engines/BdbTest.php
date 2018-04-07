<?php
/**
 * Tests for PhpMyAdmin\Engines\Bdb
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\Bdb;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Engines\Bdb
 *
 * @package PhpMyAdmin-test
 */
class BdbTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $this->object = new Bdb('bdb');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for getVariables
     *
     * @return void
     */
    public function testGetVariables()
    {
        $this->assertEquals(
            $this->object->getVariables(),
            array(
                'version_bdb' => array(
                    'title' => __('Version information'),
                ),
                'bdb_cache_size' => array(
                    'type'  => 1,
                ),
                'bdb_home' => array(
                ),
                'bdb_log_buffer_size' => array(
                    'type'  => 1,
                ),
                'bdb_logdir' => array(
                ),
                'bdb_max_lock' => array(
                    'type'  => 2,
                ),
                'bdb_shared_data' => array(
                ),
                'bdb_tmpdir' => array(
                ),
                'bdb_data_direct' => array(
                ),
                'bdb_lock_detect' => array(
                ),
                'bdb_log_direct' => array(
                ),
                'bdb_no_recover' => array(
                ),
                'bdb_no_sync' => array(
                ),
                'skip_sync_bdb_logs' => array(
                ),
                'sync_bdb_logs' => array(
                ),
            )
        );
    }

    /**
     * Test for getVariablesLikePattern
     *
     * @return void
     */
    public function testGetVariablesLikePattern()
    {
        $this->assertEquals(
            $this->object->getVariablesLikePattern(),
            '%bdb%'
        );
    }

    /**
     * Test for getMysqlHelpPage
     *
     * @return void
     */
    public function testGetMysqlHelpPage()
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'bdb'
        );

    }
}
