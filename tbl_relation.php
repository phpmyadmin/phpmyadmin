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
 * @todo if above todos are fullfilled we can add all fields meet requirements
 * in the select dropdown
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/index.lib.php';
require_once 'libraries/Template.class.php';
require_once 'libraries/Table.class.php';
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();

// Send table of column names to populate corresponding dropdowns depending
// on the current selection
if (isset($_REQUEST['getDropdownValues'])
    && $_REQUEST['getDropdownValues'] === 'true'
) {
    if (isset($_REQUEST['foreignTable'])) { // if both db and table are selected
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
    } else { // if only the db is selected
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
    exit;
}

$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_relation.js');
$scripts->addFile('indexes.js');

/**
 * Gets tables information
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
} else {
    $disp = '';
}

// will be used in the logic for internal relations and foreign keys:
$multi_edit_columns_name = isset($_REQUEST['fields_name'])
    ? $_REQUEST['fields_name']
    : null;


$html_output = '';
$upd_query = new PMA_Table($table, $db, $GLOBALS['dbi']);

// u p d a t e s   f o r   I n t e r n a l    r e l a t i o n s
if (isset($_POST['destination_db']) && $cfgRelation['relwork']) {
    if ($upd_query->updateInternalRelations(
        $multi_edit_columns_name, $_POST['destination_db'], $_POST['destination_table'],
        $_POST['destination_column'], $cfgRelation, isset($existrel) ? $existrel : null
    )) {
        $html_output .= PMA_Util::getMessage(
            __('Internal relations were successfully updated.'),
            '', 'success'
        );
    }
} // end if (updates for internal relations)

$multi_edit_columns_name = isset($_REQUEST['foreign_key_fields_name'])
    ? $_REQUEST['foreign_key_fields_name']
    : null;

// u p d a t e s    f o r    f o r e i g n    k e y s
// (for now, one index name only; we keep the definitions if the
// foreign db is not the same)
if (isset($_POST['destination_foreign_db'])) {
    list($html, $preview_sql_data, $display_query, $seen_error) = $upd_query->updateForeignKeys(
        $_POST['destination_foreign_db'],
        $multi_edit_columns_name, $_POST['destination_foreign_table'],
        $_POST['destination_foreign_column'], $options_array, $table,
        isset($existrel_foreign) ? $existrel_foreign['foreign_keys_data'] : null
    );
    $html_output .= $html;

    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {
        PMA_previewSQL($preview_sql_data);
    }

    if (! empty($display_query) && ! $seen_error) {
        $GLOBALS['display_query'] = $display_query;
        $html_output .= PMA_Util::getMessage(
            __('Your SQL query has been executed successfully.'),
            null, 'success'
        );
    }
} // end if isset($destination_foreign)

// U p d a t e s   f o r   d i s p l a y   f i e l d
if ($cfgRelation['displaywork'] && isset($_POST['display_field'])) {
    if ($upd_query->updateDisplayField($disp, $_POST['display_field'], $cfgRelation)) {
        $html_output .= PMA_Util::getMessage(
            __('Display column was successfully updated.'),
            '', 'success'
        );
    }
} // end if

// If we did an update, refresh our data
if (isset($_POST['destination_db']) && $cfgRelation['relwork']) {
    $existrel = PMA_getForeigners($db, $table, '', 'internal');
}
if (isset($_POST['destination_foreign_db'])
    && PMA_Util::isForeignKeySupported($tbl_storage_engine)
) {
    $existrel_foreign = PMA_getForeigners($db, $table, '', 'foreign');
}

if ($cfgRelation['displaywork']) {
    $disp     = PMA_getDisplayField($db, $table);
}


// display secondary level tabs if necessary
$engine = PMA_Table::sGetStatusInfo($db, $table, 'ENGINE');
$response->addHTML(PMA_getStructureSecondaryTabs($engine));
$response->addHTML('<div id="structure_content">');

/**
 * Dialog
 */
// Now find out the columns of our $table
// need to use PMA_DatabaseInterface::QUERY_STORE with $GLOBALS['dbi']->numRows()
// in mysqli
$columns = $GLOBALS['dbi']->getColumns($db, $table);

// common form
$html_output .= PMA\Template::get('tbl_relation/common_form')->render(
    array(
        'db' => $db,
        'table' => $table,
        'columns' => $columns,
        'cfgRelation' => $cfgRelation,
        'tbl_storage_engine' => $tbl_storage_engine,
        'existrel' => isset($existrel) ? $existrel : array(),
        'existrel_foreign' => isset($existrel_foreign) ? $existrel_foreign['foreign_keys_data'] : array(),
        'options_array' => $options_array
    )
);

if (PMA_Util::isForeignKeySupported($tbl_storage_engine)) {
    $html_output .= PMA_getHtmlForDisplayIndexes();
}
// Render HTML output
$response->addHTML($html_output);

$response->addHTML('</div>');
