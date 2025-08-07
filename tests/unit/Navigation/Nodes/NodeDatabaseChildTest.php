<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Navigation\Nodes\NodeDatabaseChild;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

#[CoversClass(NodeDatabaseChild::class)]
#[CoversClass(NodeDatabase::class)]
class NodeDatabaseChildTest extends AbstractTestCase
{
    protected NodeDatabaseChild&MockObject $object;

    /**
     * Sets up the fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $config = Config::getInstance();
        $config->settings['DefaultTabDatabase'] = '/database/structure';
        $config->settings['ServerDefault'] = 1;
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        $this->object = $this->getMockBuilder(NodeDatabaseChild::class)
            ->setConstructorArgs([$config, 'child'])
            ->onlyMethods(['getItemType'])
            ->getMock();
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
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);

        $parent = new NodeDatabase(Config::getInstance(), 'parent');
        $parent->addChild($this->object);
        $this->object->expects(self::once())
            ->method('getItemType')
            ->willReturn('itemType');
        $html = $this->object->getHtmlForControlButtons($relationParameters->navigationItemsHidingFeature);

        self::assertStringStartsWith('<span class="navItemControls">', $html);
        self::assertStringEndsWith('</span>', $html);
        self::assertStringContainsString(
            '<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
            . 'hideNavItem=1&itemType=itemType&itemName=child'
            . '&dbName=parent&lang=en" class="hideNavItem ajax">',
            $html,
        );
    }
}
