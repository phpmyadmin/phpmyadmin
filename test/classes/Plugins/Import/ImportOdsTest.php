<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportOds;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @requires extension zip
 */
class ImportOdsTest extends AbstractTestCase
{
    /** @var ImportOds */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['plugin_param'] = 'csv';
        $this->object = new ImportOds();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/db_test.ods';

        /**
         * Load interface for zip extension.
        */
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'ods';

        //variable for Ods
        $_REQUEST['ods_recognize_percentages'] = true;
        $_REQUEST['ods_recognize_currency'] = true;
        $_REQUEST['ods_empty_rows'] = true;
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
            __('OpenDocument Spreadsheet'),
            $properties->getText()
        );
        $this->assertEquals(
            'ods',
            $properties->getExtension()
        );
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
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
        //$import_notice will show the import detail result
        global $import_notice, $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->setDecompressContent(true);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        $this->assertStringContainsString(
            'CREATE DATABASE IF NOT EXISTS `ODS_DB` DEFAULT CHARACTER SET '
            . 'utf8 COLLATE utf8_general_ci',
            $sql_query
        );
        $this->assertStringContainsString(
            'CREATE TABLE IF NOT EXISTS `ODS_DB`.`pma_bookmark`',
            $sql_query
        );
        $this->assertStringContainsString(
            'INSERT INTO `ODS_DB`.`pma_bookmark` (`A`, `B`, `C`, `D`) VALUES '
            . "(1, 'dbbase', NULL, 'ddd');",
            $sql_query
        );

        //asset that all databases and tables are imported
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        $this->assertStringContainsString(
            'Go to database: `ODS_DB`',
            $import_notice
        );
        $this->assertStringContainsString(
            'Edit settings for `ODS_DB`',
            $import_notice
        );
        $this->assertStringContainsString(
            'Go to table: `pma_bookmark`',
            $import_notice
        );
        $this->assertStringContainsString(
            'Edit settings for `pma_bookmark`',
            $import_notice
        );

        //asset that the import process is finished
        $this->assertTrue(
            $GLOBALS['finished']
        );
    }
}
