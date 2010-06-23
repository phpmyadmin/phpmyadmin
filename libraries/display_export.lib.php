<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/Table.class.php';

// Get relations & co. status
require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();


require_once './libraries/file_listing.php';
require_once './libraries/plugin_interface.lib.php';

function PMA_exportCheckboxCheck($str) {
    if (isset($GLOBALS['cfg']['Export'][$str]) && $GLOBALS['cfg']['Export'][$str]) {
        echo ' checked="checked"';
    }
}

function PMA_exportIsActive($what, $val) {
    if (isset($GLOBALS['cfg']['Export'][$what]) &&  $GLOBALS['cfg']['Export'][$what] == $val) {
        echo ' checked="checked"';
    }
}

/* Scan for plugins */
$export_list = PMA_getPlugins('./libraries/export/', array('export_type' => $export_type, 'single_table' => isset($single_table)));

/* Fail if we didn't find any plugin */
if (empty($export_list)) {
    PMA_Message::error( __('Could not load export plugins, please check your installation!'))->display();
    require './libraries/footer.inc.php';
}
?>

<form method="post" action="export.php" name="dump">

<?php
if ($export_type == 'server') {
    echo PMA_generate_common_hidden_inputs('', '', 1);
} elseif ($export_type == 'database') {
    echo PMA_generate_common_hidden_inputs($db, '', 1);
} else {
    echo PMA_generate_common_hidden_inputs($db, $table, 1);
}

// just to keep this value for possible next display of this form after saving on server
if (isset($single_table)) {
    echo '<input type="hidden" name="single_table" value="TRUE" />' . "\n";
}

echo '<input type="hidden" name="export_type" value="' . $export_type . '" />' . "\n";

if (! empty($sql_query)) {
    echo '<input type="hidden" name="sql_query" value="' . htmlspecialchars($sql_query) . '" />' . "\n";
}
?>

<div class="exportoptions" id="quick_or_custom">
    <h3><?php echo __('Export Method:'); ?></h3>
    <ul>
        <li>
            <?php echo '<input type="radio" name="quick_or_custom" value="quick" id="radio_quick_export" checked="checked" />';
                echo '<label for ="radio_quick_export">' . __('Quick - display only the minimal options to configure') . '</label>'; ?>
        </li>
        <li>
            <?php echo '<input type="radio" name="quick_or_custom" value="custom" id="radio_custom_export" />';
            echo '<label for="radio_custom_export">' . __('Custom - display all possible options to configure') . '</label>';?>
        </li>
    </ul>
</div>

<div class="exportoptions" id="databases_and_tables" style="display: none;">
    <?php
        if($export_type == 'server') {
            echo '<h3>' . __('Database(s):') . '</h3>';
        } else if($export_type == 'database') {
            echo '<h3>' . __('Table(s):') . '</h3>';
        }
        if (! empty($multi_values)) {
            echo $multi_values;
        }
    ?>
</div>

<?php if (strlen($table) && ! isset($num_tables) && ! PMA_Table::isMerge($db, $table)) { ?>
    <div class="exportoptions" id="rows" style="display: none;">
        <h3><?php echo __('Rows:'); ?></h3>
        <ul>
            <li>
                <?php echo '<input type="radio" name="allrows" value="0" id="radio_allrows_0" checked="checked" />';
                    echo '<label for ="radio_allrows_0">' . __('Dump some row(s)') . '</label>'; ?>
                <ul>
                    <li> <?php echo __('Number of rows:') . ' <input type="text" name="limit_to" size="5" value="'
                . (isset($unlim_num_rows) ? $unlim_num_rows : PMA_Table::countRecords($db, $table))
                . '" onfocus="this.select()" />' ?></li>
                    <li><?php echo __('Row to begin at:') . ' <input type="text" name="limit_from" value="0" size="5"'
                .' onfocus="this.select()" />'; ?></li>
                </ul>
            </li>
            <li>
                <?php echo '<input type="radio" name="allrows" value="1" id="radio_allrows_1" />';
                echo ' <label for="radio_allrows_1">' . __('Dump all rows') . '</label>';?>
            </li>
        </ul>
     </div>
<?php } ?>


<?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
    <div class="exportoptions" id="output_quick_export">
        <h3><?php echo __('Output:'); ?></h3>
        <ul>
            <li>
                <input type="checkbox" name="quick_export_onserver" value="saveit"
                    id="checkbox_quick_dump_onserver"
                    <?php PMA_exportCheckboxCheck('onserver'); ?> />
                <label for="checkbox_quick_dump_onserver">
                    <?php echo sprintf(__('Save on server in the directory <b>%s</b>'), htmlspecialchars(PMA_userDir($cfg['SaveDir']))); ?>
                </label>
            </li>
            <li>
                <input type="checkbox" name="quick_export_onserverover" value="saveitover"
                id="checkbox_quick_dump_onserverover"
                <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
                <label for="checkbox_quick_dump_onserverover"><?php echo __('Overwrite existing file(s)'); ?></label>
            </li>
        </ul>
    </div>
<?php } ?>

