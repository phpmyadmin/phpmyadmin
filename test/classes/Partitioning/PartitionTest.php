<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

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
        self::assertNull($actual);
    }

    public function testGetPartitionMethodWithRangeMethod(): void
    {
        $GLOBALS['server'] = 1;
        $actual = Partition::getPartitionMethod('database', 'range_partition_method');
        self::assertSame('RANGE', $actual);
    }
}
