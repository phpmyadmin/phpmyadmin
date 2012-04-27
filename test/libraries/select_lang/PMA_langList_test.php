<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_langList from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/vendor_config.php';
require_once 'libraries/select_lang.lib.php';

class PMA_langList_test extends PHPUnit_Framework_TestCase
{
    function testLangList()
    {
        $GLOBALS['lang_path'] = '';
        $expected = array('en' => PMA_langDetails('en'));

        $this->assertEquals($expected, PMA_langList());
    }

    function testLangListWithDir()
    {
        $GLOBALS['lang_path'] = './locale/';
        $expected = array('en' => PMA_langDetails('en'));

        $handle = @opendir($GLOBALS['lang_path']);
        if ($handle === false) {
            $this->markTestSkipped("Cannot open file with locales");
        }

        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && file_exists($GLOBALS['lang_path'] . '/' . $file . '/LC_MESSAGES/phpmyadmin.mo')) {
                $expected[$file] = PMA_langDetails($file);
            }
        }

        $this->assertEquals($expected, PMA_langList());
    }

    function testLangListWithWrongDir()
    {
        $GLOBALS['lang_path'] = '/root/';
        $expected = array('en' => PMA_langDetails('en'));

        $this->assertEquals($expected, PMA_langList());
    }
}
