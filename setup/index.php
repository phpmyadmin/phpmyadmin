<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for setup script
 *
 * @package PhpMyAdmin-Setup
 * @license https://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 */
declare(strict_types=1);

use PhpMyAdmin\Controllers\Setup\ConfigController;
use PhpMyAdmin\Controllers\Setup\FormController;
use PhpMyAdmin\Controllers\Setup\HomeController;
use PhpMyAdmin\Controllers\Setup\ServersController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

global $cfg;

require ROOT_PATH . 'setup/lib/common.inc.php';

if (@file_exists(CONFIG_FILE) && ! $cfg['DBG']['demo']) {
    Core::fatalError(__('Configuration already exists, setup is disabled!'));
}

$page = Core::isValid($_GET['page'], 'scalar') ? (string) $_GET['page'] : null;
$page = preg_replace('/[^a-z]/', '', $page);
if ($page === '') {
    $page = 'index';
}

Core::noCacheHeader();

if ($page === 'form') {
    $controller = new FormController($GLOBALS['ConfigFile'], new Template());
    echo $controller->index([
        'formset' => $_GET['formset'] ?? null,
    ]);
} elseif ($page === 'config') {
    $controller = new ConfigController($GLOBALS['ConfigFile'], new Template());
    echo $controller->index([
        'formset' => $_GET['formset'] ?? null,
        'eol' => $_GET['eol'] ?? null,
    ]);
} elseif ($page === 'servers') {
    $controller = new ServersController($GLOBALS['ConfigFile'], new Template());
    if (isset($_GET['mode']) && $_GET['mode'] === 'remove' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $controller->destroy([
            'id' => $_GET['id'] ?? null,
        ]);
        header('Location: index.php' . Url::getCommonRaw());
    } else {
        echo $controller->index([
            'formset' => $_GET['formset'] ?? null,
            'mode' => $_GET['mode'] ?? null,
            'id' => $_GET['id'] ?? null,
        ]);
    }
} else {
    $controller = new HomeController($GLOBALS['ConfigFile'], new Template());
    echo $controller->index([
        'formset' => $_GET['formset'] ?? null,
        'action_done' => $_GET['action_done'] ?? null,
        'version_check' => $_GET['version_check'] ?? null,
    ]);
}
