<?php
/**
 * Tests for PMA_StorageEngine_bdb
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/bdb.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';

class PMA_StorageEngine_bdb_test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_StorageEngine_bdb('bdb');
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
     */
    public function testGetMysqlHelpPage()
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'bdb'
        );

    }
}
