<?php

declare(strict_types=1);

use PhpMyAdmin\Controllers\JavaScriptMessagesController;
use PhpMyAdmin\OutputBuffering;

global $containerBuilder;

if (! defined('ROOT_PATH')) {
    // phpcs:disable PSR1.Files.SideEffects
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
    // phpcs:enable
}

chdir('..');

// Send correct type.
header('Content-Type: text/javascript; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this file is reloaded when config changes.
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Avoid loading the full common.inc.php because this would add many non-js-compatible stuff like DOCTYPE.
// phpcs:disable PSR1.Files.SideEffects
define('PMA_MINIMUM_COMMON', true);
define('PMA_PATH_TO_BASEDIR', '../');
define('PMA_NO_SESSION', true);
// phpcs:enable

require_once ROOT_PATH . 'libraries/common.inc.php';

$buffer = OutputBuffering::getInstance();
$buffer->start();

register_shutdown_function(static function () {
    echo OutputBuffering::getInstance()->getContents();
});

/** @var JavaScriptMessagesController $controller */
$controller = $containerBuilder->get(JavaScriptMessagesController::class);
$controller->index();
