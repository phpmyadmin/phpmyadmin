<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use function __;

enum LogTypeEnum
{
    case DDL;
    case DML;

    /** @psalm-return literal-string */
    public function getColumnName(): string
    {
        return match ($this) {
            LogTypeEnum::DDL => 'schema_sql',
            LogTypeEnum::DML => 'data_sql',
        };
    }

    /** @psalm-return literal-string */
    public function getLogName(): string
    {
        return match ($this) {
            LogTypeEnum::DDL => 'ddlog',
            LogTypeEnum::DML => 'dmlog',
        };
    }

    public function getSuccessMessage(): string
    {
        return match ($this) {
            LogTypeEnum::DDL => __('Tracking data definition successfully deleted'),
            LogTypeEnum::DML => __('Tracking data manipulation successfully deleted'),
        };
    }

    public function getHeaderMessage(): string
    {
        return match ($this) {
            LogTypeEnum::DDL => __('Data definition statement'),
            LogTypeEnum::DML => __('Data manipulation statement'),
        };
    }

    /** @psalm-return literal-string */
    public function getTableId(): string
    {
        return match ($this) {
            LogTypeEnum::DDL => 'ddl_versions',
            LogTypeEnum::DML => 'dml_versions',
        };
    }
}
