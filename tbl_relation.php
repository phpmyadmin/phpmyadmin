<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display table relations for viewing and editing
 *
 * includes phpMyAdmin relations and InnoDB relations
 *
 * @todo fix name handling: currently names with dots (.) are not properly handled
 * for internal relations (but foreign keys relations are correct)
 * @todo foreign key constraints require both fields being of equal type and size
 * @todo check foreign fields to be from same type and size, all other makes no sense
 * @todo add an link to create an index required for constraints,
 * or an option to do automatically
 * @todo if above todos are fullfilled we can add all fields meet requirements
 * in the select dropdown
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/index.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_relation.js');
$scripts->addFile('indexes.js');

/**
 * Sets globals from $_POST
 */
$post_params = array(
    'destination',
    'destination_foreign',
    'display_field',
    'fields_name',
    'on_delete',
    'on_update'
);

foreach ($post_params as $one_post_param) {
    if (isset($_POST[$one_post_param])) {
        $GLOBALS[$one_post_param] = $_POST[$one_post_param];
    }
}

/**
 * Gets tables informations
 */
require_once 'libraries/tbl_info.inc.php';

$options_array = array(
    'CASCADE'   => 'CASCADE',
    'SET_NULL'  => 'SET NULL',
    'NO_ACTION' => 'NO ACTION',
    'RESTRICT'  => 'RESTRICT',
);

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
if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}
if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}

// will be used in the logic for internal relations and foreign keys:
$multi_edit_columns_name = isset($_REQUEST['fields_name'])
    ? $_REQUEST['fields_name']
    : null;

$html_output = '';

// u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
if (isset($destination) && $cfgRelation['relwork']) {

    foreach ($destination as $master_field_md5 => $foreign_string) {
        $upd_query = false;

        // Map the fieldname's md5 back to its real name
        $master_field = $multi_edit_columns_name[$master_field_md5];

        if (! empty($foreign_string)) {
            $foreign_string = trim($foreign_string, '`');
            list($foreign_db, $foreign_table, $foreign_field)
                = explode('.', $foreign_string);
            if (! isset($existrel[$master_field])) {
                $upd_query  = 'INSERT INTO '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote($cfgRelation['relation'])
                    . '(master_db, master_table, master_field, foreign_db,'
                    . ' foreign_table, foreign_field)'
                    . ' values('
                    . '\'' . PMA_Util::sqlAddSlashes($db) . '\', '
                    . '\'' . PMA_Util::sqlAddSlashes($table) . '\', '
                    . '\'' . PMA_Util::sqlAddSlashes($master_field) . '\', '
                    . '\'' . PMA_Util::sqlAddSlashes($foreign_db) . '\', '
                    . '\'' . PMA_Util::sqlAddSlashes($foreign_table) . '\','
                    . '\'' . PMA_Util::sqlAddSlashes($foreign_field) . '\')';
            } elseif ($existrel[$master_field]['foreign_db'] . '.' .$existrel[$master_field]['foreign_table'] . '.' . $existrel[$master_field]['foreign_field'] != $foreign_string) {
                $upd_query  = 'UPDATE '
                    . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                    . '.' . PMA_Util::backquote($cfgRelation['relation']) . ' SET'
                    . ' foreign_db       = \''
                    . PMA_Util::sqlAddSlashes($foreign_db) . '\', '
                    . ' foreign_table    = \''
                    . PMA_Util::sqlAddSlashes($foreign_table) . '\', '
                    . ' foreign_field    = \''
                    . PMA_Util::sqlAddSlashes($foreign_field) . '\' '
                    . ' WHERE master_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                    . ' AND master_table = \''
                    . PMA_Util::sqlAddSlashes($table) . '\''
                    . ' AND master_field = \''
                    . PMA_Util::sqlAddSlashes($master_field) . '\'';
            } // end if... else....
        } elseif (isset($existrel[$master_field])) {
            $upd_query = 'DELETE FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['relation'])
                . ' WHERE master_db  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND master_table = \'' . PMA_Util::sqlAddSlashes($table) . '\''
                . ' AND master_field = \'' . PMA_Util::sqlAddSlashes($master_field)
                . '\'';
        } // end if... else....
        if ($upd_query) {
            PMA_queryAsControlUser($upd_query);
        }
    } // end while
} // end if (updates for internal relations)

// u p d a t e s    f o r    f o r e i g n    k e y s
// (for now, one index name only; we keep the definitions if the
// foreign db is not the same)

