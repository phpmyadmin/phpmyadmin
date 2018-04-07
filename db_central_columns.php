<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Central Columns view/edit
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

$centralColumns = new CentralColumns($GLOBALS['dbi']);

if (isset($_POST['edit_save']) || isset($_POST['add_new_column'])) {
    $col_name = $_POST['col_name'];
    if (isset($_POST['edit_save'])) {
        $orig_col_name = $_POST['orig_col_name'];
    }
    $col_default = $_POST['col_default'];
    if ($col_default == 'NONE' && $_POST['col_default_sel'] != 'USER_DEFINED') {
        $col_default = "";
    }
    $col_extra = isset($_POST['col_extra']) ? $_POST['col_extra'] : '';
    $col_isNull = isset($_POST['col_isNull'])?1:0;
    $col_length = $_POST['col_length'];
    $col_attribute = $_POST['col_attribute'];
    $col_type = $_POST['col_type'];
    $collation = $_POST['collation'];
    if (isset($orig_col_name) && $orig_col_name) {
        echo $centralColumns->updateOneColumn(
            $db, $orig_col_name, $col_name, $col_type, $col_attribute,
            $col_length, $col_isNull, $collation, $col_extra, $col_default
        );
        exit;
    } else {
        $tmp_msg = $centralColumns->updateOneColumn(
            $db, "", $col_name, $col_type, $col_attribute,
            $col_length, $col_isNull, $collation, $col_extra, $col_default
        );
    }
}
if (isset($_POST['populateColumns'])) {
    $selected_tbl = $_POST['selectedTable'];
    echo $centralColumns->getHtmlForColumnDropdown(
        $db,
        $selected_tbl
    );
    exit;
}
if (isset($_POST['getColumnList'])) {
    echo $centralColumns->getListRaw(
        $db,
        $_POST['cur_table']
    );
    exit;
}
if (isset($_POST['add_column'])) {
    $selected_col = array();
    $selected_tbl = $_POST['table-select'];
    $selected_col[] = $_POST['column-select'];
    $tmp_msg = $centralColumns->syncUniqueColumns(
        $selected_col,
        false,
        $selected_tbl
    );
}
$response = Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('db_central_columns.js');
$cfgCentralColumns = $centralColumns->getParams();
$pmadb = $cfgCentralColumns['db'];
$pmatable = $cfgCentralColumns['table'];
$max_rows = intval($GLOBALS['cfg']['MaxRows']);

if (isset($_REQUEST['edit_central_columns_page'])) {
    $selected_fld = $_REQUEST['selected_fld'];
    $selected_db = $_REQUEST['db'];
    $edit_central_column_page = $centralColumns->getHtmlForEditingPage(
        $selected_fld,
        $selected_db
    );
    $response->addHTML($edit_central_column_page);
    exit;
}
if (isset($_POST['multi_edit_central_column_save'])) {
    $message = $centralColumns->updateMultipleColumn();
    if (!is_bool($message)) {
        $response->setRequestStatus(false);
        $response->addJSON('message', $message);
    }
}
if (isset($_POST['delete_save'])) {
    $col_name = array();
    parse_str($_POST['col_name'], $col_name);
    $tmp_msg = $centralColumns->deleteColumnsFromList(
        $col_name['selected_fld'],
        false
    );
}
if (!empty($_REQUEST['total_rows'])
    && Core::isValid($_REQUEST['total_rows'], 'integer')
) {
    $total_rows = $_REQUEST['total_rows'];
} else {
    $total_rows = $centralColumns->getCount($db);
}
if (Core::isValid($_REQUEST['pos'], 'integer')) {
    $pos = intval($_REQUEST['pos']);
} else {
    $pos = 0;
}
$addNewColumn = $centralColumns->getHtmlForAddNewColumn($db, $total_rows);
$response->addHTML($addNewColumn);
if ($total_rows <= 0) {
    $response->addHTML(
        '<fieldset>' . __(
            'The central list of columns for the current database is empty.'
        ) . '</fieldset>'
    );
    $columnAdd = $centralColumns->getHtmlForAddColumn($total_rows, $pos, $db);
    $response->addHTML($columnAdd);
    exit;
}
$table_navigation_html = $centralColumns->getHtmlForTableNavigation(
    $total_rows,
    $pos,
    $db
);
$response->addHTML($table_navigation_html);
$columnAdd = $centralColumns->getHtmlForAddColumn($total_rows, $pos, $db);
$response->addHTML($columnAdd);
$deleteRowForm = '<form method="post" id="del_form" action="db_central_columns.php">'
        . Url::getHiddenInputs(
            $db
        )
        . '<input id="del_col_name" type="hidden" name="col_name" value="">'
        . '<input type="hidden" name="pos" value="' . $pos . '">'
        . '<input type="hidden" name="delete_save" value="delete"></form>';
$response->addHTML($deleteRowForm);
$table_struct = '<div id="tableslistcontainer">'
        . '<form name="tableslistcontainer">'
        . '<table id="table_columns" class="tablesorter" '
        . 'class="data">';
$response->addHTML($table_struct);
$tableheader = $centralColumns->getTableHeader(
    'column_heading', __('Click to sort.'), 2
);
$response->addHTML($tableheader);
$result = $centralColumns->getColumnsList($db, $pos, $max_rows);
$row_num = 0;
foreach ($result as $row) {
    $tableHtmlRow = $centralColumns->getHtmlForTableRow(
        $row,
        $row_num,
        $db
    );
    $response->addHTML($tableHtmlRow);
    $row_num++;
}
$response->addHTML('</table>');
$tablefooter = $centralColumns->getTableFooter($pmaThemeImage, $text_dir);
$response->addHTML($tablefooter);
$response->addHTML('</form></div>');
$message = Message::success(
    sprintf(__('Showing rows %1$s - %2$s.'), ($pos + 1), ($pos + count($result)))
);
if (isset($tmp_msg) && $tmp_msg !== true) {
    $message = $tmp_msg;
}
