<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 * @psalm-type StructureOrDataType = 'structure'|'data'|'structure_and_data'
 */
final class Export
{
    /**
     * @var string
     * @psalm-var 'codegen'|'csv'|'excel'|'htmlexcel'|'htmlword'|'latex'|'ods'|'odt'|'pdf'|'sql'|'texytext'|'xml'|'yaml'
     */
    public $format = 'sql';

    /**
     * @var string
     * @psalm-var 'quick'|'custom'|'custom-no-form'
     */
    public $method = 'quick';

    /**
     * @var string
     * @psalm-var 'none'|'zip'|'gzip'
     */
    public $compression = 'none';

    /**
     * Whether to LOCK TABLES before exporting
     *
     * @var bool
     */
    public $lock_tables = false;

    /**
     * Whether to export databases/tables as separate files
     *
     * @var bool
     */
    public $as_separate_files = false;

    /** @var bool */
    public $asfile = true;

    /** @var string */
    public $charset = '';

    /** @var bool */
    public $onserver = false;

    /** @var bool */
    public $onserver_overwrite = false;

    /** @var bool */
    public $quick_export_onserver = false;

    /** @var bool */
    public $quick_export_onserver_overwrite = false;

    /** @var bool */
    public $remember_file_template = true;

    /** @var string */
    public $file_template_table = '@TABLE@';

    /** @var string */
    public $file_template_database = '@DATABASE@';

    /** @var string */
    public $file_template_server = '@SERVER@';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $codegen_structure_or_data = 'data';

    /**
     * @var int
     * @psalm-var 0|1
     */
    public $codegen_format = 0;

    /** @var bool */
    public $ods_columns = false;

    /** @var string */
    public $ods_null = 'NULL';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $odt_structure_or_data = 'structure_and_data';

    /** @var bool */
    public $odt_columns = true;

    /** @var bool */
    public $odt_relation = true;

    /** @var bool */
    public $odt_comments = true;

    /** @var bool */
    public $odt_mime = true;

    /** @var string */
    public $odt_null = 'NULL';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $htmlword_structure_or_data = 'structure_and_data';

    /** @var bool */
    public $htmlword_columns = false;

    /** @var string */
    public $htmlword_null = 'NULL';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $texytext_structure_or_data = 'structure_and_data';

    /** @var bool */
    public $texytext_columns = false;

    /** @var string */
    public $texytext_null = 'NULL';

    /** @var bool */
    public $csv_columns = false;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $csv_structure_or_data = 'data';

    /** @var string */
    public $csv_null = 'NULL';

    /** @var string */
    public $csv_separator = ',';

    /** @var string */
    public $csv_enclosed = '"';

    /** @var string */
    public $csv_escaped = '"';

    /** @var string */
    public $csv_terminated = 'AUTO';

    /** @var bool */
    public $csv_removeCRLF = false;

    /** @var bool */
    public $excel_columns = true;

    /** @var string */
    public $excel_null = 'NULL';

    /**
     * @var string
     * @psalm-var 'win'|'mac_excel2003'|'mac_excel2008'
     */
    public $excel_edition = 'win';

    /** @var bool */
    public $excel_removeCRLF = false;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $excel_structure_or_data = 'data';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $latex_structure_or_data = 'structure_and_data';

    /** @var bool */
    public $latex_columns = true;

    /** @var bool */
    public $latex_relation = true;

    /** @var bool */
    public $latex_comments = true;

    /** @var bool */
    public $latex_mime = true;

    /** @var string */
    public $latex_null = '\textit{NULL}';

    /** @var bool */
    public $latex_caption = true;

    /** @var string */
    public $latex_structure_caption = 'strLatexStructure';

    /** @var string */
    public $latex_structure_continued_caption = 'strLatexStructure strLatexContinued';

    /** @var string */
    public $latex_data_caption = 'strLatexContent';

    /** @var string */
    public $latex_data_continued_caption = 'strLatexContent strLatexContinued';

