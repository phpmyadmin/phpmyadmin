<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Import\ImportSettings;
use PhpMyAdmin\Plugins\Import\ImportCsv;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

use function __;
use function basename;

#[CoversClass(ImportCsv::class)]
#[Medium]
class ImportCsvTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected ImportCsv $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $GLOBALS['errorUrl'] = 'index.php?route=/';
        $GLOBALS['error'] = false;
        Current::$database = '';
        Current::$table = '';
        $GLOBALS['sql_query'] = '';
        $GLOBALS['message'] = null;
        $GLOBALS['csv_columns'] = null;
        ImportSettings::$timeoutPassed = false;
        ImportSettings::$maximumTime = 0;
        ImportSettings::$charsetConversion = false;
        ImportSettings::$skipQueries = 0;
        ImportSettings::$maxSqlLength = 0;
        ImportSettings::$executedQueries = 0;
        ImportSettings::$runQuery = false;
        ImportSettings::$goSql = false;
        ImportSettings::$importType = 'database';

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

        //setting
        ImportSettings::$finished = false;
        ImportSettings::$readLimit = 100000000;
        ImportSettings::$offset = 0;
        Config::getInstance()->selectedServer['DisableIS'] = false;

        ImportSettings::$importFile = 'tests/test_data/db_test.csv';
        $GLOBALS['import_text'] = 'ImportCsv_Test';
        $GLOBALS['compression'] = 'none';
        ImportSettings::$readMultiply = 10;

        ImportSettings::$importFileName = basename(ImportSettings::$importFile, '.csv');

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        DatabaseInterface::$instance = $dbi;
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
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . ImportSettings::$importFileName . '`',
            $GLOBALS['sql_query'],
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for partial import/setting table and database names in doImport
     */
    public function testDoPartialImport(): void
    {
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        ImportSettings::$importFile = 'tests/test_data/db_test_partial_import.csv';
        $_REQUEST['csv_new_tbl_name'] = 'ImportTestTable';
        $_REQUEST['csv_new_db_name'] = 'ImportTestDb';
        $_REQUEST['csv_partial_import'] = 5;

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ImportTestDb` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );
        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `ImportTestDb`.`ImportTestTable`',
            $GLOBALS['sql_query'],
        );

        self::assertTrue(ImportSettings::$finished);

        unset($_REQUEST['csv_new_tbl_name']);
        unset($_REQUEST['csv_new_db_name']);
        unset($_REQUEST['csv_partial_import']);
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
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;

        $importHandle = new File(ImportSettings::$importFile);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );

        self::assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . ImportSettings::$importFileName . '`',
            $GLOBALS['sql_query'],
        );

        self::assertTrue(ImportSettings::$finished);
    }

    /**
     * Test for doImport in the most basic and normal way
     */
    public function testDoImportNormal(): void
    {
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$importFile = 'none';
        $GLOBALS['csv_terminated'] = ',';
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

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $this->dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $this->dummyDbi->addResult(
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
            $GLOBALS['sql_query'],
        );

        self::assertTrue(ImportSettings::$finished);
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test for doImport skipping headers
     */
    public function testDoImportSkipHeaders(): void
    {
        //$sql_query_disabled will show the import SQL detail

        ImportSettings::$sqlQueryDisabled = false;
        ImportSettings::$importFile = 'none';
        $GLOBALS['csv_terminated'] = ',';
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

        $_REQUEST['csv_col_names'] = 'something';

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        $this->dummyDbi->addResult(
            'SHOW DATABASES',
            [],
        );

        $this->dummyDbi->addResult(
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
            $GLOBALS['sql_query'],
        );

        self::assertTrue(ImportSettings::$finished);
        $this->dummyDbi->assertAllQueriesConsumed();
    }
}
