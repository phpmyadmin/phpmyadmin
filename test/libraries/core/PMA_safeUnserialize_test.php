<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_safeUnserialize
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

class PMA_safeUnserialize_test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for unserializing
     *
     * @param string $data     Serialized data
     * @param mixed  $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideMySQLHosts
     */
    function testSanitizeMySQLHost($data, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_safeUnserialize($data)
        );
    }

    /**
     * Test data provider
     *
     * @return array
     */
    function provideMySQLHosts()
    {
        return array(
            array('s:6:"foobar";', 'foobar'),
            array('foobar', null),
            array('b:0;', false),
            array('O:1:"a":1:{s:5:"value";s:3:"100";}', null),
            array('O:8:"stdClass":1:{s:5:"field";O:8:"stdClass":0:{}}', null),
            array(serialize(array(1, 2, 3)), array(1, 2, 3)),
            array(serialize('string""'), 'string""'),
            array(serialize(array('foo' => 'bar')), array('foo' => 'bar')),
            array(serialize(array('1', new stdClass(), '2')), null),
        );
    }

}

