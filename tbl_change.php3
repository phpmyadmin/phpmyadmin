<?php
/* $Id$ */


/**
 * Get the variables sent or posted to this script and displays the header
 */
require('./libraries/grab_globals.lib.php3');
$js_to_run = 'tbl_change.js';
require('./header.inc.php3');
require('./libraries/relation.lib.php3'); // foreign keys

/**
 * Displays the query submitted and its result
 */
if (!empty($message)) {
    if (isset($goto)) {
        $goto_cpy      = $goto;
        $goto          = 'tbl_properties.php3'
                       . '?lang=' . $lang
                       . '&amp;convcharset=' . $convcharset
                       . '&amp;server=' . $server
                       . '&amp;db=' . urlencode($db)
                       . '&amp;table=' . urlencode($table)
                       . '&amp;$show_query=1'
                       . '&amp;sql_query=' . urlencode($disp_query);
    } else {
        $show_query = '1';
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
             . '&amp;convcharset=' . $convcharset
             . '&amp;server=' . $server
             . '&amp;db=' . urlencode($db)
             . (($goto == 'tbl_properties.php3') ? '&amp;table=' . urlencode($table) : '');
}


/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require('./libraries/db_table_exists.lib.php3');


/**
 * Sets parameters for links and displays top menu
 */
$url_query = 'lang=' . $lang
           . '&amp;convcharset=' . $convcharset
           . '&amp;server=' . $server
           . '&amp;db=' . urlencode($db)
           . '&amp;table=' . urlencode($table)
           . '&amp;goto=tbl_properties.php3';

require('./tbl_properties_table_info.php3');
echo '<br />';


/**
 * Get the list of the fields of the current table
 */
PMA_mysql_select_db($db);
$table_def = PMA_mysql_query('SHOW FIELDS FROM ' . PMA_backquote($table));
if (isset($primary_key)) {
    $local_query = 'SELECT * FROM ' . PMA_backquote($table) . ' WHERE ' . $primary_key;
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    $row         = PMA_mysql_fetch_array($result);
    // No row returned
    if (!$row) {
        unset($row);
        unset($primary_key);
        $goto_cpy          = $goto;
        $goto              = 'tbl_properties.php3'
                           . '?lang=' . $lang
                           . '&amp;convcharset=' . $convcharset
                           . '&amp;server=' . $server
                           . '&amp;db=' . urlencode($db)
                           . '&amp;table=' . urlencode($table)
                           . '&amp;$show_query=1'
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
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url);
    unset($row);
}

// <markus@noga.de>
// retrieve keys into foreign fields, if any
$cfgRelation = PMA_getRelationsParam();
$foreigners  = PMA_getForeigners($db, $table);

/**
 * Displays the form
 */
// loic1: autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 5)
                 ? 'onpropertychange'
                 : 'onchange';
// Had to put the URI because when hosted on an https server,
// some browsers send wrongly this form to the http server.
?>

<!-- Change table properties form -->
<form method="post" action="tbl_replace.php3" name="insertForm">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
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

    <table border="<?php echo $cfg['Border']; ?>">
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strType; ?></th>
<?php
if ($cfg['ShowFunctionFields']) {
    echo '        <th>' . $strFunction . '</th>' . "\n";
}
?>
        <th><?php echo $strNull; ?></th>
        <th><?php echo $strValue; ?></th>
    </tr>

<?php
// Set if we passed the first timestamp field
$timestamp_seen = 0;
$fields_cnt     = mysql_num_rows($table_def);

for ($i = 0; $i < $fields_cnt; $i++) {
    // Display the submit button after every 15 lines --swix
    // (wanted to use an <a href="#bottom"> and <a name> instead,
    // but it didn't worked because of the <base href>)

    if ((($i % 15) == 0) && ($i != 0)) {
        ?>
    <tr>
        <th colspan="5" align="right">
            <input type="submit" value="<?php echo $strGo; ?>" tabindex="<?php echo $fields_cnt+6; ?>" />&nbsp;
        </th>
    </tr>
        <?php
    } // end if
    echo "\n";

    $row_table_def   = PMA_mysql_fetch_array($table_def);
    $field           = $row_table_def['Field'];
    if ($row_table_def['Type'] == 'datetime' && empty($row[$field])) {
        $row[$field] = date('Y-m-d H:i:s', time());
    }
    $len             = (eregi('float|double', $row_table_def['Type']))
                     ? 100
                     : @mysql_field_len($result, $i);
    $first_timestamp = 0;

    $bgcolor = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
    ?>
    <tr>
        <td align="center" bgcolor="<?php echo $bgcolor; ?>"><?php echo htmlspecialchars($field); ?></td>
    <?php
    echo "\n";

    // The type column
    $is_binary                  = eregi(' binary', $row_table_def['Type']);
    $is_blob                    = eregi('blob', $row_table_def['Type']);
    $is_char                    = eregi('char', $row_table_def['Type']);
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
            $type_nowrap  = ' nowrap="nowrap"';
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
    if ($cfg['ShowFunctionFields']) {
        if (($cfg['ProtectBinary'] && $is_blob)
            || ($cfg['ProtectBinary'] == 'all' && $is_binary)) {
            echo '        <td align="center" bgcolor="'. $bgcolor . '">' . $strBinary . '</td>' . "\n";
        } else if (strstr($row_table_def['True_Type'], 'enum') || strstr($row_table_def['True_Type'], 'set')) {
            echo '        <td align="center" bgcolor="'. $bgcolor . '">--</td>' . "\n";
        } else {
            ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <select name="funcs[<?php echo urlencode($field); ?>]" <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo ($i+2*$fields_cnt); ?>" >
                <option></option>
            <?php
            echo "\n";
            $selected     = '';
            for ($j = 0; $j < count($cfg['Functions']); $j++) {
                // for default function = NOW() on first timestamp field
                // -- swix/18jul01
                $selected = ($first_timestamp && $cfg['Functions'][$j] == 'NOW')
                          ? ' selected="selected"'
                          : '';
                echo '                ';
                echo '<option' . $selected . '>' . $cfg['Functions'][$j] . '</option>' . "\n";
            } // end for
            unset($selected);
            ?>
            </select>
        </td>
            <?php
        }
    } // end if ($cfg['ShowFunctionFields'])
    echo "\n";

    // The null column
    // ---------------
    echo '        <td bgcolor="' . $bgcolor . '">' . "\n";
    if (!(($cfg['ProtectBinary'] && $is_blob) || ($cfg['ProtectBinary'] == 'all' && $is_binary))
        && $row_table_def['Null'] == 'YES') {
        echo '            <input type="checkbox" tabindex="' . ($i+3*$fields_cnt) . '"'
             . ' name="fields_null[' . urlencode($field) . ']"';
        if ($data == 'NULL' && !$first_timestamp) {
            echo ' checked="checked"';
        }
        $onclick         = ' onclick="if (this.checked) {nullify(';
        if (strstr($row_table_def['True_Type'], 'enum')) {
            if (strlen($row_table_def['Type']) > 20) {
                $onclick .= '1, ';
            } else {
                $onclick .= '2, ';
            }
        } else if (strstr($row_table_def['True_Type'], 'set')) {
            $onclick     .= '3, ';
        } else {
            $onclick     .= '4, ';
        }
        $onclick         .= '\'' . urlencode($field) . '\', \'' . md5($field) . '\'); this.checked = true}; return true" />' . "\n";
        echo $onclick;
    } else {
        echo '            &nbsp;' . "\n";
    }
    echo '        </td>' . "\n";

    // The value column (depends on type)
    // ----------------

    // <markus@noga.de>
    // selection box for foreign keys

    // lem9: array_key_exists() only in PHP >= 4.1.0
    // if (array_key_exists($field, $foreigners)) {

    if (isset($foreigners[$field])) {
        $foreigner       = $foreigners[$field];
        $foreign_db      = $foreigner['foreign_db'];
        $foreign_table   = $foreigner['foreign_table'];
        $foreign_field   = $foreigner['foreign_field'];
        $foreign_display = PMA_getDisplayField($foreign_db, $foreign_table);

        $dispsql = 'SELECT ' . PMA_backquote($foreign_field) . ', ' . PMA_backquote($foreign_display)
                 . ' FROM ' . PMA_backquote($foreign_db) . '.' . PMA_backquote($foreign_table);
        // lem9: put a LIMIT in case of big foreign table (looking for better
        //       solution, maybe a configurable limit, or a message?)
        $dispsql .= ' LIMIT 100';
        $disp    = PMA_mysql_query($dispsql);

        echo '        <td bgcolor="' . $bgcolor . '">' . "\n";
        echo '            <select name="fields[' . urlencode($field) .  ']">' . "\n";
        while ($relrow = @PMA_mysql_fetch_array($disp)) {
            $key   = $relrow[$foreign_field];
            $value = $relrow[$foreign_display];
            echo '            <option value="' . urlencode($key) . '"';
            if ($key == $data) {
               echo ' selected="selected"';
            } // end if
            echo '>' . htmlspecialchars($key) . '-' .  htmlspecialchars($value) . '</option>' . "\n";
        } // end while
        echo '            </select>' . "\n";
        echo '        </td>' . "\n";
    }
    else if (strstr($row_table_def['True_Type'], 'text')) {
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols']; ?>" wrap="virtual" dir="<?php echo $text_dir; ?>"
                <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i; ?>"><?php echo $special_chars; ?></textarea>
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
            <input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$enum$" tabindex="<?php echo $i+1; ?>" />
        <?php
        echo "\n" . '            ' . $backup_field;

        // show dropdown or radio depend on length
        if (strlen($row_table_def['Type']) > 20) {
            echo "\n";
            ?>
            <select name="field_<?php echo md5($field); ?>[]" <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i+1; ?>">
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
                echo '<input type="radio" name="field_' . md5($field) . '[]" value="' . urlencode($enum_atom) . '" id="radio_field_' . $j . '" onclick="if (typeof(document.forms[\'insertForm\'].elements[\'fields_null[' . urlencode($field) . ']\']) != \'undefined\') {document.forms[\'insertForm\'].elements[\'fields_null[' . urlencode($field) .']\'].checked = false}"';
                if ($data == $enum_atom
                    || ($data == '' && (!isset($primary_key) || $row_table_def['Null'] != 'YES')
                        && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])) {
                    echo ' checked="checked"';
                }
                echo 'tabindex="' . $i . '" />' . "\n";
                echo '            <label for="radio_field_' . $j . '">' . htmlspecialchars($enum_atom) . '</label>' . "\n";
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
            <select name="field_<?php echo md5($field); ?>[]" size="<?php echo $size; ?>" multiple="multiple" <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i+1; ?>" >
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
        if (($cfg['ProtectBinary'] && $is_blob)
            || ($cfg['ProtectBinary'] == 'all' && $is_binary)) {
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
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfg['TextareaRows']; ?>" cols="<?php echo $cfg['TextareaCols']; ?>" wrap="virtual" dir="<?php echo $text_dir; ?>"
                <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i+1; ?>" ><?php echo $special_chars; ?></textarea>
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
            <input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>" class="textfield" <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i+1; ?>" />
        </td>
            <?php
        } // end if...elseif...else
    } // end else if
    else {
        // For char or varchar, respect the maximum length (M); for other
        // types (int or float), the length is not a limit on the values that
        // can be entered, so let's be generous (20) (we could also use the
        // real limits for each numeric type)
        if ($is_char) {
            $fieldsize = (($len > 40) ? 40 : $len);
            $maxlength = $len;
        }
        else {
            $fieldsize = $maxlength = 20;
        } // end if... else...
        echo "\n";
        ?>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <?php echo $backup_field . "\n"; ?>
            <input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>" class="textfield" <?php echo $chg_evt_handler; ?>="return unNullify('<?php echo urlencode($field); ?>')" tabindex="<?php echo $i+1; ?>" />
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
            <input type="radio" name="submit_type" value="<?php echo $strSave; ?>" id="radio_submit_type_save" checked="checked" tabindex="<?php echo $fields_cnt+1; ?>" /><label for="radio_submit_type_save"><?php echo $strSave; ?></label><br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strOr; ?><br />
            <input type="radio" name="submit_type" value="<?php echo $strInsertAsNewRow; ?>" id="radio_submit_type_insert_as_new_row" tabindex="<?php echo $fields_cnt+2; ?>" /><label for="radio_submit_type_insert_as_new_row"><?php echo $strInsertAsNewRow; ?></label>
    <?php
} else {
    echo "\n";
    ?>
            <input type="hidden" name="submit_type" value="<?php echo $strInsertAsNewRow; ?>" tabindex="<?php echo $fields_cnt+3; ?>" />
    <?php
    echo '            ' . $strInsertAsNewRow . "\n";
}
echo "\n";

// Defines whether "insert a new row after the current insert" should be
// checked or not (keep this choice sticky)
$checked = (!empty($message)) ? ' checked="checked"' : '';
?>
        </td>
        <td valign="middle">
            &nbsp;&nbsp;&nbsp;<b>-- <?php echo $strAnd; ?> --</b>&nbsp;&nbsp;&nbsp;
        </td>
        <td valign="middle" nowrap="nowrap">
            <input type="radio" name="after_insert" value="back" id="radio_after_insert_back" checked="checked" tabindex="<?php echo $fields_cnt+4; ?>" /><label for="radio_after_insert_back"><?php echo $strAfterInsertBack; ?></label><br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $strOr; ?><br />
            <input type="radio" name="after_insert" value="new_insert" id="radio_after_insert_new_insert"<?php echo $checked; ?> tabindex="<?php echo $fields_cnt+5; ?>" /><label for="radio_after_insert_new_insert"><?php echo $strAfterInsertNewInsert; ?></label>
        </td>
    </tr>

    <tr>
        <td colspan="3" align="center" valign="middle">
            <input type="submit" value="<?php echo $strGo; ?>" tabindex="<?php echo $fields_cnt+6; ?>" />
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
