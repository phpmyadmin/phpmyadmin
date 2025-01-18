<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use function __;

enum TrackedDataType
{
    case DDL;
    case DML;

    /** @psalm-return literal-string */
    public function getColumnName(): string
    {
        return match ($this) {
            TrackedDataType::DDL => 'schema_sql',
            TrackedDataType::DML => 'data_sql',
        };
    }

    /** @psalm-return literal-string */
    public function getLogName(): string
    {
        return match ($this) {
            TrackedDataType::DDL => 'ddlog',
            TrackedDataType::DML => 'dmlog',
        };
    }

    public function getSuccessMessage(): string
    {
        return match ($this) {
            TrackedDataType::DDL => __('Tracking data definition successfully deleted'),
            TrackedDataType::DML => __('Tracking data manipulation successfully deleted'),
        };
    }

    public function getHeaderMessage(): string
    {
        return match ($this) {
            TrackedDataType::DDL => __('Data definition statement'),
            TrackedDataType::DML => __('Data manipulation statement'),
        };
    }

    /** @psalm-return literal-string */
    public function getTableId(): string
    {
        return match ($this) {
            TrackedDataType::DDL => 'ddl_versions',
            TrackedDataType::DML => 'dml_versions',
        };
    }
}
