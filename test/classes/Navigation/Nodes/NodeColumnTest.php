<?php
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumn class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumn class
 */
class NodeColumnTest extends AbstractTestCase
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
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance(
            'NodeColumn',
            [
                'name' => 'name',
                'key' => 'key',
            ]
        );
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'index.php?route=/table/structure',
            $parent->links['text']
        );
    }
}
