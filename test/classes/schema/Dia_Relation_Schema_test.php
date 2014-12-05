<?php
/**
 * Tests for PMA_Dia_Relation_Schema class
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
require_once 'libraries/Response.class.php';
require_once 'libraries/plugins/schema/dia/Dia_Relation_Schema.class.php';

/**
 * Tests for PMA_Dia_Relation_Schema class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Dia_Relation_Schema_Test extends PHPUnit_Framework_TestCase
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
        $_REQUEST['page_number'] = 33;
        $_REQUEST['dia_show_color'] = true;
        $_REQUEST['dia_show_keys'] = true;
        $_REQUEST['dia_orientation'] = 'orientation';
        $_REQUEST['dia_paper'] = 'paper';
        $_REQUEST['t_h'] = array('information_schema.files' => 1);
        $_REQUEST['t_x'] = array('information_schema.files' => 0);
        $_REQUEST['t_y'] = array('information_schema.files' => 0);

        $GLOBALS['server'] = 1;
        $GLOBALS['controllink'] = null;
        $GLOBALS['db'] = 'information_schema';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['Server']['table_coords'] = "table_name";

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'relation' => 'relation'
        );
        PMA_getRelationsParam();

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(1));

        $dbi->expects($this->any())
            ->method('query')
            ->will($this->returnValue("executed_1"));

        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue("executed_1"));

        $fetchArrayReturn = array(
            //table name in information_schema_relations
            'table_name' => 'CHARACTER_SETS'
        );

        $fetchArrayReturn2 = array(
            //table name in information_schema_relations
            'table_name' => 'COLLATIONS'
        );

        $dbi->expects($this->at(2))
            ->method('fetchAssoc')
            ->will($this->returnValue($fetchArrayReturn));
        $dbi->expects($this->at(3))
            ->method('fetchAssoc')
            ->will($this->returnValue($fetchArrayReturn2));
        $dbi->expects($this->at(4))
            ->method('fetchAssoc')
            ->will($this->returnValue(false));

        $getIndexesResult = array(
            array(
                'Table' => 'pma_tbl',
                'Field' => 'field1',
                'Key' => 'PRIMARY',
                'Key_name' => "Key_name",
                'Column_name' => "Column_name"
            )
        );
        $dbi->expects($this->any())->method('getTableIndexes')
            ->will($this->returnValue($getIndexesResult));

        $fetchValue = "CREATE TABLE `pma_bookmark` (
             `id` int(11) NOT NULL AUTO_INCREMENT,
              `dbase` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
              `user` varchar(255) COLLATE utf8_bin NOT NULL DEFAULT '',
              `label` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
              `query` text COLLATE utf8_bin NOT NULL,
              PRIMARY KEY (`id`)
             ) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 "
            . "COLLATE=utf8_bin COMMENT='Bookmarks'";

        $dbi->expects($this->any())
            ->method('fetchValue')
            ->will($this->returnValue($fetchValue));

        $GLOBALS['dbi'] = $dbi;

        $this->object = new PMA_Dia_Relation_Schema();
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
     * Test for construct, the Property is set correctly
     *
     * @return void
     *
     * @group medium
     */
    public function testSetProperty()
    {
        $this->assertEquals(
            33,
            $this->object->getPageNumber()
        );
        $this->assertEquals(
            true,
            $this->object->isShowColor()
        );
        $this->assertEquals(
            true,
            $this->object->isShowKeys()
        );
        $this->assertEquals(
            'L',
            $this->object->getOrientation()
        );
        $this->assertEquals(
            'paper',
            $this->object->getPaper()
        );
    }
}
