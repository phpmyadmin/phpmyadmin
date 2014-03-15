<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PMA_isValid() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

/**
 * Tests for PMA_isValid() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_IsValid_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testNoVarType
     *
     * @return array
     */
    public static function providerNoVarTypeProvider()
    {
        return array(
            array(0, false, 0),
            array(0, false, 1),
            array(1, false, null),
            array(1.1, false, null),
            array('', false, null),
            array(' ', false, null),
            array('0', false, null),
            array('string', false, null),
            array(array(), false, null),
            array(array(1, 2, 3), false, null),
            array(true, false, null),
            array(false, false, null));
    }

    /**
     * Test for PMA_isValid
     *
     * @param mixed $var     Variable to check
     * @param mixed $type    Type
     * @param mixed $compare Compared value
     *
     * @return void
     *
     * @dataProvider providerNoVarTypeProvider
     */
    public function testNoVarType($var, $type, $compare)
    {
        $this->assertTrue(PMA_isValid($var, $type, $compare));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testVarNotSetAfterTest()
    {
        PMA_isValid($var);
        $this->assertFalse(isset($var));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNotSet()
    {
        $this->assertFalse(PMA_isValid($var));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testEmptyString()
    {
        $var = '';
        $this->assertFalse(PMA_isValid($var));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNotEmptyString()
    {
        $var = '0';
        $this->assertTrue(PMA_isValid($var));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testZero()
    {
        $var = 0;
        $this->assertTrue(PMA_isValid($var));
        $this->assertTrue(PMA_isValid($var, 'int'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNullFail()
    {
        $var = null;
        $this->assertFalse(PMA_isValid($var));

        $var = 'null_text';
        $this->assertFalse(PMA_isValid($var, 'null'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNotSetArray()
    {
        /** @var $array undefined array */
        $this->assertFalse(PMA_isValid($array['x']));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testScalarString()
    {
        $var = 'string';
        $this->assertTrue(PMA_isValid($var, 'len'));
        $this->assertTrue(PMA_isValid($var, 'scalar'));
        $this->assertTrue(PMA_isValid($var));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testScalarInt()
    {
        $var = 1;
        $this->assertTrue(PMA_isValid($var, 'int'));
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testScalarFloat()
    {
        $var = 1.1;
        $this->assertTrue(PMA_isValid($var, 'float'));
        $this->assertTrue(PMA_isValid($var, 'double'));
        $this->assertTrue(PMA_isValid($var, 'scalar'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testScalarBool()
    {
        $var = true;
        $this->assertTrue(PMA_isValid($var, 'scalar'));
        $this->assertTrue(PMA_isValid($var, 'bool'));
        $this->assertTrue(PMA_isValid($var, 'boolean'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNotScalarArray()
    {
        $var = array('test');
        $this->assertFalse(PMA_isValid($var, 'scalar'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNotScalarNull()
    {
        $var = null;
        $this->assertFalse(PMA_isValid($var, 'scalar'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNumericInt()
    {
        $var = 1;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNumericFloat()
    {
        $var = 1.1;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNumericZero()
    {
        $var = 0;
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNumericString()
    {
        $var = '+0.1';
        $this->assertTrue(PMA_isValid($var, 'numeric'));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testValueInArray()
    {
        $var = 'a';
        $this->assertTrue(PMA_isValid($var, array('a', 'b',)));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testValueNotInArray()
    {
        $var = 'c';
        $this->assertFalse(PMA_isValid($var, array('a', 'b',)));
    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testNumericIdentical()
    {
        $var = 1;
        $compare = 1;
        $this->assertTrue(PMA_isValid($var, 'identic', $compare));

        $var = 1;
        $compare += 2;
        $this->assertFalse(PMA_isValid($var, 'identic', $compare));

        $var = 1;
        $compare = '1';
        $this->assertFalse(PMA_isValid($var, 'identic', $compare));
    }

    /**
     * Data provider for testSimilarType
     *
     * @return array
     */
    public function providerSimilarType()
    {
        return array(
            array(1, 1),
            array(1.5, 1.5),
            array(true, true),
            array('string', "string"),
            array(array(1, 2, 3.4), array(1, 2, 3.4)),
            array(array(1, '2', '3.4', 5, 'text'), array('1', '2', 3.4,'5'))
        );
    }

    /**
     * Test for PMA_isValid
     *
     * @param mixed $var     Variable
     * @param mixed $compare Compare
     *
     * @return void
     *
     * @dataProvider providerSimilarType
     */
    public function testSimilarType($var, $compare)
    {
        $this->assertTrue(PMA_isValid($var, 'similar', $compare));
        $this->assertTrue(PMA_isValid($var, 'equal', $compare));
        $this->assertTrue(PMA_isValid($compare, 'similar', $var));
        $this->assertTrue(PMA_isValid($compare, 'equal', $var));

    }

    /**
     * Test for PMA_isValid
     *
     * @return void
     */
    public function testOtherTypes()
    {
        $var = new PMA_isValid_test();
        $this->assertFalse(PMA_isValid($var, 'class'));
    }

}

?>
