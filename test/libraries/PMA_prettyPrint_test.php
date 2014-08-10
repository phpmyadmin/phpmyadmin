<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_prettyPrint()
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test
 */
require_once 'libraries/error_report.lib.php';

/**
 * tests for PMA_prettyPrint()
 *
 * @package PhpMyAdmin-test
 */
class PMA_PrettyPrint_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Tests correct display of none array objects.
     *
     * @return void
     */
    public function testNonArray()
    {
        $this->assertEquals(
            "test\n",
            PMA_prettyPrint('test')
        );
    }

    /**
     * Tests correct display of multilevel associative arrays.
     *
     * @return void
     */
    public function testDeepArray()
    {
        $this->assertEquals(
            "key[test0]: \"value0\"\nkey[test1]: \"value1\"\n",
            PMA_prettyPrint(
                array('key' => array('test0' => 'value0', 'test1' => 'value1'))
            )
        );
    }

    /**
     * Tests correct display of multilevel associative arrays.
     *
     * @return void
     */
    public function testNonAssociativeArray()
    {
        $this->assertEquals(
            "key[0]: \"value0\"\nkey[1]: \"value1\"\n",
            PMA_prettyPrint(array('key' => array('value0', 'value1')))
        );
    }
}
?>
