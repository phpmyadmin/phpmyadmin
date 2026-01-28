<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportCsv;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function __;

#[CoversClass(ImportCsv::class)]
#[Medium]
final class ImportCsvTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Import::$errorUrl = 'index.php?route=/';
        Import::$hasError = false;
        Current::$database = '';
        Current::$table = '';
        Current::$sqlQuery = '';
        Current::$message = null;
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
        Import::$importText = 'ImportCsv_Test';
    }

    /**
     * Test for getProperties
     */
    public function testGetProperties(): void
    {
        $importCsv = $this->getImportCsv();
        $properties = $importCsv->getProperties();
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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importCsv = $this->getImportCsv($dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => "\015",
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
            ]);
        $importCsv->setImportOptions($request);

        $importCsv->doImport($importHandle);

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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importCsv = $this->getImportCsv($dbi);

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
        $importCsv->setImportOptions($request);

        //Test function called
        $importCsv->doImport($importHandle);

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
        $importCsv = $this->getImportCsv();
        $properties = $importCsv->getProperties();
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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $importCsv = $this->getImportCsv($dbi);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => "\015",
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
            ]);
        $importCsv->setImportOptions($request);

        $importCsv->doImport($importHandle);

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
        Import::$importText = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $dummyDbi = $this->createDbiDummy();
        $importCsv = $this->getImportCsv($this->createDatabaseInterface($dummyDbi));

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
            ]);
        $importCsv->setImportOptions($request);

        $dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'CSV_DB 1\' AND TABLE_NAME = \'db_test\'',
            [],
        );

        $importCsv->doImport();

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
        Import::$importText = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $dummyDbi = $this->createDbiDummy();
        $importCsv = $this->getImportCsv($this->createDatabaseInterface($dummyDbi));

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
                'csv_col_names' => 'yes',
            ]);
        $importCsv->setImportOptions($request);

        $dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'CSV_DB 1\' AND TABLE_NAME = \'db_test\'',
            [],
        );

        $importCsv->doImport();

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

    /**
     * Test for doImport skipping headers but with ignore mode
     */
    public function testDoImportSkipHeadersInsertIgnore(): void
    {
        Current::$database = 'public';
        Current::$table = 'csv_file_table';
        ImportSettings::$importFile = 'none';
        Import::$importText = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $dummyDbi = $this->createDbiDummy();
        $importCsv = $this->getImportCsv($this->createDatabaseInterface($dummyDbi));

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody([
                'csv_terminated' => ',',
                'csv_enclosed' => '"',
                'csv_escaped' => '"',
                'csv_new_line' => 'auto',
                'csv_columns' => null,
                'csv_ignore' => 'yes',
                'csv_col_names' => 'yes',
                'csv_new_tbl_name' => 'already_uploaded_file',
            ]);
        $importCsv->setImportOptions($request);

        $dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $dummyDbi->addResult(
            'SELECT 1 FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'public\' AND TABLE_NAME = \'already_uploaded_file\'',
            [],
        );

        $importCsv->doImport();

        self::assertSame(
            'CREATE TABLE IF NOT EXISTS `public`.`already_uploaded_file` (`Row 1` int(3), `Row 2` int(3));'
            . 'INSERT IGNORE INTO `public`.`already_uploaded_file`'
            . ' (`Row 1`, `Row 2`) VALUES (123, 456);',
            Current::$sqlQuery,
        );

        self::assertTrue(ImportSettings::$finished);
        $dummyDbi->assertAllQueriesConsumed();
    }

    private function getImportCsv(DatabaseInterface|null $dbi = null): ImportCsv
    {
        $dbiObject = $dbi ?? $this->createDatabaseInterface();
        $config = new Config();

        return new ImportCsv(new Import($dbiObject, new ResponseRenderer(), $config), $dbiObject, $config);
    }
}
