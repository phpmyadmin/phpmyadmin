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

$common_functions = PMA_CommonFunctions::getInstance();
// Get relations & co. status
$cfgRelation = PMA_getRelationsParam();

if (isset($_REQUEST['single_table'])) {
    $GLOBALS['single_table'] = $_REQUEST['single_table'];
}

require_once './libraries/file_listing.php';
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

echo '<form method="post" action="export.php" name="dump">';

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
echo $common_functions->getImage('b_export.png', __('Export'));
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
echo '<input type="radio" name="quick_or_custom" value="quick" id="radio_quick_export"';
if ($export_method == 'quick' || $export_method == 'quick_no_form') {
    echo ' checked="checked"';
}
echo ' />';
echo '<label for ="radio_quick_export">';
echo __('Quick - display only the minimal options');
echo '</label>';
echo '</li>';

echo '<li>';
echo '<input type="radio" name="quick_or_custom" value="custom" id="radio_custom_export"';
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
    if (isset($_GET['allrows']) && $_GET['allrows'] == 1) {
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
        htmlspecialchars($common_functions->userDir($cfg['SaveDir']))
    );
    echo '</label>';
    echo '</li>';
    echo '<li>';
    echo '<input type="checkbox" name="quick_export_onserverover" ';
    echo 'value="saveitover" id="checkbox_quick_dump_onserverover" ';
    PMA_exportCheckboxCheck('quick_export_onserver_overwrite');
    echo '<label for="checkbox_quick_dump_onserverover">';
    echo __('Overwrite existing file(s)');
    echo '</label>';
    echo '</li>';
    echo '</ul>';
    echo '</div>';
} ?>

