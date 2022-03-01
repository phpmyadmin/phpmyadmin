<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Partitioning\Partition
 */
class PartitionTest extends AbstractTestCase
{
    public function testGetPartitionMethodReturnsNull(): void
    {
        $GLOBALS['server'] = 1;
        $actual = Partition::getPartitionMethod('database', 'no_partition_method');
        $this->assertNull($actual);
    }

    public function testGetPartitionMethodWithRangeMethod(): void
    {
        $GLOBALS['server'] = 1;
        $actual = Partition::getPartitionMethod('database', 'range_partition_method');
        $this->assertEquals('RANGE', $actual);
    }

    /**
     * @param string|false                      $varValue
     * @param array<int, array<string, string>> $pluginValue
     *
     * @dataProvider providerForTestHavePartitioning
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testHavePartitioning(bool $expected, int $version, $varValue, array $pluginValue): void
    {
        $mock = $this->createStub(DatabaseInterface::class);
        $mock->method('getVersion')->willReturn($version);
        $mock->method('fetchValue')->willReturn($varValue);
        $mock->method('fetchResult')->willReturn($pluginValue);
        $GLOBALS['dbi'] = $mock;
        $this->assertSame($expected, Partition::havePartitioning());
    }

    /**
     * @return array<string, array<int, bool|int|string|array<int, array<string, string>>>>
     * @psalm-return array<string, array{bool, positive-int, string|false, list<array{Name: string}>}>
     */
    public function providerForTestHavePartitioning(): array
    {
        return [
            '5.5.0 with partitioning support' => [true, 50500, '1', []],
            '5.5.0 without partitioning support' => [false, 50500, '0', []],
            '5.6.0 with partitioning support' => [
                true,
                50600,
                false,
                [
                    ['Name' => 'mysql_native_password'],
                    ['Name' => 'partition'],
                    ['Name' => 'InnoDB'],
                ],
            ],
            '5.6.0 without partitioning support' => [
                false,
                50600,
                false,
                [['Name' => 'mysql_native_password'], ['Name' => 'InnoDB']],
            ],
            '8.0.0' => [true, 80000, false, []],
        ];
    }
}
