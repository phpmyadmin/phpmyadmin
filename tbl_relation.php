<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./tbl_properties_common.php');
$url_query .= '&amp;goto=tbl_properties.php';


/**
 * Gets tables informations
 */
require('./tbl_properties_table_info.php');

// Note: in tbl_properties_links.php we get and display the table comment.
// For InnoDB, this comment contains the REFER information but any update
// has not been done yet (will be done in tbl_relation.php later).
$avoid_show_comment = TRUE;

/**
 * Displays top menu links
 */
require('./tbl_properties_links.php');

require_once('./libraries/relation.lib.php');

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
function PMA_generate_dropdown($dropdown_question,$radio_name,$choices,$selected_value) {
    global $font_smallest;

    echo $dropdown_question . '&nbsp;&nbsp;';

    //echo '<select name="' . $radio_name . '" style="font-size: ' . $font_smallest . '">' . "\n";
    //echo '<option value="nix" style="font-size: ' . $font_smallest . '" >--</option>' . "\n";
    echo '<select name="' . $radio_name . '">' . "\n";
    echo '<option value="nix">--</option>' . "\n";

    foreach ($choices AS $one_value => $one_label) {
        echo '<option value="' . $one_value . '"';
        if ($selected_value == $one_value) {
            echo ' selected="selected" ';
        }
        //echo ' style="font-size: ' . $font_smallest . '">'
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

// updates for internal relations or innodb?
if (isset($submit_rel) && $submit_rel == 'true') {
    // u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
    if ($cfgRelation['relwork']) {

        foreach ($destination AS $master_field => $foreign_string) {
            if ($foreign_string != 'nix') {
                list($foreign_db, $foreign_table, $foreign_field) = explode('.', $foreign_string);
                if (!isset($existrel[$master_field])) {
                    $upd_query  = 'INSERT INTO ' . PMA_backquote($cfgRelation['relation'])
                                . '(master_db, master_table, master_field, foreign_db, foreign_table, foreign_field)'
                                . ' values('
                                . '\'' . PMA_sqlAddslashes($db) . '\', '
                                . '\'' . PMA_sqlAddslashes($table) . '\', '
                                . '\'' . PMA_sqlAddslashes($master_field) . '\', '
                                . '\'' . PMA_sqlAddslashes($foreign_db) . '\', '
                                . '\'' . PMA_sqlAddslashes($foreign_table) . '\','
                                . '\'' . PMA_sqlAddslashes($foreign_field) . '\')';
                } else if ($existrel[$master_field]['foreign_db'] . '.' .$existrel[$master_field]['foreign_table'] . '.' . $existrel[$master_field]['foreign_field'] != $foreign_string) {
                    $upd_query  = 'UPDATE ' . PMA_backquote($cfgRelation['relation']) . ' SET'
                                . ' foreign_db       = \'' . PMA_sqlAddslashes($foreign_db) . '\', '
                                . ' foreign_table    = \'' . PMA_sqlAddslashes($foreign_table) . '\', '
                                . ' foreign_field    = \'' . PMA_sqlAddslashes($foreign_field) . '\' '
                                . ' WHERE master_db  = \'' . PMA_sqlAddslashes($db) . '\''
                                . ' AND master_table = \'' . PMA_sqlAddslashes($table) . '\''
                                . ' AND master_field = \'' . PMA_sqlAddslashes($master_field) . '\'';
                } // end if... else....
            } else if (isset($existrel[$master_field])) {
                $upd_query      = 'DELETE FROM ' . PMA_backquote($cfgRelation['relation'])
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
    // ( for now, one index name only; we keep the definitions if the
    // foreign db is not the same)
    if (isset($destination_innodb)) {
        foreach ($destination_innodb AS $master_field => $foreign_string) {
            if ($foreign_string != 'nix') {
                list($foreign_db, $foreign_table, $foreign_field) = explode('.', $foreign_string);
                if (!isset($existrel_innodb[$master_field])) {
                    // no key defined for this field

                    // The next few lines are repeated below, so they
                    // could be put in an include file
                    // Note: I tried to enclose the db and table name with
                    // backquotes but MySQL 4.0.16 did not like the syntax
                    // (for example: `base2`.`table1` )

                    $upd_query  = 'ALTER TABLE ' . PMA_backquote($table)
                                . ' ADD FOREIGN KEY ('
                                . PMA_backquote(PMA_sqlAddslashes($master_field)) . ')'
                                . ' REFERENCES '
                                . PMA_backquote(PMA_sqlAddslashes($foreign_db) . '.'
                                . PMA_sqlAddslashes($foreign_table)) . '('
                                . PMA_backquote(PMA_sqlAddslashes($foreign_field)) . ')';

                    if (${$master_field . '_on_delete'} != 'nix') {
                        $upd_query   .= ' ON DELETE ' . $options_array[${$master_field . '_on_delete'}];
                    }
                    if (${$master_field . '_on_update'} != 'nix') {
                        $upd_query   .= ' ON UPDATE ' . $options_array[${$master_field . '_on_update'}];
                    }

                    // end repeated code

                } else if (($existrel_innodb[$master_field]['foreign_db'] . '.' .$existrel_innodb[$master_field]['foreign_table'] . '.' . $existrel_innodb[$master_field]['foreign_field'] != $foreign_string)
                  || ( ${$master_field . '_on_delete'} != (!empty($existrel_innodb[$master_field]['on_delete']) ? $existrel_innodb[$master_field]['on_delete'] : ''))
                  || ( ${$master_field . '_on_update'} != (!empty($existrel_innodb[$master_field]['on_update']) ? $existrel_innodb[$master_field]['on_update'] : ''))
                       ) {
                    // another foreign key is already defined for this field
                    // or
                    // an option has been changed for ON DELETE or ON UPDATE

                    // remove existing key
                    if (PMA_MYSQL_INT_VERSION >= 40013) {
                        $upd_query  = 'ALTER TABLE ' . PMA_backquote($table)
                                    . ' DROP FOREIGN KEY '
                                    . PMA_backquote($existrel_innodb[$master_field]['constraint']);

                        // I tried to send both in one query but it failed
                        $upd_rs     = PMA_DBI_query($upd_query);
                    }

                    // add another
                    $upd_query  = 'ALTER TABLE ' . PMA_backquote($table)
                                . ' ADD FOREIGN KEY ('
                                . PMA_backquote(PMA_sqlAddslashes($master_field)) . ')'
                                . ' REFERENCES '
                                . PMA_backquote(PMA_sqlAddslashes($foreign_db) . '.'
                                . PMA_sqlAddslashes($foreign_table)) . '('
                                . PMA_backquote(PMA_sqlAddslashes($foreign_field)) . ')';

                    if (${$master_field . '_on_delete'} != 'nix') {
                        $upd_query   .= ' ON DELETE ' . $options_array[${$master_field . '_on_delete'}];
                    }
                    if (${$master_field . '_on_update'} != 'nix') {
                        $upd_query   .= ' ON UPDATE ' . $options_array[${$master_field . '_on_update'}];
                    }

                } // end if... else....
            } else if (isset($existrel_innodb[$master_field])) {
                    if (PMA_MYSQL_INT_VERSION >= 40013) {
                        $upd_query  = 'ALTER TABLE ' . PMA_backquote($table)
                                . ' DROP FOREIGN KEY '
                                . PMA_backquote($existrel_innodb[$master_field]['constraint']);
                    }
            } // end if... else....

            if (isset($upd_query)) {
                $upd_rs    = PMA_DBI_try_query($upd_query);
                $tmp_error = PMA_DBI_getError();
                if (substr($tmp_error, 1, 4) == '1216') {
                    PMA_mysqlDie($tmp_error, $upd_query, FALSE, '', FALSE);
                    echo PMA_showMySQLDocu('manual_Table_types', 'InnoDB_foreign_key_constraints') . "\n";
                }
                if (substr($tmp_error, 1, 4) == '1005') {
                    echo '<p class="warning">' . $strNoIndex . ' (' . $master_field .')</p>'  . PMA_showMySQLDocu('manual_Table_types', 'InnoDB_foreign_key_constraints') . "\n";
                }
                unset($upd_query);
                unset($tmp_error);
            }
        } // end while
    } // end if isset($destination_innodb)

} // end if (updates for internal relations or InnoDB)


// U p d a t e s   f o r   d i s p l a y   f i e l d

if ($cfgRelation['displaywork']
    && isset($submit_show) && $submit_show == 'true') {

    if ($disp) {
        if ($display_field != '') {
            $upd_query = 'UPDATE ' . PMA_backquote($cfgRelation['table_info'])
                       . ' SET display_field = \'' . PMA_sqlAddslashes($display_field) . '\''
                       . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['table_info'])
                       . ' WHERE db_name  = \'' . PMA_sqlAddslashes($db) . '\''
                       . ' AND table_name = \'' . PMA_sqlAddslashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['table_info'])
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

if ($cfgRelation['commwork']
    && isset($submit_comm) && $submit_comm == 'true') {
    foreach ($comment AS $key => $value) {
        // garvin: I exported the snippet here to a function (relation.lib.php) , so it can be used multiple times throughout other pages where you can set comments.
        PMA_setComment($db, $table, $key, $value);
    }  // end while (transferred data)
} // end if (commwork)

// If we did an update, refresh our data
if (isset($submit_rel) && $submit_rel == 'true') {
    if ($cfgRelation['relwork']) {
        $existrel = PMA_getForeigners($db, $table, '', 'internal');
    }
    if ($tbl_type=='INNODB') {
        $existrel_innodb = PMA_getForeigners($db, $table, '', 'innodb');
    }
}
if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}
if ($cfgRelation['commwork']) {
    $comments = PMA_getComments($db, $table);
}


/**
 * Dialog
 */
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

    $tab_rs              = PMA_DBI_query($tab_query, NULL, PMA_DBI_QUERY_STORE);
    $selectboxall['nix'] = '--';
    $selectboxall_innodb['nix'] = '--';

    while ($curr_table = @PMA_DBI_fetch_row($tab_rs)) {
        if (($curr_table[0] != $table) && ($curr_table[0] != $cfg['Server']['relation'])) {
            PMA_DBI_select_db($db);

            // need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
            $fi_rs    = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($curr_table[0]) . ';', NULL, PMA_DBI_QUERY_STORE);
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

                    } else if (isset($curr_field['Non_unique']) && $curr_field['Non_unique'] == 0 && $seen_a_primary==FALSE) {
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
                    } else if ($tbl_type=='INNODB' && isset($curr_table[1]) && $curr_table[1]=='InnoDB') {
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
        }
        else if ($curr_table[0] == $table) {
            PMA_DBI_select_db($db);

            // need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
            $fi_rs    = PMA_DBI_query('SHOW KEYS FROM ' . PMA_backquote($curr_table[0]) . ';', NULL, PMA_DBI_QUERY_STORE);
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
$col_rs    = PMA_DBI_try_query('SHOW COLUMNS FROM ' . PMA_backquote($table) . ';', NULL, PMA_DBI_QUERY_STORE);

if ($col_rs && PMA_DBI_num_rows($col_rs) > 0) {
    while ($row = PMA_DBI_fetch_assoc($col_rs)) {
        $save_row[] = $row;
    }
    $saved_row_cnt  = count($save_row);
    echo $tbl_type=='INNODB' ? '' : '<table border="0" cellpadding="0" cellspacing="0">' . "\n"
                                  . '    <tr><td valign="top">' . "\n\n";
?>

<form method="post" action="tbl_relation.php">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="submit_rel" value="true" />

    <table cellpadding="2" cellspacing="1">
    <tr>
        <th colspan="<?php echo $tbl_type=='INNODB' ? '4' : '2'; ?>" align="center" class="tblHeaders"><b><?php echo $strLinksTo; ?></b></th>
    </tr>
    <tr>
        <th></th>
        <?php
        if ($cfgRelation['relwork']) {
            echo '        <th><b>' .$strInternalRelations;
            if ($tbl_type=='INNODB') {
                echo '&nbsp;' . PMA_showHint($strInternalNotNecessary);
            }
            echo '</b></th>';
        }
        if ($tbl_type=='INNODB') {
            echo '<th colspan="2">InnoDB';
            if (PMA_MYSQL_INT_VERSION < 40013) {
                echo '&nbsp;(**)';
            }
            echo '</th>';
        }
        ?>
    </tr>
    <?php
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $myfield = $save_row[$i]['Field'];
        echo "\n";
        ?>
    <tr>
        <td align="center" bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>"><b><?php echo $save_row[$i]['Field']; ?></b></td>
        <?php
        if ($cfgRelation['relwork']) { 
        ?>
        <td bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>">
            <select name="destination[<?php echo htmlspecialchars($save_row[$i]['Field']); ?>]">
            <?php
            echo "\n";

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
                     . '<option value="' . htmlspecialchars($foreign_field) . '"';
                echo ' selected="selected"';
                echo '>' . $foreign_field . '</option>'. "\n";
            }
        ?>
            </select>
        </td>
        <?php
        } // end if (internal relations)

        if ($tbl_type=='INNODB') {
            if (!empty($save_row[$i]['Key'])) {
        ?>
        <td bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>">
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
                    echo '>' . $foreign_field . '</option>'. "\n";
                }

        ?>
                </select>
        </td>
        <td bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>">
        <?php
                  PMA_generate_dropdown('ON DELETE',
                      htmlspecialchars($save_row[$i]['Field']) . '_on_delete',
                      $options_array,
                      (isset($existrel_innodb[$myfield]['on_delete']) ? $existrel_innodb[$myfield]['on_delete']: '') );

                  echo '&nbsp;&nbsp;&nbsp;';

                  PMA_generate_dropdown('ON UPDATE',
                      htmlspecialchars($save_row[$i]['Field']) . '_on_update',
                      $options_array,
                      (isset($existrel_innodb[$myfield]['on_update']) ? $existrel_innodb[$myfield]['on_update']: '') );

                } else {
                    echo '<td>' . PMA_showHint($strNoIndex) . '</td>';
                } // end if (a key exists)
            } // end if (InnoDB)
        ?>
        </td>
    </tr>
        <?php
    } // end for

    echo "\n";
    ?>
    <tr>
        <td colspan="<?php echo $tbl_type=='INNODB' ? '4' : '2'; ?>" align="center" class="tblFooters">
            <input type="submit" value="<?php echo '  ' . $strGo . '  '; ?>" />
        </td>
    </tr>
    </table>
    <?php
        if ($tbl_type=='INNODB') {
            if (PMA_MYSQL_INT_VERSION < 40013) {
                echo '** ' . sprintf($strUpgrade, 'MySQL', '4.0.13') . '<br />';
            }
        }
    ?>
</form>

    <?php
    echo $tbl_type=='INNODB' ? '' : "\n\n" . '    </td>' . "\n";
    if ($cfgRelation['displaywork']) {
        echo $tbl_type=='INNODB' ? '' : '    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n"
            . '    <td valign="top" align="center">' . "\n\n";
        // Get "display_field" infos
        $disp = PMA_getDisplayField($db, $table);

        echo "\n";
        ?>
<form method="post" action="tbl_relation.php">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="submit_show" value="true" />

    <p><b><?php echo $strChangeDisplay . ': '; ?></b><br />
    <select name="display_field" onchange="this.form.submit();" style="vertical-align: middle">
        <option value="">---</option>
        <?php
        echo "\n";
        foreach ($save_row AS $row) {
            echo '        <option value="' . htmlspecialchars($row['Field']) . '"';
            if (isset($disp) && $row['Field'] == $disp) {
                echo ' selected="selected"';
            }
            echo '>' . htmlspecialchars($row['Field']) . '</option>'. "\n";
        } // end while
        ?>
    </select>
    <script type="text/javascript" language="javascript">
    <!--
    // Fake js to allow the use of the <noscript> tag
    //-->
    </script>
    <noscript>
        <input type="submit" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
    </noscript>
    </p>
</form>
        <?php
        echo $tbl_type=='INNODB' ? '' : "\n\n" . '    </td>' . "\n";
    } // end if (displayworks)

    if ($cfgRelation['commwork']) {
        echo $tbl_type=='INNODB' ? "\n" : '    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>' . "\n"
                                        . '    <td valign="top">' . "\n\n";
        ?>
<form method="post" action="tbl_relation.php">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="submit_comm" value="true" />

    <table border="0" cellpadding="2" cellspacing="1">
    <tr>
        <th colspan="2" align="center" class="tblHeaders"><b><?php echo $strComments; ?></b></th>
    </tr>
        <?php
        for ($i = 0; $i < $saved_row_cnt; $i++) {
            $field = $save_row[$i]['Field'];
            echo "\n";
            ?>
    <tr>
        <td align="center" bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>"><b><?php echo $field; ?></b></td>
        <td bgcolor="<?php echo ($i % 2) ? $GLOBALS['cfg']['BgcolorOne'] : $GLOBALS['cfg']['BgcolorTwo']; ?>">
            <input type="text" name="comment[<?php echo $field; ?>]" value="<?php echo (isset($comments[$field]) ?  htmlspecialchars($comments[$field]) : ''); ?>" />
        </td>
    </tr>
            <?php
        } // end for

        echo "\n";
        ?>
    <tr>
        <td colspan="2" align="center" class="tblFooters">
            <input type="submit" value="<?php echo '  ' . $strGo . '  '; ?>" />
        </td>
    </tr>
    </table>
</form>
        <?php
    } //    end if (comments work)
    echo $tbl_type=='INNODB' ? '' : "\n\n" . '    </td></tr>' . "\n"
                                  . '</table>' . "\n\n";
} // end if (we have columns in this table)


/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
