<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\NodeType;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

#[CoversClass(Node::class)]
final class NodeTest extends AbstractTestCase
{
    public function testNewNode(): void
    {
        $node = new Node(new Config(), 'Object Node');
        self::assertSame('Object Node', $node->name);
        self::assertSame('Object Node', $node->realName);
        self::assertSame(NodeType::Object, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testNewNodeWithEmptyName(): void
    {
        $node = new Node(new Config(), '');
        self::assertSame('', $node->name);
        self::assertSame('', $node->realName);
        self::assertSame(NodeType::Object, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testNewContainerNode(): void
    {
        $node = new Node(new Config(), 'Container Node', NodeType::Container);
        self::assertSame('Container Node', $node->name);
        self::assertSame('Container Node', $node->realName);
        self::assertSame(NodeType::Container, $node->type);
        self::assertFalse($node->isGroup);
    }

    public function testNewGroupNode(): void
    {
        $node = new Node(new Config(), 'Group Node', NodeType::Object, true);
        self::assertSame('Group Node', $node->name);
        self::assertSame('Group Node', $node->realName);
        self::assertSame(NodeType::Object, $node->type);
        self::assertTrue($node->isGroup);
    }

    /** @psalm-suppress DocblockTypeContradiction */
    public function testAddChildNode(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $childOne = new Node($config, 'child one');
        $childTwo = new Node($config, 'child two');
        self::assertSame([], $parent->children);
        self::assertNull($childOne->parent);
        self::assertNull($childTwo->parent);
        $parent->addChild($childOne);
        self::assertSame([$childOne], $parent->children);
        self::assertSame($parent, $childOne->parent);
        self::assertNull($childTwo->parent);
        $parent->addChild($childTwo);
        self::assertSame([$childOne, $childTwo], $parent->children);
        self::assertSame($parent, $childOne->parent);
        self::assertSame($parent, $childTwo->parent);
    }

    public function testGetChildNode(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $child = new Node($config, 'child real name');
        $child->name = 'child';
        self::assertNull($parent->getChild('child'));
        self::assertNull($parent->getChild('child', true));
        self::assertNull($parent->getChild('child real name'));
        self::assertNull($parent->getChild('child real name', true));
        $parent->addChild($child);
        self::assertSame($child, $parent->getChild('child'));
        self::assertNull($parent->getChild('child', true));
        self::assertNull($parent->getChild('child real name'));
        self::assertSame($child, $parent->getChild('child real name', true));
        $child->isNew = true;
        self::assertNull($parent->getChild('child'));
        self::assertNull($parent->getChild('child', true));
        self::assertNull($parent->getChild('child real name'));
        self::assertSame($child, $parent->getChild('child real name', true));
    }

    public function testGetChildNodeWithEqualNumericName(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $childOne = new Node($config, '0');
        $childTwo = new Node($config, '00');
        $parent->addChild($childOne);
        $parent->addChild($childTwo);
        self::assertSame($childTwo, $parent->getChild('00'));
        self::assertSame($childOne, $parent->getChild('0'));
        self::assertSame($childTwo, $parent->getChild('00', true));
        self::assertSame($childOne, $parent->getChild('0', true));
    }

    public function testRemoveChildNode(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $childOne = new Node($config, 'child one');
        $childTwo = new Node($config, 'child two');
        $childThree = new Node($config, 'child three');
        $parent->addChild($childOne);
        $parent->addChild($childTwo);
        $parent->addChild($childThree);
        $parent->addChild($childTwo);
        self::assertSame([$childOne, $childTwo, $childThree, $childTwo], $parent->children);
        $parent->removeChild('child two');
        /** @psalm-suppress DocblockTypeContradiction */
        self::assertSame([0 => $childOne, 2 => $childThree, 3 => $childTwo], $parent->children);
    }

    public function testParents(): void
    {
        $config = new Config();
        $dbContainer = new Node($config, 'root', NodeType::Container);
        $dbGroup = new Node($config, 'db_group', NodeType::Container, true);
        $dbOne = new Node($config, 'db_group__one');
        $dbTwo = new Node($config, 'db_group__two');
        $tableContainer = new Node($config, 'tables', NodeType::Container);
        $table = new Node($config, 'table');
        $dbContainer->addChild($dbGroup);
        $dbGroup->addChild($dbOne);
        $dbGroup->addChild($dbTwo);
        $dbOne->addChild($tableContainer);
        $tableContainer->addChild($table);
        self::assertSame([], $dbContainer->parents(false, true));
        self::assertSame([$dbContainer], $dbContainer->parents(true, true));
        self::assertSame([$dbContainer], $dbGroup->parents(false, true, true));
        self::assertSame([$dbGroup, $dbContainer], $dbGroup->parents(true, true, true));
        self::assertSame([$dbOne], $table->parents());
        self::assertSame([$table, $dbOne], $table->parents(true));
        self::assertSame([$tableContainer, $dbOne, $dbContainer], $table->parents(false, true));
        self::assertSame([$table, $tableContainer, $dbOne, $dbContainer], $table->parents(true, true));
        self::assertSame([$dbOne], $table->parents(false, false, true));
        self::assertSame([$tableContainer, $dbOne, $dbGroup, $dbContainer], $table->parents(false, true, true));
        self::assertSame(
            [$table, $tableContainer, $dbOne, $dbGroup, $dbContainer],
            $table->parents(true, true, true),
        );
    }

    public function testRealParent(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $child = new Node($config, 'child');
        $grandchild = new Node($config, 'grandchild');
        $parent->addChild($child);
        $child->addChild($grandchild);
        self::assertFalse($parent->getRealParent());
        self::assertSame($parent, $child->getRealParent());
        self::assertSame($child, $grandchild->getRealParent());
    }

    public function testNodeHasChildren(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $child = new Node($config, 'child');
        self::assertFalse($parent->hasChildren(true));
        self::assertFalse($parent->hasChildren(false));
        $parent->addChild($child);
        self::assertTrue($parent->hasChildren(true));
        self::assertTrue($parent->hasChildren(false));
    }

    public function testNodeHasChildrenWithContainers(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $containerOne = new Node($config, 'container 1', NodeType::Container);
        $containerTwo = new Node($config, 'container 2', NodeType::Container);
        $child = new Node($config, 'child');
        self::assertFalse($parent->hasChildren());
        self::assertFalse($parent->hasChildren(false));
        $parent->addChild($containerOne);
        self::assertTrue($parent->hasChildren());
        self::assertFalse($parent->hasChildren(false));
        $containerOne->addChild($containerTwo);
        self::assertTrue($parent->hasChildren());
        self::assertFalse($parent->hasChildren(false));
        $containerTwo->addChild($child);
        self::assertTrue($parent->hasChildren());
        self::assertTrue($parent->hasChildren(false));
    }

    public function testNodeHasSiblings(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $childOne = new Node($config, 'child one');
        $childTwo = new Node($config, 'child two');
        $parent->addChild($childOne);
        self::assertFalse($parent->hasSiblings());
        self::assertFalse($childOne->hasSiblings());
        $parent->addChild($childTwo);
        self::assertTrue($childOne->hasSiblings());
    }

    public function testNodeHasSiblingsWithContainers(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $childOne = new Node($config, 'child one');
        $containerOne = new Node($config, 'container 1', NodeType::Container);
        $containerTwo = new Node($config, 'container 2', NodeType::Container);
        $childTwo = new Node($config, 'child two');
        $parent->addChild($childOne);
        $parent->addChild($containerOne);
        self::assertFalse($childOne->hasSiblings(), 'An empty container node should not be considered a sibling.');
        $containerOne->addChild($containerTwo);
        self::assertFalse(
            $childOne->hasSiblings(),
            'A container node with empty children should not be considered a sibling.',
        );
        $containerOne->addChild($childTwo);
        self::assertTrue($childOne->hasSiblings(), 'A container node with children should be considered a sibling.');
    }

    public function testNodeHasSiblingsForNodesAtLevelThree(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $child = new Node($config, 'child');
        $grandchild = new Node($config, 'grandchild');
        $greatGrandchild = new Node($config, 'great grandchild');
        $parent->addChild($child);
        $child->addChild($grandchild);
        $grandchild->addChild($greatGrandchild);
        // Should return false for node that are two levels deeps
        self::assertFalse($grandchild->hasSiblings());
        // Should return true for node that are three levels deeps
        self::assertTrue($greatGrandchild->hasSiblings());
    }

    public function testGetPaths(): void
    {
        $config = new Config();
        $parent = new Node($config, 'parent');
        $group = new Node($config, 'group', NodeType::Container, true);
        $childOne = new Node($config, 'child one');
        $container = new Node($config, 'container', NodeType::Container);
        $childTwo = new Node($config, 'child two');
        $parent->addChild($group);
        $group->addChild($childOne);
        $childOne->addChild($container);
        $container->addChild($childTwo);
        self::assertSame(
            [
                'aPath' => 'cGFyZW50.Y2hpbGQgb25l.Y29udGFpbmVy.Y2hpbGQgdHdv',
                'aPath_clean' => ['parent', 'child one', 'container', 'child two'],
                'vPath' => 'cGFyZW50.Z3JvdXA=.Y2hpbGQgb25l.Y29udGFpbmVy.Y2hpbGQgdHdv',
                'vPath_clean' => ['parent', 'group', 'child one', 'container', 'child two'],
            ],
            $childTwo->getPaths(),
        );
    }

    public function testGetWhereClause(): void
    {
        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $method = new ReflectionMethod(Node::class, 'getWhereClause');

        $config = Config::getInstance();
        // Vanilla case
        $node = new Node($config, 'default');
        self::assertSame('WHERE TRUE ', $method->invoke($node, 'SCHEMA_NAME'));

        // When a schema names is passed as search clause
        self::assertSame(
            "WHERE TRUE AND `SCHEMA_NAME` LIKE '%schemaName%' ",
            $method->invoke($node, 'SCHEMA_NAME', 'schemaName'),
        );

        if (! isset($config->selectedServer)) {
            $config->selectedServer = [];
        }

        // When hide_db regular expression is present
        $config->selectedServer['hide_db'] = 'regexpHideDb';
        self::assertSame(
            "WHERE TRUE AND `SCHEMA_NAME` NOT REGEXP 'regexpHideDb' ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($config->selectedServer['hide_db']);

        // When only_db directive is present and it's a single db
        $config->selectedServer['only_db'] = 'stringOnlyDb';
        self::assertSame(
            "WHERE TRUE AND ( `SCHEMA_NAME` LIKE 'stringOnlyDb' ) ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($config->selectedServer['only_db']);

        // When only_db directive is present and it's an array of dbs
        $config->selectedServer['only_db'] = ['onlyDbOne', 'onlyDbTwo'];
        self::assertSame(
            'WHERE TRUE AND ( `SCHEMA_NAME` LIKE \'onlyDbOne\' OR `SCHEMA_NAME` LIKE \'onlyDbTwo\' ) ',
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($config->selectedServer['only_db']);
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping enabled.
     */
    public function testGetDataWithEnabledISAndGroupingEnabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = true;
        $config->settings['FirstLevelNavigationItems'] = 20;
        $config->settings['NavigationTreeDbSeparator'] = '_';

        $expectedSql = 'SELECT `SCHEMA_NAME` ';
        $expectedSql .= 'FROM `INFORMATION_SCHEMA`.`SCHEMATA`, ';
        $expectedSql .= '(';
        $expectedSql .= 'SELECT DB_first_level ';
        $expectedSql .= 'FROM ( ';
        $expectedSql .= 'SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, ';
        $expectedSql .= "'_', 1) ";
        $expectedSql .= 'DB_first_level ';
        $expectedSql .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $expectedSql .= 'WHERE TRUE ';
        $expectedSql .= ') t ';
        $expectedSql .= 'ORDER BY DB_first_level ASC ';
        $expectedSql .= 'LIMIT 10, 20';
        $expectedSql .= ') t2 ';
        $expectedSql .= "WHERE TRUE AND 1 = LOCATE(CONCAT(DB_first_level, '_'), ";
        $expectedSql .= "CONCAT(SCHEMA_NAME, '_')) ";
        $expectedSql .= 'ORDER BY SCHEMA_NAME ASC';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);

        $node = new Node($config, 'node');

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('fetchSingleColumn')->with($expectedSql);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");
        DatabaseInterface::$instance = $dbi;
        $node->getData(new UserPrivileges(), $relationParameters, '', 10);
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping disabled.
     */
    public function testGetDataWithEnabledISAndGroupingDisabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = false;
        $config->settings['FirstLevelNavigationItems'] = 20;

        $expectedSql = 'SELECT `SCHEMA_NAME` ';
        $expectedSql .= 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` ';
        $expectedSql .= 'WHERE TRUE ';
        $expectedSql .= 'ORDER BY `SCHEMA_NAME` ';
        $expectedSql .= 'LIMIT 10, 20';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);

        $node = new Node($config, 'node');

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('fetchSingleColumn')->with($expectedSql);

        DatabaseInterface::$instance = $dbi;
        $node->getData(new UserPrivileges(), $relationParameters, '', 10);
    }

    /**
     * Tests when DisableIS is true and navigation tree grouping enabled.
     */
    public function testGetDataWithDisabledISAndGroupingEnabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->settings['NavigationTreeEnableGrouping'] = true;
        $config->settings['FirstLevelNavigationItems'] = 10;
        $config->settings['NavigationTreeDbSeparator'] = '_';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::NAV_WORK => true,
            RelationParameters::NAVIGATION_HIDING => 'navigationhiding',
        ]);

        $node = new Node($config, 'node');

        $resultStub = self::createMock(DummyResult::class);

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' ")
            ->willReturn($resultStub);
        $resultStub->expects(self::exactly(3))
            ->method('fetchRow')
            ->willReturn(['0' => 'db'], ['0' => 'aa_db'], []);

        $dbi->expects(self::once())
            ->method('fetchSingleColumn')
            ->with(
                "SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' AND ("
                . " LOCATE('db_', CONCAT(`Database`, '_')) = 1"
                . " OR LOCATE('aa_', CONCAT(`Database`, '_')) = 1 )",
            );
        $dbi->expects(self::any())->method('escapeMysqlWildcards')
            ->willReturnArgument(0);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $node->getData(new UserPrivileges(), $relationParameters, '', 0, 'db');
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping enabled.
     */
    public function testGetPresenceWithEnabledISAndGroupingEnabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = true;
        $config->settings['NavigationTreeDbSeparator'] = '_';

        $query = 'SELECT COUNT(*) ';
        $query .= 'FROM ( ';
        $query .= "SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) ";
        $query .= 'DB_first_level ';
        $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $query .= 'WHERE TRUE ';
        $query .= ') t ';

        $node = new Node($config, 'node');

        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('fetchValue')->with($query);
        DatabaseInterface::$instance = $dbi;
        self::assertSame(0, $node->getPresence(new UserPrivileges()));
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping disabled.
     */
    public function testGetPresenceWithEnabledISAndGroupingDisabled(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['NavigationTreeEnableGrouping'] = false;

        $query = 'SELECT COUNT(*) ';
        $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $query .= 'WHERE TRUE ';

        $node = new Node($config, 'node');
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())->method('fetchValue')->with($query);
        DatabaseInterface::$instance = $dbi;
        self::assertSame(0, $node->getPresence(new UserPrivileges()));
    }

    /**
     * Tests when DisableIS is true
     */
    public function testGetPresenceWithDisabledIS(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;
        $config->settings['NavigationTreeEnableGrouping'] = true;

        $node = new Node($config, 'node');

        $resultStub = self::createMock(DummyResult::class);

        // test with no search clause
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SHOW DATABASES WHERE TRUE ')
            ->willReturn($resultStub);

        DatabaseInterface::$instance = $dbi;
        self::assertSame(0, $node->getPresence(new UserPrivileges()));

        // test with a search clause
        $dbi = self::createMock(DatabaseInterface::class);
        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%dbname%' ")
            ->willReturn($resultStub);
        $dbi->expects(self::any())->method('escapeMysqlWildcards')
            ->willReturnArgument(0);
        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        self::assertSame(0, $node->getPresence(new UserPrivileges(), '', 'dbname'));
    }

    public function testGetInstanceForNewNode(): void
    {
        $node = (new Node(new Config()))->getInstanceForNewNode('New', 'new_database italics');
        self::assertSame('New', $node->name);
        self::assertSame(NodeType::Object, $node->type);
        self::assertFalse($node->isGroup);
        self::assertTrue($node->isNew);
        self::assertSame('new_database italics', $node->classes);
    }
}
