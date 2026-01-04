<?php

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Util;

use function explode;
use function function_exists;
use function in_array;
use function is_array;
use function is_string;
use function urldecode;

final class Options
{
    public function __construct(private Relation $relation, private TemplateModel $templateModel)
    {
    }

    /**
     * Outputs appropriate checked statement for checkbox.
     *
     * @param string $str option name
     */
    private function checkboxCheck(string $str): bool
    {
        $config = Config::getInstance();

        return isset($config->settings['Export'][$str])
            && $config->settings['Export'][$str];
    }

    /**
     * Prints Html For Export Selection Options
     *
     * @return array<int, array{name: string, is_selected: bool}>
     */
    public function getDatabasesForSelectOptions(): array
    {
        /** @var array|string|null $dbSelect */
        $dbSelect = $_POST['db_select'] ?? null;

        // Check if the selected databases are defined in $_POST
        // (from clicking Back button on /export page)
        if (is_string($dbSelect)) {
            $dbSelect = urldecode($dbSelect);
            $dbSelect = explode(',', $dbSelect);
        }

        $databases = [];
        foreach (DatabaseInterface::getInstance()->getDatabaseList() as $currentDb) {
            if (Utilities::isSystemSchema($currentDb, true)) {
                continue;
            }

            $isSelected = false;
            if (is_array($dbSelect)) {
                if (in_array($currentDb, $dbSelect)) {
                    $isSelected = true;
                }
            } else {
                $isSelected = true;
            }

            $databases[] = ['name' => $currentDb, 'is_selected' => $isSelected];
        }

        return $databases;
    }

    /**
     * @param ExportType     $exportType   export type: server|database|table
     * @param string         $db           selected DB
     * @param string         $table        selected table
     * @param string         $sqlQuery     SQL query
     * @param int|string     $numTables    number of tables
     * @param int|string     $unlimNumRows unlimited number of rows
     * @param ExportPlugin[] $exportList
     *
     * @return array<string, mixed>
     */
    public function getOptions(
        ExportType $exportType,
        string $db,
        string $table,
        string $sqlQuery,
        int|string $numTables,
        int|string $unlimNumRows,
        array $exportList,
        mixed $formatParam,
        mixed $whatParam,
    ): array {
        $exportTemplatesFeature = $this->relation->getRelationParameters()->exportTemplatesFeature;

        $templates = [];

        $config = Config::getInstance();
        if ($exportTemplatesFeature !== null) {
            $templates = $this->templateModel->getAll(
                $exportTemplatesFeature->database,
                $exportTemplatesFeature->exportTemplates,
                $config->selectedServer['user'],
                $exportType,
            );

            $templates = is_array($templates) ? $templates : [];
        }

        $dropdown = Plugins::getChoice($exportList, $this->getFormat($formatParam, $whatParam));
        $tableObject = new Table($table, $db, DatabaseInterface::getInstance());
        $rows = [];

        if ($table !== '' && $numTables === 0 && ! $tableObject->isMerge() && $exportType !== ExportType::Raw) {
            $rows = [
                'allrows' => $_POST['allrows'] ?? null,
                'limit_to' => $_POST['limit_to'] ?? null,
                'limit_from' => $_POST['limit_from'] ?? null,
                'unlim_num_rows' => $unlimNumRows,
                'number_of_rows' => $tableObject->countRecords(),
            ];
        }

        $hasAliases = isset($_SESSION['tmpval']['aliases']) && ! Core::emptyRecursive($_SESSION['tmpval']['aliases']);
        $aliases = $_SESSION['tmpval']['aliases'] ?? [];
        unset($_SESSION['tmpval']['aliases']);
        $filenameTemplate = $this->getFileNameTemplate($exportType, $_POST['filename_template'] ?? null);
        $isEncodingSupported = Encoding::isSupported();
        $selectedCompression = $_POST['compression'] ?? $config->config->Export->compression;

        if (
            isset($config->settings['Export']['as_separate_files']) && $config->settings['Export']['as_separate_files']
        ) {
            $selectedCompression = 'zip';
        }

        $hiddenInputs = [
            'db' => $db,
            'table' => $table,
            'export_type' => $exportType->value,
            'export_method' => $_POST['export_method'] ?? $config->config->Export->method,
            'template_id' => $_POST['template_id'] ?? '',
        ];

        if (Export::$singleTable) {
            $hiddenInputs['single_table'] = true;
        }

        if ($sqlQuery !== '') {
            $hiddenInputs['sql_query'] = $sqlQuery;
        }

        return [
            'export_type' => $exportType->value,
            'db' => $db,
            'table' => $table,
            'templates' => [
                'is_enabled' => $exportTemplatesFeature !== null,
                'templates' => $templates,
                'selected' => $_POST['template_id'] ?? null,
            ],
            'sql_query' => $sqlQuery,
            'hidden_inputs' => $hiddenInputs,
            'export_method' => $_POST['quick_or_custom'] ?? $config->config->Export->method,
            'plugins_choice' => $dropdown,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $config->settings['ExecTimeLimit'],
            'rows' => $rows,
            'has_save_dir' => $config->config->SaveDir !== '',
            'save_dir' => Util::userDir($config->config->SaveDir),
            'export_is_checked' => $this->checkboxCheck('quick_export_onserver'),
            'export_overwrite_is_checked' => $this->checkboxCheck('quick_export_onserver_overwrite'),
            'has_aliases' => $hasAliases,
            'aliases' => $aliases,
            'is_checked_lock_tables' => $this->checkboxCheck('lock_tables'),
            'is_checked_asfile' => $this->checkboxCheck('asfile'),
            'is_checked_as_separate_files' => $this->checkboxCheck('as_separate_files'),
            'is_checked_export' => $this->checkboxCheck('onserver'),
            'is_checked_export_overwrite' => $this->checkboxCheck('onserver_overwrite'),
            'is_checked_remember_file_template' => $this->checkboxCheck('remember_file_template'),
            'repopulate' => isset($_POST['repopulate']),
            'lock_tables' => isset($_POST['lock_tables']),
            'is_encoding_supported' => $isEncodingSupported,
            'encodings' => $isEncodingSupported ? Encoding::listEncodings() : [],
            'export_charset' => $config->config->Export->charset,
            'export_asfile' => $config->settings['Export']['asfile'],
            'has_zip' => $config->config->ZipDump && function_exists('gzcompress'),
            'has_gzip' => $config->config->GZipDump && function_exists('gzencode'),
            'selected_compression' => $selectedCompression,
            'filename_template' => $filenameTemplate,
        ];
    }

    private function getFormat(mixed $formatParam, mixed $whatParam): string
    {
        if (is_string($whatParam) && $whatParam !== '') {
            return $whatParam;
        }

        if (is_string($formatParam) && $formatParam !== '') {
            return $formatParam;
        }

        return Config::getInstance()->settings['Export']['format'];
    }

    private function getFileNameTemplate(ExportType $exportType, string|null $filename = null): string
    {
        if ($filename !== null) {
            return $filename;
        }

        $config = Config::getInstance();
        if ($exportType === ExportType::Database) {
            return (string) $config->getUserValue(
                'pma_db_filename_template',
                $config->settings['Export']['file_template_database'],
            );
        }

        if ($exportType === ExportType::Table) {
            return (string) $config->getUserValue(
                'pma_table_filename_template',
                $config->settings['Export']['file_template_table'],
            );
        }

        return (string) $config->getUserValue(
            'pma_server_filename_template',
            $config->settings['Export']['file_template_server'],
        );
    }
}
