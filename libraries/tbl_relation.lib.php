<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the table relation page
 *
 * @package PhpMyAdmin
 */

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
 * @param string $foreignDb    foreign database name
 * @param string $foreignTable foreign table name
 * @param string $foreignField foreign field name
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
        . ' REFERENCES ' . PMA_Util::backquote($foreignDb)
        . '.' . PMA_Util::backquote($foreignTable)
        . '(' . PMA_Util::backquote($foreignField) . ')';

    if (! empty($onDelete)) {
        $sql_query .= ' ON DELETE ' . $onDelete;
    }
    if (! empty($onUpdate)) {
        $sql_query .= ' ON UPDATE ' . $onUpdate;
    }
    $sql_query .= ';';

    return $sql_query;
}

/**
 * Creates and populates dropdowns to select foreign db/table/column
 *
 * @param string         $name    name of the dropdowns
 * @param array          $values  dropdown values
 * @param string|boolean $foreign value of the item to be selected
 * @param string         $title   title to show on hovering the dropdown
 *
 * @return string HTML for the dropdown
 */
function PMA_generateRelationalDropdown(
    $name, $values = array(), $foreign = false, $title = ''
) {
    $html_output = '<select name="' . $name . '" title="' . $title . '">';
    $html_output .= '<option value=""></option>';

    $seen_key = false;
    foreach ($values as $value) {
        $html_output .= '<option value="' . htmlspecialchars($value) . '"';
        if ($foreign && $value == $foreign) {
            $html_output .= ' selected="selected"';
            $seen_key = true;
        }
        $html_output .= '>' . htmlspecialchars($value) . '</option>';
    }

    if ($foreign && ! $seen_key) {
        $html_output .= '<option value="' . htmlspecialchars($foreign) . '"'
            . ' selected="selected">' . htmlspecialchars($foreign) . '</option>';
    }
    $html_output .= '</select>';
    return $html_output;
}

/**
 * Function to get html for the common form
 *
 * @param string $db                 current database
 * @param string $table              current table
 * @param array  $columns            columns
 * @param array  $cfgRelation        configuration relation
 * @param string $tbl_storage_engine table storage engine
 * @param array  $existrel           db, table, column
 * @param array  $existrel_foreign   db, table, column
 * @param array  $options_array      options array
 *
 * @return string
 */
function PMA_getHtmlForCommonForm($db, $table, $columns, $cfgRelation,
    $tbl_storage_engine, $existrel, $existrel_foreign, $options_array
) {
    $html_output = PMA_getHtmlForCommonFormHeader($db, $table);

    if (count($columns) > 0) {
        $html_output .= PMA_getHtmlForCommonFormRows(
            $columns, $cfgRelation, $tbl_storage_engine,
            $existrel, $existrel_foreign, $options_array, $db, $table
        );
    } // end if (we have columns in this table)

    $html_output .= PMA_getHtmlForCommonFormFooter();

    return $html_output;
}

/**
 * Function to get html for the common form rows
 *
 * @param array  $columns            columns
 * @param array  $cfgRelation        configuration relation
 * @param string $tbl_storage_engine table storage engine
 * @param array  $existrel           existed relations
 * @param array  $existrel_foreign   existed relations for foreign keys
 * @param array  $options_array      options array
 * @param string $db                 current database
 * @param string $table              current table
 *
 * @return string
 */
function PMA_getHtmlForCommonFormRows($columns, $cfgRelation, $tbl_storage_engine,
    $existrel, $existrel_foreign, $options_array, $db, $table
) {
    $save_row = array();
    foreach ($columns as $row) {
        $save_row[] = $row;
    }

    $saved_row_cnt  = count($save_row);

    $html_output = '<fieldset>'
        . '<legend>' . __('Relations') . '</legend>'
        . '<table id="relationalTable">';

    $html_output .= PMA_getHtmlForCommonFormTableHeaders(
        $cfgRelation, $tbl_storage_engine
    );

    $odd_row = true;
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $html_output .= PMA_getHtmlForRow(
            $save_row, $i, $odd_row, $cfgRelation, $existrel, $db,
            $tbl_storage_engine, $existrel_foreign, $options_array
        );
        $odd_row = ! $odd_row;
    } // end for

    $html_output .= '</table>' . "\n"
        . '</fieldset>' . "\n";

    if ($cfgRelation['displaywork']) {
        $html_output .= PMA_getHtmlForDisplayFieldInfos($db, $table, $save_row);
    }

    return $html_output;
}

