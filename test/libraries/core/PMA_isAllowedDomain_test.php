<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_isAllowedDomain
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

class PMA_isAllowedDomain_test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for unserializing
     *
     * @param string $url      URL to test
     * @param mixed  $expected Expected result
     *
     * @return void
     *
     * @dataProvider provideURLs
     */
    function testIsAllowedDomain($url, $expected)
    {
        $_SERVER['SERVER_NAME'] = 'server.local';
        $this->assertEquals(
            $expected,
            PMA_isAllowedDomain($url)
        );
    }

    /**
     * Test data provider
     *
     * @return array
     */
    function provideURLs()
    {
        return array(
            array('https://www.phpmyadmin.net/', true),
            array('http://duckduckgo.com\\@github.com', false),
            array('https://github.com/', true),
            array('https://server.local/', true),
            array('./relative/', false),
        );
    }

}


