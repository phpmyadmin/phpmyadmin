<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets tables informations and displays top links
 */
require('./tbl_properties_common.php3');
$url_query .= '&amp;goto=tbl_properties_export.php3&amp;back=tbl_properties_export.php3';
require('./tbl_properties_table_info.php3');
?>

<!-- Dump of a table -->
<p align="center">
    <?php echo $strViewDump . "\n"; ?>
</p>

<?php
if (isset($sql_query)) {
    $sql_query = stripslashes($sql_query);
    PMA_showMessage($GLOBALS['strSQLQuery']);
}
?>
<form method="post" action="tbl_dump.php3" name="tbl_dump">
    <?php 
echo '    ' . PMA_generate_common_hidden_inputs($db, $table);
if (isset($sql_query)) {
    echo '    <input type="hidden" name="sql_query" value="' . urlencode($sql_query) . '" />';
}
    ?>
    <table cellpadding="5" border="2" align="center">
    <tr>

        <!-- Formats to export to -->
        <td nowrap="nowrap">
            <!-- SQL -->
            <input type="radio" name="what" value="structure" id="radio_dump_structure" checked="checked" />
            <label for="radio_dump_structure"><?php echo $strStrucOnly; ?></label>&nbsp;&nbsp;<br />
            <input type="radio" name="what" value="data" id="radio_dump_data" />
            <label for="radio_dump_data"><?php echo $strStrucData; ?></label>&nbsp;&nbsp;<br />
            <input type="radio" name="what" value="dataonly" id="radio_dump_dataonly" />
            <label for="radio_dump_dataonly"><?php echo $strDataOnly; ?></label>&nbsp;&nbsp;<br />
            <br />

            <!-- LaTeX table -->
            <input type="radio" name="what" value="latex" id="radio_dump_latex" />
            <label for="radio_dump_latex"><?php echo $strLaTeX; ?></label> &nbsp; &nbsp;
            <!-- for now we have only one environment supported -->
            <input type="hidden" name="environment" value="longtable" />
            <?php /*
            <select name="environment">
                <option value="longtable" selected><?php echo $strLaTeXMultipageTable; ?></option>
                <option value="sideways"><?php echo $strLaTeXSidewaysTable; ?></option>
                <option value="table"><?php echo $strLaTeXStandardTable; ?></option>
            </select>
            */ ?>
            <br /><br />

            <!-- Excel CSV -->
            <input type="radio" name="what" value="excel" id="radio_dump_excel" />
            <label for="radio_dump_excel"><?php echo $strStrucExcelCSV; ?></label>&nbsp;&nbsp;<br />
            <br />
            <!-- General CSV -->
            <input type="radio" name="what" value="csv" id="radio_dump_csv" />
            <label for="radio_dump_csv"><?php echo $strStrucCSV;?></label>&nbsp;:<br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsTerminatedBy; ?>&nbsp;
            <input type="text" name="separator" size="2" value=";" class="textfield" />&nbsp;&nbsp;<br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsEnclosedBy; ?>&nbsp;
            <input type="text" name="enclosed" size="1" value="&quot;" class="textfield" />&nbsp;&nbsp;<br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strFieldsEscapedBy; ?>&nbsp;
            <input type="text" name="escaped" size="2" value="\" class="textfield" />&nbsp;&nbsp;<br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strLinesTerminatedBy; ?>&nbsp;
            <input type="text" name="add_character" size="2" value="<?php echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); ?>" class="textfield" />&nbsp;&nbsp;<br />
            <br />
            <!-- XML -->
            <input type="radio" name="what" value="xml" id="radio_dump_xml" />
            <label for="radio_dump_xml"><?php echo $strExportToXML; ?></label>&nbsp;&nbsp;
        </td>

        <!-- Options -->
        <td valign="middle">
            <!-- For structure -->
            <?php echo $strStructure; ?><br />
            &nbsp;&nbsp;
            <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop" />
            <label for="checkbox_dump_drop"><?php echo $strStrucDrop; ?></label><br />
<?php
// Add backquotes checkbox
if (PMA_MYSQL_INT_VERSION >= 32306) {
    ?>
            &nbsp;&nbsp;
            <input type="checkbox" name="use_backquotes" value="1" id="checkbox_dump_use_backquotes" />
            <label for="checkbox_dump_use_backquotes"><?php echo $strUseBackquotes; ?></label><br />
    <?php
} // end backquotes feature
echo "\n";

// garvin: whether to show column comments
require('./libraries/relation.lib.php3');
$cfgRelation = PMA_getRelationsParam();

if ($cfgRelation['commwork']) {
    ?>
            &nbsp;&nbsp;
            <input type="checkbox" name="use_comments" value="1" id="checkbox_dump_use_comments" />
            <label for="checkbox_dump_use_comments"><?php echo $strDumpComments; ?></label><br />
    <?php
} // end dump comments
echo "\n";

