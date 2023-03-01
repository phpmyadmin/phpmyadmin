<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

use function in_array;

/**
 * Import defaults
 *
 * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Import
 *
 * @psalm-immutable
 */
final class Import
{
    /**
     * ```php
     * $cfg['Import']['format'] = 'sql';
     * ```
     *
     * @psalm-var 'csv'|'docsql'|'ldi'|'sql'
     */
    public string $format;

    /**
     * Default charset for import.
     *
     * ```php
     * $cfg['Import']['charset'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Import_charset
     */
    public string $charset;

    /**
     * ```php
     * $cfg['Import']['allow_interrupt'] = true;
     * ```
     */
    public bool $allow_interrupt;

    /**
     * ```php
     * $cfg['Import']['skip_queries'] = 0;
     * ```
     *
     * @psalm-var 0|positive-int
     */
    public int $skip_queries;

    /**
     * ```php
     * $cfg['Import']['sql_compatibility'] = 'NONE';
     * ```
     *
     * @psalm-var 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    public string $sql_compatibility;

    /**
     * ```php
     * $cfg['Import']['sql_no_auto_value_on_zero'] = true;
     * ```
     */
    public bool $sql_no_auto_value_on_zero;

    /**
     * ```php
     * $cfg['Import']['sql_read_as_multibytes'] = false;
     * ```
     */
    public bool $sql_read_as_multibytes;

    /**
     * ```php
     * $cfg['Import']['csv_replace'] = false;
     * ```
     */
    public bool $csv_replace;

    /**
     * ```php
     * $cfg['Import']['csv_ignore'] = false;
     * ```
     */
    public bool $csv_ignore;

    /**
     * ```php
     * $cfg['Import']['csv_terminated'] = ',';
     * ```
     */
    public string $csv_terminated;

    /**
     * ```php
     * $cfg['Import']['csv_enclosed'] = '"';
     * ```
     */
    public string $csv_enclosed;

    /**
     * ```php
     * $cfg['Import']['csv_escaped'] = '"';
     * ```
     */
    public string $csv_escaped;

    /**
     * ```php
     * $cfg['Import']['csv_new_line'] = 'auto';
     * ```
     */
    public string $csv_new_line;

    /**
     * ```php
     * $cfg['Import']['csv_columns'] = '';
     * ```
     */
    public string $csv_columns;

    /**
     * ```php
     * $cfg['Import']['csv_col_names'] = false;
     * ```
     */
    public bool $csv_col_names;

    /**
     * ```php
     * $cfg['Import']['ldi_replace'] = false;
     * ```
     */
    public bool $ldi_replace;

    /**
     * ```php
     * $cfg['Import']['ldi_ignore'] = false;
     * ```
     */
    public bool $ldi_ignore;

    /**
     * ```php
     * $cfg['Import']['ldi_terminated'] = ';';
     * ```
     */
    public string $ldi_terminated;

    /**
     * ```php
     * $cfg['Import']['ldi_enclosed'] = '"';
     * ```
     */
    public string $ldi_enclosed;

    /**
     * ```php
     * $cfg['Import']['ldi_escaped'] = '\\';
     * ```
     */
    public string $ldi_escaped;

    /**
     * ```php
     * $cfg['Import']['ldi_new_line'] = 'auto';
     * ```
     */
    public string $ldi_new_line;

    /**
     * ```php
     * $cfg['Import']['ldi_columns'] = '';
     * ```
     */
    public string $ldi_columns;

    /**
     * 'auto' for auto-detection, true or false for forcing
     *
     * ```php
     * $cfg['Import']['ldi_local_option'] = 'auto';
     * ```
     *
     * @psalm-var 'auto'|bool
     */
    public string|bool $ldi_local_option;

    /**
     * ```php
     * $cfg['Import']['ods_col_names'] = false;
     * ```
     */
    public bool $ods_col_names;

    /**
     * ```php
     * $cfg['Import']['ods_empty_rows'] = true;
     * ```
     */
    public bool $ods_empty_rows;

    /**
     * ```php
     * $cfg['Import']['ods_recognize_percentages'] = true;
     * ```
     */
    public bool $ods_recognize_percentages;

    /**
     * ```php
     * $cfg['Import']['ods_recognize_currency'] = true;
     * ```
     */
    public bool $ods_recognize_currency;

