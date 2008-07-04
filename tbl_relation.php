<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/tbl_common.php';
$url_query .= '&amp;goto=tbl_sql.php';


/**
 * Gets tables informations
 */
require_once './libraries/tbl_info.inc.php';

// Note: in libraries/tbl_links.inc.php we get and display the table comment.
// For InnoDB, this comment contains the REFER information but any update
// has not been done yet (will be done in tbl_relation.php later).
$avoid_show_comment = TRUE;

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';

require_once './libraries/relation.lib.php';

$options_array = array('CASCADE' => 'CASCADE', 'SET_NULL' => 'SET NULL', 'NO_ACTION' => 'NO ACTION', 'RESTRICT' => 'RESTRICT');

/**
 * Generate dropdown choices
 *
 * @param   string   Message to display
 * @param   string   Name of the <select> field
 * @param   array    Choices for dropdown
 * @return  string   The existing value (for selected)
 *
 * @access  public
 */
function PMA_generate_dropdown($dropdown_question, $radio_name, $choices, $selected_value)
{
    echo $dropdown_question . '&nbsp;&nbsp;';

    echo '<select name="' . $radio_name . '">' . "\n";
    echo '<option value="nix">--</option>' . "\n";

    foreach ($choices AS $one_value => $one_label) {
        echo '<option value="' . $one_value . '"';
        if ($selected_value == $one_value) {
            echo ' selected="selected" ';
        }
        echo '>' . $one_label . '</option>' . "\n";
    }
    echo '</select>' . "\n";
    echo "\n";
}


/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();


/**
 * Updates
 */

// ensure we are positionned to our current db (since the previous reading
// of relations makes pmadb the current one, maybe depending on the MySQL version)
PMA_DBI_select_db($db);

if ($cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if ($tbl_type=='INNODB') {
    $existrel_innodb = PMA_getForeigners($db, $table, '', 'innodb');
}
if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}

// u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
if (isset($destination) && $cfgRelation['relwork']) {

    foreach ($destination AS $master_field => $foreign_string) {
        if ($foreign_string != 'nix') {
            list($foreign_db, $foreign_table, $foreign_field) = explode('.', $foreign_string);
            if (!isset($existrel[$master_field])) {
                $upd_query  = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                            . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                            . ' values('
                            . '\'' . PMA_sqlAddslashes($db) . '\', '
                            . '\'' . PMA_sqlAddslashes($table) . '\', '
                            . '\'' . PMA_sqlAddslashes($master_field) . '\', '
                            . '\'' . PMA_sqlAddslashes($foreign_db) . '\', '
                            . '\'' . PMA_sqlAddslashes($foreign_table) . '\','
                            . '\'' . PMA_sqlAddslashes($foreign_field) . '\')';
            } elseif ($existrel[$master_field]['foreign_db'] . '.' .$existrel[$master_field]['foreign_table'] . '.' . $existrel[$master_field]['foreign_field'] != $foreign_string) {
                $upd_query  = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation']) . ' SET'
                            . ' foreign_db       = \'' . PMA_sqlAddslashes($foreign_db) . '\', '
                            . ' foreign_table    = \'' . PMA_sqlAddslashes($foreign_table) . '\', '
                            . ' foreign_field    = \'' . PMA_sqlAddslashes($foreign_field) . '\' '
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND master_field = \'' . PMA_sqlAddslashes($master_field) . '\'';
            } // end if... else....
        } elseif (isset($existrel[$master_field])) {
            $upd_query      = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['relation'])
                            . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                            . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                            . ' AND master_field = \'' . PMA_sqlAddslashes($master_field) . '\'';
        } // end if... else....
        if (isset($upd_query)) {
            $upd_rs         = PMA_query_as_cu($upd_query);
            unset($upd_query);
        }
    } // end while
} // end if (updates for internal relations)

// u p d a t e s   f o r   I n n o D B
// (for now, one index name only; we keep the definitions if the
// foreign db is not the same)
// I use $sql_query to be able to display directly the query via
// PMA_showMessage()

