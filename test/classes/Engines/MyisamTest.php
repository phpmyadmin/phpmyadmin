<?php
/**
 * Tests for PMA_StorageEngine_myisam
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\Myisam;
use PhpMyAdmin\Tests\AbstractTestCase;

class MyisamTest extends AbstractTestCase
{
    /** @var Myisam */
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
        $this->object = new Myisam('myisam');
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
                'myisam_data_pointer_size' => [
                    'title' => __('Data pointer size'),
                    'desc'  => __(
                        'The default pointer size in bytes, to be used by CREATE'
                        . ' TABLE for MyISAM tables when no MAX_ROWS option is specified.'
                    ),
                    'type'  => 1,
                ],
                'myisam_recover_options' => [
                    'title' => __('Automatic recovery mode'),
                    'desc'  => __(
                        'The mode for automatic recovery of crashed MyISAM tables,'
                        . ' as set via the --myisam-recover server startup option.'
                    ),
                ],
                'myisam_max_sort_file_size' => [
                    'title' => __('Maximum size for temporary sort files'),
                    'desc'  => __(
                        'The maximum size of the temporary file MySQL is allowed'
                        . ' to use while re-creating a MyISAM index (during REPAIR TABLE,'
                        . ' ALTER TABLE, or LOAD DATA INFILE).'
                    ),
                    'type'  => 1,
                ],
                'myisam_max_extra_sort_file_size' => [
                    'title' => __('Maximum size for temporary files on index creation'),
                    'desc'  => __(
                        'If the temporary file used for fast MyISAM index creation would'
                        . ' be larger than using the key cache by the amount specified here,'
                        . ' prefer the key cache method.'
                    ),
                    'type'  => 1,
                ],
                'myisam_repair_threads' => [
                    'title' => __('Repair threads'),
                    'desc'  => __(
                        'If this value is greater than 1, MyISAM table indexes are created'
                        . ' in parallel (each index in its own thread) during the repair by sorting process.'
                    ),
                    'type'  => 2,
                ],
                'myisam_sort_buffer_size' => [
                    'title' => __('Sort buffer size'),
                    'desc'  => __(
                        'The buffer that is allocated when sorting MyISAM indexes during a'
                        . ' REPAIR TABLE or when creating indexes with CREATE INDEX or ALTER TABLE.'
                    ),
                    'type'  => 1,
                ],
                'myisam_stats_method' => [],
                'delay_key_write' => [],
                'bulk_insert_buffer_size' => ['type' => 1],
                'skip_external_locking' => [],
            ]
        );
    }
}
