<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Main stylesheet loader
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\OutputBuffering;
use PhpMyAdmin\ThemeManager;

/**
 *
 */

define('PMA_MINIMUM_COMMON', true);
require_once 'libraries/common.inc.php';


$buffer = OutputBuffering::getInstance();
$buffer->start();
register_shutdown_function(
    function () {
        echo OutputBuffering::getInstance()->getContents();
    }
);

// Send correct type:
header('Content-Type: text/css; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

ThemeManager::getInstance()->printCss();
