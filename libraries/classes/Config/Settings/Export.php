<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * Export defaults
 *
 * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export
 *
 * @psalm-immutable
 * @psalm-type StructureOrDataType = 'structure'|'data'|'structure_and_data'
 */
final class Export
{
    /**
     * ```php
     * $cfg['Export']['format'] = 'sql';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_format
     *
     * @psalm-var 'codegen'|'csv'|'excel'|'htmlexcel'|'htmlword'|'latex'|'ods'|'odt'|'pdf'|'sql'|'texytext'|'xml'|'yaml'
     */
    public string $format;

    /**
     * ```php
     * $cfg['Export']['method'] = 'quick';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_method
     *
     * @psalm-var 'quick'|'custom'|'custom-no-form'
     */
    public string $method;

    /**
     * ```php
     * $cfg['Export']['compression'] = 'none';
     * ```
     *
     * @psalm-var 'none'|'zip'|'gzip'
     */
    public string $compression;

    /**
     * Whether to LOCK TABLES before exporting
     *
     * ```php
     * $cfg['Export']['lock_tables'] = false;
     * ```
     */
    public bool $lock_tables;

    /**
     * Whether to export databases/tables as separate files
     *
     * ```php
     * $cfg['Export']['as_separate_files'] = false;
     * ```
     */
    public bool $as_separate_files;

    /**
     * ```php
     * $cfg['Export']['asfile'] = true;
     * ```
     */
    public bool $asfile;

    /**
     * ```php
     * $cfg['Export']['charset'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_charset
     */
    public string $charset;

    /**
     * ```php
     * $cfg['Export']['onserver'] = false;
     * ```
     */
    public bool $onserver;

    /**
     * ```php
     * $cfg['Export']['onserver_overwrite'] = false;
     * ```
     */
    public bool $onserver_overwrite;

    /**
     * ```php
     * $cfg['Export']['quick_export_onserver'] = false;
     * ```
     */
    public bool $quick_export_onserver;

    /**
     * ```php
     * $cfg['Export']['quick_export_onserver_overwrite'] = false;
     * ```
     */
    public bool $quick_export_onserver_overwrite;

    /**
     * ```php
     * $cfg['Export']['remember_file_template'] = true;
     * ```
     */
    public bool $remember_file_template;

    /**
     * Default filename template for table exports.
     *
     * ```php
     * $cfg['Export']['file_template_table'] = '@TABLE@';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_file_template_table
     */
    public string $file_template_table;

    /**
     * Default filename template for database exports.
     *
     * ```php
     * $cfg['Export']['file_template_database'] = '@DATABASE@';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_file_template_database
     */
    public string $file_template_database;

    /**
     * Default filename template for server exports.
     *
     * ```php
     * $cfg['Export']['file_template_server'] = '@SERVER@';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Export_file_template_server
     */
    public string $file_template_server;

    /**
     * ```php
     * $cfg['Export']['codegen_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $codegen_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['codegen_format'] = 0;
     * ```
     *
     * @psalm-var 0|1
     */
    public int $codegen_format;

    /**
     * ```php
     * $cfg['Export']['ods_columns'] = false;
     * ```
     */
    public bool $ods_columns;

    /**
     * ```php
     * $cfg['Export']['ods_null'] = 'NULL';
     * ```
     */
    public string $ods_null;

    /**
     * ```php
     * $cfg['Export']['odt_structure_or_data'] = 'structure_and_data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $odt_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['odt_columns'] = true;
     * ```
     */
    public bool $odt_columns;

    /**
     * ```php
     * $cfg['Export']['odt_relation'] = true;
     * ```
     */
    public bool $odt_relation;

    /**
     * ```php
     * $cfg['Export']['odt_comments'] = true;
     * ```
     */
    public bool $odt_comments;

    /**
     * ```php
     * $cfg['Export']['odt_mime'] = true;
     * ```
     */
    public bool $odt_mime;

    /**
     * ```php
     * $cfg['Export']['odt_null'] = 'NULL';
     * ```
     */
    public string $odt_null;

    /**
     * ```php
     * $cfg['Export']['htmlword_structure_or_data'] = 'structure_and_data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $htmlword_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['htmlword_columns'] = false;
     * ```
     */
    public bool $htmlword_columns;

    /**
     * ```php
     * $cfg['Export']['htmlword_null'] = 'NULL';
     * ```
     */
    public string $htmlword_null;

    /**
     * ```php
     * $cfg['Export']['texytext_structure_or_data'] = 'structure_and_data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $texytext_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['texytext_columns'] = false;
     * ```
     */
    public bool $texytext_columns;

    /**
     * ```php
     * $cfg['Export']['texytext_null'] = 'NULL';
     * ```
     */
    public string $texytext_null;

    /**
     * ```php
     * $cfg['Export']['csv_columns'] = true;
     * ```
     */
    public bool $csv_columns;

    /**
     * ```php
     * $cfg['Export']['csv_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $csv_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['csv_null'] = 'NULL';
     * ```
     */
    public string $csv_null;

    /**
     * ```php
     * $cfg['Export']['csv_separator'] = ',';
     * ```
     */
    public string $csv_separator;

    /**
     * ```php
     * $cfg['Export']['csv_enclosed'] = '"';
     * ```
     */
    public string $csv_enclosed;

    /**
     * ```php
     * $cfg['Export']['csv_escaped'] = '"';
     * ```
     */
    public string $csv_escaped;

    /**
     * ```php
     * $cfg['Export']['csv_terminated'] = 'AUTO';
     * ```
     */
    public string $csv_terminated;

    /**
     * ```php
     * $cfg['Export']['csv_removeCRLF'] = false;
     * ```
     */
    public bool $csv_removeCRLF;

    /**
     * ```php
     * $cfg['Export']['excel_columns'] = true;
     * ```
     */
    public bool $excel_columns;

    /**
     * ```php
     * $cfg['Export']['excel_null'] = 'NULL';
     * ```
     */
    public string $excel_null;

    /**
     * ```php
     * $cfg['Export']['excel_edition'] = 'win';
     * ```
     *
     * @psalm-var 'win'|'mac_excel2003'|'mac_excel2008'
     */
    public string $excel_edition;

    /**
     * ```php
     * $cfg['Export']['excel_removeCRLF'] = false;
     * ```
     */
    public bool $excel_removeCRLF;

