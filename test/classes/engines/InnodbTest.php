<?php
/**
 * Tests for PMA_StorageEngine_innodb
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\engines\Innodb;

require_once 'libraries/database_interface.inc.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\engines\Innodb
 *
 * @package PhpMyAdmin-test
 */
class InnodbTest extends PMATestCase
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
        $this->object = new Innodb('innodb');
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
            array(
                'innodb_data_home_dir' => array(
                    'title' => __('Data home directory'),
                    'desc'  => __('The common part of the directory path for all InnoDB data files.'),
                ),
                'innodb_data_file_path' => array(
                    'title' => __('Data files'),
                ),
                'innodb_autoextend_increment' => array(
                    'title' => __('Autoextend increment'),
                    'desc'  => __('The increment size for extending the size of an autoextending tablespace when it becomes full.'),
                    'type'  => 2,
                ),
                'innodb_buffer_pool_size' => array(
                    'title' => __('Buffer pool size'),
                    'desc'  => __('The size of the memory buffer InnoDB uses to cache data and indexes of its tables.'),
                    'type'  => 1,
                ),
                'innodb_additional_mem_pool_size' => array(
                    'title' => 'innodb_additional_mem_pool_size',
                    'type'  => 1,
                ),
                'innodb_buffer_pool_awe_mem_mb' => array(
                    'type'  => 1,
                ),
                'innodb_checksums' => array(
                ),
                'innodb_commit_concurrency' => array(
                ),
                'innodb_concurrency_tickets' => array(
                    'type'  => 2,
                ),
                'innodb_doublewrite' => array(
                ),
                'innodb_fast_shutdown' => array(
                ),
                'innodb_file_io_threads' => array(
                    'type'  => 2,
                ),
                'innodb_file_per_table' => array(
                ),
                'innodb_flush_log_at_trx_commit' => array(
                ),
                'innodb_flush_method' => array(
                ),
                'innodb_force_recovery' => array(
                ),
                'innodb_lock_wait_timeout' => array(
                    'type'  => 2,
                ),
                'innodb_locks_unsafe_for_binlog' => array(
                ),
                'innodb_log_arch_dir' => array(
                ),
                'innodb_log_archive' => array(
                ),
                'innodb_log_buffer_size' => array(
                    'type'  => 1,
                ),
                'innodb_log_file_size' => array(
                    'type'  => 1,
                ),
                'innodb_log_files_in_group' => array(
                    'type'  => 2,
                ),
                'innodb_log_group_home_dir' => array(
                ),
                'innodb_max_dirty_pages_pct' => array(
                    'type'  => 2,
                ),
                'innodb_max_purge_lag' => array(
                ),
                'innodb_mirrored_log_groups' => array(
                    'type'  => 2,
                ),
                'innodb_open_files' => array(
                    'type'  => 2,
                ),
                'innodb_support_xa' => array(
                ),
                'innodb_sync_spin_loops' => array(
                    'type'  => 2,
                ),
                'innodb_table_locks' => array(
                    'type'  => 3,
                ),
                'innodb_thread_concurrency' => array(
                    'type'  => 2,
                ),
                'innodb_thread_sleep_delay' => array(
                    'type'  => 2,
                ),
            ),
            $this->object->getVariables()
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
            'innodb\\_%',
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getInfoPages
     *
     * @return void
     */
    public function testGetInfoPages()
    {
        $this->assertEquals(
            array(),
            $this->object->getInfoPages()
        );
        $this->object->support = 2;
        $this->assertEquals(
            array (
                'Bufferpool' => 'Buffer Pool',
                'Status' => 'InnoDB Status'
            ),
            $this->object->getInfoPages()
        );
    }

    /**
     * Test for getPageBufferpool
     *
     * @return void
     */
    public function testGetPageBufferpool()
    {
        $this->assertEquals(
            '<table class="data" id="table_innodb_bufferpool_usage">
    <caption class="tblHeaders">
        Buffer Pool Usage
    </caption>
    <tfoot>
        <tr>
            <th colspan="2">
                Total
                : 4,096&nbsp;pages / 65,536&nbsp;KiB
            </th>
        </tr>
    </tfoot>
    <tbody>
        <tr class="odd">
            <th>Free pages</th>
            <td class="value">0</td>
        </tr>
        <tr class="even">
            <th>Dirty pages</th>
            <td class="value">0</td>
        </tr>
        <tr class="odd">
            <th>Pages containing data</th>
            <td class="value">0
</td>
        </tr>
        <tr class="even">
            <th>Pages to be flushed</th>
            <td class="value">0
</td>
        </tr>
        <tr class="odd">
            <th>Busy pages</th>
            <td class="value">0
</td>
        </tr>    </tbody>
</table>

<table class="data" id="table_innodb_bufferpool_activity">
    <caption class="tblHeaders">
        Buffer Pool Activity
    </caption>
    <tbody>
        <tr class="odd">
            <th>Read requests</th>
            <td class="value">64
</td>
        </tr>
        <tr class="even">
            <th>Write requests</th>
            <td class="value">64
</td>
        </tr>
        <tr class="odd">
            <th>Read misses</th>
            <td class="value">32
</td>
        </tr>
        <tr class="even">
            <th>Write waits</th>
            <td class="value">0
</td>
        </tr>
        <tr class="odd">
            <th>Read misses in %</th>
            <td class="value">50   %
</td>
        </tr>
        <tr class="even">
            <th>Write waits in %</th>
            <td class="value">0 %
</td>
        </tr>
    </tbody>
</table>
',
            $this->object->getPageBufferpool()
        );
    }

    /**
     * Test for getPageStatus
     *
     * @return void
     */
    public function testGetPageStatus()
    {
        $this->assertEquals(
            '<pre id="pre_innodb_status">' . "\n" . "\n" . '</pre>' . "\n",
            $this->object->getPageStatus()
        );

    }

    /**
     * Test for getPage
     *
     * @return void
     */
    public function testGetPage()
    {
        $this->assertEquals(
            '',
            $this->object->getPage('Status')
        );
        $this->object->support = 2;
        $this->assertEquals(
            '<pre id="pre_innodb_status">' . "\n" . "\n" . '</pre>' . "\n",
            $this->object->getPage('Status')
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
            'innodb-storage-engine',
            $this->object->getMysqlHelpPage()
        );

    }

    /**
     * Test for getInnodbPluginVersion
     *
     * @return void
     */
    public function testGetInnodbPluginVersion()
    {
        $this->assertEquals(
            '1.1.8',
            $this->object->getInnodbPluginVersion()
        );

    }

    /**
     * Test for supportsFilePerTable
     *
     * @return void
     */
    public function testSupportsFilePerTable()
    {
        $this->assertFalse(
            $this->object->supportsFilePerTable()
        );

    }

    /**
     * Test for getInnodbFileFormat
     *
     * @return void
     */
    public function testGetInnodbFileFormat()
    {
        $this->assertEquals(
            'Antelope',
            $this->object->getInnodbFileFormat()
        );

    }
}
