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

/**
 * Test for PMA_langList from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */
class PMA_LangList_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_langList
     *
     * @return void
     */
    function testLangList()
    {
        $GLOBALS['lang_path'] = '';
        $expected = array('en' => PMA_langDetails('en'));

        $this->assertEquals($expected, PMA_langList());
    }

    /**
     * Test for PMA_langList
     *
     * @return void
     */
    function testLangListWithDir()
    {
        $GLOBALS['lang_path'] = './locale/';
        $expected = array('en' => PMA_langDetails('en'));

        $handle = @opendir($GLOBALS['lang_path']);
        if ($handle === false) {
            $this->markTestSkipped("Cannot open file with locales");
        }

        while (false !== ($file = readdir($handle))) {
            $path = $GLOBALS['lang_path'] . '/' . $file
                . '/LC_MESSAGES/phpmyadmin.mo';
            if ($file != "." && $file != ".." && file_exists($path)) {
                $expected[$file] = PMA_langDetails($file);
            }
        }

        $this->assertEquals($expected, PMA_langList());
    }

    /**
     * Test for PMA_langList
     *
     * @return void
     */
    function testLangListWithWrongDir()
    {
        $GLOBALS['lang_path'] = '/root/';
        $expected = array('en' => PMA_langDetails('en'));

        $this->assertEquals($expected, PMA_langList());
    }
}