if (isset($_REQUEST['destination_innodb'])) {
    $display_query = '';
    $seen_error = false;
    foreach ($_REQUEST['destination_innodb'] as $master_field => $foreign_string) {
        if ($foreign_string != 'nix') {
            list($foreign_db, $foreign_table, $foreign_field) = explode('.', $foreign_string);
            if (!isset($existrel_innodb[$master_field])) {
                // no key defined for this field

                // The next few lines are repeated below, so they
                // could be put in an include file
                // Note: I tried to enclose the db and table name with
                // backquotes but MySQL 4.0.16 did not like the syntax
                // (for example: `base2`.`table1`)

                $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                            . ' ADD FOREIGN KEY ('
                            . PMA_backquote($master_field) . ')'
                            . ' REFERENCES '
                            . PMA_backquote($foreign_db) . '.'
                            . PMA_backquote($foreign_table) . '('
                            . PMA_backquote($foreign_field) . ')';

                if ($_REQUEST['on_delete'][$master_field] != 'nix') {
                    $sql_query   .= ' ON DELETE ' . $options_array[$_REQUEST['on_delete'][$master_field]];
                }
                if ($_REQUEST['on_update'][$master_field] != 'nix') {
                    $sql_query   .= ' ON UPDATE ' . $options_array[$_REQUEST['on_update'][$master_field]];
                }
                $sql_query .= ';';
                $display_query .= $sql_query . "\n";
                // end repeated code

            } elseif (($existrel_innodb[$master_field]['foreign_db'] . '.' .$existrel_innodb[$master_field]['foreign_table'] . '.' . $existrel_innodb[$master_field]['foreign_field'] != $foreign_string)
              || ($_REQUEST['on_delete'][$master_field] != (!empty($existrel_innodb[$master_field]['on_delete']) ? $existrel_innodb[$master_field]['on_delete'] : 'nix'))
              || ($_REQUEST['on_update'][$master_field] != (!empty($existrel_innodb[$master_field]['on_update']) ? $existrel_innodb[$master_field]['on_update'] : 'nix'))
                   ) {
                // another foreign key is already defined for this field
                // or
                // an option has been changed for ON DELETE or ON UPDATE

                // remove existing key
                if (PMA_MYSQL_INT_VERSION >= 40013) {
                    $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                                . ' DROP FOREIGN KEY '
                                . PMA_backquote($existrel_innodb[$master_field]['constraint']) . ';';

                    // I tried to send both in one query but it failed
                    $upd_rs     = PMA_DBI_query($sql_query);
                    $display_query .= $sql_query . "\n";
                }

                // add another
                $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                            . ' ADD FOREIGN KEY ('
                            . PMA_backquote($master_field) . ')'
                            . ' REFERENCES '
                            . PMA_backquote($foreign_db) . '.'
                            . PMA_backquote($foreign_table) . '('
                            . PMA_backquote($foreign_field) . ')';

                if ($_REQUEST['on_delete'][$master_field] != 'nix') {
                    $sql_query   .= ' ON DELETE '
                        . $options_array[$_REQUEST['on_delete'][$master_field]];
                }
                if ($_REQUEST['on_update'][$master_field] != 'nix') {
                    $sql_query   .= ' ON UPDATE '
                        . $options_array[$_REQUEST['on_update'][$master_field]];
                }
                $sql_query .= ';';
                $display_query .= $sql_query . "\n";

            } // end if... else....
        } elseif (isset($existrel_innodb[$master_field])) {
            if (PMA_MYSQL_INT_VERSION >= 40013) {
                $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                        . ' DROP FOREIGN KEY '
                        . PMA_backquote($existrel_innodb[$master_field]['constraint']);
                $sql_query .= ';';
                $display_query .= $sql_query . "\n";
            }
        } // end if... else....

        if (! empty($sql_query)) {
            $upd_rs    = PMA_DBI_try_query($sql_query);
            $tmp_error = PMA_DBI_getError();
            if (! empty($tmp_error)) {
                $seen_error = true;
            }
            if (substr($tmp_error, 1, 4) == '1216'
            ||  substr($tmp_error, 1, 4) == '1452') {
                PMA_mysqlDie($tmp_error, $sql_query, FALSE, '', FALSE);
                echo PMA_showMySQLDocu('manual_Table_types', 'InnoDB_foreign_key_constraints') . "\n";
            }
            if (substr($tmp_error, 1, 4) == '1005') {
                echo '<p class="warning">' . $strForeignKeyError . ' : ' . $master_field
                    .'</p>'  . PMA_showMySQLDocu('manual_Table_types', 'InnoDB_foreign_key_constraints') . "\n";
            }
            unset($tmp_error);
            $sql_query = '';
        }
    } // end foreach
    if (!empty($display_query)) {
        if ($seen_error) {
            PMA_showMessage($strError);
        } else {
            PMA_showMessage($strSuccess);
        }
    }
} // end if isset($destination_innodb)


