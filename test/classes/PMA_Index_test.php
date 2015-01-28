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
require_once 'libraries/php-gettext/gettext.inc';
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
        $this->_params['Index_choice'] = "PMA_Index_choice";
        $this->_params['Comment'] = "PMA_Comment";
        $this->_params['Index_comment'] = "PMA_Index_comment";
        $this->_params['Non_unique'] = "PMA_Non_unique";
        $this->_params['Packed'] = "PMA_Packed";

        //test add columns
        $column1 = array("Column_name"=>"column1","Seq_in_index"=>"index1",
                         "Collation"=>"Collation1","Cardinality"=>"Cardinality1",
                         "Null"=>"null1"
                        );
        $column2 = array("Column_name"=>"column2","Seq_in_index"=>"index2",
                         "Collation"=>"Collation2","Cardinality"=>"Cardinality2",
                         "Null"=>"null2"
                        );
        $column3 = array("Column_name"=>"column3","Seq_in_index"=>"index3",
                         "Collation"=>"Collation3","Cardinality"=>"Cardinality3",
                         "Null"=>"null3"
                        );
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
            'PMA_Index_choice',
            $index->getChoice()
        );
        $this->assertEquals(
            'PMA_Packed',
            $index->getPacked()
        );
        $this->assertEquals(
            'PMA_Non_unique',
            $index->getNonUnique()
        );
        $this->assertContains(
            'PMA_Comment',
            $index->getComments()
        );
        $this->assertContains(
            'PMA_Index_comment',
            $index->getComments()
        );
        $this->assertEquals(
            'PMA_Index_choice',
            $index->getChoice()
        );

    }

    /**
     * Test for getIndexChoices
     *
     * @return void
     */
    public function testGetIndexChoices()
    {
        $index_choices = PMA_Index::getIndexChoices();
        $this->assertEquals(
            5,
            count($index_choices)
        );
        $this->assertEquals(
            'PRIMARY,INDEX,UNIQUE,SPATIAL,FULLTEXT',
            implode(",", $index_choices)
        );
    }

    /**
     * Test for isUnique
     *
     * @return void
     */
    public function testIsUniquer()
    {
        $this->_params['Non_unique'] = "0";
        $index = new PMA_Index($this->_params);
        $this->assertTrue(
            $index->isUnique()
        );
        $this->assertEquals(
            'Yes',
            $index->isUnique(true)
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
        $this->assertTrue($index->hasColumn("column1"));
        $this->assertTrue($index->hasColumn("column2"));
        $this->assertTrue($index->hasColumn("column3"));
        $this->assertEquals(
            3,
            $index->getColumnCount()
        );
    }

    /**
     * Test for get Name & set Name
     *
     * @return void
     */
    public function testName()
    {
        $index = new PMA_Index();
        $index->setName('PMA_name');
        $this->assertEquals(
            'PMA_name',
            $index->getName()
        );
    }

    /**
     * Test for PMA_Index_Column
     *
     * @return void
     */
    public function testColumns()
    {
        $index = new PMA_Index();
        $index->addColumns($this->_params['columns']);

        $index_columns = $index->getColumns();
        $index_column = $index_columns['column1'];
        $this->assertEquals(
            'column1',
            $index_column->getName()
        );
        $this->assertEquals(
            'index1',
            $index_column->getSeqInIndex()
        );
        $this->assertEquals(
            'Collation1',
            $index_column->getCollation()
        );
        $this->assertEquals(
            'Cardinality1',
            $index_column->getCardinality()
        );
    }
}
