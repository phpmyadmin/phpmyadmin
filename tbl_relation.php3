<?php
/* $Id$ */


/**
 * Gets some core libraries
 */
require('./libraries/grab_globals.lib.php3');
require('./libraries/common.lib.php3');
require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');
require('./libraries/relation.lib.php3');

$cfgRelation = PMA_getRelationsParam();

/**
 * Updates
 */
if ($cfgRelation['relwork']) {
        $existrel = getForeigners($db,$table);
}
if ($cfgRelation['displaywork']) {
    $disp = getDisplayField($db,$table);
}
if ($cfgRelation['relwork']
    && isset($submit_rel) && $submit_rel == 'true') {

    while (list($key, $value) = each($destination)) {
        if ($value != 'nix') {
            $for        = explode('.', $value);
            if (!isset($existrel[$key])) {
                $upd_query  = 'INSERT INTO ' . PMA_backquote($cfgRelation['relation'])
                            . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                            . ' values('
                            . '\'' . PMA_sqlAddslashes($db) . '\', '
                            . '\'' . PMA_sqlAddslashes($table) . '\', '
                            . '\'' . PMA_sqlAddslashes($key)  . '\', '
                            . '\'' . PMA_sqlAddslashes($for[0]) . '\', '
                            . '\'' . PMA_sqlAddslashes($for[1]) . '\','
                            . '\'' . PMA_sqlAddslashes($for[2]) . '\')';
            } else if ($existrel[$key] != $value) {
                $upd_query  = 'UPDATE ' . PMA_backquote($cfgRelation['relation']) . ' SET'
                            . ' foreign_db       = \'' . PMA_sqlAddslashes($for[0]) .'\', '
                            . ' foreign_table    = \'' . PMA_sqlAddslashes($for[1]) .'\', '
                            . ' foreign_field    = \'' . PMA_sqlAddslashes($for[2]) .'\' '
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db)     . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($table)  . '\''
                            . ' AND master_field = \'' . PMA_sqlAddslashes($key)    . '\'';
            } // end if... else....
        } else if (isset($existrel[$key])) {
            $upd_query      = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db)    . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND master_field = \'' . PMA_sqlAddslashes($key)   . '\'';
        } // end if... else....
        if(isset($upd_query)){
            if (isset($dbh)) {
                mysql_select_db($cfgRelation['db'],$dbh);
                $upd_rs = PMA_mysql_query($upd_query, $dbh) or PMA_mysqlDie(mysql_error($dbh), $upd_query, '', $err_url_0);
                mysql_select_db($db,$dbh);
            } else {
                mysql_select_db($cfgRelation['db']);
                $upd_rs = PMA_mysql_query($upd_query, $GLOBALS['dbh']) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
                mysql_select_db($db);
            }
        }
    } // end while
} // end if

if ($cfgRelation['displaywork']
    && isset($submit_show) && $submit_show == 'true') {

    if ($disp) {
        $upd_query = 'UPDATE ' .  PMA_backquote($cfgRelation['table_info'])
                   . ' SET display_field = \'' . PMA_sqlAddslashes($display_field) . '\''
                   . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                   . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
    } else {
        $upd_query = 'INSERT INTO ' .  PMA_backquote($cfgRelation['table_info'])
                   . '(db_name,table_name,display_field) '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddslashes($db) . '\','
                   . '\'' . PMA_sqlAddslashes($table) . '\','
                   . '\'' . PMA_sqlAddslashes($display_field) .'\')';
    }
    if(isset($upd_query)){
        if (isset($dbh)) {
            mysql_select_db($cfgRelation['db'],$dbh);
            $upd_rs = PMA_mysql_query($upd_query, $dbh) or PMA_mysqlDie(mysql_error($dbh), $upd_query, '', $err_url_0);
            mysql_select_db($db,$dbh);
        } else {
            mysql_select_db($cfgRelation['db']);
            $upd_rs = PMA_mysql_query($upd_query, $GLOBALS['dbh']) or PMA_mysqlDie('', $upd_query, '', $err_url_0);
            mysql_select_db($db);
        }
    }
} // end if

//  now that we might have changed we have to see again
if ($cfgRelation['relwork']) {
        $existrel = getForeigners($db,$table);
}
if ($cfgRelation['displaywork']) {
    $disp = getDisplayField($db,$table);
}
/**
 * Dialog
 */
if ($cfgRelation['relwork']) {

    // to choose Relations we first need all tablenames in current db
    $tab_query           = 'SHOW TABLES FROM ' . PMA_backquote($db);
    $tab_rs              = PMA_mysql_query($tab_query) or PMA_mysqlDie('', $tab_query, '', $err_url_0);
    $selectboxall['nix'] = '--';
    while ($curr_table = @PMA_mysql_fetch_array($tab_rs)) {
        if (($curr_table[0] != $table) && ($curr_table[0] != $cfg['Server']['relation'])) {
            $fi_query = 'SHOW KEYS FROM ' . PMA_backquote($curr_table[0]);
            $fi_rs    = PMA_mysql_query($fi_query) or PMA_mysqlDie('', $fi_query, '', $err_url_0);
            if ($fi_rs && mysql_num_rows($fi_rs) > 0) {
                while ($curr_field = PMA_mysql_fetch_array($fi_rs)) {
                    if (isset($curr_field['Key_name']) && $curr_field['Key_name'] == 'PRIMARY') {
                        $field_full = $db . '.' .$curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                        break;
                    } else if (isset($curr_field['non_unique']) && $curr_field['non_unique'] == 0) {
                        // if we can't find a primary key we take any unique one
                        $field_full = $db . '.' . $curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                    } // end if
                } // end while

                if (isset($field_full) && isset($field_v)) {
                    $selectboxall[$field_full] =  $field_v;
                }
            } // end if (mysql_num_rows)
        }
    } // end while

    //  create Array of Relations (Mike Beck)
    $rel_dest = getForeigners($db,$table);
} // end if

// now find out the columns of our $table
$col_query = 'SHOW COLUMNS FROM ' . PMA_backquote($table);
$col_rs    = PMA_mysql_query($col_query) or PMA_mysqlDie('', $col_query, '', $err_url_0);

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

    while ($row = PMA_mysql_fetch_array($col_rs)) {
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
            if(isset($existrel[$myfield])){
                $test    = $existrel[$myfield]['foreign_db'] . '.'
                         . $existrel[$myfield]['foreign_table'] . '.'
                         . $existrel[$myfield]['foreign_field'];
            } else {
                $test    = FALSE;
            }
            echo '                '
                 . '<option value="' . htmlspecialchars($key) . '"';
            if ( $test && $key == $test) {
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
    if ($cfgRelation['displaywork']) {
        // Get "display_filed" infos
        $disp = getDisplayField($db,$table);

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
        while ($row = @PMA_mysql_fetch_array($col_rs)) {
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
