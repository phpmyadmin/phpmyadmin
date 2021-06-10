<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Partition;

/**
 * @covers \PhpMyAdmin\Partition
 */
class PartitionTest extends AbstractTestCase
{
    public function testGetPartitionMethodReturnsNull(): void
    {
        $actual = Partition::getPartitionMethod('database', 'no_partition_method');
        $this->assertNull($actual);
    }

    public function testGetPartitionMethodWithRangeMethod(): void
    {
        $actual = Partition::getPartitionMethod('database', 'range_partition_method');
        $this->assertEquals('RANGE', $actual);
    }
}
