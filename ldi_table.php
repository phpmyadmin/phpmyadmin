<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * This file defines the forms used to insert a textfile into a table
 */

require_once('./libraries/grab_globals.lib.php');

// Check parameters
require_once('./libraries/common.lib.php');
PMA_checkParameters(array('db', 'table'));


/**
 * Gets some core libraries and displays links
 */
require('./tbl_properties_common.php');
$err_url   = 'ldi_table.php' . $err_url;
$url_query .= '&amp;goto=ldi_table.php&amp;back=ldi_table.php';
require('./tbl_properties_table_info.php');

/**
 * Displays the form
 */
?>
<form action="ldi_check.php" method="post" enctype="multipart/form-data">
    <table cellpadding="5" border="2">
    <tr>
        <td><?php echo $strLocationTextfile; ?></td>
        <td colspan="2"><input type="file" name="textfile" />
        <?php
if (!empty($cfg['UploadDir'])) {
    if (substr($cfg['UploadDir'], -1) != '/') {
        $cfg['UploadDir'] .= '/';
    }
    if ($handle = @opendir($cfg['UploadDir'])) {
        $is_first = 0;
        while ($file = @readdir($handle)) {
            if (is_file($cfg['UploadDir'] . $file) && substr($file, -4) == '.csv') {
                if ($is_first == 0) {
                    $is_upload_dir = true;
                    echo "<br />\n";
                    echo '    <i>' . $strOr . '</i> ' . $strWebServerUploadDirectory . '&nbsp;: ' . "\n";
                    echo '    <div style="margin-bottom: 5px">' . "\n";
                    echo '        <select size="1" name="local_textfile">' . "\n";
                    echo '            <option value="" selected="selected"></option>' . "\n";
                } // end if (is_first)
                echo '            <option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . '</option>' . "\n";
                $is_first++;
            } // end if (is_file)
        } // end while
        if ($is_first > 0) {
            echo '        </select>' . "\n"
                    . '    </div>' . "\n\n";
        } // end if (isfirst > 0)
        @closedir($handle);
    } else {
        echo '    <div style="margin-bottom: 5px">' . "\n";
        echo '        <font color="red">' . $strError . '</font><br />' . "\n";
        echo '        ' . $strWebServerUploadDirectoryError . "\n";
        echo '    </div>' . "\n";
    }
} // end if (web-server upload directory)
echo "\n";
        ?>
        </td>
    </tr>

<?php
if ($cfg['AllowAnywhereRecoding'] && $allow_recoding) {
    $temp_charset = reset($cfg['AvailableCharsets']);
    echo '    <tr>' . "\n"
         . '        <td>' . $strCharsetOfFile . '</td>' . "\n"
         . '        <td colspan="2">' . "\n"
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
    echo '            </select>' . "\n";
    echo '        </td>' . "\n";
    echo '    </tr>';
} // end if
echo "\n";
?>
    <tr>
        <td><?php echo $strReplaceTable; ?></td>
        <td><input type="checkbox" name="replace" value="REPLACE" id="checkbox_replace" /><?php echo $strReplace; ?></td>
        <td><label for="checkbox_replace"><?php echo $strTheContents; ?></label></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsTerminatedBy; ?></td>
        <td><input type="text" name="field_terminater" size="2" maxlength="2" value=";" /></td>
        <td><?php echo $strTheTerminator; ?></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsEnclosedBy; ?></td>
        <td>
            <input type="text" name="enclosed" size="1" maxlength="1" value="&quot;" />
            <input type="checkbox" name="enclose_option" value="OPTIONALLY" id="checkbox_enclose_option" />
            <label for="checkbox_enclose_option"><?php echo $strOptionally; ?></label>
        </td>
        <td><?php echo $strOftenQuotation; ?></td>
    </tr>
    <tr>
        <td><?php echo $strFieldsEscapedBy; ?></td>
        <td><input type="text" name="escaped" size="2" maxlength="2" value="\" /></td>
        <td><?php echo $strOptionalControls; ?></td>
    </tr>
    <tr>
        <td><?php echo $strLinesTerminatedBy; ?></td>
        <td><input type="text" name="line_terminator" size="8" maxlength="8" value="<?php echo ((PMA_whichCrlf() == "\n") ? '\n' : '\r\n'); ?>" /></td>
        <td><?php echo $strCarriage; ?><br /><?php echo $strLineFeed; ?></td>
    </tr>
    <tr>
        <td><?php echo $strColumnNames; ?></td>
        <td><input type="text" name="column_name" /></td>
        <td><?php echo $strIfYouWish; ?></td>
    </tr>
<?php
// 2002/2/22 appended by Y.Kawada: Kanji encoding convert controls
if (function_exists('PMA_set_enc_form')) {
    echo '    <tr>' . "\n"
         . '        <td>' . $strKanjiEncodConvert . '</td>' . "\n"
         . '        <td colspan=2>' . "\n"
         . PMA_set_enc_form('            ')
         . '        </td>' . "\n"
         . '    </tr>' . "\n";
} // end if


// Check if we should check the LOCAL radio button by default
$local_option_selected = FALSE;

if (PMA_MYSQL_INT_VERSION < 32349) {
        $local_option_selected = TRUE;
}

if (PMA_MYSQL_INT_VERSION > 40003) {
    $tmp_query  = "SHOW VARIABLES LIKE 'local\\_infile'";
    $result = PMA_mysql_query($tmp_query);
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        $tmp = PMA_mysql_fetch_row($result);
        if ($tmp[1] == 'ON') {
            $local_option_selected = TRUE;
        }
    }
    mysql_free_result($result);
}

?>
    <tr>
        <td><?php echo $strLoadMethod; ?>
        </td>
        <td>
            <input type="radio" id="radio_local_option_0" name="local_option" value="0" <?php echo (!$local_option_selected ? ' checked="checked" ' : ''); ?>/><label for="radio_local_option_0">...DATA</label><br />
            <input type="radio" id="radio_local_option_1" name="local_option" value="1" <?php echo ($local_option_selected ? ' checked="checked" ' : ''); ?>/><label for="radio_local_option_1">...DATA LOCAL</label>
        </td>
        <td><?php echo $strLoadExplanation; ?>
        </td>
    </tr>
    <tr>
        <td colspan="3" align="center"><?php print PMA_showMySQLDocu('Reference', 'LOAD_DATA'); ?></td>
    </tr>
    <tr>
        <td colspan="3" align="center">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <input type="hidden" name="zero_rows" value="<?php echo $strTheContent; ?>" />
            <input type="hidden" name="goto" value="tbl_properties.php" />
            <input type="hidden" name="back" value="ldi_table.php" />
            <input type="hidden" name="into_table" value="<?php echo htmlspecialchars($table); ?>" />
            <input type="submit" name="btnLDI" value="<?php echo $strSubmit; ?>" />&nbsp;&nbsp;
            <input type="reset" value="<?php echo $strReset; ?>" />
        </td>
    </tr>
</table>
</form>


<?php
/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
