<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeIndexContainer class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeIndexContainer class
 *
 * @package PhpMyAdmin-test
 */
class NodeIndexContainerTest extends PmaTestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
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
        $this->assertContains(
            'tbl_structure.php',
            $parent->links['text']
        );
        $this->assertEquals('indexes', $parent->real_name);
    }
}
