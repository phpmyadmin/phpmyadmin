<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function __;

#[CoversClass(Innodb::class)]
class InnodbTest extends AbstractTestCase
{
    protected Innodb $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $this->object = new Innodb('innodb');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
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
        self::assertSame(
            [
                'innodb_data_home_dir' => [
                    'title' => __('Data home directory'),
                    'desc' => __('The common part of the directory path for all InnoDB data files.'),
                ],
                'innodb_data_file_path' => ['title' => __('Data files')],
                'innodb_autoextend_increment' => [
                    'title' => __('Autoextend increment'),
                    'desc' => __(
                        'The increment size for extending the size of'
                        . ' an autoextending tablespace when it becomes full.',
                    ),
                    'type' => 2,
                ],
                'innodb_buffer_pool_size' => [
                    'title' => __('Buffer pool size'),
                    'desc' => __('The size of the memory buffer InnoDB uses to cache data and indexes of its tables.'),
                    'type' => 1,
                ],
                'innodb_additional_mem_pool_size' => ['title' => 'innodb_additional_mem_pool_size', 'type' => 1],
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
            $this->object->getVariables(),
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(): void
    {
        self::assertSame(
            'innodb\\_%',
            $this->object->getVariablesLikePattern(),
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(): void
    {
        self::assertSame(
            [],
            $this->object->getInfoPages(),
        );
        $this->object->support = 2;
        self::assertSame(
            ['Bufferpool' => 'Buffer Pool', 'Status' => 'InnoDB Status'],
            $this->object->getInfoPages(),
        );
    }

    /**
     * @param list<array{string, string}> $variables
     * @param list<array{string, string}> $usageTableRows
     * @param list<array{string, string}> $activityTableRows
     */
    #[DataProvider('pageBufferPoolProvider')]
    public function testGetPageBufferPool(
        array $variables,
        string $totalPages,
        string $totalBytes,
        array $usageTableRows,
        array $activityTableRows,
    ): void {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SHOW STATUS WHERE Variable_name LIKE 'Innodb\\_buffer\\_pool\\_%' OR Variable_name = 'Innodb_page_size';",
            $variables,
        );
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $pageBufferPool = (new Innodb('innodb'))->getPageBufferPool();
        $dbiDummy->assertAllQueriesConsumed();

        $expected = '<table class="table table-striped table-hover w-auto float-start caption-top">' . "\n" .
            '  <caption>Buffer pool usage</caption>' . "\n" .
            '  <tbody>' . "\n";

        foreach ($usageTableRows as $tableRow) {
            $expected .= '    <tr>' . "\n" .
                '      <th scope="row">' . $tableRow[0] . '</th>' . "\n" .
                '      <td class="font-monospace text-end">' . $tableRow[1] . '</td>' . "\n" .
                '    </tr>' . "\n";
        }

        $expected .= '  </tbody>' . "\n" .
            '  <tfoot>' . "\n" .
            '    <tr>' . "\n" .
            '      <th colspan="2">' . "\n" .
            '        Total: ' . $totalPages . ' pages / ' . $totalBytes . "\n" .
            '      </th>' . "\n" .
            '    </tr>' . "\n" .
            '  </tfoot>' . "\n" .
            '</table>' . "\n\n" .
            '<table class="table table-striped table-hover w-auto ms-4 float-start caption-top">' . "\n" .
            '  <caption>Buffer pool activity</caption>' . "\n" .
            '  <tbody>' . "\n";

        foreach ($activityTableRows as $tableRow) {
            $expected .= '    <tr>' . "\n" .
                '      <th scope="row">' . $tableRow[0] . '</th>' . "\n" .
                '      <td class="font-monospace text-end">' . $tableRow[1] . '</td>' . "\n" .
                '    </tr>' . "\n";
        }

        $expected .= '  </tbody>' . "\n" . '</table>' . "\n";

        self::assertSame($expected, $pageBufferPool);
    }

    /**
     * @return iterable<array-key, array{
     *   list<array{string, string}>,
     *   string,
     *   string,
     *   list<array{string, string}>,
     *   list<array{string, string}>
     * }>
     */
    public static function pageBufferPoolProvider(): iterable
    {
        yield [
            [
                ['Innodb_buffer_pool_pages_data', '0'],
                ['Innodb_buffer_pool_pages_dirty', '0'],
                ['Innodb_buffer_pool_pages_flushed', '0'],
                ['Innodb_buffer_pool_pages_free', '0'],
                ['Innodb_buffer_pool_pages_misc', '0'],
                ['Innodb_buffer_pool_pages_total', '4096'],
                ['Innodb_buffer_pool_read_ahead_rnd', '0'],
                ['Innodb_buffer_pool_read_ahead', '0'],
                ['Innodb_buffer_pool_read_ahead_evicted', '0'],
                ['Innodb_buffer_pool_read_requests', '64'],
                ['Innodb_buffer_pool_reads', '32'],
                ['Innodb_buffer_pool_wait_free', '0'],
                ['Innodb_buffer_pool_write_requests', '64'],
                ['Innodb_page_size', '16384'],
            ],
            '4,096',
            '65,536 KiB',
            [
                ['Free pages', '0'],
                ['Dirty pages', '0'],
                ['Pages containing data', '0'],
                ['Pages to be flushed', '0'],
                ['Busy pages', '0'],
            ],
            [
                ['Read requests', '64'],
                ['Write requests', '64'],
                ['Read misses', '32'],
                ['Write waits', '0'],
                ['Read misses in %', '50%'],
                ['Write waits in %', '0%'],
            ],
        ];

        yield [
            [
                ['Innodb_buffer_pool_pages_data', '0'],
                ['Innodb_buffer_pool_pages_dirty', '0'],
                ['Innodb_buffer_pool_pages_flushed', '0'],
                ['Innodb_buffer_pool_pages_free', '0'],
                ['Innodb_buffer_pool_pages_latched', '0'],
                ['Innodb_buffer_pool_pages_misc', '0'],
                ['Innodb_buffer_pool_pages_total', '4096'],
                ['Innodb_buffer_pool_read_ahead_rnd', '0'],
                ['Innodb_buffer_pool_read_ahead', '0'],
                ['Innodb_buffer_pool_read_ahead_evicted', '0'],
                ['Innodb_buffer_pool_read_requests', '0'],
                ['Innodb_buffer_pool_reads', '32'],
                ['Innodb_buffer_pool_wait_free', '0'],
                ['Innodb_buffer_pool_write_requests', '0'],
                ['Innodb_page_size', '16384'],
            ],
            '4,096',
            '65,536 KiB',
            [
                ['Free pages', '0'],
                ['Dirty pages', '0'],
                ['Pages containing data', '0'],
                ['Pages to be flushed', '0'],
                ['Busy pages', '0'],
                ['Latched pages', '0'],
            ],
            [
                ['Read requests', '0'],
                ['Write requests', '0'],
                ['Read misses', '32'],
                ['Write waits', '0'],
                ['Read misses in %', '---'],
                ['Write waits in %', '---'],
            ],
        ];

        yield [
            [
                ['Innodb_buffer_pool_pages_data', '1000'],
                ['Innodb_buffer_pool_pages_dirty', '2000'],
                ['Innodb_buffer_pool_pages_flushed', '3000'],
                ['Innodb_buffer_pool_pages_free', '4000'],
                ['Innodb_buffer_pool_pages_misc', '5000'],
                ['Innodb_buffer_pool_reads', '6000'],
                ['Innodb_buffer_pool_read_requests', '7000'],
                ['Innodb_buffer_pool_wait_free', '8000'],
                ['Innodb_buffer_pool_write_requests', '9000'],
                ['Innodb_buffer_pool_pages_latched', '10000'],
                ['Innodb_page_size', '11000'],
                ['Innodb_buffer_pool_pages_total', '12000'],
            ],
            '12,000',
            '129 k KiB',
            [
                ['Free pages', '4,000'],
                ['Dirty pages', '2,000'],
                ['Pages containing data', '1,000'],
                ['Pages to be flushed', '3,000'],
                ['Busy pages', '5,000'],
                ['Latched pages', '10,000'],
            ],
            [
                ['Read requests', '7,000'],
                ['Write requests', '9,000'],
                ['Read misses', '6,000'],
                ['Write waits', '8,000'],
                ['Read misses in %', '85.71%'],
                ['Write waits in %', '88.89%'],
            ],
        ];
    }

    /**
     * Test for getPageStatus
     */
    public function testGetPageStatus(): void
    {
        self::assertSame(
            '<pre id="pre_innodb_status">' . "\n\n" . '</pre>' . "\n",
            $this->object->getPageStatus(),
        );
    }

    /**
     * Test for getPage
     */
    public function testGetPage(): void
    {
        self::assertSame(
            '',
            $this->object->getPage('Status'),
        );
        $this->object->support = 2;
        self::assertSame(
            '<pre id="pre_innodb_status">' . "\n\n" . '</pre>' . "\n",
            $this->object->getPage('Status'),
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        self::assertSame(
            'innodb-storage-engine',
            $this->object->getMysqlHelpPage(),
        );
    }

    /**
     * Test for getInnodbPluginVersion
     */
    public function testGetInnodbPluginVersion(): void
    {
        self::assertSame(
            '1.1.8',
            $this->object->getInnodbPluginVersion(),
        );
    }

    /**
     * Test for supportsFilePerTable
     */
    public function testSupportsFilePerTable(): void
    {
        self::assertFalse(
            $this->object->supportsFilePerTable(),
        );
    }

    /**
     * Test for getInnodbFileFormat
     */
    public function testGetInnodbFileFormat(): void
    {
        self::assertSame(
            'Antelope',
            $this->object->getInnodbFileFormat(),
        );
    }
}