    /** @var string */
    public $latex_data_label = 'tab:@TABLE@-data';

    /** @var string */
    public $latex_structure_label = 'tab:@TABLE@-structure';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $mediawiki_structure_or_data = 'data';

    /** @var bool */
    public $mediawiki_caption = true;

    /** @var bool */
    public $mediawiki_headers = true;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $ods_structure_or_data = 'data';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $pdf_structure_or_data = 'data';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $phparray_structure_or_data = 'data';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $json_structure_or_data = 'data';

    /** @var bool */
    public $json_pretty_print = false;

    /** @var bool */
    public $json_unicode = true;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $sql_structure_or_data = 'structure_and_data';

    /**
     * @var string
     * @psalm-var 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    public $sql_compatibility = 'NONE';

    /**
     * Whether to include comments in SQL export.
     *
     * @var bool
     */
    public $sql_include_comments = true;

    /** @var bool */
    public $sql_disable_fk = false;

    /** @var bool */
    public $sql_views_as_tables = false;

    /** @var bool */
    public $sql_metadata = false;

    /** @var bool */
    public $sql_use_transaction = true;

    /** @var bool */
    public $sql_create_database = false;

    /** @var bool */
    public $sql_drop_database = false;

    /** @var bool */
    public $sql_drop_table = false;

    /**
     * true by default for correct behavior when dealing with exporting
     * of VIEWs and the stand-in table
     *
     * @var bool
     */
    public $sql_if_not_exists = false;

    /** @var bool */
    public $sql_view_current_user = false;

    /** @var bool */
    public $sql_or_replace_view = false;

    /** @var bool */
    public $sql_procedure_function = true;

    /** @var bool */
    public $sql_create_table = true;

    /** @var bool */
    public $sql_create_view = true;

    /** @var bool */
    public $sql_create_trigger = true;

    /** @var bool */
    public $sql_auto_increment = true;

    /** @var bool */
    public $sql_backquotes = true;

    /** @var bool */
    public $sql_dates = false;

    /** @var bool */
    public $sql_relation = false;

    /** @var bool */
    public $sql_truncate = false;

    /** @var bool */
    public $sql_delayed = false;

    /** @var bool */
    public $sql_ignore = false;

    /**
     * Export time in UTC.
     *
     * @var bool
     */
    public $sql_utc_time = true;

    /** @var bool */
    public $sql_hex_for_binary = true;

    /**
     * @var string
     * @psalm-var 'INSERT'|'UPDATE'|'REPLACE'
     */
    public $sql_type = 'INSERT';

    /**
     * @var int
     * @psalm-var 0|positive-int
     */
    public $sql_max_query_size = 50000;

    /** @var bool */
    public $sql_mime = false;

    /**
     * \n is replaced by new line
     *
     * @var string
     */
    public $sql_header_comment = '';

    /**
     * Whether to use complete inserts, extended inserts, both, or neither
     *
     * @var string
     * @psalm-var 'complete'|'extended'|'both'|'none'
     */
    public $sql_insert_syntax = 'both';

    /** @var string */
    public $pdf_report_title = '';

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $xml_structure_or_data = 'data';

    /**
     * Export schema for each structure
     *
     * @var bool
     */
    public $xml_export_struc = true;

    /**
     * Export events
     *
     * @var bool
     */
    public $xml_export_events = true;

    /**
     * Export functions
     *
     * @var bool
     */
    public $xml_export_functions = true;

    /**
     * Export procedures
     *
     * @var bool
     */
    public $xml_export_procedures = true;

    /**
     * Export schema for each table
     *
     * @var bool
     */
    public $xml_export_tables = true;

    /**
     * Export triggers
     *
     * @var bool
     */
    public $xml_export_triggers = true;

    /**
     * Export views
     *
     * @var bool
     */
    public $xml_export_views = true;

    /**
     * Export contents data
     *
     * @var bool
     */
    public $xml_export_contents = true;

