<?php
/**
 * functions for displaying server, database and table export
 */

declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\TemplateModel;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function explode;
use function function_exists;
use function in_array;
use function is_array;
use function mb_strpos;
use function strlen;
use function urldecode;

/**
 * PhpMyAdmin\Display\Export class
 */
class Export
{
    /** @var Relation */
    private $relation;

    /** @var Template */
    public $template;

    /** @var TemplateModel */
    private $templateModel;

    public function __construct()
    {
        $this->relation = new Relation($GLOBALS['dbi']);
        $this->template = new Template();
        $this->templateModel = new TemplateModel($GLOBALS['dbi']);
    }

    /**
     * Outputs appropriate checked statement for checkbox.
     *
     * @param string $str option name
     *
     * @return bool
     */
    private function checkboxCheck($str)
    {
        return isset($GLOBALS['cfg']['Export'][$str])
            && $GLOBALS['cfg']['Export'][$str];
    }

    /**
     * Prints Html For Export Selection Options
     *
     * @param string $tmpSelect Tmp selected method of export
     *
     * @return string
     */
    public function getHtmlForSelectOptions($tmpSelect = '')
    {
        // Check if the selected databases are defined in $_POST
        // (from clicking Back button on /export page)
        if (isset($_POST['db_select'])) {
            $_POST['db_select'] = urldecode($_POST['db_select']);
            $_POST['db_select'] = explode(',', $_POST['db_select']);
        }

        $databases = [];
        foreach ($GLOBALS['dblist']->databases as $currentDb) {
            if (Utilities::isSystemSchema($currentDb, true)) {
                continue;
            }
            $isSelected = false;
            if (isset($_POST['db_select'])) {
                if (in_array($currentDb, $_POST['db_select'])) {
                    $isSelected = true;
                }
            } elseif (! empty($tmpSelect)) {
                if (mb_strpos(
                    ' ' . $tmpSelect,
                    '|' . $currentDb . '|'
                )) {
                    $isSelected = true;
                }
            } else {
                $isSelected = true;
            }
            $databases[] = [
                'name' => $currentDb,
                'is_selected' => $isSelected,
            ];
        }

        return $this->template->render('display/export/select_options', ['databases' => $databases]);
    }

