<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_CommonFunctions::getIcon() from CommonFunctions.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/Theme.class.php';

class PMA_getIcon_test extends PHPUnit_Framework_TestCase
{
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    function testGetIconWithoutPropertiesIconic()
    {
        $GLOBALS['cfg']['PropertiesIconic'] = false;

        $this->assertEquals(
            '<span class="nowrap"></span>',
            PMA_CommonFunctions::getInstance()->getIcon('b_comment.png')
        );
    }

    function testGetIconWithPropertiesIconic()
    {
        $GLOBALS['cfg']['PropertiesIconic'] = true;

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment" /></span>',
            PMA_CommonFunctions::getInstance()->getIcon('b_comment.png')
        );
    }

    function testGetIconAlternate()
    {
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="' . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /></span>',
            PMA_CommonFunctions::getInstance()->getIcon('b_comment.png', $alternate_text)
        );
    }

    function testGetIconWithForceText()
    {
        $GLOBALS['cfg']['PropertiesIconic'] = true;
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="' . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /> ' . $alternate_text . '</span>',
            PMA_CommonFunctions::getInstance()->getIcon('b_comment.png', $alternate_text, true)
        );

    }
}
