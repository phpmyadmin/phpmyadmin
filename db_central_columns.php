<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Central Columns view/edit
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

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
    $col_isNull = isset($_POST['col_isNull']) ? 1 : 0;
    $col_length = $_POST['col_length'];
    $col_attribute = $_POST['col_attribute'];
    $col_type = $_POST['col_type'];
    $collation = $_POST['collation'];
    if (isset($orig_col_name) && $orig_col_name) {
        echo $centralColumns->updateOneColumn(
            $db,
            $orig_col_name,
            $col_name,
            $col_type,
            $col_attribute,
            $col_length,
            $col_isNull,
            $collation,
            $col_extra,
            $col_default
        );
        exit;
    } else {
        $tmp_msg = $centralColumns->updateOneColumn(
            $db,
            "",
            $col_name,
            $col_type,
            $col_attribute,
            $col_length,
            $col_isNull,
            $collation,
            $col_extra,
            $col_default
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
    $selected_col = [];
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

if (isset($_POST['edit_central_columns_page'])) {
    $selected_fld = $_POST['selected_fld'];
    $selected_db = $_POST['db'];
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
    $col_name = [];
    parse_str($_POST['col_name'], $col_name);
    $tmp_msg = $centralColumns->deleteColumnsFromList(
        $col_name['selected_fld'],
        false
    );
}
if (!empty($_POST['total_rows'])
    && Core::isValid($_POST['total_rows'], 'integer')
) {
    $total_rows = $_POST['total_rows'];
} else {
    $total_rows = $centralColumns->getCount($db);
}
if (Core::isValid($_POST['pos'], 'integer')) {
    $pos = intval($_POST['pos']);
} else {
    $pos = 0;
}
$main = $centralColumns->getHtmlForMain($db, $total_rows, $pos, $pmaThemeImage, $text_dir);
$response->addHTML($main);

$num_cols = $centralColumns->getColumnsCount($db, $pos, $max_rows);
$message = Message::success(
    sprintf(__('Showing rows %1$s - %2$s.'), ($pos + 1), ($pos + $num_cols))
);
if (isset($tmp_msg) && $tmp_msg !== true) {
    $message = $tmp_msg;
}
