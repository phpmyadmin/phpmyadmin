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
    $html_output = (! empty($dropdown_question) ?
        htmlspecialchars($dropdown_question) . '&nbsp;&nbsp;' : '')
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
    $final_pos = /*overload*/mb_strlen($text) - 1;
    $pos = 0;
    while ($pos <= $final_pos) {
        $first_backquote = /*overload*/mb_strpos($text, '`', $pos);
        $second_backquote = /*overload*/mb_strpos($text, '`', $first_backquote + 1);
        // after the second one, there might be another one which means
        // this is an escaped backquote
        if ($second_backquote < $final_pos && '`' == $text[$second_backquote + 1]) {
            $second_backquote
                = /*overload*/mb_strpos($text, '`', $second_backquote + 2);
        }
        if (false === $first_backquote || false === $second_backquote) {
            break;
        }
        $elements[] = /*overload*/mb_substr(
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
 * @param array  $field        field names
 * @param string $foreignDb    foreign database name
 * @param string $foreignTable foreign table name
 * @param array  $foreignField foreign field names
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

    foreach ($field as $key => $one_field) {
        $field[$key] = PMA_Util::backquote($one_field);
    }
    foreach ($foreignField as $key => $one_field) {
        $foreignField[$key] = PMA_Util::backquote($one_field);
    }
    $sql_query .= ' FOREIGN KEY (' . implode(', ', $field) . ')'
        . ' REFERENCES ' . PMA_Util::backquote($foreignDb)
        . '.' . PMA_Util::backquote($foreignTable)
        . '(' . implode(', ', $foreignField) . ')';

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

    if (is_string($foreign) && ! $seen_key) {
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

    if ($cfgRelation['relwork']) {
        $html_output .= PMA_getHtmlForInternalRelationForm(
            $columns, $tbl_storage_engine, $existrel, $db
        );
    }

    if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
        $html_output .= PMA_getHtmlForForeignKeyForm(
            $columns, $existrel_foreign,
            $db, $tbl_storage_engine, $options_array
        );
    } // end if (InnoDB)

    if ($cfgRelation['displaywork']) {
        $html_output .= PMA_getHtmlForDisplayFieldInfos(
            $db, $table,
            array_values($columns)
        );
    }

    $html_output .= PMA_getHtmlForCommonFormFooter();

    return $html_output;
}

/**
 * Function to get html for Internal relations form
 *
 * @param array  $columns            columns
 * @param string $tbl_storage_engine table storage engine
 * @param array  $existrel           db, table, column
 * @param string $db                 current database
 *
 * @return string
 */
function PMA_getHtmlForInternalRelationForm($columns, $tbl_storage_engine,
    $existrel, $db
) {
    $save_row = array_values($columns);
    $saved_row_cnt  = count($save_row);

    $html_output = '<fieldset>'
        . '<legend>' . __('Internal relations') . '</legend>'
        . '<table id="internal_relations" class="relationalTable">';

    $html_output .= '<tr><th>' . __('Column') . '</th>';

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

    $odd_row = true;
    for ($i = 0; $i < $saved_row_cnt; $i++) {
        $html_output .= PMA_getHtmlForInternalRelationRow(
            $save_row, $i, $odd_row,
            $existrel, $db
        );
        $odd_row = ! $odd_row;
    }

    $html_output .= '</table>';
    $html_output .= '</fieldset>';

    return $html_output;
}

/**
 * Function to get html for an entire row in common form
 *
 * @param array  $save_row save row
 * @param int    $i        counter
 * @param bool   $odd_row  whether odd row or not
 * @param array  $existrel db, table, column
 * @param string $db       current db
 *
 * @return string
 */
function PMA_getHtmlForInternalRelationRow($save_row, $i, $odd_row,
    $existrel, $db
) {
    $myfield = $save_row[$i]['Field'];
    // Use an md5 as array index to avoid having special characters
    // in the name attribute (see bug #1746964 )
    $myfield_md5 = md5($myfield);
    $myfield_html = htmlspecialchars($myfield);

    $html_output = '<tr class="' . ($odd_row ? 'odd' : 'even') . '">'
        . '<td class="vmiddle">'
        . '<strong>' . $myfield_html . '</strong>'
        . '<input type="hidden" name="fields_name[' . $myfield_md5 . ']"'
        . ' value="' . $myfield_html . '"/>'
        . '</td>';

    $html_output .= '<td>';

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
    $html_output .= '</tr>';

    return $html_output;
}

/**
 * Function to get html for Foreign key form
 *
 * @param array  $columns            columns
 * @param array  $existrel_foreign   db, table, column
 * @param string $db                 current database
 * @param string $tbl_storage_engine table storage engine
 * @param array  $options_array      options array
 *
 * @return string
 */
function PMA_getHtmlForForeignKeyForm($columns, $existrel_foreign, $db,
    $tbl_storage_engine, $options_array
) {
    $html_output = '<fieldset>'
        . '<legend>' . __('Foreign key constraints') . '</legend>'
        . '<table id="foreign_keys" class="relationalTable">';

    $html_output .= '<tr><th>' . __('Actions') . '</th>';
    $html_output .= '<th>' . __('Constraint properties') . '</th>'
        . '<th>'
        . __('Column')
        . PMA_Util::showHint(
            __(
                'Only columns with index will be displayed. You can define an'
                . ' index below.'
            )
        )
        . '</th>';
    $html_output .= '<th colspan="3">' . __('Foreign key constraint')
        . ' (' . $tbl_storage_engine . ')';
    $html_output .= '</th></tr>';

    $odd_row = true;
    $i = 0;
    foreach ($existrel_foreign as $key => $one_key) {
        $html_output .= PMA_getHtmlForForeignKeyRow(
            $one_key, $odd_row, $columns, $i++, $options_array, $tbl_storage_engine,
            $db
        );
        $odd_row = ! $odd_row;
    }
    $html_output .= PMA_getHtmlForForeignKeyRow(
        array(), $odd_row, $columns, $i++, $options_array, $tbl_storage_engine,
        $db
    );

    $html_output .= '<tr>'
        . '<td colspan="5"><a class="formelement clearfloat'
        . ' add_foreign_key" href="">'
        . __('+ Add constraint')
        . '</td>';
    $html_output .= '</tr>';
    $html_output .= '</table>'
        . '</fieldset>';

    return $html_output;
}

/**
 * Function to get html for an entire row in foreign key form
 *
 * @param array  $one_key            Single foreign key constraint
 * @param bool   $odd_row            whether odd or even row
 * @param array  $columns            Array of table columns
 * @param int    $i                  Row number
 * @param array  $options_array      Options array
 * @param string $tbl_storage_engine table storage engine
 * @param string $db                 Database
 *
 * @return string html
 */
function PMA_getHtmlForForeignKeyRow($one_key, $odd_row, $columns, $i,
    $options_array, $tbl_storage_engine, $db
) {
    $html_output = '<tr class="' . ($odd_row ? 'odd' : 'even') . '">';
    // Drop key anchor.
    $html_output .= '<td>';
    if (isset($one_key['constraint'])) {
        $drop_fk_query = 'ALTER TABLE ' . PMA_Util::backquote($GLOBALS['table'])
            . ' DROP FOREIGN KEY '
            . PMA_Util::backquote($one_key['constraint']) . ';';
        $this_params = $GLOBALS['url_params'];
        $this_params['goto'] = 'tbl_relation.php';
        $this_params['back'] = 'tbl_relation.php';
        $this_params['sql_query'] = $drop_fk_query;
        $this_params['message_to_show'] = sprintf(
            __('Foreign key constraint %s has been dropped'),
            $one_key['constraint']
        );
        $js_msg = PMA_jsFormat(
            'ALTER TABLE ' . $GLOBALS['table']
            . ' DROP FOREIGN KEY '
            . $one_key['constraint'] . ';'
        );

        $html_output .= '<input type="hidden" class="drop_foreign_key_msg"'
            . ' value="' . $js_msg . '" />';
        $html_output .= '    <a class="drop_foreign_key_anchor';
        $html_output .= ' ajax';
        $html_output .= '" href="sql.php' . PMA_URL_getCommon($this_params)
           . '" >'
           . PMA_Util::getIcon('b_drop.png', __('Drop'))  . '</a>';
    }
    $html_output .= '</td>';
    $html_output .= '<td>';
    $html_output .= '<span class="formelement clearfloat">';
    $constraint_name = isset($one_key['constraint'])
        ? $one_key['constraint'] : '';
    $html_output .= '<input type="text" name="constraint_name[' . $i . ']"'
        . ' value="' . htmlspecialchars($constraint_name) . '"'
        . ' placeholder="' . __('Constraint name') . '" />';
    $html_output .= '</span>' . "\n";

    $html_output .= '<div class="floatleft">';
    $html_output .= '<span class="formelement">';
    // For ON DELETE and ON UPDATE, the default action
    // is RESTRICT as per MySQL doc; however, a SHOW CREATE TABLE
    // won't display the clause if it's set as RESTRICT.
    $on_delete = isset($one_key['on_delete'])
        ? $one_key['on_delete'] : 'RESTRICT';
    $html_output .= PMA_generateDropdown(
        'ON DELETE',
        'on_delete[' . $i . ']',
        $options_array,
        $on_delete
    );
    $html_output .= '</span>' . "\n";

    $html_output .= '<span class="formelement">' . "\n";
    $on_update = isset($one_key['on_update'])
        ? $one_key['on_update'] : 'RESTRICT';
    $html_output .= PMA_generateDropdown(
        'ON UPDATE',
        'on_update[' . $i . ']',
        $options_array,
        $on_update
    );
    $html_output .= '</span>';
    $html_output .= '</div>';

    $column_array = array();
    $column_array[''] = '';
    foreach ($columns as $column) {
        if (! empty($column['Key'])) {
            $column_array[$column['Field']] = $column['Field'];
        }
    }

    $html_output .= '</span>' . "\n";
    $html_output .= '</td>';
    $html_output .= '<td>';
    if (isset($one_key['index_list'])) {
        foreach ($one_key['index_list'] as $key => $column) {
            $html_output .= '<span class="formelement clearfloat">';
            $html_output .= PMA_generateDropdown(
                '',
                'foreign_key_fields_name[' . $i . '][]',
                $column_array,
                $column
            );
            $html_output .= '</span>';
        }
    } else {
        $html_output .= '<span class="formelement clearfloat">';
        $html_output .= PMA_generateDropdown(
            '',
            'foreign_key_fields_name[' . $i . '][]',
            $column_array,
            ''
        );
        $html_output .= '</span>';
    }

    $html_output .= '<a class="formelement clearfloat add_foreign_key_field"'
        . ' href="" data-index="' . $i . '">'
        . __('+ Add column')
        . '</a>';
    $html_output .= '</td>';
    $html_output .= '<td>';
    $foreign_table = false;

    // foreign database dropdown
    $foreign_db = (isset($one_key['ref_db_name'])) ? $one_key['ref_db_name'] : $db;
    $html_output .= '<span class="formelement clearfloat">';
    $html_output .= PMA_generateRelationalDropdown(
        'destination_foreign_db[' . $i . ']',
        $GLOBALS['pma']->databases,
        $foreign_db,
        __('Database')
    );
    // end of foreign database dropdown
    $html_output .= '</td>';
    $html_output .= '<td>';
    // foreign table dropdown
    $tables = array();
    if ($foreign_db) {
        $foreign_table = isset($one_key['ref_table_name'])
            ? $one_key['ref_table_name'] : '';

        // In Drizzle, 'SHOW TABLE STATUS' will show status only for the tables
        // which are currently in the table cache. Hence we have to use
        // 'SHOW TABLES' and manualy retrieve table engine values.
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
                    && /*overload*/mb_strtoupper($engine) == $tbl_storage_engine
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
                    && /*overload*/mb_strtoupper($row[1]) == $tbl_storage_engine
                ) {
                    $tables[] = $row[0];
                }
            }
        }
    }
    $html_output .= '<span class="formelement clearfloat">';
    $html_output .= PMA_generateRelationalDropdown(
        'destination_foreign_table[' . $i . ']',
        $tables,
        $foreign_table,
        __('Table')
    );
    $html_output .= '</span>';
    // end of foreign table dropdown
    $html_output .= '</td>';
    $html_output .= '<td>';
    // foreign column dropdown
    if ($foreign_db && $foreign_table) {
        foreach ($one_key['ref_index_list'] as $foreign_column) {
            $table_obj = new PMA_Table($foreign_table, $foreign_db);
            $columns = $table_obj->getUniqueColumns(false, false);
            $html_output .= '<span class="formelement clearfloat">';
            $html_output .= PMA_generateRelationalDropdown(
                'destination_foreign_column[' . $i . '][]',
                $columns,
                $foreign_column,
                __('Column')
            );
            $html_output .= '</span>';
        }
    } else {
        $html_output .= '<span class="formelement clearfloat">';
        $html_output .= PMA_generateRelationalDropdown(
            'destination_foreign_column[' . $i . '][]',
            array(),
            '',
            __('Column')
        );
        $html_output .= '</span>';
    }
    // end of foreign column dropdown
    $html_output .= '</td>';
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
        . '<input type="button" class="preview_sql" value="'
        . __('Preview SQL') . '" />'
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
    // Since views do not have keys defined on them provide the full list of columns
    if (PMA_Table::isView($_REQUEST['foreignDb'], $foreignTable)) {
        $columnList = $table_obj->getColumns(false, false);
    } else {
        $columnList = $table_obj->getIndexedColumns(false, false);
    }
    $columns = array();
    foreach ($columnList as $column) {
        $columns[] = htmlspecialchars($column);
    }
    $response->addJSON('columns', $columns);

    // @todo should be: $server->db($db)->table($table)->primary()
    $primary = PMA_Index::getPrimary($foreignTable, $_REQUEST['foreignDb']);
    if (false === $primary) {
        return;
    }

    $primarycols = array_keys($primary->getColumns());
    $response->addJSON('primary', $primarycols);
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
        $tbl_storage_engine = /*overload*/mb_strtoupper(
            PMA_Table::sGetStatusInfo(
                $_REQUEST['db'],
                $_REQUEST['table'],
                'Engine'
            )
        );
    }

    // In Drizzle, 'SHOW TABLE STATUS' will show status only for the tables
    // which are currently in the table cache. Hence we have to use 'SHOW TABLES'
    // and manually retrieve table engine values.
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
                && /*overload*/mb_strtoupper($row['Engine']) == $tbl_storage_engine
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
                $engine = /*overload*/mb_strtoupper(
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
 * @param string $disp          current display field
 * @param string $display_field display field
 * @param string $db            current database
 * @param string $table         current table
 * @param array  $cfgRelation   configuration relation
 *
 * @return string
 */
function PMA_handleUpdateForDisplayField($disp, $display_field, $db, $table,
    $cfgRelation
) {
    $html_output = '';
    $upd_query = PMA_getQueryForDisplayUpdate(
        $disp, $display_field, $db, $table, $cfgRelation
    );
    if ($upd_query) {
        PMA_queryAsControlUser($upd_query);
        $html_output = PMA_Util::getMessage(
            __('Display column was successfully updated.'),
            '', 'success'
        );
    }
    return $html_output;
}

/**
 * Function to get display query for handlingdisplay update
 *
 * @param string $disp          current display field
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
        if ($display_field == '') {
            $upd_query = 'DELETE FROM '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                . ' WHERE db_name  = \'' . PMA_Util::sqlAddSlashes($db) . '\''
                . ' AND table_name = \'' . PMA_Util::sqlAddSlashes($table) . '\'';
        } elseif ($disp != $display_field) {
            $upd_query = 'UPDATE '
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info'])
                . ' SET display_field = \''
                . PMA_Util::sqlAddSlashes($display_field) . '\''
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
 * @param array      $destination_db          destination databases
 * @param array      $multi_edit_columns_name multi edit column names
 * @param array      $destination_table       destination tables
 * @param array      $destination_column      destination columns
 * @param array      $cfgRelation             configuration relation
 * @param string     $db                      current database
 * @param string     $table                   current table
 * @param array|null $existrel                db, table, column
 *
 * @return string
 */
function PMA_handleUpdatesForInternalRelations($destination_db,
    $multi_edit_columns_name, $destination_table, $destination_column, $cfgRelation,
    $db, $table, $existrel
) {
    $html_output = '';
    $updated = false;
    foreach ($destination_db as $master_field_md5 => $foreign_db) {
        $upd_query = PMA_getQueryForInternalRelationUpdate(
            $multi_edit_columns_name,
            $master_field_md5, $foreign_db, $destination_table, $destination_column,
            $cfgRelation, $db, $table, isset($existrel) ? $existrel : null
        );
        if ($upd_query) {
            PMA_queryAsControlUser($upd_query);
            $updated = true;
        }
    }
    if ($updated) {
        $html_output = PMA_Util::getMessage(
            __('Internal relations were successfully updated.'),
            '', 'success'
        );
    }
    return $html_output;
}

/**
 * Function to get update query for updating internal relations
 *
 * @param array      $multi_edit_columns_name multi edit column names
 * @param string     $master_field_md5        master field md5
 * @param string     $foreign_db              foreign database
 * @param array      $destination_table       destination tables
 * @param array      $destination_column      destination columns
 * @param array      $cfgRelation             configuration relation
 * @param string     $db                      current database
 * @param string     $table                   current table
 * @param array|null $existrel                db, table, column
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
 * @param array  $destination_foreign_db     destination foreign database
 * @param array  $multi_edit_columns_name    multi edit column names
 * @param array  $destination_foreign_table  destination foreign table
 * @param array  $destination_foreign_column destination foreign column
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
    $preview_sql_data = '';
    $display_query = '';
    $seen_error = false;
    $preview_sql = (isset($_REQUEST['preview_sql'])) ? true : false;
    foreach ($destination_foreign_db as $master_field_md5 => $foreign_db) {
        list($html, $sql_data) = PMA_handleUpdateForForeignKey(
            $multi_edit_columns_name, $master_field_md5,
            $destination_foreign_table, $destination_foreign_column, $options_array,
            $existrel_foreign, $table, $seen_error, $display_query, $foreign_db,
            $preview_sql
        );
        $html_output .= $html;
        $preview_sql_data .= $sql_data;
    } // end foreach

    // If there is a request for SQL previewing.
    if ($preview_sql) {
        PMA_previewSQL($preview_sql_data);
    }

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
 * @param array  $multi_edit_columns_name    multi edit columns names
 * @param string $master_field_md5           master field md5
 * @param array  $destination_foreign_table  destination foreign tables
 * @param array  $destination_foreign_column destination foreign columns
 * @param array  $options_array              options array
 * @param array  $existrel_foreign           db, table, column
 * @param string $table                      current table
 * @param bool   &$seen_error                whether seen error
 * @param string &$display_query             display query
 * @param string $foreign_db                 foreign database
 * @param bool   $preview_sql                preview sql before executing
 *
 * @return array
 */
function PMA_handleUpdateForForeignKey($multi_edit_columns_name, $master_field_md5,
    $destination_foreign_table, $destination_foreign_column, $options_array,
    $existrel_foreign, $table, &$seen_error, &$display_query,
    $foreign_db, $preview_sql
) {
    $html_output = '';
    $preview_sql_data = '';
    $create = false;
    $drop = false;

    // Map the fieldname's md5 back to its real name
    $master_field = $multi_edit_columns_name[$master_field_md5];

    $foreign_table = $destination_foreign_table[$master_field_md5];
    $foreign_field = $destination_foreign_column[$master_field_md5];

    if (isset($existrel_foreign[$master_field_md5]['ref_db_name'])) {
        $ref_db_name = $existrel_foreign[$master_field_md5]['ref_db_name'];
    } else {
        $ref_db_name = $GLOBALS['db'];
    }

    $empty_fields = false;
    foreach ($master_field as $key => $one_field) {
        if ((! empty($one_field) && empty($foreign_field[$key]))
            || (empty($one_field) && ! empty($foreign_field[$key]))
        ) {
            $empty_fields = true;
        }

        if (empty($one_field) && empty($foreign_field[$key])) {
            unset($master_field[$key]);
            unset($foreign_field[$key]);
        }
    }

    if (! empty($foreign_db)
        && ! empty($foreign_table)
        && ! $empty_fields
    ) {
        if (isset($existrel_foreign[$master_field_md5])) {
            $constraint_name = $existrel_foreign[$master_field_md5]['constraint'];
            $on_delete = ! empty(
                        $existrel_foreign[$master_field_md5]['on_delete'])
                        ? $existrel_foreign[$master_field_md5]['on_delete']
                        : 'RESTRICT';
            $on_update = ! empty(
                        $existrel_foreign[$master_field_md5]['on_update'])
                        ? $existrel_foreign[$master_field_md5]['on_update']
                        : 'RESTRICT';

            if ($ref_db_name != $foreign_db
                || $existrel_foreign[$master_field_md5]['ref_table_name'] != $foreign_table
                || $existrel_foreign[$master_field_md5]['ref_index_list'] != $foreign_field
                || $existrel_foreign[$master_field_md5]['index_list'] != $master_field
                || $_REQUEST['constraint_name'][$master_field_md5] != $constraint_name
                || ($_REQUEST['on_delete'][$master_field_md5] != $on_delete)
                || ($_REQUEST['on_update'][$master_field_md5] != $on_update)
            ) {
                // another foreign key is already defined for this field
                // or an option has been changed for ON DELETE or ON UPDATE
                $drop = true;
                $create = true;
            } // end if... else....
        } else {
            // no key defined for this field(s)
            $create = true;
        }
    } elseif (isset($existrel_foreign[$master_field_md5])) {
        $drop = true;
    } // end if... else....

    $tmp_error_drop = false;
    if ($drop) {
        $drop_query = PMA_getSQLToDropForeignKey(
            $table, $existrel_foreign[$master_field_md5]['constraint']
        );

        if (! $preview_sql) {
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
        } else {
            $preview_sql_data .= $drop_query . "\n";
        }
    }
    $tmp_error_create = false;
    if (!$create) {
        return array($html_output, $preview_sql_data);
    }

    $create_query = PMA_getSQLToCreateForeignKey(
        $table, $master_field, $foreign_db, $foreign_table, $foreign_field,
        $_REQUEST['constraint_name'][$master_field_md5],
        $options_array[$_REQUEST['on_delete'][$master_field_md5]],
        $options_array[$_REQUEST['on_update'][$master_field_md5]]
    );

    if (! $preview_sql) {
        $display_query .= $create_query . "\n";
        $GLOBALS['dbi']->tryQuery($create_query);
        $tmp_error_create = $GLOBALS['dbi']->getError();
        if (! empty($tmp_error_create)) {
            $seen_error = true;

            if (substr($tmp_error_create, 1, 4) == '1005') {
                $message = PMA_Message::error(
                    __('Error creating foreign key on %1$s (check data types)')
                );
                $message->addParam(implode(', ', $master_field));
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
    } else {
        $preview_sql_data .= $create_query . "\n";
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
            $existrel_foreign[$master_field_md5]['ref_db_name'],
            $existrel_foreign[$master_field_md5]['ref_table_name'],
            $existrel_foreign[$master_field_md5]['ref_index_list'],
            $existrel_foreign[$master_field_md5]['constraint'],
            $options_array[$existrel_foreign[$master_field_md5]['on_delete']],
            $options_array[$existrel_foreign[$master_field_md5]['on_update']]
        );
        if (! $preview_sql) {
            $display_query .= $sql_query_recreate . "\n";
            $GLOBALS['dbi']->tryQuery($sql_query_recreate);
        } else {
            $preview_sql_data .= $sql_query_recreate;
        }
    }

    return array($html_output, $preview_sql_data);
}
?>