/**
 * Function to get html for an entire row in common form
 *
 * @param array  $save_row           save row
 * @param int    $i                  counter
 * @param bool   $odd_row            whether odd row or not
 * @param array  $cfgRelation        configuration relation
 * @param array  $existrel           db, table, column
 * @param string $db                 current db
 * @param string $tbl_storage_engine table storage engine
 * @param array  $existrel_foreign   db, table, column
 * @param array  $options_array      options array
 *
 * @return string
 */
function PMA_getHtmlForRow($save_row, $i, $odd_row, $cfgRelation, $existrel, $db,
    $tbl_storage_engine, $existrel_foreign, $options_array
) {
    $myfield = $save_row[$i]['Field'];
    // Use an md5 as array index to avoid having special characters
    // in the name attribute (see bug #1746964 )
    $myfield_md5 = md5($myfield);
    $myfield_html = htmlspecialchars($myfield);

    $html_output = '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
        . '<td class="center">'
        . '<strong>' . $myfield_html . '</strong>'
        . '<input type="hidden" name="fields_name[' . $myfield_md5 . ']"'
        . ' value="' . $myfield_html . '"/>'
        . '</td>';

    if ($cfgRelation['relwork']) {
        $html_output .= '<td>';

        $foreign_db = false;
        $foreign_table = false;
        $foreign_column = false;

        // database dropdown
        if (isset($existrel[$myfield])) {
            $foreign_db = $existrel[$myfield]['foreign_db'];
        } else {
            $foreign_db = $db;
        }
        $html_output .= PMA_generateRelationalDropdown(
            'destination_db[' . $myfield_md5 . ']',
            $GLOBALS['pma']->databases,
            $foreign_db,
            __('Database')
        );
        // end of database dropdown

        // table dropdown
        $tables = array();
        if ($foreign_db) {
            if (isset($existrel[$myfield])) {
                $foreign_table = $existrel[$myfield]['foreign_table'];
            }
            $tables_rs = $GLOBALS['dbi']->query(
                'SHOW TABLES FROM ' . PMA_Util::backquote($foreign_db),
                null,
                PMA_DatabaseInterface::QUERY_STORE
            );
            while ($row = $GLOBALS['dbi']->fetchRow($tables_rs)) {
                $tables[] = $row[0];
            }
        }
        $html_output .= PMA_generateRelationalDropdown(
            'destination_table[' . $myfield_md5 . ']',
            $tables,
            $foreign_table,
            __('Table')
        );
        // end of table dropdown

        // column dropdown
        $columns = array();
        if ($foreign_db && $foreign_table) {
            if (isset($existrel[$myfield])) {
                $foreign_column = $existrel[$myfield]['foreign_field'];
            }
            $table_obj = new PMA_Table($foreign_table, $foreign_db);
            $columns = $table_obj->getUniqueColumns(false, false);
        }
        $html_output .= PMA_generateRelationalDropdown(
            'destination_column[' . $myfield_md5 . ']',
            $columns,
            $foreign_column,
            __('Column')
        );
        // end of column dropdown

        $html_output .= '</td>';
    } // end if (internal relations)

    if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
        $html_output .= PMA_getHtmlForForeignKey(
            $save_row, $i, $existrel_foreign, $myfield, $db,
            $myfield_md5, $tbl_storage_engine, $options_array
        );
    } // end if (InnoDB)
    $html_output .= '</tr>';

    return $html_output;
}

/**
 * Function to get html for the common form header
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return string
 */
function PMA_getHtmlForCommonFormHeader($db, $table)
{
    return '<form method="post" action="tbl_relation.php">' . "\n"
    . PMA_URL_getHiddenInputs($db, $table);
}

/**
 * Function to get html for the common form footer
 *
 * @return string
 */
function PMA_getHtmlForCommonFormFooter()
{
    return '<fieldset class="tblFooters">'
        . '<input type="submit" value="' . __('Save') . '" />'
        . '</fieldset>'
        . '</form>';
}

/**
 * Function to get html for display field infos
 *
 * @param string $db       current database
 * @param string $table    current table
 * @param array  $save_row save row
 *
 * @return string
 */
