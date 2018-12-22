<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Format SQL for SQL editors
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Loading common files. Used to check for authorization, localization and to
 * load the parsing library.
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

$query = ! empty($_POST['sql']) ? $_POST['sql'] : '';

$query = PhpMyAdmin\SqlParser\Utils\Formatter::format($query);

$response = Response::getInstance();
$response->addJSON("sql", $query);
