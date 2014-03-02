<?php
/**
 * Tests for User_Schema class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/schema/User_Schema.class.php';

/**
 * Tests for User_Schema class
 *
 * @package PhpMyAdmin-test
 */
class PMA_User_Schema_Test extends PHPUnit_Framework_TestCase
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
        $_POST['pdf_page_number'] = 33;
        $_POST['show_grid'] = true;
        $_POST['show_color'] = 'on';
        $_POST['show_keys'] = true;
        $_POST['orientation'] = 'orientation';
        $_POST['show_table_dimension'] = 'on';
        $_POST['all_tables_same_width'] = 'on';
        $_POST['paper'] = 'paper';
        $_POST['export_type'] = 'Xml';
        $_POST['with_doc'] = 'on';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['Server']['table_coords'] = "table_name";
        $GLOBALS['cfgRelation']['db'] = "PMA";
        $GLOBALS['cfgRelation']['table_coords'] = "table_name";
        $GLOBALS['cfgRelation']['pdf_pages'] = "pdf_pages";
        $GLOBALS['cfgRelation']['relation'] = "relation";

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('insertId')
            ->will($this->returnValue(10));

        $databases = array();
        $database_name = 'PMA';
        $databases[$database_name]['SCHEMA_TABLES'] = 1;
        $databases[$database_name]['SCHEMA_TABLE_ROWS'] = 3;
        $databases[$database_name]['SCHEMA_DATA_LENGTH'] = 5;
        $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 10;
        $databases[$database_name]['SCHEMA_INDEX_LENGTH'] = 10;
        $databases[$database_name]['SCHEMA_LENGTH'] = 10;
        $databases[$database_name]['ENGINE'] = "InnerDB";

        $dbi->expects($this->any())->method('getTablesFull')
            ->will($this->returnValue($databases));

        $GLOBALS['dbi'] = $dbi;

        $this->object = new PMA_User_Schema();
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
     * Test for setAction & processUserChoice
     *
     * @return void
     *
     * @group medium
     */
    public function testSetActionAndProcessUserChoice()
    {
        //action: selectpage
        $_REQUEST['chpage'] = 10;
        $_REQUEST['action_choose'] = '2';
        $this->object->setAction("selectpage");
        $this->object->processUserChoice();
        $this->assertEquals(
            "selectpage",
            $this->object->action
        );
        $this->assertEquals(
            10,
            $this->object->chosenPage
        );

        $_REQUEST['action_choose'] = '1';
        $this->object->processUserChoice();
        //deleteCoordinates successfully
        $this->assertEquals(
            0,
            $this->object->chosenPage
        );

        //action: createpage
        $_POST['newpage'] = "3";
        $_POST['auto_layout_foreign'] = true;
        $_POST['auto_layout_internal'] = true;
        $_POST['delrow'] = array("row1", "row2");
        $_POST['chpage'] = "chpage";
        $this->object->setAction("delete_old_references");
        $this->object->processUserChoice();
        $this->object->setAction("createpage");
        $this->object->processUserChoice();
        $this->assertEquals(
            10,
            $this->object->pageNumber
        );
        $this->assertEquals(
            "1",
            $this->object->autoLayoutForeign
        );
        $this->assertEquals(
            "1",
            $this->object->autoLayoutInternal
        );

        //action: edcoord
        $_POST['chpage'] = "3";
        $_POST['c_table_rows'] = 1;
        $_POST['c_table_0'] = array(
            'x' => 'x0',
            'y' => 'y0',
            'name' => 'name0',
            'delete' => 'delete0',
            'x' => 'x0',
        );
        $this->object->setAction("edcoord");
        $this->object->processUserChoice();
        $this->assertEquals(
            '3',
            $this->object->chosenPage
        );
        $this->assertEquals(
            1,
            $this->object->c_table_rows
        );
    }
}
