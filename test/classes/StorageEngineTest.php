<?php
/**
 * Tests for StorageEngine.php
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\StorageEngine;
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
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for StorageEngine.php
 */
class StorageEngineTest extends PmaTestCase
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
        unset($this->object);
    }

    /**
     * Test for getStorageEngines
     *
     * @return void
     */
    public function testGetStorageEngines()
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
     * @dataProvider providerGetEngine
     */
    public function testGetEngine($expectedClass, $engineName): void
    {
        $this->assertInstanceOf(
            $expectedClass,
            StorageEngine::getEngine($engineName)
        );
    }

    /**
     * Provider for testGetEngine
     *
     * @return array
     */
    public function providerGetEngine()
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
     *
     * @return void
     */
    public function testIsValid()
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
     *
     * @return void
     */
    public function testGetPage()
    {
        $this->assertEquals(
            '',
            $this->object->getPage('Foo')
        );
    }

    /**
     * Test for getInfoPages
     *
     * @return void
     */
    public function testGetInfoPages()
    {
        $this->assertEquals(
            [],
            $this->object->getInfoPages()
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
            '',
            $this->object->getVariablesLikePattern()
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
            'dummy-storage-engine',
            $this->object->getMysqlHelpPage()
        );
    }

    /**
     * Test for getVariables
     *
     * @return void
     */
    public function testGetVariables()
    {
        $this->assertEquals(
            [],
            $this->object->getVariables()
        );
    }

    /**
     * Test for getSupportInformationMessage
     *
     * @return void
     */
    public function testGetSupportInformationMessage()
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
     *
     * @return void
     */
    public function testGetComment()
    {
        $this->assertEquals(
            'dummy comment',
            $this->object->getComment()
        );
    }

    /**
     * Test for getTitle
     *
     * @return void
     */
    public function testGetTitle()
    {
        $this->assertEquals(
            'dummy',
            $this->object->getTitle()
        );
    }

    /**
     * Test for resolveTypeSize
     *
     * @return void
     */
    public function testResolveTypeSize()
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
