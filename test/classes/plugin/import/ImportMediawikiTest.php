<?php
/**
 * Tests for PMA\libraries\plugins\import\ImportMediawiki class
 *
 * @package PhpMyAdmin-test
 */
/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
use PMA\libraries\plugins\import\ImportMediawiki;

$GLOBALS['server'] = 0;

/*
 * Include to test.
 */
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\plugins\import\ImportMediawiki class
 *
 * @package PhpMyAdmin-test
 */
class ImportMediawikiTest extends PMATestCase
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
        $GLOBALS['compression'] = 'none';
        $GLOBALS['read_multiply'] = 10;
        $GLOBALS['import_type'] = 'Mediawiki';
        $GLOBALS['import_handle'] = @fopen($GLOBALS['import_file'], 'r');
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
        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
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
