<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Engines\MrgMyisam;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Engines\MrgMyisam
 */
class MrgMyisamTest extends AbstractTestCase
{
    /** @var MrgMyisam */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 0;
        $this->object = new MrgMyisam('mrg_myisam');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
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
        self::assertEquals($this->object->getMysqlHelpPage(), 'merge-storage-engine');
    }
}
