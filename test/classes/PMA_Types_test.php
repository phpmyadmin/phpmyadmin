<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

require_once 'libraries/Types.class.php';

/**
 * Test class for PMA_Types.
 */
class PMA_TypesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PMA_Types
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new PMA_Types;
    }

    public function testUnary()
    {
        $this->assertTrue($this->object->isUnaryOperator('IS NULL'));
        $this->assertFalse($this->object->isUnaryOperator('='));
    }
}
?>