?>
            <br />
            <!-- For data -->
            <?php echo $strData; ?><br />
            &nbsp;&nbsp;
            <input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" />
            <label for="checkbox_dump_showcolumns"><?php echo $strCompleteInserts; ?></label><br />
            &nbsp;&nbsp;
            <input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" />
            <label for="checkbox_dump_extended_ins"><?php echo $strExtendedInserts; ?></label><br />
            &nbsp;&nbsp;
            <?php echo sprintf($strDumpXRows , '<input type="text" name="limit_to" size="5" value="' . (isset($unlim_num_rows)?$unlim_num_rows: PMA_countRecords($db, $table, TRUE)) . '" class="textfield" style="vertical-align: middle" onfocus="this.select()" />' , '<input type="text" name="limit_from" value="0" size="5" class="textfield" style="vertical-align: middle" onfocus="this.select()" />') . "\n"; ?>
            <br /><br />
            <!-- For CSV data -->
            <?php echo $strStrucCSV; ?><br />
            &nbsp;&nbsp;
            <input type="checkbox" name="showcsvnames" value="yes" id="checkbox_dump_showcsvnames" />
            <label for="checkbox_dump_showcsvnames"><?php echo $strPutColNames; ?></label>
        </td>
    </tr>

    <tr>
        <!-- Export to screen or to file -->
        <td colspan="2" align="center">
            <input type="checkbox" name="asfile" value="sendit" id="checkbox_dump_asfile" onclick="return checkTransmitDump(this.form, 'transmit')" />
            <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
<?php
// charset of file
if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
    $temp_charset = reset($cfg['AvailableCharsets']);
    echo "\n" . '            , ' . $strCharsetOfFile . "\n"
         . '            <select name="charset_of_file" size="1">' . "\n"
         . '                <option value="' . $temp_charset . '"';
    if ($temp_charset == $charset) {
        echo ' selected="selected"';
    }
    echo '>' . $temp_charset . '</option>' . "\n";
    while ($temp_charset = next($cfg['AvailableCharsets'])) {
        echo '                <option value="' . $temp_charset . '"';
        if ($temp_charset == $charset) {
            echo ' selected="selected"';
        }
        echo '>' . $temp_charset . '</option>' . "\n";
    } // end while
    echo '            </select>';
} // end if
echo "\n";

// zip, gzip and bzip2 encode features
if (PMA_PHP_INT_VERSION >= 40004) {
    $is_zip  = (isset($cfg['ZipDump']) && $cfg['ZipDump'] && @function_exists('gzcompress'));
    $is_gzip = (isset($cfg['GZipDump']) && $cfg['GZipDump'] && @function_exists('gzencode'));
    $is_bzip = (isset($cfg['BZipDump']) && $cfg['BZipDump'] && @function_exists('bzcompress'));
    if ($is_zip || $is_gzip || $is_bzip) {
        echo "\n" . '            (' . "\n";
        if ($is_zip) {
            ?>
            <input type="checkbox" name="zip" value="zip" id="checkbox_dump_zip" onclick="return checkTransmitDump(this.form, 'zip')" />
            <?php echo '<label for="checkbox_dump_zip">' . $strZip . '</label>' . (($is_gzip || $is_bzip) ? '&nbsp;' : '') . "\n"; ?>
            <?php
        }
        if ($is_gzip) {
            echo "\n"
            ?>
            <input type="checkbox" name="gzip" value="gzip" id="checkbox_dump_gzip" onclick="return checkTransmitDump(this.form, 'gzip')" />
            <?php echo '<label for="checkbox_dump_gzip">' . $strGzip . '</label>' . (($is_bzip) ? '&nbsp;' : '') . "\n"; ?>
            <?php
        }
        if ($is_bzip) {
            echo "\n"
            ?>
            <input type="checkbox" name="bzip" value="bzip" id="checkbox_dump_bzip" onclick="return checkTransmitDump(this.form, 'bzip')" />
            <?php echo '<label for="checkbox_dump_bzip">' . $strBzip . '</label>' . "\n"; ?>
            <?php
        }
        echo "\n" . '            )';
    }
}
echo "\n";
?>
        </td>
    </tr>

<?php
// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    ?>
    <tr>
        <!-- Japanese encoding setting -->
        <td colspan="2" align="center">
    <?php
    echo PMA_set_enc_form('            ');
    ?>
        </td>
    </tr>
    <?php
}
echo "\n";
?>

    <tr>
        <td colspan="2" align="center">
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
    </table>
</form>

<p align="center">
    <a href="./Documentation.html#faqexport" target="documentation"><?php echo $strDocu; ?></a>
</p>


<?php
/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
