<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
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

use function json_encode;

/**
 * @covers \PhpMyAdmin\StorageEngine
 */
class StorageEngineTest extends AbstractTestCase
{
    /** @var StorageEngine|MockObject */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
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
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
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
                    'Engine' => 'Pbxt',
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

    public function testHasMroongaEngine(): void
    {
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_list\');', [
            [
                (string) json_encode([]), // Fake result
            ],
        ]);
        $this->assertTrue(StorageEngine::hasMroongaEngine());
        $this->assertTrue(StorageEngine::hasMroongaEngine()); // Does not call any query

        Cache::remove('storage-engine.mroonga.has.mroonga_command'); // Cache clear

        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_list\');', false);
        $this->assertFalse(StorageEngine::hasMroongaEngine());

        $this->assertAllQueriesConsumed();
    }

    public function testGetMroongaLengths(): void
    {
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_list\');', [
            [
                // Partial
                (string) json_encode([
                    'WGS84GeoPoint' => [
                        'id' => 18,
                        'name' => 'WGS84GeoPoint',
                        'opened' => true,
                        'n_elements' => 4,
                        'type' => [
                            'id' => 32,
                            'name' => 'type',
                        ],
                        'flags' => [
                            'value' => 24,
                            'names' => 'KEY_GEO_POINT',
                        ],
                        'path' => null,
                        'size' => 8,
                    ],
                    'mroonga_operations' => [
                        'type' => [
                            'id' => 51,
                            'name' => 'table:no_key',
                        ],
                    ],
                    'mroonga_operations.type' => [
                        'type' => [
                            'id' => 65,
                            'name' => 'column:var_size',
                        ],
                    ],
                    'mroonga_operations.table' => [
                        'type' => [
                            'id' => 65,
                            'name' => 'column:var_size',
                        ],
                    ],
                    'mroonga_operations.record' => [
                        'type' => [
                            'id' => 64,
                            'name' => 'column:fix_size',
                        ],
                    ],
                    'idx_correo' => [
                        'type' => [
                            'id' => 49,
                            'name' => 'table:pat_key',
                        ],
                    ],
                    'idx_correo.id' => [
                        'type' => [
                            'id' => 64,
                            'name' => 'column:fix_size',
                        ],
                    ],
                    'idx_correo.search' => [
                        'type' => [
                            'id' => 65,
                            'name' => 'column:var_size',
                        ],
                    ],
                    'idx_correo#idx_correo_search' => [
                        'type' => [
                            'id' => 49,
                            'name' => 'table:pat_key',
                        ],
                    ],
                    'idx_correo#idx_correo_search.index' => [
                        'type' => [
                            'id' => 72,
                            'name' => 'column:index',
                        ],
                    ],
                ]),
            ],
        ]);
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_inspect idx_correo\');', [
            [
                // Partial
                (string) json_encode([
                    'id' => 265,
                    'name' => 'idx_correo',
                    'type' => [
                        'id' => 49,
                        'name' => 'table:pat_key',
                    ],
                    'key' => [
                        'type' => [
                            'id' => 8,
                            'name' => 'Int32',
                            'type' => [
                                'id' => 32,
                                'name' => 'type',
                            ],
                            'size' => 4,
                        ],
                        'total_size' => 0,
                        'max_total_size' => 4294967294,
                    ],
                    'value' => ['type' => null],
                    'n_records' => 0,
                    'disk_usage' => 4243456,
                ]),
            ],
        ]);
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_inspect idx_correo.id\');', [
            [
                // Full object
                (string) json_encode([
                    'id' => 266,
                    'name' => 'id',
                    'table' => [
                        'id' => 265,
                        'name' => 'idx_correo',
                        'type' => [
                            'id' => 49,
                            'name' => 'table:pat_key',
                        ],
                        'key' => [
                            'type' => [
                                'id' => 8,
                                'name' => 'Int32',
                                'type' => [
                                    'id' => 32,
                                    'name' => 'type',
                                ],
                                'size' => 4,
                            ],
                            'total_size' => 0,
                            'max_total_size' => 4294967294,
                        ],
                        'value' => ['type' => null],
                        'n_records' => 0,
                        'disk_usage' => 4243456,
                    ],
                    'full_name' => 'idx_correo.id',
                    'type' => [
                        'name' => 'scalar',
                        'raw' => [
                            'id' => 64,
                            'name' => 'column:fix_size',
                        ],
                    ],
                    'value' => [
                        'type' => [
                            'id' => 8,
                            'name' => 'Int32',
                            'type' => [
                                'id' => 32,
                                'name' => 'type',
                            ],
                            'size' => 4,
                        ],
                        'compress' => null,
                    ],
                    'disk_usage' => 4096,
                ]),
            ],
        ]);
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_inspect idx_correo.search\');', [
            [
                // Full object
                (string) json_encode([
                    'id' => 267,
                    'name' => 'search',
                    'table' => [
                        'id' => 265,
                        'name' => 'idx_correo',
                        'type' => [
                            'id' => 49,
                            'name' => 'table:pat_key',
                        ],
                        'key' => [
                            'type' => [
                                'id' => 8,
                                'name' => 'Int32',
                                'type' => [
                                    'id' => 32,
                                    'name' => 'type',
                                ],
                                'size' => 4,
                            ],
                            'total_size' => 0,
                            'max_total_size' => 4294967294,
                        ],
                        'value' => ['type' => null],
                        'n_records' => 0,
                        'disk_usage' => 4243456,
                    ],
                    'full_name' => 'idx_correo.search',
                    'type' => [
                        'name' => 'scalar',
                        'raw' => [
                            'id' => 65,
                            'name' => 'column:var_size',
                        ],
                    ],
                    'value' => [
                        'type' => [
                            'id' => 16,
                            'name' => 'LongText',
                            'type' => [
                                'id' => 32,
                                'name' => 'type',
                            ],
                            'size' => 2147483648,
                        ],
                        'compress' => null,
                    ],
                    'disk_usage' => 274432,
                ]),
            ],
        ]);
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_inspect idx_correo#idx_correo_search\');', [
            [
                // Partial
                (string) json_encode([
                    'id' => 268,
                    'name' => 'idx_correo#idx_correo_search',
                    'type' => [
                        'id' => 49,
                        'name' => 'table:pat_key',
                    ],
                    'key' => [
                        'type' => [
                            'id' => 14,
                            'name' => 'ShortText',
                            'type' => [
                                'id' => 32,
                                'name' => 'type',
                            ],
                            'size' => 4096,
                        ],
                        'total_size' => 0,
                        'max_total_size' => 4294967294,
                    ],
                    'value' => ['type' => null],
                    'n_records' => 0,
                    'disk_usage' => 12878,
                ]),
            ],
        ]);
        $this->dummyDbi->addResult('SELECT mroonga_command(\'object_inspect idx_correo#idx_correo_search.index\');', [
            [
                // Full object
                (string) json_encode([
                    'id' => 269,
                    'name' => 'index',
                    'table' => [
                        'id' => 268,
                        'name' => 'idx_correo#idx_correo_search',
                        'type' => [
                            'id' => 49,
                            'name' => 'table:pat_key',
                        ],
                        'key' => [
                            'type' => [
                                'id' => 14,
                                'name' => 'ShortText',
                                'type' => [
                                    'id' => 32,
                                    'name' => 'type',
                                ],
                                'size' => 4096,
                            ],
                            'total_size' => 0,
                            'max_total_size' => 4294967294,
                        ],
                        'value' => ['type' => null],
                        'n_records' => 0,
                        'disk_usage' => 4243456,
                    ],
                    'full_name' => 'idx_correo#idx_correo_search.index',
                    'type' => [
                        'name' => 'index',
                        'raw' => [
                            'id' => 72,
                            'name' => 'column:index',
                        ],
                    ],
                    'value' => [
                        'type' => [
                            'id' => 265,
                            'name' => 'idx_correo',
                            'type' => [
                                'id' => 49,
                                'name' => 'table:pat_key',
                            ],
                            'size' => 4,
                        ],
                        'section' => false,
                        'weight' => false,
                        'position' => true,
                        'size' => 'normal',
                        'statistics' => [
                            'max_section_id' => 0,
                            'n_garbage_segments' => 0,
                            'max_array_segment_id' => 0,
                            'n_array_segments' => 0,
                            'max_buffer_segment_id' => 0,
                            'n_buffer_segments' => 0,
                            'max_in_use_physical_segment_id' => 0,
                            'n_unmanaged_segments' => 0,
                            'total_chunk_size' => 0,
                            'max_in_use_chunk_id' => 0,
                            'n_garbage_chunks' => [
                                0 => 0,
                                1 => 0,
                                2 => 0,
                                3 => 0,
                                4 => 0,
                                5 => 0,
                                6 => 0,
                                7 => 0,
                                8 => 0,
                                9 => 0,
                                10 => 0,
                                11 => 0,
                                12 => 0,
                                13 => 0,
                                14 => 0,
                            ],
                        ],
                    ],
                    'sources' => [
                        0 => [
                            'id' => 267,
                            'name' => 'search',
                            'table' => [
                                'id' => 265,
                                'name' => 'idx_correo',
                                'type' => [
                                    'id' => 49,
                                    'name' => 'table:pat_key',
                                ],
                                'key' => [
                                    'type' => [
                                        'id' => 8,
                                        'name' => 'Int32',
                                        'type' => [
                                            'id' => 32,
                                            'name' => 'type',
                                        ],
                                        'size' => 4,
                                    ],
                                    'total_size' => 0,
                                    'max_total_size' => 4294967294,
                                ],
                                'value' => ['type' => null],
                                'n_records' => 0,
                                'disk_usage' => 4243456,
                            ],
                            'full_name' => 'idx_correo.search',
                        ],
                    ],
                    'disk_usage' => 565248,
                ]),
            ],
        ]);

        $this->dummyDbi->addSelectDb('my_db');
        $lengths = StorageEngine::getMroongaLengths('my_db', 'idx_correo');
        $this->assertAllSelectsConsumed();
        $this->assertSame([4521984, 578126], $lengths);

        $this->assertAllQueriesConsumed();
    }
}
