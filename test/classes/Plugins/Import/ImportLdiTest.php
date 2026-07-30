<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportLdi;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\MockObject\MockObject;

use function __;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportLdi
 * @medium
 */
#[CoversClass(ImportLdi::class)]
#[Medium]
#[AllowMockObjectsWithoutExpectations]
class ImportLdiTest extends AbstractTestCase
{
    /** @var ImportLdi */
    protected $object;

    /** @var DatabaseInterface */
    protected $dbi;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        //setting
        $GLOBALS['server'] = 0;
        $GLOBALS['plugin_param'] = 'table';
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test_ldi.csv';
        $GLOBALS['import_text'] = 'ImportLdi_Test';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'csv';

        //setting for Ldi
        $GLOBALS['cfg']['Import']['ldi_replace'] = false;
        $GLOBALS['cfg']['Import']['ldi_ignore'] = false;
        $GLOBALS['cfg']['Import']['ldi_terminated'] = ';';
        $GLOBALS['cfg']['Import']['ldi_enclosed'] = '"';
        $GLOBALS['cfg']['Import']['ldi_escaped'] = '\\';
        $GLOBALS['cfg']['Import']['ldi_new_line'] = 'auto';
        $GLOBALS['cfg']['Import']['ldi_columns'] = '';
        $GLOBALS['cfg']['Import']['ldi_local_option'] = false;
        $GLOBALS['table'] = 'phpmyadmintest';

        //Mock DBI
        $this->dbi = $this->createMock(DatabaseInterface::class);
        $GLOBALS['dbi'] = $this->dbi;

        $this->object = new ImportLdi();
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
        self::assertSame(__('CSV using LOAD DATA'), $properties->getText());
        self::assertSame('ldi', $properties->getExtension());
    }

    /**
     * Test for getProperties for ldi_local_option = auto
     */
    public function testGetPropertiesAutoLdi(): void
    {
        /**
         * The \PhpMyAdmin\DatabaseInterface mocked object
         *
         * @var MockObject $dbi
         */
        $dbi = $this->dbi;

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->method('tryQuery')->willReturn($resultStub);

        $resultStub->method('numRows')->willReturn(10);

        $resultStub->method('fetchValue')->willReturn('ON');

        $GLOBALS['cfg']['Import']['ldi_local_option'] = 'auto';
        $this->object = new ImportLdi();
        $properties = $this->object->getProperties();
        self::assertTrue($GLOBALS['cfg']['Import']['ldi_local_option']);
        self::assertSame(__('CSV using LOAD DATA'), $properties->getText());
        self::assertSame('ldi', $properties->getExtension());
    }

    /**
     * Test for doImport
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;
        /**
         * The \PhpMyAdmin\DatabaseInterface mocked object
         *
         * @var MockObject $dbi
         */
        $dbi = $this->dbi;
        $dbi->method('escapeString')->willReturnArgument(0);
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        self::assertStringContainsString(
            'LOAD DATA INFILE \'test/test_data/db_test_ldi.csv\' INTO TABLE `phpmyadmintest`',
            $sql_query
        );

        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport : invalid import file
     */
    public function testDoImportInvalidFile(): void
    {
        global $import_file;
        $import_file = 'none';

        //Test function called
        $this->object->doImport();

        // We handle only some kind of data!
        self::assertStringContainsString(
            __('This plugin does not support compressed imports!'),
            $GLOBALS['message']->__toString()
        );

        self::assertTrue($GLOBALS['error']);
    }

    /**
     * Test for doImport with LDI setting
     */
    public function testDoImportLDISetting(): void
    {
        global $ldi_local_option, $ldi_replace, $ldi_ignore, $ldi_terminated,
        $ldi_enclosed, $ldi_new_line, $skip_queries;

        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;
        /**
         * The \PhpMyAdmin\DatabaseInterface mocked object
         *
         * @var MockObject $dbi
         */
        $dbi = $this->dbi;
        $dbi->method('escapeString')->willReturnArgument(0);
        $GLOBALS['dbi'] = $dbi;

        $ldi_local_option = true;
        $ldi_replace = true;
        $ldi_ignore = true;
        $ldi_terminated = ',';
        $ldi_enclosed = ')';
        $ldi_new_line = 'newline_mark';
        $skip_queries = true;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        //replace
        self::assertStringContainsString(
            'LOAD DATA LOCAL INFILE \'test/test_data/db_test_ldi.csv\' REPLACE INTO TABLE `phpmyadmintest`',
            $sql_query
        );

        //FIELDS TERMINATED
        self::assertStringContainsString("FIELDS TERMINATED BY ','", $sql_query);

        //LINES TERMINATED
        self::assertStringContainsString("LINES TERMINATED BY 'newline_mark'", $sql_query);

        //IGNORE
        self::assertStringContainsString('IGNORE 1 LINES', $sql_query);

        self::assertTrue($GLOBALS['finished']);
    }
}
