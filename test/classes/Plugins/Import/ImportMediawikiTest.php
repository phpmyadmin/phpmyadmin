<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportMediawiki;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportMediawiki
 */
class ImportMediawikiTest extends AbstractTestCase
{
    /** @var ImportMediawiki */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
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
        self::assertEquals(__('MediaWiki Table'), $properties->getText());
        self::assertEquals('txt', $properties->getExtension());
        self::assertEquals('text/plain', $properties->getMimeType());
        self::assertNull($properties->getOptions());
        self::assertEquals(__('Options'), $properties->getOptionsText());
    }

    /**
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImport(): void
    {
        //$import_notice will show the import detail result
        global $import_notice;

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
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        self::assertStringContainsString('Go to database: `mediawiki_DB`', $import_notice);
        self::assertStringContainsString('Edit settings for `mediawiki_DB`', $import_notice);
        self::assertStringContainsString('Go to table: `pma_bookmarktest`', $import_notice);
        self::assertStringContainsString('Edit settings for `pma_bookmarktest`', $import_notice);
        self::assertTrue($GLOBALS['finished']);
    }

    /**
     * Test for doImport
     *
     * @group medium
     */
    public function testDoImportWithEmptyTable(): void
    {
        //$import_notice will show the import detail result
        global $import_notice;

        //Mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        $importHandle = new File('test/test_data/__slashes.mediawiki');
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
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        self::assertStringContainsString('Go to database: `mediawiki_DB`', $import_notice);
        self::assertStringContainsString('Edit settings for `mediawiki_DB`', $import_notice);
        self::assertStringContainsString('Go to table: `empty`', $import_notice);
        self::assertStringContainsString('Edit settings for `empty`', $import_notice);
        self::assertTrue($GLOBALS['finished']);
    }
}
