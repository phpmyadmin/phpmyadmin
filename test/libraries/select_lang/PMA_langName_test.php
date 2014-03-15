<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_languageName from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/select_lang.lib.php';

/**
 * Test for PMA_languageName from select_lang.lib.php
 *
 * @package PhpMyAdmin-test
 * @group select_lang.lib-tests
 */
class PMA_LangName_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Data provider for testLangName
     *
     * @return array
     */
    function dataProvider()
    {
        return array(
            array(array('en|english', 'en', ''),'English'),
            array(
                array('fr|french', 'fr', 'Fran&ccedil;ais'),
                'Fran&ccedil;ais - French'
            ),
            array(
                array('zh|chinese simplified', 'zh', '&#20013;&#25991;'),
                '&#20013;&#25991; - Chinese simplified'
            ),
        );
    }

    /**
     * Test for
     *
     * @param string $test   Language code
     * @param string $result Expected value
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    function testLangName($test, $result)
    {
        $this->assertEquals($result, PMA_languageName($test));
    }
}
