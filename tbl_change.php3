<?php
/* $Id$ */


/**
 * Get the variables sent or posted to this script and displays the header
 */
require('./libraries/grab_globals.lib.php3');
include('./header.inc.php3');
// Displays the query submitted and its result
if (!empty($message)) {
    if (isset($goto)) {
        $goto_cpy      = $goto;
        $goto          = 'tbl_properties.php3'
                       . '?lang=' . $lang
                       . '&amp;server=' . $server
                       . '&amp;db=' . urlencode($db)
                       . '&amp;table=' . urlencode($table)
                       . '&amp;$show_query=y'
                       . '&amp;sql_query=' . urlencode($disp_query);
    } else {
        $show_query = 'y';
    }
    if (isset($sql_query)) {
        $sql_query_cpy = $sql_query;
        unset($sql_query);
    }
    if (isset($disp_query)) {
        $sql_query     = (get_magic_quotes_gpc() ? stripslashes($disp_query) : $disp_query);
    }
    PMA_showMessage($message);
    if (isset($goto_cpy)) {
        $goto          = $goto_cpy;
        unset($goto_cpy);
    }
    if (isset($sql_query_cpy)) {
        $sql_query     = $sql_query_cpy;
        unset($sql_query_cpy);
    }
}
if (get_magic_quotes_gpc()) {
    if (!empty($sql_query)) {
        $sql_query   = stripslashes($sql_query);
    }
    if (!empty($primary_key)) {
        $primary_key = stripslashes($primary_key);
    }
} // end if


/**
 * Defines the url to return to in case of error in a sql statement
 */
if (!isset($goto)) {
    $goto    = 'db_details.php3';
}
if ($goto != 'db_details.php3' && $goto != 'tbl_properties.php3') {
    $err_url = $goto;
} else {
    $err_url = $goto
             . '?lang=' . $lang
             . '&amp;server=' . $server
             . '&amp;db=' . urlencode($db)
             . (($goto == 'tbl_properties.php3') ? '&amp;table=' . urlencode($table) : '');
}


/**
 * Get the list of the fields of the current table
 */
mysql_select_db($db);
$table_def = mysql_query('SHOW FIELDS FROM ' . PMA_backquote($table));
if (isset($primary_key)) {
    $local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $primary_key;
    $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $row         = mysql_fetch_array($result);
    // No row returned
    if (!$row) {
        unset($row);
        unset($primary_key);
        $goto_cpy          = $goto;
        $goto              = 'tbl_properties.php3'
                           . '?lang=' . $lang
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;$show_query=y'
                           . '&amp;sql_query=' . urlencode($local_query);
        if (isset($sql_query)) {
            $sql_query_cpy = $sql_query;
            unset($sql_query);
        }
        $sql_query         = $local_query;
        PMA_showMessage($strEmptyResultSet);
        $goto              = $goto_cpy;
        unset($goto_cpy);
        if (isset($sql_query_cpy)) {
            $sql_query    = $sql_query_cpy;
            unset($sql_query_cpy);
        }
    } // end if (no record returned)
}
else
{
    $local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' LIMIT 1';
    $result      = mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    unset($row);
}


/**
 * Displays the form
 */

// Had to put the URI because  when hosted on an https server,
// some browsers send wrongly this form to the http server.
?>

<!-- Change table properties form -->
<form method="post" action="tbl_replace.php3" name="insertForm">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="pos" value="<?php echo isset($pos) ? $pos : 0; ?>" />
    <input type="hidden" name="session_max_rows" value="<?php echo isset($session_max_rows) ? $session_max_rows : ''; ?>" />
    <input type="hidden" name="disp_direction" value="<?php echo isset($disp_direction) ? $disp_direction : ''; ?>" />
    <input type="hidden" name="repeat_cells" value="<?php echo isset($repeat_cells) ? $repeat_cells : ''; ?>" />
    <input type="hidden" name="err_url" value="<?php echo urlencode($err_url); ?>" />
    <input type="hidden" name="sql_query" value="<?php echo isset($sql_query) ? urlencode($sql_query) : ''; ?>" />
<?php
if (isset($primary_key)) {
    ?>
    <input type="hidden" name="primary_key" value="<?php echo urlencode($primary_key); ?>" />
    <?php
}
echo "\n";
?>

    <table border="<?php echo $cfgBorder; ?>">
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strType; ?></th>
        <th><?php echo $strFunction; ?></th>
        <th><?php echo $strNull; ?></th>
        <th><?php echo $strValue; ?></th>
    </tr>

