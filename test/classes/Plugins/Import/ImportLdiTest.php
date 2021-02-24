<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportLdi;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ImportLdiTest extends AbstractTestCase
{
    /** @var ImportLdi */
    protected $object;

    /**
     * @var DatabaseInterface
     * @access protected
     */
    protected $dbi;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
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
        $this->dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $this->dbi;

        $this->object = new ImportLdi();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
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
            __('CSV using LOAD DATA'),
            $properties->getText()
        );
        $this->assertEquals(
            'ldi',
            $properties->getExtension()
        );
    }

    /**
     * Test for getProperties for ldi_local_option = auto
     *
     * @group medium
     */
    public function testGetPropertiesAutoLdi(): void
    {
        /**
         * The \PhpMyAdmin\DatabaseInterface mocked object
         *
         * @var MockObject $dbi
         */
        $dbi = $this->dbi;
        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())->method('numRows')
            ->will($this->returnValue(10));

        $fetchRowResult = ['ON'];
        $dbi->expects($this->any())->method('fetchRow')
            ->will($this->returnValue($fetchRowResult));

        $GLOBALS['dbi'] = $dbi;

        $GLOBALS['cfg']['Import']['ldi_local_option'] = 'auto';
        $this->object = new ImportLdi();
        $properties = $this->object->getProperties();
        $this->assertTrue(
            $GLOBALS['cfg']['Import']['ldi_local_option']
        );
        $this->assertEquals(
            __('CSV using LOAD DATA'),
            $properties->getText()
        );
        $this->assertEquals(
            'ldi',
            $properties->getExtension()
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
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;
        /**
         * The \PhpMyAdmin\DatabaseInterface mocked object
         *
         * @var MockObject $dbi
         */
        $dbi = $this->dbi;
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        $this->assertStringContainsString(
            "LOAD DATA INFILE 'test/test_data/db_test_ldi.csv' INTO TABLE "
            . '`phpmyadmintest`',
            $sql_query
        );

        $this->assertTrue(
            $GLOBALS['finished']
        );
    }

    /**
     * Test for doImport : invalid import file
     *
     * @group medium
     */
    public function testDoImportInvalidFile(): void
    {
        global $import_file;
        $import_file = 'none';

        //Test function called
        $this->object->doImport();

        // We handle only some kind of data!
        $this->assertStringContainsString(
            __('This plugin does not support compressed imports!'),
            $GLOBALS['message']->__toString()
        );

        $this->assertTrue(
            $GLOBALS['error']
        );
    }

    /**
     * Test for doImport with LDI setting
     *
     * @group medium
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
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
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
        $this->assertStringContainsString(
            "LOAD DATA LOCAL INFILE 'test/test_data/db_test_ldi.csv' REPLACE INTO "
            . 'TABLE `phpmyadmintest`',
            $sql_query
        );

        //FIELDS TERMINATED
        $this->assertStringContainsString(
            "FIELDS TERMINATED BY ','",
            $sql_query
        );

        //LINES TERMINATED
        $this->assertStringContainsString(
            "LINES TERMINATED BY 'newline_mark'",
            $sql_query
        );

        //IGNORE
        $this->assertStringContainsString(
            'IGNORE 1 LINES',
            $sql_query
        );

        $this->assertTrue(
            $GLOBALS['finished']
        );
    }
}