    /** @param array<int|string, mixed> $import */
    public function __construct(array $import = [])
    {
        $this->format = $this->setFormat($import);
        $this->charset = $this->setCharset($import);
        $this->allow_interrupt = $this->setAllowInterrupt($import);
        $this->skip_queries = $this->setSkipQueries($import);
        $this->sql_compatibility = $this->setSqlCompatibility($import);
        $this->sql_no_auto_value_on_zero = $this->setSqlNoAutoValueOnZero($import);
        $this->sql_read_as_multibytes = $this->setSqlReadAsMultibytes($import);
        $this->csv_replace = $this->setCsvReplace($import);
        $this->csv_ignore = $this->setCsvIgnore($import);
        $this->csv_terminated = $this->setCsvTerminated($import);
        $this->csv_enclosed = $this->setCsvEnclosed($import);
        $this->csv_escaped = $this->setCsvEscaped($import);
        $this->csv_new_line = $this->setCsvNewLine($import);
        $this->csv_columns = $this->setCsvColumns($import);
        $this->csv_col_names = $this->setCsvColNames($import);
        $this->ldi_replace = $this->setLdiReplace($import);
        $this->ldi_ignore = $this->setLdiIgnore($import);
        $this->ldi_terminated = $this->setLdiTerminated($import);
        $this->ldi_enclosed = $this->setLdiEnclosed($import);
        $this->ldi_escaped = $this->setLdiEscaped($import);
        $this->ldi_new_line = $this->setLdiNewLine($import);
        $this->ldi_columns = $this->setLdiColumns($import);
        $this->ldi_local_option = $this->setLdiLocalOption($import);
        $this->ods_col_names = $this->setOdsColNames($import);
        $this->ods_empty_rows = $this->setOdsEmptyRows($import);
        $this->ods_recognize_percentages = $this->setOdsRecognizePercentages($import);
        $this->ods_recognize_currency = $this->setOdsRecognizeCurrency($import);
    }

    /** @return array<string, string|bool|int> */
    public function asArray(): array
    {
        return [
            'format' => $this->format,
            'charset' => $this->charset,
            'allow_interrupt' => $this->allow_interrupt,
            'skip_queries' => $this->skip_queries,
            'sql_compatibility' => $this->sql_compatibility,
            'sql_no_auto_value_on_zero' => $this->sql_no_auto_value_on_zero,
            'sql_read_as_multibytes' => $this->sql_read_as_multibytes,
            'csv_replace' => $this->csv_replace,
            'csv_ignore' => $this->csv_ignore,
            'csv_terminated' => $this->csv_terminated,
            'csv_enclosed' => $this->csv_enclosed,
            'csv_escaped' => $this->csv_escaped,
            'csv_new_line' => $this->csv_new_line,
            'csv_columns' => $this->csv_columns,
            'csv_col_names' => $this->csv_col_names,
            'ldi_replace' => $this->ldi_replace,
            'ldi_ignore' => $this->ldi_ignore,
            'ldi_terminated' => $this->ldi_terminated,
            'ldi_enclosed' => $this->ldi_enclosed,
            'ldi_escaped' => $this->ldi_escaped,
            'ldi_new_line' => $this->ldi_new_line,
            'ldi_columns' => $this->ldi_columns,
            'ldi_local_option' => $this->ldi_local_option,
            'ods_col_names' => $this->ods_col_names,
            'ods_empty_rows' => $this->ods_empty_rows,
            'ods_recognize_percentages' => $this->ods_recognize_percentages,
            'ods_recognize_currency' => $this->ods_recognize_currency,
        ];
    }

