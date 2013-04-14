<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays export tab.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

// Get relations & co. status
$cfgRelation = PMA_getRelationsParam();

if (isset($_REQUEST['single_table'])) {
    $GLOBALS['single_table'] = $_REQUEST['single_table'];
}

require_once './libraries/file_listing.lib.php';
require_once './libraries/plugin_interface.lib.php';

/**
 * Outputs appropriate checked statement for checkbox.
 *
 * @param string $str option name
 *
 * @return void
 */
function PMA_exportCheckboxCheck($str)
{
    if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
        echo ' checked="checked"';
    }
}

/* Scan for plugins */
$export_list = PMA_getPlugins(
    "export",
    'libraries/plugins/export/',
    array(
        'export_type' => $export_type,
        'single_table' => isset($single_table)
    )
);

/* Fail if we didn't find any plugin */
if (empty($export_list)) {
    PMA_Message::error(
        __('Could not load export plugins, please check your installation!')
    )->display();
    exit;
}

echo '<form method="post" action="export.php" name="dump" class="disableAjax">';

if ($export_type == 'server') {
    echo PMA_generate_common_hidden_inputs('', '', 1);
} elseif ($export_type == 'database') {
    echo PMA_generate_common_hidden_inputs($db, '', 1);
} else {
    echo PMA_generate_common_hidden_inputs($db, $table, 1);
}

// just to keep this value for possible next display of this form after saving
// on server
if (isset($single_table)) {
    echo '<input type="hidden" name="single_table" value="TRUE" />' . "\n";
}

echo '<input type="hidden" name="export_type" value="' . $export_type . '" />';
echo "\n";

// If the export method was not set, the default is quick
if (isset($_GET['export_method'])) {
    $cfg['Export']['method'] = $_GET['export_method'];
} elseif (! isset($cfg['Export']['method'])) {
    $cfg['Export']['method'] = 'quick';
}
// The export method (quick, custom or custom-no-form)
echo '<input type="hidden" name="export_method" value="'
    . htmlspecialchars($cfg['Export']['method']) . '" />';


if (isset($_GET['sql_query'])) {
    echo '<input type="hidden" name="sql_query" value="'
        . htmlspecialchars($_GET['sql_query']) . '" />' . "\n";
} elseif (! empty($sql_query)) {
    echo '<input type="hidden" name="sql_query" value="'
        . htmlspecialchars($sql_query) . '" />' . "\n";
}

echo '<div class="exportoptions" id="header">';
echo '<h2>';
echo PMA_Util::getImage('b_export.png', __('Export'));
if ($export_type == 'server') {
    echo __('Exporting databases from the current server');
} elseif ($export_type == 'database') {
    printf(__('Exporting tables from "%s" database'), htmlspecialchars($db));
} else {
    printf(__('Exporting rows from "%s" table'), htmlspecialchars($table));
}
echo '</h2>';
echo '</div>';

if (isset($_GET['quick_or_custom'])) {
    $export_method = $_GET['quick_or_custom'];
} else {
    $export_method = $cfg['Export']['method'];
}

echo '<div class="exportoptions" id="quick_or_custom">';
echo '<h3>' . __('Export Method:') . '</h3>';
echo '<ul>';
echo '<li>';
echo '<input type="radio" name="quick_or_custom" value="quick" '
    . ' id="radio_quick_export"';
if ($export_method == 'quick' || $export_method == 'quick_no_form') {
    echo ' checked="checked"';
}
echo ' />';
echo '<label for ="radio_quick_export">';
echo __('Quick - display only the minimal options');
echo '</label>';
echo '</li>';

echo '<li>';
echo '<input type="radio" name="quick_or_custom" value="custom" '
    . ' id="radio_custom_export"';
if ($export_method == 'custom' || $export_method == 'custom_no_form') {
    echo ' checked="checked"';
}
echo ' />';
echo '<label for="radio_custom_export">';
echo __('Custom - display all possible options');
echo '</label>';
echo '</li>';

echo '</ul>';
echo '</div>';

echo '<div class="exportoptions" id="databases_and_tables">';
if ($export_type == 'server') {
    echo '<h3>' . __('Database(s):') . '</h3>';
} else if ($export_type == 'database') {
    echo '<h3>' . __('Table(s):') . '</h3>';
}
if (! empty($multi_values)) {
    echo $multi_values;
}
echo '</div>';

