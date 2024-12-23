<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportCsv;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function __;

#[CoversClass(ImportCsv::class)]
#[Medium]
class ImportCsvTest extends AbstractTestCase
{
    protected ImportCsv $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['errorUrl'] = 'index.php?route=/';
        $GLOBALS['error'] = false;
        Current::$database = '';
        Current::$table = '';
        Current::$sqlQuery = '';
        $GLOBALS['message'] = null;
        ImportSettings::$timeoutPassed = false;
        ImportSettings::$maximumTime = 0;
        ImportSettings::$charsetConversion = false;
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$importType = 'database';
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        ImportSettings::$importFile = 'tests/test_data/db_test.csv';
        ImportSettings::$importFileName = 'db_test';
        ImportSettings::$readMultiply = 10;
        ImportSettings::$sqlQueryDisabled = false;

        $this->object = new ImportCsv();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => "\015",
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
            ]);
        $this->object->setImportOptions($request);

        $GLOBALS['import_text'] = 'ImportCsv_Test';
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
            __('CSV'),
            $properties->getText(),
        );
        self::assertSame(
            'csv',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        DatabaseInterface::$instance = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            Current::$sqlQuery,
        );
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . ImportSettings::$importFileName . '`',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for partial import/setting table and database names in doImport
     */
    public function testDoPartialImport(): void
    {
        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
                'csv_new_tbl_name' => 'ImportTestTable',
                'csv_new_db_name' => 'ImportTestDb',
            ]);
        $this->object->setImportOptions($request);

        DatabaseInterface::$instance = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ImportTestDb` DEFAULT CHARACTER',
            Current::$sqlQuery,
        );
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `ImportTestDb`.`ImportTestTable`',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for getProperties for Table param
     */
    public function testGetPropertiesForTable(): void
    {
        $properties = $this->object->getProperties();
        self::assertSame(
            __('CSV'),
            $properties->getText(),
        );
        self::assertSame(
            'csv',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport for _getAnalyze = false, should be OK as well
     */
    public function testDoImportNotAnalysis(): void
    {
        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        DatabaseInterface::$instance = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            Current::$sqlQuery,
        );

        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . ImportSettings::$importFileName . '`',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for doImport in the most basic and normal way
     */
    public function testDoImportNormal(): void
    {
        ImportSettings::$importFile = 'none';
        $GLOBALS['import_text'] = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
            ]);
        $this->object->setImportOptions($request);

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'CSV_DB 1\' AND TABLE_NAME = \'db_test\'',
            [],
        );

        $this->object->doImport();

        self::assertSame(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'
            . 'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`db_test` (`COL 1` varchar(5), `COL 2` varchar(5));'
            . 'INSERT INTO `CSV_DB 1`.`db_test`'
            . ' (`COL 1`, `COL 2`) VALUES (\'Row 1\', \'Row 2\'),' . "\n" . ' (\'123\', \'456\');',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
        $dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test for doImport skipping headers
     */
    public function testDoImportSkipHeaders(): void
    {
        ImportSettings::$importFile = 'none';
        $GLOBALS['import_text'] = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
                'csv_col_names' => 'yes',
            ]);
        $this->object->setImportOptions($request);

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'CSV_DB 1\' AND TABLE_NAME = \'db_test\'',
            [],
        );

        $this->object->doImport();

        self::assertSame(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;'
            . 'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`db_test` (`Row 1` int(3), `Row 2` int(3));'
            . 'INSERT INTO `CSV_DB 1`.`db_test`'
            . ' (`Row 1`, `Row 2`) VALUES (123, 456);',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
        $dummyDbi->assertAllQueriesConsumed();
    }
}
