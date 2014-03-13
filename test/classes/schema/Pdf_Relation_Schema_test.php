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
require_once 'libraries/sqlparser.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/Index.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/schema/Pdf_Relation_Schema.class.php';

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
        $_POST['pdf_page_number'] = 33;
        $_POST['show_grid'] = true;
        $_POST['show_color'] = 'on';
        $_POST['show_keys'] = true;
        $_POST['orientation'] = 'orientation';
        $_POST['show_table_dimension'] = 'on';
        $_POST['all_tables_same_width'] = 'on';
        $_POST['paper'] = 'paper';
        $_POST['export_type'] = 'PMA_ExportType';
        $_POST['with_doc'] = 'on';

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
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => false,
            'relation' => 'relation',
            'mimework' => 'mimework',
            'commwork' => 'commwork',
            'column_info' => 'column_info'
        );

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
                'table_name'
        );

        //let fetchRow have more results
        for ($index=0; $index<10; ++$index) {
            $dbi->expects($this->at($index))
                ->method('fetchRow')
                ->will($this->returnValue($fetchRowReturn));
        }

        $dbi->expects($this->at(10))
            ->method('fetchRow')
            ->will($this->returnValue($fetchRowReturn));

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

        $this->object = new PMA_Pdf_Relation_Schema();
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
            $this->object->pageNumber
        );
        $this->assertEquals(
            1,
            $this->object->showGrid
        );
        $this->assertEquals(
            1,
            $this->object->showColor
        );
        $this->assertEquals(
            1,
            $this->object->showKeys
        );
        $this->assertEquals(
            1,
            $this->object->tableDimension
        );
        $this->assertEquals(
            1,
            $this->object->sameWide
        );
        $this->assertEquals(
            1,
            $this->object->withDoc
        );
        $this->assertEquals(
            'L',
            $this->object->orientation
        );
        $this->assertEquals(
            'PMA_ExportType',
            $this->object->exportType
        );
        $this->assertEquals(
            'paper',
            $this->object->paper
        );
    }
}
