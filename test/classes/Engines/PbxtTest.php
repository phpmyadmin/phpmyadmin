<?php
/**
 * Tests for PMA_StorageEngine_pbxt
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Core;
use PhpMyAdmin\Engines\Pbxt;
use PhpMyAdmin\Tests\AbstractTestCase;
use function sprintf;

class PbxtTest extends AbstractTestCase
{
    /** @var Pbxt */
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
        parent::loadDefaultConfig();

        $GLOBALS['server'] = 0;
        $this->object = new Pbxt('pbxt');
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
            $this->object->getVariables(),
            [
                'pbxt_index_cache_size' => [
                    'title' => __('Index cache size'),
                    'desc'  => __(
                        'This is the amount of memory allocated to the'
                        . ' index cache. Default value is 32MB. The memory'
                        . ' allocated here is used only for caching index pages.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_record_cache_size' => [
                    'title' => __('Record cache size'),
                    'desc'  => __(
                        'This is the amount of memory allocated to the'
                        . ' record cache used to cache table data. The default'
                        . ' value is 32MB. This memory is used to cache changes to'
                        . ' the handle data (.xtd) and row pointer (.xtr) files.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_log_cache_size' => [
                    'title' => __('Log cache size'),
                    'desc'  => __(
                        'The amount of memory allocated to the'
                        . ' transaction log cache used to cache on transaction log'
                        . ' data. The default is 16MB.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_log_file_threshold' => [
                    'title' => __('Log file threshold'),
                    'desc'  => __(
                        'The size of a transaction log before rollover,'
                        . ' and a new log is created. The default value is 16MB.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_transaction_buffer_size' => [
                    'title' => __('Transaction buffer size'),
                    'desc'  => __(
                        'The size of the global transaction log buffer'
                        . ' (the engine allocates 2 buffers of this size).'
                        . ' The default is 1MB.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_checkpoint_frequency' => [
                    'title' => __('Checkpoint frequency'),
                    'desc'  => __(
                        'The amount of data written to the transaction'
                        . ' log before a checkpoint is performed.'
                        . ' The default value is 24MB.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_data_log_threshold' => [
                    'title' => __('Data log threshold'),
                    'desc'  => __(
                        'The maximum size of a data log file. The default'
                        . ' value is 64MB. PBXT can create a maximum of 32000 data'
                        . ' logs, which are used by all tables. So the value of'
                        . ' this variable can be increased to increase the total'
                        . ' amount of data that can be stored in the database.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_garbage_threshold' => [
                    'title' => __('Garbage threshold'),
                    'desc'  => __(
                        'The percentage of garbage in a data log file'
                        . ' before it is compacted. This is a value between 1 and'
                        . ' 99. The default is 50.'
                    ),
                    'type'  => 2,
                ],
                'pbxt_log_buffer_size' => [
                    'title' => __('Log buffer size'),
                    'desc'  => __(
                        'The size of the buffer used when writing a data'
                        . ' log. The default is 256MB. The engine allocates one'
                        . ' buffer per thread, but only if the thread is required'
                        . ' to write a data log.'
                    ),
                    'type'  => 1,
                ],
                'pbxt_data_file_grow_size' => [
                    'title' => __('Data file grow size'),
                    'desc'  => __('The grow size of the handle data (.xtd) files.'),
                    'type'  => 1,
                ],
                'pbxt_row_file_grow_size' => [
                    'title' => __('Row file grow size'),
                    'desc'  => __('The grow size of the row pointer (.xtr) files.'),
                    'type'  => 1,
                ],
                'pbxt_log_file_count' => [
                    'title' => __('Log file count'),
                    'desc'  => __(
                        'This is the number of transaction log files'
                        . ' (pbxt/system/xlog*.xt) the system will maintain. If the'
                        . ' number of logs exceeds this value then old logs will be'
                        . ' deleted, otherwise they are renamed and given the next'
                        . ' highest number.'
                    ),
                    'type'  => 2,
                ],
            ]
        );
    }

    /**
     * Test for resolveTypeSize
     *
     * @param string $formatted_size the size expression (for example 8MB)
     * @param array  $output         Expected output
     *
     * @dataProvider providerFortTestResolveTypeSize
     */
    public function testResolveTypeSize(string $formatted_size, array $output): void
    {
        $this->assertEquals(
            $this->object->resolveTypeSize($formatted_size),
            $output
        );
    }

    /**
     * Provider for testResolveTypeSize
     *
     * @return array
     */
    public function providerFortTestResolveTypeSize(): array
    {
        return [
            [
                '8MB',
                [
                    0 => '8,192',
                    1 => 'KiB',
                ],
            ],
            [
                '10mb',
                [
                    0 => '-1',
                    1 => 'B',
                ],
            ],
            [
                'A4',
                [
                    0 => '0',
                    1 => 'B',
                ],
            ],
        ];
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(): void
    {
        $this->assertEquals(
            $this->object->getInfoPages(),
            ['Documentation' => 'Documentation']
        );
    }

    /**
     * Test for getPage
     */
    public function testGetPage(): void
    {
        $this->assertEquals(
            $this->object->getPage('Documentation'),
            '<p>'
            . sprintf(
                __(
                    'Documentation and further information about PBXT'
                    . ' can be found on the %sPrimeBase XT Home Page%s.'
                ),
                '<a href="' . Core::linkURL('https://mariadb.com/kb/en/mariadb/about-pbxt/')
                . '" rel="noopener noreferrer" target="_blank">',
                '</a>'
            )
            . '</p>' . "\n"
        );

        $this->assertEquals(
            $this->object->getPage('NonExistMethod'),
            false
        );
    }
}
