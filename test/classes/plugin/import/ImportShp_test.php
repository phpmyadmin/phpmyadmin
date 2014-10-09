<?php
/**
 * Tests for ImportShp class
 *
 * @package PhpMyAdmin-test
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;

require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Table.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'libraries/Message.class.php';

/**
 * Tests for ImportShp class
 *
 * @package PhpMyAdmin-test
 */
class ImportShp_Test extends PHPUnit_Framework_TestCase
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
        // Drizzle does not support GIS data types
        if (!defined("PMA_DRIZZLE")) {
            define("PMA_DRIZZLE", false);
        } else if (PMA_DRIZZLE) {
            //PMA_DRIZZLE is defined and PMA_DRIZZLE = true
            if (PMA_HAS_RUNKIT) {
                runkit_constant_redefine("PMA_DRIZZLE", false);
            } else {
                //Drizzle does not support GIS data types
                $this->markTestSkipped("Drizzle does not support GIS data types");
            }
        }

        //setting
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $GLOBALS['cfg']['AllowUserDropDatabase'] = false;
        $GLOBALS['import_file'] = 'test/test_data/timezone.shp.zip';

        //Mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        include_once 'libraries/plugins/import/ImportShp.class.php';
        $this->object = new ImportShp();

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
        $this->object->doImport();

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
