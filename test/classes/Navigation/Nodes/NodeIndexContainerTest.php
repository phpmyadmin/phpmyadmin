<?php
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeIndexContainer class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeIndexContainer class
 */
class NodeIndexContainerTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 0;
    }

    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance('NodeIndexContainer');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertStringContainsString(
            'index.php?route=/table/structure',
            $parent->links['text']
        );
        $this->assertEquals('indexes', $parent->realName);
    }
}
