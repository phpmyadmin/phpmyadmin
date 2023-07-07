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

    /**
     * SetUp for hasChildren
     */
    public function testNodeHasChildren(): void
    {
        $parent = new Node('default');
        $emptyContainer = new Node('empty', Node::CONTAINER);
        $child = new Node('default');
        // test with no children
        $this->assertEquals(
            $parent->hasChildren(true),
            false,
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false,
        );
        // test with an empty container
        $parent->addChild($emptyContainer);
        $this->assertEquals(
            $parent->hasChildren(true),
            true,
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false,
        );
        // test with a real child
        $parent->addChild($child);
        $this->assertEquals(
            $parent->hasChildren(true),
            true,
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            true,
        );
    }

    /**
     * SetUp for numChildren
     */
    public function testNumChildren(): void
    {
        // start with root node only
        $parent = new Node('default');
        $this->assertEquals($parent->numChildren(), 0);
        // add a child
        $child = new Node('default');
        $parent->addChild($child);
        $this->assertEquals($parent->numChildren(), 1);
        // add a direct grandchild, this one doesn't count as
        // it's not enclosed in a CONTAINER
        $child->addChild(new Node('default'));
        $this->assertEquals($parent->numChildren(), 1);
        // add a container, this one doesn't count wither
        $container = new Node('default', Node::CONTAINER);
        $parent->addChild($container);
        $this->assertEquals($parent->numChildren(), 1);
        // add a grandchild to container, this one counts
        $container->addChild(new Node('default'));
        $this->assertEquals($parent->numChildren(), 2);
        // add another grandchild to container, this one counts
        $container->addChild(new Node('default'));
        $this->assertEquals($parent->numChildren(), 3);
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

    /**
     * Tests whether Node->hasSiblings() method returns false
     * when the node does not have any siblings.
     */
    public function testHasSiblingsWithNoSiblings(): void
    {
        $parent = new Node('default');
        $child = new Node('default');
        $parent->addChild($child);
        $this->assertFalse($child->hasSiblings());
    }

    /**
     * Tests whether Node->hasSiblings() method returns true
     * when it actually has siblings.
     */
    public function testHasSiblingsWithSiblings(): void
    {
        $parent = new Node('default');
        $firstChild = new Node('default');
        $parent->addChild($firstChild);
        $secondChild = new Node('default');
        $parent->addChild($secondChild);
        // Normal case; two Node:NODE type siblings
        $this->assertTrue($firstChild->hasSiblings());

        $parent = new Node('default');
        $firstChild = new Node('default');
        $parent->addChild($firstChild);
        $secondChild = new Node('default', Node::CONTAINER);
        $parent->addChild($secondChild);
        // Empty Node::CONTAINER type node should not be considered in hasSiblings()
        $this->assertFalse($firstChild->hasSiblings());

        $grandChild = new Node('default');
        $secondChild->addChild($grandChild);
        // Node::CONTAINER type nodes with children are counted for hasSiblings()
        $this->assertTrue($firstChild->hasSiblings());
    }

    /**
     * It is expected that Node->hasSiblings() method always return true
     * for Nodes that are 3 levels deep (columns and indexes).
     */
    public function testHasSiblingsForNodesAtLevelThree(): void
    {
        $parent = new Node('default');
        $child = new Node('default');
        $parent->addChild($child);
        $grandChild = new Node('default');
        $child->addChild($grandChild);
        $greatGrandChild = new Node('default');
        $grandChild->addChild($greatGrandChild);

        // Should return false for node that are two levels deeps
        $this->assertFalse($grandChild->hasSiblings());
        // Should return true for node that are three levels deeps
        $this->assertTrue($greatGrandChild->hasSiblings());
    }

    /**
     * Tests private method _getWhereClause()
     */
    public function testGetWhereClause(): void
    {
        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $method = new ReflectionMethod(Node::class, 'getWhereClause');

        // Vanilla case
        $node = new Node('default');
        $this->assertEquals(
            'WHERE TRUE ',
            $method->invoke($node, 'SCHEMA_NAME'),
        );

        // When a schema names is passed as search clause
        $this->assertEquals(
            "WHERE TRUE AND `SCHEMA_NAME` LIKE '%schemaName%' ",
            $method->invoke($node, 'SCHEMA_NAME', 'schemaName'),
        );

        if (! isset($GLOBALS['cfg']['Server'])) {
            $GLOBALS['cfg']['Server'] = [];
        }

        // When hide_db regular expression is present
        $GLOBALS['cfg']['Server']['hide_db'] = 'regexpHideDb';
        $this->assertEquals(
            "WHERE TRUE AND `SCHEMA_NAME` NOT REGEXP 'regexpHideDb' ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['hide_db']);

        // When only_db directive is present and it's a single db
        $GLOBALS['cfg']['Server']['only_db'] = 'stringOnlyDb';
        $this->assertEquals(
            "WHERE TRUE AND ( `SCHEMA_NAME` LIKE 'stringOnlyDb' ) ",
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['only_db']);

        // When only_db directive is present and it's an array of dbs
        $GLOBALS['cfg']['Server']['only_db'] = ['onlyDbOne', 'onlyDbTwo'];
        $this->assertEquals(
            'WHERE TRUE AND ( `SCHEMA_NAME` LIKE \'onlyDbOne\' OR `SCHEMA_NAME` LIKE \'onlyDbTwo\' ) ',
            $method->invoke($node, 'SCHEMA_NAME'),
        );
        unset($GLOBALS['cfg']['Server']['only_db']);
    }

    /**
     * Tests getData() method when DisableIS is false and navigation tree
     * grouping enabled.
     */
    public function testGetDataWithEnabledISAndGroupingEnabled(): void
    {
        $pos = 10;
        $limit = 20;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = $limit;
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
        $expectedSql .= 'LIMIT ' . $pos . ', ' . $limit;
        $expectedSql .= ') t2 ';
        $expectedSql .= "WHERE TRUE AND 1 = LOCATE(CONCAT(DB_first_level, '_'), ";
        $expectedSql .= "CONCAT(SCHEMA_NAME, '_')) ";
        $expectedSql .= 'ORDER BY SCHEMA_NAME ASC';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        // It would have been better to mock _getWhereClause method
        // but strangely, mocking private methods is not supported in PHPUnit
        $node = new Node('default');

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;
        $node->getData($relationParameters, '', $pos);
    }

    /**
     * Tests getData() method when DisableIS is false and navigation tree
     * grouping disabled.
     */
    public function testGetDataWithEnabledISAndGroupingDisabled(): void
    {
        $pos = 10;
        $limit = 20;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = false;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = $limit;

        $expectedSql = 'SELECT `SCHEMA_NAME` ';
        $expectedSql .= 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` ';
        $expectedSql .= 'WHERE TRUE ';
        $expectedSql .= 'ORDER BY `SCHEMA_NAME` ';
        $expectedSql .= 'LIMIT ' . $pos . ', ' . $limit;

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        // It would have been better to mock _getWhereClause method
        // but strangely, mocking private methods is not supported in PHPUnit
        $node = new Node('default');

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getData($relationParameters, '', $pos);
    }

    /**
     * Tests getData() method when DisableIS is true and navigation tree
     * grouping enabled.
     */
    public function testGetDataWithDisabledISAndGroupingEnabled(): void
    {
        $pos = 0;
        $limit = 10;
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['dbs_to_test'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;
        $GLOBALS['cfg']['FirstLevelNavigationItems'] = $limit;
        $GLOBALS['cfg']['NavigationTreeDbSeparator'] = '_';

        $relationParameters = RelationParameters::fromArray([
            'db' => 'pmadb',
            'navwork' => true,
            'navigationhiding' => 'navigationhiding',
        ]);

        $node = new Node('default');

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
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
        $node->getData($relationParameters, '', $pos, 'db');
    }

    /**
     * Tests the getPresence method when DisableIS is false and navigation tree
     * grouping enabled.
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

        // It would have been better to mock _getWhereClause method
        // but strangely, mocking private methods is not supported in PHPUnit
        $node = new Node('default');

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query);
        $GLOBALS['dbi'] = $dbi;
        $node->getPresence();
    }

    /**
     * Tests the getPresence method when DisableIS is false and navigation tree
     * grouping disabled.
     */
    public function testGetPresenceWithEnabledISAndGroupingDisabled(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = false;

        $query = 'SELECT COUNT(*) ';
        $query .= 'FROM INFORMATION_SCHEMA.SCHEMATA ';
        $query .= 'WHERE TRUE ';

        $node = new Node('default');
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with($query);
        $GLOBALS['dbi'] = $dbi;
        $node->getPresence();
    }

    /**
     * Tests the getPresence method when DisableIS is true
     */
    public function testGetPresenceWithDisabledIS(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['dbs_to_test'] = false;
        $GLOBALS['cfg']['NavigationTreeEnableGrouping'] = true;

        $node = new Node('default');

        $resultStub = $this->createMock(DummyResult::class);

        // test with no search clause
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SHOW DATABASES WHERE TRUE ')
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getPresence();

        // test with a search clause
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%dbname%' ")
            ->will($this->returnValue($resultStub));
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getPresence('', 'dbname');
    }
}
