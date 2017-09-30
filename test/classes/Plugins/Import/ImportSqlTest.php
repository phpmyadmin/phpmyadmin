<?php
/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportSql class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Import;

use PhpMyAdmin\File;
use PhpMyAdmin\Plugins\Import\ImportSql;
use PhpMyAdmin\Tests\PmaTestCase;

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.inc.php' will use it globally
 */
$GLOBALS['server'] = 0;

/**
 * Tests for PhpMyAdmin\Plugins\Import\ImportSql class
 *
 * @package PhpMyAdmin-test
 */
class ImportSqlTest extends PmaTestCase
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
     * Test for doImport
     *
     * @return void
     *
     * @group medium
     */
    public function testDoImport()
    {
        //$sql_query_disabled will show the import SQL detail
        global $sql_query, $sql_query_disabled;
        $sql_query_disabled = false;

        //Mock DBI
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;

        //Test function called
        $this->object->doImport();

        //asset that all sql are executed
        $this->assertContains(
            'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"',
            $sql_query
        );
        $this->assertContains(
            'CREATE TABLE IF NOT EXISTS `pma_bookmark`',
            $sql_query
        );
        $this->assertContains(
            'INSERT INTO `pma_bookmark` (`id`, `dbase`, `user`, `label`, `query`) '
            . 'VALUES',
            $sql_query
        );

        $this->assertEquals(
            true,
            $GLOBALS['finished']
        );

    }
}
