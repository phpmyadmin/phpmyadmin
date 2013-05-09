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
    private $params = array();

    /**
     * Configures parameters.
     *
     * @return void
     */
    public function setup()
    {
        $this->params['columns'] = "PMA_columns";
        $this->params['Schema'] = "PMA_Schema";
        $this->params['Table'] = "PMA_Table";
        $this->params['Key_name'] = "PMA_Key_name";
        $this->params['Index_type'] = "PMA_Index_type";
        $this->params['Comment'] = "PMA_Comment";
        $this->params['Index_comment'] = "PMA_Index_comment";
        $this->params['Non_unique'] = "PMA_Non_unique";
        $this->params['Packed'] = "PMA_Packed";
    }

    /**
     * Test for Constructor
     *
     * @return void
     */
    public function testConstructor()
    {
        $index = new PMA_Index($this->params);
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
}
