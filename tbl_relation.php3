<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');


/**
 * Updates
 */
if (!empty($cfg['Server']['relation'])
    && isset($submit_rel) && $submit_rel == 'true') {
    //  first check if there is a entry allready
    $upd_query  = 'SELECT master_field, foreign_table, foreign_field FROM ' . PMA_backquote($cfg['Server']['relation'])
                . ' WHERE master_table = \'' . PMA_sqlAddslashes($table) . '\'';
    $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('', $upd_query, '', $err_url_0);

    while ($foundrel = @mysql_fetch_array($upd_rs)) {
        $currfield            = $foundrel['master_field'];
        $existrel[$currfield] = $foundrel['foreign_table'] . '.' . $foundrel['foreign_field'];
    }
    while (list($key, $value) = each($destination)) {
        if ($value != 'nix') {
            if (!isset($existrel[$key])) {
                $for        = explode('.', $destination[$key]);
                $upd_query  = 'INSERT INTO ' . PMA_backquote($cfg['Server']['relation']) 
                            . '(master_table, master_field, foreign_table, foreign_field)'
                            . ' values('
                            . '\'' . PMA_sqlAddslashes($table) . '\', '
                            . '\'' . PMA_sqlAddslashes($key)  . '\', '
                            . '\'' . PMA_sqlAddslashes($for[0]) . '\', '
                            . '\'' . PMA_sqlAddslashes($for[1]) . '\')';
                $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
            } else if ($existrel[$key] != $value) {
                $for        = explode('.', $destination[$key]);
                $upd_query  = 'UPDATE ' . PMA_backquote($cfg['Server']['relation']) . ' SET'
                            . ' foreign_table = \'' . PMA_sqlAddslashes($for[0]) . '\', foreign_field = \'' . PMA_sqlAddslashes($for[1]) . '\' '
                            . ' WHERE master_table = \'' . PMA_sqlAddslashes($table) . '\' AND master_field = \'' . PMA_sqlAddslashes($key) . '\'';
                $upd_rs     = mysql_query($upd_query) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
            } // end if... else....
        } else if (isset($existrel[$key])) {
            $for            = explode('.', $destination[$key]);
            $upd_query      = 'DELETE FROM ' . PMA_backquote($cfg['Server']['relation'])
                            . ' WHERE master_table = \'' . PMA_sqlAddslashes($table) . '\' AND master_field = \'' . PMA_sqlAddslashes($key) . '\'';
            $upd_rs         = mysql_query($upd_query) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
        } // end if... else....
    } // end while
} // end if


/**
 * Dialog
 */
if ($cfg['Server']['relation']) {
    $rel_work            = FALSE;
    // Mike Beck: get all Table-Fields to choose relation
    $tab_query           = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $tab_rs              = mysql_query($tab_query) or PMA_mysqlDie('', $tab_query, '', $err_url_0);
    $selectboxall['nix'] = '--';
    while ($curr_table = @mysql_fetch_array($tab_rs)) {
        if (($curr_table[0] != $table) && ($curr_table[0] != $cfg['Server']['relation'])) {
            $fi_query = 'SHOW KEYS FROM ' . PMA_backquote($curr_table[0]);
            $fi_rs    = mysql_query($fi_query) or PMA_mysqlDie('', $fi_query, '', $err_url_0);
            if ($fi_rs && mysql_num_rows($fi_rs) > 0) {
                while ($curr_field = mysql_fetch_array($fi_rs)) {
                    if (isset($curr_field['Key_name']) && $curr_field['Key_name'] == 'PRIMARY') {
                        $field_full = $curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                        break;
                    } else if (isset($curr_field['non_unique']) && $curr_field['non_unique'] == 0) {
                        // if we can't find a primary key we take any unique one
                        $field_full = $curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                    } // end if
                } // end while

                if (isset($field_full) && isset($field_v)) {
                    $selectboxall[$field_full] =  $field_v;
                }
            } // end if (mysql_num_rows)
        }
        if ($curr_table[0] == $cfg['Server']['relation']) {
            $rel_work = TRUE;
        }
    } // end while

    //  create Array of Relations (Mike Beck)
    if ($rel_work) {
        $rel_query = 'SELECT master_field, concat(foreign_table, \'.\', foreign_field) AS rel'
                   . ' FROM ' . PMA_backquote($cfg['Server']['relation'])
                   . ' WHERE master_table = \'' . PMA_sqlAddslashes($table) . '\'';
        $relations = @mysql_query($rel_query) or PMA_mysqlDie('', $rel_query, '', $err_url);

        while ($relrow = @mysql_fetch_array($relations)) {
            $rel_col            = $relrow['master_field'];
            $rel_dest[$rel_col] = $relrow['rel'];
        } // end while
    } // end if
} // end if

// now find out the columns of our $table
$col_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table);
$col_rs    = mysql_query($col_query) or PMA_mysqlDie('', $col_query, '', $err_url_0);

if ($col_rs && mysql_num_rows($col_rs) > 0) {
    ?>
<form method="post" action="tbl_relation.php3">
    <input type="hidden" name="submit_rel" value="true" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />

    <table>
    <tr>
        <td>&nbsp;</td>
        <td align="center"><b><?php echo $strLinksTo; ?></b></td>
    </tr>
    <?php

    while ($row = mysql_fetch_array($col_rs)) {
        echo "\n";
        ?>
    <tr>
        <th><?php echo $row[0]; ?></th>
        <td>
            <input type="hidden" name="src_field" value="<?php echo $row['Field']; ?>" />
            <select name="destination[<?php echo htmlspecialchars($row['Field']); ?>]" onchange="this.form.submit(); ">
        <?php
        echo "\n";
        reset($selectboxall);
        while (list($key, $value) = each($selectboxall)) {
            $myfield = $row['Field'];
            echo '                '
                 . '<option value="' . htmlspecialchars($key) . '"';
            if (isset($rel_dest[$myfield]) && $key == $rel_dest[$myfield]) {
                echo ' selected="selected"';
            }
            echo '>' . $value . '</option>'. "\n";
        }
        ?>
            </select>
        </td>
    </tr>
        <?php
    } // end while

    echo "\n";
    ?>
    <tr>
        <td colspan="2" align="center">
            <input type="submit" value="<?php echo $strGo; ?>" />
        </td>
    </tr>
    </table>
</form>
    <?php
} // end if


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
