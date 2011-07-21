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

class PMA_showDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = true;
        $GLOBALS['pmaThemeImage'] = 'theme/';
    }

    function testShowDocuReplaceHelpImg()
    {
        $anchor = "relation";
        $expected = '<a href="Documentation.html#' . $anchor . '" target="documentation"><img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_help.png" width="11" height="11" alt="' . __('Documentation') . '" title="' . __('Documentation') . '" /></a>';

        $this->assertEquals($expected, PMA_showDocu($anchor));

    }

    function testShowDocuNotReplaceHelpImg()
    {
        $anchor = "relation";
        $expected = '[<a href="Documentation.html#' . $anchor . '" target="documentation">' . __('Documentation') . '</a>]';

        $this->assertEquals($expected, PMA_showDocu($anchor));

    }
}
