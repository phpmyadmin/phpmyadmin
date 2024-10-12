<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

use PhpMyAdmin\Partitioning\SubPartition;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Partitioning\SubPartition
 */
class SubPartitionTest extends TestCase
{
    public function testSubPartition(): void
    {
        $row = [
            'TABLE_SCHEMA' => 'TABLE_SCHEMA',
            'TABLE_NAME' => 'TABLE_NAME',
            'SUBPARTITION_NAME' => 'subpartition_name',
            'SUBPARTITION_ORDINAL_POSITION' => 1,
            'SUBPARTITION_METHOD' => 'subpartition_method',
            'SUBPARTITION_EXPRESSION' => 'subpartition_expression',
            'TABLE_ROWS' => 2,
            'DATA_LENGTH' => 3,
            'INDEX_LENGTH' => 4,
            'PARTITION_COMMENT' => 'partition_comment',
        ];
        $object = new SubPartition($row);
        self::assertSame('subpartition_name', $object->getName());
        self::assertSame(1, $object->getOrdinal());
        self::assertSame('subpartition_method', $object->getMethod());
        self::assertSame('subpartition_expression', $object->getExpression());
        self::assertSame(2, $object->getRows());
        self::assertSame(3, $object->getDataLength());
        self::assertSame(4, $object->getIndexLength());
        self::assertSame('partition_comment', $object->getComment());
    }
}
