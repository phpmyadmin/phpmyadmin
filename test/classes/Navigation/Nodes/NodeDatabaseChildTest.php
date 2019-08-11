<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild;
use PhpMyAdmin\Tests\PmaTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild class
 *
 * @package PhpMyAdmin-test
 */
class NodeDatabaseChildTest extends PmaTestCase
{
    /**
     * Mock of NodeDatabaseChild
     * @var MockObject
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['pmaThemePath'] = $GLOBALS['PMA_Theme']->getPath();
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['pmaThemeImage'] = '';
        $_SESSION['relation'][1]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][1]['navwork'] = true;
        $this->object = $this->getMockForAbstractClass(
            'PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild',
            ['child']
        );
    }

    /**
     * Tears down the fixture.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
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
        $this->assertStringContainsString(
            '<a href="navigation.php" data-post="'
            . 'hideNavItem=1&amp;itemType=itemType&amp;itemName=child'
            . '&amp;dbName=parent&amp;lang=en" class="hideNavItem ajax">',
            $html
        );
    }
}
