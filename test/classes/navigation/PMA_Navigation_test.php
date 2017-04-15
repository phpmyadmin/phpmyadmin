<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PMA_Navigation class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/Navigation.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';


class PMA_Navigation_test extends PHPUnit_Framework_TestCase
{
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['token'] = 'token';
        $GLOBALS['cfg']['Servers'] = 1;
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    /**
     * Test for GetDisplay
     *
     * @return void
     */
    public function testGetDisplay()
    {
        $navigation = new PMA_Navigation();
        $html_navigation = $navigation->getDisplay();
        $this->assertContains(
            "pma_navigation",
            $html_navigation
        );
        $this->assertContains(
            "pma_navigation_resizer",
            $html_navigation
        );
        $this->assertContains(
            "pma_navigation_collapser",
            $html_navigation
        );
    }
}
?>
