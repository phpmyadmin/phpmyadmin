<?php
/**
 * Tests for Table.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/Table.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';


/**
 * Tests behaviour of PMA_Table class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Table_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Configures environment
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['lang'] = 'en';
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['cfg']['MySQLManualType'] = 'viewable';
        $GLOBALS['cfg']['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'themes/dot.gif';
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
    }

    /**
     * Test object creating
     *
     * @return void
     */
    public function testCreate()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $this->assertInstanceOf('PMA_Table', $table);
    }

    /**
     * Test renaming
     *
     * @return void
     */
    public function testRename()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $table->rename('table3');
        $this->assertEquals('table3', $table->getName());
    }

    /**
     * Test getting columns
     *
     * @return void
     */
    public function testColumns()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $this->assertEquals(
            array('`pma_test`.`table1`.`i`', '`pma_test`.`table1`.`o`'),
            $table->getColumns()
        );
    }

    /**
     * Test getting unique columns
     *
     * @return void
     */
    public function testUniqueColumns()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $this->assertEquals(
            array(),
            $table->getUniqueColumns()
        );
    }

    /**
     * Test name validation
     *
     * @param string  $name   name to test
     * @param boolena $result expected result
     *
     * @return void
     *
     * @dataProvider dataValidateName
     */
    public function testValidateName($name, $result)
    {
        $this->assertEquals(
            $result,
            PMA_Table::isValidName($name)
        );
    }

    /**
     * Data provider for name validation
     *
     * @return array with test data
     */
    public function dataValidateName()
    {
        return array(
            array('test', true),
            array('te/st', false),
            array('te.st', false),
            array('te\\st', false),
        );
    }
}

