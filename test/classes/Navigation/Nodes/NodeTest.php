<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionMethod;

class NodeTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * SetUp for AddNode
     */
    public function testAddNode(): void
    {
        $parent = NodeFactory::getInstance('Node', 'parent');
        $child = NodeFactory::getInstance('Node', 'child');
        $parent->addChild($child);
        $this->assertEquals(
            $parent->getChild($child->name),
            $child
        );
        $this->assertEquals(
            $parent->getChild($child->realName, true),
            $child
        );
    }

    /**
     * SetUp for getChild
     */
    public function testGetChildError(): void
    {
        $parent = NodeFactory::getInstance('Node', 'parent');
        $this->assertNull(
            $parent->getChild('foo')
        );
        $this->assertNull(
            $parent->getChild('foo', true)
        );
    }

    /**
     * SetUp for getChild
     */
    public function testRemoveNode(): void
    {
        $parent = NodeFactory::getInstance('Node', 'parent');
        $child = NodeFactory::getInstance('Node', 'child');
        $parent->addChild($child);
        $this->assertEquals(
            $parent->getChild($child->name),
            $child
        );
        $parent->removeChild($child->name);
        $this->assertNull(
            $parent->getChild($child->name)
        );
    }

    /**
     * SetUp for hasChildren
     */
    public function testNodeHasChildren(): void
    {
        $parent = NodeFactory::getInstance();
        $emptyContainer = NodeFactory::getInstance(
            'Node',
            'empty',
            Node::CONTAINER
        );
        $child = NodeFactory::getInstance();
        // test with no children
        $this->assertEquals(
            $parent->hasChildren(true),
            false
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false
        );
        // test with an empty container
        $parent->addChild($emptyContainer);
        $this->assertEquals(
            $parent->hasChildren(true),
            true
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            false
        );
        // test with a real child
        $parent->addChild($child);
        $this->assertEquals(
            $parent->hasChildren(true),
            true
        );
        $this->assertEquals(
            $parent->hasChildren(false),
            true
        );
    }

    /**
     * SetUp for numChildren
     */
    public function testNumChildren(): void
    {
        // start with root node only
        $parent = NodeFactory::getInstance();
        $this->assertEquals($parent->numChildren(), 0);
        // add a child
        $child = NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals($parent->numChildren(), 1);
        // add a direct grandchild, this one doesn't count as
        // it's not enclosed in a CONTAINER
        $child->addChild(NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 1);
        // add a container, this one doesn't count wither
        $container = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER
        );
        $parent->addChild($container);
        $this->assertEquals($parent->numChildren(), 1);
        // add a grandchild to container, this one counts
        $container->addChild(NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 2);
        // add another grandchild to container, this one counts
        $container->addChild(NodeFactory::getInstance());
        $this->assertEquals($parent->numChildren(), 3);
    }

    /**
     * SetUp for parents
     */
    public function testParents(): void
    {
        $parent = NodeFactory::getInstance();
        $this->assertEquals($parent->parents(), []); // exclude self
        $this->assertEquals($parent->parents(true), [$parent]); // include self

        $child = NodeFactory::getInstance();
        $parent->addChild($child);

        $this->assertEquals($child->parents(), [$parent]); // exclude self
        $this->assertEquals(
            $child->parents(true),
            [
                $child,
                $parent,
            ]
        ); // include self
    }

    /**
     * SetUp for realParent
     */
    public function testRealParent(): void
    {
        $parent = NodeFactory::getInstance();
        $this->assertFalse($parent->realParent());

        $child = NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertEquals($child->realParent(), $parent);
    }

    /**
     * Tests whether Node->hasSiblings() method returns false
     * when the node does not have any siblings.
     */
    public function testHasSiblingsWithNoSiblings(): void
    {
        $parent = NodeFactory::getInstance();
        $child = NodeFactory::getInstance();
        $parent->addChild($child);
        $this->assertFalse($child->hasSiblings());
    }

    /**
     * Tests whether Node->hasSiblings() method returns true
     * when it actually has siblings.
     */
    public function testHasSiblingsWithSiblings(): void
    {
        $parent = NodeFactory::getInstance();
        $firstChild = NodeFactory::getInstance();
        $parent->addChild($firstChild);
        $secondChild = NodeFactory::getInstance();
        $parent->addChild($secondChild);
        // Normal case; two Node:NODE type siblings
        $this->assertTrue($firstChild->hasSiblings());

        $parent = NodeFactory::getInstance();
        $firstChild = NodeFactory::getInstance();
        $parent->addChild($firstChild);
        $secondChild = NodeFactory::getInstance(
            'Node',
            'default',
            Node::CONTAINER
        );
        $parent->addChild($secondChild);
        // Empty Node::CONTAINER type node should not be considered in hasSiblings()
        $this->assertFalse($firstChild->hasSiblings());

        $grandChild = NodeFactory::getInstance();
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
        $parent = NodeFactory::getInstance();
        $child = NodeFactory::getInstance();
        $parent->addChild($child);
        $grandChild = NodeFactory::getInstance();
        $child->addChild($grandChild);
        $greatGrandChild = NodeFactory::getInstance();
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
        $method = new ReflectionMethod(
            Node::class,
            'getWhereClause'
        );
        $method->setAccessible(true);

        // Vanilla case
        $node = NodeFactory::getInstance();
        $this->assertEquals(
            'WHERE TRUE ',
            $method->invoke($node, 'SCHEMA_NAME')
        );

        // When a schema names is passed as search clause
        $this->assertEquals(
            "WHERE TRUE AND `SCHEMA_NAME` LIKE '%schemaName%' ",
            $method->invoke($node, 'SCHEMA_NAME', 'schemaName')
        );

        if (! isset($GLOBALS['cfg']['Server'])) {
            $GLOBALS['cfg']['Server'] = [];
        }

        // When hide_db regular expression is present
        $GLOBALS['cfg']['Server']['hide_db'] = 'regexpHideDb';
        $this->assertEquals(
            "WHERE TRUE AND `SCHEMA_NAME` NOT REGEXP 'regexpHideDb' ",
            $method->invoke($node, 'SCHEMA_NAME')
        );
        unset($GLOBALS['cfg']['Server']['hide_db']);

        // When only_db directive is present and it's a single db
        $GLOBALS['cfg']['Server']['only_db'] = 'stringOnlyDb';
        $this->assertEquals(
            "WHERE TRUE AND ( `SCHEMA_NAME` LIKE 'stringOnlyDb' ) ",
            $method->invoke($node, 'SCHEMA_NAME')
        );
        unset($GLOBALS['cfg']['Server']['only_db']);

        // When only_db directive is present and it's an array of dbs
        $GLOBALS['cfg']['Server']['only_db'] = [
            'onlyDbOne',
            'onlyDbTwo',
        ];
        $this->assertEquals(
            "WHERE TRUE AND ( `SCHEMA_NAME` LIKE 'onlyDbOne' "
            . "OR `SCHEMA_NAME` LIKE 'onlyDbTwo' ) ",
            $method->invoke($node, 'SCHEMA_NAME')
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

        $expectedSql  = 'SELECT `SCHEMA_NAME` ';
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

        // It would have been better to mock _getWhereClause method
        // but strangely, mocking private methods is not supported in PHPUnit
        $node = NodeFactory::getInstance();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));
        $GLOBALS['dbi'] = $dbi;
        $node->getData('', $pos);
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

        $expectedSql  = 'SELECT `SCHEMA_NAME` ';
        $expectedSql .= 'FROM `INFORMATION_SCHEMA`.`SCHEMATA` ';
        $expectedSql .= 'WHERE TRUE ';
        $expectedSql .= 'ORDER BY `SCHEMA_NAME` ';
        $expectedSql .= 'LIMIT ' . $pos . ', ' . $limit;

        // It would have been better to mock _getWhereClause method
        // but strangely, mocking private methods is not supported in PHPUnit
        $node = NodeFactory::getInstance();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with($expectedSql);
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getData('', $pos);
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

        $node = NodeFactory::getInstance();

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' ")
            ->will($this->returnValue(true));
        $dbi->expects($this->exactly(3))
            ->method('fetchArray')
            ->willReturnOnConsecutiveCalls(
                ['0' => 'db'],
                ['0' => 'aa_db'],
                null
            );

        $dbi->expects($this->once())
            ->method('fetchResult')
            ->with(
                "SHOW DATABASES WHERE TRUE AND `Database` LIKE '%db%' AND ("
                . " LOCATE('db_', CONCAT(`Database`, '_')) = 1"
                . " OR LOCATE('aa_', CONCAT(`Database`, '_')) = 1 )"
            );
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getData('', $pos, 'db');
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
        $node = NodeFactory::getInstance();

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

        $node = NodeFactory::getInstance();
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

        $node = NodeFactory::getInstance();

        // test with no search clause
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SHOW DATABASES WHERE TRUE ');
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
            ->with("SHOW DATABASES WHERE TRUE AND `Database` LIKE '%dbname%' ");
        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
        $node->getPresence('', 'dbname');
    }
}