if (isset($_REQUEST['destination_foreign'])) {
    $display_query = '';
    $seen_error = false;
    foreach ($_REQUEST['destination_foreign'] as $master_field_md5 => $foreign_string) {
        $create = false;
        $drop = false;

        // Map the fieldname's md5 back to it's real name
        $master_field = $multi_edit_columns_name[$master_field_md5];

        if (! empty($foreign_string)) {
            list($foreign_db, $foreign_table, $foreign_field)
                = PMA_backquoteSplit($foreign_string);
            if (! isset($existrel_foreign[$master_field])) {
                // no key defined for this field
                $create = true;
            } elseif (PMA_Util::backquote($existrel_foreign[$master_field]['foreign_db']) != $foreign_db
                || PMA_Util::backquote($existrel_foreign[$master_field]['foreign_table']) != $foreign_table
                || PMA_Util::backquote($existrel_foreign[$master_field]['foreign_field']) != $foreign_field
                || $_REQUEST['constraint_name'][$master_field_md5] != $existrel_foreign[$master_field]['constraint']
                || ($_REQUEST['on_delete'][$master_field_md5] != (! empty($existrel_foreign[$master_field]['on_delete']) ? $existrel_foreign[$master_field]['on_delete'] : 'RESTRICT'))
                || ($_REQUEST['on_update'][$master_field_md5] != (! empty($existrel_foreign[$master_field]['on_update']) ? $existrel_foreign[$master_field]['on_update'] : 'RESTRICT'))
            ) {
                // another foreign key is already defined for this field
                // or an option has been changed for ON DELETE or ON UPDATE
                $drop = true;
                $create = true;
            } // end if... else....
        } elseif (isset($existrel_foreign[$master_field])) {
            $drop = true;
        } // end if... else....

        $tmp_error_drop = false;
        if ($drop) {
            $drop_query = PMA_getSQLToDropForeignKey(
                $table, $existrel_foreign[$master_field]['constraint']
            );
            $display_query .= $drop_query . "\n";
            PMA_DBI_try_query($drop_query);
            $tmp_error_drop = PMA_DBI_getError();

            if (! empty($tmp_error_drop)) {
                $seen_error = true;
                $html_output .= PMA_Util::mysqlDie(
                    $tmp_error_drop, $drop_query, false, '', false
                );
                continue;
            }
        }
        $tmp_error_create = false;
        if ($create) {
            $create_query = PMA_getSQLToCreateForeignKey(
                $table, $master_field, $foreign_db, $foreign_table, $foreign_field,
                $_REQUEST['constraint_name'][$master_field_md5],
                $options_array[$_REQUEST['on_delete'][$master_field_md5]],
                $options_array[$_REQUEST['on_update'][$master_field_md5]]
            );

            $display_query .= $create_query . "\n";
            PMA_DBI_try_query($create_query);
            $tmp_error_create = PMA_DBI_getError();
            if (! empty($tmp_error_create)) {
                $seen_error = true;

                if (substr($tmp_error_create, 1, 4) == '1005') {
                    $message = PMA_Message::error(
                        __('Error creating foreign key on %1$s (check data types)')
                    );
                    $message->addParam($master_field);
                    $message->display();
                } else {
                    $html_output .= PMA_Util::mysqlDie(
                        $tmp_error_create, $create_query, false, '', false
                    );
                }
                $html_output .= PMA_Util::showMySQLDocu(
                    'manual_Table_types', 'InnoDB_foreign_key_constraints'
                ) . "\n";
            }

            // this is an alteration and the old constraint has been dropped
            // without creation of a new one
            if ($drop && $create && empty($tmp_error_drop)
                && ! empty($tmp_error_create)
            ) {
                // a rollback may be better here
                $sql_query_recreate = '# Restoring the dropped constraint...' . "\n";
                $sql_query_recreate .= PMA_getSQLToCreateForeignKey(
                    $table, $master_field,
                    PMA_Util::backquote($existrel_foreign[$master_field]['foreign_db']),
                    PMA_Util::backquote($existrel_foreign[$master_field]['foreign_table']),
                    PMA_Util::backquote($existrel_foreign[$master_field]['foreign_field']),
                    $existrel_foreign[$master_field]['constraint'],
                    $options_array[$existrel_foreign[$master_field]['on_delete']],
                    $options_array[$existrel_foreign[$master_field]['on_update']]
                );
                $display_query .= $sql_query_recreate . "\n";
                PMA_DBI_try_query($sql_query_recreate);
            }
        }
    } // end foreach
    if (! empty($display_query) && ! $seen_error) {
        $html_output .= PMA_Util::getMessage(
            __('Your SQL query has been executed successfully'),
            null, 'success'
        );
    }
} // end if isset($destination_foreign)


