<?php
/**
 * Tests for PMA_Pdf_Relation_Schema class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/plugins/schema/pdf/Pdf_Relation_Schema.class.php';

/**
 * Tests for PMA_Pdf_Relation_Schema class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Pdf_Relation_Schema_Test extends PHPUnit_Framework_TestCase
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
        $_REQUEST['pdf_show_grid'] = true;
        $_REQUEST['pdf_show_color'] = true;
        $_REQUEST['pdf_show_keys'] = true;
        $_REQUEST['pdf_orientation'] = 'orientation';
        $_REQUEST['pdf_show_table_dimension'] = true;
        $_REQUEST['pdf_all_tables_same_width'] = true;
        $_REQUEST['pdf_paper'] = 'paper';
        $_REQUEST['pdf_table_order'] = '';
        $_REQUEST['t_h'] = array('information_schema.files' => 1);
        $_REQUEST['t_x'] = array('information_schema.files' => 0);
        $_REQUEST['t_y'] = array('information_schema.files' => 0);

        $GLOBALS['server'] = 1;
        $GLOBALS['controllink'] = null;
        $GLOBALS['db'] = 'information_schema';
        $GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
        $GLOBALS['cfg']['LimitChars'] = 100;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['Server']['user'] = "user";
        $GLOBALS['cfg']['Server']['table_coords'] = "table_name";
        $GLOBALS['cfg']['Server']['bookmarktable'] = "bookmarktable";
        $GLOBALS['cfg']['Server']['relation'] = "relation";
        $GLOBALS['cfg']['Server']['table_info'] = "table_info";

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => false,
            'relation' => 'relation',
            'mimework' => 'mimework',
            'commwork' => 'commwork',
            'column_info' => 'column_info',
            'pdf_pages' => 'pdf_pages'
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

        $fetchRowReturn = array(
            'table_name',
            'table_name'
        );

        //let fetchRow have more results
        for ($index=0; $index<4; ++$index) {
            $dbi->expects($this->at($index))
                ->method('fetchRow')
                ->will($this->returnValue($fetchRowReturn));
        }

        $fields_info = array(
            "Host" => array(
                "Field" => "host",
                "Type" => "char(60)",
                "Null" => "NO",
                'Extra' => "Extra",
            )
        );
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($fields_info));

        $dbi->expects($this->any())->method('selectDb')
            ->will($this->returnValue(true));

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

        $fetchResult = array(
            'column1' => array('mimetype' => 'value1', 'transformation'=> 'pdf'),
            'column2' => array('mimetype' => 'value2', 'transformation'=> 'xml'),
        );

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValue($fetchResult));

        $GLOBALS['dbi'] = $dbi;

        $this->object = new PMA_Pdf_Relation_Schema('information_schema');
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
     * Test for construct
     *
     * @return void
     *
     * @group large
     */
    public function testConstructor()
    {
        $this->assertEquals(
            33,
            $this->object->getPageNumber()
        );
        $this->assertEquals(
            true,
            $this->object->isShowGrid()
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
            true,
            $this->object->isTableDimension()
        );
        $this->assertEquals(
            true,
            $this->object->isAllTableSameWidth()
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
