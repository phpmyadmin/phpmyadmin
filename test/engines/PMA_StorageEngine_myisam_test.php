<?php
/**
 * Tests for PMA_StorageEngine_myisam
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/myisam.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests for PMA_StorageEngine_myisam
 *
 * @package PhpMyAdmin-test
 */
class PMA_StorageEngine_Myisam_Test extends PHPUnit_Framework_TestCase
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
        $this->object = new PMA_StorageEngine_Myisam('myisam');
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
                'myisam_data_pointer_size' => array(
                    'title' => __('Data pointer size'),
                    'desc'  => __('The default pointer size in bytes, to be used by CREATE TABLE for MyISAM tables when no MAX_ROWS option is specified.'),
                    'type'  => 1,
                ),
                'myisam_recover_options' => array(
                    'title' => __('Automatic recovery mode'),
                    'desc'  => __('The mode for automatic recovery of crashed MyISAM tables, as set via the --myisam-recover server startup option.'),
                ),
                'myisam_max_sort_file_size' => array(
                    'title' => __('Maximum size for temporary sort files'),
                    'desc'  => __('The maximum size of the temporary file MySQL is allowed to use while re-creating a MyISAM index (during REPAIR TABLE, ALTER TABLE, or LOAD DATA INFILE).'),
                    'type'  => 1,
                ),
                'myisam_max_extra_sort_file_size' => array(
                    'title' => __('Maximum size for temporary files on index creation'),
                    'desc'  => __('If the temporary file used for fast MyISAM index creation would be larger than using the key cache by the amount specified here, prefer the key cache method.'),
                    'type'  => 1,
                ),
                'myisam_repair_threads' => array(
                    'title' => __('Repair threads'),
                    'desc'  => __('If this value is greater than 1, MyISAM table indexes are created in parallel (each index in its own thread) during the repair by sorting process.'),
                    'type'  => 2,
                ),
                'myisam_sort_buffer_size' => array(
                    'title' => __('Sort buffer size'),
                    'desc'  => __('The buffer that is allocated when sorting MyISAM indexes during a REPAIR TABLE or when creating indexes with CREATE INDEX or ALTER TABLE.'),
                    'type'  => 1,
                ),
                'myisam_stats_method' => array(
                ),
                'delay_key_write' => array(
                ),
                'bulk_insert_buffer_size' => array(
                    'type'  => 1,
                ),
                'skip_external_locking' => array(
                ),
            )
        );
    }


}