if (strlen($table) && ! isset($num_tables) && ! PMA_Table::isMerge($db, $table)) {
    echo '<div class="exportoptions" id="rows">';
    echo '<h3>' . __('Rows:') . '</h3>';
    echo '<ul>';
    echo '<li>';
    echo '<input type="radio" name="allrows" value="0" id="radio_allrows_0"';
    if (isset($_GET['allrows']) && $_GET['allrows'] == 0) {
        echo ' checked="checked"';
    }
    echo '/>';
    echo '<label for ="radio_allrows_0">' . __('Dump some row(s)') . '</label>';
    echo '<ul>';
    echo '<li>';
    echo '<label for="limit_to">' . __('Number of rows:') . '</label>';
    echo '<input type="text" id="limit_to" name="limit_to" size="5" value="';
    if (isset($_GET['limit_to'])) {
        echo htmlspecialchars($_GET['limit_to']);
    } elseif (isset($unlim_num_rows)) {
        echo $unlim_num_rows;
    } else {
        echo PMA_Table::countRecords($db, $table);
    }
    echo '" onfocus="this.select()" />';
    echo '</li>';
    echo '<li>';
    echo '<label for="limit_from">' . __('Row to begin at:') . '</label>';
    echo '<input type="text" id="limit_from" name="limit_from" value="';
    if (isset($_GET['limit_from'])) {
        echo htmlspecialchars($_GET['limit_from']);
    } else {
        echo '0';
    }
    echo '" size="5" onfocus="this.select()" />';
    echo '</li>';
    echo '</ul>';
    echo '</li>';
    echo '<li>';
    echo '<input type="radio" name="allrows" value="1" id="radio_allrows_1"';
    if (! isset($_GET['allrows']) || $_GET['allrows'] == 1) {
        echo ' checked="checked"';
    }
    echo '/>';
    echo ' <label for="radio_allrows_1">' . __('Dump all rows') . '</label>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
}

if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
    echo '<div class="exportoptions" id="output_quick_export">';
    echo '<h3>' . __('Output:') . '</h3>';
    echo '<ul>';
    echo '<li>';
    echo '<input type="checkbox" name="quick_export_onserver" value="saveit" ';
    echo 'id="checkbox_quick_dump_onserver" ';
    PMA_exportCheckboxCheck('quick_export_onserver');
    echo '/>';
    echo '<label for="checkbox_quick_dump_onserver">';
    printf(
        __('Save on server in the directory <b>%s</b>'),
        htmlspecialchars(PMA_Util::userDir($cfg['SaveDir']))
    );
    echo '</label>';
    echo '</li>';
    echo '<li>';
    echo '<input type="checkbox" name="quick_export_onserverover" ';
    echo 'value="saveitover" id="checkbox_quick_dump_onserverover" ';
    PMA_exportCheckboxCheck('quick_export_onserver_overwrite');
    echo '/>';
    echo '<label for="checkbox_quick_dump_onserverover">';
    echo __('Overwrite existing file(s)');
    echo '</label>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
}

echo '<div class="exportoptions" id="output">';
echo '<h3>' . __('Output:') . '</h3>';
echo '<ul id="ul_output">';
echo '<li>';
echo '<input type="radio" name="output_format" value="sendit" ';
echo 'id="radio_dump_asfile" ';
if (!isset($_GET['repopulate'])) {
    PMA_exportCheckboxCheck('asfile');
}
echo '/>';
echo '<label for="radio_dump_asfile">' . __('Save output to a file') . '</label>';
echo '<ul id="ul_save_asfile">';
if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) {
    echo '<li>';
    echo '<input type="checkbox" name="onserver" value="saveit" ';
    echo 'id="checkbox_dump_onserver" ';
    PMA_exportCheckboxCheck('onserver');
    echo '/>';
    echo '<label for="checkbox_dump_onserver">';
    printf(
        __('Save on server in the directory <b>%s</b>'),
        htmlspecialchars(PMA_Util::userDir($cfg['SaveDir']))
    );
    echo '</label>';
    echo '</li>';
    echo '<li>';
    echo '<input type="checkbox" name="onserverover" value="saveitover"';
    echo ' id="checkbox_dump_onserverover" ';
    PMA_exportCheckboxCheck('onserver_overwrite');
    echo '/>';
    echo '<label for="checkbox_dump_onserverover">';
    echo __('Overwrite existing file(s)');
    echo '</label>';
    echo '</li>';
}
echo '<li>';
echo '<label for="filename_template" class="desc">';
echo __('File name template:');
$trans = new PMA_Message;
$trans->addMessage(__('@SERVER@ will become the server name'));
if ($export_type == 'database' || $export_type == 'table') {
    $trans->addMessage(__(', @DATABASE@ will become the database name'));
    if ($export_type == 'table') {
        $trans->addMessage(__(', @TABLE@ will become the table name'));
    }
}

$msg = new PMA_Message(
    __('This value is interpreted using %1$sstrftime%2$s, so you can use time formatting strings. Additionally the following transformations will happen: %3$s. Other text will be kept as is. See the %4$sFAQ%5$s for details.')
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
    '<a href="'. $doc_url . '" target="documentation">',
    false
);
$msg->addParam('</a>', false);

