<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportMediawiki;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;

/** @covers \PhpMyAdmin\Plugins\Import\ImportMediawiki */
class ImportMediawikiTest extends AbstractTestCase
{
    protected ImportMediawiki $object;

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
        $GLOBALS['db'] = '';
        $GLOBALS['skip_queries'] = null;
        $GLOBALS['max_sql_len'] = null;
        $GLOBALS['sql_query_disabled'] = null;
        $GLOBALS['sql_query'] = '';
        $GLOBALS['executed_queries'] = null;
        $GLOBALS['run_query'] = null;
        $GLOBALS['go_sql'] = null;
        $GLOBALS['plugin_param'] = 'database';
        $this->object = new ImportMediawiki();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin.mediawiki';
        $GLOBALS['import_text'] = 'ImportMediawiki_Test';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Mediawiki';
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
            __('MediaWiki Table'),
            $properties->getText(),
        );
        $this->assertEquals(
            'txt',
            $properties->getExtension(),
        );
        $this->assertEquals(
            'text/plain',
            $properties->getMimeType(),
        );
        $this->assertNull($properties->getOptions());
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText(),
        );
    }

    /**
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImport(): void
    {
        //$import_notice will show the import detail result

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File($GLOBALS['import_file']);
        $importHandle->open();

        //Test function called
        $this->object->doImport($importHandle);

        // If import successfully, PMA will show all databases and
        // tables imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           mediawiki_DB (Options)
           pma_bookmarktest (Structure) (Options)
        */

        //asset that all databases and tables are imported
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $GLOBALS['import_notice'],
        );
        $this->assertStringContainsString('Go to database: `mediawiki_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `mediawiki_DB`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Go to table: `pma_bookmarktest`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `pma_bookmarktest`', $GLOBALS['import_notice']);
        $this->assertTrue($GLOBALS['finished']);
    }
}
