<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Format SQL for SQL editors
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/sql-formatter/lib/SqlFormatter.php';

$query = isset($_POST['sql']) ? $_POST['sql'] : '';

SqlFormatter::$tab = "\t";
$query = SqlFormatter::format($query, false);

$response = PMA_Response::getInstance();
$response->addJSON("sql", $query);
