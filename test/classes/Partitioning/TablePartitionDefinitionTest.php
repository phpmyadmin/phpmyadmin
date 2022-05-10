<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

use PhpMyAdmin\Partitioning\TablePartitionDefinition;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * @covers \PhpMyAdmin\Partitioning\TablePartitionDefinition
 */
class TablePartitionDefinitionTest extends TestCase
{
    /**
     * @dataProvider providerGetDetails
     */
    public function testGetDetails(
        string $partitionBy,
        bool $canHaveSubpartitions,
        bool $valueEnabled,
        int $partitionCount,
        int $subPartitionCount,
        ?array $partitions
    ): void {
        $expected = [
            'partition_by' => $partitionBy,
            'partition_expr' => 'partition_expr',
            'subpartition_by' => 'subpartition_by',
            'subpartition_expr' => 'subpartition_expr',
            'partition_count' => $partitionCount,
            'subpartition_count' => $subPartitionCount,
            'can_have_subpartitions' => $canHaveSubpartitions,
            'value_enabled' => $valueEnabled,
            'partitions' => [
                [
                    'name' => 'part0',
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                    'prefix' => 'partitions[0]',
                    'subpartition_count' => 2,
                    'subpartitions' => [
                        [
                            'name' => 'part0_s0',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                            'prefix' => 'partitions[0][subpartitions][0]',
                        ],
                        [
                            'name' => 'part0_s1',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                            'prefix' => 'partitions[0][subpartitions][1]',
                        ],
                    ],
                ],
                [
                    'name' => 'p1',
                    'value_type' => '',
                    'value' => '',
                    'engine' => '',
                    'comment' => '',
                    'data_directory' => '',
                    'index_directory' => '',
                    'max_rows' => '',
                    'min_rows' => '',
                    'tablespace' => '',
                    'node_group' => '',
                    'prefix' => 'partitions[1]',
                    'subpartition_count' => 2,
                    'subpartitions' => [
                        [
                            'name' => 'p1_s0',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                            'prefix' => 'partitions[1][subpartitions][0]',
                        ],
                        [
                            'name' => 'p1_s1',
                            'engine' => '',
                            'comment' => '',
                            'data_directory' => '',
                            'index_directory' => '',
                            'max_rows' => '',
                            'min_rows' => '',
                            'tablespace' => '',
                            'node_group' => '',
                            'prefix' => 'partitions[1][subpartitions][1]',
                        ],
                    ],
                ],
            ],
        ];

        if (! $canHaveSubpartitions && $partitionCount === 2) {
            unset($expected['partitions'][0]['subpartition_count']);
            unset($expected['partitions'][0]['subpartitions']);
            unset($expected['partitions'][1]['subpartition_count']);
            unset($expected['partitions'][1]['subpartitions']);
        }

        if ($partitionCount < 2) {
            unset($expected['partitions']);
        }

        $_POST['partition_by'] = $partitionBy;
        $_POST['partition_expr'] = 'partition_expr';
        $_POST['subpartition_by'] = 'subpartition_by';
        $_POST['subpartition_expr'] = 'subpartition_expr';
        $_POST['partition_count'] = (string) $partitionCount;
        $_POST['subpartition_count'] = (string) $subPartitionCount;
        $_POST['partitions'] = $partitions;
        $_POST['ignored_key'] = 'ignored_value';

        $actual = TablePartitionDefinition::getDetails();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @psalm-return array<string, array{
     *   0: string, 1: bool, 2: bool, 3: int, 4: int, 5: array<string, string|array<string, string>[]>[]|null
     * }>
     */
    public function providerGetDetails(): array
    {
        return [
            'partition by RANGE' => ['RANGE', true, true, 2, 2, [['name' => 'part0']]],
            'partition by RANGE COLUMNS' => ['RANGE COLUMNS', true, true, 2, 2, [['name' => 'part0']]],
            'partition by LIST' => ['LIST', true, true, 2, 2, [['name' => 'part0']]],
            'partition by LIST COLUMNS' => ['LIST COLUMNS', true, true, 2, 2, [['name' => 'part0']]],
            'partition by HASH' => ['HASH', false, false, 2, 2, [['name' => 'part0']]],
            'partition count === 0' => ['RANGE', false, true, 0, 0, null],
            'partition count === 1' => ['RANGE', false, true, 1, 1, null],
            'more partitions than the partition count' => [
                'RANGE',
                true,
                true,
                2,
                2,
                [['name' => 'part0'], ['name' => 'p1'], ['name' => 'p2']],
            ],
            'more subpartitions than the subpartition count' => [
                'RANGE',
                true,
                true,
                2,
                2,
                [
                    [
                        'name' => 'part0',
                        'subpartitions' => [
                            [
                                'name' => 'part0_s0',
                                'engine' => '',
                                'comment' => '',
                                'data_directory' => '',
                                'index_directory' => '',
                                'max_rows' => '',
                                'min_rows' => '',
                                'tablespace' => '',
                                'node_group' => '',
                                'prefix' => 'partitions[1][subpartitions][0]',
                            ],
                            [
                                'name' => 'part0_s1',
                                'engine' => '',
                                'comment' => '',
                                'data_directory' => '',
                                'index_directory' => '',
                                'max_rows' => '',
                                'min_rows' => '',
                                'tablespace' => '',
                                'node_group' => '',
                                'prefix' => 'partitions[1][subpartitions][1]',
                            ],
                            [
                                'name' => 'part0_s1',
                                'engine' => '',
                                'comment' => '',
                                'data_directory' => '',
                                'index_directory' => '',
                                'max_rows' => '',
                                'min_rows' => '',
                                'tablespace' => '',
                                'node_group' => '',
                                'prefix' => 'partitions[1][subpartitions][1]',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testGetDetailsWithoutPostValues(): void
    {
        $_POST = [];
        $expected = [
            'partition_by' => null,
            'partition_expr' => null,
            'subpartition_by' => null,
            'subpartition_expr' => null,
            'partition_count' => 0,
            'subpartition_count' => 0,
            'can_have_subpartitions' => false,
            'value_enabled' => false,
        ];

        $actual = TablePartitionDefinition::getDetails($expected);
        $this->assertEquals($expected, $actual);

        $actual = TablePartitionDefinition::getDetails();
        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerGetDetailsWithMaxPartitions
     */
    public function testGetDetailsWithMaxPartitions(int $partitionCount, string $partitionCountFromPost): void
    {
        $_POST = ['partition_count' => $partitionCountFromPost];
        $actual = TablePartitionDefinition::getDetails();
        $this->assertArrayHasKey('partition_count', $actual);
        $this->assertArrayHasKey('partitions', $actual);
        $this->assertSame($partitionCount, $actual['partition_count']);
        $this->assertIsArray($actual['partitions']);
        $this->assertEquals($partitionCount, count($actual['partitions']));
    }

    /**
     * @psalm-return array{0: int, 1: string}[]
     */
    public function providerGetDetailsWithMaxPartitions(): array
    {
        return ['count within the limit' => [8192, '8192'], 'count above the limit' => [8192, '8193']];
    }
}
