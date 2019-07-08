<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main loader script
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\HomeController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $server;

require_once ROOT_PATH . 'libraries/common.inc.php';

/**
 * pass variables to child pages
 */
$drops = [
    'lang',
    'server',
    'collation_connection',
    'db',
    'table',
];
foreach ($drops as $each_drop) {
    if (array_key_exists($each_drop, $_GET)) {
        unset($_GET[$each_drop]);
    }
}
unset($drops, $each_drop);

/**
 * Black list of all scripts to which front-end must submit data.
 * Such scripts must not be loaded on home page.
 */
$target_blacklist =  [
    'import.php',
    'export.php',
];

// If we have a valid target, let's load that script instead
if (! empty($_REQUEST['target'])
    && is_string($_REQUEST['target'])
    && 0 !== strpos($_REQUEST['target'], "index")
    && ! in_array($_REQUEST['target'], $target_blacklist)
    && Core::checkPageValidity($_REQUEST['target'], [], true)
) {
    include ROOT_PATH . $_REQUEST['target'];
    exit;
}

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

    header('Location: index.php' . Url::getCommonRaw());
} elseif (isset($_POST['collation_connection'])) {
    $controller->setCollationConnection([
        'collation_connection' => $_POST['collation_connection'],
    ]);

    header('Location: index.php' . Url::getCommonRaw());
} elseif (! empty($_REQUEST['db'])) {
    // See FAQ 1.34
    $page = null;
    if (! empty($_REQUEST['table'])) {
        $page = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabTable'],
            'table'
        );
    } else {
        $page = Util::getScriptNameForOption(
            $GLOBALS['cfg']['DefaultTabDatabase'],
            'database'
        );
    }
    include ROOT_PATH . $page;
} elseif ($response->isAjax() && ! empty($_REQUEST['recent_table'])) {
    $response->addJSON($controller->reloadRecentTablesList());
} elseif ($GLOBALS['PMA_Config']->isGitRevision()
    && isset($_REQUEST['git_revision'])
    && $response->isAjax()
) {
    $response->addHTML($controller->gitRevision());
} else {
    // Handles some variables that may have been sent by the calling script
    $GLOBALS['db'] = '';
    $GLOBALS['table'] = '';
    $show_query = '1';

    if ($server > 0) {
        include ROOT_PATH . 'libraries/server_common.inc.php';
    }

    $response->addHTML($controller->index());
}
