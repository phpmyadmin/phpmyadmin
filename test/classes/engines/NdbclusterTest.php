<?php
/**
 * Tests for PMA_StorageEngine_ndbcluster
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\engines\Ndbcluster;

require_once 'libraries/database_interface.inc.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for PMA\libraries\engines\Ndbcluster
 *
 * @package PhpMyAdmin-test
 */
class NdbclusterTest extends PMATestCase
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
        $GLOBALS['server'] = 0;
        $this->object = new Ndbcluster('nbdcluster');
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
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function testGetMysqlHelpPage()
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'ndbcluster'
        );

    }
}
