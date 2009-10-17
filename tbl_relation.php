<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display table relations for viewing and editing
 *
 * includes phpMyAdmin relations and InnoDB relations
 *
 * @todo fix name handling: currently names with dots (.) are not properly handled for internal relations (but foreign keys relations are correct)
 * @todo foreign key constraints require both fields being of equal type and size
 * @todo check foreign fields to be from same type and size, all other makes no sense
 * @todo add an link to create an index required for constraints, or an option to do automatically
 * @todo if above todos are fullfilled we can add all fields meet requirements in the select dropdown
 * @version $Id$
 * @package phpMyAdmin
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

$options_array = array(
    'CASCADE'   => 'CASCADE',
    'SET_NULL'  => 'SET NULL',
    'NO_ACTION' => 'NO ACTION',
    'RESTRICT'  => 'RESTRICT',
);

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
function PMA_generate_dropdown($dropdown_question, $select_name, $choices, $selected_value)
{
    echo htmlspecialchars($dropdown_question) . '&nbsp;&nbsp;';

    echo '<select name="' . htmlspecialchars($select_name) . '">' . "\n";
    echo '<option value=""></option>' . "\n";

    foreach ($choices as $one_value => $one_label) {
        echo '<option value="' . htmlspecialchars($one_value) . '"';
        if ($selected_value == $one_value) {
            echo ' selected="selected" ';
        }
        echo '>' . htmlspecialchars($one_label) . '</option>' . "\n";
    }
    echo '</select>' . "\n";
}

/**
 * Split a string on backquote pairs 
 *
 * @param   string  original string 
 * @return  array   containing the elements (and their surrounding backquotes)
 *
 * @access  public
 */
function PMA_backquote_split($text)
{
    $elements = array();
    $final_pos = strlen($text) - 1;
    $pos = 0;
    while ($pos <= $final_pos) {
        $first_backquote = strpos($text, '`', $pos);
        $second_backquote = strpos($text, '`', $first_backquote + 1);
        // after the second one, there might be another one which means
        // this is an escaped backquote
        if ($second_backquote < $final_pos && '`' == $text[$second_backquote + 1]) {
            $second_backquote = strpos($text, '`', $second_backquote + 2);
        }
        if (false === $first_backquote || false === $second_backquote) {
            break;
        }
        $elements[] = substr($text, $first_backquote, $second_backquote - $first_backquote + 1);
        $pos = $second_backquote + 1;
    } 
    return($elements);
}

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();


/**
 * Updates
 */
if ($cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (PMA_foreignkey_supported($tbl_type)) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}
if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}

// will be used in the logic for internal relations and foreign keys:
$me_fields_name =
    isset($_REQUEST['fields_name'])
    ? $_REQUEST['fields_name']
    : null;

