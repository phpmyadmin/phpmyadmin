<?php
/**
 * Tests for Types.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

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
        $this->object = new PMA_Types();
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

    /**
     * Test for getTypeOperatorsHtml
     *
     * @param string  $type             Type of field
     * @param boolean $null             Whether field can be NULL
     * @param string  $selectedOperator Option to be selected
     * @param $output
     *
     * @dataProvider providerForTestGetTypeOperatorsHtml
     */
    public function testGetTypeOperatorsHtml($type, $null, $selectedOperator, $output){
        $this->assertEquals(
            $this->object->getTypeOperatorsHtml($type, $null, $selectedOperator),
            $output
        );
    }

    /**
     * Provider for testGetTypeOperatorsHtml
     */
    public function providerForTestGetTypeOperatorsHtml(){
        return array(
            array(
                'enum',
                false,
                '=',
                '<option value="=" selected="selected">=</option><option value="!=">!=</option>'
            )
        );
    }

    /**
     * Test for getTypeDescription
     */
    public function testGetTypeDescription(){
        $this->assertEquals(
            $this->object->getTypeDescription('enum'),
            ''
        );
    }

    /**
     * Test for getFunctionsClass
     */
    public function testGetFunctionsClass(){
        $this->assertEquals(
            $this->object->getFunctionsClass('enum'),
            array()
        );
    }

    /**
     * Test for getFunctions
     */
    public function testGetFunctions(){
        $this->assertEquals(
            $this->object->getFunctions('enum'),
            array()
        );
    }

    /**
     * Test for getAllFunctions
     */
    public function testGetAllFunctions(){
        $this->assertEquals(
            $this->object->getAllFunctions(),
            array()
        );
    }

    /**
     * Test for getAttributes
     */
    public function testGetAttributes(){
        $this->assertEquals(
            $this->object->getAttributes(),
            array()
        );
    }

    /**
     * Test for getColumns
     */
    public function testGetColumns(){
        $this->assertEquals(
            $this->object->getColumns(),
            array(
                'INT',
                'VARCHAR',
                'TEXT',
                'DATE',
            )
        );
    }
}
?>
