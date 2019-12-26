<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Provides download to a given field defined in parameters.
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Mime;
use PhpMyAdmin\Response;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

$response->disable();

/* Check parameters */
PhpMyAdmin\Util::checkParameters(
    [
        'db',
        'table',
    ]
);

/* Select database */
if (! $dbi->selectDb($db)) {
    PhpMyAdmin\Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        false
    );
}

/* Check if table exists */
if (! $dbi->getColumns($db, $table)) {
    PhpMyAdmin\Util::mysqlDie(__('Invalid table name'));
}

/* Grab data */
$sql = 'SELECT ' . PhpMyAdmin\Util::backquote($_GET['transform_key'])
    . ' FROM ' . PhpMyAdmin\Util::backquote($table)
    . ' WHERE ' . $_GET['where_clause'] . ';';
$result = $dbi->fetchValue($sql);

/* Check return code */
if ($result === false) {
    PhpMyAdmin\Util::mysqlDie(
        __('MySQL returned an empty result set (i.e. zero rows).'),
        $sql
    );
}

/* Avoid corrupting data */
ini_set('url_rewriter.tags', '');

Core::downloadHeader(
    $table . '-' . $_GET['transform_key'] . '.bin',
    Mime::detect($result),
    strlen($result)
);
echo $result;
