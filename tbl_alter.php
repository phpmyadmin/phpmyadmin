<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Alter one or more table columns
 *
 * linked from table_structure, uses libraries/tbl_properties.inc.php to display
 * form and handles this form data
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

if (isset($_REQUEST['field'])) {
    $GLOBALS['field'] = $_REQUEST['field'];
}

// Check parameters
PMA_Util::checkParameters(array('db', 'table'));

/**
 * Gets tables informations
 */
require_once 'libraries/tbl_common.inc.php';
require_once 'libraries/tbl_info.inc.php';

$active_page = 'tbl_structure.php';

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);

/**
 * Moving columns
 */
if (isset($_REQUEST['move_columns'])
    && is_array($_REQUEST['move_columns'])
    && $GLOBALS['is_ajax_request']
) {
    /*
     * first, load the definitions for all columns
     */
    $columns = PMA_DBI_get_columns_full($db, $table);
    $column_names = array_keys($columns);
    $changes = array();
    $we_dont_change_keys = array();

    // move columns from first to last
    for ($i = 0, $l = count($_REQUEST['move_columns']); $i < $l; $i++) {
        $column = $_REQUEST['move_columns'][$i];
        // is this column already correctly placed?
        if ($column_names[$i] == $column) {
            continue;
        }

        // it is not, let's move it to index $i
        $data = $columns[$column];
        $extracted_columnspec = PMA_Util::extractColumnSpec($data['Type']);
        if (isset($data['Extra']) && $data['Extra'] == 'on update CURRENT_TIMESTAMP') {
            $extracted_columnspec['attribute'] = $data['Extra'];
            unset($data['Extra']);
        }
        $current_timestamp = false;
        if ($data['Type'] == 'timestamp' && $data['Default'] == 'CURRENT_TIMESTAMP') {
            $current_timestamp = true;
        }
        $default_type
            = $data['Null'] === 'YES' && $data['Default'] === null
                ? 'NULL'
                : ($current_timestamp
                    ? 'CURRENT_TIMESTAMP'
                    : ($data['Default'] == ''
                        ? 'NONE'
                        : 'USER_DEFINED'));

        $changes[] = 'CHANGE ' . PMA_Table::generateAlter(
            $column,
            $column,
            strtoupper($extracted_columnspec['type']),
            $extracted_columnspec['spec_in_brackets'],
            $extracted_columnspec['attribute'],
            isset($data['Collation']) ? $data['Collation'] : '',
            $data['Null'] === 'YES' ? 'NULL' : 'NOT NULL',
            $default_type,
            $current_timestamp ? '' : $data['Default'],
            isset($data['Extra']) && $data['Extra'] !== '' ? $data['Extra'] : false,
            isset($data['Comments']) && $data['Comments'] !== ''
            ? $data['Comments'] : false,
            $we_dont_change_keys,
            $i,
            $i === 0 ? '-first' : $column_names[$i - 1]
        );
        // update current column_names array, first delete old position
        for ($j = 0, $ll = count($column_names); $j < $ll; $j++) {
            if ($column_names[$j] == $column) {
                unset($column_names[$j]);
            }
        }
        // insert moved column
        array_splice($column_names, $i, 0, $column);
    }
    $response = PMA_Response::getInstance();
    if (empty($changes)) { // should never happen
        $response->isSuccess(false);
        exit;
    }
    $move_query = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ';
    $move_query .= implode(', ', $changes);
    // move columns
    $result = PMA_DBI_try_query($move_query);
    $tmp_error = PMA_DBI_getError();
    if ($tmp_error) {
        $response->isSuccess(false);
        $response->addJSON('message', PMA_Message::error($tmp_error));
    } else {
        $message = PMA_Message::success(
            __('The columns have been moved successfully.')
        );
        $response->addJSON('message', $message);
        $response->addJSON('columns', $column_names);
    }
    exit;
}
?>