    /**
     * ```php
     * $cfg['Export']['excel_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $excel_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['latex_structure_or_data'] = 'structure_and_data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $latex_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['latex_columns'] = true;
     * ```
     */
    public bool $latex_columns;

    /**
     * ```php
     * $cfg['Export']['latex_relation'] = true;
     * ```
     */
    public bool $latex_relation;

    /**
     * ```php
     * $cfg['Export']['latex_comments'] = true;
     * ```
     */
    public bool $latex_comments;

    /**
     * ```php
     * $cfg['Export']['latex_mime'] = true;
     * ```
     */
    public bool $latex_mime;

    /**
     * ```php
     * $cfg['Export']['latex_null'] = '\textit{NULL}';
     * ```
     */
    public string $latex_null;

    /**
     * ```php
     * $cfg['Export']['latex_caption'] = true;
     * ```
     */
    public bool $latex_caption;

    /**
     * ```php
     * $cfg['Export']['latex_structure_caption'] = 'strLatexStructure';
     * ```
     */
    public string $latex_structure_caption;

    /**
     * ```php
     * $cfg['Export']['latex_structure_continued_caption'] = 'strLatexStructure strLatexContinued';
     * ```
     */
    public string $latex_structure_continued_caption;

    /**
     * ```php
     * $cfg['Export']['latex_data_caption'] = 'strLatexContent';
     * ```
     */
    public string $latex_data_caption;

    /**
     * ```php
     * $cfg['Export']['latex_data_continued_caption'] = 'strLatexContent strLatexContinued';
     * ```
     */
    public string $latex_data_continued_caption;

    /**
     * ```php
     * $cfg['Export']['latex_data_label'] = 'tab:@TABLE@-data';
     * ```
     */
    public string $latex_data_label;

    /**
     * ```php
     * $cfg['Export']['latex_structure_label'] = 'tab:@TABLE@-structure';
     * ```
     */
    public string $latex_structure_label;

    /**
     * ```php
     * $cfg['Export']['mediawiki_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $mediawiki_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['mediawiki_caption'] = true;
     * ```
     */
    public bool $mediawiki_caption;

    /**
     * ```php
     * $cfg['Export']['mediawiki_headers'] = true;
     * ```
     */
    public bool $mediawiki_headers;

    /**
     * ```php
     * $cfg['Export']['ods_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $ods_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['pdf_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $pdf_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['phparray_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $phparray_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['json_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $json_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['json_pretty_print'] = false;
     * ```
     */
    public bool $json_pretty_print;

    /**
     * ```php
     * $cfg['Export']['json_unicode'] = true;
     * ```
     */
    public bool $json_unicode;

    /**
     * ```php
     * $cfg['Export']['sql_structure_or_data'] = 'structure_and_data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $sql_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['sql_compatibility'] = 'NONE';
     * ```
     *
     * @psalm-var 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    public string $sql_compatibility;

    /**
     * Whether to include comments in SQL export.
     *
     * ```php
     * $cfg['Export']['sql_include_comments'] = true;
     * ```
     */
    public bool $sql_include_comments;

    /**
     * ```php
     * $cfg['Export']['sql_disable_fk'] = false;
     * ```
     */
    public bool $sql_disable_fk;

    /**
     * ```php
     * $cfg['Export']['sql_views_as_tables'] = false;
     * ```
     */
    public bool $sql_views_as_tables;

    /**
     * ```php
     * $cfg['Export']['sql_metadata'] = false;
     * ```
     */
    public bool $sql_metadata;

    /**
     * ```php
     * $cfg['Export']['sql_use_transaction'] = true;
     * ```
     */
    public bool $sql_use_transaction;

    /**
     * ```php
     * $cfg['Export']['sql_create_database'] = false;
     * ```
     */
    public bool $sql_create_database;

    /**
     * ```php
     * $cfg['Export']['sql_drop_database'] = false;
     * ```
     */
    public bool $sql_drop_database;

    /**
     * ```php
     * $cfg['Export']['sql_drop_table'] = false;
     * ```
     */
    public bool $sql_drop_table;

    /**
     * true by default for correct behavior when dealing with exporting
     * of VIEWs and the stand-in table
     *
     * ```php
     * $cfg['Export']['sql_if_not_exists'] = false;
     * ```
     */
    public bool $sql_if_not_exists;

    /**
     * ```php
     * $cfg['Export']['sql_view_current_user'] = false;
     * ```
     */
    public bool $sql_view_current_user;

    /**
     * ```php
     * $cfg['Export']['sql_or_replace_view'] = false;
     * ```
     */
    public bool $sql_or_replace_view;

    /**
     * ```php
     * $cfg['Export']['sql_procedure_function'] = true;
     * ```
     */
    public bool $sql_procedure_function;

    /**
     * ```php
     * $cfg['Export']['sql_create_table'] = true;
     * ```
     */
    public bool $sql_create_table;

    /**
     * ```php
     * $cfg['Export']['sql_create_view'] = true;
     * ```
     */
    public bool $sql_create_view;

    /**
     * ```php
     * $cfg['Export']['sql_create_trigger'] = true;
     * ```
     */
    public bool $sql_create_trigger;

    /**
     * ```php
     * $cfg['Export']['sql_auto_increment'] = true;
     * ```
     */
    public bool $sql_auto_increment;

    /**
     * ```php
     * $cfg['Export']['sql_backquotes'] = true;
     * ```
     */
    public bool $sql_backquotes;

    /**
     * ```php
     * $cfg['Export']['sql_dates'] = false;
     * ```
     */
    public bool $sql_dates;

    /**
     * ```php
     * $cfg['Export']['sql_relation'] = false;
     * ```
     */
    public bool $sql_relation;

    /**
     * ```php
     * $cfg['Export']['sql_truncate'] = false;
     * ```
     */
    public bool $sql_truncate;

    /**
     * ```php
     * $cfg['Export']['sql_delayed'] = false;
     * ```
     */
    public bool $sql_delayed;

    /**
     * ```php
     * $cfg['Export']['sql_ignore'] = false;
     * ```
     */
    public bool $sql_ignore;

    /**
     * Export time in UTC.
     *
     * ```php
     * $cfg['Export']['sql_utc_time'] = true;
     * ```
     */
    public bool $sql_utc_time;

