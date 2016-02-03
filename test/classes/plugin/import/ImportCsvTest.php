<?php
/**
 * Tests for PMA\libraries\plugins\import\ImportCsv class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
use PMA\libraries\plugins\import\ImportCsv;
use PMA\libraries\Theme;

$GLOBALS['server'] = 0;

/*
 * Include to test.
 */
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\plugins\import\ImportCsv class
 *
 * @package PhpMyAdmin-test
 */
class ImportCsvTest extends PMATestCase
{
    /**
     * @var ImportCsv
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
        $GLOBALS['plugin_param'] = "csv";
        $this->object = new ImportCsv();

        unset($GLOBALS['db']);

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.csv';
        $GLOBALS['import_text'] = 'ImportCsv_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['import_handle'] = @fopen($GLOBALS['import_file'], 'r');

        //separator for csv
        $GLOBALS['csv_terminated'] = "\015";
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '"';
        $GLOBALS['csv_new_line'] = 'auto';

        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
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
            __('CSV'),
            $properties->getText()
        );
        $this->assertEquals(
            'csv',
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
            'CREATE DATABASE IF NOT EXISTS `CSV_DB` DEFAULT CHARACTER',
            $sql_query
        );
        $this->assertContains(
            'CREATE TABLE IF NOT EXISTS `CSV_DB`.`TBL_NAME`',
            $sql_query
        );

        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }

    /**
     * Test for getProperties for Table param
     *
     * @return void
     *
     * @group medium
     */
    public function testGetPropertiesForTable()
    {
        $GLOBALS['plugin_param'] = 'table';
        $this->object = new ImportCsv();
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('CSV'),
            $properties->getText()
        );
        $this->assertEquals(
            'csv',
            $properties->getExtension()
        );
    }

    /**
     * Test for doImport for _getAnalyze = false, should be OK as well
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImportNotAnalysis()
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Test function called
        $this->object->doImport();

        //asset that all sql are executed
        $this->assertContains(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB` DEFAULT CHARACTER',
            $sql_query
        );

        $this->assertContains(
            'CREATE TABLE IF NOT EXISTS `CSV_DB`.`TBL_NAME`',
            $sql_query
        );

        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }
}
