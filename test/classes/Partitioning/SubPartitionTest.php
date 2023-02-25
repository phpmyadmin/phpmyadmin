<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Partitioning;

use PhpMyAdmin\Partitioning\SubPartition;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Partitioning\SubPartition */
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
        $this->assertEquals('subpartition_name', $object->getName());
        $this->assertEquals(1, $object->getOrdinal());
        $this->assertEquals('subpartition_method', $object->getMethod());
        $this->assertEquals('subpartition_expression', $object->getExpression());
        $this->assertEquals(2, $object->getRows());
        $this->assertEquals(3, $object->getDataLength());
        $this->assertEquals(4, $object->getIndexLength());
        $this->assertEquals('partition_comment', $object->getComment());
    }
}