<div class="exportoptions" id="output">
    <h3><?php echo __('Output:'); ?></h3>
    <ul id="ul_output">
        <li>
            <input type="radio" name="output_format" value="sendit" id="radio_dump_asfile" <?php isset($_GET['repopulate']) ? '' : PMA_exportCheckboxCheck('asfile'); ?> />
            <label for="radio_dump_asfile"><?php echo __('Save output to a file'); ?></label>
            <ul id="ul_save_asfile">
                <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
                <li>
                    <input type="checkbox" name="onserver" value="saveit"
                        id="checkbox_dump_onserver"
                        <?php PMA_exportCheckboxCheck('onserver'); ?> />
                    <label for="checkbox_dump_onserver">
                        <?php echo sprintf(__('Save on server in the directory <b>%s</b>'), htmlspecialchars($common_functions->userDir($cfg['SaveDir']))); ?>
                    </label>
                </li>
                <li>
                    <input type="checkbox" name="onserverover" value="saveitover"
                    id="checkbox_dump_onserverover"
                    <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
                    <label for="checkbox_dump_onserverover"><?php echo __('Overwrite existing file(s)'); ?></label>
                </li>
                <?php } ?>
                <li>
                    <label for="filename_template" class="desc">
                    <?php
                    echo __('File name template:');
                    $trans = new PMA_Message;
                    $trans->addMessage(__('@SERVER@ will become the server name'));
                    if ($export_type == 'database' || $export_type == 'table') {
                        $trans->addMessage(__(', @DATABASE@ will become the database name'));
                        if ($export_type == 'table') {
                            $trans->addMessage(__(', @TABLE@ will become the table name'));
                        }
                    }

                    $msg = new PMA_Message(__('This value is interpreted using %1$sstrftime%2$s, so you can use time formatting strings. Additionally the following transformations will happen: %3$s. Other text will be kept as is. See the %4$sFAQ%5$s for details.'));
                    $msg->addParam(
                        '<a href="' . PMA_linkURL(PMA_getPHPDocLink('function.strftime.php'))
                        . '" target="documentation" title="' . __('Documentation') . '">',
                        false
                    );
                    $msg->addParam('</a>', false);
                    $msg->addParam($trans);
                    $msg->addParam('<a href="Documentation.html#faq6_27" target="documentation">', false);
                    $msg->addParam('</a>', false);

                    echo $common_functions->showHint($msg);
                    ?>
                    </label>
                    <input type="text" name="filename_template" id="filename_template"
                    <?php
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
                    ?>
                    />
                    <input type="checkbox" name="remember_template"
                        id="checkbox_remember_template"
                        <?php PMA_exportCheckboxCheck('remember_file_template'); ?> />
                    <label for="checkbox_remember_template">
                        <?php echo __('use this for future exports'); ?></label>
                </li>
                <?php
                // charset of file
                if ($GLOBALS['PMA_recoding_engine'] != PMA_CHARSET_NONE) {
                    echo '        <li><label for="select_charset_of_file" class="desc">'
                        . __('Character set of the file:') . '</label>' . "\n";
                    reset($cfg['AvailableCharsets']);
                    echo '<select id="select_charset_of_file" name="charset_of_file" size="1">';
                    foreach ($cfg['AvailableCharsets'] as $temp_charset) {
                        echo '<option value="' . $temp_charset . '"';
                        if (isset($_GET['charset_of_file']) && ($_GET['charset_of_file'] != $temp_charset)) {
                            echo '';
                        } elseif ((empty($cfg['Export']['charset']) && $temp_charset == 'utf-8')
                          || $temp_charset == $cfg['Export']['charset']) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $temp_charset . '</option>';
                    } // end foreach
                    echo '</select></li>';
                } // end if
                ?>
                 <?php
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
                if ($is_zip || $is_gzip || $is_bzip2) { ?>
                    <li>
                    <label for="compression" class="desc"><?php echo __('Compression:'); ?></label>
                    <select id="compression" name="compression">
                        <option value="none"><?php echo __('None'); ?></option>
                        <?php if ($is_zip) { ?>
                            <option value="zip" <?php echo ($selected_compression == "zip") ? 'selected="selected"' : ''; ?>><?php echo __('zipped'); ?></option>
                        <?php } if ($is_gzip) { ?>
                            <option value="gzip" <?php echo ($selected_compression == "gzip") ? 'selected="selected"' : ''; ?>><?php echo __('gzipped'); ?></option>
                        <?php } if ($is_bzip2) { ?>
                            <option value="bzip2" <?php echo ($selected_compression == "bzip2") ? 'selected="selected"' : ''; ?>><?php echo __('bzipped'); ?></option>
                        <?php } ?>
                    </select>
                    </li>
                <?php } else { ?>
                    <input type="hidden" name="compression" value="<?php echo $selected_compression; ?>" />
                <?php } ?>
             </ul>
        </li>
        <li><input type="radio" id="radio_view_as_text" name="output_format" value="astext" <?php echo (isset($_GET['repopulate']) || $GLOBALS['cfg']['Export']['asfile'] == false) ? 'checked="checked"' : '' ?>/><label for="radio_view_as_text"><?php echo __('View output as text'); ?></label></li>
    </ul>
 </div>

<div class="exportoptions" id="format">
    <h3><?php echo __('Format:'); ?></h3>
    <?php echo PMA_pluginGetChoice('Export', 'what', $export_list, 'format'); ?>
</div>

<div class="exportoptions" id="format_specific_opts">
    <h3><?php echo __('Format-specific options:'); ?></h3>
    <p class="no_js_msg" id="scroll_to_options_msg"><?php echo __('Scroll down to fill in the options for the selected format and ignore the options for other formats.'); ?></p>
    <?php echo PMA_pluginGetOptions('Export', $export_list); ?>
</div>

<?php if (function_exists('PMA_set_enc_form')) { ?>
<!-- Encoding setting form appended by Y.Kawada -->
<!-- Japanese encoding setting -->
    <div class="exportoptions" id="kanji_encoding">
        <h3><?php echo __('Encoding Conversion:'); ?></h3>
        <?php echo PMA_set_enc_form('            '); ?>
    </div>
<?php } ?>

<div class="exportoptions" id="submit">
<?php
echo $common_functions->getExternalBug(
    __('SQL compatibility mode'), 'mysql', '50027', '14515'
);
?>
    <input type="submit" value="<?php echo __('Go'); ?>" id="buttonGo" />
</div>
</form>
