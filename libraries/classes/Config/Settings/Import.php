<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

use function in_array;

/**
 * @psalm-immutable
 */
final class Import
{
    /**
     * @var string
     * @psalm-var 'csv'|'docsql'|'ldi'|'sql'
     */
    public $format = 'sql';

    /**
     * Default charset for import.
     *
     * @var string
     */
    public $charset = '';

    /** @var bool */
    public $allow_interrupt = true;

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    public $skip_queries = 0;

    /**
     * @var string
     * @psalm-var 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    public $sql_compatibility = 'NONE';

    /** @var bool */
    public $sql_no_auto_value_on_zero = true;

    /** @var bool */
    public $sql_read_as_multibytes = false;

    /** @var bool */
    public $csv_replace = false;

    /** @var bool */
    public $csv_ignore = false;

    /** @var string */
    public $csv_terminated = ',';

    /** @var string */
    public $csv_enclosed = '"';

    /** @var string */
    public $csv_escaped = '"';

    /** @var string */
    public $csv_new_line = 'auto';

    /** @var string */
    public $csv_columns = '';

    /** @var bool */
    public $csv_col_names = false;

    /** @var bool */
    public $ldi_replace = false;

    /** @var bool */
    public $ldi_ignore = false;

    /** @var string */
    public $ldi_terminated = ';';

    /** @var string */
    public $ldi_enclosed = '"';

    /** @var string */
    public $ldi_escaped = '\\';

    /** @var string */
    public $ldi_new_line = 'auto';

    /** @var string */
    public $ldi_columns = '';

    /**
     * 'auto' for auto-detection, true or false for forcing
     *
     * @var string|bool
     * @psalm-var 'auto'|bool
     */
    public $ldi_local_option = 'auto';

    /** @var bool */
    public $ods_col_names = false;

    /** @var bool */
    public $ods_empty_rows = true;

    /** @var bool */
    public $ods_recognize_percentages = true;

    /** @var bool */
    public $ods_recognize_currency = true;

    /**
     * @param array<int|string, mixed> $import
     */
    public function __construct(array $import = [])
    {
        if (isset($import['format']) && in_array($import['format'], ['csv', 'docsql', 'ldi', 'sql'], true)) {
            $this->format = $import['format'];
        }

        if (isset($import['charset'])) {
            $this->charset = (string) $import['charset'];
        }

        if (isset($import['allow_interrupt'])) {
            $this->allow_interrupt = (bool) $import['allow_interrupt'];
        }

        if (isset($import['skip_queries'])) {
            $skipQueries = (int) $import['skip_queries'];
            if ($skipQueries >= 1) {
                $this->skip_queries = $skipQueries;
            }
        }

        if (
            isset($import['sql_compatibility']) && in_array(
                $import['sql_compatibility'],
                ['NONE', 'ANSI', 'DB2', 'MAXDB', 'MYSQL323', 'MYSQL40', 'MSSQL', 'ORACLE', 'TRADITIONAL'],
                true
            )
        ) {
            $this->sql_compatibility = $import['sql_compatibility'];
        }

        if (isset($import['sql_no_auto_value_on_zero'])) {
            $this->sql_no_auto_value_on_zero = (bool) $import['sql_no_auto_value_on_zero'];
        }

        if (isset($import['sql_read_as_multibytes'])) {
            $this->sql_read_as_multibytes = (bool) $import['sql_read_as_multibytes'];
        }

        if (isset($import['csv_replace'])) {
            $this->csv_replace = (bool) $import['csv_replace'];
        }

        if (isset($import['csv_ignore'])) {
            $this->csv_ignore = (bool) $import['csv_ignore'];
        }

        if (isset($import['csv_terminated'])) {
            $this->csv_terminated = (string) $import['csv_terminated'];
        }

        if (isset($import['csv_enclosed'])) {
            $this->csv_enclosed = (string) $import['csv_enclosed'];
        }

        if (isset($import['csv_escaped'])) {
            $this->csv_escaped = (string) $import['csv_escaped'];
        }

        if (isset($import['csv_new_line'])) {
            $this->csv_new_line = (string) $import['csv_new_line'];
        }

        if (isset($import['csv_columns'])) {
            $this->csv_columns = (string) $import['csv_columns'];
        }

        if (isset($import['csv_col_names'])) {
            $this->csv_col_names = (bool) $import['csv_col_names'];
        }

        if (isset($import['ldi_replace'])) {
            $this->ldi_replace = (bool) $import['ldi_replace'];
        }

        if (isset($import['ldi_ignore'])) {
            $this->ldi_ignore = (bool) $import['ldi_ignore'];
        }

        if (isset($import['ldi_terminated'])) {
            $this->ldi_terminated = (string) $import['ldi_terminated'];
        }

        if (isset($import['ldi_enclosed'])) {
            $this->ldi_enclosed = (string) $import['ldi_enclosed'];
        }

        if (isset($import['ldi_escaped'])) {
            $this->ldi_escaped = (string) $import['ldi_escaped'];
        }

        if (isset($import['ldi_new_line'])) {
            $this->ldi_new_line = (string) $import['ldi_new_line'];
        }

        if (isset($import['ldi_columns'])) {
            $this->ldi_columns = (string) $import['ldi_columns'];
        }

        if (isset($import['ldi_local_option']) && $import['ldi_local_option'] !== 'auto') {
            $this->ldi_local_option = (bool) $import['ldi_local_option'];
        }

        if (isset($import['ods_col_names'])) {
            $this->ods_col_names = (bool) $import['ods_col_names'];
        }

        if (isset($import['ods_empty_rows'])) {
            $this->ods_empty_rows = (bool) $import['ods_empty_rows'];
        }

        if (isset($import['ods_recognize_percentages'])) {
            $this->ods_recognize_percentages = (bool) $import['ods_recognize_percentages'];
        }

        if (! isset($import['ods_recognize_currency'])) {
            return;
        }

        $this->ods_recognize_currency = (bool) $import['ods_recognize_currency'];
    }
}
