<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeFunction class
 *
 * @package PhpMyAdmin-test
 */

use PhpMyAdmin\Navigation\NodeFactory;
use PhpMyAdmin\Theme;

require_once 'test/PMATestCase.php';

/**
 * Tests for PhpMyAdmin\Navigation\Nodes\NodeFunction class
 *
 * @package PhpMyAdmin-test
 */
class NodeFunctionTest extends PMATestCase
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
        $parent = NodeFactory::getInstance('NodeFunction');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'db_routines.php',
            $parent->links['text']
        );
    }
}
