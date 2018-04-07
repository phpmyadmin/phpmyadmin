<?php
/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportShp class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportShp;
use PhpMyAdmin\Tests\PmaTestCase;

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.inc.php' will use it globally
 */
$GLOBALS['server'] = 0;

/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportShp class
 *
 * @package PhpMyAdmin-test
 */
class ImportShpTest extends PmaTestCase
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
        $GLOBALS['import_handle'] = new File($filename);
        $GLOBALS['import_handle']->setDecompressContent(true);
        $GLOBALS['import_handle']->open();

        $GLOBALS['message'] = '';
        $GLOBALS['error'] = false;
        $this->object->doImport();
        $this->assertEquals('', $GLOBALS['message']);
        $this->assertFalse($GLOBALS['error']);
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
