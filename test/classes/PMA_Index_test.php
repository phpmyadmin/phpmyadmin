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
}
