<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server, database and table export
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;
use PMA\libraries\plugins\ExportPlugin;
use PMA\libraries\Table;

/**
 * Outputs appropriate checked statement for checkbox.
 *
 * @param string $str option name
 *
 * @return string
 */
function PMA_exportCheckboxCheck($str)
{
    if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
        return ' checked="checked"';
    }

    return null;
}

/**
 * Prints Html For Export Selection Options
 *
 * @param String $tmp_select Tmp selected method of export
 *
 * @return string
 */
function PMA_getHtmlForExportSelectOptions($tmp_select = '')
{
    $multi_values  = '<div>';
    $multi_values .= '<a href="#"';
    $multi_values .= ' onclick="setSelectOptions'
        . '(\'dump\', \'db_select[]\', true); return false;">';
    $multi_values .= __('Select all');
    $multi_values .= '</a>';
    $multi_values .= ' / ';
    $multi_values .= '<a href="#"';
    $multi_values .= ' onclick="setSelectOptions'
        . '(\'dump\', \'db_select[]\', false); return false;">';
    $multi_values .= __('Unselect all') . '</a><br />';

    $multi_values .= '<select name="db_select[]" '
        . 'id="db_select" size="10" multiple="multiple">';
    $multi_values .= "\n";

    // Check if the selected databases are defined in $_GET
    // (from clicking Back button on export.php)
    if (isset($_GET['db_select'])) {
        $_GET['db_select'] = urldecode($_GET['db_select']);
        $_GET['db_select'] = explode(",", $_GET['db_select']);
    }

    foreach ($GLOBALS['dblist']->databases as $current_db) {
        if ($GLOBALS['dbi']->isSystemSchema($current_db, true)) {
            continue;
        }
        if (isset($_GET['db_select'])) {
            if (in_array($current_db, $_GET['db_select'])) {
                $is_selected = ' selected="selected"';
            } else {
                $is_selected = '';
            }
        } elseif (!empty($tmp_select)) {
            if (mb_strpos(
                ' ' . $tmp_select,
                '|' . $current_db . '|'
            )) {
                $is_selected = ' selected="selected"';
            } else {
                $is_selected = '';
            }
        } else {
            $is_selected = ' selected="selected"';
        }
        $current_db   = htmlspecialchars($current_db);
        $multi_values .= '                <option value="' . $current_db . '"'
            . $is_selected . '>' . $current_db . '</option>' . "\n";
    } // end while
    $multi_values .= "\n";
    $multi_values .= '</select></div>';

    return $multi_values;
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
function PMA_getHtmlForHiddenInput(
    $export_type, $db, $table, $single_table, $sql_query
) {
    global $cfg;
    $html = "";
    if ($export_type == 'server') {
        $html .= PMA_URL_getHiddenInputs('', '', 1);
    } elseif ($export_type == 'database') {
        $html .= PMA_URL_getHiddenInputs($db, '', 1);
    } else {
        $html .= PMA_URL_getHiddenInputs($db, $table, 1);
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
function PMA_getHtmlForExportOptionHeader($export_type, $db, $table)
{
    $html  = '<div class="exportoptions" id="header">';
    $html .= '<h2>';
    $html .= PMA\libraries\Util::getImage('b_export.png', __('Export'));
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
function PMA_getHtmlForExportTemplateLoading($export_type)
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
    $html .= PMA_getOptionsForExportTemplates($export_type);
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
function PMA_getOptionsForExportTemplates($export_type)
{
    $ret = '<option value="">-- ' . __('Select a template') . ' --</option>';

    // Get the relation settings
    $cfgRelation = PMA_getRelationsParam();

    $query = "SELECT `id`, `template_name` FROM "
       . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
       . PMA\libraries\Util::backquote($cfgRelation['export_templates'])
       . " WHERE `username` = "
       . "'" . PMA\libraries\Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user'])
        . "' AND `export_type` = '" . PMA\libraries\Util::sqlAddSlashes($export_type) . "'"
       . " ORDER BY `template_name`;";

    $result = PMA_queryAsControlUser($query);
    if (!$result) {
        return $ret;
    }

    while ($row = $GLOBALS['dbi']->fetchAssoc($result, $GLOBALS['controllink'])) {
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
function PMA_getHtmlForExportOptionsMethod()
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
function PMA_getHtmlForExportOptionsSelection($export_type, $multi_values)
{
    $html = '<div class="exportoptions" id="databases_and_tables">';
    if ($export_type == 'server') {
        $html .= '<h3>' . __('Databases:') . '</h3>';
    } else if ($export_type == 'database') {
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
function PMA_getHtmlForExportOptionsFormatDropdown($export_list)
{
    $html  = '<div class="exportoptions" id="format">';
    $html .= '<h3>' . __('Format:') . '</h3>';
    $html .= PMA_pluginGetChoice('Export', 'what', $export_list, 'format');
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
function PMA_getHtmlForExportOptionsFormat($export_list)
{
    $html = '<div class="exportoptions" id="format_specific_opts">';
    $html .= '<h3>' . __('Format-specific options:') . '</h3>';
    $html .= '<p class="no_js_msg" id="scroll_to_options_msg">';
    $html .= __(
        'Scroll down to fill in the options for the selected format '
        . 'and ignore the options for other formats.'
    );
    $html .= '</p>';
    $html .= PMA_pluginGetOptions('Export', $export_list);
    $html .= '</div>';

    if (function_exists('PMA_Kanji_encodingForm')) {
        // Encoding setting form appended by Y.Kawada
        // Japanese encoding setting
        $html .= '<div class="exportoptions" id="kanji_encoding">';
        $html .= '<h3>' . __('Encoding Conversion:') . '</h3>';
        $html .= PMA_Kanji_encodingForm();
        $html .= '</div>';
    }

    $html .= '<div class="exportoptions" id="submit">';

    $html .= PMA\libraries\Util::getExternalBug(
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
function PMA_getHtmlForExportOptionsRows($db, $table, $unlim_num_rows)
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
function PMA_getHtmlForExportOptionsQuickExport()
{
    global $cfg;
    $html  = '<div class="exportoptions" id="output_quick_export">';
    $html .= '<h3>' . __('Output:') . '</h3>';
    $html .= '<ul>';
    $html .= '<li>';
    $html .= '<input type="checkbox" name="quick_export_onserver" value="saveit" ';
    $html .= 'id="checkbox_quick_dump_onserver" ';
    $html .= PMA_exportCheckboxCheck('quick_export_onserver');
    $html .= '/>';
    $html .= '<label for="checkbox_quick_dump_onserver">';
    $html .= sprintf(
        __('Save on server in the directory <b>%s</b>'),
        htmlspecialchars(PMA\libraries\Util::userDir($cfg['SaveDir']))
    );
    $html .= '</label>';
    $html .= '</li>';
    $html .= '<li>';
    $html .= '<input type="checkbox" name="quick_export_onserver_overwrite" ';
    $html .= 'value="saveitover" id="checkbox_quick_dump_onserver_overwrite" ';
    $html .= PMA_exportCheckboxCheck('quick_export_onserver_overwrite');
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
function PMA_getHtmlForExportOptionsOutputSaveDir()
{
    global $cfg;
    $html  = '<li>';
    $html .= '<input type="checkbox" name="onserver" value="saveit" ';
    $html .= 'id="checkbox_dump_onserver" ';
    $html .= PMA_exportCheckboxCheck('onserver');
    $html .= '/>';
    $html .= '<label for="checkbox_dump_onserver">';
    $html .= sprintf(
        __('Save on server in the directory <b>%s</b>'),
        htmlspecialchars(PMA\libraries\Util::userDir($cfg['SaveDir']))
    );
    $html .= '</label>';
    $html .= '</li>';
    $html .= '<li>';
    $html .= '<input type="checkbox" name="onserver_overwrite" value="saveitover"';
    $html .= ' id="checkbox_dump_onserver_overwrite" ';
    $html .= PMA_exportCheckboxCheck('onserver_overwrite');
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
function PMA_getHtmlForExportOptionsOutputFormat($export_type)
{
    $html  = '<li>';
    $html .= '<label for="filename_template" class="desc">';
    $html .= __('File name template:');
    $trans = new Message;
    $trans->addMessage(__('@SERVER@ will become the server name'));
    if ($export_type == 'database' || $export_type == 'table') {
        $trans->addMessage(__(', @DATABASE@ will become the database name'));
        if ($export_type == 'table') {
            $trans->addMessage(__(', @TABLE@ will become the table name'));
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
    $msg->addParam(
        '<a href="' . PMA_linkURL(PMA_getPHPDocLink('function.strftime.php'))
        . '" target="documentation" title="' . __('Documentation') . '">',
        false
    );
    $msg->addParam('</a>', false);
    $msg->addParam($trans);
    $doc_url = PMA\libraries\Util::getDocuLink('faq', 'faq6-27');
    $msg->addParam(
        '<a href="' . $doc_url . '" target="documentation">',
        false
    );
    $msg->addParam('</a>', false);

    $html .= PMA\libraries\Util::showHint($msg);
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
    $html .= PMA_exportCheckboxCheck('remember_file_template');
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
function PMA_getHtmlForExportOptionsOutputCharset()
{
    global $cfg;
    $html = '        <li><label for="select_charset" class="desc">'
        . __('Character set of the file:') . '</label>' . "\n";
    $html .= '<select id="select_charset" name="charset" size="1">';
    foreach ($cfg['AvailableCharsets'] as $temp_charset) {
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
function PMA_getHtmlForExportOptionsOutputCompression()
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
function PMA_getHtmlForExportOptionsOutputRadio()
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
function PMA_getHtmlForExportOptionsOutputSeparateFiles($export_type)
{
    $html  = '<li>';
    $html .= '<input type="checkbox" id="checkbox_as_separate_files" '
        . PMA_exportCheckboxCheck('as_separate_files')
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
function PMA_getHtmlForExportOptionsOutput($export_type)
{
    global $cfg;
    $html  = '<div class="exportoptions" id="output">';
    $html .= '<h3>' . __('Output:') . '</h3>';
    $html .= '<ul id="ul_output">';
    $html .= '<li><input type="checkbox" id="btn_alias_config" ';
    if (isset($_SESSION['tmpval']['aliases'])
        && !PMA_emptyRecursive($_SESSION['tmpval']['aliases'])
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
            $html .= PMA_exportCheckboxCheck('lock_tables') . '/>';
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
        $html .= PMA_exportCheckboxCheck('asfile');
    }
    $html .= '/>';
    $html .= '<label for="radio_dump_asfile">'
        . __('Save output to a file') . '</label>';
    $html .= '<ul id="ul_save_asfile">';
    if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
        $html .= PMA_getHtmlForExportOptionsOutputSaveDir();
    }

    $html .= PMA_getHtmlForExportOptionsOutputFormat($export_type);

    // charset of file
    if ($GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE) {
        $html .= PMA_getHtmlForExportOptionsOutputCharset();
    } // end if

    $html .= PMA_getHtmlForExportOptionsOutputCompression();

    if ($export_type == 'server'
        || $export_type == 'database'
    ) {
        $html .= PMA_getHtmlForExportOptionsOutputSeparateFiles($export_type);
    }

    $html .= '</ul>';
    $html .= '</li>';

    $html .= PMA_getHtmlForExportOptionsOutputRadio();

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
function PMA_getHtmlForExportOptions(
    $export_type, $db, $table, $multi_values,
    $num_tables, $export_list, $unlim_num_rows
) {
    global $cfg;
    $html  = PMA_getHtmlForExportOptionsMethod();
    $html .= PMA_getHtmlForExportOptionsFormatDropdown($export_list);
    $html .= PMA_getHtmlForExportOptionsSelection($export_type, $multi_values);

    $tableLength = mb_strlen($table);
    $_table = new Table($table, $db);
    if ($tableLength && empty($num_tables) && ! $_table->isMerge()) {
        $html .= PMA_getHtmlForExportOptionsRows($db, $table, $unlim_num_rows);
    }

    if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
        $html .= PMA_getHtmlForExportOptionsQuickExport();
    }

    $html .= PMA_getHtmlForAliasModalDialog($db, $table);
    $html .= PMA_getHtmlForExportOptionsOutput($export_type);
    $html .= PMA_getHtmlForExportOptionsFormat($export_list);
    return $html;
}

/**
 * Prints Html For Alias Modal Dialog
 *
 * @param String $db    Selected DB
 * @param String $table Selected Table
 *
 * @return string
 */
function PMA_getHtmlForAliasModalDialog($db = '', $table = '')
{
    if (isset($_SESSION['tmpval']['aliases'])) {
        $aliases = $_SESSION['tmpval']['aliases'];
    }
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
    if (empty($db)) {
        $databases = $GLOBALS['dbi']->getColumnsFull(
            null, null, null, $GLOBALS['userlink']
        );
        foreach ($dbs_not_allowed as $db) {
            unset($databases[$db]);
        }
        // Database export does not have table set.
    } elseif (empty($table)) {
        $tables = $GLOBALS['dbi']->getColumnsFull(
            $db, null, null, $GLOBALS['userlink']
        );
        $databases = array($db => $tables);
        // Table export
    } else {
        $columns = $GLOBALS['dbi']->getColumnsFull(
            $db, $table, null, $GLOBALS['userlink']
        );
        $databases = array(
            $db => array(
                $table => $columns
            )
        );
    }

    $html = '<div id="alias_modal" class="hide" title="' . $title . '">';
    $db_html = '<label class="col-2">' . __('Select database') . ': '
        . '</label><select id="db_alias_select">';
    $table_html = '<label class="col-2">' . __('Select table') . ': </label>';
    $first_db = true;
    $table_input_html = $db_input_html = '';
    foreach ($databases as  $db => $tables) {
        $val = '';
        if (!empty($aliases[$db]['alias'])) {
            $val = htmlspecialchars($aliases[$db]['alias']);
        }
        $db = htmlspecialchars($db);
        $name_attr = 'aliases[' . $db . '][alias]';
        $id_attr = substr(md5($name_attr), 0, 12);
        $class = 'hide';
        if ($first_db) {
            $first_db = false;
            $class = '';
            $db_input_html = '<label class="col-2" for="' . $id_attr . '">'
                . __('New database name') . ': </label>';
        }
        $db_input_html .= '<input type="text" name="' . $name_attr . '" '
            . 'placeholder="' . $db . ' alias" class="' . $class . '" '
            . 'id="' . $id_attr . '" value="' . $val . '" disabled="disabled"/>';
        $db_html .= '<option value="' . $id_attr . '">' . $db . '</option>';
        $table_html .= '<span id="' . $id_attr . '_tables" class="' . $class . '">';
        $table_html .= '<select id="' . $id_attr . '_tables_select" '
            . 'class="table_alias_select">';
        $first_tbl = true;
        $col_html = '';
        foreach ($tables as $table => $columns) {
            $val = '';
            if (!empty($aliases[$db]['tables'][$table]['alias'])) {
                $val = htmlspecialchars($aliases[$db]['tables'][$table]['alias']);
            }
            $table = htmlspecialchars($table);
            $name_attr =  'aliases[' . $db . '][tables][' . $table . '][alias]';
            $id_attr = substr(md5($name_attr), 0, 12);
            $class = 'hide';
            if ($first_tbl) {
                $first_tbl = false;
                $class = '';
                $table_input_html = '<label class="col-2" for="' . $id_attr . '">'
                    . __('New table name') . ': </label>';
            }
            $table_input_html .= '<input type="text" value="' . $val . '" '
                . 'name="' . $name_attr . '" id="' . $id_attr . '" '
                . 'placeholder="' . $table . ' alias" class="' . $class . '" '
                . 'disabled="disabled"/>';
            $table_html .= '<option value="' . $id_attr . '">'
                . $table . '</option>';
            $col_html .= '<table id="' . $id_attr . '_cols" class="'
                . $class . '" width="100%">';
            $col_html .= '<thead><tr><th>' . __('Old column name') . '</th>'
                . '<th>' . __('New column name') . '</th></tr></thead><tbody>';
            $class = 'odd';
            foreach ($columns as $column => $col_def) {
                $val = '';
                if (!empty($aliases[$db]['tables'][$table]['columns'][$column])) {
                    $val = htmlspecialchars(
                        $aliases[$db]['tables'][$table]['columns'][$column]
                    );
                }
                $column = htmlspecialchars($column);
                $name_attr = 'aliases[' . $db . '][tables][' . $table
                    . '][columns][' . $column . ']';
                $id_attr = substr(md5($name_attr), 0, 12);
                $col_html .= '<tr class="' . $class . '">';
                $col_html .= '<th><label for="' . $id_attr . '">' . $column
                    . '</label></th>';
                $col_html .= '<td><dummy_inp type="text" name="' . $name_attr . '" '
                    . 'id="' . $id_attr . '" placeholder="'
                    . $column . ' alias" value="' . $val . '"></dummy_inp></td>';
                $col_html .= '</tr>';
                $class = $class === 'odd' ? 'even' : 'odd';
            }
            $col_html .= '</tbody></table>';
        }
        $table_html .= '</select>';
        $table_html .= $table_input_html . '<hr/>' . $col_html . '</span>';
    }
    $db_html .= '</select>';
    $html .= $db_html;
    $html .= $db_input_html . '<hr/>';
    $html .= $table_html;

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
function PMA_getExportDisplay(
    $export_type, $db, $table, $sql_query, $num_tables,
    $unlim_num_rows, $multi_values
) {
    $cfgRelation = PMA_getRelationsParam();

    if (isset($_REQUEST['single_table'])) {
        $GLOBALS['single_table'] = $_REQUEST['single_table'];
    }

    include_once './libraries/file_listing.lib.php';
    include_once './libraries/plugin_interface.lib.php';
    include_once './libraries/display_export.lib.php';

    /* Scan for plugins */
    /* @var $export_list ExportPlugin[] */
    $export_list = PMA_getPlugins(
        "export",
        'libraries/plugins/export/',
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

    $html = PMA_getHtmlForExportOptionHeader($export_type, $db, $table);

    if ($cfgRelation['exporttemplateswork']) {
        $html .= PMA_getHtmlForExportTemplateLoading($export_type);
    }

    $html .= '<form method="post" action="export.php" '
        . ' name="dump" class="disableAjax">';

    //output Hidden Inputs
    $single_table_str = isset($GLOBALS['single_table']) ? $GLOBALS['single_table']
        : '';
    $html .= PMA_getHtmlForHiddenInput(
        $export_type,
        $db,
        $table,
        $single_table_str,
        $sql_query
    );

    //output Export Options
    $html .= PMA_getHtmlForExportOptions(
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
function PMA_handleExportTemplateActions($cfgRelation)
{
    if (isset($_REQUEST['templateId'])) {
        $id = PMA\libraries\Util::sqlAddSlashes($_REQUEST['templateId']);
    } else {
        $id = '';
    }

    $templateTable = PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
       . PMA\libraries\Util::backquote($cfgRelation['export_templates']);
    $user = PMA\libraries\Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']);

    switch ($_REQUEST['templateAction']) {
    case 'create':
        $query = "INSERT INTO " . $templateTable . "("
            . " `username`, `export_type`,"
            . " `template_name`, `template_data`"
            . ") VALUES ("
            . "'" . $user . "', "
            . "'" . PMA\libraries\Util::sqlAddSlashes($_REQUEST['exportType'])
            . "', '" . PMA\libraries\Util::sqlAddSlashes($_REQUEST['templateName'])
            . "', '" . PMA\libraries\Util::sqlAddSlashes($_REQUEST['templateData'])
            . "');";
        break;
    case 'load':
        $query = "SELECT `template_data` FROM " . $templateTable
             . " WHERE `id` = " . $id  . " AND `username` = '" . $user . "'";
        break;
    case 'update':
        $query = "UPDATE " . $templateTable . " SET `template_data` = "
          . "'" . PMA\libraries\Util::sqlAddSlashes($_REQUEST['templateData']) . "'"
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

    $result = PMA_queryAsControlUser($query, false);

    $response = PMA\libraries\Response::getInstance();
    if (! $result) {
        $error = $GLOBALS['dbi']->getError($GLOBALS['controllink']);
        $response->setRequestStatus(false);
        $response->addJSON('message', $error);
        exit;
    }

    $response->setRequestStatus(true);
    if ('create' == $_REQUEST['templateAction']) {
        $response->addJSON(
            'data',
            PMA_getOptionsForExportTemplates($_REQUEST['exportType'])
        );
    } elseif ('load' == $_REQUEST['templateAction']) {
        $data = null;
        while ($row = $GLOBALS['dbi']->fetchAssoc(
            $result, $GLOBALS['controllink']
        )) {
            $data = $row['template_data'];
        }
        $response->addJSON('data', $data);
    }
    $GLOBALS['dbi']->freeResult($result);
}
