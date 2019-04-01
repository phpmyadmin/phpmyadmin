<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table SQL executor
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Table\SqlController;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

$response = Response::getInstance();

$controller = new SqlController(
    $response,
    $GLOBALS['dbi'],
    $db,
    $table
);

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

$response->addHTML($controller->index([
    'delimiter' => $_POST['delimiter'] ?? null,
    'sql_query' => $_GET['sql_query'] ?? true,
]));