    /**
     * ```php
     * $cfg['Export']['sql_hex_for_binary'] = true;
     * ```
     */
    public bool $sql_hex_for_binary;

    /**
     * ```php
     * $cfg['Export']['sql_type'] = 'INSERT';
     * ```
     *
     * @psalm-var 'INSERT'|'UPDATE'|'REPLACE'
     */
    public string $sql_type;

    /**
     * ```php
     * $cfg['Export']['sql_max_query_size'] = 50000;
     * ```
     *
     * @psalm-var 0|positive-int
     */
    public int $sql_max_query_size;

    /**
     * ```php
     * $cfg['Export']['sql_mime'] = false;
     * ```
     */
    public bool $sql_mime;

    /**
     * \n is replaced by new line
     *
     * ```php
     * $cfg['Export']['sql_header_comment'] = '';
     * ```
     */
    public string $sql_header_comment;

    /**
     * Whether to use complete inserts, extended inserts, both, or neither
     *
     * ```php
     * $cfg['Export']['sql_insert_syntax'] = 'both';
     * ```
     *
     * @psalm-var 'complete'|'extended'|'both'|'none'
     */
    public string $sql_insert_syntax;

    /**
     * ```php
     * $cfg['Export']['pdf_report_title'] = '';
     * ```
     */
    public string $pdf_report_title;

    /**
     * ```php
     * $cfg['Export']['xml_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $xml_structure_or_data;

    /**
     * Export schema for each structure
     *
     * ```php
     * $cfg['Export']['xml_export_struc'] = true;
     * ```
     */
    public bool $xml_export_struc;

    /**
     * Export events
     *
     * ```php
     * $cfg['Export']['xml_export_events'] = true;
     * ```
     */
    public bool $xml_export_events;

    /**
     * Export functions
     *
     * ```php
     * $cfg['Export']['xml_export_functions'] = true;
     * ```
     */
    public bool $xml_export_functions;

    /**
     * Export procedures
     *
     * ```php
     * $cfg['Export']['xml_export_procedures'] = true;
     * ```
     */
    public bool $xml_export_procedures;

    /**
     * Export schema for each table
     *
     * ```php
     * $cfg['Export']['xml_export_tables'] = true;
     * ```
     */
    public bool $xml_export_tables;

    /**
     * Export triggers
     *
     * ```php
     * $cfg['Export']['xml_export_triggers'] = true;
     * ```
     */
    public bool $xml_export_triggers;

    /**
     * Export views
     *
     * ```php
     * $cfg['Export']['xml_export_views'] = true;
     * ```
     */
    public bool $xml_export_views;

    /**
     * Export contents data
     *
     * ```php
     * $cfg['Export']['xml_export_contents'] = true;
     * ```
     */
    public bool $xml_export_contents;

    /**
     * ```php
     * $cfg['Export']['yaml_structure_or_data'] = 'data';
     * ```
     *
     * @psalm-var StructureOrDataType
     */
    public string $yaml_structure_or_data;

    /**
     * ```php
     * $cfg['Export']['remove_definer_from_definitions'] = false;
     * ```
     */
    public bool $remove_definer_from_definitions;

