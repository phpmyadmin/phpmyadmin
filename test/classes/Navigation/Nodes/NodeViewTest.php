<?php
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeView class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;
use function is_string;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeView class
 */
class NodeViewTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance('NodeView');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'index.php?route=/sql',
            $parent->links['text']
        );
        if (is_string($parent->icon)) {
            $this->assertStringContainsString('b_props', $parent->icon);
        }
        $this->assertStringContainsString('view', $parent->classes);
    }
}
