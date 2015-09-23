<?php
/**
 * Tests for ImportLdi class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;
$GLOBALS['plugin_param'] = "table";

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Table.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'libraries/plugins/import/ImportLdi.class.php';


/**
 * Tests for ImportLdi class
 *
 * @package PhpMyAdmin-test
 */
class ImportLdi_Test extends PHPUnit_Framework_TestCase
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
        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['cfg']['AllowUserDropDatabase'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'csv';
        $GLOBALS['import_handle'] = @fopen($GLOBALS['import_file'], 'r');

        //setting for Ldi
        $GLOBALS['cfg']['Import']['ldi_replace'] = false;
        $GLOBALS['cfg']['Import']['ldi_ignore'] = false;
        $GLOBALS['cfg']['Import']['ldi_terminated'] = ';';
        $GLOBALS['cfg']['Import']['ldi_enclosed'] = '"';
        $GLOBALS['cfg']['Import']['ldi_escaped'] = '\\';
        $GLOBALS['cfg']['Import']['ldi_new_line'] = 'auto';
        $GLOBALS['cfg']['Import']['ldi_columns'] = '';
        $GLOBALS['cfg']['Import']['ldi_local_option'] = false;
        $GLOBALS['table'] = "phpmyadmintest";

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));

        $dbi->expects($this->any())->method('numRows')
            ->will($this->returnValue(10));

        $fetchRowResult = array("ON");
        $dbi->expects($this->any())->method('fetchRow')
            ->will($this->returnValue($fetchRowResult));

        $GLOBALS['dbi'] = $dbi;

        $this->object = new ImportLdi();
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
     * Test for getProperties
     *
     * @return void
     *
     * @group medium
     */
    public function testGetProperties()
    {
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText()
        );
        $this->assertEquals(
            'ldi',
            $properties->getExtension()
        );
    }

    /**
     * Test for getProperties for ldi_local_option = auto
     *
     * @return void
     *
     * @group medium
     */
    public function testGetPropertiesAutoLdi()
    {
        $GLOBALS['cfg']['Import']['ldi_local_option'] = 'auto';
        $this->object = new ImportLdi();
        $properties = $this->object->getProperties();
        $this->assertEquals(
            true,
            $GLOBALS['cfg']['Import']['ldi_local_option']
        );
        $this->assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText()
        );
        $this->assertEquals(
            'ldi',
            $properties->getExtension()
        );
    }

    /**
     * Test for doImport
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImport()
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Test function called
        $this->object->doImport();

        //asset that all sql are executed
        $this->assertContains(
            "LOAD DATA INFILE 'test/test_data/db_test_ldi.csv' INTO TABLE "
            . "`phpmyadmintest`",
            $sql_query
        );

        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }

    /**
     * Test for doImport : invalid import file
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImportInvalidFile()
    {
        global $import_file;
        $import_file = 'none';

        //Test function called
        $this->object->doImport();

        // We handle only some kind of data!
        $this->assertContains(
            __('This plugin does not support compressed imports!'),
            $GLOBALS['message']->__toString()
        );

        $this->assertEquals(
            true,
            $GLOBALS['error']
        );
    }

    /**
     * Test for doImport with LDI setting
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImportLDISetting()
    {
        global $ldi_local_option, $ldi_replace, $ldi_ignore, $ldi_terminated,
        $ldi_enclosed, $ldi_new_line, $skip_queries;

        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        $ldi_local_option = true;
        $ldi_replace = true;
        $ldi_ignore = true;
        $ldi_terminated = ',';
        $ldi_enclosed = ')';
        $ldi_new_line = 'newline_mark';
        $skip_queries = true;

        //Test function called
        $this->object->doImport();

        //asset that all sql are executed
        //replace
        $this->assertContains(
            "LOAD DATA LOCAL INFILE 'test/test_data/db_test_ldi.csv' REPLACE INTO "
            . "TABLE `phpmyadmintest`",
            $sql_query
        );

        //FIELDS TERMINATED
        $this->assertContains(
            "FIELDS TERMINATED BY ','",
            $sql_query
        );

        //LINES TERMINATED
        $this->assertContains(
            "LINES TERMINATED BY 'newline_mark'",
            $sql_query
        );

        //IGNORE
        $this->assertContains(
            "IGNORE 1 LINES",
            $sql_query
        );

        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }
}
