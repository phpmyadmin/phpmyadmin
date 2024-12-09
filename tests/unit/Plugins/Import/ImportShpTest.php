<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportShp;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

use function __;
use function extension_loaded;

#[CoversClass(ImportShp::class)]
#[RequiresPhpExtension('zip')]
#[Medium]
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
        ImportSettings::$maximumTime = 0;
        ImportSettings::$charsetConversion = false;
        $GLOBALS['eof'] = null;
        Current::$database = '';
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        Current::$sqlQuery = '';
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$importType = 'table';
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;

        $this->object = new ImportShp();

        ImportSettings::$readMultiply = 10;
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
        ImportSettings::$importFile = $filename;

        $importHandle = new File($filename);
        $importHandle->setDecompressContent(true);
        $importHandle->open();

        $GLOBALS['message'] = '';
        $GLOBALS['error'] = false;
        $this->object->doImport($importHandle);
        self::assertSame('', $GLOBALS['message']);
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
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        self::assertSame(
            __('ESRI Shape File'),
            $properties->getText(),
        );
        self::assertSame(
            'shp',
            $properties->getExtension(),
        );
        self::assertNull($properties->getOptions());
        self::assertSame(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport with complex data
     */
    #[Group('32bit-incompatible')]
    public function testImportOsm(): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail
        Current::$database = '';

        $this->runImport('tests/test_data/dresden_osm.shp.zip');

        $this->assertMessages(ImportSettings::$importNotice);

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
            Current::$sqlQuery,
        );
    }

    /**
     * Test for doImport
     */
    #[Group('32bit-incompatible')]
    public function testDoImport(): void
    {
        ImportSettings::$sqlQueryDisabled = false; //will show the import SQL detail
        Current::$database = '';

        $this->runImport('tests/test_data/timezone.shp.zip');

        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `SHP_DB` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci',
            Current::$sqlQuery,
        );

        // dbase extension will generate different sql statement
        if (extension_loaded('dbase')) {
            self::assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` '
                . '(`SPATIAL` geometry, `ID` int(2), `AUTHORITY` varchar(25), `NAME` varchar(42));',
                Current::$sqlQuery,
            );

            self::assertStringContainsString(
                'INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`, `ID`, `AUTHORITY`, `NAME`) VALUES',
                Current::$sqlQuery,
            );
        } else {
            self::assertStringContainsString(
                'CREATE TABLE IF NOT EXISTS `SHP_DB`.`TBL_NAME` (`SPATIAL` geometry)',
                Current::$sqlQuery,
            );

            self::assertStringContainsString('INSERT INTO `SHP_DB`.`TBL_NAME` (`SPATIAL`) VALUES', Current::$sqlQuery);
        }

        self::assertStringContainsString("GeomFromText('POINT(1294523.1759236", Current::$sqlQuery);

        //assert that all databases and tables are imported
        $this->assertMessages(ImportSettings::$importNotice);
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

        //assert that the import process is finished
        self::assertTrue(ImportSettings::$finished);
    }
}
