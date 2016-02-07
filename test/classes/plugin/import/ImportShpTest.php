<?php
/**
 * Tests for PMA\libraries\plugins\import\ImportShp class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
use PMA\libraries\plugins\import\ImportShp;

$GLOBALS['server'] = 0;

require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\plugins\import\ImportShp class
 *
 * @package PhpMyAdmin-test
 */
class ImportShpTest extends PMATestCase
{
    /**
     * @var ImportShp
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
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $this->object = new ImportShp();

        $GLOBALS['compression'] = 'application/zip';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'ods';
        unset($GLOBALS['db'], $GLOBALS['table']);
    }

    /**
     * Executes import of given file
     *
     * @param string $filename Name of test file
     *
     * @return void
     */
    protected function runImport($filename)
    {
        $GLOBALS['import_file'] = $filename;
        $GLOBALS['import_handle'] = @fopen($filename, 'r');

        $this->object->doImport();
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
            __('ESRI Shape File'),
            $properties->getText()
        );
        $this->assertEquals(
            'shp',
            $properties->getExtension()
        );
        $this->assertEquals(
            array(),
            $properties->getOptions()
        );
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
        );

    }

    /**
     * Test for doImport with complex data
     *
     * @return void
     *
     * @group medium
     */
    public function testImportOsm()
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result
        global $import_notice, $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Test function called
        $this->runImport('test/test_data/dresden_osm.shp.zip');

        $this->assertMessages($import_notice);
        $this->assertContains(
            "(GeomFromText('MULTIPOLYGON((("
            . "13.737122 51.0542065,"
            . "13.7373039 51.0541298,"
            . "13.7372661 51.0540944,"
            . "13.7370842 51.0541711,"
            . "13.737122 51.0542065)))'))",
            $sql_query
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

        //Test function called
        $this->runImport('test/test_data/timezone.shp.zip');

        //asset that all sql are executed
        $this->assertContains(
            'CREATE DATABASE IF NOT EXISTS `SHP_DB` DEFAULT CHARACTER '
            . 'SET utf8 COLLATE utf8_general_ci',
            $sql_query
        );
        $this->assertContains(
            'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` '
            . '(`SPATIAL` geometry) DEFAULT CHARACTER '
            . 'SET utf8 COLLATE utf8_general_ci;',
            $sql_query
        );
        $this->assertContains(
            "INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`) VALUES",
            $sql_query
        );
        $this->assertContains(
            "GeomFromText('POINT(1294523.1759236",
            $sql_query
        );

        //asset that all databases and tables are imported
        $this->assertMessages($import_notice);
    }

    /**
     * Validates import messages
     *
     * @param string $import_notice Messages to check
     *
     * @return void
     */
    protected function assertMessages($import_notice)
    {
        $this->assertContains(
            'The following structures have either been created or altered.',
            $import_notice
        );
        $this->assertContains(
            'Go to database: `SHP_DB`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `SHP_DB`',
            $import_notice
        );
        $this->assertContains(
            'Go to table: `TBL_NAME`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `TBL_NAME`',
            $import_notice
        );

        //asset that the import process is finished
        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );
    }
}
