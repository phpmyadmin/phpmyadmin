<?php
/* $Id$ */


/**
 * Get the variables sent or posted to this script and displays the header
 */
require('./grab_globals.inc.php3');
require('./header.inc.php3');
if (get_magic_quotes_gpc()) {
    if (!empty($sql_query)) {
        $sql_query   = stripslashes($sql_query);
    }
    if (!empty($primary_key)) {
        $primary_key = stripslashes($primary_key);
    }
}


/**
 * Get the list of the fields of the current table
 */
mysql_select_db($db);
$table_def = mysql_query('SHOW FIELDS FROM ' . backquote($table));
if (isset($primary_key)) {
    $result = mysql_query('SELECT * FROM ' . backquote($table) . ' WHERE ' . $primary_key) or mysql_die();
    $row    = mysql_fetch_array($result);
}
else
{
    $result = mysql_query('SELECT * FROM ' . backquote($table) . ' LIMIT 1') or mysql_die();
}


/**
 * Displays the form
 */
?>

<!-- Change table properties form -->
<form method="post" action="tbl_replace.php3">
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
    <input type="hidden" name="goto" value="<?php echo $goto; ?>" />
    <input type="hidden" name="sql_query" value="<?php echo isset($sql_query) ? urlencode($sql_query) : ''; ?>" />
    <input type="hidden" name="pos" value="<?php echo isset($pos) ? $pos : 0; ?>" />
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
        <th><?php echo $strValue; ?></th>
    </tr>

<?php
// Set if we passed the first timestamp field
$timestamp_seen = 0;
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
    <tr bgcolor="<?php echo $bgcolor; ?>">
        <td align="center"><?php echo htmlspecialchars($field); ?></td>
    <?php
    echo "\n";

    // The type column
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
        <td align="center"<?php echo $type_nowrap; ?>><?php echo $type; ?></td>
    <?php
    echo "\n";

    // The function column
    if (isset($row) && isset($row[$field])) {
        $special_chars = htmlspecialchars($row[$field]);
        $data          = $row[$field];
        $backup_field  = '<input type="hidden" name="fields_prev[' . urlencode($field) . ']" value="' . urlencode($data) . '" />';
    } else {
        $data = $special_chars = $backup_field = '';
    }

    // Change by Bernard M. Piller <bernard@bmpsystems.com>
    // We don't want binary data to be destroyed
    // Note: from the MySQL manual: "BINARY doesn't affect how the column is
    //       stored or retrieved" so it does not mean that the contents is
    //       binary

    //if ((strstr($row_table_def['Type'], 'blob') || strstr($row_table_def['Type'], 'binary'))
    //    && !empty($data)) {
    if (strstr($row_table_def['True_Type'], 'blob')
        && !empty($data)
        && $cfgProtectBlob == TRUE) {
        echo '        <td align="center">' . $strBinary . '</td>' . "\n";
    } else if (strstr($row_table_def['True_Type'], 'enum') || strstr($row_table_def['True_Type'], 'set')) {
        echo '        <td align="center">--</td>' . "\n";
    } else {
        ?>
        <td>
            <select name="funcs[<?php echo $field; ?>]">
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

    // The value column (depends on type)
    if (strstr($row_table_def['True_Type'], 'text')) {
        ?>
        <td>
            <?php echo $backup_field . "\n"; ?>
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>"><?php if (!empty($special_chars)) echo $special_chars; ?></textarea>
        </td>
        <?php
        echo "\n";
        if (strlen($special_chars) > 32000) {
            echo '        <td>' . $strTextAreaLength . '</td>' . "\n";
        }
    }
    else if (strstr($row_table_def['True_Type'], 'enum')) {
        $enum        = str_replace('enum(', '', $row_table_def['Type']);
        $enum        = ereg_replace('\\)$', '', $enum);
        $enum        = explode('\',\'', substr($enum, 1, -1));
        $enum_cnt    = count($enum);
        $seenchecked = 0;
        ?>
        <td>
            <input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$enum$" />
        <?php
        echo "\n" . '            ' . $backup_field;

        // show dropdown or radio depend on length
        if (strlen($row_table_def['Type']) > 20) {
            echo "\n";
            ?>
            <select name="field_<?php echo md5($field); ?>[]">
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
                    // To be able to select the [Null] value when the field is
                    // null, we lose the ability to select besides the default
                    // value
                    echo ' selected="selected"';
                    $seenchecked = 1;
                }
                echo '>' . htmlspecialchars($enum_atom) . '</option>' . "\n";
            } // end for
             
            if ($row_table_def['Null'] == 'YES') {
                echo '                ';
                echo '<option value="null"';
                if ($seenchecked == 0) {
                    echo ' selected="selected"';
                }
                echo '>[' . $strNull . ']</option>' . "\n";
            } // end if
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
                echo '<input type="radio" name="field_' . md5($field) . '[]" value="' . urlencode($enum_atom) . '"';
                if ($data == $enum_atom
                    || ($data == '' && (!isset($primary_key) || $row_table_def['Null'] != 'YES')
                        && isset($row_table_def['Default']) && $enum_atom == $row_table_def['Default'])) {
                    // To be able to display a checkmark in the [Null] box when
                    // the field is null, we lose the ability to display a
                    // checkmark besides the default value
                    echo ' checked="checked"';
                    $seenchecked = 1;
                }
                echo ' />' . "\n";
                echo '            ' . htmlspecialchars($enum_atom) . "\n";
            } // end for

            if ($row_table_def['Null'] == 'YES') {
                echo '            ';
                echo '<input type="radio" name="field_' . md5($field) . '[]" value="null"';
                if ($seenchecked == 0) {
                    echo ' checked="checked"';
                }
                echo ' />' . "\n";
                echo '            [' . $strNull . ']' . "\n";
            } // end if
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
        <td>
            <?php echo $backup_field . "\n"; ?>
            <input type="hidden" name="fields[<?php echo urlencode($field); ?>]" value="$set$" />
            <select name="field_<?php echo md5($field); ?>[]" size="<?php echo $size; ?>" multiple="multiple">
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
    else if (strstr($row_table_def['Type'], 'blob') && !empty($data)) {
        if ($cfgProtectBlob == TRUE) {
            echo "\n";
            ?>
        <td align="center">
            <?php echo $strBinaryDoNotEdit . "\n"; ?>
        </td>
            <?php
        } else {
            echo "\n";
            ?>
        <td>
            <?php echo $backup_field . "\n"; ?>
            <textarea name="fields[<?php echo urlencode($field); ?>]" rows="<?php echo $cfgTextareaRows; ?>" cols="<?php echo $cfgTextareaCols; ?>"><?php if (!empty($special_chars)) echo $special_chars; ?></textarea>
        </td>
            <?php
        } // end if...else
    } // end else if
    else {
        $fieldsize = (($len > 40) ? 40 : $len);
        $maxlength = (($len < 4)  ? 4  : $len);
        echo "\n";
        ?>
        <td>
            <?php echo $backup_field . "\n"; ?>
            <input type="text" name="fields[<?php echo urlencode($field); ?>]" value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>" maxlength="<?php echo $maxlength; ?>" />
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
    <br /><br />

    <input type="submit" name="submit_type" value="<?php echo $strSave; ?>" />
<?php
if (isset($primary_key)) {
    ?>
    <input type="submit" name="submit_type" value="<?php echo $strInsertAsNewRow; ?>" />
    <?php
}
echo "\n";
?>
</form>


<?php
/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
