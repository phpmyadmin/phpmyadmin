<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumn class
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Theme;

require_once 'test/PMATestCase.php';

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeColumn class
 *
 * @package PhpMyAdmin-test
 */
class NodeColumnTest extends PMATestCase
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
     * Test for PhpMyAdmin\Navigation\NodeFactory::getInstance
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = NodeFactory::getInstance('NodeColumn');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'tbl_structure.php',
            $parent->links['text']
        );
    }
}
