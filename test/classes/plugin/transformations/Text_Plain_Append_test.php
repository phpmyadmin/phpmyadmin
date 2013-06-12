<?php
/**
 * Tests for Text_Plain_Append class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/plugins/transformations/Text_Plain_Append.class.php';

/**
 * Tests for Text_Plain_Append class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Append_Test extends PHPUnit_Framework_TestCase
{
   /**
     * Test for getInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetInfo()
    {
        $info = 'Appends text to a string. The only option is the text to be appended'
            . ' (enclosed in single quotes, default empty string).';     
        $this->assertEquals(
            $info,
            Text_Plain_Append::getInfo()
        );  
    
    }

    /**
     * Test for getName
     *
     * @return void
     *
     * @group medium
     */
    public function testGetName()
    {       
        $this->assertEquals(
            "Append",
            Text_Plain_Append::getName()
        );    
    }

    /**
     * Test for getMIMEType
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMEType()
    {       
        $this->assertEquals(
            "Text",
            Text_Plain_Append::getMIMEType()
        );    
    }

    /**
     * Test for getMIMESubtype
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMESubtype()
    {       
        $this->assertEquals(
            "Plain",
            Text_Plain_Append::getMIMESubtype()
        );    
    }
}
