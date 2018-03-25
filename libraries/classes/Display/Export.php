<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying server, database and table export
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Display;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Display\Export class
 *
 * @package PhpMyAdmin
 */
class Export
{
    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation();
    }

    /**
     * Outputs appropriate checked statement for checkbox.
     *
     * @param string $str option name
     *
     * @return boolean
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
        // Check if the selected databases are defined in $_GET
        // (from clicking Back button on export.php)
        if (isset($_GET['db_select'])) {
            $_GET['db_select'] = urldecode($_GET['db_select']);
            $_GET['db_select'] = explode(",", $_GET['db_select']);
        }

        $databases = [];
        foreach ($GLOBALS['dblist']->databases as $currentDb) {
            if ($GLOBALS['dbi']->isSystemSchema($currentDb, true)) {
                continue;
            }
            $isSelected = false;
            if (isset($_GET['db_select'])) {
                if (in_array($currentDb, $_GET['db_select'])) {
                    $isSelected = true;
                }
            } elseif (!empty($tmpSelect)) {
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

        return Template::get('display/export/select_options')->render([
            'databases' => $databases,
        ]);
    }

    /**
     * Prints Html For Export Hidden Input
     *
     * @param string $exportType  Selected Export Type
     * @param string $db          Selected DB
     * @param string $table       Selected Table
     * @param string $singleTable Single Table
     * @param string $sqlQuery    SQL Query
     *
     * @return string
     */
    public function getHtmlForHiddenInputs(
        $exportType,
        $db,
        $table,
        $singleTable,
        $sqlQuery
    ) {
        global $cfg;

        // If the export method was not set, the default is quick
        if (isset($_GET['export_method'])) {
            $cfg['Export']['method'] = $_GET['export_method'];
        } elseif (! isset($cfg['Export']['method'])) {
            $cfg['Export']['method'] = 'quick';
        }

        if (empty($sqlQuery) && isset($_GET['sql_query'])) {
            $sqlQuery = $_GET['sql_query'];
        }

        return Template::get('display/export/hidden_inputs')->render([
            'db' => $db,
            'table' => $table,
            'export_type' => $exportType,
            'export_method' => $cfg['Export']['method'],
            'single_table' => $singleTable,
            'sql_query' => $sqlQuery,
            'template_id' => isset($_GET['template_id']) ? $_GET['template_id'] : '',
        ]);
    }

    /**
     * Returns HTML for the options in template dropdown
     *
     * @param string $exportType export type - server, database, or table
     *
     * @return string HTML for the options in teplate dropdown
     */
    private function getOptionsForTemplates($exportType)
    {
        // Get the relation settings
        $cfgRelation = $this->relation->getRelationsParam();

        $query = "SELECT `id`, `template_name` FROM "
           . Util::backquote($cfgRelation['db']) . '.'
           . Util::backquote($cfgRelation['export_templates'])
           . " WHERE `username` = "
           . "'" . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user'])
            . "' AND `export_type` = '" . $GLOBALS['dbi']->escapeString($exportType) . "'"
           . " ORDER BY `template_name`;";

        $result = $this->relation->queryAsControlUser($query);

        $templates = [];
        if ($result !== false) {
            while ($row = $GLOBALS['dbi']->fetchAssoc($result, DatabaseInterface::CONNECT_CONTROL)) {
                $templates[] = [
                    'name' => $row['template_name'],
                    'id' => $row['id'],
                ];
            }
        }

        return Template::get('display/export/template_options')->render([
            'templates' => $templates,
            'selected_template' => !empty($_GET['template_id']) ? $_GET['template_id'] : null,
        ]);
    }

    /**
     * Prints Html For Export Options Method
     *
     * @return string
     */
    private function getHtmlForOptionsMethod()
    {
        global $cfg;
        if (isset($_GET['quick_or_custom'])) {
            $exportMethod = $_GET['quick_or_custom'];
        } else {
            $exportMethod = $cfg['Export']['method'];
        }

        return Template::get('display/export/method')->render([
            'export_method' => $exportMethod,
        ]);
    }

    /**
     * Prints Html For Export Options Selection
     *
     * @param string $exportType  Selected Export Type
     * @param string $multiValues Export Options
     *
     * @return string
     */
    private function getHtmlForOptionsSelection($exportType, $multiValues)
    {
        return Template::get('display/export/selection')->render([
            'export_type' => $exportType,
            'multi_values' => $multiValues,
        ]);
    }

    /**
     * Prints Html For Export Options Format dropdown
     *
     * @param ExportPlugin[] $exportList Export List
     *
     * @return string
     */
    private function getHtmlForOptionsFormatDropdown($exportList)
    {
        $dropdown = Plugins::getChoice('Export', 'what', $exportList, 'format');
        return Template::get('display/export/format_dropdown')->render([
            'dropdown' => $dropdown,
        ]);
    }

    /**
     * Prints Html For Export Options Format-specific options
     *
     * @param ExportPlugin[] $exportList Export List
     *
     * @return string
     */
    private function getHtmlForOptionsFormat($exportList)
    {
        global $cfg;
        $options = Plugins::getOptions('Export', $exportList);

        return Template::get('display/export/options_format')->render([
            'options' => $options,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'exec_time_limit' => $cfg['ExecTimeLimit'],
        ]);
    }

    /**
     * Prints Html For Export Options Rows
     *
     * @param string $db           Selected DB
     * @param string $table        Selected Table
     * @param string $unlimNumRows Num of Rows
     *
     * @return string
     */
    private function getHtmlForOptionsRows($db, $table, $unlimNumRows)
    {
        $tableObject = new Table($table, $db);
        $numberOfRows = $tableObject->countRecords();

        return Template::get('display/export/options_rows')->render([
            'allrows' => isset($_GET['allrows']) ? $_GET['allrows'] : null,
            'limit_to' => isset($_GET['limit_to']) ? $_GET['limit_to'] : null,
            'limit_from' => isset($_GET['limit_from']) ? $_GET['limit_from'] : null,
            'unlim_num_rows' => $unlimNumRows,
            'number_of_rows' => $numberOfRows,
        ]);
    }

    /**
     * Prints Html For Export Options Quick Export
     *
     * @return string
     */
    private function getHtmlForOptionsQuickExport()
    {
        global $cfg;
        $saveDir = Util::userDir($cfg['SaveDir']);
        $exportIsChecked = $this->checkboxCheck(
            'quick_export_onserver'
        );
        $exportOverwriteIsChecked = $this->checkboxCheck(
            'quick_export_onserver_overwrite'
        );

        return Template::get('display/export/options_quick_export')->render([
            'save_dir' => $saveDir,
            'export_is_checked' => $exportIsChecked,
            'export_overwrite_is_checked' => $exportOverwriteIsChecked,
        ]);
    }

    /**
     * Prints Html For Export Options Save Dir
     *
     * @return string
     */
    private function getHtmlForOptionsOutputSaveDir()
    {
        global $cfg;
        $saveDir = Util::userDir($cfg['SaveDir']);
        $exportIsChecked = $this->checkboxCheck(
            'onserver'
        );
        $exportOverwriteIsChecked = $this->checkboxCheck(
            'onserver_overwrite'
        );

        return Template::get('display/export/options_output_save_dir')->render([
            'save_dir' => $saveDir,
            'export_is_checked' => $exportIsChecked,
            'export_overwrite_is_checked' => $exportOverwriteIsChecked,
        ]);
    }


    /**
     * Prints Html For Export Options
     *
     * @param string $exportType Selected Export Type
     *
     * @return string
     */
    private function getHtmlForOptionsOutputFormat($exportType)
    {
        $trans = new Message;
        $trans->addText(__('@SERVER@ will become the server name'));
        if ($exportType == 'database' || $exportType == 'table') {
            $trans->addText(__(', @DATABASE@ will become the database name'));
            if ($exportType == 'table') {
                $trans->addText(__(', @TABLE@ will become the table name'));
            }
        }

        $msg = new Message(
            __(
                'This value is interpreted using %1$sstrftime%2$s, '
                . 'so you can use time formatting strings. '
                . 'Additionally the following transformations will happen: %3$s. '
                . 'Other text will be kept as is. See the %4$sFAQ%5$s for details.'
            )
        );
        $msg->addParamHtml(
            '<a href="' . Core::linkURL(Core::getPHPDocLink('function.strftime.php'))
            . '" target="documentation" title="' . __('Documentation') . '">'
        );
        $msg->addParamHtml('</a>');
        $msg->addParam($trans);
        $docUrl = Util::getDocuLink('faq', 'faq6-27');
        $msg->addParamHtml(
            '<a href="' . $docUrl . '" target="documentation">'
        );
        $msg->addParamHtml('</a>');

        if (isset($_GET['filename_template'])) {
            $filenameTemplate = $_GET['filename_template'];
        } else {
            if ($exportType == 'database') {
                $filenameTemplate = $GLOBALS['PMA_Config']->getUserValue(
                    'pma_db_filename_template',
                    $GLOBALS['cfg']['Export']['file_template_database']
                );
            } elseif ($exportType == 'table') {
                $filenameTemplate = $GLOBALS['PMA_Config']->getUserValue(
                    'pma_table_filename_template',
                    $GLOBALS['cfg']['Export']['file_template_table']
                );
            } else {
                $filenameTemplate = $GLOBALS['PMA_Config']->getUserValue(
                    'pma_server_filename_template',
                    $GLOBALS['cfg']['Export']['file_template_server']
                );
            }
        }

        return Template::get('display/export/options_output_format')->render([
            'message' => $msg->getMessage(),
            'filename_template' => $filenameTemplate,
            'is_checked' => $this->checkboxCheck('remember_file_template'),
        ]);
    }

    /**
     * Prints Html For Export Options Charset
     *
     * @return string
     */
    private function getHtmlForOptionsOutputCharset()
    {
        global $cfg;

        return Template::get('display/export/options_output_charset')->render([
            'encodings' => Encoding::listEncodings(),
            'export_charset' => $cfg['Export']['charset'],
        ]);
    }

    /**
     * Prints Html For Export Options Compression
     *
     * @return string
     */
    private function getHtmlForOptionsOutputCompression()
    {
        global $cfg;
        if (isset($_GET['compression'])) {
            $selectedCompression = $_GET['compression'];
        } elseif (isset($cfg['Export']['compression'])) {
            $selectedCompression = $cfg['Export']['compression'];
        } else {
            $selectedCompression = 'none';
        }

        // Since separate files export works with ZIP only
        if (isset($cfg['Export']['as_separate_files'])
            && $cfg['Export']['as_separate_files']
        ) {
            $selectedCompression = 'zip';
        }

        // zip and gzip encode features
        $isZip = ($cfg['ZipDump'] && function_exists('gzcompress'));
        $isGzip = ($cfg['GZipDump'] && function_exists('gzencode'));

        return Template::get('display/export/options_output_compression')->render([
            'is_zip' => $isZip,
            'is_gzip' => $isGzip,
            'selected_compression' => $selectedCompression,
        ]);
    }

    /**
     * Prints Html For Export Options Radio
     *
     * @return string
     */
    private function getHtmlForOptionsOutputRadio()
    {
        return Template::get('display/export/options_output_radio')->render([
            'has_repopulate' => isset($_GET['repopulate']),
            'export_asfile' => $GLOBALS['cfg']['Export']['asfile'],
        ]);
    }

    /**
     * Prints Html For Export Options Checkbox - Separate files
     *
     * @param string $exportType Selected Export Type
     *
     * @return string
     */
    private function getHtmlForOptionsOutputSeparateFiles($exportType)
    {
        $isChecked = $this->checkboxCheck('as_separate_files');

        return Template::get('display/export/options_output_separate_files')->render([
            'is_checked' => $isChecked,
            'export_type' => $exportType,
        ]);
    }

    /**
     * Prints Html For Export Options
     *
     * @param string $exportType Selected Export Type
     *
     * @return string
     */
    private function getHtmlForOptionsOutput($exportType)
    {
        global $cfg;

        $hasAliases = isset($_SESSION['tmpval']['aliases'])
            && !Core::emptyRecursive($_SESSION['tmpval']['aliases']);
        unset($_SESSION['tmpval']['aliases']);

        $isCheckedLockTables = $this->checkboxCheck('lock_tables');
        $isCheckedAsfile = $this->checkboxCheck('asfile');

        $optionsOutputSaveDir = '';
        if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
            $optionsOutputSaveDir = $this->getHtmlForOptionsOutputSaveDir();
        }
        $optionsOutputFormat = $this->getHtmlForOptionsOutputFormat($exportType);
        $optionsOutputCharset = '';
        if (Encoding::isSupported()) {
            $optionsOutputCharset = $this->getHtmlForOptionsOutputCharset();
        }
        $optionsOutputCompression = $this->getHtmlForOptionsOutputCompression();
        $optionsOutputSeparateFiles = '';
        if ($exportType == 'server' || $exportType == 'database') {
            $optionsOutputSeparateFiles = $this->getHtmlForOptionsOutputSeparateFiles(
                $exportType
            );
        }
        $optionsOutputRadio = $this->getHtmlForOptionsOutputRadio();

        return Template::get('display/export/options_output')->render([
            'has_aliases' => $hasAliases,
            'export_type' => $exportType,
            'is_checked_lock_tables' => $isCheckedLockTables,
            'is_checked_asfile' => $isCheckedAsfile,
            'repopulate' => isset($_GET['repopulate']),
            'lock_tables' => isset($_GET['lock_tables']),
            'save_dir' => isset($cfg['SaveDir']) ? $cfg['SaveDir'] : null,
            'is_encoding_supported' => Encoding::isSupported(),
            'options_output_save_dir' => $optionsOutputSaveDir,
            'options_output_format' => $optionsOutputFormat,
            'options_output_charset' => $optionsOutputCharset,
            'options_output_compression' => $optionsOutputCompression,
            'options_output_separate_files' => $optionsOutputSeparateFiles,
            'options_output_radio' => $optionsOutputRadio,
        ]);
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
        $html = $this->getHtmlForOptionsMethod();
        $html .= $this->getHtmlForOptionsFormatDropdown($exportList);
        $html .= $this->getHtmlForOptionsSelection($exportType, $multiValues);

        $tableObject = new Table($table, $db);
        if (strlen($table) > 0 && empty($numTables) && ! $tableObject->isMerge()) {
            $html .= $this->getHtmlForOptionsRows($db, $table, $unlimNumRows);
        }

        if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
            $html .= $this->getHtmlForOptionsQuickExport();
        }

        $html .= $this->getHtmlForAliasModalDialog();
        $html .= $this->getHtmlForOptionsOutput($exportType);
        $html .= $this->getHtmlForOptionsFormat($exportList);
        return $html;
    }

    /**
     * Generate Html For currently defined aliases
     *
     * @return string
     */
    private function getHtmlForCurrentAlias()
    {
        $result = '<table id="alias_data"><thead><tr><th colspan="4">'
            . __('Defined aliases')
            . '</th></tr></thead><tbody>';

        $template = Template::get('export/alias_item');
        if (isset($_SESSION['tmpval']['aliases'])) {
            foreach ($_SESSION['tmpval']['aliases'] as $db => $dbData) {
                if (isset($dbData['alias'])) {
                    $result .= $template->render(array(
                        'type' => _pgettext('Alias', 'Database'),
                        'name' => $db,
                        'field' => 'aliases[' . $db . '][alias]',
                        'value' => $dbData['alias'],
                    ));
                }
                if (! isset($dbData['tables'])) {
                    continue;
                }
                foreach ($dbData['tables'] as $table => $tableData) {
                    if (isset($tableData['alias'])) {
                        $result .= $template->render(array(
                            'type' => _pgettext('Alias', 'Table'),
                            'name' => $db . '.' . $table,
                            'field' => 'aliases[' . $db . '][tables][' . $table . '][alias]',
                            'value' => $tableData['alias'],
                        ));
                    }
                    if (! isset($tableData['columns'])) {
                        continue;
                    }
                    foreach ($tableData['columns'] as $column => $columnName) {
                        $result .= $template->render(array(
                            'type' => _pgettext('Alias', 'Column'),
                            'name' => $db . '.' . $table . '.'. $column,
                            'field' => 'aliases[' . $db . '][tables][' . $table . '][colums][' . $column . ']',
                            'value' => $columnName,
                        ));
                    }
                }
            }
        }

        // Empty row for javascript manipulations
        $result .= '</tbody><tfoot class="hide">' . $template->render(array(
            'type' => '', 'name' => '', 'field' => 'aliases_new', 'value' => ''
        )) . '</tfoot>';

        return $result . '</table>';
    }

    /**
     * Generate Html For Alias Modal Dialog
     *
     * @return string
     */
    public function getHtmlForAliasModalDialog()
    {
        $title = __('Rename exported databases/tables/columns');

        $html = '<div id="alias_modal" class="hide" title="' . $title . '">';
        $html .= $this->getHtmlForCurrentAlias();
        $html .= Template::get('export/alias_add')->render();

        $html .= '</div>';
        return $html;
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
     * @return string $html
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
        $cfgRelation = $this->relation->getRelationsParam();

        if (isset($_REQUEST['single_table'])) {
            $GLOBALS['single_table'] = $_REQUEST['single_table'];
        }

        /* Scan for plugins */
        /* @var $exportList ExportPlugin[] */
        $exportList = Plugins::getPlugins(
            "export",
            'libraries/classes/Plugins/Export/',
            array(
                'export_type' => $exportType,
                'single_table' => isset($GLOBALS['single_table'])
            )
        );

        /* Fail if we didn't find any plugin */
        if (empty($exportList)) {
            Message::error(
                __('Could not load export plugins, please check your installation!')
            )->display();
            exit;
        }

        $html = Template::get('display/export/option_header')->render([
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
        ]);

        if ($cfgRelation['exporttemplateswork']) {
            $html .= Template::get('display/export/template_loading')->render([
                'options' => $this->getOptionsForTemplates($exportType),
            ]);
        }

        $html .= '<form method="post" action="export.php" '
            . ' name="dump" class="disableAjax">';

        //output Hidden Inputs
        $singleTableStr = isset($GLOBALS['single_table']) ? $GLOBALS['single_table']
            : '';
        $html .= $this->getHtmlForHiddenInputs(
            $exportType,
            $db,
            $table,
            $singleTableStr,
            $sqlQuery
        );

        //output Export Options
        $html .= $this->getHtmlForOptions(
            $exportType,
            $db,
            $table,
            $multiValues,
            $numTables,
            $exportList,
            $unlimNumRows
        );

        $html .= '</form>';
        return $html;
    }

    /**
     * Handles export template actions
     *
     * @param array $cfgRelation Relation configuration
     *
     * @return void
     */
    public function handleTemplateActions(array $cfgRelation)
    {
        if (isset($_REQUEST['templateId'])) {
            $id = $GLOBALS['dbi']->escapeString($_REQUEST['templateId']);
        } else {
            $id = '';
        }

        $templateTable = Util::backquote($cfgRelation['db']) . '.'
           . Util::backquote($cfgRelation['export_templates']);
        $user = $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user']);

        switch ($_REQUEST['templateAction']) {
        case 'create':
            $query = "INSERT INTO " . $templateTable . "("
                . " `username`, `export_type`,"
                . " `template_name`, `template_data`"
                . ") VALUES ("
                . "'" . $user . "', "
                . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['exportType'])
                . "', '" . $GLOBALS['dbi']->escapeString($_REQUEST['templateName'])
                . "', '" . $GLOBALS['dbi']->escapeString($_REQUEST['templateData'])
                . "');";
            break;
        case 'load':
            $query = "SELECT `template_data` FROM " . $templateTable
                 . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
            break;
        case 'update':
            $query = "UPDATE " . $templateTable . " SET `template_data` = "
              . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['templateData']) . "'"
              . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
            break;
        case 'delete':
            $query = "DELETE FROM " . $templateTable
               . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
            break;
        default:
            $query = '';
            break;
        }

        $result = $this->relation->queryAsControlUser($query, false);

        $response = Response::getInstance();
        if (! $result) {
            $error = $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL);
            $response->setRequestStatus(false);
            $response->addJSON('message', $error);
            exit;
        }

        $response->setRequestStatus(true);
        if ('create' == $_REQUEST['templateAction']) {
            $response->addJSON(
                'data',
                $this->getOptionsForTemplates($_REQUEST['exportType'])
            );
        } elseif ('load' == $_REQUEST['templateAction']) {
            $data = null;
            while ($row = $GLOBALS['dbi']->fetchAssoc(
                $result, DatabaseInterface::CONNECT_CONTROL
            )) {
                $data = $row['template_data'];
            }
            $response->addJSON('data', $data);
        }
        $GLOBALS['dbi']->freeResult($result);
    }
}