    /**
     * Prints Html For Export Options
     *
     * @param string         $exportType   Selected Export Type
     * @param string         $db           Selected DB
     * @param string         $table        Selected Table
     * @param string         $multiValues  Export selection
     * @param string         $numTables    number of tables
     * @param ExportPlugin[] $exportList   Export List
     * @param string         $unlimNumRows Number of Rows
     *
     * @return string
     */
    public function getHtmlForOptions(
        $exportType,
        $db,
        $table,
        $multiValues,
        $numTables,
        $exportList,
        $unlimNumRows
    ) {
        global $cfg;

        $dropdown = Plugins::getChoice('Export', 'what', $exportList, 'format');
        $tableObject = new Table($table, $db);
        $rows = [];

        if (strlen($table) > 0 && empty($numTables) && ! $tableObject->isMerge() && $exportType !== 'raw') {
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
        $selectedCompression = $_POST['compression'] ?? $cfg['Export']['compression'] ?? 'none';

        if (isset($cfg['Export']['as_separate_files']) && $cfg['Export']['as_separate_files']) {
            $selectedCompression = 'zip';
        }

        return $this->template->render('display/export/options', [
            'export_method' => $_POST['quick_or_custom'] ?? $cfg['Export']['method'] ?? '',
            'dropdown' => $dropdown,
            'export_type' => $exportType,
            'multi_values' => $multiValues,
            'options' => Plugins::getOptions('Export', $exportList),
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $cfg['ExecTimeLimit'],
            'rows' => $rows,
            'has_save_dir' => isset($cfg['SaveDir']) && ! empty($cfg['SaveDir']),
            'save_dir' => Util::userDir($cfg['SaveDir'] ?? ''),
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
            'export_charset' => $cfg['Export']['charset'],
            'export_asfile' => $cfg['Export']['asfile'],
            'has_zip' => $cfg['ZipDump'] && function_exists('gzcompress'),
            'has_gzip' => $cfg['GZipDump'] && function_exists('gzencode'),
            'selected_compression' => $selectedCompression,
            'filename_template' => $filenameTemplate,
        ]);
    }

    /**
     * Gets HTML to display export dialogs
     *
     * @param string $exportType   export type: server|database|table
     * @param string $db           selected DB
     * @param string $table        selected table
     * @param string $sqlQuery     SQL query
     * @param int    $numTables    number of tables
     * @param int    $unlimNumRows unlimited number of rows
     * @param string $multiValues  selector options
     *
     * @return string
     */
    public function getDisplay(
        $exportType,
        $db,
        $table,
        $sqlQuery,
        $numTables,
        $unlimNumRows,
        $multiValues
    ) {
        global $cfg;

        $cfgRelation = $this->relation->getRelationsParam();

        if (isset($_POST['single_table'])) {
            $GLOBALS['single_table'] = $_POST['single_table'];
        }

        // Export a single table
        if (isset($_GET['single_table'])) {
            $GLOBALS['single_table'] = $_GET['single_table'];
        }

        /* Scan for plugins */
        /** @var ExportPlugin[] $exportList */
        $exportList = Plugins::getPlugins(
            'export',
            'libraries/classes/Plugins/Export/',
            [
                'export_type' => $exportType,
                'single_table' => isset($GLOBALS['single_table']),
            ]
        );

        /* Fail if we didn't find any plugin */
        if (empty($exportList)) {
            return Message::error(
                __('Could not load export plugins, please check your installation!')
            )->getDisplay();
        }

        $templates = [];

        if ($cfgRelation['exporttemplateswork']) {
            $templates = $this->templateModel->getAll(
                $cfgRelation['db'],
                $cfgRelation['export_templates'],
                $GLOBALS['cfg']['Server']['user'],
                $exportType
            );

            $templates = is_array($templates) ? $templates : [];
        }

        $options = $this->getHtmlForOptions(
            $exportType,
            $db,
            $table,
            $multiValues,
            $numTables,
            $exportList,
            $unlimNumRows
        );

        $hiddenInputs = [
            'db' => $db,
            'table' => $table,
            'export_type' => $exportType,
            'export_method' => $_POST['export_method'] ?? $cfg['Export']['method'] ?? 'quick',
            'template_id' => $_POST['template_id'] ?? '',
        ];

        if (! empty($GLOBALS['single_table'])) {
            $hiddenInputs['single_table'] = true;
        }

        if (! empty($sqlQuery)) {
            $hiddenInputs['sql_query'] = $sqlQuery;
        }

        return $this->template->render('display/export/display', [
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
            'templates' => [
                'is_enabled' => $cfgRelation['exporttemplateswork'],
                'templates' => $templates,
                'selected' => $_POST['template_id'] ?? null,
            ],
            'sql_query' => $sqlQuery,
            'hidden_inputs' => $hiddenInputs,
            'options' => $options,
        ]);
    }

    private function getFileNameTemplate(string $exportType, ?string $filename = null): string
    {
        global $cfg, $PMA_Config;

        if ($filename !== null) {
            return $filename;
        }

        if ($exportType === 'database') {
            return (string) $PMA_Config->getUserValue(
                'pma_db_filename_template',
                $cfg['Export']['file_template_database']
            );
        }

        if ($exportType === 'table') {
            return (string) $PMA_Config->getUserValue(
                'pma_table_filename_template',
                $cfg['Export']['file_template_table']
            );
        }

        return (string) $PMA_Config->getUserValue(
            'pma_server_filename_template',
            $cfg['Export']['file_template_server']
        );
    }
}