// U p d a t e s   f o r   d i s p l a y   f i e l d

if ($cfgRelation['displaywork']
    && isset($display_field)) {

    if ($disp) {
        if ($display_field != '') {
            $upd_query = 'UPDATE ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                       . ' SET display_field = \'' . PMA_sqlAddslashes($display_field) . '\''
                       . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                       . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_info'])
                   . '(db_name, table_name, display_field) '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddslashes($db) . '\','
                   . '\'' . PMA_sqlAddslashes($table) . '\','
                   . '\'' . PMA_sqlAddslashes($display_field) . '\')';
    }

    if (isset($upd_query)) {
        $upd_rs    = PMA_query_as_cu($upd_query);
    }
} // end if

// If we did an update, refresh our data
if (isset($destination) && $cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (isset($destination_innodb) && $tbl_type=='INNODB') {
    $existrel_innodb = PMA_getForeigners($db, $table, '', 'innodb');
}

if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}


/**
 * Dialog
 */

// common form
echo '<form method="post" action="tbl_relation.php">' . "\n";
echo PMA_generate_common_hidden_inputs($db, $table);


// relations

if ($cfgRelation['relwork'] || $tbl_type=='INNODB') {
    // To choose relations we first need all tables names in current db
    // and if PMA version permits and the main table is innodb,
    // we use SHOW TABLE STATUS because we need to find other InnoDB tables

    if ($tbl_type=='INNODB') {
        $tab_query           = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db);
    // [0] of the row is the name
    // [1] is the type
    } else {
        $tab_query           = 'SHOW TABLES FROM ' . PMA_backquote($db);
    }
    // [0] of the row is the name

    $tab_rs              = PMA_DBI_query($tab_query, null, PMA_DBI_QUERY_STORE);
    $selectboxall['nix'] = '--';
    $selectboxall_innodb['nix'] = '--';

    while ($curr_table = @PMA_DBI_fetch_row($tab_rs)) {
        if (($curr_table[0] != $table) && ($curr_table[0] != $cfg['Server']['relation'])) {
            PMA_DBI_select_db($db);

            // need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
            $fi_rs    = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($curr_table[0]) . ';', null, PMA_DBI_QUERY_STORE);
            if ($fi_rs && PMA_DBI_num_rows($fi_rs) > 0) {
                $seen_a_primary = FALSE;
                while ($curr_field = PMA_DBI_fetch_assoc($fi_rs)) {
                    if (isset($curr_field['Key_name']) && $curr_field['Key_name'] == 'PRIMARY') {
                        $seen_a_primary = TRUE;
                        $field_full = $db . '.' .$curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                        $selectboxall[$field_full] =  $field_v;
                        // there could be more than one segment of the primary
                        // so do not break

                        // Please watch here, tbl_type is INNODB but the
                        // resulting value of SHOW KEYS is InnoDB

                        if ($tbl_type=='INNODB' && isset($curr_table[1]) && $curr_table[1]=='InnoDB') {
                            $selectboxall_innodb[$field_full] =  $field_v;
                        }

                    } elseif (isset($curr_field['Non_unique']) && $curr_field['Non_unique'] == 0 && $seen_a_primary==FALSE) {
                        // if we can't find a primary key we take any unique one
                        // (in fact, we show all segments of unique keys
                        //  and all unique keys)
                        $field_full = $db . '.' . $curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                        $selectboxall[$field_full] =  $field_v;
                        if ($tbl_type=='INNODB' && isset($curr_table[1]) && $curr_table[1]=='InnoDB') {
                            $selectboxall_innodb[$field_full] =  $field_v;
                        }

                    // for InnoDB, any index is allowed
                    } elseif ($tbl_type=='INNODB' && isset($curr_table[1]) && $curr_table[1]=='InnoDB') {
                        $field_full = $db . '.' . $curr_field['Table'] . '.' . $curr_field['Column_name'];
                        $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                        $selectboxall_innodb[$field_full] =  $field_v;

                    } // end if
                } // end while over keys
            } // end if (PMA_DBI_num_rows)
            PMA_DBI_free_result($fi_rs);
            unset($fi_rs);
        // Mike Beck - 24.07.02: i've been asked to add all keys of the
        // current table (see bug report #574851)
        } elseif ($curr_table[0] == $table) {
            PMA_DBI_select_db($db);

            // need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
            $fi_rs    = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($curr_table[0]) . ';', null, PMA_DBI_QUERY_STORE);
            if ($fi_rs && PMA_DBI_num_rows($fi_rs) > 0) {
                while ($curr_field = PMA_DBI_fetch_assoc($fi_rs)) {
                    $field_full = $db . '.' . $curr_field['Table'] . '.' . $curr_field['Column_name'];
                    $field_v    = $curr_field['Table'] . '->' . $curr_field['Column_name'];
                    $selectboxall[$field_full] =  $field_v;
                    if ($tbl_type=='INNODB' && isset($curr_table[1]) && $curr_table[1]=='InnoDB') {
                        $selectboxall_innodb[$field_full] =  $field_v;
                    }
                } // end while
            } // end if (PMA_DBI_num_rows)
            PMA_DBI_free_result($fi_rs);
            unset($fi_rs);
        }
    } // end while over tables

} // end if


