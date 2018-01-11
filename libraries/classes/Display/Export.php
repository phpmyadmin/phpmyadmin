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
     * Outputs appropriate checked statement for checkbox.
     *
     * @param string $str option name
     *
     * @return string
     */
    private static function exportCheckboxCheck($str)
    {
        if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
            return ' checked="checked"';
        }

        return null;
    }

    /**
     * Prints Html For Export Selection Options
     *
     * @param string $tmpSelect Tmp selected method of export
     *
     * @return string
     */
    public static function getHtmlForExportSelectOptions($tmpSelect = '')
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
    public static function getHtmlForHiddenInput(
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
     * Prints Html For Export Options Header
     *
     * @param string $exportType Selected Export Type
     * @param string $db         Selected DB
     * @param string $table      Selected Table
     *
     * @return string HTML
     */
    public static function getHtmlForExportOptionHeader($exportType, $db, $table)
    {
        return Template::get('display/export/option_header')->render([
            'export_type' => $exportType,
            'db' => $db,
            'table' => $table,
        ]);
    }

    /**
     * Returns HTML for export template operations
     *
     * @param string $exportType export type - server, database, or table
     *
     * @return string HTML for export template operations
     */
    public static function getHtmlForExportTemplateLoading($exportType)
    {
        return Template::get('display/export/template_loading')->render([
            'options' => self::getOptionsForExportTemplates($exportType),
        ]);
    }

    /**
     * Returns HTML for the options in template dropdown
     *
     * @param string $exportType export type - server, database, or table
     *
     * @return string HTML for the options in teplate dropdown
     */
    private static function getOptionsForExportTemplates($exportType)
    {
        // Get the relation settings
        $cfgRelation = Relation::getRelationsParam();

        $query = "SELECT `id`, `template_name` FROM "
           . Util::backquote($cfgRelation['db']) . '.'
           . Util::backquote($cfgRelation['export_templates'])
           . " WHERE `username` = "
           . "'" . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user'])
            . "' AND `export_type` = '" . $GLOBALS['dbi']->escapeString($exportType) . "'"
           . " ORDER BY `template_name`;";

        $result = Relation::queryAsControlUser($query);

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
    public static function getHtmlForExportOptionsMethod()
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
    public static function getHtmlForExportOptionsSelection($exportType, $multiValues)
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
    public static function getHtmlForExportOptionsFormatDropdown($exportList)
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
    public static function getHtmlForExportOptionsFormat($exportList)
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
    public static function getHtmlForExportOptionsRows($db, $table, $unlimNumRows)
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
    public static function getHtmlForExportOptionsQuickExport()
    {
        global $cfg;
        $saveDir = Util::userDir($cfg['SaveDir']);
        $exportIsChecked = (bool) self::exportCheckboxCheck(
            'quick_export_onserver'
        );
        $exportOverwriteIsChecked = (bool) self::exportCheckboxCheck(
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
    public static function getHtmlForExportOptionsOutputSaveDir()
    {
        global $cfg;
        $saveDir = Util::userDir($cfg['SaveDir']);
        $exportIsChecked = (bool) self::exportCheckboxCheck(
            'onserver'
        );
        $exportOverwriteIsChecked = (bool) self::exportCheckboxCheck(
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
    public static function getHtmlForExportOptionsOutputFormat($exportType)
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
            'is_checked' => (bool) self::exportCheckboxCheck('remember_file_template'),
        ]);
    }

    /**
     * Prints Html For Export Options Charset
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputCharset()
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
    public static function getHtmlForExportOptionsOutputCompression()
    {
        global $cfg;
        if (isset($_GET['compression'])) {
            $selected_compression = $_GET['compression'];
        } elseif (isset($cfg['Export']['compression'])) {
            $selected_compression = $cfg['Export']['compression'];
        } else {
            $selected_compression = "none";
        }

        // Since separate files export works with ZIP only
        if (isset($cfg['Export']['as_separate_files'])
            && $cfg['Export']['as_separate_files']
        ) {
            $selected_compression = "zip";
        }

        $html = "";
        // zip and gzip encode features
        $is_zip  = ($cfg['ZipDump']  && @function_exists('gzcompress'));
        $is_gzip = ($cfg['GZipDump'] && @function_exists('gzencode'));
        if ($is_zip || $is_gzip) {
            $html .= '<li>';
            $html .= '<label for="compression" class="desc">'
                . __('Compression:') . '</label>';
            $html .= '<select id="compression" name="compression">';
            $html .= '<option value="none">' . __('None') . '</option>';
            if ($is_zip) {
                $html .= '<option value="zip" ';
                if ($selected_compression == "zip") {
                    $html .= 'selected="selected"';
                }
                $html .= '>' . __('zipped') . '</option>';
            }
            if ($is_gzip) {
                $html .= '<option value="gzip" ';
                if ($selected_compression == "gzip") {
                    $html .= 'selected="selected"';
                }
                $html .= '>' . __('gzipped') . '</option>';
            }
            $html .= '</select>';
            $html .= '</li>';
        } else {
            $html .= '<input type="hidden" name="compression" value="'
                . htmlspecialchars($selected_compression) . '" />';
        }

        return $html;
    }

    /**
     * Prints Html For Export Options Radio
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputRadio()
    {
        $html  = '<li>';
        $html .= '<input type="radio" id="radio_view_as_text" '
            . ' name="output_format" value="astext" ';
        if (isset($_GET['repopulate']) || $GLOBALS['cfg']['Export']['asfile'] == false) {
            $html .= 'checked="checked"';
        }
        $html .= '/>';
        $html .= '<label for="radio_view_as_text">'
            . __('View output as text') . '</label></li>';
        return $html;
    }

    /**
     * Prints Html For Export Options Checkbox - Separate files
     *
     * @param String $export_type Selected Export Type
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputSeparateFiles($export_type)
    {
        $html  = '<li>';
        $html .= '<input type="checkbox" id="checkbox_as_separate_files" '
            . self::exportCheckboxCheck('as_separate_files')
            . ' name="as_separate_files" value="' . $export_type . '" />';
        $html .= '<label for="checkbox_as_separate_files">';

        if ($export_type == 'server') {
            $html .= __('Export databases as separate files');
        } elseif ($export_type == 'database') {
            $html .= __('Export tables as separate files');
        }

        $html .= '</label></li>';

        return $html;
    }

    /**
     * Prints Html For Export Options
     *
     * @param String $export_type Selected Export Type
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutput($export_type)
    {
        global $cfg;
        $html  = '<div class="exportoptions" id="output">';
        $html .= '<h3>' . __('Output:') . '</h3>';
        $html .= '<ul id="ul_output">';
        $html .= '<li><input type="checkbox" id="btn_alias_config" ';
        if (isset($_SESSION['tmpval']['aliases'])
            && !Core::emptyRecursive($_SESSION['tmpval']['aliases'])
        ) {
            $html .= 'checked="checked"';
        }
        unset($_SESSION['tmpval']['aliases']);
        $html .= '/>';
        $html .= '<label for="btn_alias_config">';
        $html .= __('Rename exported databases/tables/columns');
        $html .= '</label></li>';

        if ($export_type != 'server') {
            $html .= '<li>';
            $html .= '<input type="checkbox" name="lock_tables"';
            $html .= ' value="something" id="checkbox_lock_tables"';
            if (! isset($_GET['repopulate'])) {
                $html .= self::exportCheckboxCheck('lock_tables') . '/>';
            } elseif (isset($_GET['lock_tables'])) {
                $html .= ' checked="checked"';
            }
            $html .= '<label for="checkbox_lock_tables">';
            $html .= sprintf(__('Use %s statement'), '<code>LOCK TABLES</code>');
            $html .= '</label></li>';
        }

        $html .= '<li>';
        $html .= '<input type="radio" name="output_format" value="sendit" ';
        $html .= 'id="radio_dump_asfile" ';
        if (!isset($_GET['repopulate'])) {
            $html .= self::exportCheckboxCheck('asfile');
        }
        $html .= '/>';
        $html .= '<label for="radio_dump_asfile">'
            . __('Save output to a file') . '</label>';
        $html .= '<ul id="ul_save_asfile">';
        if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
            $html .= self::getHtmlForExportOptionsOutputSaveDir();
        }

        $html .= self::getHtmlForExportOptionsOutputFormat($export_type);

        // charset of file
        if (Encoding::isSupported()) {
            $html .= self::getHtmlForExportOptionsOutputCharset();
        } // end if

        $html .= self::getHtmlForExportOptionsOutputCompression();

        if ($export_type == 'server'
            || $export_type == 'database'
        ) {
            $html .= self::getHtmlForExportOptionsOutputSeparateFiles($export_type);
        }

        $html .= '</ul>';
        $html .= '</li>';

        $html .= self::getHtmlForExportOptionsOutputRadio();

        $html .= '</ul>';

        /*
         * @todo use sprintf() for better translatability, while keeping the
         *       <label></label> principle (for screen readers)
         */
        $html .= '<label for="maxsize">'
            . __('Skip tables larger than') . '</label>';
        $html .= '<input type="text" id="maxsize" name="maxsize" size="4">' . __('MiB');

        $html .= '</div>';

        return $html;
    }

    /**
     * Prints Html For Export Options
     *
     * @param String         $export_type    Selected Export Type
     * @param String         $db             Selected DB
     * @param String         $table          Selected Table
     * @param String         $multi_values   Export selection
     * @param String         $num_tables     number of tables
     * @param ExportPlugin[] $export_list    Export List
     * @param String         $unlim_num_rows Number of Rows
     *
     * @return string
     */
    public static function getHtmlForExportOptions(
        $export_type, $db, $table, $multi_values,
        $num_tables, $export_list, $unlim_num_rows
    ) {
        global $cfg;
        $html  = self::getHtmlForExportOptionsMethod();
        $html .= self::getHtmlForExportOptionsFormatDropdown($export_list);
        $html .= self::getHtmlForExportOptionsSelection($export_type, $multi_values);

        $_table = new Table($table, $db);
        if (strlen($table) > 0 && empty($num_tables) && ! $_table->isMerge()) {
            $html .= self::getHtmlForExportOptionsRows($db, $table, $unlim_num_rows);
        }

        if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
            $html .= self::getHtmlForExportOptionsQuickExport();
        }

        $html .= self::getHtmlForAliasModalDialog();
        $html .= self::getHtmlForExportOptionsOutput($export_type);
        $html .= self::getHtmlForExportOptionsFormat($export_list);
        return $html;
    }

    /**
     * Generate Html For currently defined aliases
     *
     * @return string
     */
    public static function getHtmlForCurrentAlias()
    {
        $result = '<table id="alias_data"><thead><tr><th colspan="4">'
            . __('Defined aliases')
            . '</th></tr></thead><tbody>';

        $template = Template::get('export/alias_item');
        if (isset($_SESSION['tmpval']['aliases'])) {
            foreach ($_SESSION['tmpval']['aliases'] as $db => $db_data) {
                if (isset($db_data['alias'])) {
                    $result .= $template->render(array(
                        'type' => _pgettext('Alias', 'Database'),
                        'name' => $db,
                        'field' => 'aliases[' . $db . '][alias]',
                        'value' => $db_data['alias'],
                    ));
                }
                if (! isset($db_data['tables'])) {
                    continue;
                }
                foreach ($db_data['tables'] as $table => $table_data) {
                    if (isset($table_data['alias'])) {
                        $result .= $template->render(array(
                            'type' => _pgettext('Alias', 'Table'),
                            'name' => $db . '.' . $table,
                            'field' => 'aliases[' . $db . '][tables][' . $table . '][alias]',
                            'value' => $table_data['alias'],
                        ));
                    }
                    if (! isset($table_data['columns'])) {
                        continue;
                    }
                    foreach ($table_data['columns'] as $column => $column_name) {
                        $result .= $template->render(array(
                            'type' => _pgettext('Alias', 'Column'),
                            'name' => $db . '.' . $table . '.'. $column,
                            'field' => 'aliases[' . $db . '][tables][' . $table . '][colums][' . $column . ']',
                            'value' => $column_name,
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
    public static function getHtmlForAliasModalDialog()
    {
        // In case of server export, the following list of
        // databases are not shown in the list.
        $dbs_not_allowed = array(
            'information_schema',
            'performance_schema',
            'mysql'
        );
        // Fetch Columns info
        // Server export does not have db set.
        $title = __('Rename exported databases/tables/columns');

        $html = '<div id="alias_modal" class="hide" title="' . $title . '">';
        $html .= self::getHtmlForCurrentAlias();
        $html .= Template::get('export/alias_add')->render();

        $html .= '</div>';
        return $html;
    }

    /**
     * Gets HTML to display export dialogs
     *
     * @param String $export_type    export type: server|database|table
     * @param String $db             selected DB
     * @param String $table          selected table
     * @param String $sql_query      SQL query
     * @param Int    $num_tables     number of tables
     * @param Int    $unlim_num_rows unlimited number of rows
     * @param String $multi_values   selector options
     *
     * @return string $html
     */
    public static function getExportDisplay(
        $export_type, $db, $table, $sql_query, $num_tables,
        $unlim_num_rows, $multi_values
    ) {
        $cfgRelation = Relation::getRelationsParam();

        if (isset($_REQUEST['single_table'])) {
            $GLOBALS['single_table'] = $_REQUEST['single_table'];
        }

        /* Scan for plugins */
        /* @var $export_list ExportPlugin[] */
        $export_list = Plugins::getPlugins(
            "export",
            'libraries/classes/Plugins/Export/',
            array(
                'export_type' => $export_type,
                'single_table' => isset($GLOBALS['single_table'])
            )
        );

        /* Fail if we didn't find any plugin */
        if (empty($export_list)) {
            Message::error(
                __('Could not load export plugins, please check your installation!')
            )->display();
            exit;
        }

        $html = self::getHtmlForExportOptionHeader($export_type, $db, $table);

        if ($cfgRelation['exporttemplateswork']) {
            $html .= self::getHtmlForExportTemplateLoading($export_type);
        }

        $html .= '<form method="post" action="export.php" '
            . ' name="dump" class="disableAjax">';

        //output Hidden Inputs
        $single_table_str = isset($GLOBALS['single_table']) ? $GLOBALS['single_table']
            : '';
        $html .= self::getHtmlForHiddenInput(
            $export_type,
            $db,
            $table,
            $single_table_str,
            $sql_query
        );

        //output Export Options
        $html .= self::getHtmlForExportOptions(
            $export_type,
            $db,
            $table,
            $multi_values,
            $num_tables,
            $export_list,
            $unlim_num_rows
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
    public static function handleExportTemplateActions(array $cfgRelation)
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

        $result = Relation::queryAsControlUser($query, false);

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
                self::getOptionsForExportTemplates($_REQUEST['exportType'])
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