// u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
if (isset($destination) && $cfgRelation['relwork']) {

    foreach ($destination as $master_field_md5 => $foreign_string) {
        $upd_query = false;

        // Map the fieldname's md5 back to its real name
        $master_field = $me_fields_name[$master_field_md5];

        if (! empty($foreign_string)) {
            $foreign_string = trim($foreign_string, '`');
            list($foreign_db, $foreign_table, $foreign_field) =
                explode('.', $foreign_string);
            if (! isset($existrel[$master_field])) {
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
        if ($upd_query) {
            PMA_query_as_controluser($upd_query);
        }
    } // end while
} // end if (updates for internal relations)

// u p d a t e s    f o r    f o r e i g n    k e y s
// (for now, one index name only; we keep the definitions if the
// foreign db is not the same)
// I use $sql_query to be able to display directly the query via
// PMA_showMessage()

if (isset($_REQUEST['destination_foreign'])) {
    $display_query = '';
    $seen_error = false;
    foreach ($_REQUEST['destination_foreign'] as $master_field_md5 => $foreign_string) {

        // Map the fieldname's md5 back to it's real name
        $master_field = $me_fields_name[$master_field_md5];

        if (! empty($foreign_string)) {
            list($foreign_db, $foreign_table, $foreign_field) = PMA_backquote_split($foreign_string);
            if (!isset($existrel_foreign[$master_field])) {
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
                            . $foreign_db . '.'
                            . $foreign_table . '('
                            . $foreign_field . ')';

                if (! empty($_REQUEST['on_delete'][$master_field_md5])) {
                    $sql_query .= ' ON DELETE ' . $options_array[$_REQUEST['on_delete'][$master_field_md5]];
                }
                if (! empty($_REQUEST['on_update'][$master_field_md5])) {
                    $sql_query .= ' ON UPDATE ' . $options_array[$_REQUEST['on_update'][$master_field_md5]];
                }
                $sql_query .= ';';
                $display_query .= $sql_query . "\n";
                // end repeated code

            } elseif (PMA_backquote($existrel_foreign[$master_field]['foreign_db']) != $foreign_db
                || PMA_backquote($existrel_foreign[$master_field]['foreign_table']) != $foreign_table
                || PMA_backquote($existrel_foreign[$master_field]['foreign_field']) != $foreign_field
                || ($_REQUEST['on_delete'][$master_field_md5] != (!empty($existrel_foreign[$master_field]['on_delete']) ? $existrel_foreign[$master_field]['on_delete'] : ''))
                || ($_REQUEST['on_update'][$master_field_md5] != (!empty($existrel_foreign[$master_field]['on_update']) ? $existrel_foreign[$master_field]['on_update'] : ''))
                   ) {
                // another foreign key is already defined for this field
                // or
                // an option has been changed for ON DELETE or ON UPDATE

                // remove existing key
                $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                            . ' DROP FOREIGN KEY '
                            . PMA_backquote($existrel_foreign[$master_field]['constraint']) . ';';

                // I tried to send both in one query but it failed
                PMA_DBI_query($sql_query);
                $display_query .= $sql_query . "\n";

                // add another
                $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                            . ' ADD FOREIGN KEY ('
                            . PMA_backquote($master_field) . ')'
                            . ' REFERENCES '
                            . $foreign_db . '.'
                            . $foreign_table . '('
                            . $foreign_field . ')';

                if (! empty($_REQUEST['on_delete'][$master_field_md5])) {
                    $sql_query   .= ' ON DELETE '
                        . $options_array[$_REQUEST['on_delete'][$master_field_md5]];
                }
                if (! empty($_REQUEST['on_update'][$master_field_md5])) {
                    $sql_query   .= ' ON UPDATE '
                        . $options_array[$_REQUEST['on_update'][$master_field_md5]];
                }
                $sql_query .= ';';
                $display_query .= $sql_query . "\n";

            } // end if... else....
        } elseif (isset($existrel_foreign[$master_field])) {
            $sql_query  = 'ALTER TABLE ' . PMA_backquote($table)
                    . ' DROP FOREIGN KEY '
                    . PMA_backquote($existrel_foreign[$master_field]['constraint']);
            $sql_query .= ';';
            $display_query .= $sql_query . "\n";
        } // end if... else....

        if (! empty($sql_query)) {
            PMA_DBI_try_query($sql_query);
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
                $message = PMA_Message::warning('strForeignKeyError');
                $message->addParam($master_field);
                $message->display();
                echo PMA_showMySQLDocu('manual_Table_types', 'InnoDB_foreign_key_constraints') . "\n";
            }
            unset($tmp_error);
            $sql_query = '';
        }
    } // end foreach
    if (!empty($display_query)) {
        if ($seen_error) {
            PMA_showMessage($strError, null, 'error');
        } else {
            PMA_showMessage($strSuccess, null, 'success');
        }
    }
} // end if isset($destination_foreign)


// U p d a t e s   f o r   d i s p l a y   f i e l d

if ($cfgRelation['displaywork'] && isset($display_field)) {
    $upd_query = false;
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

    if ($upd_query) {
        PMA_query_as_controluser($upd_query);
    }
} // end if

// If we did an update, refresh our data
if (isset($destination) && $cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (isset($destination_foreign) && PMA_foreignkey_supported($tbl_type)) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
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

if ($cfgRelation['relwork'] || PMA_foreignkey_supported($tbl_type)) {
    // To choose relations we first need all tables names in current db
    // and if the main table supports foreign keys
    // we use SHOW TABLE STATUS because we need to find other tables of the
    // same engine.

    if (PMA_foreignkey_supported($tbl_type)) {
        $tab_query           = 'SHOW TABLE STATUS FROM ' . PMA_backquote($db);
        // [0] of the row is the name
        // [1] is the type
    } else {
        $tab_query           = 'SHOW TABLES FROM ' . PMA_backquote($db);
        // [0] of the row is the name
    }

    $tab_rs              = PMA_DBI_query($tab_query, null, PMA_DBI_QUERY_STORE);
    $selectboxall[] = '';
    $selectboxall_foreign[] = '';

    while ($curr_table = PMA_DBI_fetch_row($tab_rs)) {
        $current_table = new PMA_Table($curr_table[0], $db);

        // explicitely ask for non-quoted list of indexed columns
        $selectboxall = array_merge($selectboxall, $current_table->getUniqueColumns($backquoted = false));

        // if foreign keys are supported, collect all keys from other
        // tables of the same engine
        if (PMA_foreignkey_supported($tbl_type)
         && isset($curr_table[1])
         && strtoupper($curr_table[1]) == $tbl_type) {
             // explicitely ask for non-quoted list of indexed columns
             // need to obtain backquoted values to support dots inside values 
             $selectboxall_foreign = array_merge($selectboxall_foreign, $current_table->getIndexedColumns($backquoted = true));
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
        if (PMA_foreignkey_supported($tbl_type)) {
            echo PMA_showHint($strInternalAndForeign);
        }
        echo '</th>';
    }
    if (PMA_foreignkey_supported($tbl_type)) {
        // this does not have to be translated, it's part of the MySQL syntax
        echo '<th colspan="2">FOREIGN KEY (' . $tbl_type . ')';
        echo '</th>';
    }
    ?>
    </tr>
    <?php
    $odd_row = true;
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $myfield = $save_row[$i]['Field'];
        // Use an md5 as array index to avoid having special characters in the name atttibure (see bug #1746964 )
        $myfield_md5 = md5($myfield);
        $myfield_html = htmlspecialchars($myfield);
        ?>
    <tr class="<?php echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row; ?>">
        <td align="center">
            <strong><?php echo $myfield_html; ?></strong>
            <input type="hidden" name="fields_name[<?php echo $myfield_md5; ?>]" value="<?php echo $myfield_html; ?>"/>
        </td>
        <?php
        if ($cfgRelation['relwork']) {
            ?>
        <td><select name="destination[<?php echo $myfield_md5; ?>]">
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
            foreach ($selectboxall as $value) {
                echo '                '
                     . '<option value="' . htmlspecialchars($value) . '"';
                if ($foreign_field && $value == $foreign_field) {
                    echo ' selected="selected"';
                    $seen_key = TRUE;
                }
                echo '>' . htmlspecialchars($value) . '</option>'. "\n";
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

        if (PMA_foreignkey_supported($tbl_type)) {
            echo '<td>';
            if (!empty($save_row[$i]['Key'])) {
                ?>
            <span class="formelement">
            <select name="destination_foreign[<?php echo $myfield_md5; ?>]">
                <?php
                if (isset($existrel_foreign[$myfield])) {
                    // need to backquote to support a dot character inside
                    // an element
                    $foreign_field    = PMA_backquote($existrel_foreign[$myfield]['foreign_db']) . '.'
                             . PMA_backquote($existrel_foreign[$myfield]['foreign_table']) . '.'
                             . PMA_backquote($existrel_foreign[$myfield]['foreign_field']);
                } else {
                    $foreign_field    = FALSE;
                }

                $found_foreign_field = FALSE;
                foreach ($selectboxall_foreign as $value) {
                    echo '                '
                         . '<option value="' . htmlspecialchars($value) . '"';
                    if ($foreign_field && $value == $foreign_field) {
                        echo ' selected="selected"';
                        $found_foreign_field = TRUE;
                    }
                    echo '>' . htmlspecialchars($value) . '</option>'. "\n";
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
                    'on_delete[' . $myfield_md5 . ']',
                    $options_array,
                    isset($existrel_foreign[$myfield]['on_delete']) ? $existrel_foreign[$myfield]['on_delete']: '');

                echo '</span>' . "\n"
                    .'<span class="formelement">' . "\n";

                PMA_generate_dropdown('ON UPDATE',
                    'on_update[' . $myfield_md5 . ']',
                    $options_array,
                    isset($existrel_foreign[$myfield]['on_update']) ? $existrel_foreign[$myfield]['on_update']: '');
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

    unset( $myfield, $myfield_md5, $myfield_html);

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

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