<?php
// Set if we passed the first timestamp field (loic1: in insert mode only -not
// in edit mode-)
$timestamp_seen = (isset($primary_key) ? 1 : 0);
$fields_cnt     = mysql_num_rows($table_def);

for ($i = 0; $i < $fields_cnt; $i++) {
    $row_table_def   = mysql_fetch_array($table_def);
    $field           = $row_table_def['Field'];
    if ($row_table_def['Type'] == 'datetime' && empty($row[$field])) {
        $row[$field] = date('Y-m-d H:i:s', time());
    }
    $len             = @mysql_field_len($result, $i);
    $first_timestamp = 0;

    $bgcolor = ($i % 2) ? $cfgBgcolorOne : $cfgBgcolorTwo;
    ?>
    <tr>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>"><?php echo htmlspecialchars($field); ?></td>
    <?php
    echo "\n";

    // The type column
    $is_binary                  = eregi(' binary', $row_table_def['Type']);
    $is_blob                    = eregi('blob', $row_table_def['Type']);
    $row_table_def['True_Type'] = ereg_replace('\\(.*', '', $row_table_def['Type']);
    switch ($row_table_def['True_Type']) {
        case 'set':
            $type         = 'set';
            $type_nowrap  = '';
            break;
        case 'enum':
            $type         = 'enum';
            $type_nowrap  = '';
            break;
        case 'timestamp':
            if (!$timestamp_seen) {   // can only occur once per table
                $timestamp_seen  = 1;
                $first_timestamp = 1;
            }
            $type         = $row_table_def['Type'];
            break;
        default:
            $type         = $row_table_def['Type'];
            $type_nowrap  = ' nowrap="nowrap"';
            break;
    }
    ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>"<?php echo $type_nowrap; ?>><?php echo $type; ?></td>
    <?php
    echo "\n";

    // Prepares the field value
    if (isset($row)) {
        // loic1: null field value
        if (!isset($row[$field])) {
            $row[$field]   = 'NULL';
            $special_chars = '';
            $data          = $row[$field];
        } else {
            // loic1: special binary "characters"
            if ($is_binary || $is_blob) {
                $row[$field] = str_replace("\x00", '\0', $row[$field]);
                $row[$field] = str_replace("\x08", '\b', $row[$field]);
                $row[$field] = str_replace("\x0a", '\n', $row[$field]);
                $row[$field] = str_replace("\x0d", '\r', $row[$field]);
                $row[$field] = str_replace("\x1a", '\Z', $row[$field]);
            } // end if
            $special_chars   = htmlspecialchars($row[$field]);
            $data            = $row[$field];
        } // end if... else...
        // loic1: if a timestamp field value is not included in an update
        //        statement MySQL auto-update it to the current timestamp
        $backup_field  = ($row_table_def['True_Type'] == 'timestamp')
                       ? ''
                       : '<input type="hidden" name="fields_prev[' . urlencode($field) . ']" value="' . urlencode($row[$field]) . '" />';
    } else {
        // loic1: display default values 
        if (!isset($row_table_def['Default'])) {
            $row_table_def['Default'] = '';
            $data                     = 'NULL';
        } else {
            $data                     = $row_table_def['Default'];
        }
        $special_chars = htmlspecialchars($row_table_def['Default']);
        $backup_field  = '';
    }

    // The function column
    // -------------------
    // Change by Bernard M. Piller <bernard@bmpsystems.com>
    // We don't want binary data to be destroyed
    // Note: from the MySQL manual: "BINARY doesn't affect how the column is
    //       stored or retrieved" so it does not mean that the contents is
    //       binary
    if ((($cfgProtectBinary && $is_blob)
         || ($cfgProtectBinary == 'all' && $is_binary))
        && !empty($data)) {
        echo '        <td align="center" bgcolor="'. $bgcolor . '">' . $strBinary . '</td>' . "\n";
    } else if (strstr($row_table_def['True_Type'], 'enum') || strstr($row_table_def['True_Type'], 'set')) {
        echo '        <td align="center" bgcolor="'. $bgcolor . '">--</td>' . "\n";
    } else {
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <select name="funcs[<?php echo urlencode($field); ?>]">
                <option></option>
        <?php
        echo "\n";
        if (!$first_timestamp) {
            for ($j = 0; $j < count($cfgFunctions); $j++) {
                echo '                ';
                echo '<option>' . $cfgFunctions[$j] . '</option>' . "\n";
            }
        } else {
            // for default function = NOW() on first timestamp field
            // -- swix/18jul01
            for ($j = 0; $j < count($cfgFunctions); $j++) {
                echo '                ';
                if ($cfgFunctions[$j] == 'NOW') {
                    echo '<option selected="selected">' . $cfgFunctions[$j] . '</option>' . "\n";
                } else {
                    echo '<option>' . $cfgFunctions[$j] . '</option>' . "\n";
                }
            } // end for
        }
        ?>
            </select>
        </td>
        <?php
    }
    echo "\n";

    // The null column
    // ---------------
    echo '        <td bgcolor="' . $bgcolor . '">' . "\n";
    if ($row_table_def['Null'] == 'YES') {
        echo '            <input type="checkbox"'
             . ' name="fields_null[' . urlencode($field) . ']"';
        if ($data == 'NULL') {
            echo ' checked="checked"';
        }
        if (strstr($row_table_def['True_Type'], 'enum')) {
            if (strlen($row_table_def['Type']) > 20) {
                echo ' onclick="if (this.checked) {document.forms[\'insertForm\'].elements[\'field_' . md5($field) . '[]\'].selectedIndex = -1}; return true" />' . "\n";
            } else {
                echo ' onclick="if (this.checked) {var elts = document.forms[\'insertForm\'].elements[\'field_' . md5($field) . '[]\']; var elts_cnt = elts.length; for (var i = 0; i < elts_cnt; i++ ) {elts[i].checked = false}}; return true" />' . "\n";
            }
        } else if (strstr($row_table_def['True_Type'], 'set')) {
            echo ' onclick="if (this.checked) {document.forms[\'insertForm\'].elements[\'field_' . md5($field) . '[]\'].selectedIndex = -1}; return true" />' . "\n";
	} else {
            echo ' onclick="if (this.checked) {document.forms[\'insertForm\'].elements[\'fields[' . urlencode($field) . ']\'].value = \'\'}; return true" />' . "\n";
        }
    } else {
        echo '            &nbsp;' . "\n";
    }
    echo '        </td>' . "\n";

    // The value column (depends on type)
    // ----------------
    if (strstr($row_table_def['True_Type'], 'text')) {
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>" wrap="virtual" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}"><?php echo $special_chars; ?></textarea>
        </td>
        <?php
        echo "\n";
        if (strlen($special_chars) > 32000) {
            echo '        <td bgcolor="' . $bgcolor . '">' . $strTextAreaLength . '</td>' . "\n";
        }
    }
    else if (strstr($row_table_def['True_Type'], 'enum')) {
        $enum        = str_replace('enum(', '', $row_table_def['Type']);
        $enum        = ereg_replace('\\)$', '', $enum);
        $enum        = explode('\',\'', substr($enum, 1, -1));
        $enum_cnt    = count($enum);
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$enum$" />
        <?php
        echo "\n" . '            ' . $backup_field;

        // show dropdown or radio depend on length
        if (strlen($row_table_def['Type']) > 20) {
            echo "\n";
            ?>
            <select name="field_<?php echo md5($field); ?>[]" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}">
                <option value=""></option>
            <?php
            echo "\n";

            for ($j = 0; $j < $enum_cnt; $j++) {
                // Removes automatic MySQL escape format
                $enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
                echo '                ';
                echo '<option value="' . urlencode($enum_atom) . '"';
                if ($data == $enum_atom
                    || ($data == '' && (!isset($primary_key) || $row_table_def['Null'] != 'YES')
                        && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])) {
                    echo ' selected="selected"';
                }
                echo '>' . htmlspecialchars($enum_atom) . '</option>' . "\n";
            } // end for
             
            ?>
            </select>
            <?php
        } // end if
        else {
            echo "\n";
            for ($j = 0; $j < $enum_cnt; $j++) {
                // Removes automatic MySQL escape format
                $enum_atom = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $enum[$j]));
                echo '            ';
                echo '<input type="radio" name="field_' . md5($field) . '[]" value="' . urlencode($enum_atom) . '"' . ' onclick="if (typeof(document.forms[\'insertForm\'].elements[\'fields_null[' . urlencode($field) . ']\']) != \'undefined\') {document.forms[\'insertForm\'].elements[\'fields_null[' . urlencode($field) .']\'].checked = false}"'; 
                if ($data == $enum_atom
                    || ($data == '' && (!isset($primary_key) || $row_table_def['Null'] != 'YES')
                        && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])) {
                    echo ' checked="checked"';
                }
                echo ' />' . "\n";
                echo '            ' . htmlspecialchars($enum_atom) . "\n";
            } // end for

        } // end else
        echo "\n";
        ?>
        </td>
        <?php
        echo "\n";
    }
    else if (strstr($row_table_def['Type'], 'set')) {
        $set = str_replace('set(', '', $row_table_def['Type']);
        $set = ereg_replace('\)$', '', $set);
        $set = explode(',', $set);

        if (isset($vset)) {
            unset($vset);
        }
        for ($vals = explode(',', $data); list($t, $k) = each($vals);) {
            $vset[$k] = 1;
        }
        $size = min(4, count($set));
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$set$" />
            <select name="field_<?php echo md5($field); ?>[]" size="<?php echo $size; ?>" multiple="multiple" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}">
        <?php
        echo "\n";
        $countset = count($set);
        for ($j = 0; $j < $countset;$j++) {
            $subset = substr($set[$j], 1, -1);
            // Removes automatic MySQL escape format
            $subset = str_replace('\'\'', '\'', str_replace('\\\\', '\\', $subset));
            echo '                ';
            echo '<option value="'. urlencode($subset) . '"';
            if (isset($vset[$subset]) && $vset[$subset]) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($subset) . '</option>' . "\n";
        } // end for
        ?>
            </select>
        </td>
        <?php
    }
    // Change by Bernard M. Piller <bernard@bmpsystems.com>
    // We don't want binary data destroyed
    else if ($is_binary || $is_blob) {
        if (($cfgProtectBinary && $is_blob)
            || ($cfgProtectBinary == 'all' && $is_binary)) {
            echo "\n";
            ?>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $strBinaryDoNotEdit . "\n"; ?>
        </td>
            <?php
        } else if ($is_blob) {
            echo "\n";
            ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>" wrap="virtual" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}"><?php echo $special_chars; ?></textarea>
        </td>
            <?php
        } else {
            if ($len < 4) {
                $fieldsize = $maxlength = 4;
            } else {
                $fieldsize = (($len > 40) ? 40 : $len);
                $maxlength = $len;
            }
            echo "\n";
            ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}" />
        </td>
            <?php
        } // end if...elseif...else
    } // end else if
    else {
        if ($len < 4) {
            $fieldsize = $maxlength = 4;
        } else {
            $fieldsize = (($len > 40) ? 40 : $len);
            $maxlength = $len;
        }
        echo "\n";
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>" onchange="if (typeof(document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]']) != 'undefined') {document.forms['insertForm'].elements['fields_null[<?php echo urlencode($field); ?>]'].checked = false}" />
        </td>
        <?php
    }
    echo "\n";
    ?>
    </tr>
    <?php
echo "\n";
} // end for
?>
    </table>
    <br />

    <table cellpadding="5">
    <tr>
        <td valign="middle" nowrap="nowrap">
