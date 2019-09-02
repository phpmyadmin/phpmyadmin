<?php
/**
 * display selection for relational field values
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Controllers\BrowseForeignersController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder;

Util::checkParameters(['db', 'table', 'field'], true);

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var Template $template */
$template = $containerBuilder->get('template');
/* Register BrowseForeignersController dependencies */
$containerBuilder->set(
    'browse_foreigners',
    new BrowseForeigners(
        $GLOBALS['cfg']['LimitChars'],
        $GLOBALS['cfg']['MaxRows'],
        $GLOBALS['cfg']['RepeatCells'],
        $GLOBALS['cfg']['ShowAll'],
        $GLOBALS['pmaThemeImage'],
        $template
    )
);

/** @var BrowseForeignersController $controller */
$controller = $containerBuilder->get(BrowseForeignersController::class);

$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->disableMenuAndConsole();
$header->setBodyId('body_browse_foreigners');

$response->addHTML($controller->index([
    'db' => $_POST['db'] ?? null,
    'table' => $_POST['table'] ?? null,
    'field' => $_POST['field'] ?? null,
    'fieldkey' => $_POST['fieldkey'] ?? null,
    'data' => $_POST['data'] ?? null,
    'foreign_showAll' => $_POST['foreign_showAll'] ?? null,
    'foreign_filter' => $_POST['foreign_filter'] ?? null,
]));
