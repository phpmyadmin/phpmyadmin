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
require_once 'libraries/database_interface.inc.php';
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
        $GLOBALS['cfg']['ServerDefault'] = 1;
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
        
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
        		'table_name' => 'table_name'
        );
        $dbi->expects($this->at(1))
            ->method('fetchAssoc')
            ->will($this->returnValue($fetchArrayReturn));
        
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
     * @group medium
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
