<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\IndexColumn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexColumn::class)]
class IndexColumnTest extends TestCase
{
    private IndexColumn $object;

    protected function setUp(): void
    {
        $this->object = new IndexColumn();
    }

    public function testGetNull(): void
    {
        $this->object->set(['Null' => '']);
        self::assertEquals('No', $this->object->getNull());
        $this->object->set(['Null' => 'YES']);
        self::assertEquals('Yes', $this->object->getNull());
    }

    public function testIsNullable(): void
    {
        $this->object->set(['Null' => '']);
        self::assertEquals(false, $this->object->isNullable());
        $this->object->set(['Null' => 'YES']);
        self::assertEquals(true, $this->object->isNullable());
    }

    public function testGetSeqInIndex(): void
    {
        self::assertEquals(1, $this->object->getSeqInIndex());
        $this->object->set(['Seq_in_index' => 2]);
        self::assertEquals(2, $this->object->getSeqInIndex());
    }

    public function testGetSubPart(): void
    {
        self::assertNull($this->object->getSubPart());
        $this->object->set(['Sub_part' => 2]);
        self::assertEquals(2, $this->object->getSubPart());
    }

    public function testGetCompareData(): void
    {
        self::assertSame(
            ['Column_name' => '', 'Seq_in_index' => 1, 'Collation' => null, 'Sub_part' => null, 'Null' => ''],
            $this->object->getCompareData(),
        );
        $object = new IndexColumn([
            'Column_name' => 'name',
            'Seq_in_index' => 2,
            'Collation' => 'collation',
            'Sub_part' => 2,
            'Null' => 'NO',
        ]);
        self::assertSame(
            [
                'Column_name' => 'name',
                'Seq_in_index' => 2,
                'Collation' => 'collation',
                'Sub_part' => 2,
                'Null' => 'NO',
            ],
            $object->getCompareData(),
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('', $this->object->getName());
        $this->object->set(['Column_name' => 'name']);
        self::assertEquals('name', $this->object->getName());
    }

    public function testGetCardinality(): void
    {
        self::assertNull($this->object->getCardinality());
        $this->object->set(['Cardinality' => 2]);
        self::assertEquals(2, $this->object->getCardinality());
    }

    public function testGetCollation(): void
    {
        self::assertNull($this->object->getCollation());
        $this->object->set(['Collation' => 'collation']);
        self::assertEquals('collation', $this->object->getCollation());
    }
}
