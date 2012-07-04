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

    /**
     * Test for isUnaryOperator
     */
    public function testUnary()
    {
        $this->assertTrue($this->object->isUnaryOperator('IS NULL'));
        $this->assertFalse($this->object->isUnaryOperator('='));
    }

    /**
     * Test for getUnaryOperators
     */
    public function testGetUnaryOperators(){
        $this->assertEquals($this->object->getUnaryOperators(),
            array(
                'IS NULL',
                'IS NOT NULL',
                "= ''",
                "!= ''",
            )
        );
    }

    /**
     * Test for getNullOperators
     */
    public function testGetNullOperators(){
        $this->assertEquals($this->object->getNullOperators(),
            array(
                'IS NULL',
                'IS NOT NULL',
            )
        );
    }

    /**
     * Test for getEnumOperators
     */
    public function testGetEnumOperators(){
        $this->assertEquals($this->object->getEnumOperators(),
            array(
                '=',
                '!=',
            )
        );
    }

    /**
     * Test for getTextOperators
     */
    public function testgetTextOperators(){
        $this->assertEquals($this->object->getTextOperators(),
            array(
                'LIKE',
                'LIKE %...%',
                'NOT LIKE',
                '=',
                '!=',
                'REGEXP',
                'REGEXP ^...$',
                'NOT REGEXP',
                "= ''",
                "!= ''",
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            )
        );
    }

    /**
     * Test for getNumberOperators
     */
    public function testGetNumberOperators(){
        $this->assertEquals($this->object->getNumberOperators(),
            array(
                '=',
                '>',
                '>=',
                '<',
                '<=',
                '!=',
                'LIKE',
                'NOT LIKE',
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            )
        );
    }

    /**
     * @param string  $type Type of field
     * @param boolean $null Whether field can be NULL
     * @param $output
     *
     * @dataProvider providerForGetTypeOperators
     */
    public function testGetTypeOperators($type, $null, $output){
        $this->assertEquals(
          $this->object->getTypeOperators($type, $null),
          $output
        );
    }

    /**
     * data provider for testGetTypeOperators
     */
    public function providerForGetTypeOperators(){
        return array(
            array(
                'enum',
                false,
                array(
                    '=',
                    '!=',
                )
            ),
            array(
                'CHAR',
                true,
                array(
                    '=',
                    '>',
                    '>=',
                    '<',
                    '<=',
                    '!=',
                    'LIKE',
                    'NOT LIKE',
                    'IN (...)',
                    'NOT IN (...)',
                    'BETWEEN',
                    'NOT BETWEEN',
                    'IS NULL',
                    'IS NOT NULL',
                ),
                array(
                    'int',
                    false,
                    array(
                        '=',
                        '!=',
                    )
                ),
            )
        );
    }

    public function testGetTypeOperatorsHtml(){
        $this->assertTrue(true);
    }
}
?>
