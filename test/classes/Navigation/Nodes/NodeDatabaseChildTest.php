<?php
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
use PhpMyAdmin\Url;
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
     *
     * @var NodeDatabaseChild|MockObject
     */
    protected $object;

    /**
     * Sets up the fixture.
     *
     * @return void
     *
     * @access protected
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
            NodeDatabaseChild::class,
            ['child']
        );
    }

    /**
     * Tears down the fixture.
     *
     * @return void
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Tests getHtmlForControlButtons() method
     *
     * @return void
     *
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
            '<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
            . 'hideNavItem=1&amp;itemType=itemType&amp;itemName=child'
            . '&amp;dbName=parent&amp;lang=en" class="hideNavItem ajax">',
            $html
        );
    }
}
