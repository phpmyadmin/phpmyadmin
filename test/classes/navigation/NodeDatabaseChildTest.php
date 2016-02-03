<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA\libraries\navigation\nodes\NodeDatabaseChild
 *
 * @package PhpMyAdmin-test
 */

use PMA\libraries\navigation\NodeFactory;
use PMA\libraries\navigation\nodes\NodeDatabaseChild;
use PMA\libraries\Theme;

require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\navigation\nodes\NodeDatabaseChild class
 *
 * @package PhpMyAdmin-test
 */
class NodeDatabaseChildTest extends PMATestCase
{
    /**
     * @var NodeDatabaseChild
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $_SESSION['PMA_Theme'] = new Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['relation'][1]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][1]['navwork'] = true;
        $this->object = $this->getMockForAbstractClass(
            'PMA\libraries\navigation\nodes\NodeDatabaseChild', array('child')
        );
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Tests getHtmlForControlButtons() method
     *
     * @return void
     * @test
     */
    public function testGetHtmlForControlButtons()
    {
        $parent = NodeFactory::getInstance('NodeDatabase', 'parent');
        $parent->addChild($this->object);
        $this->object->expects($this->once())
            ->method('getItemType')
            ->will($this->returnValue('itemType'));
        $html = $this->object->getHtmlForControlButtons();

        $this->assertStringStartsWith(
            '<span class="navItemControls">',
            $html
        );
        $this->assertStringEndsWith(
            '</span>',
            $html
        );
        $this->assertContains(
            '<a href="navigation.php' . PMA_URL_getCommon()
            . '&hideNavItem=true&itemType=itemType&itemName=child'
            . '&dbName=parent" class="hideNavItem ajax">',
            $html
        );
    }
}
