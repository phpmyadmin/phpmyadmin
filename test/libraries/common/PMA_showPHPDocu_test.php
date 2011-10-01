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
require_once 'libraries/core.lib.php';
require_once 'libraries/common.lib.php';
require_once 'libraries/Theme.class.php';

class PMA_showPHPDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;
    }

    function testShwoPHPDocuReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = true;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="./url.php?url=http%3A%2F%2Fphp.net%2Fmanual%2F' . $lang . '%2F' . $target . '&amp;server=99'
            . '" target="documentation"><img src="themes/dot.gif" title="'
            . __('Documentation') . '" alt="' . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }

    function testShwoPHPDocuNotReplaceHelpImg()
    {
        $GLOBALS['cfg']['ReplaceHelpImg'] = false;

        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '[<a href="./url.php?url=http%3A%2F%2Fphp.net%2Fmanual%2F' . $lang . '%2F' . $target . '&amp;server=99'
            . '" target="documentation">' . __('Documentation') . '</a>]';

        $this->assertEquals($expected, PMA_showPHPDocu($target));
    }


}
