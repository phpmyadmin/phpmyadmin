<?php
/**
 * Handles the home page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder, $server, $show_query, $db, $table;

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var HomeController $controller */
$controller = $containerBuilder->get(HomeController::class);

if (isset($_REQUEST['ajax_request']) && ! empty($_REQUEST['access_time'])) {
    exit;
}

if (isset($_POST['set_theme'])) {
    $controller->setTheme([
        'set_theme' => $_POST['set_theme'],
    ]);

    header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
} elseif (isset($_POST['collation_connection'])) {
    $controller->setCollationConnection([
        'collation_connection' => $_POST['collation_connection'],
    ]);

    header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
} elseif ($response->isAjax() && ! empty($_REQUEST['recent_table'])) {
    $response->addJSON($controller->reloadRecentTablesList());
} elseif ($GLOBALS['PMA_Config']->isGitRevision()
    && isset($_REQUEST['git_revision'])
    && $response->isAjax()
) {
    $response->addHTML($controller->gitRevision());
} else {
    // Handles some variables that may have been sent by the calling script
    $db = '';
    $table = '';
    $show_query = '1';

    if ($server > 0) {
        include ROOT_PATH . 'libraries/server_common.inc.php';
    }

    $response->addHTML($controller->index());
}