function PMA_getHtmlForDisplayFieldInfos($db, $table, $save_row)
{
    $disp = PMA_getDisplayField($db, $table);
    $html_output = '<fieldset>'
        . '<label>' . __('Choose column to display:') . '</label>'
        . '<select name="display_field">'
        . '<option value="">---</option>';

    foreach ($save_row as $row) {
        $html_output .= '<option value="'
            . htmlspecialchars($row['Field']) . '"';
        if (isset($disp) && $row['Field'] == $disp) {
            $html_output .= ' selected="selected"';
        }
        $html_output .= '>' . htmlspecialchars($row['Field'])
            . '</option>' . "\n";
    } // end while

    $html_output .= '</select>'
        . '</fieldset>';

    return $html_output;
}

/**
 * Function to get html for the common form title headers
 *
 * @param array  $cfgRelation        configuration relation
 * @param string $tbl_storage_engine table storage engine
 *
 * @return string
 */
function PMA_getHtmlForCommonFormTableHeaders($cfgRelation, $tbl_storage_engine)
{
    $html_output = '<tr><th>' . __('Column') . '</th>';

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

    return $html_output;
}

/**
 * Function to get html for the foreign key
 *
 * @param array  $save_row           save row
 * @param int    $i                  counter
 * @param array  $existrel_foreign   db, table, columns
 * @param string $myfield            my field
 * @param string $db                 current database
 * @param string $myfield_md5        my field md5
 * @param string $tbl_storage_engine table storage engine
 * @param array  $options_array      options array
 *
 * @return string
 */
