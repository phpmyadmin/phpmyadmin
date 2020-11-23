<?php
/**
 * Tests for PMA_StorageEngine_innodb
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Tests\AbstractTestCase;

class InnodbTest extends AbstractTestCase
{
    /** @var Innodb */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $this->object = new Innodb('innodb');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables(): void
    {
        $this->assertEquals(
            [
                'innodb_data_home_dir' => [
                    'title' => __('Data home directory'),
                    'desc'  => __('The common part of the directory path for all InnoDB data files.'),
                ],
                'innodb_data_file_path' => [
                    'title' => __('Data files'),
                ],
                'innodb_autoextend_increment' => [
                    'title' => __('Autoextend increment'),
                    'desc'  => __(
                        'The increment size for extending the size of an'
                        . ' autoextending tablespace when it becomes full.'
                    ),
                    'type'  => 2,
                ],
                'innodb_buffer_pool_size' => [
                    'title' => __('Buffer pool size'),
                    'desc'  => __('The size of the memory buffer InnoDB uses to cache data and indexes of its tables.'),
                    'type'  => 1,
                ],
                'innodb_additional_mem_pool_size' => [
                    'title' => 'innodb_additional_mem_pool_size',
                    'type'  => 1,
                ],
                'innodb_buffer_pool_awe_mem_mb' => ['type' => 1],
                'innodb_checksums' => [],
                'innodb_commit_concurrency' => [],
                'innodb_concurrency_tickets' => ['type' => 2],
                'innodb_doublewrite' => [],
                'innodb_fast_shutdown' => [],
                'innodb_file_io_threads' => ['type' => 2],
                'innodb_file_per_table' => [],
                'innodb_flush_log_at_trx_commit' => [],
                'innodb_flush_method' => [],
                'innodb_force_recovery' => [],
                'innodb_lock_wait_timeout' => ['type' => 2],
                'innodb_locks_unsafe_for_binlog' => [],
                'innodb_log_arch_dir' => [],
                'innodb_log_archive' => [],
                'innodb_log_buffer_size' => ['type' => 1],
                'innodb_log_file_size' => ['type' => 1],
                'innodb_log_files_in_group' => ['type' => 2],
                'innodb_log_group_home_dir' => [],
                'innodb_max_dirty_pages_pct' => ['type' => 2],
                'innodb_max_purge_lag' => [],
                'innodb_mirrored_log_groups' => ['type' => 2],
                'innodb_open_files' => ['type' => 2],
                'innodb_support_xa' => [],
                'innodb_sync_spin_loops' => ['type' => 2],
                'innodb_table_locks' => ['type' => 3],
                'innodb_thread_concurrency' => ['type' => 2],
                'innodb_thread_sleep_delay' => ['type' => 2],
            ],
            $this->object->getVariables()
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(): void
    {
        $this->assertEquals(
            'innodb\\_%',
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(): void
    {
        $this->assertEquals(
            [],
            $this->object->getInfoPages()
        );
        $this->object->support = 2;
        $this->assertEquals(
            [
                'Bufferpool' => 'Buffer Pool',
                'Status' => 'InnoDB Status',
            ],
            $this->object->getInfoPages()
        );
    }

    /**
     * Test for getPageBufferpool
     */
    public function testGetPageBufferpool(): void
    {
        $this->assertEquals(
            '<table class="table table-light table-striped table-hover w-auto float-left">' . "\n" .
            '    <caption>' . "\n" .
            '        Buffer Pool Usage' . "\n" .
            '    </caption>' . "\n" .
            '    <tfoot class="thead-light">' . "\n" .
            '        <tr>' . "\n" .
            '            <th colspan="2">' . "\n" .
            '                Total: 4,096&nbsp;pages / 65,536&nbsp;KiB' . "\n" .
            '            </th>' . "\n" .
            '        </tr>' . "\n" .
            '    </tfoot>' . "\n" .
            '    <tbody>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Free pages</th>' . "\n" .
            '            <td class="text-monospace text-right">0</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Dirty pages</th>' . "\n" .
            '            <td class="text-monospace text-right">0</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Pages containing data</th>' . "\n" .
            '            <td class="text-monospace text-right">0' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Pages to be flushed</th>' . "\n" .
            '            <td class="text-monospace text-right">0' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Busy pages</th>' . "\n" .
            '            <td class="text-monospace text-right">0' . "\n" .
            '</td>' . "\n" .
            '        </tr>    </tbody>' . "\n" .
            '</table>' . "\n\n" .
            '<table class="table table-light table-striped table-hover w-auto ml-4 float-left">' . "\n" .
            '    <caption>' . "\n" .
            '        Buffer Pool Activity' . "\n" .
            '    </caption>' . "\n" .
            '    <tbody>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Read requests</th>' . "\n" .
            '            <td class="text-monospace text-right">64' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Write requests</th>' . "\n" .
            '            <td class="text-monospace text-right">64' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Read misses</th>' . "\n" .
            '            <td class="text-monospace text-right">32' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Write waits</th>' . "\n" .
            '            <td class="text-monospace text-right">0' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Read misses in %</th>' . "\n" .
            '            <td class="text-monospace text-right">50   %' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '        <tr>' . "\n" .
            '            <th scope="row">Write waits in %</th>' . "\n" .
            '            <td class="text-monospace text-right">0 %' . "\n" .
            '</td>' . "\n" .
            '        </tr>' . "\n" .
            '    </tbody>' . "\n" .
            '</table>' . "\n",
            $this->object->getPageBufferpool()
        );
    }

    /**
     * Test for getPageStatus
     */
    public function testGetPageStatus(): void
    {
        $this->assertEquals(
            '<pre id="pre_innodb_status">' . "\n\n" . '</pre>' . "\n",
            $this->object->getPageStatus()
        );
    }

    /**
     * Test for getPage
     */
    public function testGetPage(): void
    {
        $this->assertEquals(
            '',
            $this->object->getPage('Status')
        );
        $this->object->support = 2;
        $this->assertEquals(
            '<pre id="pre_innodb_status">' . "\n\n" . '</pre>' . "\n",
            $this->object->getPage('Status')
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        $this->assertEquals(
            'innodb-storage-engine',
            $this->object->getMysqlHelpPage()
        );
    }

    /**
     * Test for getInnodbPluginVersion
     */
    public function testGetInnodbPluginVersion(): void
    {
        $this->assertEquals(
            '1.1.8',
            $this->object->getInnodbPluginVersion()
        );
    }

    /**
     * Test for supportsFilePerTable
     */
    public function testSupportsFilePerTable(): void
    {
        $this->assertFalse(
            $this->object->supportsFilePerTable()
        );
    }

    /**
     * Test for getInnodbFileFormat
     */
    public function testGetInnodbFileFormat(): void
    {
        $this->assertEquals(
            'Antelope',
            $this->object->getInnodbFileFormat()
        );
    }
}
