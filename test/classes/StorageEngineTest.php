<?php
/**
 * Tests for StorageEngine.php
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Engines\Bdb;
use PhpMyAdmin\Engines\Berkeleydb;
use PhpMyAdmin\Engines\Binlog;
use PhpMyAdmin\Engines\Innobase;
use PhpMyAdmin\Engines\Innodb;
use PhpMyAdmin\Engines\Memory;
use PhpMyAdmin\Engines\Merge;
use PhpMyAdmin\Engines\MrgMyisam;
use PhpMyAdmin\Engines\Myisam;
use PhpMyAdmin\Engines\Ndbcluster;
use PhpMyAdmin\Engines\Pbxt;
use PhpMyAdmin\Engines\PerformanceSchema;
use PhpMyAdmin\StorageEngine;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for StorageEngine.php
 */
class StorageEngineTest extends AbstractTestCase
{
    /** @var StorageEngine|MockObject */
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
        $GLOBALS['server'] = 1;
        $this->object = $this->getMockForAbstractClass(
            StorageEngine::class,
            ['dummy']
        );
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
     * Test for getStorageEngines
     */
    public function testGetStorageEngines(): void
    {
        $this->assertEquals(
            [
                'dummy' => [
                    'Engine' => 'dummy',
                    'Support' => 'YES',
                    'Comment' => 'dummy comment',
                ],
                'dummy2' => [
                    'Engine' => 'dummy2',
                    'Support' => 'NO',
                    'Comment' => 'dummy2 comment',
                ],
                'FEDERATED' => [
                    'Engine' => 'FEDERATED',
                    'Support' => 'NO',
                    'Comment' => 'Federated MySQL storage engine',
                ],
                'Pbxt' => [
                    'Engine'  => 'Pbxt',
                    'Support' => 'NO',
                    'Comment' => 'Pbxt storage engine',
                ],
            ],
            $this->object->getStorageEngines()
        );
    }

    public function testGetArray(): void
    {
        $actual = $this->object->getArray();

        $this->assertEquals(
            [
                'dummy' => [
                    'name' => 'dummy',
                    'comment' => 'dummy comment',
                    'is_default' => false,
                ],
            ],
            $actual
        );
    }

    /**
     * Test for StorageEngine::getEngine
     *
     * @param string $expectedClass Class that should be selected
     * @param string $engineName    Engine name
     *
     * @psalm-param class-string $expectedClass
     *
     * @dataProvider providerGetEngine
     */
    public function testGetEngine(string $expectedClass, string $engineName): void
    {
        $actual = StorageEngine::getEngine($engineName);
        $this->assertInstanceOf($expectedClass, $actual);
    }

    /**
     * Provider for testGetEngine
     *
     * @return array
     */
    public function providerGetEngine(): array
    {
        return [
            [
                StorageEngine::class,
                'unknown engine',
            ],
            [
                Bdb::class,
                'Bdb',
            ],
            [
                Berkeleydb::class,
                'Berkeleydb',
            ],
            [
                Binlog::class,
                'Binlog',
            ],
            [
                Innobase::class,
                'Innobase',
            ],
            [
                Innodb::class,
                'Innodb',
            ],
            [
                Memory::class,
                'Memory',
            ],
            [
                Merge::class,
                'Merge',
            ],
            [
                MrgMyisam::class,
                'Mrg_Myisam',
            ],
            [
                Myisam::class,
                'Myisam',
            ],
            [
                Ndbcluster::class,
                'Ndbcluster',
            ],
            [
                Pbxt::class,
                'Pbxt',
            ],
            [
                PerformanceSchema::class,
                'Performance_Schema',
            ],
        ];
    }

    /**
     * Test for isValid
     */
    public function testIsValid(): void
    {
        $this->assertTrue(
            $this->object->isValid('PBMS')
        );
        $this->assertTrue(
            $this->object->isValid('dummy')
        );
        $this->assertTrue(
            $this->object->isValid('dummy2')
        );
        $this->assertFalse(
            $this->object->isValid('invalid')
        );
    }

    /**
     * Test for getPage
     */
    public function testGetPage(): void
    {
        $this->assertEquals(
            '',
            $this->object->getPage('Foo')
        );
    }

    /**
     * Test for getInfoPages
     */
    public function testGetInfoPages(): void
    {
        $this->assertEquals(
            [],
            $this->object->getInfoPages()
        );
    }

    /**
     * Test for getVariablesLikePattern
     */
    public function testGetVariablesLikePattern(): void
    {
        $this->assertEquals(
            '',
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getMysqlHelpPage
     */
    public function testGetMysqlHelpPage(): void
    {
        $this->assertEquals(
            'dummy-storage-engine',
            $this->object->getMysqlHelpPage()
        );
    }

    /**
     * Test for getVariables
     */
    public function testGetVariables(): void
    {
        $this->assertEquals(
            [],
            $this->object->getVariables()
        );
    }

    /**
     * Test for getSupportInformationMessage
     */
    public function testGetSupportInformationMessage(): void
    {
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 1;
        $this->assertEquals(
            'dummy has been disabled for this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 2;
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 3;
        $this->assertEquals(
            'dummy is the default storage engine on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );
    }

    /**
     * Test for getComment
     */
    public function testGetComment(): void
    {
        $this->assertEquals(
            'dummy comment',
            $this->object->getComment()
        );
    }

    /**
     * Test for getTitle
     */
    public function testGetTitle(): void
    {
        $this->assertEquals(
            'dummy',
            $this->object->getTitle()
        );
    }

    /**
     * Test for resolveTypeSize
     */
    public function testResolveTypeSize(): void
    {
        $this->assertEquals(
            [
                0 => 12,
                1 => 'B',
            ],
            $this->object->resolveTypeSize(12)
        );
    }
}
