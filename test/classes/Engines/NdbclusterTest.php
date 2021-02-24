<?php
/**
 * Tests for PMA_StorageEngine_ndbcluster
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\Ndbcluster;
use PhpMyAdmin\Tests\AbstractTestCase;

class NdbclusterTest extends AbstractTestCase
{
    /** @var Ndbcluster */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $this->object = new Ndbcluster('nbdcluster');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables(): void
    {
        $this->assertEquals(
            $this->object->getVariables(),
            [
                'ndb_connectstring' => [],
            ]
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(): void
    {
        $this->assertEquals(
            $this->object->getVariablesLikePattern(),
            'ndb\\_%'
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'ndbcluster'
        );
    }
}
