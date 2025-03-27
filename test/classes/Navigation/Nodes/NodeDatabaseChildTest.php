<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild
 */
class NodeDatabaseChildTest extends AbstractTestCase
{
    /**
     * Mock of NodeDatabaseChild
     *
     * @var NodeDatabaseChild|MockObject
     */
    protected $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        parent::setLanguage();
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ])->toArray();
        $this->object = $this->getMockForAbstractClass(
            NodeDatabaseChild::class,
            ['child']
        );
    }

    /**
     * Tears down the fixture.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Tests getHtmlForControlButtons() method
     */
    public function testGetHtmlForControlButtons(): void
    {
        $parent = NodeFactory::getInstance('NodeDatabase', 'parent');
        $parent->addChild($this->object);
        $this->object->expects($this->once())
            ->method('getItemType')
            ->will($this->returnValue('itemType'));
        $html = $this->object->getHtmlForControlButtons();

        self::assertStringStartsWith('<span class="navItemControls">', $html);
        self::assertStringEndsWith('</span>', $html);
        self::assertStringContainsString('<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
        . 'hideNavItem=1&itemType=itemType&itemName=child'
        . '&dbName=parent&lang=en" class="hideNavItem ajax">', $html);
    }
}
