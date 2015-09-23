<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 ** Test for PMA_Util::getIcon() from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';

/**
 ** Test for PMA_Util::getIcon() from Util.class.php
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_GetIcon_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up
     *
     * @return void
     */
    function setup()
    {
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    /**
     * Test for getIcon
     *
     * @return void
     */
    function testGetIconWithoutActionLinksMode()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'text';

        $this->assertEquals(
            '<span class="nowrap"></span>',
            PMA_Util::getIcon('b_comment.png')
        );
    }

    /**
     * Test for getIcon
     *
     * @return void
     */
    function testGetIconWithActionLinksMode()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment" /></span>',
            PMA_Util::getIcon('b_comment.png')
        );
    }

    /**
     * Test for getIcon
     *
     * @return void
     */
    function testGetIconAlternate()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" /></span>',
            PMA_Util::getIcon('b_comment.png', $alternate_text)
        );
    }

    /**
     * Test for getIcon
     *
     * @return void
     */
    function testGetIconWithForceText()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        // Here we are checking for an icon embedded inside a span (i.e not a menu
        // bar icon
        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment" />&nbsp;' . $alternate_text . '</span>',
            PMA_Util::getIcon('b_comment.png', $alternate_text, true, false)
        );

    }
}
