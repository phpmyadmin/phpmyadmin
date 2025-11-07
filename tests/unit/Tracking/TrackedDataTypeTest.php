<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Tracking;

use PhpMyAdmin\Tracking\TrackedDataType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrackedDataType::class)]
final class TrackedDataTypeTest extends TestCase
{
    public function testGetSuccessMessage(): void
    {
        self::assertSame('Tracking data definition successfully deleted', TrackedDataType::DDL->getSuccessMessage());
        self::assertSame('Tracking data manipulation successfully deleted', TrackedDataType::DML->getSuccessMessage());
    }

    public function testGetLogName(): void
    {
        self::assertSame('ddlog', TrackedDataType::DDL->getLogName());
        self::assertSame('dmlog', TrackedDataType::DML->getLogName());
    }

    public function testGetHeaderMessage(): void
    {
        self::assertSame('Data definition statement', TrackedDataType::DDL->getHeaderMessage());
        self::assertSame('Data manipulation statement', TrackedDataType::DML->getHeaderMessage());
    }

    public function testGetTableId(): void
    {
        self::assertSame('ddl_versions', TrackedDataType::DDL->getTableId());
        self::assertSame('dml_versions', TrackedDataType::DML->getTableId());
    }

    public function testGetColumnName(): void
    {
        self::assertSame('schema_sql', TrackedDataType::DDL->getColumnName());
        self::assertSame('data_sql', TrackedDataType::DML->getColumnName());
    }
}
