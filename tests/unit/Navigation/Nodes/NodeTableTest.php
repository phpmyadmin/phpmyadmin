<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Navigation\Nodes\NodeTable;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(NodeTable::class)]
class NodeTableTest extends AbstractTestCase
{
    /**
     * Test for __construct
     */
    public function testConstructor(): void
    {
        $config = Config::getInstance();
        $config->settings['NavigationTreeDefaultTabTable'] = '/table/search';
        $config->settings['NavigationTreeDefaultTabTable2'] = '/table/change';

        $parent = new NodeTable($config, 'default');
        self::assertSame('/sql', $parent->link->route);
        self::assertSame(['pos' => 0, 'db' => null, 'table' => null], $parent->link->params);
        self::assertSame('Browse', $parent->link->title);
        self::assertSame('/table/search', $parent->icon->route);
        self::assertSame(['db' => null, 'table' => null], $parent->icon->params);
        self::assertNotNull($parent->secondIcon);
        self::assertSame('/table/change', $parent->secondIcon->route);
        self::assertSame(['db' => null, 'table' => null], $parent->secondIcon->params);
        self::assertStringContainsString('table', $parent->classes);
    }

    /**
     * Tests whether the node icon is properly set based on the icon target.
     *
     * @param '/table/sql'|'/table/search'|'/table/change'|'/sql'|'/table/structure' $target
     */
    #[DataProvider('providerForTestIcon')]
    public function testIcon(string $target, string $imageName, string $imageTitle): void
    {
        $config = Config::getInstance();
        $config->settings['NavigationTreeDefaultTabTable'] = $target;
        $node = new NodeTable($config, 'default');
        self::assertSame($imageName, $node->icon->image);
        self::assertSame($imageTitle, $node->icon->title);
    }

    /**
     * Data provider for testIcon().
     *
     * @return array<array{'/table/sql'|'/table/search'|'/table/change'|'/sql'|'/table/structure', string, string}>
     */
    public static function providerForTestIcon(): array
    {
        return [
            ['/table/structure', 'b_props', 'Structure'],
            ['/table/search', 'b_search', 'Search'],
            ['/table/change', 'b_insrow', 'Insert'],
            ['/table/sql', 'b_sql', 'SQL'],
            ['/sql', 'b_browse', 'Browse'],
        ];
    }
}