<div class="exportoptions" id="output" style="display: none;">
    <h3><?php echo __('Output:'); ?></h3>
    <ul id="ul_output">
        <li>
            <input type="radio" name="output_format" value="sendit" id="radio_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> />
            <label for="radio_dump_asfile"><?php echo __('Save output to a file'); ?></label>
            <ul id="ul_save_asfile">
                <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
                <li>
                    <input type="checkbox" name="onserver" value="saveit"
                        id="checkbox_dump_onserver"
                        <?php PMA_exportCheckboxCheck('onserver'); ?> />
                    <label for="checkbox_dump_onserver">
                        <?php echo sprintf(__('Save on server in the directory <b>%s</b>'), htmlspecialchars(PMA_userDir($cfg['SaveDir']))); ?>
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
                    $trans->addMessage('__SERVER__/');
                    $trans->addString(__('server name'));
                    if ($export_type == 'database' || $export_type == 'table') {
                        $trans->addMessage('__DB__/');
                        $trans->addString(__('database name'));
                        if ($export_type == 'table') {
                            $trans->addMessage('__TABLE__/');
                            $trans->addString(__('table name'));
                        }
                    }

                    $message = new PMA_Message(__('This value is interpreted using %1$sstrftime%2$s, so you can use time formatting strings. Additionally the following transformations will happen: %3$s. Other text will be kept as is.'));
                    $message->addParam('<a href="http://php.net/strftime" target="documentation" title="'
                        . __('Documentation') . '">', false);
                    $message->addParam('</a>', false);
                    $message->addParam($trans);

                    echo PMA_showHint($message);
                    ?>
                    </label>
                    <input type="text" name="filename_template" id="filename_template"
                    <?php
                        echo ' value="';
                        if ($export_type == 'database') {
                            if (isset($_COOKIE) && !empty($_COOKIE['pma_db_filename_template'])) {
                                echo htmlspecialchars($_COOKIE['pma_db_filename_template']);
                            } else {
                                echo $GLOBALS['cfg']['Export']['file_template_database'];
                            }
                        } elseif ($export_type == 'table') {
                            if (isset($_COOKIE) && !empty($_COOKIE['pma_table_filename_template'])) {
                                echo htmlspecialchars($_COOKIE['pma_table_filename_template']);
                            } else {
                                echo $GLOBALS['cfg']['Export']['file_template_table'];
                            }
                        } else {
                            if (isset($_COOKIE) && !empty($_COOKIE['pma_server_filename_template'])) {
                                echo htmlspecialchars($_COOKIE['pma_server_filename_template']);
                            } else {
                                echo $GLOBALS['cfg']['Export']['file_template_server'];
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
                if ($cfg['AllowAnywhereRecoding']) {
                    echo '        <li><label for="select_charset_of_file" class="desc">'
                        . __('Character set of the file:') . '</label>' . "\n";
                    reset($cfg['AvailableCharsets']);
                    echo '<select id="select_charset_of_file" name="charset_of_file" size="1">';
                    foreach ($cfg['AvailableCharsets'] as $temp_charset) {
                        echo '<option value="' . $temp_charset . '"';
                        if ((empty($cfg['Export']['charset']) && $temp_charset == $charset)
                          || $temp_charset == $cfg['Export']['charset']) {
                            echo ' selected="selected"';
                        }
                        echo '>' . $temp_charset . '</option>';
                    } // end foreach
                    echo '</select></li>';
                } // end if
                ?>
                 <?php
                // zip, gzip and bzip2 encode features
                $is_zip  = ($cfg['ZipDump']  && @function_exists('gzcompress'));
                $is_gzip = ($cfg['GZipDump'] && @function_exists('gzencode'));
                $is_bzip = ($cfg['BZipDump'] && @function_exists('bzcompress'));
                if ($is_zip || $is_gzip || $is_bzip) { ?>
                    <li>
                    <label for="compression" class="desc"><?php echo __('Compression:'); ?></label>
                    <select id="compression" name="compression">
                        <option value="none"><?php echo __('None'); ?></option>
                        <?php if ($is_zip) { ?>
                            <option value="zip"><?php echo __('zipped'); ?></option>
                        <?php } if ($is_gzip) { ?>
                            <option value="gzip"><?php echo __('gzipped'); ?></option>
                        <?php } if ($is_bzip) { ?>
                            <option value="bzip"><?php echo __('bzipped'); ?></option>
                        <?php } ?>
                    </select>
                    </li>
                <?php } else { ?>
                    <input type="hidden" name="compression" value="none" />
                <?php } ?>
             </ul>
        </li>
        <li><input type="radio" id="radio_view_as_text" name="output_format" value="astext" /><label for="radio_view_as_text">View output as text</label></li>
    </ul>
 </div>

<div class="exportoptions" id="format">
    <h3><?php echo __('Format:'); ?></h3>
    <?php echo PMA_pluginGetChoice('Export', 'what', $export_list, 'format'); ?>
</div>

<div class="exportoptions" id="format_specific_opts" style="display: none;">
    <h3><?php echo __('Format-Specific Options:'); ?></h3>
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
<?php PMA_externalBug(__('SQL compatibility mode'), 'mysql', '50027', '14515'); ?>
    <input type="submit" value="<?php echo __('Go'); ?>" id="buttonGo" />
</div>
</form>
