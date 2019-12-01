<?php
/**
 * Format SQL for SQL editors
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Response;

if (! defined('PHPMYADMIN')) {
    exit;
}

$query = ! empty($_POST['sql']) ? $_POST['sql'] : '';

$query = PhpMyAdmin\SqlParser\Utils\Formatter::format($query);

$response = Response::getInstance();
$response->addJSON('sql', $query);