function PMA_getHtmlForForeignKey($save_row, $i, $existrel_foreign, $myfield, $db,
    $myfield_md5, $tbl_storage_engine, $options_array
) {
    $html_output = '<td>';
    if (! empty($save_row[$i]['Key'])) {

        $foreign_db = false;
        $foreign_table = false;
        $foreign_column = false;

        // foreign database dropdown
        if (isset($existrel_foreign[$myfield])) {
            $foreign_db = $existrel_foreign[$myfield]['foreign_db'];
        } else {
            $foreign_db = $db;
        }
        $html_output .= '<span class="formelement clearfloat">';
        $html_output .= PMA_generateRelationalDropdown(
            'destination_foreign_db[' . $myfield_md5 . ']',
            $GLOBALS['pma']->databases,
            $foreign_db,
            __('Database')
        );
        // end of foreign database dropdown

        // foreign table dropdown
        $tables = array();
        if ($foreign_db) {
            if (isset($existrel_foreign[$myfield])) {
                $foreign_table = $existrel_foreign[$myfield]['foreign_table'];
            }
            // In Drizzle, 'SHOW TABLE STATUS' will show status only for the tables
            // which are currently in the table cache. Hence we have to use
            // 'SHOW TABLES' and manully retrieve table engine values.
            if (PMA_DRIZZLE) {
                $tables_rs = $GLOBALS['dbi']->query(
                    'SHOW TABLES FROM ' . PMA_Util::backquote($foreign_db),
                    null,
                    PMA_DatabaseInterface::QUERY_STORE
                );
                while ($row = $GLOBALS['dbi']->fetchArray($tables_rs)) {
                    $engine = PMA_Table::sGetStatusInfo(
                        $foreign_db,
                        $row[0],
                        'Engine'
                    );
                    if (isset($engine)
                        && strtoupper($engine) == $tbl_storage_engine
                    ) {
                        $tables[] = $row[0];
                    }
                }
            } else {
                $tables_rs = $GLOBALS['dbi']->query(
                    'SHOW TABLE STATUS FROM ' . PMA_Util::backquote($foreign_db),
                    null,
                    PMA_DatabaseInterface::QUERY_STORE
                );
                while ($row = $GLOBALS['dbi']->fetchRow($tables_rs)) {
                    if (isset($row[1])
                        && strtoupper($row[1]) == $tbl_storage_engine
                    ) {
                        $tables[] = $row[0];
                    }
                }
            }
        }
        $html_output .= PMA_generateRelationalDropdown(
            'destination_foreign_table[' . $myfield_md5 . ']',
            $tables,
            $foreign_table,
            __('Table')
        );
        // end of foreign table dropdown

        // foreign column dropdown
        $columns = array();
        if ($foreign_db && $foreign_table) {
            if (isset($existrel_foreign[$myfield])) {
                $foreign_column = $existrel_foreign[$myfield]['foreign_field'];
            }
            $table_obj = new PMA_Table($foreign_table, $foreign_db);
            $columns = $table_obj->getUniqueColumns(false, false);
        }
        $html_output .= PMA_generateRelationalDropdown(
            'destination_foreign_column[' . $myfield_md5 . ']',
            $columns,
            $foreign_column,
            __('Column')
        );
        $html_output .= '</span>';
        // end of foreign column dropdown

        // For constraint name
        $html_output .= '<span class="formelement clearfloat">';
        $constraint_name = isset($existrel_foreign[$myfield]['constraint'])
            ? $existrel_foreign[$myfield]['constraint'] : '';
        $html_output .= __('Constraint name');
        $html_output .= '<input type="text" name="constraint_name['
            . $myfield_md5 . ']"'
            . ' value="' . $constraint_name . '"/>';
        $html_output .= '</span>' . "\n";

        $html_output .= '<span class="formelement clearfloat">';
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

        $html_output .= '<span class="formelement clearfloat">' . "\n";
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

    return $html_output;
}

/**
 * Function to send html for table or column dropdown list
 *
 * @return void
 */
function PMA_sendHtmlForTableOrColumnDropdownList()
{
    if (isset($_REQUEST['foreignTable'])) { // if both db and table are selected
        PMA_sendHtmlForColumnDropdownList();
    } else { // if only the db is selected
        PMA_sendHtmlForTableDropdownList();
    }
    exit;
}

/**
 * Function to send html for column dropdown list
 *
 * @return void
 */
function PMA_sendHtmlForColumnDropdownList()
{
    $response = PMA_Response::getInstance();

    $foreignTable = $_REQUEST['foreignTable'];
    $table_obj = new PMA_Table($foreignTable, $_REQUEST['foreignDb']);
    $columns = array();
    foreach ($table_obj->getUniqueColumns(false, false) as $column) {
        $columns[] = htmlspecialchars($column);
    }
    $response->addJSON('columns', $columns);
}

/**
 * Function to send html for table dropdown list
 *
 * @return void
 */
function PMA_sendHtmlForTableDropdownList()
{
    $response = PMA_Response::getInstance();
    $tables = array();

    $foreign = isset($_REQUEST['foreign']) && $_REQUEST['foreign'] === 'true';
    if ($foreign) {
        $tbl_storage_engine = strtoupper(
            PMA_Table::sGetStatusInfo(
                $_REQUEST['db'],
                $_REQUEST['table'],
                'Engine'
            )
        );
    }

    // In Drizzle, 'SHOW TABLE STATUS' will show status only for the tables
    // which are currently in the table cache. Hence we have to use 'SHOW TABLES'
    // and manully retrieve table engine values.
    if ($foreign && ! PMA_DRIZZLE) {
        $query = 'SHOW TABLE STATUS FROM '
            . PMA_Util::backquote($_REQUEST['foreignDb']);
        $tables_rs = $GLOBALS['dbi']->query(
            $query,
            null,
            PMA_DatabaseInterface::QUERY_STORE
        );

        while ($row = $GLOBALS['dbi']->fetchArray($tables_rs)) {
            if (isset($row['Engine'])
                && strtoupper($row['Engine']) == $tbl_storage_engine
            ) {
                $tables[] = htmlspecialchars($row['Name']);
            }
        }
    } else {
        $query = 'SHOW TABLES FROM '
            . PMA_Util::backquote($_REQUEST['foreignDb']);
        $tables_rs = $GLOBALS['dbi']->query(
            $query,
            null,
            PMA_DatabaseInterface::QUERY_STORE
        );
        while ($row = $GLOBALS['dbi']->fetchArray($tables_rs)) {
            if ($foreign && PMA_DRIZZLE) {
                $engine = strtoupper(
                    PMA_Table::sGetStatusInfo(
                        $_REQUEST['foreignDb'],
                        $row[0],
                        'Engine'
                    )
                );
                if (isset($engine) && $engine == $tbl_storage_engine) {
                    $tables[] = htmlspecialchars($row[0]);
                }
            } else {
                $tables[] = htmlspecialchars($row[0]);
            }
        }
    }
    $response->addJSON('tables', $tables);
}

/**
 * Function to handle update for display field
 *
 * @param string $disp          field name
 * @param string $display_field display field
 * @param string $db            current database
 * @param string $table         current table
 * @param array  $cfgRelation   configuration relation
 *
 * @return void
 */
function PMA_handleUpdateForDisplayField($disp, $display_field, $db, $table,
    $cfgRelation
) {
    $upd_query = PMA_getQueryForDisplayUpdate(
        $disp, $display_field, $db, $table, $cfgRelation
    );
    if ($upd_query) {
        PMA_queryAsControlUser($upd_query);
    }
}

/**
 * Function to get display query for handlingdisplay update
 *
 * @param string $disp          field name
 * @param string $display_field display field
 * @param string $db            current database
 * @param string $table         current table
 * @param array  $cfgRelation   configuration relation
 *
 * @return string
 */
function PMA_getQueryForDisplayUpdate($disp, $display_field, $db, $table,
    $cfgRelation
) {
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

    return $upd_query;
}

/**
 * Function to handle updates for internal relations
 *
 * @param string $destination_db          destination database
 * @param string $multi_edit_columns_name multi edit column name
 * @param string $destination_table       destination table
 * @param string $destination_column      destination column
 * @param array  $cfgRelation             configuration relation
 * @param string $db                      current database
 * @param string $table                   current table
 * @param array  $existrel                db, table, column
 *
 * @return void
 */
function PMA_handleUpdatesForInternalRelations($destination_db,
    $multi_edit_columns_name, $destination_table, $destination_column, $cfgRelation,
    $db, $table, $existrel
) {
    foreach ($destination_db as $master_field_md5 => $foreign_db) {
        $upd_query = PMA_getQueryForInternalRelationUpdate(
            $multi_edit_columns_name,
            $master_field_md5, $foreign_db, $destination_table, $destination_column,
            $cfgRelation, $db, $table, isset($existrel) ? $existrel : null
        );
        if ($upd_query) {
            PMA_queryAsControlUser($upd_query);
        }
    }
}

/**
 * Function to get update query for updating internal relations
 *
 * @param string $multi_edit_columns_name multi edit column names
 * @param string $master_field_md5        master field md5
 * @param string $foreign_db              foreign database
 * @param string $destination_table       destination table
 * @param string $destination_column      destination column
 * @param array  $cfgRelation             configuration relation
 * @param string $db                      current database
 * @param string $table                   current table
 * @param array  $existrel                db, table, column
 *
 * @return string
 */
function PMA_getQueryForInternalRelationUpdate($multi_edit_columns_name,
    $master_field_md5, $foreign_db, $destination_table, $destination_column,
    $cfgRelation, $db, $table, $existrel
) {
    $upd_query = false;

    // Map the fieldname's md5 back to its real name
    $master_field = $multi_edit_columns_name[$master_field_md5];

    $foreign_table = $destination_table[$master_field_md5];
    $foreign_field = $destination_column[$master_field_md5];
    if (! empty($foreign_db)
        && ! empty($foreign_table)
        && ! empty($foreign_field)
    ) {
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

        } elseif ($existrel[$master_field]['foreign_db'] != $foreign_db
            || $existrel[$master_field]['foreign_table'] != $foreign_table
            || $existrel[$master_field]['foreign_field'] != $foreign_field
        ) {
            $upd_query  = 'UPDATE '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['relation']) . ' SET'
                . ' foreign_db       = \''
                . PMA_Util::sqlAddSlashes($foreign_db) . '\', '
                . ' foreign_table    = \''
                . PMA_Util::sqlAddSlashes($foreign_table) . '\', '
                . ' foreign_field    = \''
                . PMA_Util::sqlAddSlashes($foreign_field) . '\' '
                . ' WHERE master_db  = \''
                . PMA_Util::sqlAddSlashes($db) . '\''
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

    return $upd_query;
}

/**
 * Function to handle foreign key updates
 *
 * @param string $destination_foreign_db     destination foreign database
 * @param string $multi_edit_columns_name    multi edit column names
 * @param string $destination_foreign_table  destination foreign table
 * @param string $destination_foreign_column destination foreign column
 * @param array  $options_array              options array
 * @param string $table                      current table
 * @param array  $existrel_foreign           db, table, column
 *
 * @return string
 */
function PMA_handleUpdatesForForeignKeys($destination_foreign_db,
    $multi_edit_columns_name, $destination_foreign_table,
    $destination_foreign_column, $options_array, $table, $existrel_foreign
) {
    $html_output = '';
    $display_query = '';
    $seen_error = false;
    foreach ($destination_foreign_db as $master_field_md5 => $foreign_db) {
        $html_output .= PMA_handleUpdateForForeignKey(
            $multi_edit_columns_name, $master_field_md5,
            $destination_foreign_table, $destination_foreign_column, $options_array,
            $existrel_foreign, $table, $seen_error, $display_query, $foreign_db
        );
    } // end foreach
    if (! empty($display_query) && ! $seen_error) {
        $GLOBALS['display_query'] = $display_query;
        $html_output = PMA_Util::getMessage(
            __('Your SQL query has been executed successfully.'),
            null, 'success'
        );
    }

    return $html_output;
}

/**
 * Function to handle update for a foreign key
 *
 * @param array  $multi_edit_columns_name    multu edit columns name
 * @param string $master_field_md5           master field md5
 * @param string $destination_foreign_table  destination foreign table
 * @param string $destination_foreign_column destination foreign column
 * @param array  $options_array              options array
 * @param array  $existrel_foreign           db, table, column
 * @param string $table                      current table
 * @param bool   &$seen_error                whether seen error
 * @param string &$display_query             display query
 * @param string $foreign_db                 foreign database
 *
 * @return string
 */
function PMA_handleUpdateForForeignKey($multi_edit_columns_name, $master_field_md5,
    $destination_foreign_table, $destination_foreign_column, $options_array,
    $existrel_foreign, $table, &$seen_error, &$display_query, $foreign_db
) {
    $html_output = '';
    $create = false;
    $drop = false;

    // Map the fieldname's md5 back to its real name
    $master_field = $multi_edit_columns_name[$master_field_md5];

    $foreign_table = $destination_foreign_table[$master_field_md5];
    $foreign_field = $destination_foreign_column[$master_field_md5];
    if (! empty($foreign_db)
        && ! empty($foreign_table)
        && ! empty($foreign_field)
    ) {
        if ( isset($existrel_foreign[$master_field])) {
            $constraint_name = $existrel_foreign[$master_field]['constraint'];
            $on_delete = ! empty(
                        $existrel_foreign[$master_field]['on_delete'])
                        ? $existrel_foreign[$master_field]['on_delete'] : 'RESTRICT';
            $on_update = ! empty(
                        $existrel_foreign[$master_field]['on_update'])
                        ? $existrel_foreign[$master_field]['on_update'] : 'RESTRICT';
        }
        if (! isset($existrel_foreign[$master_field])) {
            // no key defined for this field
            $create = true;
        } elseif ($existrel_foreign[$master_field]['foreign_db'] != $foreign_db
            || $existrel_foreign[$master_field]['foreign_table'] != $foreign_table
            || $existrel_foreign[$master_field]['foreign_field'] != $foreign_field
            || $_REQUEST['constraint_name'][$master_field_md5] != $constraint_name
            || ($_REQUEST['on_delete'][$master_field_md5] != $on_delete)
            || ($_REQUEST['on_update'][$master_field_md5] != $on_update)
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
        $GLOBALS['dbi']->tryQuery($drop_query);
        $tmp_error_drop = $GLOBALS['dbi']->getError();

        if (! empty($tmp_error_drop)) {
            $seen_error = true;
            $html_output .= PMA_Util::mysqlDie(
                $tmp_error_drop, $drop_query, false, '', false
            );
            return $html_output;
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
        $GLOBALS['dbi']->tryQuery($create_query);
        $tmp_error_create = $GLOBALS['dbi']->getError();
        if (! empty($tmp_error_create)) {
            $seen_error = true;

            if (substr($tmp_error_create, 1, 4) == '1005') {
                $message = PMA_Message::error(
                    __('Error creating foreign key on %1$s (check data types)')
                );
                $message->addParam($master_field);
                $html_output .= $message->getDisplay();
            } else {
                $html_output .= PMA_Util::mysqlDie(
                    $tmp_error_create, $create_query, false, '', false
                );
            }
            $html_output .= PMA_Util::showMySQLDocu(
                'InnoDB_foreign_key_constraints'
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
                $table,
                $master_field,
                $existrel_foreign[$master_field]['foreign_db'],
                $existrel_foreign[$master_field]['foreign_table'],
                $existrel_foreign[$master_field]['foreign_field'],
                $existrel_foreign[$master_field]['constraint'],
                $options_array[$existrel_foreign[$master_field]['on_delete']],
                $options_array[$existrel_foreign[$master_field]['on_update']]
            );
            $display_query .= $sql_query_recreate . "\n";
            $GLOBALS['dbi']->tryQuery($sql_query_recreate);
        }
    }

    return $html_output;
}
?>
