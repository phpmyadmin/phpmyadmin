<?php
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumnContainer class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumnContainer class
 */
class NodeColumnContainerTest extends AbstractTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for PhpMyAdmin\Navigation\NodeFactory::__construct
     */
    public function testConstructor(): void
    {
        $parent = NodeFactory::getInstance('NodeColumnContainer');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'index.php?route=/table/structure',
            $parent->links['text']
        );
        $this->assertEquals('columns', $parent->realName);
    }
}