    /**
     * @var string
     * @psalm-var StructureOrDataType
     */
    public $yaml_structure_or_data = 'data';

    /**
     * @param array<int|string, mixed> $export
     */
    public function __construct(array $export = [])
    {
        if (
            isset($export['format']) && in_array($export['format'], [
                'codegen',
                'csv',
                'excel',
                'htmlexcel',
                'htmlword',
                'latex',
                'ods',
                'odt',
                'pdf',
                'texytext',
                'xml',
                'yaml',
            ], true)
        ) {
            $this->format = $export['format'];
        }

        if (isset($export['method']) && in_array($export['method'], ['custom', 'custom-no-form'], true)) {
            $this->method = $export['method'];
        }

        if (isset($export['compression']) && in_array($export['compression'], ['zip', 'gzip'], true)) {
            $this->compression = $export['compression'];
        }

        if (isset($export['lock_tables'])) {
            $this->lock_tables = (bool) $export['lock_tables'];
        }

        if (isset($export['as_separate_files'])) {
            $this->as_separate_files = (bool) $export['as_separate_files'];
        }

        if (isset($export['asfile'])) {
            $this->asfile = (bool) $export['asfile'];
        }

        if (isset($export['charset'])) {
            $this->charset = (string) $export['charset'];
        }

        if (isset($export['onserver'])) {
            $this->onserver = (bool) $export['onserver'];
        }

        if (isset($export['onserver_overwrite'])) {
            $this->onserver_overwrite = (bool) $export['onserver_overwrite'];
        }

        if (isset($export['quick_export_onserver'])) {
            $this->quick_export_onserver = (bool) $export['quick_export_onserver'];
        }

        if (isset($export['quick_export_onserver_overwrite'])) {
            $this->quick_export_onserver_overwrite = (bool) $export['quick_export_onserver_overwrite'];
        }

        if (isset($export['remember_file_template'])) {
            $this->remember_file_template = (bool) $export['remember_file_template'];
        }

        if (isset($export['file_template_table'])) {
            $this->file_template_table = (string) $export['file_template_table'];
        }

        if (isset($export['file_template_database'])) {
            $this->file_template_database = (string) $export['file_template_database'];
        }

        if (isset($export['file_template_server'])) {
            $this->file_template_server = (string) $export['file_template_server'];
        }

        if (
            isset($export['codegen_structure_or_data'])
            && in_array($export['codegen_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->codegen_structure_or_data = $export['codegen_structure_or_data'];
        }

        if (isset($export['codegen_format'])) {
            $codegenFormat = (int) $export['codegen_format'];
            if ($codegenFormat === 0 || $codegenFormat === 1) {
                $this->codegen_format = $codegenFormat;
            }
        }

        if (isset($export['ods_columns'])) {
            $this->ods_columns = (bool) $export['ods_columns'];
        }

        if (isset($export['ods_null'])) {
            $this->ods_null = (string) $export['ods_null'];
        }

        if (
            isset($export['odt_structure_or_data'])
            && in_array($export['odt_structure_or_data'], ['structure', 'data'], true)
        ) {
            $this->odt_structure_or_data = $export['odt_structure_or_data'];
        }

        if (isset($export['odt_columns'])) {
            $this->odt_columns = (bool) $export['odt_columns'];
        }

        if (isset($export['odt_relation'])) {
            $this->odt_relation = (bool) $export['odt_relation'];
        }

        if (isset($export['odt_comments'])) {
            $this->odt_comments = (bool) $export['odt_comments'];
        }

        if (isset($export['odt_mime'])) {
            $this->odt_mime = (bool) $export['odt_mime'];
        }

        if (isset($export['odt_null'])) {
            $this->odt_null = (string) $export['odt_null'];
        }

        if (
            isset($export['htmlword_structure_or_data'])
            && in_array($export['htmlword_structure_or_data'], ['structure', 'data'], true)
        ) {
            $this->htmlword_structure_or_data = $export['htmlword_structure_or_data'];
        }

        if (isset($export['htmlword_columns'])) {
            $this->htmlword_columns = (bool) $export['htmlword_columns'];
        }

        if (isset($export['htmlword_null'])) {
            $this->htmlword_null = (string) $export['htmlword_null'];
        }

        if (
            isset($export['texytext_structure_or_data'])
            && in_array($export['texytext_structure_or_data'], ['structure', 'data'], true)
        ) {
            $this->texytext_structure_or_data = $export['texytext_structure_or_data'];
        }

        if (isset($export['texytext_columns'])) {
            $this->texytext_columns = (bool) $export['texytext_columns'];
        }

        if (isset($export['texytext_null'])) {
            $this->texytext_null = (string) $export['texytext_null'];
        }

        if (isset($export['csv_columns'])) {
            $this->csv_columns = (bool) $export['csv_columns'];
        }

        if (
            isset($export['csv_structure_or_data'])
            && in_array($export['csv_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->csv_structure_or_data = $export['csv_structure_or_data'];
        }

        if (isset($export['csv_null'])) {
            $this->csv_null = (string) $export['csv_null'];
        }

        if (isset($export['csv_separator'])) {
            $this->csv_separator = (string) $export['csv_separator'];
        }

        if (isset($export['csv_enclosed'])) {
            $this->csv_enclosed = (string) $export['csv_enclosed'];
        }

        if (isset($export['csv_escaped'])) {
            $this->csv_escaped = (string) $export['csv_escaped'];
        }

        if (isset($export['csv_terminated'])) {
            $this->csv_terminated = (string) $export['csv_terminated'];
        }

        if (isset($export['csv_removeCRLF'])) {
            $this->csv_removeCRLF = (bool) $export['csv_removeCRLF'];
        }

        if (isset($export['excel_columns'])) {
            $this->excel_columns = (bool) $export['excel_columns'];
        }

        if (isset($export['excel_null'])) {
            $this->excel_null = (string) $export['excel_null'];
        }

        if (
            isset($export['excel_edition'])
            && in_array($export['excel_edition'], ['mac_excel2003', 'mac_excel2008'], true)
        ) {
            $this->excel_edition = $export['excel_edition'];
        }

        if (isset($export['excel_removeCRLF'])) {
            $this->excel_removeCRLF = (bool) $export['excel_removeCRLF'];
        }

        if (
            isset($export['excel_structure_or_data'])
            && in_array($export['excel_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->excel_structure_or_data = $export['excel_structure_or_data'];
        }

        if (
            isset($export['latex_structure_or_data'])
            && in_array($export['latex_structure_or_data'], ['structure', 'data'], true)
        ) {
            $this->latex_structure_or_data = $export['latex_structure_or_data'];
        }

        if (isset($export['latex_columns'])) {
            $this->latex_columns = (bool) $export['latex_columns'];
        }

        if (isset($export['latex_relation'])) {
            $this->latex_relation = (bool) $export['latex_relation'];
        }

        if (isset($export['latex_comments'])) {
            $this->latex_comments = (bool) $export['latex_comments'];
        }

        if (isset($export['latex_mime'])) {
            $this->latex_mime = (bool) $export['latex_mime'];
        }

        if (isset($export['latex_null'])) {
            $this->latex_null = (string) $export['latex_null'];
        }

        if (isset($export['latex_caption'])) {
            $this->latex_caption = (bool) $export['latex_caption'];
        }

        if (isset($export['latex_structure_caption'])) {
            $this->latex_structure_caption = (string) $export['latex_structure_caption'];
        }

        if (isset($export['latex_structure_continued_caption'])) {
            $this->latex_structure_continued_caption = (string) $export['latex_structure_continued_caption'];
        }

        if (isset($export['latex_data_caption'])) {
            $this->latex_data_caption = (string) $export['latex_data_caption'];
        }

        if (isset($export['latex_data_continued_caption'])) {
            $this->latex_data_continued_caption = (string) $export['latex_data_continued_caption'];
        }

        if (isset($export['latex_data_label'])) {
            $this->latex_data_label = (string) $export['latex_data_label'];
        }

        if (isset($export['latex_structure_label'])) {
            $this->latex_structure_label = (string) $export['latex_structure_label'];
        }

        if (
            isset($export['mediawiki_structure_or_data'])
            && in_array($export['mediawiki_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->mediawiki_structure_or_data = $export['mediawiki_structure_or_data'];
        }

        if (isset($export['mediawiki_caption'])) {
            $this->mediawiki_caption = (bool) $export['mediawiki_caption'];
        }

        if (isset($export['mediawiki_headers'])) {
            $this->mediawiki_headers = (bool) $export['mediawiki_headers'];
        }

        if (
            isset($export['ods_structure_or_data'])
            && in_array($export['ods_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->ods_structure_or_data = $export['ods_structure_or_data'];
        }

        if (
            isset($export['pdf_structure_or_data'])
            && in_array($export['pdf_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->pdf_structure_or_data = $export['pdf_structure_or_data'];
        }

        if (
            isset($export['phparray_structure_or_data'])
            && in_array($export['phparray_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->phparray_structure_or_data = $export['phparray_structure_or_data'];
        }

        if (
            isset($export['json_structure_or_data'])
            && in_array($export['json_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->json_structure_or_data = $export['json_structure_or_data'];
        }

        if (isset($export['json_pretty_print'])) {
            $this->json_pretty_print = (bool) $export['json_pretty_print'];
        }

        if (isset($export['json_unicode'])) {
            $this->json_unicode = (bool) $export['json_unicode'];
        }

        if (
            isset($export['sql_structure_or_data'])
            && in_array($export['sql_structure_or_data'], ['structure', 'data'], true)
        ) {
            $this->sql_structure_or_data = $export['sql_structure_or_data'];
        }

        if (
            isset($export['sql_compatibility']) && in_array($export['sql_compatibility'], [
                'ANSI',
                'DB2',
                'MAXDB',
                'MYSQL323',
                'MYSQL40',
                'MSSQL',
                'ORACLE',
                'TRADITIONAL',
            ], true)
        ) {
            $this->sql_compatibility = $export['sql_compatibility'];
        }

        if (isset($export['sql_include_comments'])) {
            $this->sql_include_comments = (bool) $export['sql_include_comments'];
        }

        if (isset($export['sql_disable_fk'])) {
            $this->sql_disable_fk = (bool) $export['sql_disable_fk'];
        }

        if (isset($export['sql_views_as_tables'])) {
            $this->sql_views_as_tables = (bool) $export['sql_views_as_tables'];
        }

        if (isset($export['sql_metadata'])) {
            $this->sql_metadata = (bool) $export['sql_metadata'];
        }

        if (isset($export['sql_use_transaction'])) {
            $this->sql_use_transaction = (bool) $export['sql_use_transaction'];
        }

        if (isset($export['sql_create_database'])) {
            $this->sql_create_database = (bool) $export['sql_create_database'];
        }

        if (isset($export['sql_drop_database'])) {
            $this->sql_drop_database = (bool) $export['sql_drop_database'];
        }

        if (isset($export['sql_drop_table'])) {
            $this->sql_drop_table = (bool) $export['sql_drop_table'];
        }

        if (isset($export['sql_if_not_exists'])) {
            $this->sql_if_not_exists = (bool) $export['sql_if_not_exists'];
        }

        if (isset($export['sql_view_current_user'])) {
            $this->sql_view_current_user = (bool) $export['sql_view_current_user'];
        }

        if (isset($export['sql_or_replace_view'])) {
            $this->sql_or_replace_view = (bool) $export['sql_or_replace_view'];
        }

        if (isset($export['sql_procedure_function'])) {
            $this->sql_procedure_function = (bool) $export['sql_procedure_function'];
        }

        if (isset($export['sql_create_table'])) {
            $this->sql_create_table = (bool) $export['sql_create_table'];
        }

        if (isset($export['sql_create_view'])) {
            $this->sql_create_view = (bool) $export['sql_create_view'];
        }

        if (isset($export['sql_create_trigger'])) {
            $this->sql_create_trigger = (bool) $export['sql_create_trigger'];
        }

        if (isset($export['sql_auto_increment'])) {
            $this->sql_auto_increment = (bool) $export['sql_auto_increment'];
        }

        if (isset($export['sql_backquotes'])) {
            $this->sql_backquotes = (bool) $export['sql_backquotes'];
        }

        if (isset($export['sql_dates'])) {
            $this->sql_dates = (bool) $export['sql_dates'];
        }

        if (isset($export['sql_relation'])) {
            $this->sql_relation = (bool) $export['sql_relation'];
        }

        if (isset($export['sql_truncate'])) {
            $this->sql_truncate = (bool) $export['sql_truncate'];
        }

        if (isset($export['sql_delayed'])) {
            $this->sql_delayed = (bool) $export['sql_delayed'];
        }

        if (isset($export['sql_ignore'])) {
            $this->sql_ignore = (bool) $export['sql_ignore'];
        }

        if (isset($export['sql_utc_time'])) {
            $this->sql_utc_time = (bool) $export['sql_utc_time'];
        }

        if (isset($export['sql_hex_for_binary'])) {
            $this->sql_hex_for_binary = (bool) $export['sql_hex_for_binary'];
        }

        if (isset($export['sql_type']) && in_array($export['sql_type'], ['UPDATE', 'REPLACE'], true)) {
            $this->sql_type = $export['sql_type'];
        }

        if (isset($export['sql_max_query_size'])) {
            $maxQuerySize = (int) $export['sql_max_query_size'];
            if ($maxQuerySize >= 0) {
                $this->sql_max_query_size = $maxQuerySize;
            }
        }

        if (isset($export['sql_mime'])) {
            $this->sql_mime = (bool) $export['sql_mime'];
        }

        if (isset($export['sql_header_comment'])) {
            $this->sql_header_comment = (string) $export['sql_header_comment'];
        }

        if (
            isset($export['sql_insert_syntax'])
            && in_array($export['sql_insert_syntax'], ['complete', 'extended', 'none'], true)
        ) {
            $this->sql_insert_syntax = $export['sql_insert_syntax'];
        }

        if (isset($export['pdf_report_title'])) {
            $this->pdf_report_title = (string) $export['pdf_report_title'];
        }

        if (
            isset($export['xml_structure_or_data'])
            && in_array($export['xml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            $this->xml_structure_or_data = $export['xml_structure_or_data'];
        }

        if (isset($export['xml_export_struc'])) {
            $this->xml_export_struc = (bool) $export['xml_export_struc'];
        }

        if (isset($export['xml_export_events'])) {
            $this->xml_export_events = (bool) $export['xml_export_events'];
        }

        if (isset($export['xml_export_functions'])) {
            $this->xml_export_functions = (bool) $export['xml_export_functions'];
        }

        if (isset($export['xml_export_procedures'])) {
            $this->xml_export_procedures = (bool) $export['xml_export_procedures'];
        }

        if (isset($export['xml_export_tables'])) {
            $this->xml_export_tables = (bool) $export['xml_export_tables'];
        }

        if (isset($export['xml_export_triggers'])) {
            $this->xml_export_triggers = (bool) $export['xml_export_triggers'];
        }

        if (isset($export['xml_export_views'])) {
            $this->xml_export_views = (bool) $export['xml_export_views'];
        }

        if (isset($export['xml_export_contents'])) {
            $this->xml_export_contents = (bool) $export['xml_export_contents'];
        }

        if (
            ! isset($export['yaml_structure_or_data'])
            || ! in_array($export['yaml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return;
        }

        $this->yaml_structure_or_data = $export['yaml_structure_or_data'];
    }
}
