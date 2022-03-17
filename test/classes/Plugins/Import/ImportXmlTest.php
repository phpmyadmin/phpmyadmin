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
        $this->assertEquals(
            __('XML'),
            $properties->getText()
        );
        $this->assertEquals(
            'xml',
            $properties->getExtension()
        );
        $this->assertEquals(
            'text/xml',
            $properties->getMimeType()
        );
        $this->assertNull($properties->getOptions());
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
        );
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
        $this->assertStringContainsString(
            'The following structures have either been created or altered.',
            $GLOBALS['import_notice']
        );
        $this->assertStringContainsString('Go to database: `phpmyadmintest`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `phpmyadmintest`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Go to table: `pma_bookmarktest`', $GLOBALS['import_notice']);
        $this->assertStringContainsString('Edit settings for `pma_bookmarktest`', $GLOBALS['import_notice']);
        $this->assertTrue($GLOBALS['finished']);
    }
}
