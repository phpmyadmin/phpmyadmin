<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_langName from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/select_lang.lib.php';

class PMA_langName_test extends PHPUnit_Framework_TestCase
{
    function dataProvider()
    {
        return array(
            array(array('en|english', 'en', ''),'English'),
            array(array('fr|french', 'fr', 'Fran&ccedil;ais'), 'Fran&ccedil;ais - French'),
            array(array('zh|chinese simplified', 'zh', '&#20013;&#25991;'), '&#20013;&#25991; - Chinese simplified'),
        );
    }

    /**
     * @dataProvider dataProvider
     * @return void
     */
    function testLangName($test, $result)
    {
        $this->assertEquals($result, PMA_langName($test));
    }
}