    /** @param array<int|string, mixed> $export */
    public function __construct(array $export = [])
    {
        $this->format = $this->setFormat($export);
        $this->method = $this->setMethod($export);
        $this->compression = $this->setCompression($export);
        $this->lock_tables = $this->setLockTables($export);
        $this->as_separate_files = $this->setAsSeparateFiles($export);
        $this->asfile = $this->setAsFile($export);
        $this->charset = $this->setCharset($export);
        $this->onserver = $this->setOnServer($export);
        $this->onserver_overwrite = $this->setOnServerOverwrite($export);
        $this->quick_export_onserver = $this->setQuickExportOnServer($export);
        $this->quick_export_onserver_overwrite = $this->setQuickExportOnServerOverwrite($export);
        $this->remember_file_template = $this->setRememberFileTemplate($export);
        $this->file_template_table = $this->setFileTemplateTable($export);
        $this->file_template_database = $this->setFileTemplateDatabase($export);
        $this->file_template_server = $this->setFileTemplateServer($export);
        $this->codegen_structure_or_data = $this->setCodegenStructureOrData($export);
        $this->codegen_format = $this->setCodegenFormat($export);
        $this->ods_columns = $this->setOdsColumns($export);
        $this->ods_null = $this->setOdsNull($export);
        $this->odt_structure_or_data = $this->setOdtStructureOrData($export);
        $this->odt_columns = $this->setOdtColumns($export);
        $this->odt_relation = $this->setOdtRelation($export);
        $this->odt_comments = $this->setOdtComments($export);
        $this->odt_mime = $this->setOdtMime($export);
        $this->odt_null = $this->setOdtNull($export);
        $this->htmlword_structure_or_data = $this->setHtmlwordStructureOrData($export);
        $this->htmlword_columns = $this->setHtmlwordColumns($export);
        $this->htmlword_null = $this->setHtmlwordNull($export);
        $this->texytext_structure_or_data = $this->setTexytextStructureOrData($export);
        $this->texytext_columns = $this->setTexytextColumns($export);
        $this->texytext_null = $this->setTexytextNull($export);
        $this->csv_columns = $this->setCsvColumns($export);
        $this->csv_structure_or_data = $this->setCsvStructureOrData($export);
        $this->csv_null = $this->setCsvNull($export);
        $this->csv_separator = $this->setCsvSeparator($export);
        $this->csv_enclosed = $this->setCsvEnclosed($export);
        $this->csv_escaped = $this->setCsvEscaped($export);
        $this->csv_terminated = $this->setCsvTerminated($export);
        $this->csv_removeCRLF = $this->setCsvRemoveCRLF($export);
        $this->excel_columns = $this->setExcelColumns($export);
        $this->excel_null = $this->setExcelNull($export);
        $this->excel_edition = $this->setExcelEdition($export);
        $this->excel_removeCRLF = $this->setExcelRemoveCRLF($export);
        $this->excel_structure_or_data = $this->setExcelStructureOrData($export);
        $this->latex_structure_or_data = $this->setLatexStructureOrData($export);
        $this->latex_columns = $this->setLatexColumns($export);
        $this->latex_relation = $this->setLatexRelation($export);
        $this->latex_comments = $this->setLatexComments($export);
        $this->latex_mime = $this->setLatexMime($export);
        $this->latex_null = $this->setLatexNull($export);
        $this->latex_caption = $this->setLatexCaption($export);
        $this->latex_structure_caption = $this->setLatexStructureCaption($export);
        $this->latex_structure_continued_caption = $this->setLatexStructureContinuedCaption($export);
        $this->latex_data_caption = $this->setLatexDataCaption($export);
        $this->latex_data_continued_caption = $this->setLatexDataContinuedCaption($export);
        $this->latex_data_label = $this->setLatexDataLabel($export);
        $this->latex_structure_label = $this->setLatexStructureLabel($export);
        $this->mediawiki_structure_or_data = $this->setMediawikiStructureOrData($export);
        $this->mediawiki_caption = $this->setMediawikiCaption($export);
        $this->mediawiki_headers = $this->setMediawikiHeaders($export);
        $this->ods_structure_or_data = $this->setOdsStructureOrData($export);
        $this->pdf_structure_or_data = $this->setPdfStructureOrData($export);
        $this->phparray_structure_or_data = $this->setPhparrayStructureOrData($export);
        $this->json_structure_or_data = $this->setJsonStructureOrData($export);
        $this->json_pretty_print = $this->setJsonPrettyPrint($export);
        $this->json_unicode = $this->setJsonUnicode($export);
        $this->sql_structure_or_data = $this->setSqlStructureOrData($export);
        $this->sql_compatibility = $this->setSqlCompatibility($export);
        $this->sql_include_comments = $this->setSqlIncludeComments($export);
        $this->sql_disable_fk = $this->setSqlDisableFk($export);
        $this->sql_views_as_tables = $this->setSqlViewsAsTables($export);
        $this->sql_metadata = $this->setSqlMetadata($export);
        $this->sql_use_transaction = $this->setSqlUseTransaction($export);
        $this->sql_create_database = $this->setSqlCreateDatabase($export);
        $this->sql_drop_database = $this->setSqlDropDatabase($export);
        $this->sql_drop_table = $this->setSqlDropTable($export);
        $this->sql_if_not_exists = $this->setSqlIfNotExists($export);
        $this->sql_view_current_user = $this->setSqlViewCurrentUser($export);
        $this->sql_or_replace_view = $this->setSqlOrReplaceView($export);
        $this->sql_procedure_function = $this->setSqlProcedureFunction($export);
        $this->sql_create_table = $this->setSqlCreateTable($export);
        $this->sql_create_view = $this->setSqlCreateView($export);
        $this->sql_create_trigger = $this->setSqlCreateTrigger($export);
        $this->sql_auto_increment = $this->setSqlAutoIncrement($export);
        $this->sql_backquotes = $this->setSqlBackquotes($export);
        $this->sql_dates = $this->setSqlDates($export);
        $this->sql_relation = $this->setSqlRelation($export);
        $this->sql_truncate = $this->setSqlTruncate($export);
        $this->sql_delayed = $this->setSqlDelayed($export);
        $this->sql_ignore = $this->setSqlIgnore($export);
        $this->sql_utc_time = $this->setSqlUtcTime($export);
        $this->sql_hex_for_binary = $this->setSqlHexForBinary($export);
        $this->sql_type = $this->setSqlType($export);
        $this->sql_max_query_size = $this->setSqlMaxQuerySize($export);
        $this->sql_mime = $this->setSqlMime($export);
        $this->sql_header_comment = $this->setSqlHeaderComment($export);
        $this->sql_insert_syntax = $this->setSqlInsertSyntax($export);
        $this->pdf_report_title = $this->setPdfReportTitle($export);
        $this->xml_structure_or_data = $this->setXmlStructureOrData($export);
        $this->xml_export_struc = $this->setXmlExportStruc($export);
        $this->xml_export_events = $this->setXmlExportEvents($export);
        $this->xml_export_functions = $this->setXmlExportFunctions($export);
        $this->xml_export_procedures = $this->setXmlExportProcedures($export);
        $this->xml_export_tables = $this->setXmlExportTables($export);
        $this->xml_export_triggers = $this->setXmlExportTriggers($export);
        $this->xml_export_views = $this->setXmlExportViews($export);
        $this->xml_export_contents = $this->setXmlExportContents($export);
        $this->yaml_structure_or_data = $this->setYamlStructureOrData($export);
        $this->remove_definer_from_definitions = $this->setRemoveDefinerClause($export);
    }

