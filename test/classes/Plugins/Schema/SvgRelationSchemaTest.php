<?php
/**
 * Tests for PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Plugins\Schema\Svg\SvgRelationSchema class
 *
 * @package PhpMyAdmin-test
 */
class SvgRelationSchemaTest extends PmaTestCase
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
        $_REQUEST['svg_show_color'] = true;
        $_REQUEST['svg_show_keys'] = true;
        $_REQUEST['svg_show_table_dimension'] = true;
        $_REQUEST['svg_all_tables_same_width'] = true;
        $_REQUEST['t_h'] = array('information_schema.files' => 1);
        $_REQUEST['t_x'] = array('information_schema.files' => 0);
        $_REQUEST['t_y'] = array('information_schema.files' => 0);

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'information_schema';
        $GLOBALS['cfg']['Server']['table_coords'] = "table_name";

        //_SESSION
        $_SESSION['relation'][$GLOBALS['server']] = array(
            'PMA_VERSION' => PMA_VERSION,
            'table_coords' => "table_name",
            'displaywork' => 'displaywork',
            'db' => "information_schema",
            'table_info' => 'table_info',
            'relwork' => 'relwork',
            'relation' => 'relation'
        );
        $relation = new Relation();
        $relation->getRelationsParam();

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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

        $this->object = new SvgRelationSchema('information_schema');
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
     * @group medium
     */
    public function testConstructor()
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
            true,
            $this->object->isTableDimension()
        );
        $this->assertEquals(
            true,
            $this->object->isAllTableSameWidth()
        );
    }
}
