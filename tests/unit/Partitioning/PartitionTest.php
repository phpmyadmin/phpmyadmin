<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

#[CoversClass(Partition::class)]
class PartitionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testGetPartitionMethodReturnsNull(): void
    {
        $actual = Partition::getPartitionMethod('database', 'no_partition_method');
        self::assertNull($actual);
    }

    public function testGetPartitionMethodWithRangeMethod(): void
    {
        $actual = Partition::getPartitionMethod('database', 'range_partition_method');
        self::assertSame('RANGE', $actual);
    }

    /** @param array<int, array<string, string>> $pluginValue */
    #[DataProvider('providerForTestHavePartitioning')]
    #[PreserveGlobalState(false)]
    #[RunInSeparateProcess]
    public function testHavePartitioning(bool $expected, int $version, string|false $varValue, array $pluginValue): void
    {
        $mock = self::createStub(DatabaseInterface::class);
        $mock->method('getVersion')->willReturn($version);
        $mock->method('fetchValue')->willReturn($varValue);
        $mock->method('fetchResultSimple')->willReturn($pluginValue);
        DatabaseInterface::$instance = $mock;
        self::assertSame($expected, Partition::havePartitioning());
    }

    /**
     * @return array<string, array<int, bool|int|string|array<int, array<string, string>>>>
     * @psalm-return array<string, array{bool, positive-int, string|false, list<array{Name: string}>}>
     */
    public static function providerForTestHavePartitioning(): array
    {
        return [
            '5.5.0 with partitioning support' => [true, 50500, '1', []],
            '5.5.0 without partitioning support' => [false, 50500, '0', []],
            '5.6.0 with partitioning support' => [
                true,
                50600,
                false,
                [['Name' => 'mysql_native_password'], ['Name' => 'partition'], ['Name' => 'InnoDB']],
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
