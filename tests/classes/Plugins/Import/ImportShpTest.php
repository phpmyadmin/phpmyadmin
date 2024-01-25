<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportShp;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;
use function extension_loaded;

#[CoversClass(ImportShp::class)]
#[RequiresPhpExtension('zip')]
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
        ImportSettings::$charsetConversion = false;
        $GLOBALS['eof'] = null;
        Current::$database = '';
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        ImportSettings::$goSql = false;

        //setting
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        ImportSettings::$readLimit = 100000000;
        $GLOBALS['offset'] = 0;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $this->object = new ImportShp();

        $GLOBALS['compression'] = 'application/zip';
        ImportSettings::$readMultiply = 10;
        $GLOBALS['import_type'] = 'ods';
        Current::$database = '';
        Current::$table = '';
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
        self::assertEquals('', $GLOBALS['message']);
        self::assertFalse($GLOBALS['error']);
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
     */
    #[Group('medium')]
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        self::assertEquals(
            __('ESRI Shape File'),
            $properties->getText(),
        );
        self::assertEquals(
            'shp',
            $properties->getExtension(),
        );
        self::assertNull($properties->getOptions());
        self::assertEquals(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport with complex data
     */
    #[Group('medium')]
    #[Group('32bit-incompatible')]
    public function testImportOsm(): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        ImportSettings::$sqlQueryDisabled = false;
        Current::$database = '';

        //Test function called
        $this->runImport('tests/test_data/dresden_osm.shp.zip');

        $this->assertMessages($GLOBALS['import_notice']);

        $endsWith = "13.737122 51.0542065)))'))";

        if (extension_loaded('dbase')) {
            $endsWith = "13.737122 51.0542065)))'),";
        }

        self::assertStringContainsString(
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
     */
    #[Group('medium')]
    #[Group('32bit-incompatible')]
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail
        //$import_notice will show the import detail result

        ImportSettings::$sqlQueryDisabled = false;
        Current::$database = '';

        //Test function called
        $this->runImport('tests/test_data/timezone.shp.zip');

        // asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `SHP_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
            $GLOBALS['sql_query'],
        );

        // dbase extension will generate different sql statement
        if (extension_loaded('dbase')) {
            self::assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` '
                . '(`SPATIAL` geometry, `ID` int(2), `AUTHORITY` varchar(25), `NAME` varchar(42)) '
                . 'DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;',
                $GLOBALS['sql_query'],
            );

            self::assertStringContainsString(
                'INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`, `ID`, `AUTHORITY`, `NAME`) VALUES',
                $GLOBALS['sql_query'],
            );
        } else {
            self::assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` (`SPATIAL` geometry)',
                $GLOBALS['sql_query'],
            );

            self::assertStringContainsString(
                'INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`) VALUES',
                $GLOBALS['sql_query'],
            );
        }

        self::assertStringContainsString("GeomFromText('POINT(1294523.1759236", $GLOBALS['sql_query']);

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
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $importNotice,
        );
        self::assertStringContainsString('Go to database: `SHP_DB`', $importNotice);
        self::assertStringContainsString('Edit settings for `SHP_DB`', $importNotice);
        self::assertStringContainsString('Go to table: `TBL_NAME`', $importNotice);
        self::assertStringContainsString('Edit settings for `TBL_NAME`', $importNotice);

        //asset that the import process is finished
        self::assertTrue($GLOBALS['finished']);
    }
}
