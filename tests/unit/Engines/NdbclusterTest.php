<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Engines\Ndbcluster;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Ndbcluster::class)]
class NdbclusterTest extends AbstractTestCase
{
    protected Ndbcluster $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        $this->object = new Ndbcluster('nbdcluster');
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
     * Test for getVariables
     */
    public function testGetVariables(): void
    {
        self::assertSame(
            $this->object->getVariables(),
            ['ndb_connectstring' => []],
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(): void
    {
        self::assertSame(
            $this->object->getVariablesLikePattern(),
            'ndb\\_%',
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        self::assertSame(
            $this->object->getMysqlHelpPage(),
            'ndbcluster',
        );
    }
}
