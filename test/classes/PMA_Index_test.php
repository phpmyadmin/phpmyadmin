<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Index class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Index.class.php';

/**
 * Test for PMA_Index class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Index_Test extends PHPUnit_Framework_TestCase
{
    private $_params = array();

    /**
     * Configures parameters.
     *
     * @return void
     */
    public function setup()
    {
        $this->_params['Schema'] = "PMA_Schema";
        $this->_params['Table'] = "PMA_Table";
        $this->_params['Key_name'] = "PMA_Key_name";
        $this->_params['Index_type'] = "PMA_Index_type";
        $this->_params['Comment'] = "PMA_Comment";
        $this->_params['Index_comment'] = "PMA_Index_comment";
        $this->_params['Non_unique'] = "PMA_Non_unique";
        $this->_params['Packed'] = "PMA_Packed";
        
        //test add columns
        $column1 = array("Column_name"=>"column1","Seq_in_index"=>"index1");
        $column2 = array("Column_name"=>"column2","Seq_in_index"=>"index2");
        $column3 = array("Column_name"=>"column3","Seq_in_index"=>"index3");
        $this->_params['columns'][] = $column1;
        $this->_params['columns'][] = $column2;
        $this->_params['columns'][] = $column3;
    }

    /**
     * Test for Constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $index = new PMA_Index($this->_params);
        $this->assertEquals(
            'PMA_Index_comment',
            $index->getComment()
        );
        $this->assertEquals(
            'PMA_Comment',
            $index->getRemarks()
        );
        $this->assertEquals(
            'PMA_Index_type',
            $index->getType()
        );
        $this->assertEquals(
            'PMA_Packed',
            $index->getPacked()
        );
        $this->assertEquals(
            'PMA_Non_unique',
            $index->getNonUnique()
        );
    }
    
    /**
     * Test for add Columns
     *
     * @return void
     */
    public function testAddColumns()
    {
        $index = new PMA_Index();
        $index->addColumns($this->_params['columns']);       
        $this->assertTrue($this->hasColumn("column1"));       
        $this->assertTrue($this->hasColumn("column2"));       
        $this->assertTrue($this->hasColumn("column3"));
        $this->assertEquals(
            3,
            $this->getColumnCount()
        );
    }
        
    /**
     * Test for PMA_Index_Column
     *
     * @return void
     */
    public function testPMA_Index_Column()
    {
        $index = new PMA_Index();
        $index->addColumns($this->_params['columns']);
        
        $index_columns = $this->getColumns();
        $index_column = $index_columns['index1'];
        $this->assertEquals(
            'column1',
            $index_column->getName();
        );
        $this->assertEquals(
            'index1',
            $index_column->getSeqInIndex();
        );
    }
}