// Now find out the columns of our $table
// need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
$col_rs    = PMA_DBI_try_query('SHOW COLUMNS FROM ' . PMA_backquote($table) . ';', null, PMA_DBI_QUERY_STORE);

if ($col_rs && PMA_DBI_num_rows($col_rs) > 0) {
    while ($row = PMA_DBI_fetch_assoc($col_rs)) {
        $save_row[] = $row;
    }
    $saved_row_cnt  = count($save_row);
    ?>
    <fieldset>
    <legend><?php echo $strLinksTo; ?></legend>

    <table>
    <tr><th></th>
    <?php
    if ($cfgRelation['relwork']) {
        echo '<th>' . $strInternalRelations;
        if ($tbl_type=='INNODB') {
            echo PMA_showHint($strInternalNotNecessary);
        }
        echo '</th>';
    }
    if ($tbl_type=='INNODB') {
        echo '<th colspan="2">InnoDB';
        if (PMA_MYSQL_INT_VERSION < 40013) {
            echo '(**)';
        }
        echo '</th>';
    }
    ?>
    </tr>
    <?php
    $odd_row = true;
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $myfield = $save_row[$i]['Field'];
        ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <td align="center">
            <b><?php echo $save_row[$i]['Field']; ?></b></td>
        <?php
        if ($cfgRelation['relwork']) {
            ?>
        <td><select name="destination[<?php echo htmlspecialchars($save_row[$i]['Field']); ?>]">
            <?php
            // PMA internal relations
            if (isset($existrel[$myfield])) {
                $foreign_field    = $existrel[$myfield]['foreign_db'] . '.'
                         . $existrel[$myfield]['foreign_table'] . '.'
                         . $existrel[$myfield]['foreign_field'];
            } else {
                $foreign_field    = FALSE;
            }
            $seen_key = FALSE;
            foreach ($selectboxall AS $key => $value) {
                echo '                '
                     . '<option value="' . htmlspecialchars($key) . '"';
                if ($foreign_field && $key == $foreign_field) {
                    echo ' selected="selected"';
                    $seen_key = TRUE;
                }
                echo '>' . $value . '</option>'. "\n";
            } // end while

            // if the link defined in relationtable points to a foreign field
            // that is not a key in the foreign table, we show the link
            // (will not be shown with an arrow)
            if ($foreign_field && !$seen_key) {
                echo '                '
                    .'<option value="' . htmlspecialchars($foreign_field) . '"'
                    .' selected="selected"'
                    .'>' . $foreign_field . '</option>'. "\n";
            }
            ?>
            </select>
        </td>
            <?php
        } // end if (internal relations)

        if ($tbl_type=='INNODB') {
            echo '<td>';
            if (!empty($save_row[$i]['Key'])) {
                ?>
            <span class="formelement">
            <select name="destination_innodb[<?php echo htmlspecialchars($save_row[$i]['Field']); ?>]">
                <?php
                if (isset($existrel_innodb[$myfield])) {
                    $foreign_field    = $existrel_innodb[$myfield]['foreign_db'] . '.'
                             . $existrel_innodb[$myfield]['foreign_table'] . '.'
                             . $existrel_innodb[$myfield]['foreign_field'];
                } else {
                    $foreign_field    = FALSE;
                }

                $found_foreign_field = FALSE;
                foreach ($selectboxall_innodb AS $key => $value) {
                    echo '                '
                         . '<option value="' . htmlspecialchars($key) . '"';
                    if ($foreign_field && $key == $foreign_field) {
                        echo ' selected="selected"';
                        $found_foreign_field = TRUE;
                    }
                    echo '>' . $value . '</option>'. "\n";
                } // end while

                // we did not find the foreign field in the tables of current db,
                // must be defined in another db so show it to avoid erasing it
                if (!$found_foreign_field && $foreign_field) {
                    echo '                '
                         . '<option value="' . htmlspecialchars($foreign_field) . '"';
                    echo ' selected="selected"';
                    echo '>' . $foreign_field . '</option>' . "\n";
                }

                ?>
            </select>
            </span>
            <span class="formelement">
                <?php
                PMA_generate_dropdown('ON DELETE',
                    'on_delete[' . htmlspecialchars($save_row[$i]['Field']) . ']',
                    $options_array,
                    isset($existrel_innodb[$myfield]['on_delete']) ? $existrel_innodb[$myfield]['on_delete']: '');

                echo '</span>' . "\n"
                    .'<span class="formelement">' . "\n";

                PMA_generate_dropdown('ON UPDATE',
                    'on_update[' . htmlspecialchars($save_row[$i]['Field']) . ']',
                    $options_array,
                    isset($existrel_innodb[$myfield]['on_update']) ? $existrel_innodb[$myfield]['on_update']: '');
                echo '</span>' . "\n";
            } else {
                echo $strNoIndex;
            } // end if (a key exists)
            echo '        </td>';
        } // end if (InnoDB)
        ?>
    </tr>
        <?php
    } // end for

    echo '    </table>' . "\n";
    echo '</fieldset>' . "\n";

    if ($cfgRelation['displaywork']) {
        // Get "display_field" infos
        $disp = PMA_getDisplayField($db, $table);
        ?>
    <fieldset>
        <label><?php echo $strChangeDisplay . ': '; ?></label>
        <select name="display_field" style="vertical-align: middle">
            <option value="">---</option>
        <?php
        foreach ($save_row AS $row) {
            echo '            <option value="' . htmlspecialchars($row['Field']) . '"';
            if (isset($disp) && $row['Field'] == $disp) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($row['Field']) . '</option>'. "\n";
        } // end while
        ?>
        </select>
    </fieldset>
        <?php
    } // end if (displayworks)
    ?>
    <fieldset class="tblFooters">
        <input type="submit" value="<?php echo $strSave; ?>" />
    </fieldset>
</form>
    <?php
} // end if (we have columns in this table)

if ($tbl_type === 'INNODB' && PMA_MYSQL_INT_VERSION < 40013) {
    echo '<div class="warning">'
        .'** ' . sprintf($strUpgrade, 'MySQL', '4.0.13')
        .'</div>';
}

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
