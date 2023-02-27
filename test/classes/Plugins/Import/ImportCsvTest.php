<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportCsv;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

use function __;
use function basename;

/** @covers \PhpMyAdmin\Plugins\Import\ImportCsv */
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
        $GLOBALS['dbi'] = $this->dbi;
        $GLOBALS['server'] = 0;
        $GLOBALS['plugin_param'] = 'csv';
        $GLOBALS['errorUrl'] = 'index.php?route=/';
        $GLOBALS['error'] = false;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['sql_query'] = '';
        $GLOBALS['message'] = null;
        $GLOBALS['csv_columns'] = null;
        $GLOBALS['timeout_passed'] = null;
        $GLOBALS['maximum_time'] = null;
        $GLOBALS['charset_conversion'] = null;
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        $GLOBALS['go_sql'] = null;

        $this->object = new ImportCsv();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.csv';
        $GLOBALS['import_text'] = 'ImportCsv_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';

        //separator for csv
        $GLOBALS['csv_terminated'] = "\015";
        $GLOBALS['csv_enclosed'] = '"';
        $GLOBALS['csv_escaped'] = '"';
        $GLOBALS['csv_new_line'] = 'auto';
        $GLOBALS['import_file_name'] = basename($GLOBALS['import_file'], '.csv');

        //$_SESSION

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
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
            __('CSV'),
            $properties->getText(),
        );
        $this->assertEquals(
            'csv',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );
        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . $GLOBALS['import_file_name'] . '`',
            $GLOBALS['sql_query'],
        );

        $this->assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for partial import/setting table and database names in doImport
     *
     * @group medium
     */
    public function testDoPartialImport(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        $GLOBALS['import_file'] = 'test/test_data/db_test_partial_import.csv';
        $_REQUEST['csv_new_tbl_name'] = 'ImportTestTable';
        $_REQUEST['csv_new_db_name'] = 'ImportTestDb';
        $_REQUEST['csv_partial_import'] = 5;

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ImportTestDb` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );
        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `ImportTestDb`.`ImportTestTable`',
            $GLOBALS['sql_query'],
        );

        $this->assertTrue($GLOBALS['finished']);

        unset($_REQUEST['csv_new_tbl_name']);
        unset($_REQUEST['csv_new_db_name']);
        unset($_REQUEST['csv_partial_import']);
    }

    /**
     * Test for getProperties for Table param
     *
     * @group medium
     */
    public function testGetPropertiesForTable(): void
    {
        $GLOBALS['plugin_param'] = 'table';
        $this->object = new ImportCsv();
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('CSV'),
            $properties->getText(),
        );
        $this->assertEquals(
            'csv',
            $properties->getExtension(),
        );
    }

    /**
     * Test for doImport for _getAnalyze = false, should be OK as well
     *
     * @group medium
     */
    public function testDoImportNotAnalysis(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER',
            $GLOBALS['sql_query'],
        );

        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`' . $GLOBALS['import_file_name'] . '`',
            $GLOBALS['sql_query'],
        );

        $this->assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport in the most basic and normal way
     *
     * @group medium
     */
    public function testDoImportNormal(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;
        $GLOBALS['import_type'] = 'query';
        $GLOBALS['import_file'] = 'none';
        $GLOBALS['csv_terminated'] = ',';
        $GLOBALS['import_text'] = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

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

        $this->assertSame(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`db_test` (`COL 1` varchar(5), `COL 2` varchar(5))'
            . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;INSERT INTO `CSV_DB 1`.`db_test`'
            . ' (`COL 1`, `COL 2`) VALUES (\'Row 1\', \'Row 2\'),' . "\n" . ' (\'123\', \'456\');;',
            $GLOBALS['sql_query'],
        );

        $this->assertEquals(true, $GLOBALS['finished']);
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    /**
     * Test for doImport skipping headers
     *
     * @group medium
     */
    public function testDoImportSkipHeaders(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;
        $GLOBALS['import_type'] = 'query';
        $GLOBALS['import_file'] = 'none';
        $GLOBALS['csv_terminated'] = ',';
        $GLOBALS['import_text'] = '"Row 1","Row 2"' . "\n" . '"123","456"';

        $_REQUEST['csv_col_names'] = 'something';

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

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

        $this->assertSame(
            'CREATE DATABASE IF NOT EXISTS `CSV_DB 1` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;'
            . 'CREATE TABLE IF NOT EXISTS `CSV_DB 1`.`db_test` (`Row 1` int(3), `Row 2` int(3))'
            . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;;INSERT INTO `CSV_DB 1`.`db_test`'
            . ' (`Row 1`, `Row 2`) VALUES (123, 456);;',
            $GLOBALS['sql_query'],
        );

        $this->assertEquals(true, $GLOBALS['finished']);
        $this->dummyDbi->assertAllQueriesConsumed();
    }
}
