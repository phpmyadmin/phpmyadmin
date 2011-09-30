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
require_once 'libraries/Theme.class.php';

class PMA_showPHPDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    function testShwoPHPDocuReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = true;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="http://php.net/manual/' . $lang . '/' . $target
            . '" target="documentation"><img src="themes/dot.gif" title="'
            . __('Documentation') . '" alt="' . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }

    function testShwoPHPDocuNotReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = false;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '[<a href="http://php.net/manual/' . $lang . '/' . $target 
            . '" target="documentation">' . __('Documentation') . '</a>]';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }


}
