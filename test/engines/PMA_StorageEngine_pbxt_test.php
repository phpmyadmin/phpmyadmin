<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_StorageEngine_pbxt
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/pbxt.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Util.class.php';
require_once 'libraries/core.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests for PMA_StorageEngine_pbxt
 *
 * @package PhpMyAdmin-test
 */
class PMA_StorageEngine_Pbxt_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_StorageEngine_Pbxt('pbxt');
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
                'pbxt_index_cache_size' => array(
                    'title' => __('Index cache size'),
                    'desc'  => __(
                        'This is the amount of memory allocated to the'
                        . ' index cache. Default value is 32MB. The memory'
                        . ' allocated here is used only for caching index pages.'
                    ),
                    'type'  => 1
                ),
                'pbxt_record_cache_size' => array(
                    'title' => __('Record cache size'),
                    'desc'  => __(
                        'This is the amount of memory allocated to the'
                        . ' record cache used to cache table data. The default'
                        . ' value is 32MB. This memory is used to cache changes to'
                        . ' the handle data (.xtd) and row pointer (.xtr) files.'
                    ),
                    'type'  => 1
                ),
                'pbxt_log_cache_size' => array(
                    'title' => __('Log cache size'),
                    'desc'  => __(
                        'The amount of memory allocated to the'
                        . ' transaction log cache used to cache on transaction log'
                        . ' data. The default is 16MB.'
                    ),
                    'type'  => 1
                ),
                'pbxt_log_file_threshold' => array(
                    'title' => __('Log file threshold'),
                    'desc'  => __(
                        'The size of a transaction log before rollover,'
                        . ' and a new log is created. The default value is 16MB.'
                    ),
                    'type'  => 1
                ),
                'pbxt_transaction_buffer_size' => array(
                    'title' => __('Transaction buffer size'),
                    'desc'  => __(
                        'The size of the global transaction log buffer'
                        . ' (the engine allocates 2 buffers of this size).'
                        . ' The default is 1MB.'
                    ),
                    'type'  => 1
                ),
                'pbxt_checkpoint_frequency' => array(
                    'title' => __('Checkpoint frequency'),
                    'desc'  => __(
                        'The amount of data written to the transaction'
                        . ' log before a checkpoint is performed.'
                        . ' The default value is 24MB.'
                    ),
                    'type'  => 1
                ),
                'pbxt_data_log_threshold' => array(
                    'title' => __('Data log threshold'),
                    'desc'  => __(
                        'The maximum size of a data log file. The default'
                        . ' value is 64MB. PBXT can create a maximum of 32000 data'
                        . ' logs, which are used by all tables. So the value of'
                        . ' this variable can be increased to increase the total'
                        . ' amount of data that can be stored in the database.'
                    ),
                    'type'  => 1
                ),
                'pbxt_garbage_threshold' => array(
                    'title' => __('Garbage threshold'),
                    'desc'  => __(
                        'The percentage of garbage in a data log file'
                        . ' before it is compacted. This is a value between 1 and'
                        . ' 99. The default is 50.'
                    ),
                    'type'  => 2
                ),
                'pbxt_log_buffer_size' => array(
                    'title' => __('Log buffer size'),
                    'desc'  => __(
                        'The size of the buffer used when writing a data'
                        . ' log. The default is 256MB. The engine allocates one'
                        . ' buffer per thread, but only if the thread is required'
                        . ' to write a data log.'
                    ),
                    'type'  => 1
                ),
                'pbxt_data_file_grow_size' => array(
                    'title' => __('Data file grow size'),
                    'desc'  => __('The grow size of the handle data (.xtd) files.'),
                    'type'  => 1
                ),
                'pbxt_row_file_grow_size' => array(
                    'title' => __('Row file grow size'),
                    'desc'  => __('The grow size of the row pointer (.xtr) files.'),
                    'type'  => 1
                ),
                'pbxt_log_file_count' => array(
                    'title' => __('Log file count'),
                    'desc'  => __(
                        'This is the number of transaction log files'
                        . ' (pbxt/system/xlog*.xt) the system will maintain. If the'
                        . ' number of logs exceeds this value then old logs will be'
                        . ' deleted, otherwise they are renamed and given the next'
                        . ' highest number.'
                    ),
                    'type'  => 2
                ),
            )
        );
    }

    /**
     * Test for resolveTypeSize
     *
     * @param string $formatted_size the size expression (for example 8MB)
     * @param string $output         Expected output
     *
     * @dataProvider providerFortTestResolveTypeSize
     *
     * @return void
     */
    public function testResolveTypeSize($formatted_size, $output)
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
    public function providerFortTestResolveTypeSize()
    {
        return array(
            array(
                '8MB',
                array (
                    0 => '8,192',
                    1 => 'KiB'
                )
            ),
            array(
                '10mb',
                array (
                    0 => '-1',
                    1 => 'B'
                )
            ),
            array(
                'A4',
                array (
                    0 => '0',
                    1 => 'B'
                )
            )
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
            $this->object->getInfoPages(),
            array(
                'Documentation' => 'Documentation'
            )
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
            $this->object->getPage('Documentation'),
            '<p>'
            . sprintf(
                __(
                    'Documentation and further information about PBXT'
                    . ' can be found on the %sPrimeBase XT Home Page%s.'
                ),
                '<a href="' . PMA_linkURL('http://www.primebase.com/xt/')
                . '" target="_blank">',
                '</a>'
            )
            . '</p>' . "\n"
            . '<h3>' . __('Related Links') . '</h3>' . "\n"
            . '<ul>' . "\n"
            . '<li><a href="' . PMA_linkURL('http://pbxt.blogspot.com/')
            . '" target="_blank">'
            . __('The PrimeBase XT Blog by Paul McCullagh')
            . '</a></li>' . "\n" . '</ul>' . "\n"
        );

        $this->assertEquals(
            $this->object->getPage('NonExistMethod'),
            false
        );
    }
}
