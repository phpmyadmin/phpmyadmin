<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Format SQL for SQL editors
 *
 * @package PhpMyAdmin
 */

/**
 * Loading common files. Used to check for authorization, localization and to
 * load the parsing library.
 */
require_once 'libraries/common.inc.php';

$query = !empty($_POST['sql']) ? $_POST['sql'] : '';

$query = SqlParser\Utils\Formatter::format($query);

$response = PMA\libraries\Response::getInstance();
$response->addJSON("sql", $query);
