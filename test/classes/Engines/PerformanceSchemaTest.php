<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\PerformanceSchema;
use PhpMyAdmin\Tests\AbstractTestCase;

class PerformanceSchemaTest extends AbstractTestCase
{
    /** @var PerformanceSchema */
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
        $this->object = new PerformanceSchema('PERFORMANCE_SCHEMA');
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
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        $this->assertEquals(
            $this->object->getMysqlHelpPage(),
            'performance-schema'
        );
    }
}
