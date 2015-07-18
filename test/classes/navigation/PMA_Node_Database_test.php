<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Node_Database class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for Node_Database class
 *
 * @package PhpMyAdmin-test
 */
class Node_Database_Test extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp for test cases
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['MaxNavigationItems'] = 250;
        $GLOBALS['cfg']['Server'] = array();
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['cfg']['DBG'] = array();
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
    }

    /**
     * Test for __construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Database');
        $this->assertArrayHasKey(
            'text',
            $parent->links
        );
        $this->assertContains(
            'db_structure.php',
            $parent->links['text']
        );
        $this->assertContains('database', $parent->classes);
    }

    /**
     * Test for getPresence
     *
     * @return void
     */
    public function testGetPresence()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Database');
        $this->assertEquals(
            2,
            $parent->getPresence('tables')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('views')
        );
        $this->assertEquals(
            1,
            $parent->getPresence('functions')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('procedures')
        );
        $this->assertEquals(
            0,
            $parent->getPresence('events')
        );
    }

    /**
     * Test for getData
     *
     * @return void
     */
    public function testGetData()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Database');

        $tables = $parent->getData('tables', 0);
        $this->assertContains(
            'test1',
            $tables
        );
        $this->assertContains(
            'test2',
            $tables
        );

        $views = $parent->getData('views', 0);
        $this->assertEmpty($views);

        $functions = $parent->getData('functions', 0);
        $this->assertContains(
            'testFunction',
            $functions
        );
        $this->assertEquals(
            1,
            count($functions)
        );

        $this->assertEmpty($parent->getData('procedures', 0));
        $this->assertEmpty($parent->getData('events', 0));
    }

    /**
     * Test for setHiddenCount and getHiddenCount
     *
     * @return void
     */
    public function testHiddenCount()
    {
        $parent = PMA_NodeFactory::getInstance('Node_Database');

        $parent->setHiddenCount(3);
        $this->assertEquals(
            3,
            $parent->getHiddenCount()
        );
    }
}
