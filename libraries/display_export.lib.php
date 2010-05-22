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
echo PMA_pluginGetJavascript($export_list);
?>
<fieldset id="fieldsetexport">
<legend><?php echo $export_page_title; ?></legend>

<?php
/*
 * this table is needed to fix rendering in Opera <= 9 and Safari <= 2
 * normaly just the two fieldset would have float: left
 */
?>
<table><tr><td>

<div id="div_container_exportoptions">
<fieldset id="exportoptions">
<legend><?php echo __('Export'); ?></legend>

    <?php if (! empty($multi_values)) { ?>
    <div class="formelementrow">
        <?php echo $multi_values; ?>
    </div>
    <?php } ?>
<?php echo PMA_pluginGetChoice('Export', 'what', $export_list, 'format'); ?>
</fieldset>
</div>

</td><td>

<div id="div_container_sub_exportoptions">
<?php echo PMA_pluginGetOptions('Export', $export_list); ?>
</div>
</td></tr></table>


<?php if (strlen($table) && ! isset($num_tables) && ! PMA_Table::isMerge($db, $table)) { ?>
    <div class="formelementrow">
        <?php
        echo '<input type="radio" name="allrows" value="0" id="radio_allrows_0" checked="checked" />';

        echo sprintf(__('Dump %s row(s) starting at record # %s'),
            '<input type="text" name="limit_to" size="5" value="'
            . (isset($unlim_num_rows) ? $unlim_num_rows : PMA_Table::countRecords($db, $table))
            . '" onfocus="this.select()" />',
            '<input type="text" name="limit_from" value="0" size="5"'
            .' onfocus="this.select()" /> ');

        echo '<input type="radio" name="allrows" value="1" id="radio_allrows_1" />';
        echo '<label for="radio_allrows_1">' . __('Dump all rows') . '</label>';
        ?>
    </div>
<?php } ?>
</fieldset>

<fieldset>
    <legend>
        <input type="checkbox" name="asfile" value="sendit"
            id="checkbox_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> />
        <label for="checkbox_dump_asfile"><?php echo __('Save as file'); ?></label>
    </legend>

    <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
    <input type="checkbox" name="onserver" value="saveit"
        id="checkbox_dump_onserver"
        onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver'); ?> />
    <label for="checkbox_dump_onserver">
        <?php echo sprintf(__('Save on server in %s directory'), htmlspecialchars(PMA_userDir($cfg['SaveDir']))); ?>
    </label>,<br />
    <input type="checkbox" name="onserverover" value="saveitover"
        id="checkbox_dump_onserverover"
        onclick="document.getElementById('checkbox_dump_onserver').checked = true;
            document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
    <label for="checkbox_dump_onserverover">
        <?php echo __('Overwrite existing file(s)'); ?></label>
    <br />
    <?php } ?>

    <label for="filename_template">
        <?php
        echo __('File name template');

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
        </label>:
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

    (
    <input type="checkbox" name="remember_template"
        id="checkbox_remember_template"
        <?php PMA_exportCheckboxCheck('remember_file_template'); ?> />
    <label for="checkbox_remember_template">
        <?php echo __('remember template'); ?></label>
    )

    <div class="formelementrow">
    <?php
    // charset of file
    if ($cfg['AllowAnywhereRecoding']) {
        echo '        <label for="select_charset_of_file">'
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
        echo '</select>';
    } // end if
    ?>
    </div>

<?php
// zip, gzip and bzip2 encode features
$is_zip  = ($cfg['ZipDump']  && @function_exists('gzcompress'));
$is_gzip = ($cfg['GZipDump'] && @function_exists('gzencode'));
$is_bzip = ($cfg['BZipDump'] && @function_exists('bzcompress'));

if ($is_zip || $is_gzip || $is_bzip) { ?>
    <div class="formelementrow">
        <?php echo __('Compression'); ?>:
        <input type="radio" name="compression" value="none"
            id="radio_compression_none"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'none'); ?> />
        <label for="radio_compression_none"><?php echo __('None'); ?></label>
    <?php
    if ($is_zip) { ?>
        <input type="radio" name="compression" value="zip"
            id="radio_compression_zip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'zip'); ?> />
        <label for="radio_compression_zip"><?php echo __('zipped'); ?></label>
    <?php } if ($is_gzip) { ?>
        <input type="radio" name="compression" value="gzip"
            id="radio_compression_gzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'gzip'); ?> />
        <label for="radio_compression_gzip"><?php echo __('gzipped'); ?></label>
    <?php } if ($is_bzip) { ?>
        <input type="radio" name="compression" value="bzip"
            id="radio_compression_bzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'bzip2'); ?> />
        <label for="radio_compression_bzip"><?php echo __('bzipped'); ?></label>
    <?php } ?>
    </div>
<?php } else { ?>
    <input type="hidden" name="compression" value="none" />
<?php } ?>
</fieldset>

<?php if (function_exists('PMA_set_enc_form')) { ?>
<!-- Encoding setting form appended by Y.Kawada -->
<!-- Japanese encoding setting -->
<?php echo PMA_set_enc_form('            '); ?>
<?php } ?>

<fieldset class="tblFooters">
<?php PMA_externalBug(__('SQL compatibility mode'), 'mysql', '50027', '14515'); ?>
    <input type="submit" value="<?php echo __('Go'); ?>" id="buttonGo" />
</fieldset>
</form>
