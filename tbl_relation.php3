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

if (!empty($cfg['Server']['table_info'])
    && isset($submit_show) && $submit_show == 'true') {
    $test_query   = 'SELECT display_field FROM ' . PMA_backquote($cfg['Server']['table_info'])
                  . ' WHERE table_name = \'' . PMA_sqlAddslashes($table) . '\'';
    $test_rs      = mysql_query($test_query) or PMA_mysqlDie('', $test_query, '', $err_url_0);
    if ($test_rs && mysql_num_rows($test_rs) > 0) {
       $upd_query = 'UPDATE ' . PMA_backquote($cfg['Server']['table_info']) . ' SET'
                  . ' display_field = \'' . PMA_sqlAddslashes($display_field) . '\''
                  . ' WHERE table_name = \'' . PMA_sqlAddslashes($table) . '\'';
       $upd_rs    = mysql_query($upd_query) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
    } else {
       $ins_query = 'INSERT INTO ' . PMA_backquote($cfg['Server']['table_info']) . ' (table_name, display_field)'
                  . ' VALUES(\'' . PMA_sqlAddslashes($table) . '\', \'' . PMA_sqlAddslashes($display_field) .'\')';
       $ins_rs    = mysql_query($ins_query) or PMA_mysqlDie('', $ins_query, '', $err_url_0);
    }
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
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
    <input type="hidden" name="submit_rel" value="true" />

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
            <select name="destination[<?php echo htmlspecialchars($row['Field']); ?>]">
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
        } // end while
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
    if (!empty($cfg['Server']['table_info'])) {
        // Get "display_filed" infos
        $disp_query = 'SELECT display_field FROM ' .  PMA_backquote($cfg['Server']['table_info'])
                  . ' WHERE table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        $disp_rs    = mysql_query($disp_query) or PMA_mysqlDie('', $disp_query, '', $err_url_0);
        $row        = ($disp_rs ? mysql_fetch_array($disp_rs) : '');
        if (isset($row['display_field'])) {
            $disp   = $row['display_field'];
        }

        echo "\n";
        ?>
<form method="post" action="tbl_relation.php3" onchange="this.form.submit();">
    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
    <input type="hidden" name="server" value="<?php echo $server; ?>" />
    <input type="hidden" name="db" value="<?php echo $db; ?>" />
    <input type="hidden" name="table" value="<?php echo $table; ?>" />
    <input type="hidden" name="submit_show" value="true" />

    <p><?php echo $strChangeDisplay; ?></P>
    <select name="display_field">
        <?php
        echo "\n";
        mysql_data_seek($col_rs, 0);
        while ($row = @mysql_fetch_array($col_rs)) {
            echo '        <option value="' . htmlspecialchars($row['Field']) . '"';
            if (isset($disp) && $row['Field'] == $disp) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($row['Field']) . '</option>'. "\n";
        } // end while
        ?>
    </select>
    <input type="submit" value="<?php echo $strGo; ?>" />
</form>
        <?php
    } // end if
} // end if


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
