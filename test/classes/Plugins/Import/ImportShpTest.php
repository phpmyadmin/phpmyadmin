<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportShp;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;
use function extension_loaded;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportShp
 * @requires extension zip
 */
class ImportShpTest extends AbstractTestCase
{
    protected ImportShp $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['error'] = null;
        $GLOBALS['buffer'] = null;
        $GLOBALS['maximum_time'] = null;
        $GLOBALS['charset_conversion'] = null;
        $GLOBALS['eof'] = null;
        $GLOBALS['db'] = '';
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        $GLOBALS['go_sql'] = null;

        $GLOBALS['server'] = 0;
        //setting
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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
     */
    protected function runImport(string $filename): void
    {
        $GLOBALS['import_file'] = $filename;

        $importHandle = new File($filename);
        $importHandle->setDecompressContent(true);
        $importHandle->open();

        $GLOBALS['message'] = '';
        $GLOBALS['error'] = false;
        $this->object->doImport($importHandle);
        $this->assertEquals('', $GLOBALS['message']);
        $this->assertFalse($GLOBALS['error']);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Test for getProperties
     *
     * @group medium
     */
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('ESRI Shape File'),
            $properties->getText(),
        );
        $this->assertEquals(
            'shp',
            $properties->getExtension(),
        );
        $this->assertNull($properties->getOptions());
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport with complex data
     *
     * @group medium
     * @group 32bit-incompatible
     */
    public function testImportOsm(): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        $GLOBALS['sql_query_disabled'] = false;
        $GLOBALS['db'] = '';

        //Test function called
        $this->runImport('test/test_data/dresden_osm.shp.zip');

        $this->assertMessages($GLOBALS['import_notice']);

        $endsWith = "13.737122 51.0542065)))'))";

        if (extension_loaded('dbase')) {
            $endsWith = "13.737122 51.0542065)))'),";
        }

        $this->assertStringContainsString(
            "(GeomFromText('MULTIPOLYGON((("
            . '13.737122 51.0542065,'
            . '13.7373039 51.0541298,'
            . '13.7372661 51.0540944,'
            . '13.7370842 51.0541711,'
            . $endsWith,
            $GLOBALS['sql_query'],
        );
    }

    /**
     * Test for doImport
     *
     * @group medium
     * @group 32bit-incompatible
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        $GLOBALS['sql_query_disabled'] = false;
        $GLOBALS['db'] = '';

        //Test function called
        $this->runImport('test/test_data/timezone.shp.zip');

        // asset that all sql are executed
        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `SHP_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
            $GLOBALS['sql_query'],
        );

        // dbase extension will generate different sql statement
        if (extension_loaded('dbase')) {
            $this->assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` '
                . '(`SPATIAL` geometry, `ID` int(2), `AUTHORITY` varchar(25), `NAME` varchar(42)) '
                . 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;',
                $GLOBALS['sql_query'],
            );

            $this->assertStringContainsString(
                'INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`, `ID`, `AUTHORITY`, `NAME`) VALUES',
                $GLOBALS['sql_query'],
            );
        } else {
            $this->assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` (`SPATIAL` geometry)',
                $GLOBALS['sql_query'],
            );

            $this->assertStringContainsString(
                'INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`) VALUES',
                $GLOBALS['sql_query'],
            );
        }

        $this->assertStringContainsString("GeomFromText('POINT(1294523.1759236", $GLOBALS['sql_query']);

        //asset that all databases and tables are imported
        $this->assertMessages($GLOBALS['import_notice']);
    }

    /**
     * Validates import messages
     *
     * @param string $importNotice Messages to check
     */
    protected function assertMessages(string $importNotice): void
    {
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $importNotice,
        );
        $this->assertStringContainsString('Go to database: `SHP_DB`', $importNotice);
        $this->assertStringContainsString('Edit settings for `SHP_DB`', $importNotice);
        $this->assertStringContainsString('Go to table: `TBL_NAME`', $importNotice);
        $this->assertStringContainsString('Edit settings for `TBL_NAME`', $importNotice);

        //asset that the import process is finished
        $this->assertTrue($GLOBALS['finished']);
    }
}
