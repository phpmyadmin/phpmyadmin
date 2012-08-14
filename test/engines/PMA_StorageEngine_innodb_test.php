<?php
/**
 * Tests for PMA_StorageEngine_innodb
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/innodb.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/CommonFunctions.class.php';

class PMA_StorageEngine_innodb_test extends PHPUnit_Framework_TestCase
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
        if (! defined('PMA_DRIZZLE')) {
            define('PMA_DRIZZLE', 1);
        }
        if (! function_exists('PMA_DBI_fetch_result')) {
            function PMA_DBI_fetch_result($query)
            {
                return array(
                    'dummy' => 'table1',
                    'engine' => 'table`2',
                    'Innodb_buffer_pool_pages_total' => 10,
                    'Innodb_page_size' => 4,
                    'Innodb_buffer_pool_pages_free' => 4,
                    'Innodb_buffer_pool_pages_dirty' => 3,
                    'Innodb_buffer_pool_pages_data' => 2,
                    'Innodb_buffer_pool_pages_flushed' => 5,
                    'Innodb_buffer_pool_pages_misc' => 1,
                    'Innodb_buffer_pool_pages_latched' => 2,
                    'Innodb_buffer_pool_read_requests' => 3,
                    'Innodb_buffer_pool_write_requests' => 4,
                    'Innodb_buffer_pool_reads' => 4,
                    'Innodb_buffer_pool_wait_free' => 3,
                );
            }
        }
        $this->object = $this->getMockForAbstractClass('PMA_StorageEngine_innodb', array('dummy'));
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
    public function testGetVariables(){
        $this->assertEquals(
            $this->object->getVariables(),
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
            )
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(){
        $this->assertEquals(
            $this->object->getVariablesLikePattern(),
            'innodb\\_%'
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(){
        $this->assertEquals(
            $this->object->getInfoPages(),
            array()
        );
        $this->object->support = 2;
        $this->assertEquals(
            $this->object->getInfoPages(),
            array (
                'Bufferpool' => 'Buffer Pool',
                'Status' => 'InnoDB Status'
            )
        );
    }

    /**
     * Test for getPageBufferpool
     */
    public function testGetPageBufferpool(){
        $this->assertEquals(
            $this->object->getPageBufferpool(),
            '<table class="data" id="table_innodb_bufferpool_usage">
    <caption class="tblHeaders">
        Buffer Pool Usage
    </caption>
    <tfoot>
        <tr>
            <th colspan="2">
                Total
                : 10&nbsp;pages / 40&nbsp;B
            </th>
        </tr>
    </tfoot>
    <tbody>
        <tr class="odd">
            <th>Free pages</th>
            <td class="value">4</td>
        </tr>
        <tr class="even">
            <th>Dirty pages</th>
            <td class="value">3</td>
        </tr>
        <tr class="odd">
            <th>Pages containing data</th>
            <td class="value">2
</td>
        </tr>
        <tr class="even">
            <th>Pages to be flushed</th>
            <td class="value">5
</td>
        </tr>
        <tr class="odd">
            <th>Busy pages</th>
            <td class="value">1
</td>
        </tr>        <tr class="even">            <th>Latched pages</th>            <td class="value">2</td>        </tr>    </tbody>
</table>

<table class="data" id="table_innodb_bufferpool_activity">
    <caption class="tblHeaders">
        Buffer Pool Activity
    </caption>
    <tbody>
        <tr class="odd">
            <th>Read requests</th>
            <td class="value">3
</td>
        </tr>
        <tr class="even">
            <th>Write requests</th>
            <td class="value">4
</td>
        </tr>
        <tr class="odd">
            <th>Read misses</th>
            <td class="value">4
</td>
        </tr>
        <tr class="even">
            <th>Write waits</th>
            <td class="value">3
</td>
        </tr>
        <tr class="odd">
            <th>Read misses in %</th>
            <td class="value">133.33   %
</td>
        </tr>
        <tr class="even">
            <th>Write waits in %</th>
            <td class="value">75   %
</td>
        </tr>
    </tbody>
</table>
'

        );
    }

    /**
     * Test for getPageStatus
     */
    public function testGetPageStatus(){
        if (! function_exists('PMA_DBI_fetch_value')) {
            function PMA_DBI_fetch_value()
            {
                return 2;
            }
        }

        $this->assertEquals(
            $this->object->getPageStatus(),
            '<pre id="pre_innodb_status">' . "\n"
                . 2 . "\n"
                . '</pre>' . "\n"
        );

    }

    /**
     * Test for getPage
     */
    public function testGetPage(){
        $this->assertFalse(
            $this->object->getPage('Status')
        );
        $this->object->support = 2;
        $this->assertEquals(
            $this->object->getPage('Status'),
            '<pre id="pre_innodb_status">' . "\n"
                . 2 . "\n"
                . '</pre>' . "\n"
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(){
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'innodb'
        );

    }

    /**
     * Test for getInnodbPluginVersion
     */
    public function testGetInnodbPluginVersion(){
        $this->assertEquals(
            $this->object->getInnodbPluginVersion(),
            2
        );

    }

    /**
     * Test for supportsFilePerTable
     */
    public function testSupportsFilePerTable(){
        $this->assertEquals(
            $this->object->supportsFilePerTable(),
            false
        );

    }

    /**
     * Test for getInnodbFileFormat
     */
    public function testGetInnodbFileFormat(){
        $this->assertEquals(
            $this->object->getInnodbFileFormat(),
            2
        );

    }
}
