<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;

#[CoversClass(Node::class)]
final class NodeTest extends AbstractTestCase
{
    public function testNewNode(): void
    {
        $node = new Node('Object Node');
        $this->assertSame('Object Node', $node->name);
        $this->assertSame('Object Node', $node->realName);
        $this->assertSame(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testNewNodeWithEmptyName(): void
    {
        $node = new Node('');
        $this->assertSame('', $node->name);
        $this->assertSame('', $node->realName);
        $this->assertSame(Node::OBJECT, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testNewContainerNode(): void
    {
        $node = new Node('Container Node', Node::CONTAINER);
        $this->assertSame('Container Node', $node->name);
        $this->assertSame('Container Node', $node->realName);
        $this->assertSame(Node::CONTAINER, $node->type);
        $this->assertFalse($node->isGroup);
    }

    public function testNewGroupNode(): void
    {
        $node = new Node('Group Node', Node::OBJECT, true);
        $this->assertSame('Group Node', $node->name);
        $this->assertSame('Group Node', $node->realName);
        $this->assertSame(Node::OBJECT, $node->type);
        $this->assertTrue($node->isGroup);
    }

    /** @psalm-suppress DocblockTypeContradiction */
    public function testAddChildNode(): void
    {
        $parent = new Node('parent');
        $childOne = new Node('child one');
        $childTwo = new Node('child two');
        $this->assertSame([], $parent->children);
        $this->assertNull($childOne->parent);
        $this->assertNull($childTwo->parent);
        $parent->addChild($childOne);
        $this->assertSame([$childOne], $parent->children);
        $this->assertSame($parent, $childOne->parent);
        $this->assertNull($childTwo->parent);
        $parent->addChild($childTwo);
        $this->assertSame([$childOne, $childTwo], $parent->children);
        $this->assertSame($parent, $childOne->parent);
        $this->assertSame($parent, $childTwo->parent);
    }

    public function testGetChildNode(): void
    {
        $parent = new Node('parent');
        $child = new Node('child real name');
        $child->name = 'child';
        $this->assertNull($parent->getChild('child'));
        $this->assertNull($parent->getChild('child', true));
        $this->assertNull($parent->getChild('child real name'));
        $this->assertNull($parent->getChild('child real name', true));
        $parent->addChild($child);
        $this->assertSame($child, $parent->getChild('child'));
        $this->assertNull($parent->getChild('child', true));
        $this->assertNull($parent->getChild('child real name'));
        $this->assertSame($child, $parent->getChild('child real name', true));
        $child->isNew = true;
        $this->assertNull($parent->getChild('child'));
        $this->assertNull($parent->getChild('child', true));
        $this->assertNull($parent->getChild('child real name'));
        $this->assertSame($child, $parent->getChild('child real name', true));
    }

    public function testRemoveChildNode(): void
    {
        $parent = new Node('parent');
        $childOne = new Node('child one');
        $childTwo = new Node('child two');
        $childThree = new Node('child three');
        $parent->addChild($childOne);
        $parent->addChild($childTwo);
        $parent->addChild($childThree);
        $parent->addChild($childTwo);
        $this->assertSame([0 => $childOne, 1 => $childTwo, 2 => $childThree, 3 => $childTwo], $parent->children);
        $parent->removeChild('child two');
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertSame([0 => $childOne, 2 => $childThree, 3 => $childTwo], $parent->children);
    }

    public function testParents(): void
    {
        $dbContainer = new Node('root', Node::CONTAINER);
        $dbGroup = new Node('db_group', Node::CONTAINER, true);
        $dbOne = new Node('db_group__one');
        $dbTwo = new Node('db_group__two');
        $tableContainer = new Node('tables', Node::CONTAINER);
        $table = new Node('table');
        $dbContainer->addChild($dbGroup);
        $dbGroup->addChild($dbOne);
        $dbGroup->addChild($dbTwo);
        $dbOne->addChild($tableContainer);
        $tableContainer->addChild($table);
        $this->assertSame([], $dbContainer->parents(false, true));
        $this->assertSame([$dbContainer], $dbContainer->parents(true, true));
        $this->assertSame([$dbContainer], $dbGroup->parents(false, true, true));
        $this->assertSame([$dbGroup, $dbContainer], $dbGroup->parents(true, true, true));
        $this->assertSame([$dbOne], $table->parents());
        $this->assertSame([$table, $dbOne], $table->parents(true));
        $this->assertSame([$tableContainer, $dbOne, $dbContainer], $table->parents(false, true));
        $this->assertSame([$table, $tableContainer, $dbOne, $dbContainer], $table->parents(true, true));
        $this->assertSame([$dbOne], $table->parents(false, false, true));
        $this->assertSame([$tableContainer, $dbOne, $dbGroup, $dbContainer], $table->parents(false, true, true));
        $this->assertSame(
            [$table, $tableContainer, $dbOne, $dbGroup, $dbContainer],
            $table->parents(true, true, true),
        );
    }

    public function testRealParent(): void
    {
        $parent = new Node('parent');
        $child = new Node('child');
        $grandchild = new Node('grandchild');
        $parent->addChild($child);
        $child->addChild($grandchild);
        $this->assertFalse($parent->realParent());
        $this->assertSame($parent, $child->realParent());
        $this->assertSame($child, $grandchild->realParent());
    }

    public function testNodeHasChildren(): void
    {
        $parent = new Node('parent');
        $child = new Node('child');
        $this->assertFalse($parent->hasChildren(true));
        $this->assertFalse($parent->hasChildren(false));
        $parent->addChild($child);
        $this->assertTrue($parent->hasChildren(true));
        $this->assertTrue($parent->hasChildren(false));
    }

    public function testNodeHasChildrenWithContainers(): void
    {
        $parent = new Node('parent');
        $containerOne = new Node('container 1', Node::CONTAINER);
        $containerTwo = new Node('container 2', Node::CONTAINER);
        $child = new Node('child');
        $this->assertFalse($parent->hasChildren());
        $this->assertFalse($parent->hasChildren(false));
        $parent->addChild($containerOne);
        $this->assertTrue($parent->hasChildren());
        $this->assertFalse($parent->hasChildren(false));
        $containerOne->addChild($containerTwo);
        $this->assertTrue($parent->hasChildren());
        $this->assertFalse($parent->hasChildren(false));
        $containerTwo->addChild($child);
        $this->assertTrue($parent->hasChildren());
        $this->assertTrue($parent->hasChildren(false));
    }

    public function testNodeHasSiblings(): void
    {
        $parent = new Node('parent');
        $childOne = new Node('child one');
        $childTwo = new Node('child two');
        $parent->addChild($childOne);
        $this->assertFalse($parent->hasSiblings());
        $this->assertFalse($childOne->hasSiblings());
        $parent->addChild($childTwo);
        $this->assertTrue($childOne->hasSiblings());
    }

    public function testNodeHasSiblingsWithContainers(): void
    {
        $parent = new Node('parent');
        $childOne = new Node('child one');
        $containerOne = new Node('container 1', Node::CONTAINER);
        $containerTwo = new Node('container 2', Node::CONTAINER);
        $childTwo = new Node('child two');
        $parent->addChild($childOne);
        $parent->addChild($containerOne);
        $this->assertFalse($childOne->hasSiblings(), 'An empty container node should not be considered a sibling.');
        $containerOne->addChild($containerTwo);
        $this->assertFalse(
            $childOne->hasSiblings(),
            'A container node with empty children should not be considered a sibling.',
        );
        $containerOne->addChild($childTwo);
        $this->assertTrue($childOne->hasSiblings(), 'A container node with children should be considered a sibling.');
    }

    public function testNodeHasSiblingsForNodesAtLevelThree(): void
    {
        $parent = new Node('parent');
        $child = new Node('child');
        $grandchild = new Node('grandchild');
        $greatGrandchild = new Node('great grandchild');
        $parent->addChild($child);
        $child->addChild($grandchild);
        $grandchild->addChild($greatGrandchild);
        // Should return false for node that are two levels deeps
        $this->assertFalse($grandchild->hasSiblings());
        // Should return true for node that are three levels deeps
        $this->assertTrue($greatGrandchild->hasSiblings());
    }

    public function testNumChildren(): void
    {
        $parent = new Node('parent');
        $this->assertSame(0, $parent->numChildren());
        $child = new Node('child one');
        $parent->addChild($child);
        $this->assertSame(1, $parent->numChildren());
        // add a direct grandchild, this one doesn't count as it's not enclosed in a CONTAINER
        $child->addChild(new Node('child two'));
        $this->assertSame(1, $parent->numChildren());
        // add a container, this one doesn't count wither
        $container = new Node('container', Node::CONTAINER);
        $parent->addChild($container);
        $this->assertSame(1, $parent->numChildren());
        // add a grandchild to container, this one counts
        $container->addChild(new Node('child three'));
        $this->assertSame(2, $parent->numChildren());
        // add another grandchild to container, this one counts
        $container->addChild(new Node('child four'));
        $this->assertSame(3, $parent->numChildren());
    }

    public function testGetPaths(): void
    {
        $parent = new Node('parent');
        $group = new Node('group', Node::CONTAINER, true);
        $childOne = new Node('child one');
        $container = new Node('container', Node::CONTAINER);
        $childTwo = new Node('child two');
        $parent->addChild($group);
        $group->addChild($childOne);
        $childOne->addChild($container);
        $container->addChild($childTwo);
        $this->assertSame(
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
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $method = new ReflectionMethod(Node::class, 'getWhereClause');

        // Vanilla case
        $node = new Node('default');
        $this->assertSame('WHERE TRUE ', $method->invoke($node, 'SCHEMA_NAME'));

        // When a schema names is passed as search clause
        $this->assertSame(
            "WHERE TRUE AND `SCHEMA_NAME` LIKE '%schemaName%' ",
            $method->invoke($node, 'SCHEMA_NAME', 'schemaName'),
        );

        if (! isset($GLOBALS['cfg']['Server'])) {
            $GLOBALS['cfg']['Server'] = [];
        }

        // When hide_db regular expression is present
        $GLOBALS['cfg']['Server']['hide_db'] = 'regexpHideDb';
        $this->assertSame(
            "WHERE TRUE AND `SCHEMA_NAME` NOT REGEXP 'regexpHideDb' ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['hide_db']);

        // When only_db directive is present and it's a single db
        $GLOBALS['cfg']['Server']['only_db'] = 'stringOnlyDb';
        $this->assertSame(
            "WHERE TRUE AND ( `SCHEMA_NAME` LIKE 'stringOnlyDb' ) ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['only_db']);

        // When only_db directive is present and it's an array of dbs
        $GLOBALS['cfg']['Server']['only_db'] = ['onlyDbOne', 'onlyDbTwo'];
        $this->assertSame(
            'WHERE TRUE AND ( `SCHEMA_NAME` LIKE \'onlyDbOne\' OR `SCHEMA_NAME` LIKE \'onlyDbTwo\' ) ',
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['only_db']);
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping enabled.
     */
    public function testGetDataWithEnabledISAndGroupingEnabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = 20;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';

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
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        $node = new Node('node');

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('fetchResult')->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;
        $node->getData($relationParameters, '', 10);
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping disabled.
     */
    public function testGetDataWithEnabledISAndGroupingDisabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = false;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = 20;

        $expectedSql = 'SELECT `SCHEMA_NAME` ';
        $expectedSql .= 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` ';
        $expectedSql .= 'WHERE TRUE ';
        $expectedSql .= 'ORDER BY `SCHEMA_NAME` ';
        $expectedSql .= 'LIMIT 10, 20';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        $node = new Node('node');

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('fetchResult')->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getData($relationParameters, '', 10);
    }

    /**
     * Tests when DisableIS is true and navigation tree grouping enabled.
     */
    public function testGetDataWithDisabledISAndGroupingEnabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['dbs_to_test'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = 10;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        $node = new Node('node');

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' ")
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->exactly(3))
            ->method('fetchRow')
            ->willReturnOnConsecutiveCalls(
                ['0' => 'db'],
                ['0' => 'aa_db'],
                [],
            );

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' AND ("
                . " LOCATE('db_', CONCAT(`Database`, '_')) = 1"
                . " OR LOCATE('aa_', CONCAT(`Database`, '_')) = 1 )",
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getData($relationParameters, '', 0, 'db');
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping enabled.
     */
    public function testGetPresenceWithEnabledISAndGroupingEnabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';

        $query = 'SELECT COUNT(*) ';
        $query .= 'FROM ( ';
        $query .= "SELECT DISTINCT SUBSTRING_INDEX(SCHEMA_NAME, '_', 1) ";
        $query .= 'DB_first_level ';
        $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $query .= 'WHERE TRUE ';
        $query .= ') t ';

        $node = new Node('node');

        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('fetchValue')->with($query);
        $GLOBALS['dbi'] = $dbi;
        $this->assertSame(0, $node->getPresence());
    }

    /**
     * Tests when DisableIS is false and navigation tree grouping disabled.
     */
    public function testGetPresenceWithEnabledISAndGroupingDisabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = false;

        $query = 'SELECT COUNT(*) ';
        $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $query .= 'WHERE TRUE ';

        $node = new Node('node');
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())->method('fetchValue')->with($query);
        $GLOBALS['dbi'] = $dbi;
        $this->assertSame(0, $node->getPresence());
    }

    /**
     * Tests when DisableIS is true
     */
    public function testGetPresenceWithDisabledIS(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['dbs_to_test'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;

        $node = new Node('node');

        $resultStub = $this->createMock(DummyResult::class);

        // test with no search clause
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SHOW DATABASES WHERE TRUE ')
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('escapeString')->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertSame(0, $node->getPresence());

        // test with a search clause
        $dbi = $this->createMock(DatabaseInterface::class);
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%dbname%' ")
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $this->assertSame(0, $node->getPresence('', 'dbname'));
    }
}
