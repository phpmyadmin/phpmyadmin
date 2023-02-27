<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportSql;
use PhpMyAdmin\Tests\AbstractTestCase;

/** @covers \PhpMyAdmin\Plugins\Import\ImportSql */
class ImportSqlTest extends AbstractTestCase
{
    protected ImportSql $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 0;
        $GLOBALS['error'] = null;
        $GLOBALS['timeout_passed'] = null;
        $GLOBALS['maximum_time'] = null;
        $GLOBALS['charset_conversion'] = null;
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        $GLOBALS['go_sql'] = null;

        $this->object = new ImportSql();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/pma_bookmark.sql';
        $GLOBALS['import_text'] = 'ImportSql_Test';
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Xml';
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
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImport(): void
    {
        //$sql_query_disabled will show the import SQL detail

        $GLOBALS['sql_query_disabled'] = false;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        //asset that all sql are executed
        $this->assertStringContainsString('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"', $GLOBALS['sql_query']);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `pma_bookmark`', $GLOBALS['sql_query']);
        $this->assertStringContainsString(
            'INSERT INTO `pma_bookmark` (`id`, `dbase`, `user`, `label`, `query`) VALUES',
            $GLOBALS['sql_query'],
        );

        $this->assertTrue($GLOBALS['finished']);
    }
}