    /**
     * @param array<int|string, mixed> $import
     *
     * @psalm-return 'csv'|'docsql'|'ldi'|'sql'
     */
    private function setFormat(array $import): string
    {
        if (! isset($import['format']) || ! in_array($import['format'], ['csv', 'docsql', 'ldi'], true)) {
            return 'sql';
        }

        return $import['format'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCharset(array $import): string
    {
        if (! isset($import['charset'])) {
            return '';
        }

        return (string) $import['charset'];
    }

    /** @param array<int|string, mixed> $import */
    private function setAllowInterrupt(array $import): bool
    {
        if (! isset($import['allow_interrupt'])) {
            return true;
        }

        return (bool) $import['allow_interrupt'];
    }

    /**
     * @param array<int|string, mixed> $import
     *
     * @psalm-return 0|positive-int
     */
    private function setSkipQueries(array $import): int
    {
        if (! isset($import['skip_queries'])) {
            return 0;
        }

        $skipQueries = (int) $import['skip_queries'];

        return $skipQueries >= 1 ? $skipQueries : 0;
    }

    /**
     * @param array<int|string, mixed> $import
     *
     * @psalm-return 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    private function setSqlCompatibility(array $import): string
    {
        if (
            ! isset($import['sql_compatibility']) || ! in_array(
                $import['sql_compatibility'],
                ['ANSI', 'DB2', 'MAXDB', 'MYSQL323', 'MYSQL40', 'MSSQL', 'ORACLE', 'TRADITIONAL'],
                true,
            )
        ) {
            return 'NONE';
        }

        return $import['sql_compatibility'];
    }

    /** @param array<int|string, mixed> $import */
    private function setSqlNoAutoValueOnZero(array $import): bool
    {
        if (! isset($import['sql_no_auto_value_on_zero'])) {
            return true;
        }

        return (bool) $import['sql_no_auto_value_on_zero'];
    }

    /** @param array<int|string, mixed> $import */
    private function setSqlReadAsMultibytes(array $import): bool
    {
        if (! isset($import['sql_read_as_multibytes'])) {
            return false;
        }

        return (bool) $import['sql_read_as_multibytes'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvReplace(array $import): bool
    {
        if (! isset($import['csv_replace'])) {
            return false;
        }

        return (bool) $import['csv_replace'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvIgnore(array $import): bool
    {
        if (! isset($import['csv_ignore'])) {
            return false;
        }

        return (bool) $import['csv_ignore'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvTerminated(array $import): string
    {
        if (! isset($import['csv_terminated'])) {
            return ',';
        }

        return (string) $import['csv_terminated'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvEnclosed(array $import): string
    {
        if (! isset($import['csv_enclosed'])) {
            return '"';
        }

        return (string) $import['csv_enclosed'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvEscaped(array $import): string
    {
        if (! isset($import['csv_escaped'])) {
            return '"';
        }

        return (string) $import['csv_escaped'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvNewLine(array $import): string
    {
        if (! isset($import['csv_new_line'])) {
            return 'auto';
        }

        return (string) $import['csv_new_line'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvColumns(array $import): string
    {
        if (! isset($import['csv_columns'])) {
            return '';
        }

        return (string) $import['csv_columns'];
    }

    /** @param array<int|string, mixed> $import */
    private function setCsvColNames(array $import): bool
    {
        if (! isset($import['csv_col_names'])) {
            return false;
        }

        return (bool) $import['csv_col_names'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiReplace(array $import): bool
    {
        if (! isset($import['ldi_replace'])) {
            return false;
        }

        return (bool) $import['ldi_replace'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiIgnore(array $import): bool
    {
        if (! isset($import['ldi_ignore'])) {
            return false;
        }

        return (bool) $import['ldi_ignore'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiTerminated(array $import): string
    {
        if (! isset($import['ldi_terminated'])) {
            return ';';
        }

        return (string) $import['ldi_terminated'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiEnclosed(array $import): string
    {
        if (! isset($import['ldi_enclosed'])) {
            return '"';
        }

        return (string) $import['ldi_enclosed'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiEscaped(array $import): string
    {
        if (! isset($import['ldi_escaped'])) {
            return '\\';
        }

        return (string) $import['ldi_escaped'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiNewLine(array $import): string
    {
        if (! isset($import['ldi_new_line'])) {
            return 'auto';
        }

        return (string) $import['ldi_new_line'];
    }

    /** @param array<int|string, mixed> $import */
    private function setLdiColumns(array $import): string
    {
        if (! isset($import['ldi_columns'])) {
            return '';
        }

        return (string) $import['ldi_columns'];
    }

    /**
     * @param array<int|string, mixed> $import
     *
     * @psalm-return 'auto'|bool
     */
    private function setLdiLocalOption(array $import): bool|string
    {
        if (! isset($import['ldi_local_option']) || $import['ldi_local_option'] === 'auto') {
            return 'auto';
        }

        return (bool) $import['ldi_local_option'];
    }

    /** @param array<int|string, mixed> $import */
    private function setOdsColNames(array $import): bool
    {
        if (! isset($import['ods_col_names'])) {
            return false;
        }

        return (bool) $import['ods_col_names'];
    }

    /** @param array<int|string, mixed> $import */
    private function setOdsEmptyRows(array $import): bool
    {
        if (! isset($import['ods_empty_rows'])) {
            return true;
        }

        return (bool) $import['ods_empty_rows'];
    }

    /** @param array<int|string, mixed> $import */
    private function setOdsRecognizePercentages(array $import): bool
    {
        if (! isset($import['ods_recognize_percentages'])) {
            return true;
        }

        return (bool) $import['ods_recognize_percentages'];
    }

    /** @param array<int|string, mixed> $import */
    private function setOdsRecognizeCurrency(array $import): bool
    {
        if (! isset($import['ods_recognize_currency'])) {
            return true;
        }

        return (bool) $import['ods_recognize_currency'];
    }
}
