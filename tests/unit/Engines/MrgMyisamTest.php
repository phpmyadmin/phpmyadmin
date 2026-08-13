<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Engines\MrgMyisam;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MrgMyisam::class)]
class MrgMyisamTest extends AbstractTestCase
{
    protected MrgMyisam $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $this->object = new MrgMyisam('mrg_myisam');
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        self::assertSame(
            'merge-storage-engine',
            $this->object->getMysqlHelpPage(),
        );
    }
}
