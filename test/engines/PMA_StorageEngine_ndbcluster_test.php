<?php
/**
 * Tests for PMA_StorageEngine_ndbcluster
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/StorageEngine.class.php';
require_once 'libraries/engines/ndbcluster.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';

class PMA_StorageEngine_ndbcluster_test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_StorageEngine_ndbcluster('nbdcluster');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables()
    {
        $this->assertEquals(
            $this->object->getVariables(),
            array(
                'ndb_connectstring' => array(
                ),
            )
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern()
    {
        $this->assertEquals(
            $this->object->getVariablesLikePattern(),
            'ndb\\_%'
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage()
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'ndbcluster'
        );

    }
}
