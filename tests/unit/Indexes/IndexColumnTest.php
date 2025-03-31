<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Indexes;

use PhpMyAdmin\Indexes\IndexColumn;
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
        self::assertSame('No', $this->object->getNull());
        $this->object->set(['Null' => 'YES']);
        self::assertSame('Yes', $this->object->getNull());
    }

    public function testIsNullable(): void
    {
        $this->object->set(['Null' => '']);
        self::assertFalse($this->object->isNullable());
        $this->object->set(['Null' => 'YES']);
        self::assertTrue($this->object->isNullable());
    }

    public function testGetSeqInIndex(): void
    {
        self::assertSame(1, $this->object->getSeqInIndex());
        $this->object->set(['Seq_in_index' => 2]);
        self::assertSame(2, $this->object->getSeqInIndex());
    }

    public function testGetSubPart(): void
    {
        self::assertNull($this->object->getSubPart());
        $this->object->set(['Sub_part' => 2]);
        self::assertSame(2, $this->object->getSubPart());
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
        self::assertSame('', $this->object->getName());
        $this->object->set(['Column_name' => 'name']);
        self::assertSame('name', $this->object->getName());
    }

    public function testGetCardinality(): void
    {
        self::assertNull($this->object->getCardinality());
        $this->object->set(['Cardinality' => 2]);
        self::assertSame(2, $this->object->getCardinality());
    }

    public function testGetCollation(): void
    {
        self::assertNull($this->object->getCollation());
        $this->object->set(['Collation' => 'collation']);
        self::assertSame('collation', $this->object->getCollation());
    }
}
