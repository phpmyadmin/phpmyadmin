<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the table relation page
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/Template.class.php';

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
    return PMA\Template::get('tbl_relation/dropdown_generate')->render(
        array(
            'dropdown_question' => $dropdown_question,
            'select_name' => $select_name,
            'choices' => $choices,
            'selected_value' => $selected_value
        )
    );
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
    return PMA\Template::get('tbl_relation/relational_dropdown')->render(
        array(
            'name' => $name,
            'title' => $title,
            'values' => $values,
            'foreign' => $foreign
        )
    );
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
    return PMA\Template::get('tbl_relation/common_form')->render(
        array(
            'db' => $db,
            'table' => $table,
            'columns' => $columns,
            'cfgRelation' => $cfgRelation,
            'tbl_storage_engine' => $tbl_storage_engine,
            'existrel' => $existrel,
            'existrel_foreign' => $existrel_foreign,
            'options_array' => $options_array
        )
    );
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

    $foreign_table = false;
    $foreign_column = false;

    // database dropdown
    if (isset($existrel[$myfield])) {
        $foreign_db = $existrel[$myfield]['foreign_db'];
    } else {
        $foreign_db = $db;
    }

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

    // column dropdown
    $columns = array();
    if ($foreign_db && $foreign_table) {
        if (isset($existrel[$myfield])) {
            $foreign_column = $existrel[$myfield]['foreign_field'];
        }
        $table_obj = new PMA_Table($foreign_table, $foreign_db);
        $columns = $table_obj->getUniqueColumns(false, false);
    }

    return PMA\Template::get('tbl_relation/internal_relational_row')->render(
        array(
            'myfield_md5' => $myfield_md5,
            'myfield_html' => $myfield_html,
            'odd_row' => $odd_row,
            'foreign_db' => $foreign_db,
            'foreign_table' => $foreign_table,
            'tables' => $tables,
            'foreign_column' => $foreign_column,
            'columns' => $columns
        )
    );
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
    $js_msg = '';
    $this_params = null;
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
    }

    // For ON DELETE and ON UPDATE, the default action
    // is RESTRICT as per MySQL doc; however, a SHOW CREATE TABLE
    // won't display the clause if it's set as RESTRICT.
    $on_delete = isset($one_key['on_delete'])
        ? $one_key['on_delete'] : 'RESTRICT';
    $on_update = isset($one_key['on_update'])
        ? $one_key['on_update'] : 'RESTRICT';

    $column_array = array();
    $column_array[''] = '';
    foreach ($columns as $column) {
        if (! empty($column['Key'])) {
            $column_array[$column['Field']] = $column['Field'];
        }
    }

    $foreign_table = false;
    // foreign database dropdown
    $foreign_db = (isset($one_key['ref_db_name'])) ? $one_key['ref_db_name'] : $db;

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

    return PMA\Template::get('tbl_relation/foreign_key_row')->render(
        array(
            'odd_row' => $odd_row,
            'js_msg' => $js_msg,
            'one_key' => $one_key,
            'this_params' => $this_params,
            'on_delete' => $on_delete,
            'on_update' => $on_update,
            'column_array' => $column_array,
            'foreign_db' => $foreign_db,
            'foreign_table' => $foreign_table,
            'tables' => $tables,
            'i' => $i,
            'options_array' => $options_array
        )
    );
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
