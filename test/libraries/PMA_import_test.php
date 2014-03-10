<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_checkTimeout()
 * from libraries/import.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/*
 * we must set $GLOBALS['server'] here
 * since 'check_user_privileges.lib.php' will use it globally
 */
$GLOBALS['server'] = 0;

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/import.lib.php';

/**
 * Tests for import functions
 *
 * @package PhpMyAdmin-test
 */
class PMA_Import_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_checkTimeout
     *
     * @return void
     */
    function testCheckTimeout()
    {
        global $timestamp, $maximum_time, $timeout_passed;

        //Reinit values.
        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = false;

        $this->assertFalse(PMA_checkTimeout());

        //Reinit values.
        $timestamp = time();
        $maximum_time = 0;
        $timeout_passed = true;

        $this->assertFalse(PMA_checkTimeout());

        //Reinit values.
        $timestamp = time();
        $maximum_time = 30;
        $timeout_passed = true;

        $this->assertTrue(PMA_checkTimeout());

        //Reinit values.
        $timestamp = time()-15;
        $maximum_time = 30;
        $timeout_passed = false;

        $this->assertFalse(PMA_checkTimeout());

        //Reinit values.
        $timestamp = time()-60;
        $maximum_time = 30;
        $timeout_passed = false;

        $this->assertTrue(PMA_checkTimeout());
    }

    /**
     * Test for PMA_lookForUse
     *
     * @return void
     */
    function testLookForUse()
    {
        $this->assertEquals(
            array(null, null),
            PMA_lookForUse(null, null, null)
        );

        $this->assertEquals(
            array('myDb', null),
            PMA_lookForUse(null, 'myDb', null)
        );

        $this->assertEquals(
            array('myDb', true),
            PMA_lookForUse(null, 'myDb', true)
        );

        $this->assertEquals(
            array('myDb', true),
            PMA_lookForUse('select 1 from myTable', 'myDb', true)
        );

        $this->assertEquals(
            array('anotherDb', true),
            PMA_lookForUse('use anotherDb', 'myDb', false)
        );

        $this->assertEquals(
            array('anotherDb', true),
            PMA_lookForUse('use anotherDb', 'myDb', true)
        );

        $this->assertEquals(
            array('anotherDb', true),
            PMA_lookForUse('use `anotherDb`;', 'myDb', true)
        );
    }

    /**
     * Test for PMA_getColumnAlphaName
     *
     * @param string $expected Expected result of the function
     * @param int    $num      The column number
     *
     * @return void
     *
     * @dataProvider provGetColumnAlphaName
     */
    function testGetColumnAlphaName($expected, $num)
    {
        $this->assertEquals($expected, PMA_getColumnAlphaName($num));
    }

    /**
     * Data provider for testGetColumnAlphaName
     *
     * @return array
     */
    function provGetColumnAlphaName()
    {
        return array(
            array('A', 1),
            array('Z', 0),
            array('AA', 27),
            array('AZ', 52),
            array('BA', 53),
            array('BB', 54),
        );
    }

    /**
     * Test for PMA_getColumnNumberFromName
     *
     * @param int         $expected Expected result of the function
     * @param string|null $name     column name(i.e. "A", or "BC", etc.)
     *
     * @return void
     *
     * @dataProvider provGetColumnNumberFromNamee
     */
    function testGetColumnNumberFromName($expected, $name)
    {
        $this->assertEquals($expected, PMA_getColumnNumberFromName($name));
    }

    /**
     * Data provider for testGetColumnNumberFromName
     *
     * @return array
     */
    function provGetColumnNumberFromNamee()
    {
        return array(
            array(1, 'A'),
            array(26, 'Z'),
            array(27, 'AA'),
            array(52, 'AZ'),
            array(53, 'BA'),
            array(54, 'BB'),
        );
    }

    /**
     * Test for PMA_getDecimalPrecision
     *
     * @param int         $expected Expected result of the function
     * @param string|null $size     Size of field
     *
     * @return void
     *
     * @dataProvider provGetDecimalPrecision
     */
    function testGetDecimalPrecision($expected, $size)
    {
        $this->assertEquals($expected, PMA_getDecimalPrecision($size));
    }

    /**
     * Data provider for testGetDecimalPrecision
     *
     * @return array
     */
    function provGetDecimalPrecision()
    {
        return array(
            array(2, '2,1'),
            array(6, '6,2'),
            array(6, '6,0'),
            array(16, '16,2'),
        );
    }

    /**
     * Test for PMA_getDecimalScale
     *
     * @param int         $expected Expected result of the function
     * @param string|null $size     Size of field
     *
     * @return void
     *
     * @dataProvider provGetDecimalScale
     */
    function testGetDecimalScale($expected, $size)
    {
        $this->assertEquals($expected, PMA_getDecimalScale($size));
    }

    /**
     * Data provider for testGetDecimalScale
     *
     * @return array
     */
    function provGetDecimalScale()
    {
        return array(
            array(1, '2,1'),
            array(2, '6,2'),
            array(0, '6,0'),
            array(20, '30,20'),
        );
    }

    /**
     * Test for PMA_getDecimalSize
     *
     * @param array       $expected Expected result of the function
     * @param string|null $cell     Cell content
     *
     * @return void
     *
     * @dataProvider provGetDecimalSize
     */
    function testGetDecimalSize($expected, $cell)
    {
        $this->assertEquals($expected, PMA_getDecimalSize($cell));
    }

    /**
     * Data provider for testGetDecimalSize
     *
     * @return array
     */
    function provGetDecimalSize()
    {
        return array(
            array(array(2, 1, '2,1'), '2.1'),
            array(array(2, 1, '2,1'), '6.2'),
            array(array(3, 1, '3,1'), '10.0'),
            array(array(4, 2, '4,2'), '30.20'),
        );
    }

    /**
     * Test for PMA_detectType
     *
     * @param int         $expected Expected result of the function
     * @param int|null    $type     Last cumulative column type (VARCHAR or INT or
     *                              BIGINT or DECIMAL or NONE)
     * @param string|null $cell     String representation of the cell for which a
     *                              best-fit type is to be determined
     *
     * @return void
     *
     * @dataProvider provDetectType
     */
    function testDetectType($expected, $type, $cell)
    {
        $this->assertEquals($expected, PMA_detectType($type, $cell));
    }

    /**
     * Data provider for testDetectType
     *
     * @return array
     */
    function provDetectType()
    {
        return array(
            array(NONE, null, 'NULL'),
            array(NONE, NONE, 'NULL'),
            array(INT, INT, 'NULL'),
            array(VARCHAR, VARCHAR, 'NULL'),
            array(VARCHAR, null, null),
            array(VARCHAR, INT, null),
            array(INT, INT, '10'),
            array(DECIMAL, DECIMAL, '10.2'),
            array(DECIMAL, INT, '10.2'),
            array(BIGINT, BIGINT, '2147483648'),
            array(BIGINT, INT, '2147483648'),
            array(VARCHAR, VARCHAR, 'test'),
            array(VARCHAR, INT, 'test'),
        );
    }
}
