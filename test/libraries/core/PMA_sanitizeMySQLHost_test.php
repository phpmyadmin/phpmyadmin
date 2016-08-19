<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_sanitizeMySQLHost
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

class PMA_sanitizeMySQLHost_test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for MySQL host sanitizing
     *
     * @param string $host     Test host name
     * @param string $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideMySQLHosts
     */
    function testSanitizeMySQLHost($host, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_sanitizeMySQLHost($host)
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
            array('p:foo.bar', 'foo.bar'),
            array('bar.baz', 'bar.baz'),
            array('P:example.com', 'example.com'),
        );
    }

}
