<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_expandUserString from common.lib.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/common.lib.php';

if (!defined('PMA_VERSION')) {
    define('PMA_VERSION', 'TEST');
}

/**
 * Test for PMA_expandUserString function.
 */
class PMA_expandUserString_test extends PHPUnit_Extensions_OutputTestCase
{

    /**
     * Setup variables needed by test.
     */
    public function setup()
    {
        $GLOBALS['cfg'] = array(
            'Server' => array(
                'host' => 'host&',
                'verbose' => 'verbose',
                ));
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = 'table';
    }

    /**
     * Test case for expanding strings
     *
     * @dataProvider provider
     */
    public function testExpand($in, $out)
    {
        $this->assertEquals($out, PMA_expandUserString($in));
    }

    /**
     * Test case for expanding strings with escaping
     *
     * @dataProvider provider
     */
    public function testExpandEscape($in, $out)
    {
        $this->assertEquals(htmlspecialchars($out), PMA_expandUserString($in, 'htmlspecialchars'));
    }

    /**
     * Data provider
     *
     * @return array
     */
    public function provider()
    {
        return array(
            array('@SERVER@', 'host&'),
            array('@VSERVER@', 'verbose'),
            array('@DATABASE@', 'database'),
            array('@TABLE@', 'table'),
            array('@IGNORE@', '@IGNORE@'),
            array('@PHPMYADMIN@', 'phpMyAdmin ' . PMA_VERSION),
            );
    }
}