    /** @return array<string, string|bool|int> */
    public function asArray(): array
    {
        return [
            'format' => $this->format,
            'method' => $this->method,
            'compression' => $this->compression,
            'lock_tables' => $this->lock_tables,
            'as_separate_files' => $this->as_separate_files,
            'asfile' => $this->asfile,
            'charset' => $this->charset,
            'onserver' => $this->onserver,
            'onserver_overwrite' => $this->onserver_overwrite,
            'quick_export_onserver' => $this->quick_export_onserver,
            'quick_export_onserver_overwrite' => $this->quick_export_onserver_overwrite,
            'remember_file_template' => $this->remember_file_template,
            'file_template_table' => $this->file_template_table,
            'file_template_database' => $this->file_template_database,
            'file_template_server' => $this->file_template_server,
            'codegen_structure_or_data' => $this->codegen_structure_or_data,
            'codegen_format' => $this->codegen_format,
            'ods_columns' => $this->ods_columns,
            'ods_null' => $this->ods_null,
            'odt_structure_or_data' => $this->odt_structure_or_data,
            'odt_columns' => $this->odt_columns,
            'odt_relation' => $this->odt_relation,
            'odt_comments' => $this->odt_comments,
            'odt_mime' => $this->odt_mime,
            'odt_null' => $this->odt_null,
            'htmlword_structure_or_data' => $this->htmlword_structure_or_data,
            'htmlword_columns' => $this->htmlword_columns,
            'htmlword_null' => $this->htmlword_null,
            'texytext_structure_or_data' => $this->texytext_structure_or_data,
            'texytext_columns' => $this->texytext_columns,
            'texytext_null' => $this->texytext_null,
            'csv_columns' => $this->csv_columns,
            'csv_structure_or_data' => $this->csv_structure_or_data,
            'csv_null' => $this->csv_null,
            'csv_separator' => $this->csv_separator,
            'csv_enclosed' => $this->csv_enclosed,
            'csv_escaped' => $this->csv_escaped,
            'csv_terminated' => $this->csv_terminated,
            'csv_removeCRLF' => $this->csv_removeCRLF,
            'excel_columns' => $this->excel_columns,
            'excel_null' => $this->excel_null,
            'excel_edition' => $this->excel_edition,
            'excel_removeCRLF' => $this->excel_removeCRLF,
            'excel_structure_or_data' => $this->excel_structure_or_data,
            'latex_structure_or_data' => $this->latex_structure_or_data,
            'latex_columns' => $this->latex_columns,
            'latex_relation' => $this->latex_relation,
            'latex_comments' => $this->latex_comments,
            'latex_mime' => $this->latex_mime,
            'latex_null' => $this->latex_null,
            'latex_caption' => $this->latex_caption,
            'latex_structure_caption' => $this->latex_structure_caption,
            'latex_structure_continued_caption' => $this->latex_structure_continued_caption,
            'latex_data_caption' => $this->latex_data_caption,
            'latex_data_continued_caption' => $this->latex_data_continued_caption,
            'latex_data_label' => $this->latex_data_label,
            'latex_structure_label' => $this->latex_structure_label,
            'mediawiki_structure_or_data' => $this->mediawiki_structure_or_data,
            'mediawiki_caption' => $this->mediawiki_caption,
            'mediawiki_headers' => $this->mediawiki_headers,
            'ods_structure_or_data' => $this->ods_structure_or_data,
            'pdf_structure_or_data' => $this->pdf_structure_or_data,
            'phparray_structure_or_data' => $this->phparray_structure_or_data,
            'json_structure_or_data' => $this->json_structure_or_data,
            'json_pretty_print' => $this->json_pretty_print,
            'json_unicode' => $this->json_unicode,
            'sql_structure_or_data' => $this->sql_structure_or_data,
            'sql_compatibility' => $this->sql_compatibility,
            'sql_include_comments' => $this->sql_include_comments,
            'sql_disable_fk' => $this->sql_disable_fk,
            'sql_views_as_tables' => $this->sql_views_as_tables,
            'sql_metadata' => $this->sql_metadata,
            'sql_use_transaction' => $this->sql_use_transaction,
            'sql_create_database' => $this->sql_create_database,
            'sql_drop_database' => $this->sql_drop_database,
            'sql_drop_table' => $this->sql_drop_table,
            'sql_if_not_exists' => $this->sql_if_not_exists,
            'sql_view_current_user' => $this->sql_view_current_user,
            'sql_or_replace_view' => $this->sql_or_replace_view,
            'sql_procedure_function' => $this->sql_procedure_function,
            'sql_create_table' => $this->sql_create_table,
            'sql_create_view' => $this->sql_create_view,
            'sql_create_trigger' => $this->sql_create_trigger,
            'sql_auto_increment' => $this->sql_auto_increment,
            'sql_backquotes' => $this->sql_backquotes,
            'sql_dates' => $this->sql_dates,
            'sql_relation' => $this->sql_relation,
            'sql_truncate' => $this->sql_truncate,
            'sql_delayed' => $this->sql_delayed,
            'sql_ignore' => $this->sql_ignore,
            'sql_utc_time' => $this->sql_utc_time,
            'sql_hex_for_binary' => $this->sql_hex_for_binary,
            'sql_type' => $this->sql_type,
            'sql_max_query_size' => $this->sql_max_query_size,
            'sql_mime' => $this->sql_mime,
            'sql_header_comment' => $this->sql_header_comment,
            'sql_insert_syntax' => $this->sql_insert_syntax,
            'pdf_report_title' => $this->pdf_report_title,
            'xml_structure_or_data' => $this->xml_structure_or_data,
            'xml_export_struc' => $this->xml_export_struc,
            'xml_export_events' => $this->xml_export_events,
            'xml_export_functions' => $this->xml_export_functions,
            'xml_export_procedures' => $this->xml_export_procedures,
            'xml_export_tables' => $this->xml_export_tables,
            'xml_export_triggers' => $this->xml_export_triggers,
            'xml_export_views' => $this->xml_export_views,
            'xml_export_contents' => $this->xml_export_contents,
            'yaml_structure_or_data' => $this->yaml_structure_or_data,
            'remove_definer_from_definitions' => $this->remove_definer_from_definitions,
        ];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'codegen'|'csv'|'excel'|'htmlexcel'|'htmlword'|'latex'|'ods'|'odt'|'pdf'|'sql'|'texytext'|'xml'|'yaml'
     */
    private function setFormat(array $export): string
    {
        if (
            ! isset($export['format']) || ! in_array($export['format'], [
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
            return 'sql';
        }

        return $export['format'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'quick'|'custom'|'custom-no-form'
     */
    private function setMethod(array $export): string
    {
        if (! isset($export['method']) || ! in_array($export['method'], ['custom', 'custom-no-form'], true)) {
            return 'quick';
        }

        return $export['method'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'none'|'zip'|'gzip'
     */
    private function setCompression(array $export): string
    {
        if (! isset($export['compression']) || ! in_array($export['compression'], ['zip', 'gzip'], true)) {
            return 'none';
        }

        return $export['compression'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLockTables(array $export): bool
    {
        if (! isset($export['lock_tables'])) {
            return false;
        }

        return (bool) $export['lock_tables'];
    }

    /** @param array<int|string, mixed> $export */
    private function setAsSeparateFiles(array $export): bool
    {
        if (! isset($export['as_separate_files'])) {
            return false;
        }

        return (bool) $export['as_separate_files'];
    }

    /** @param array<int|string, mixed> $export */
    private function setAsFile(array $export): bool
    {
        if (! isset($export['asfile'])) {
            return true;
        }

        return (bool) $export['asfile'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCharset(array $export): string
    {
        if (! isset($export['charset'])) {
            return '';
        }

        return (string) $export['charset'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOnServer(array $export): bool
    {
        if (! isset($export['onserver'])) {
            return false;
        }

        return (bool) $export['onserver'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOnServerOverwrite(array $export): bool
    {
        if (! isset($export['onserver_overwrite'])) {
            return false;
        }

        return (bool) $export['onserver_overwrite'];
    }

    /** @param array<int|string, mixed> $export */
    private function setQuickExportOnServer(array $export): bool
    {
        if (! isset($export['quick_export_onserver'])) {
            return false;
        }

        return (bool) $export['quick_export_onserver'];
    }

    /** @param array<int|string, mixed> $export */
    private function setQuickExportOnServerOverwrite(array $export): bool
    {
        if (! isset($export['quick_export_onserver_overwrite'])) {
            return false;
        }

        return (bool) $export['quick_export_onserver_overwrite'];
    }

    /** @param array<int|string, mixed> $export */
    private function setRememberFileTemplate(array $export): bool
    {
        if (! isset($export['remember_file_template'])) {
            return true;
        }

        return (bool) $export['remember_file_template'];
    }

    /** @param array<int|string, mixed> $export */
    private function setFileTemplateTable(array $export): string
    {
        if (! isset($export['file_template_table'])) {
            return '@TABLE@';
        }

        return (string) $export['file_template_table'];
    }

    /** @param array<int|string, mixed> $export */
    private function setFileTemplateDatabase(array $export): string
    {
        if (! isset($export['file_template_database'])) {
            return '@DATABASE@';
        }

        return (string) $export['file_template_database'];
    }

    /** @param array<int|string, mixed> $export */
    private function setFileTemplateServer(array $export): string
    {
        if (! isset($export['file_template_server'])) {
            return '@SERVER@';
        }

        return (string) $export['file_template_server'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setCodegenStructureOrData(array $export): string
    {
        if (
            ! isset($export['codegen_structure_or_data'])
            || ! in_array($export['codegen_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['codegen_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 0|1
     */
    private function setCodegenFormat(array $export): int
    {
        if (! isset($export['codegen_format'])) {
            return 0;
        }

        $codegenFormat = (int) $export['codegen_format'];

        return $codegenFormat === 1 ? 1 : 0;
    }

    /** @param array<int|string, mixed> $export */
    private function setOdsColumns(array $export): bool
    {
        if (! isset($export['ods_columns'])) {
            return false;
        }

        return (bool) $export['ods_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdsNull(array $export): string
    {
        if (! isset($export['ods_null'])) {
            return 'NULL';
        }

        return (string) $export['ods_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setOdtStructureOrData(array $export): string
    {
        if (
            ! isset($export['odt_structure_or_data'])
            || ! in_array($export['odt_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['odt_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdtColumns(array $export): bool
    {
        if (! isset($export['odt_columns'])) {
            return true;
        }

        return (bool) $export['odt_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdtRelation(array $export): bool
    {
        if (! isset($export['odt_relation'])) {
            return true;
        }

        return (bool) $export['odt_relation'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdtComments(array $export): bool
    {
        if (! isset($export['odt_comments'])) {
            return true;
        }

        return (bool) $export['odt_comments'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdtMime(array $export): bool
    {
        if (! isset($export['odt_mime'])) {
            return true;
        }

        return (bool) $export['odt_mime'];
    }

    /** @param array<int|string, mixed> $export */
    private function setOdtNull(array $export): string
    {
        if (! isset($export['odt_null'])) {
            return 'NULL';
        }

        return (string) $export['odt_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setHtmlwordStructureOrData(array $export): string
    {
        if (
            ! isset($export['htmlword_structure_or_data'])
            || ! in_array($export['htmlword_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['htmlword_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setHtmlwordColumns(array $export): bool
    {
        if (! isset($export['htmlword_columns'])) {
            return false;
        }

        return (bool) $export['htmlword_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setHtmlwordNull(array $export): string
    {
        if (! isset($export['htmlword_null'])) {
            return 'NULL';
        }

        return (string) $export['htmlword_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setTexytextStructureOrData(array $export): string
    {
        if (
            ! isset($export['texytext_structure_or_data'])
            || ! in_array($export['texytext_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['texytext_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setTexytextColumns(array $export): bool
    {
        if (! isset($export['texytext_columns'])) {
            return false;
        }

        return (bool) $export['texytext_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setTexytextNull(array $export): string
    {
        if (! isset($export['texytext_null'])) {
            return 'NULL';
        }

        return (string) $export['texytext_null'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvColumns(array $export): bool
    {
        if (! isset($export['csv_columns'])) {
            return true;
        }

        return (bool) $export['csv_columns'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setCsvStructureOrData(array $export): string
    {
        if (
            ! isset($export['csv_structure_or_data'])
            || ! in_array($export['csv_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['csv_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvNull(array $export): string
    {
        if (! isset($export['csv_null'])) {
            return 'NULL';
        }

        return (string) $export['csv_null'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvSeparator(array $export): string
    {
        if (! isset($export['csv_separator'])) {
            return ',';
        }

        return (string) $export['csv_separator'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvEnclosed(array $export): string
    {
        if (! isset($export['csv_enclosed'])) {
            return '"';
        }

        return (string) $export['csv_enclosed'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvEscaped(array $export): string
    {
        if (! isset($export['csv_escaped'])) {
            return '"';
        }

        return (string) $export['csv_escaped'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvTerminated(array $export): string
    {
        if (! isset($export['csv_terminated'])) {
            return 'AUTO';
        }

        return (string) $export['csv_terminated'];
    }

    /** @param array<int|string, mixed> $export */
    private function setCsvRemoveCRLF(array $export): bool
    {
        if (! isset($export['csv_removeCRLF'])) {
            return false;
        }

        return (bool) $export['csv_removeCRLF'];
    }

    /** @param array<int|string, mixed> $export */
    private function setExcelColumns(array $export): bool
    {
        if (! isset($export['excel_columns'])) {
            return true;
        }

        return (bool) $export['excel_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setExcelNull(array $export): string
    {
        if (! isset($export['excel_null'])) {
            return 'NULL';
        }

        return (string) $export['excel_null'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'win'|'mac_excel2003'|'mac_excel2008'
     */
    private function setExcelEdition(array $export): string
    {
        if (
            ! isset($export['excel_edition'])
            || ! in_array($export['excel_edition'], ['mac_excel2003', 'mac_excel2008'], true)
        ) {
            return 'win';
        }

        return $export['excel_edition'];
    }

    /** @param array<int|string, mixed> $export */
    private function setExcelRemoveCRLF(array $export): bool
    {
        if (! isset($export['excel_removeCRLF'])) {
            return false;
        }

        return (bool) $export['excel_removeCRLF'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setExcelStructureOrData(array $export): string
    {
        if (
            ! isset($export['excel_structure_or_data'])
            || ! in_array($export['excel_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['excel_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setLatexStructureOrData(array $export): string
    {
        if (
            ! isset($export['latex_structure_or_data'])
            || ! in_array($export['latex_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['latex_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexColumns(array $export): bool
    {
        if (! isset($export['latex_columns'])) {
            return true;
        }

        return (bool) $export['latex_columns'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexRelation(array $export): bool
    {
        if (! isset($export['latex_relation'])) {
            return true;
        }

        return (bool) $export['latex_relation'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexComments(array $export): bool
    {
        if (! isset($export['latex_comments'])) {
            return true;
        }

        return (bool) $export['latex_comments'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexMime(array $export): bool
    {
        if (! isset($export['latex_mime'])) {
            return true;
        }

        return (bool) $export['latex_mime'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexNull(array $export): string
    {
        if (! isset($export['latex_null'])) {
            return '\textit{NULL}';
        }

        return (string) $export['latex_null'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexCaption(array $export): bool
    {
        if (! isset($export['latex_caption'])) {
            return true;
        }

        return (bool) $export['latex_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexStructureCaption(array $export): string
    {
        if (! isset($export['latex_structure_caption'])) {
            return 'strLatexStructure';
        }

        return (string) $export['latex_structure_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexStructureContinuedCaption(array $export): string
    {
        if (! isset($export['latex_structure_continued_caption'])) {
            return 'strLatexStructure strLatexContinued';
        }

        return (string) $export['latex_structure_continued_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexDataCaption(array $export): string
    {
        if (! isset($export['latex_data_caption'])) {
            return 'strLatexContent';
        }

        return (string) $export['latex_data_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexDataContinuedCaption(array $export): string
    {
        if (! isset($export['latex_data_continued_caption'])) {
            return 'strLatexContent strLatexContinued';
        }

        return (string) $export['latex_data_continued_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexDataLabel(array $export): string
    {
        if (! isset($export['latex_data_label'])) {
            return 'tab:@TABLE@-data';
        }

        return (string) $export['latex_data_label'];
    }

    /** @param array<int|string, mixed> $export */
    private function setLatexStructureLabel(array $export): string
    {
        if (! isset($export['latex_structure_label'])) {
            return 'tab:@TABLE@-structure';
        }

        return (string) $export['latex_structure_label'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setMediawikiStructureOrData(array $export): string
    {
        if (
            ! isset($export['mediawiki_structure_or_data'])
            || ! in_array($export['mediawiki_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['mediawiki_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setMediawikiCaption(array $export): bool
    {
        if (! isset($export['mediawiki_caption'])) {
            return true;
        }

        return (bool) $export['mediawiki_caption'];
    }

    /** @param array<int|string, mixed> $export */
    private function setMediawikiHeaders(array $export): bool
    {
        if (! isset($export['mediawiki_headers'])) {
            return true;
        }

        return (bool) $export['mediawiki_headers'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setOdsStructureOrData(array $export): string
    {
        if (
            ! isset($export['ods_structure_or_data'])
            || ! in_array($export['ods_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['ods_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setPdfStructureOrData(array $export): string
    {
        if (
            ! isset($export['pdf_structure_or_data'])
            || ! in_array($export['pdf_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['pdf_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setPhparrayStructureOrData(array $export): string
    {
        if (
            ! isset($export['phparray_structure_or_data'])
            || ! in_array($export['phparray_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['phparray_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setJsonStructureOrData(array $export): string
    {
        if (
            ! isset($export['json_structure_or_data'])
            || ! in_array($export['json_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['json_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setJsonPrettyPrint(array $export): bool
    {
        if (! isset($export['json_pretty_print'])) {
            return false;
        }

        return (bool) $export['json_pretty_print'];
    }

    /** @param array<int|string, mixed> $export */
    private function setJsonUnicode(array $export): bool
    {
        if (! isset($export['json_unicode'])) {
            return true;
        }

        return (bool) $export['json_unicode'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setSqlStructureOrData(array $export): string
    {
        if (
            ! isset($export['sql_structure_or_data'])
            || ! in_array($export['sql_structure_or_data'], ['structure', 'data'], true)
        ) {
            return 'structure_and_data';
        }

        return $export['sql_structure_or_data'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'NONE'|'ANSI'|'DB2'|'MAXDB'|'MYSQL323'|'MYSQL40'|'MSSQL'|'ORACLE'|'TRADITIONAL'
     */
    private function setSqlCompatibility(array $export): string
    {
        if (
            ! isset($export['sql_compatibility']) || ! in_array($export['sql_compatibility'], [
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
            return 'NONE';
        }

        return $export['sql_compatibility'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlIncludeComments(array $export): bool
    {
        if (! isset($export['sql_include_comments'])) {
            return true;
        }

        return (bool) $export['sql_include_comments'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlDisableFk(array $export): bool
    {
        if (! isset($export['sql_disable_fk'])) {
            return false;
        }

        return (bool) $export['sql_disable_fk'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlViewsAsTables(array $export): bool
    {
        if (! isset($export['sql_views_as_tables'])) {
            return false;
        }

        return (bool) $export['sql_views_as_tables'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlMetadata(array $export): bool
    {
        if (! isset($export['sql_metadata'])) {
            return false;
        }

        return (bool) $export['sql_metadata'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlUseTransaction(array $export): bool
    {
        if (! isset($export['sql_use_transaction'])) {
            return true;
        }

        return (bool) $export['sql_use_transaction'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlCreateDatabase(array $export): bool
    {
        if (! isset($export['sql_create_database'])) {
            return false;
        }

        return (bool) $export['sql_create_database'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlDropDatabase(array $export): bool
    {
        if (! isset($export['sql_drop_database'])) {
            return false;
        }

        return (bool) $export['sql_drop_database'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlDropTable(array $export): bool
    {
        if (! isset($export['sql_drop_table'])) {
            return false;
        }

        return (bool) $export['sql_drop_table'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlIfNotExists(array $export): bool
    {
        if (! isset($export['sql_if_not_exists'])) {
            return false;
        }

        return (bool) $export['sql_if_not_exists'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlViewCurrentUser(array $export): bool
    {
        if (! isset($export['sql_view_current_user'])) {
            return false;
        }

        return (bool) $export['sql_view_current_user'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlOrReplaceView(array $export): bool
    {
        if (! isset($export['sql_or_replace_view'])) {
            return false;
        }

        return (bool) $export['sql_or_replace_view'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlProcedureFunction(array $export): bool
    {
        if (! isset($export['sql_procedure_function'])) {
            return true;
        }

        return (bool) $export['sql_procedure_function'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlCreateTable(array $export): bool
    {
        if (! isset($export['sql_create_table'])) {
            return true;
        }

        return (bool) $export['sql_create_table'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlCreateView(array $export): bool
    {
        if (! isset($export['sql_create_view'])) {
            return true;
        }

        return (bool) $export['sql_create_view'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlCreateTrigger(array $export): bool
    {
        if (! isset($export['sql_create_trigger'])) {
            return true;
        }

        return (bool) $export['sql_create_trigger'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlAutoIncrement(array $export): bool
    {
        if (! isset($export['sql_auto_increment'])) {
            return true;
        }

        return (bool) $export['sql_auto_increment'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlBackquotes(array $export): bool
    {
        if (! isset($export['sql_backquotes'])) {
            return true;
        }

        return (bool) $export['sql_backquotes'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlDates(array $export): bool
    {
        if (! isset($export['sql_dates'])) {
            return false;
        }

        return (bool) $export['sql_dates'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlRelation(array $export): bool
    {
        if (! isset($export['sql_relation'])) {
            return false;
        }

        return (bool) $export['sql_relation'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlTruncate(array $export): bool
    {
        if (! isset($export['sql_truncate'])) {
            return false;
        }

        return (bool) $export['sql_truncate'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlDelayed(array $export): bool
    {
        if (! isset($export['sql_delayed'])) {
            return false;
        }

        return (bool) $export['sql_delayed'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlIgnore(array $export): bool
    {
        if (! isset($export['sql_ignore'])) {
            return false;
        }

        return (bool) $export['sql_ignore'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlUtcTime(array $export): bool
    {
        if (! isset($export['sql_utc_time'])) {
            return true;
        }

        return (bool) $export['sql_utc_time'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlHexForBinary(array $export): bool
    {
        if (! isset($export['sql_hex_for_binary'])) {
            return true;
        }

        return (bool) $export['sql_hex_for_binary'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'INSERT'|'UPDATE'|'REPLACE'
     */
    private function setSqlType(array $export): string
    {
        if (! isset($export['sql_type']) || ! in_array($export['sql_type'], ['UPDATE', 'REPLACE'], true)) {
            return 'INSERT';
        }

        return $export['sql_type'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 0|positive-int
     */
    private function setSqlMaxQuerySize(array $export): int
    {
        if (! isset($export['sql_max_query_size'])) {
            return 50000;
        }

        $maxQuerySize = (int) $export['sql_max_query_size'];

        return $maxQuerySize >= 0 ? $maxQuerySize : 50000;
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlMime(array $export): bool
    {
        if (! isset($export['sql_mime'])) {
            return false;
        }

        return (bool) $export['sql_mime'];
    }

    /** @param array<int|string, mixed> $export */
    private function setSqlHeaderComment(array $export): string
    {
        if (! isset($export['sql_header_comment'])) {
            return '';
        }

        return (string) $export['sql_header_comment'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return 'complete'|'extended'|'both'|'none'
     */
    private function setSqlInsertSyntax(array $export): string
    {
        if (
            ! isset($export['sql_insert_syntax'])
            || ! in_array($export['sql_insert_syntax'], ['complete', 'extended', 'none'], true)
        ) {
            return 'both';
        }

        return $export['sql_insert_syntax'];
    }

    /** @param array<int|string, mixed> $export */
    private function setPdfReportTitle(array $export): string
    {
        if (! isset($export['pdf_report_title'])) {
            return '';
        }

        return (string) $export['pdf_report_title'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setXmlStructureOrData(array $export): string
    {
        if (
            ! isset($export['xml_structure_or_data'])
            || ! in_array($export['xml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['xml_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportStruc(array $export): bool
    {
        if (! isset($export['xml_export_struc'])) {
            return true;
        }

        return (bool) $export['xml_export_struc'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportEvents(array $export): bool
    {
        if (! isset($export['xml_export_events'])) {
            return true;
        }

        return (bool) $export['xml_export_events'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportFunctions(array $export): bool
    {
        if (! isset($export['xml_export_functions'])) {
            return true;
        }

        return (bool) $export['xml_export_functions'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportProcedures(array $export): bool
    {
        if (! isset($export['xml_export_procedures'])) {
            return true;
        }

        return (bool) $export['xml_export_procedures'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportTables(array $export): bool
    {
        if (! isset($export['xml_export_tables'])) {
            return true;
        }

        return (bool) $export['xml_export_tables'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportTriggers(array $export): bool
    {
        if (! isset($export['xml_export_triggers'])) {
            return true;
        }

        return (bool) $export['xml_export_triggers'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportViews(array $export): bool
    {
        if (! isset($export['xml_export_views'])) {
            return true;
        }

        return (bool) $export['xml_export_views'];
    }

    /** @param array<int|string, mixed> $export */
    private function setXmlExportContents(array $export): bool
    {
        if (! isset($export['xml_export_contents'])) {
            return true;
        }

        return (bool) $export['xml_export_contents'];
    }

    /**
     * @param array<int|string, mixed> $export
     *
     * @psalm-return StructureOrDataType
     */
    private function setYamlStructureOrData(array $export): string
    {
        if (
            ! isset($export['yaml_structure_or_data'])
            || ! in_array($export['yaml_structure_or_data'], ['structure', 'structure_and_data'], true)
        ) {
            return 'data';
        }

        return $export['yaml_structure_or_data'];
    }

    /** @param array<int|string, mixed> $export */
    private function setRemoveDefinerClause(array $export): bool
    {
        if (! isset($export['remove_definer_from_definitions'])) {
            return false;
        }

        return (bool) $export['remove_definer_from_definitions'];
    }
}