echo PMA_Util::showHint($msg);
echo '</label>';
echo '<input type="text" name="filename_template" id="filename_template" ';
echo ' value="';
if (isset($_GET['filename_template'])) {
    echo htmlspecialchars($_GET['filename_template']);
} else {
    if ($export_type == 'database') {
        echo htmlspecialchars(
            $GLOBALS['PMA_Config']->getUserValue(
                'pma_db_filename_template',
                $GLOBALS['cfg']['Export']['file_template_database']
            )
        );
    } elseif ($export_type == 'table') {
        echo htmlspecialchars(
            $GLOBALS['PMA_Config']->getUserValue(
                'pma_table_filename_template',
                $GLOBALS['cfg']['Export']['file_template_table']
            )
        );
    } else {
        echo htmlspecialchars(
            $GLOBALS['PMA_Config']->getUserValue(
                'pma_server_filename_template',
                $GLOBALS['cfg']['Export']['file_template_server']
            )
        );
    }
}
echo '"';
echo '/>';
echo '<input type="checkbox" name="remember_template" ';
echo 'id="checkbox_remember_template" ';
PMA_exportCheckboxCheck('remember_file_template');
echo '/>';
echo '<label for="checkbox_remember_template">';
echo __('use this for future exports');
echo '</label>';
echo '</li>';
// charset of file
if ($GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE) {
    echo '        <li><label for="select_charset_of_file" class="desc">'
        . __('Character set of the file:') . '</label>' . "\n";
    reset($cfg['AvailableCharsets']);
    echo '<select id="select_charset_of_file" name="charset_of_file" size="1">';
    foreach ($cfg['AvailableCharsets'] as $temp_charset) {
        echo '<option value="' . $temp_charset . '"';
        if (isset($_GET['charset_of_file'])
            && ($_GET['charset_of_file'] != $temp_charset)
        ) {
            echo '';
        } elseif ((empty($cfg['Export']['charset']) && $temp_charset == 'utf-8')
            || $temp_charset == $cfg['Export']['charset']
        ) {
            echo ' selected="selected"';
        }
        echo '>' . $temp_charset . '</option>';
    } // end foreach
    echo '</select></li>';
} // end if

if (isset($_GET['compression'])) {
    $selected_compression = $_GET['compression'];
} elseif (isset($cfg['Export']['compression'])) {
    $selected_compression = $cfg['Export']['compression'];
} else {
    $selected_compression = "none";
}

// zip, gzip and bzip2 encode features
$is_zip  = ($cfg['ZipDump']  && @function_exists('gzcompress'));
$is_gzip = ($cfg['GZipDump'] && @function_exists('gzencode'));
$is_bzip2 = ($cfg['BZipDump'] && @function_exists('bzcompress'));
if ($is_zip || $is_gzip || $is_bzip2) {
    echo '<li>';
    echo '<label for="compression" class="desc">' . __('Compression:') . '</label>';
    echo '<select id="compression" name="compression">';
    echo '<option value="none">' . __('None') . '</option>';
    if ($is_zip) {
        echo '<option value="zip" ';
        if ($selected_compression == "zip") {
            echo 'selected="selected"';
        }
        echo '>' . __('zipped') . '</option>';
    }
    if ($is_gzip) {
        echo '<option value="gzip" ';
        if ($selected_compression == "gzip") {
            echo 'selected="selected"';
        }
        echo '>' . __('gzipped') . '</option>';
    }
    if ($is_bzip2) {
        echo '<option value="bzip2" ';
        if ($selected_compression == "bzip2") {
            echo 'selected="selected"';
        }
        echo '>' . __('bzipped') . '</option>';
    }
    echo '</select>';
    echo '</li>';
} else {
    echo '<input type="hidden" name="compression" value="'
        . htmlspecialchars($selected_compression) . '" />';
}
echo '</ul>';
echo '</li>';
echo '<li>';
echo '<input type="radio" id="radio_view_as_text" name="output_format" '
    . 'value="astext" ';
if (isset($_GET['repopulate']) || $GLOBALS['cfg']['Export']['asfile'] == false) {
    echo 'checked="checked"';
}
echo '/>';
echo '<label for="radio_view_as_text">'
    . __('View output as text') . '</label></li>';
echo '</ul>';
echo '</div>';

echo '<div class="exportoptions" id="format">';
echo '<h3>' . __('Format:') . '</h3>';
echo PMA_pluginGetChoice('Export', 'what', $export_list, 'format');
echo '</div>';

echo '<div class="exportoptions" id="format_specific_opts">';
echo '<h3>' . __('Format-specific options:') . '</h3>';
echo '<p class="no_js_msg" id="scroll_to_options_msg">';
echo __('Scroll down to fill in the options for the selected format and ignore the options for other formats.');
echo '</p>';
echo PMA_pluginGetOptions('Export', $export_list);
echo '</div>';

if (function_exists('PMA_set_enc_form')) {
    // Encoding setting form appended by Y.Kawada
    // Japanese encoding setting
    echo '<div class="exportoptions" id="kanji_encoding">';
    echo '<h3>' . __('Encoding Conversion:') . '</h3>';
    echo PMA_set_enc_form('            ');
    echo '</div>';
}

echo '<div class="exportoptions" id="submit">';

echo PMA_Util::getExternalBug(
    __('SQL compatibility mode'), 'mysql', '50027', '14515'
);

echo '<input type="submit" value="' . __('Go') . '" id="buttonGo" />';
echo '</div>';
echo '</form>';