// U p d a t e s   f o r   d i s p l a y   f i e l d

if ($cfgRelation['displaywork'] && isset($display_field)) {
    $upd_query = false;
    if ($disp) {
        if ($display_field != '') {
            $upd_query = 'UPDATE '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                . ' SET display_field = \''
                . PMA_Util::sqlAddSlashes($display_field) . '\''
                . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        } else {
            $upd_query = 'DELETE FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        }
    } elseif ($display_field != '') {
        $upd_query = 'INSERT INTO '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.' . PMA_Util::backquote($cfgRelation['table_info'])
            . '(db_name, table_name, display_field) VALUES('
            . '\'' . PMA_Util::sqlAddSlashes($db) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($table) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($display_field) . '\')';
    }

    if ($upd_query) {
        PMA_queryAsControlUser($upd_query);
    }
} // end if

// If we did an update, refresh our data
if (isset($destination) && $cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (isset($destination_foreign)
    && PMA_Util::isForeignKeySupported($tbl_storage_engine)
) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}

if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}


/**
 * Dialog
 */

// common form
$html_output .= '<form method="post" action="tbl_relation.php">' . "\n"
    . PMA_generate_common_hidden_inputs($db, $table);


// relations

if ($cfgRelation['relwork']
    || PMA_Util::isForeignKeySupported($tbl_storage_engine)
) {
    // To choose relations we first need all tables names in current db
    // and if the main table supports foreign keys
    // we use SHOW TABLE STATUS because we need to find other tables of the
    // same engine.

    if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
        $tab_query = 'SHOW TABLE STATUS FROM ' . PMA_Util::backquote($db);
        // [0] of the row is the name
        // [1] is the type
    } else {
        $tab_query = 'SHOW TABLES FROM ' . PMA_Util::backquote($db);
        // [0] of the row is the name
    }

    $tab_rs = PMA_DBI_query($tab_query, null, PMA_DBI_QUERY_STORE);
    $selectboxall[] = '';
    $selectboxall_foreign[] = '';

    while ($curr_table = PMA_DBI_fetch_row($tab_rs)) {
        $current_table = new PMA_Table($curr_table[0], $db);

        // explicitely ask for non-quoted list of indexed columns
        $selectboxall = array_merge(
            $selectboxall,
            $current_table->getUniqueColumns($backquoted = false)
        );

        // if foreign keys are supported, collect all keys from other
        // tables of the same engine
        if (PMA_Util::isForeignKeySupported($tbl_storage_engine)
            && isset($curr_table[1])
            && strtoupper($curr_table[1]) == $tbl_storage_engine
        ) {
             // explicitely ask for non-quoted list of indexed columns
             // need to obtain backquoted values to support dots inside values
             $selectboxall_foreign = array_merge(
                 $selectboxall_foreign,
                 $current_table->getIndexedColumns($backquoted = true)
             );
        }
    } // end while over tables
} // end if

// Now find out the columns of our $table
// need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
$columns = PMA_DBI_get_columns($db, $table);

