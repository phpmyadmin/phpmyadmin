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
 *
 * @package PhpMyAdmin-test
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
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Types();
    }

    /**
     * Test for isUnaryOperator
     *
     * @return void
     */
    public function testUnary()
    {
        $this->assertTrue($this->object->isUnaryOperator('IS NULL'));
        $this->assertFalse($this->object->isUnaryOperator('='));
    }

    /**
     * Test for getUnaryOperators
     *
     * @return void
     */
    public function testGetUnaryOperators()
    {
        $this->assertEquals(
            array(
                'IS NULL',
                'IS NOT NULL',
                "= ''",
                "!= ''",
            ),
            $this->object->getUnaryOperators()
        );
    }

    /**
     * Test for getNullOperators
     *
     * @return void
     */
    public function testGetNullOperators()
    {
        $this->assertEquals(
            array(
                'IS NULL',
                'IS NOT NULL',
            ),
            $this->object->getNullOperators()
        );
    }

    /**
     * Test for getEnumOperators
     *
     * @return void
     */
    public function testGetEnumOperators()
    {
        $this->assertEquals(
            array(
                '=',
                '!=',
            ),
            $this->object->getEnumOperators()
        );
    }

    /**
     * Test for getTextOperators
     *
     * @return void
     */
    public function testgetTextOperators()
    {
        $this->assertEquals(
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
            ),
            $this->object->getTextOperators()
        );
    }

    /**
     * Test for getNumberOperators
     *
     * @return void
     */
    public function testGetNumberOperators()
    {
        $this->assertEquals(
            array(
                '=',
                '>',
                '>=',
                '<',
                '<=',
                '!=',
                'LIKE',
                'LIKE %...%',
                'NOT LIKE',
                'IN (...)',
                'NOT IN (...)',
                'BETWEEN',
                'NOT BETWEEN',
            ),
            $this->object->getNumberOperators()
        );
    }

    /**
     * Test for getting type operators
     *
     * @param string  $type   Type of field
     * @param boolean $null   Whether field can be NULL
     * @param string  $output Expected output
     *
     * @return void
     *
     * @dataProvider providerForGetTypeOperators
     */
    public function testGetTypeOperators($type, $null, $output)
    {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperators($type, $null)
        );
    }

    /**
     * data provider for testGetTypeOperators
     *
     * @return data for testGetTypeOperators
     */
    public function providerForGetTypeOperators()
    {
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
                    'LIKE %...%',
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
     * @param string  $output           Expected output
     *
     * @return void
     *
     * @dataProvider providerForTestGetTypeOperatorsHtml
     */
    public function testGetTypeOperatorsHtml(
        $type, $null, $selectedOperator, $output
    ) {
        $this->assertEquals(
            $output,
            $this->object->getTypeOperatorsHtml($type, $null, $selectedOperator)
        );
    }

    /**
     * Provider for testGetTypeOperatorsHtml
     *
     * @return test data for getTypeOperatorsHtml
     */
    public function providerForTestGetTypeOperatorsHtml()
    {
        return array(
            array(
                'enum',
                false,
                '=',
                '<option value="=" selected="selected">=</option>'
                . '<option value="!=">!=</option>'
            )
        );
    }

    /**
     * Test for getTypeDescription
     *
     * @return void
     */
    public function testGetTypeDescription()
    {
        $this->assertEquals(
            '',
            $this->object->getTypeDescription('enum')
        );
    }

    /**
     * Test for getFunctionsClass
     *
     * @return void
     */
    public function testGetFunctionsClass()
    {
        $this->assertEquals(
            array(),
            $this->object->getFunctionsClass('enum')
        );
    }

    /**
     * Test for getFunctions
     *
     * @return void
     */
    public function testGetFunctions()
    {
        $this->assertEquals(
            array(),
            $this->object->getFunctions('enum')
        );
    }

    /**
     * Test for getAllFunctions
     *
     * @return void
     */
    public function testGetAllFunctions()
    {
        $this->assertEquals(
            array(),
            $this->object->getAllFunctions()
        );
    }

    /**
     * Test for getAttributes
     *
     * @return void
     */
    public function testGetAttributes()
    {
        $this->assertEquals(
            array(),
            $this->object->getAttributes()
        );
    }

    /**
     * Test for getColumns
     *
     * @return void
     */
    public function testGetColumns()
    {
        $this->assertEquals(
            array(
                'INT',
                'VARCHAR',
                'TEXT',
                'DATE',
            ),
            $this->object->getColumns()
        );
    }
}
