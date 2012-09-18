<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::showPHPDocu from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_showPHPDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;
    }

    function testShowPHPDocu()
    {
        $target = "docu";
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="./url.php?url=http%3A%2F%2Fphp.net%2Fmanual%2F' . $lang
            . '%2F' . $target . '&amp;server=99&amp;lang=en&amp;token=token'
            . '" target="documentation"><img src="themes/dot.gif" title="'
            . __('Documentation') . '" alt="' . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals(
            $expected, PMA_Util::showPHPDocu($target)
        );
    }
}