if (count($columns) > 0) {

    foreach ($columns as $row) {
        $save_row[] = $row;
    }

    $saved_row_cnt  = count($save_row);
    $html_output .= '<fieldset>'
        . '<legend>' . __('Relations'). '</legend>'
        . '<table>'
        . '<tr><th>' . __('Column') . '</th>';

    if ($cfgRelation['relwork']) {
        $html_output .= '<th>' . __('Internal relation');
        if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
            $html_output .= PMA_Util::showHint(
                __(
                    'An internal relation is not necessary when a corresponding'
                    . ' FOREIGN KEY relation exists.'
                )
            );
        }
        $html_output .= '</th>';
    }

    if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
        // this does not have to be translated, it's part of the MySQL syntax
        $html_output .= '<th colspan="2">' . __('Foreign key constraint')
            . ' (' . $tbl_storage_engine . ')';
        $html_output .= '</th>';
    }
    $html_output .= '</tr>';

    $odd_row = true;
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $myfield = $save_row[$i]['Field'];
        // Use an md5 as array index to avoid having special characters
        // in the name atttibure (see bug #1746964 )
        $myfield_md5 = md5($myfield);
        $myfield_html = htmlspecialchars($myfield);

        $html_output .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
            . '<td class="center">'
            . '<strong>' . $myfield_html . '</strong>'
            . '<input type="hidden" name="fields_name[' . $myfield_md5 . ']"'
            . ' value="' . $myfield_html . '"/>'
            . '</td>';
        $odd_row = ! $odd_row;

        if ($cfgRelation['relwork']) {
            $html_output .= '<td><select name="destination[' . $myfield_md5 . ']">';
            // PMA internal relations
            if (isset($existrel[$myfield])) {
                $foreign_field    = $existrel[$myfield]['foreign_db'] . '.'
                         . $existrel[$myfield]['foreign_table'] . '.'
                         . $existrel[$myfield]['foreign_field'];
            } else {
                $foreign_field    = false;
            }
            $seen_key = false;
            foreach ($selectboxall as $value) {
                $html_output .= '<option value="' . htmlspecialchars($value) . '"';
                if ($foreign_field && $value == $foreign_field) {
                    $html_output .= ' selected="selected"';
                    $seen_key = true;
                }
                $html_output .= '>' . htmlspecialchars($value) . '</option>'. "\n";
            } // end while

            // if the link defined in relationtable points to a foreign field
            // that is not a key in the foreign table, we show the link
            // (will not be shown with an arrow)
            if ($foreign_field && !$seen_key) {
                $html_output .= '<option value="' . htmlspecialchars($foreign_field)
                    . '"'
                    . ' selected="selected">' . $foreign_field . '</option>'. "\n";
            }
            $html_output .= '</select>'
                . '</td>';
        } // end if (internal relations)

        if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
            $html_output .= '<td>';
            if (!empty($save_row[$i]['Key'])) {
                $html_output .= '<span class="formelement">'
                    . '<select name="destination_foreign[' . $myfield_md5 . ']"'
                    . ' class="referenced_column_dropdown">';
                if (isset($existrel_foreign[$myfield])) {
                    // need to PMA_Util::backquote to support a dot character inside
                    // an element
                    $foreign_field = PMA_Util::backquote(
                        $existrel_foreign[$myfield]['foreign_db']
                    )
                    . '.' . PMA_Util::backquote(
                        $existrel_foreign[$myfield]['foreign_table']
                    )
                    . '.' . PMA_Util::backquote(
                        $existrel_foreign[$myfield]['foreign_field']
                    );
                } else {
                    $foreign_field    = false;
                }

                $found_foreign_field = false;
                foreach ($selectboxall_foreign as $value) {
                    $html_output .= '<option value="'
                        . htmlspecialchars($value) . '"';
                    if ($foreign_field && $value == $foreign_field) {
                        $html_output .= ' selected="selected"';
                        $found_foreign_field = true;
                    }
                    $html_output .= '>' . htmlspecialchars($value)
                        . '</option>'. "\n";
                } // end while

                // we did not find the foreign field in the tables of current db,
                // must be defined in another db so show it to avoid erasing it
                if (!$found_foreign_field && $foreign_field) {
                    $html_output .= '<option value="'
                        . htmlspecialchars($foreign_field) . '"'
                        . ' selected="selected"'
                        . '>' . $foreign_field . '</option>' . "\n";
                }
                $html_output .= '</select>'
                    . '</span>';

                // For constraint name
                $html_output .= '<span class="formelement">';
                $constraint_name = isset($existrel_foreign[$myfield]['constraint'])
                    ? $existrel_foreign[$myfield]['constraint'] : '';
                $html_output .= __('Constraint name');
                $html_output .= '<input type="text" name="constraint_name['
                    . $myfield_md5 . ']"'
                    . ' value="' . htmlspecialchars($constraint_name) . '"/>';
                $html_output .= '</span>' . "\n";

                $html_output .= '<span class="formelement">';
                // For ON DELETE and ON UPDATE, the default action
                // is RESTRICT as per MySQL doc; however, a SHOW CREATE TABLE
                // won't display the clause if it's set as RESTRICT.
                $on_delete = isset($existrel_foreign[$myfield]['on_delete'])
                    ? $existrel_foreign[$myfield]['on_delete'] : 'RESTRICT';
                $html_output .= PMA_generateDropdown(
                    'ON DELETE',
                    'on_delete[' . $myfield_md5 . ']',
                    $options_array,
                    $on_delete
                );
                $html_output .= '</span>' . "\n";

                $html_output .= '<span class="formelement">' . "\n";
                $on_update = isset($existrel_foreign[$myfield]['on_update'])
                    ? $existrel_foreign[$myfield]['on_update'] : 'RESTRICT';
                $html_output .= PMA_generateDropdown(
                    'ON UPDATE',
                    'on_update[' . $myfield_md5 . ']',
                    $options_array,
                    $on_update
                );
                $html_output .= '</span>' . "\n";
            } else {
                $html_output .= __('No index defined! Create one below');
            } // end if (a key exists)
            $html_output .= '</td>';
        } // end if (InnoDB)
        $html_output .= '</tr>';
    } // end for

    unset( $myfield, $myfield_md5, $myfield_html);
    $html_output .= '</table>' . "\n"
        . '</fieldset>' . "\n";

    if ($cfgRelation['displaywork']) {
        // Get "display_field" infos
        $disp = PMA_getDisplayField($db, $table);
        $html_output .= '<fieldset>'
            . '<label>' . __('Choose column to display') . ': </label>'
            . '<select name="display_field">'
            . '<option value="">---</option>';

        foreach ($save_row AS $row) {
            $html_output .= '<option value="'
                . htmlspecialchars($row['Field']) . '"';
            if (isset($disp) && $row['Field'] == $disp) {
                $html_output .= ' selected="selected"';
            }
            $html_output .= '>' . htmlspecialchars($row['Field'])
                . '</option>'. "\n";
        } // end while

        $html_output .= '</select>'
            . '</fieldset>';
    } // end if (displayworks)

    $html_output .= '<fieldset class="tblFooters">'
        . '<input type="submit" value="' . __('Save') . '" />'
        . '</fieldset>'
        . '</form>';
} // end if (we have columns in this table)

