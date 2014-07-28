<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server, database and table export
 *
 * @usedby display_export.inc.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Outputs appropriate checked statement for checkbox.
 *
 * @param string $str option name
 *
 * @return string|void
 */
function PMA_exportCheckboxCheck($str)
{
    if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
        return ' checked="checked"';
    }
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
    $multi_values  = '<div style="text-align: left">';
    $multi_values .= '<a href="#"';
    $multi_values .= ' onclick="setSelectOptions'
        . '(\'dump\', \'db_select[]\', true); return false;">';
    $multi_values .= __('Select All');
    $multi_values .= '</a>';
    $multi_values .= ' / ';
    $multi_values .= '<a href="#"';
    $multi_values .= ' onclick="setSelectOptions'
        . '(\'dump\', \'db_select[]\', false); return false;">';
    $multi_values .= __('Unselect All') . '</a><br />';

    $multi_values .= '<select name="db_select[]" '
        . 'id="db_select" size="10" multiple="multiple">';
    $multi_values .= "\n";

    // Check if the selected databases are defined in $_GET
    // (from clicking Back button on export.php)
    if (isset($_GET['db_select'])) {
        $_GET['db_select'] = urldecode($_GET['db_select']);
        $_GET['db_select'] = explode(",", $_GET['db_select']);
    }

    foreach ($GLOBALS['pma']->databases as $current_db) {
        if ($current_db == 'information_schema'
            || $current_db == 'performance_schema'
            || $current_db == 'mysql'
        ) {
            continue;
        }
        if (isset($_GET['db_select'])) {
            if (in_array($current_db, $_GET['db_select'])) {
                $is_selected = ' selected="selected"';
            } else {
                $is_selected = '';
            }
        } elseif (!empty($tmp_select)) {
            if (strpos(' ' . $tmp_select, '|' . $current_db . '|')) {
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


    if (isset($_GET['sql_query'])) {
        $html .= '<input type="hidden" name="sql_query" value="'
            . htmlspecialchars($_GET['sql_query']) . '" />' . "\n";
    } elseif (! empty($sql_query)) {
        $html .= '<input type="hidden" name="sql_query" value="'
            . htmlspecialchars($sql_query) . '" />' . "\n";
    }

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
    $html .= PMA_Util::getImage('b_export.png', __('Export'));
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

    $html  = '<div class="exportoptions" id="quick_or_custom">';
    $html .= '<h3>' . __('Export Method:') . '</h3>';
    $html .= '<ul>';
    $html .= '<li>';
    $html .= '<input type="radio" name="quick_or_custom" value="quick" '
        . ' id="radio_quick_export"';
    if ($export_method == 'quick' || $export_method == 'quick_no_form') {
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
    if ($export_method == 'custom' || $export_method == 'custom_no_form') {
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
        $html .= '<h3>' . __('Database(s):') . '</h3>';
    } else if ($export_type == 'database') {
        $html .= '<h3>' . __('Table(s):') . '</h3>';
    }
    if (! empty($multi_values)) {
        $html .= $multi_values;
    }
    $html .= '</div>';

    return $html;
}

/**
 * Prints Html For Export Options Format
 *
 * @param array $export_list Export List
 *
 * @return string
 */
function PMA_getHtmlForExportOptionsFormat($export_list)
{
    $html  = '<div class="exportoptions" id="format">';
    $html .= '<h3>' . __('Format:') . '</h3>';
    $html .= PMA_pluginGetChoice('Export', 'what', $export_list, 'format');
    $html .= '</div>';

    $html .= '<div class="exportoptions" id="format_specific_opts">';
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

    $html .= PMA_Util::getExternalBug(
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
        $html .= PMA_Table::countRecords($db, $table);
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
        htmlspecialchars(PMA_Util::userDir($cfg['SaveDir']))
    );
    $html .= '</label>';
    $html .= '</li>';
    $html .= '<li>';
    $html .= '<input type="checkbox" name="quick_export_onserverover" ';
    $html .= 'value="saveitover" id="checkbox_quick_dump_onserverover" ';
    $html .= PMA_exportCheckboxCheck('quick_export_onserver_overwrite');
    $html .= '/>';
    $html .= '<label for="checkbox_quick_dump_onserverover">';
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
        htmlspecialchars(PMA_Util::userDir($cfg['SaveDir']))
    );
    $html .= '</label>';
    $html .= '</li>';
    $html .= '<li>';
    $html .= '<input type="checkbox" name="onserverover" value="saveitover"';
    $html .= ' id="checkbox_dump_onserverover" ';
    $html .= PMA_exportCheckboxCheck('onserver_overwrite');
    $html .= '/>';
    $html .= '<label for="checkbox_dump_onserverover">';
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
    $trans = new PMA_Message;
    $trans->addMessage(__('@SERVER@ will become the server name'));
    if ($export_type == 'database' || $export_type == 'table') {
        $trans->addMessage(__(', @DATABASE@ will become the database name'));
        if ($export_type == 'table') {
            $trans->addMessage(__(', @TABLE@ will become the table name'));
        }
    }

    $msg = new PMA_Message(
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
    $doc_url = PMA_Util::getDocuLink('faq', 'faq6-27');
    $msg->addParam(
        '<a href="' . $doc_url . '" target="documentation">',
        false
    );
    $msg->addParam('</a>', false);

    $html .= PMA_Util::showHint($msg);
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
    $html = '        <li><label for="select_charset_of_file" class="desc">'
        . __('Character set of the file:') . '</label>' . "\n";
    reset($cfg['AvailableCharsets']);
    $html .= '<select id="select_charset_of_file" name="charset_of_file" size="1">';
    foreach ($cfg['AvailableCharsets'] as $temp_charset) {
        $html .= '<option value="' . $temp_charset . '"';
        if (isset($_GET['charset_of_file'])
            && ($_GET['charset_of_file'] != $temp_charset)
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
 * @param string $export_type    Selected Export Type
 * @param string $db             Selected DB
 * @param string $table          Selected Table
 * @param string $multi_values   Export selection
 * @param string $num_tables     number of tables
 * @param array  $export_list    Export List
 * @param string $unlim_num_rows Number of Rows
 *
 * @return string
 */
function PMA_getHtmlForExportOptions(
    $export_type, $db, $table, $multi_values,
    $num_tables, $export_list, $unlim_num_rows
) {
    global $cfg;
    $html  = PMA_getHtmlForExportOptionHeader($export_type, $db, $table);
    $html .= PMA_getHtmlForExportOptionsMethod();
    $html .= PMA_getHtmlForExportOptionsSelection($export_type, $multi_values);

    if (strlen($table) && empty($num_tables) && ! PMA_Table::isMerge($db, $table)) {
        $html .= PMA_getHtmlForExportOptionsRows($db, $table, $unlim_num_rows);
    }

    if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
        $html .= PMA_getHtmlForExportOptionsQuickExport();
    }

    $html .= PMA_getHtmlForExportOptionsOutput($export_type);

    $html .= PMA_getHtmlForExportOptionsFormat($export_list);
    return $html;
}
?>
