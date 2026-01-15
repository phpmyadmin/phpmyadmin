<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Navigation\Nodes\NodeTable;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
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

    public function testGetColumnsWithoutDisableIS(): void
    {
        $config = Config::getInstance();

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())
            ->method('fetchResultSimple')
            ->willReturnOnConsecutiveCalls(
                [
                    ['name' => 'foo1', 'type' => 'varchar(255)', 'key' => '', 'default' => '', 'nullable' => ''],
                    ['name' => 'foo2', 'type' => 'varchar(64)', 'key' => '', 'default' => '', 'nullable' => ''],
                    ['name' => 'foo3', 'type' => 'int(11)', 'key' => '', 'default' => '0', 'nullable' => ''],
                    ['name' => 'foo4', 'type' => 'text', 'key' => '', 'default' => '', 'nullable' => ''],
                ],
            );

        $nodeContainer = new NodeDatabase($config, 'DB');
        $nodeTable = new NodeTable($config, 'node');
        $nodeContainer->addChild($nodeTable);

        $result = $nodeTable->getColumns($dbi, 1);
        self::assertCount(4, $result);

        self::assertSame('foo1 (varchar(255))', $result[0]->displayName);
        self::assertSame('foo2 (varchar(64))', $result[1]->displayName);
        self::assertSame('foo3 (int(11), 0)', $result[2]->displayName);
        self::assertSame('foo4 (text)', $result[3]->displayName);
    }

    public function testGetColumnsWithDisableIS(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->set('MaxNavigationItems', 2);

        $dbi = self::createMock(DatabaseInterface::class);
        $resultStub = self::createMock(DummyResult::class);
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SHOW COLUMNS FROM `node` FROM `DB`')
            ->willReturn($resultStub);
        $resultStub->expects(self::once())
            ->method('fetchAllAssoc')
            ->willReturnOnConsecutiveCalls(
                [
                    ['Field' => 'foo1', 'Type' => 'varchar(255)', 'Key' => '', 'Default' => '', 'Null' => 'NO'],
                    ['Field' => 'foo2', 'Type' => 'varchar(64)', 'Key' => '', 'Default' => '', 'Null' => 'NO'],
                    ['Field' => 'foo3', 'Type' => 'int(11)', 'Key' => '', 'Default' => '0', 'Null' => 'NO'],
                    ['Field' => 'foo4', 'Type' => 'text', 'Key' => '', 'Default' => '', 'Null' => 'NO'],
                ],
            );

        $nodeContainer = new NodeDatabase($config, 'DB');
        $nodeTable = new NodeTable($config, 'node');
        $nodeContainer->addChild($nodeTable);

        $result = $nodeTable->getColumns($dbi, 1);
        self::assertCount(2, $result);

        self::assertSame('foo2 (varchar)', $result[0]->displayName);
        self::assertSame('foo3 (int, 0)', $result[1]->displayName);
    }

    public function testGetTriggersWithoutDisableIS(): void
    {
        $config = Config::getInstance();

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())
            ->method('fetchSingleColumn')
            ->willReturnOnConsecutiveCalls(
                [
                    'foo1',
                    'foo2',
                    'foo3',
                    'foo4',
                ],
            );

        $nodeContainer = new NodeDatabase($config, 'DB');
        $nodeTable = new NodeTable($config, 'node');
        $nodeContainer->addChild($nodeTable);

        $result = $nodeTable->getTriggers($dbi, 1);
        self::assertCount(4, $result);

        self::assertSame('foo1', $result[0]->name);
        self::assertSame('foo2', $result[1]->name);
        self::assertSame('foo3', $result[2]->name);
        self::assertSame('foo4', $result[3]->name);
    }

    public function testGetTriggersWithDisableIS(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->set('MaxNavigationItems', 2);

        $dbi = self::createMock(DatabaseInterface::class);
        $resultStub = self::createMock(DummyResult::class);
        $dbi->expects(self::once())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SHOW TRIGGERS FROM `DB` WHERE `Table` = \'node\'')
            ->willReturn($resultStub);
        $resultStub->expects(self::once())
            ->method('fetchAllAssoc')
            ->willReturnOnConsecutiveCalls(
                [
                    ['Trigger' => 'foo1'],
                    ['Trigger' => 'foo2'],
                    ['Trigger' => 'foo3'],
                    ['Trigger' => 'foo4'],
                ],
            );

        $nodeContainer = new NodeDatabase($config, 'DB');
        $nodeTable = new NodeTable($config, 'node');
        $nodeContainer->addChild($nodeTable);

        $result = $nodeTable->getTriggers($dbi, 1);
        self::assertCount(2, $result);

        self::assertSame('foo2', $result[0]->name);
        self::assertSame('foo3', $result[1]->name);
    }

    public function testGetIndexes(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->set('MaxNavigationItems', 2);

        $dbi = self::createMock(DatabaseInterface::class);
        $resultStub = self::createMock(DummyResult::class);
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SHOW INDEXES FROM `node` FROM `DB`')
            ->willReturn($resultStub);
        $resultStub->expects(self::once())
            ->method('fetchAllAssoc')
            ->willReturnOnConsecutiveCalls(
                [
                    ['Key_name' => 'foo1'],
                    ['Key_name' => 'foo2'],
                    ['Key_name' => 'foo3'],
                    ['Key_name' => 'foo4'],
                ],
            );

        $nodeContainer = new NodeDatabase($config, 'DB');
        $nodeTable = new NodeTable($config, 'node');
        $nodeContainer->addChild($nodeTable);

        $result = $nodeTable->getIndexes($dbi, 1);
        self::assertCount(2, $result);

        self::assertSame('foo2', $result[0]->name);
        self::assertSame('foo3', $result[1]->name);
    }
}
