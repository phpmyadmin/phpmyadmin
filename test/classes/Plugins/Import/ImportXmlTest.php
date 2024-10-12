<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportXml;
use PhpMyAdmin\Tests\AbstractTestCase;

use function __;

/**
 * @covers \PhpMyAdmin\Plugins\Import\ImportXml
 * @requires extension xml
 * @requires extension xmlwriter
 */
class ImportXmlTest extends AbstractTestCase
{
    /** @var ImportXml */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;

        $this->object = new ImportXml();

        //setting
        $GLOBALS['finished'] = false;
        $GLOBALS['read_limit'] = 100000000;
        $GLOBALS['offset'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $GLOBALS['import_file'] = 'test/test_data/phpmyadmin_importXML_For_Testing.xml';
        $GLOBALS['import_text'] = 'ImportXml_Test';
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
     * Test for getProperties
     *
     * @group medium
     */
    public function testGetProperties(): void
    {
        $properties = $this->object->getProperties();
        self::assertSame(__('XML'), $properties->getText());
        self::assertSame('xml', $properties->getExtension());
        self::assertSame('text/xml', $properties->getMimeType());
        self::assertNull($properties->getOptions());
        self::assertSame(__('Options'), $properties->getOptionsText());
    }

    /**
     * Test for doImport
     *
     * @group medium
     * @requires extension simplexml
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

        // If import successfully, PMA will show all databases and tables
        // imported as following HTML Page
        /*
           The following structures have either been created or altered. Here you
           can:
           View a structure's contents by clicking on its name
           Change any of its settings by clicking the corresponding "Options" link
           Edit structure by following the "Structure" link

           phpmyadmintest (Options)
           pma_bookmarktest (Structure) (Options)
        */

        //asset that all databases and tables are imported
        self::assertStringContainsString(
            'The following structures have either been created or altered.',
            $import_notice
        );
        self::assertStringContainsString('Go to database: `phpmyadmintest`', $import_notice);
        self::assertStringContainsString('Edit settings for `phpmyadmintest`', $import_notice);
        self::assertStringContainsString('Go to table: `pma_bookmarktest`', $import_notice);
        self::assertStringContainsString('Edit settings for `pma_bookmarktest`', $import_notice);
        self::assertTrue($GLOBALS['finished']);
    }
}
