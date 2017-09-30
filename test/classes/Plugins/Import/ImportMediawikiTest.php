<?php
/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportMediawiki class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportMediawiki;
use PhpMyAdmin\Tests\PmaTestCase;

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.inc.php' will use it globally
 */
$GLOBALS['server'] = 0;

/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportMediawiki class
 *
 * @package PhpMyAdmin-test
 */
class ImportMediawikiTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
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
        $GLOBALS['import_handle'] = new File($GLOBALS['import_file']);
        $GLOBALS['import_handle']->open();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for getProperties
     *
     * @return void
     *
     * @group medium
     */
    public function testGetProperties()
    {
        $properties = $this->object->getProperties();
        $this->assertEquals(
            __('MediaWiki Table'),
            $properties->getText()
        );
        $this->assertEquals(
            'txt',
            $properties->getExtension()
        );
        $this->assertEquals(
            'text/plain',
            $properties->getMimeType()
        );
        $this->assertEquals(
            array(),
            $properties->getOptions()
        );
        $this->assertEquals(
            __('Options'),
            $properties->getOptionsText()
        );

    }

    /**
     * Test for doImport
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImport()
    {
        //$import_notice will show the import detail result
        global $import_notice;

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        //Test function called
        $this->object->doImport();

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
        $this->assertContains(
            'The following structures have either been created or altered.',
            $import_notice
        );
        $this->assertContains(
            'Go to database: `mediawiki_DB`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `mediawiki_DB`',
            $import_notice
        );
        $this->assertContains(
            'Go to table: `pma_bookmarktest`',
            $import_notice
        );
        $this->assertContains(
            'Edit settings for `pma_bookmarktest`',
            $import_notice
        );
        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );

    }
}
