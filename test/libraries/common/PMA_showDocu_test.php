<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_showDocu from common.lib.php
 *
 * @package phpMyAdmin-test
 * @version $Id: PMA_showDocu.php
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/common.lib.php';
require_once 'libraries/Theme.class.php';

class PMA_showDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    function testShowDocuReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = true;

        $anchor = "relation";
        $expected = '<a href="Documentation.html#' . $anchor . '" target="documentation">'
                  . '<img src="themes/dot.gif" title="' . __('Documentation') . '" '
                  . 'alt="' . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals($expected, PMA_showDocu($anchor));

    }

    function testShowDocuNotReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = false;

        $anchor = "relation";
        $expected = '[<a href="Documentation.html#' . $anchor . '" target="documentation">'
                  . __('Documentation')
                  . '</a>]';

        $this->assertEquals($expected, PMA_showDocu($anchor));

    }
}
