<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
$sub_part  = '_export';
require('./db_details_common.php3');
$url_query .= '&amp;goto=db_details_export.php3';
require('./db_details_db_info.php3');


/**
 * Displays the form
 */
?>
<!-- Dump of a database -->
<form method="post" action="tbl_dump.php3" name="db_dump">
    <?php echo $strViewDumpDB; ?><br />
    <table>
    <tr>
<?php
$colspan    = '';
if ($num_tables > 1) {
    $colspan = ' colspan="2"';
    ?>
        <td>
            <select name="table_select[]" size="6" multiple="multiple">
    <?php
    $i = 0;
    echo "\n";
    $is_selected = (!empty($selectall) ? ' selected="selected"' : '');
    while ($i < $num_tables) {
        $table   = htmlspecialchars((PMA_MYSQL_INT_VERSION >= 32303) ? $tables[$i]['Name'] : $tables[$i]);
        echo '                <option value="' . $table . '"' . $is_selected . '>' . $table . '</option>' . "\n";
        $i++;
    } // end while
    ?>
            </select>
        </td>
    <?php
} // end if

echo "\n";
?>
        <td valign="middle">
            <input type="radio" name="what" value="structure" id="radio_dump_structure" checked="checked" />
            <label for="radio_dump_structure"><?php echo $strStrucOnly; ?></label><br />
            <input type="radio" name="what" id="radio_dump_data" value="data" />
            <label for="radio_dump_data"><?php echo $strStrucData; ?></label><br />
            <input type="radio" name="what" id="radio_dump_dataonly" value="dataonly" />
            <label for="radio_dump_dataonly"><?php echo $strDataOnly; ?></label><br />
            <input type="radio" name="what" id="radio_dump_xml" value="xml" />
            <label for="radio_dump_xml"><?php echo $strExportToXML; ?></label>
<?php
if ($num_tables > 1) {
    $checkall_url = 'db_details_export.php3'
                  . '?lang=' . $lang
                  . '&amp;convcharset=' . $convcharset
                  . '&amp;server=' . $server
                  . '&amp;db=' . $db
                  . '&amp;goto=db_details_export.php3';
    ?>
            <br />
            <a href="<?php echo $checkall_url; ?>&amp;selectall=1#dumpdb" onclick="setSelectOptions('db_dump', 'table_select[]', true); return false;"><?php echo $strSelectAll; ?></a>
            &nbsp;/&nbsp;
            <a href="<?php echo $checkall_url; ?>#dumpdb" onclick="setSelectOptions('db_dump', 'table_select[]', false); return false;"><?php echo $strUnselectAll; ?></a>
    <?php
}  // end if
echo "\n";
?>
        </td>
    </tr>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="checkbox" name="drop" value="1" id="checkbox_dump_drop" />
            <label for="checkbox_dump_drop"><?php echo $strStrucDrop; ?></label>
        </td>
    </tr>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="checkbox" name="showcolumns" value="yes" id="checkbox_dump_showcolumns" />
            <label for="checkbox_dump_showcolumns"><?php echo $strCompleteInserts; ?></label>
        </td>
    </tr>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="checkbox" name="extended_ins" value="yes" id="checkbox_dump_extended_ins" />
            <label for="checkbox_dump_extended_ins"><?php echo $strExtendedInserts; ?></label>
        </td>
    </tr>
<?php
// Add backquotes checkbox
if (PMA_MYSQL_INT_VERSION >= 32306) {
    ?>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="checkbox" name="use_backquotes" value="1" id="checkbox_dump_use_backquotes" />
            <label for="checkbox_dump_use_backquotes"><?php echo $strUseBackquotes; ?></label>
        </td>
    </tr>
    <?php
} // end backquotes feature
echo "\n";
?>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="checkbox" name="asfile" value="sendit" id="checkbox_dump_asfile" onclick="return checkTransmitDump(this.form, 'transmit')" />
            <label for="checkbox_dump_asfile"><?php echo $strSend; ?></label>
<?php
// zip, gzip and bzip2 encode features
if (PMA_PHP_INT_VERSION >= 40004) {
    $is_zip  = (isset($cfg['ZipDump']) && $cfg['ZipDump'] && @function_exists('gzcompress'));
    $is_gzip = (isset($cfg['GZipDump']) && $cfg['GZipDump'] && @function_exists('gzencode'));
    $is_bzip = (isset($cfg['BZipDump']) && $cfg['BZipDump'] && @function_exists('bzcompress'));
    if ($is_zip || $is_gzip || $is_bzip) {
        echo "\n" . '                (' . "\n";
        if ($is_zip) {
            ?>
            <input type="checkbox" name="zip" value="zip" id="checkbox_dump_zip" onclick="return checkTransmitDump(this.form, 'zip')" />
            <?php
            echo '<label for="checkbox_dump_zip">' . $strZip . '</label>'
                 . (($is_gzip || $is_bzip) ? '&nbsp;' : '') . "\n";
        }
        if ($is_gzip) {
            echo "\n"
            ?>
            <input type="checkbox" name="gzip" value="gzip" id="checkbox_dump_gzip" onclick="return checkTransmitDump(this.form, 'gzip')" />
            <?php
            echo '<label for="checkbox_dump_gzip">' . $strGzip . '</label>'
                 . (($is_bzip) ? '&nbsp;' : '') . "\n";
        }
        if ($is_bzip) {
            echo "\n"
            ?>
            <input type="checkbox" name="bzip" value="bzip" id="checkbox_dump_bzip" onclick="return checkTransmitDump(this.form, 'bzip')" />
            <?php
            echo '<label for="checkbox_dump_bzip">' . $strBzip . '</label>' . "\n";
        }
        echo "\n" . '                )';
    }
} // end *zip feature
echo "\n";

// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    echo '            <br />' . "\n"
         . PMA_set_enc_form('            ');
}
?>
        </td>
    </tr>
    <tr>
        <td<?php echo $colspan; ?>>
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
    </table>
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang;?>" />
    <input type="hidden" name="db" value="<?php echo $db;?>" />
</form>

<a href="./Documentation.html#faqexport" target="documentation"><?php echo $strDocu; ?></a>


<?php
/**
 * Displays the footer
 */
require('./footer.inc.php3');
?>
