<?php
/**
 * Tests for PMA\libraries\plugins\import\ImportOds class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
use PMA\libraries\plugins\import\ImportOds;

$GLOBALS['server'] = 0;

/*
 * Include to test.
 */
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'libraries/sanitizing.lib.php';
require_once 'libraries/plugins/import/ImportOds.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\plugins\import\ImportOds class
 *
 * @package PhpMyAdmin-test
 */
class ImportOdsTest extends PMATestCase
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
        $GLOBALS['plugin_param'] = "csv";
        $this->object = new ImportOds();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.ods';

        /**
         * Load interface for zip extension.
        */
        include_once 'libraries/zip_extension.lib.php';
        $result = PMA_getZipContents($GLOBALS['import_file']);
        $GLOBALS['import_text'] = $result["data"];
        $GLOBALS['compression'] = 'application/zip';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'ods';
        $GLOBALS['import_handle'] = @fopen($GLOBALS['import_file'], 'r');

        //variable for Ods
        $_REQUEST['ods_recognize_percentages'] = true;
        $_REQUEST['ods_recognize_currency'] = true;
        $_REQUEST['ods_empty_rows'] = true;
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
            __('OpenDocument Spreadsheet'),
            $properties->getText()
        );
        $this->assertEquals(
            'ods',
            $properties->getExtension()
        );
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
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
        //$import_notice will show the import detail result
        global $import_notice, $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        //Test function called
        $this->object->doImport();

        $this->assertContains(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET '
            . 'utf8 COLLATE utf8_general_ci',
            $sql_query
        );
        $this->assertContains(
            'CREATE TABLE IF NOT EXISTS `ODS_DB`.`pma_bookmark`',
            $sql_query
        );
        $this->assertContains(
            "INSERT INTO `ODS_DB`.`pma_bookmark` (`A`, `B`, `C`, `D`) VALUES "
            . "(1, 'dbbase', NULL, 'ddd');",
            $sql_query
        );

        //asset that all databases and tables are imported
        $this->assertContains(
            'The following structures have either been created or altered.',
            $import_notice
        );
        $this->assertContains(
            'Go to database: `ODS_DB`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `ODS_DB`',
            $import_notice
        );
        $this->assertContains(
            'Go to table: `pma_bookmark`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `pma_bookmark`',
            $import_notice
        );

        //asset that the import process is finished
        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }
}