<?php
if (isset($primary_key)) {
    ?>
            <input type="radio" name="submit_type" value="<?php echo $strSave; ?>" checked="checked" /><?php echo $strSave; ?><br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strOr; ?><br />
            <input type="radio" name="submit_type" value="<?php echo $strInsertAsNewRow; ?>" /><?php echo $strInsertAsNewRow. "\n"; ?>
    <?php
} else {
    echo "\n";
    ?>
            <input type="hidden" name="submit_type" value="<?php echo $strInsertAsNewRow; ?>" />
    <?php
    echo '            ' . $strInsertAsNewRow . "\n";
}
echo "\n"
?>
        </td>
        <td valign="middle">
            &nbsp;&nbsp;&nbsp;<b>-- <?php echo $strAnd; ?> --</b>&nbsp;&nbsp;&nbsp;
        </td>
        <td valign="middle" nowrap="nowrap">
            <input type="radio" name="after_insert" value="back" checked="checked" /><?php echo $strAfterInsertBack; ?><br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strOr; ?><br />
            <input type="radio" name="after_insert" value="new_insert" /><?php echo $strAfterInsertNewInsert . "\n"; ?>
        </td>
    </tr>

    <tr>
        <td colspan="3" align="center" valign="middle">
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
    </table>

</form>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
