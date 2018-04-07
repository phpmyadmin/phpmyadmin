<?php
/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportLdi class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportLdi;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportLdi class
 *
 * @package PhpMyAdmin-test
 */
class ImportLdiTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * @var \PhpMyAdmin\DatabaseInterface
     * @access protected
     */
    protected $dbi;

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
        $GLOBALS['server'] = 0;
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'csv';
        $GLOBALS['import_handle'] = new File($GLOBALS['import_file']);
        $GLOBALS['import_handle']->open();

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
        $this->dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $this->dbi;

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
        $this->dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));
        $this->dbi->expects($this->any())->method('numRows')
            ->will($this->returnValue(10));

        $fetchRowResult = array("ON");
        $this->dbi->expects($this->any())->method('fetchRow')
            ->will($this->returnValue($fetchRowResult));

        $GLOBALS['dbi'] = $this->dbi;

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

        $this->dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $this->dbi;

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

        $this->dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $this->dbi;

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
