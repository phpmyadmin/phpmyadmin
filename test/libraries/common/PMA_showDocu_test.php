<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_CommonFunctions::showDocu from CommonFunctions.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

class PMA_showDocu_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    function testShowDocu()
    {
        $anchor = "relation";
        $expected = '<a href="Documentation.html#' . $anchor . '" target="documentation">'
                  . '<img src="themes/dot.gif" title="' . __('Documentation') . '" '
                  . 'alt="' . __('Documentation') . '" class="icon ic_b_help" /></a>';

        $this->assertEquals(
            $expected, PMA_CommonFunctions::getInstance()->showDocu($anchor)
        );

    }
}
