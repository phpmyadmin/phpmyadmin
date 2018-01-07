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
    public static function exportCheckboxCheck($str)
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
     * @param String $export_type  Selected Export Type
     * @param String $db           Selected DB
     * @param String $table        Selected Table
     * @param String $single_table Single Table
     * @param String $sql_query    Sql Query
     *
     * @return string
     */
    public static function getHtmlForHiddenInput(
        $export_type, $db, $table, $single_table, $sql_query
    ) {
        global $cfg;
        $html = "";
        if ($export_type == 'server') {
            $html .= Url::getHiddenInputs('', '', 1);
        } elseif ($export_type == 'database') {
            $html .= Url::getHiddenInputs($db, '', 1);
        } else {
            $html .= Url::getHiddenInputs($db, $table, 1);
        }

        // just to keep this value for possible next display of this form after saving
        // on server
        if (!empty($single_table)) {
            $html .= '<input type="hidden" name="single_table" value="TRUE" />'
                . "\n";
        }

        $html .= '<input type="hidden" name="export_type" value="'
            . $export_type . '" />';
        $html .= "\n";

        // If the export method was not set, the default is quick
        if (isset($_GET['export_method'])) {
            $cfg['Export']['method'] = $_GET['export_method'];
        } elseif (! isset($cfg['Export']['method'])) {
            $cfg['Export']['method'] = 'quick';
        }
        // The export method (quick, custom or custom-no-form)
        $html .= '<input type="hidden" name="export_method" value="'
            . htmlspecialchars($cfg['Export']['method']) . '" />';

        if (! empty($sql_query)) {
            $html .= '<input type="hidden" name="sql_query" value="'
                . htmlspecialchars($sql_query) . '" />' . "\n";
        } elseif (isset($_GET['sql_query'])) {
            $html .= '<input type="hidden" name="sql_query" value="'
                . htmlspecialchars($_GET['sql_query']) . '" />' . "\n";
        }

        $html .= '<input type="hidden" name="template_id"' . ' value="'
            . (isset($_GET['template_id'])
                ?  htmlspecialchars($_GET['template_id'])
                : '')
            . '" />';

        return $html;
    }

    /**
     * Prints Html For Export Options Header
     *
     * @param String $export_type Selected Export Type
     * @param String $db          Selected DB
     * @param String $table       Selected Table
     *
     * @return string
     */
    public static function getHtmlForExportOptionHeader($export_type, $db, $table)
    {
        $html  = '<div class="exportoptions" id="header">';
        $html .= '<h2>';
        $html .= Util::getImage('b_export', __('Export'));
        if ($export_type == 'server') {
            $html .= __('Exporting databases from the current server');
        } elseif ($export_type == 'database') {
            $html .= sprintf(
                __('Exporting tables from "%s" database'),
                htmlspecialchars($db)
            );
        } else {
            $html .= sprintf(
                __('Exporting rows from "%s" table'),
                htmlspecialchars($table)
            );
        }
        $html .= '</h2>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns HTML for export template operations
     *
     * @param string $export_type export type - server, database, or table
     *
     * @return string HTML for export template operations
     */
    public static function getHtmlForExportTemplateLoading($export_type)
    {
        $html  = '<div class="exportoptions" id="export_templates">';
        $html .= '<h3>' . __('Export templates:') . '</h3>';

        $html .= '<div class="floatleft">';
        $html .= '<form method="post" action="tbl_export.php" id="newTemplateForm"'
            . ' class="ajax">';
        $html .= '<h4>' . __('New template:') . '</h4>';
        $html .= '<input type="text" name="templateName" id="templateName" '
            . 'maxlength="64"' . 'required="required" '
            . 'placeholder="' . __('Template name') . '" />';
        $html .= '<input type="submit" name="createTemplate" id="createTemplate" '
            . 'value="' . __('Create') . '" />';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div class="floatleft" style="margin-left: 50px;">';
        $html .= '<form method="post" action="tbl_export.php"'
            . ' id="existingTemplatesForm" class="ajax">';
        $html .= '<h4>' . __('Existing templates:') . '</h4>';
        $html .= '<label for="template">' . __('Template:') . '</label>';
        $html .= '<select required="required" name="template" id="template">';
        $html .= self::getOptionsForExportTemplates($export_type);
        $html .= '</select>';
        $html .= '<input type="submit" name="updateTemplate" '
            . 'id="updateTemplate" value="' . __('Update') . '" />';
        $html .= '<input type="submit" name="deleteTemplate" '
            . 'id="deleteTemplate" value="' . __('Delete') . '" />';
        $html .= '</form>';
        $html .= '</div>';

        $html .= '<div class="clearfloat"></div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Returns HTML for the options in teplate dropdown
     *
     * @param string $export_type export type - server, database, or table
     *
     * @return string HTML for the options in teplate dropdown
     */
    public static function getOptionsForExportTemplates($export_type)
    {
        $ret = '<option value="">-- ' . __('Select a template') . ' --</option>';

        // Get the relation settings
        $cfgRelation = Relation::getRelationsParam();

        $query = "SELECT `id`, `template_name` FROM "
           . Util::backquote($cfgRelation['db']) . '.'
           . Util::backquote($cfgRelation['export_templates'])
           . " WHERE `username` = "
           . "'" . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user'])
            . "' AND `export_type` = '" . $GLOBALS['dbi']->escapeString($export_type) . "'"
           . " ORDER BY `template_name`;";

        $result = Relation::queryAsControlUser($query);
        if (!$result) {
            return $ret;
        }

        while ($row = $GLOBALS['dbi']->fetchAssoc($result, DatabaseInterface::CONNECT_CONTROL)) {
            $ret .= '<option value="' . htmlspecialchars($row['id']) . '"';
            if (!empty($_GET['template_id']) && $_GET['template_id'] == $row['id']) {
                $ret .= ' selected="selected"';
            }
            $ret .= '>';
            $ret .=  htmlspecialchars($row['template_name']) . '</option>';
        }

        return $ret;
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
            $export_method = $_GET['quick_or_custom'];
        } else {
            $export_method = $cfg['Export']['method'];
        }

        if ($export_method == 'custom-no-form') {
            return '';
        }

        $html  = '<div class="exportoptions" id="quick_or_custom">';
        $html .= '<h3>' . __('Export method:') . '</h3>';
        $html .= '<ul>';
        $html .= '<li>';
        $html .= '<input type="radio" name="quick_or_custom" value="quick" '
            . ' id="radio_quick_export"';
        if ($export_method == 'quick') {
            $html .= ' checked="checked"';
        }
        $html .= ' />';
        $html .= '<label for ="radio_quick_export">';
        $html .= __('Quick - display only the minimal options');
        $html .= '</label>';
        $html .= '</li>';

        $html .= '<li>';
        $html .= '<input type="radio" name="quick_or_custom" value="custom" '
            . ' id="radio_custom_export"';
        if ($export_method == 'custom') {
            $html .= ' checked="checked"';
        }
        $html .= ' />';
        $html .= '<label for="radio_custom_export">';
        $html .= __('Custom - display all possible options');
        $html .= '</label>';
        $html .= '</li>';

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Prints Html For Export Options Selection
     *
     * @param String $export_type  Selected Export Type
     * @param String $multi_values Export Options
     *
     * @return string
     */
    public static function getHtmlForExportOptionsSelection($export_type, $multi_values)
    {
        $html = '<div class="exportoptions" id="databases_and_tables">';
        if ($export_type == 'server') {
            $html .= '<h3>' . __('Databases:') . '</h3>';
        } elseif ($export_type == 'database') {
            $html .= '<h3>' . __('Tables:') . '</h3>';
        }
        if (! empty($multi_values)) {
            $html .= $multi_values;
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Prints Html For Export Options Format dropdown
     *
     * @param ExportPlugin[] $export_list Export List
     *
     * @return string
     */
    public static function getHtmlForExportOptionsFormatDropdown($export_list)
    {
        $html  = '<div class="exportoptions" id="format">';
        $html .= '<h3>' . __('Format:') . '</h3>';
        $html .= Plugins::getChoice('Export', 'what', $export_list, 'format');
        $html .= '</div>';
        return $html;
    }

    /**
     * Prints Html For Export Options Format-specific options
     *
     * @param ExportPlugin[] $export_list Export List
     *
     * @return string
     */
    public static function getHtmlForExportOptionsFormat($export_list)
    {
        $html = '<div class="exportoptions" id="format_specific_opts">';
        $html .= '<h3>' . __('Format-specific options:') . '</h3>';
        $html .= '<p class="no_js_msg" id="scroll_to_options_msg">';
        $html .= __(
            'Scroll down to fill in the options for the selected format '
            . 'and ignore the options for other formats.'
        );
        $html .= '</p>';
        $html .= Plugins::getOptions('Export', $export_list);
        $html .= '</div>';

        if (Encoding::canConvertKanji()) {
            // Japanese encoding setting
            $html .= '<div class="exportoptions" id="kanji_encoding">';
            $html .= '<h3>' . __('Encoding Conversion:') . '</h3>';
            $html .= Encoding::kanjiEncodingForm();
            $html .= '</div>';
        }

        $html .= '<div class="exportoptions" id="submit">';

        $html .= Util::getExternalBug(
            __('SQL compatibility mode'), 'mysql', '50027', '14515'
        );
        global $cfg;
        if ($cfg['ExecTimeLimit'] > 0) {
            $html .= '<input type="submit" value="' . __('Go')
                . '" id="buttonGo" onclick="check_time_out('
                . $cfg['ExecTimeLimit'] . ')"/>';
        } else {
            // if the time limit set is zero, then time out won't occur
            // So no need to check for time out.
            $html .= '<input type="submit" value="' . __('Go') . '" id="buttonGo" />';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Prints Html For Export Options Rows
     *
     * @param String $db             Selected DB
     * @param String $table          Selected Table
     * @param String $unlim_num_rows Num of Rows
     *
     * @return string
     */
    public static function getHtmlForExportOptionsRows($db, $table, $unlim_num_rows)
    {
        $html  = '<div class="exportoptions" id="rows">';
        $html .= '<h3>' . __('Rows:') . '</h3>';
        $html .= '<ul>';
        $html .= '<li>';
        $html .= '<input type="radio" name="allrows" value="0" id="radio_allrows_0"';
        if (isset($_GET['allrows']) && $_GET['allrows'] == 0) {
            $html .= ' checked="checked"';
        }
        $html .= '/>';
        $html .= '<label for ="radio_allrows_0">' . __('Dump some row(s)') . '</label>';
        $html .= '<ul>';
        $html .= '<li>';
        $html .= '<label for="limit_to">' . __('Number of rows:') . '</label>';
        $html .= '<input type="text" id="limit_to" name="limit_to" size="5" value="';
        if (isset($_GET['limit_to'])) {
            $html .= htmlspecialchars($_GET['limit_to']);
        } elseif (!empty($unlim_num_rows)) {
            $html .= $unlim_num_rows;
        } else {
            $_table = new Table($table, $db);
            $html .= $_table->countRecords();
        }
        $html .= '" onfocus="this.select()" />';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<label for="limit_from">' . __('Row to begin at:') . '</label>';
        $html .= '<input type="text" id="limit_from" name="limit_from" value="';
        if (isset($_GET['limit_from'])) {
            $html .= htmlspecialchars($_GET['limit_from']);
        } else {
            $html .= '0';
        }
        $html .= '" size="5" onfocus="this.select()" />';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<input type="radio" name="allrows" value="1" id="radio_allrows_1"';
        if (! isset($_GET['allrows']) || $_GET['allrows'] == 1) {
            $html .= ' checked="checked"';
        }
        $html .= '/>';
        $html .= ' <label for="radio_allrows_1">' . __('Dump all rows') . '</label>';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Prints Html For Export Options Quick Export
     *
     * @return string
     */
    public static function getHtmlForExportOptionsQuickExport()
    {
        global $cfg;
        $html  = '<div class="exportoptions" id="output_quick_export">';
        $html .= '<h3>' . __('Output:') . '</h3>';
        $html .= '<ul>';
        $html .= '<li>';
        $html .= '<input type="checkbox" name="quick_export_onserver" value="saveit" ';
        $html .= 'id="checkbox_quick_dump_onserver" ';
        $html .= self::exportCheckboxCheck('quick_export_onserver');
        $html .= '/>';
        $html .= '<label for="checkbox_quick_dump_onserver">';
        $html .= sprintf(
            __('Save on server in the directory <b>%s</b>'),
            htmlspecialchars(Util::userDir($cfg['SaveDir']))
        );
        $html .= '</label>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<input type="checkbox" name="quick_export_onserver_overwrite" ';
        $html .= 'value="saveitover" id="checkbox_quick_dump_onserver_overwrite" ';
        $html .= self::exportCheckboxCheck('quick_export_onserver_overwrite');
        $html .= '/>';
        $html .= '<label for="checkbox_quick_dump_onserver_overwrite">';
        $html .= __('Overwrite existing file(s)');
        $html .= '</label>';
        $html .= '</li>';
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Prints Html For Export Options Save Dir
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputSaveDir()
    {
        global $cfg;
        $html  = '<li>';
        $html .= '<input type="checkbox" name="onserver" value="saveit" ';
        $html .= 'id="checkbox_dump_onserver" ';
        $html .= self::exportCheckboxCheck('onserver');
        $html .= '/>';
        $html .= '<label for="checkbox_dump_onserver">';
        $html .= sprintf(
            __('Save on server in the directory <b>%s</b>'),
            htmlspecialchars(Util::userDir($cfg['SaveDir']))
        );
        $html .= '</label>';
        $html .= '</li>';
        $html .= '<li>';
        $html .= '<input type="checkbox" name="onserver_overwrite" value="saveitover"';
        $html .= ' id="checkbox_dump_onserver_overwrite" ';
        $html .= self::exportCheckboxCheck('onserver_overwrite');
        $html .= '/>';
        $html .= '<label for="checkbox_dump_onserver_overwrite">';
        $html .= __('Overwrite existing file(s)');
        $html .= '</label>';
        $html .= '</li>';

        return $html;
    }


    /**
     * Prints Html For Export Options
     *
     * @param String $export_type Selected Export Type
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputFormat($export_type)
    {
        $html  = '<li>';
        $html .= '<label for="filename_template" class="desc">';
        $html .= __('File name template:');
        $trans = new Message;
        $trans->addText(__('@SERVER@ will become the server name'));
        if ($export_type == 'database' || $export_type == 'table') {
            $trans->addText(__(', @DATABASE@ will become the database name'));
            if ($export_type == 'table') {
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
        $doc_url = Util::getDocuLink('faq', 'faq6-27');
        $msg->addParamHtml(
            '<a href="' . $doc_url . '" target="documentation">'
        );
        $msg->addParamHtml('</a>');

        $html .= Util::showHint($msg);
        $html .= '</label>';
        $html .= '<input type="text" name="filename_template" id="filename_template" ';
        $html .= ' value="';
        if (isset($_GET['filename_template'])) {
            $html .= htmlspecialchars($_GET['filename_template']);
        } else {
            if ($export_type == 'database') {
                $html .= htmlspecialchars(
                    $GLOBALS['PMA_Config']->getUserValue(
                        'pma_db_filename_template',
                        $GLOBALS['cfg']['Export']['file_template_database']
                    )
                );
            } elseif ($export_type == 'table') {
                $html .= htmlspecialchars(
                    $GLOBALS['PMA_Config']->getUserValue(
                        'pma_table_filename_template',
                        $GLOBALS['cfg']['Export']['file_template_table']
                    )
                );
            } else {
                $html .= htmlspecialchars(
                    $GLOBALS['PMA_Config']->getUserValue(
                        'pma_server_filename_template',
                        $GLOBALS['cfg']['Export']['file_template_server']
                    )
                );
            }
        }
        $html .= '"';
        $html .= '/>';
        $html .= '<input type="checkbox" name="remember_template" ';
        $html .= 'id="checkbox_remember_template" ';
        $html .= self::exportCheckboxCheck('remember_file_template');
        $html .= '/>';
        $html .= '<label for="checkbox_remember_template">';
        $html .= __('use this for future exports');
        $html .= '</label>';
        $html .= '</li>';
        return $html;
    }

    /**
     * Prints Html For Export Options Charset
     *
     * @return string
     */
    public static function getHtmlForExportOptionsOutputCharset()
    {
        global $cfg;
        $html = '        <li><label for="select_charset" class="desc">'
            . __('Character set of the file:') . '</label>' . "\n";
        $html .= '<select id="select_charset" name="charset" size="1">';
        foreach (Encoding::listEncodings() as $temp_charset) {
            $html .= '<option value="' . $temp_charset . '"';
            if (isset($_GET['charset'])
                && ($_GET['charset'] != $temp_charset)
            ) {
                $html .= '';
            } elseif ((empty($cfg['Export']['charset']) && $temp_charset == 'utf-8')
                || $temp_charset == $cfg['Export']['charset']
            ) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $temp_charset . '</option>';
        } // end foreach
        $html .= '</select></li>';

        return $html;
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
