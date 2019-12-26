<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Central Columns view/edit
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

use PhpMyAdmin\CentralColumns;
use PhpMyAdmin\Controllers\Database\CentralColumnsController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var CentralColumns $centralColumns */
$centralColumns = $containerBuilder->get('central_columns');

/** @var CentralColumnsController $controller */
$controller = $containerBuilder->get(CentralColumnsController::class);

/** @var string $db */
$db = $containerBuilder->getParameter('db');

if (isset($_POST['edit_save'])) {
    echo $controller->editSave([
        'col_name' => $_POST['col_name'] ?? null,
        'orig_col_name' => $_POST['orig_col_name'] ?? null,
        'col_default' => $_POST['col_default'] ?? null,
        'col_default_sel' => $_POST['col_default_sel'] ?? null,
        'col_extra' => $_POST['col_extra'] ?? null,
        'col_isNull' => $_POST['col_isNull'] ?? null,
        'col_length' => $_POST['col_length'] ?? null,
        'col_attribute' => $_POST['col_attribute'] ?? null,
        'col_type' => $_POST['col_type'] ?? null,
        'collation' => $_POST['collation'] ?? null,
    ]);
    exit;
} elseif (isset($_POST['add_new_column'])) {
    $tmp_msg = $controller->addNewColumn([
        'col_name' => $_POST['col_name'] ?? null,
        'col_default' => $_POST['col_default'] ?? null,
        'col_default_sel' => $_POST['col_default_sel'] ?? null,
        'col_extra' => $_POST['col_extra'] ?? null,
        'col_isNull' => $_POST['col_isNull'] ?? null,
        'col_length' => $_POST['col_length'] ?? null,
        'col_attribute' => $_POST['col_attribute'] ?? null,
        'col_type' => $_POST['col_type'] ?? null,
        'collation' => $_POST['collation'] ?? null,
    ]);
}
if (isset($_POST['populateColumns'])) {
    $response->addHTML($controller->populateColumns([
        'selectedTable' => $_POST['selectedTable'],
    ]));
    exit;
}
if (isset($_POST['getColumnList'])) {
    $response->addJSON($controller->getColumnList([
        'cur_table' => $_POST['cur_table'] ?? null,
    ]));
    exit;
}
if (isset($_POST['add_column'])) {
    $tmp_msg = $controller->addColumn([
        'table-select' => $_POST['table-select'] ?? null,
        'column-select' => $_POST['column-select'] ?? null,
    ]);
}

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('database/central_columns.js');

if (isset($_POST['edit_central_columns_page'])) {
    $response->addHTML($controller->editPage([
        'selected_fld' => $_POST['selected_fld'] ?? null,
        'db' => $_POST['db'] ?? null,
    ]));
    exit;
}
if (isset($_POST['multi_edit_central_column_save'])) {
    $message = $controller->updateMultipleColumn([
        'db' => $_POST['db'] ?? null,
        'orig_col_name' => $_POST['orig_col_name'] ?? null,
        'field_name' => $_POST['field_name'] ?? null,
        'field_default_type' => $_POST['field_default_type'] ?? null,
        'field_default_value' => $_POST['field_default_value'] ?? null,
        'field_length' => $_POST['field_length'] ?? null,
        'field_attribute' => $_POST['field_attribute'] ?? null,
        'field_type' => $_POST['field_type'] ?? null,
        'field_collation' => $_POST['field_collation'] ?? null,
        'field_null' => $_POST['field_null'] ?? null,
        'col_extra' => $_POST['col_extra'] ?? null,
    ]);
    if (! is_bool($message)) {
        $response->setRequestStatus(false);
        $response->addJSON('message', $message);
    }
}
if (isset($_POST['delete_save'])) {
    $tmp_msg = $controller->deleteSave([
        'db' => $_POST['db'] ?? null,
        'col_name' => $_POST['col_name'] ?? null,
    ]);
}

$response->addHTML($controller->index([
    'pos' => $_POST['pos'] ?? null,
    'total_rows' => $_POST['total_rows'] ?? null,
]));

$pos = 0;
if (Core::isValid($_POST['pos'], 'integer')) {
    $pos = (int) $_POST['pos'];
}
$num_cols = $centralColumns->getColumnsCount(
    $db,
    $pos,
    (int) $GLOBALS['cfg']['MaxRows']
);
$message = Message::success(
    sprintf(__('Showing rows %1$s - %2$s.'), $pos + 1, $pos + $num_cols)
);
if (isset($tmp_msg) && $tmp_msg !== true) {
    $message = $tmp_msg;
}
