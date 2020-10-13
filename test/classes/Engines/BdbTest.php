<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\Bdb;
use PhpMyAdmin\Tests\AbstractTestCase;

class BdbTest extends AbstractTestCase
{
    /** @var Bdb */
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
        $this->object = new Bdb('bdb');
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
                'version_bdb' => [
                    'title' => __('Version information'),
                ],
                'bdb_cache_size' => ['type' => 1],
                'bdb_home' => [],
                'bdb_log_buffer_size' => ['type' => 1],
                'bdb_logdir' => [],
                'bdb_max_lock' => ['type' => 2],
                'bdb_shared_data' => [],
                'bdb_tmpdir' => [],
                'bdb_data_direct' => [],
                'bdb_lock_detect' => [],
                'bdb_log_direct' => [],
                'bdb_no_recover' => [],
                'bdb_no_sync' => [],
                'skip_sync_bdb_logs' => [],
                'sync_bdb_logs' => [],
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
            '%bdb%'
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'bdb'
        );
    }
}
