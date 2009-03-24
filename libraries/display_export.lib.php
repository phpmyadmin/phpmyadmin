<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
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
    $GLOBALS['show_error_header'] = TRUE;
    PMA_showMessage($strCanNotLoadExportPlugins);
    unset($GLOBALS['show_error_header']);
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
<legend><?php echo $strExport; ?></legend>

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

<script type="text/javascript">
//<![CDATA[
    init_options();
//]]>
</script>

<?php if (strlen($table) && ! isset($num_tables)) { ?>
    <div class="formelementrow">
        <?php
        echo sprintf($strDumpXRows,
            '<input type="text" name="limit_to" size="5" value="'
            . (isset($unlim_num_rows) ? $unlim_num_rows : PMA_Table::countRecords($db, $table, TRUE))
            . '" onfocus="this.select()" />',
            '<input type="text" name="limit_from" value="0" size="5"'
            .' onfocus="this.select()" /> ');
        ?>
    </div>
<?php } ?>
</fieldset>

<fieldset>
    <legend>
        <input type="checkbox" name="asfile" value="sendit"
            id="checkbox_dump_asfile" <?php PMA_exportCheckboxCheck('asfile'); ?> />
        <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
    </legend>

    <?php if (isset($cfg['SaveDir']) && !empty($cfg['SaveDir'])) { ?>
    <input type="checkbox" name="onserver" value="saveit"
        id="checkbox_dump_onserver"
        onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver'); ?> />
    <label for="checkbox_dump_onserver">
        <?php echo sprintf($strSaveOnServer, htmlspecialchars(PMA_userDir($cfg['SaveDir']))); ?>
    </label>,<br />
    <input type="checkbox" name="onserverover" value="saveitover"
        id="checkbox_dump_onserverover"
        onclick="document.getElementById('checkbox_dump_onserver').checked = true;
            document.getElementById('checkbox_dump_asfile').checked = true;"
        <?php PMA_exportCheckboxCheck('onserver_overwrite'); ?> />
    <label for="checkbox_dump_onserverover">
        <?php echo $strOverwriteExisting; ?></label>
    <br />
    <?php } ?>

    <label for="filename_template">
        <?php echo $strFileNameTemplate; ?>
        <sup>(1)</sup></label>:
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
        echo '" />';
    ?>

    (
    <input type="checkbox" name="remember_template"
        id="checkbox_remember_template"
        <?php PMA_exportCheckboxCheck('remember_file_template'); ?> />
    <label for="checkbox_remember_template">
        <?php echo $strFileNameTemplateRemember; ?></label>
    )

    <div class="formelementrow">
    <?php
    // charset of file
    if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
        echo '        <label for="select_charset_of_file">'
            . $strCharsetOfFile . '</label>' . "\n";

        $temp_charset = reset($cfg['AvailableCharsets']);
        echo '        <select id="select_charset_of_file" name="charset_of_file" size="1">' . "\n";
        foreach ($cfg['AvailableCharsets'] as $key => $temp_charset) {
            echo '            <option value="' . $temp_charset . '"';
            if ((empty($cfg['Export']['charset']) && $temp_charset == $charset)
              || $temp_charset == $cfg['Export']['charset']) {
                echo ' selected="selected"';
            }
            echo '>' . $temp_charset . '</option>' . "\n";
        } // end foreach
        echo '        </select>';
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
        <?php echo $strCompression; ?>:
        <input type="radio" name="compression" value="none"
            id="radio_compression_none"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'none'); ?> />
        <label for="radio_compression_none"><?php echo $strNone; ?></label>
    <?php
    if ($is_zip) { ?>
        <input type="radio" name="compression" value="zip"
            id="radio_compression_zip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'zip'); ?> />
        <label for="radio_compression_zip"><?php echo $strZip; ?></label>
    <?php } if ($is_gzip) { ?>
        <input type="radio" name="compression" value="gzip"
            id="radio_compression_gzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'gzip'); ?> />
        <label for="radio_compression_gzip"><?php echo $strGzip; ?></label>
    <?php } if ($is_bzip) { ?>
        <input type="radio" name="compression" value="bzip"
            id="radio_compression_bzip"
            onclick="document.getElementById('checkbox_dump_asfile').checked = true;"
            <?php PMA_exportIsActive('compression', 'bzip2'); ?> />
        <label for="radio_compression_bzip"><?php echo $strBzip; ?></label>
    <?php } ?>
    </div>
<?php } else { ?>
    <input type="hidden" name="compression" value="none" />
<?php } ?>
</fieldset>

<?php if (function_exists('PMA_set_enc_form')) { ?>
<!-- Encoding setting form appended by Y.Kawada -->
<!-- Japanese encoding setting -->
<fieldset>
<?php echo PMA_set_enc_form('            '); ?>
</fieldset>
<?php } ?>

<fieldset class="tblFooters">
<?php PMA_externalBug($GLOBALS['strSQLCompatibility'], 'mysql', '50027', '14515'); ?>
    <input type="submit" value="<?php echo $strGo; ?>" id="buttonGo" />
</fieldset>
</form>

<div class="notice">
    <sup id="FileNameTemplateHelp">(1)</sup>
    <?php
    $trans = '__SERVER__/' . $strFileNameTemplateDescriptionServer;
    if ($export_type == 'database' || $export_type == 'table') {
        $trans .= ', __DB__/' . $strFileNameTemplateDescriptionDatabase;
    }
    if ($export_type == 'table') {
        $trans .= ', __TABLE__/' . $strFileNameTemplateDescriptionTable;
    }
    echo sprintf($strFileNameTemplateDescription,
        '<a href="http://www.php.net/strftime" target="documentation" title="'
        . $strDocu . '">', '</a>', $trans); ?>
</div>
