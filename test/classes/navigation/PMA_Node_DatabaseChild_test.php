<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for Node_DatabaseChild
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/navigation/Nodes/Node.class.php';
require_once 'libraries/navigation/Nodes/Node_DatabaseChild.class.php';
require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Node_DatabaseChild class
 *
 * @package PhpMyAdmin-test
 */
class Node_DatabaseChildTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Node_DatabaseChild
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
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemePath'] = $_SESSION['PMA_Theme']->getPath();
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'db_structure.php';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['relation'][1]['navwork'] = true;
        $this->object = $this->getMockForAbstractClass(
            'Node_DatabaseChild', array('child')
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
        $parent = PMA_NodeFactory::getInstance('Node_Database', 'parent');
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
?>
