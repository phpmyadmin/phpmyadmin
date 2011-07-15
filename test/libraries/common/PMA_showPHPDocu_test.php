<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_showPHPDocu from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_showPHPDocu_test.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';

class PMA_showPHPDocu_test extends PHPUnit_Framework_TestCase
{
    function testShwoPHPDocuReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = true;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="http://php.net/manual/' . $lang . '/' . $target . '" target="documentation"><img class="icon" src="'
                    . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }

    function testShwoPHPDocuNotReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = false;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '[<a href="http://php.net/manual/' . $lang . '/' . $target . '" target="documentation">' . __('Documentation') . '</a>]';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }


}