if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $html_output .= '<div id="index_div" class="ajax" >'
        . PMA_getHtmlForDisplayIndexes();
}
// Render HTML output
PMA_Response::getInstance()->addHTML($html_output);

/**
 * Generate dropdown choices
 *
 * @param string $dropdown_question Message to display
 * @param string $select_name       Name of the <select> field
 * @param array  $choices           Choices for dropdown
 * @param string $selected_value    Selected value
 *
 * @return string The html code for existing value (for selected)
 *
 * @access public
 */
function PMA_generateDropdown(
    $dropdown_question, $select_name, $choices, $selected_value
) {
    $html_output = htmlspecialchars($dropdown_question) . '&nbsp;&nbsp;'
        . '<select name="' . htmlspecialchars($select_name) . '">' . "\n";

    foreach ($choices as $one_value => $one_label) {
        $html_output .= '<option value="' . htmlspecialchars($one_value) . '"';
        if ($selected_value == $one_value) {
            $html_output .= ' selected="selected" ';
        }
        $html_output .= '>' . htmlspecialchars($one_label) . '</option>' . "\n";
    }
    $html_output .= '</select>' . "\n";

    return $html_output;
}

/**
 * Split a string on backquote pairs
 *
 * @param string $text original string
 *
 * @return array containing the elements (and their surrounding backquotes)
 *
 * @access public
 */
function PMA_backquoteSplit($text)
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
        $elements[] = substr(
            $text, $first_backquote, $second_backquote - $first_backquote + 1
        );
        $pos = $second_backquote + 1;
    }
    return($elements);
}

/**
 * Returns the DROP query for a foreign key constraint
 *
 * @param string $table table of the foreign key
 * @param string $fk    foreign key name
 *
 * @return string DROP query for the foreign key constraint
 */
function PMA_getSQLToDropForeignKey($table, $fk)
{
    return 'ALTER TABLE ' . PMA_Util::backquote($table)
        . ' DROP FOREIGN KEY ' . PMA_Util::backquote($fk) . ';';
}

/**
 * Returns the SQL query for foreign key constraint creation
 *
 * @param string $table        table name
 * @param string $field        field name
 * @param string $foreignDb    back-quoted foreign database name
 * @param string $foreignTable back-quoted foreign table name
 * @param string $foreignField back-quoted foreign field name
 * @param string $name         name of the constraint
 * @param string $onDelete     on delete action
 * @param string $onUpdate     on update action
 *
 * @return string SQL query for foreign key constraint creation
 */
function PMA_getSQLToCreateForeignKey($table, $field, $foreignDb, $foreignTable,
    $foreignField, $name = null, $onDelete = null, $onUpdate = null
) {
    $sql_query  = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD ';
    // if user entered a constraint name
    if (! empty($name)) {
        $sql_query .= ' CONSTRAINT ' . PMA_Util::backquote($name);
    }

    $sql_query .= ' FOREIGN KEY (' . PMA_Util::backquote($field) . ')'
        . ' REFERENCES ' . $foreignDb . '.' . $foreignTable
        . '(' . $foreignField . ')';

    if (! empty($onDelete)) {
        $sql_query .= ' ON DELETE ' . $onDelete;
    }
    if (! empty($onUpdate)) {
        $sql_query .= ' ON UPDATE ' . $onUpdate;
    }
    $sql_query .= ';';

    return $sql_query;
}
?>
