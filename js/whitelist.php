<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of Core::$goto_whitelist from PHP to Javascript
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\OutputBuffering;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

if (! defined('TESTSUITE')) {
    chdir('..');

    // Send correct type:
    header('Content-Type: text/javascript; charset=UTF-8');

    // Cache output in client - the nocache query parameter makes sure that this
    // file is reloaded when config changes
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

    // Avoid loading the full common.inc.php because this would add many
    // non-js-compatible stuff like DOCTYPE
    define('PMA_MINIMUM_COMMON', true);
    define('PMA_PATH_TO_BASEDIR', '../');
    require_once ROOT_PATH . 'libraries/common.inc.php';
    // Close session early as we won't write anything there
    session_write_close();
}

$buffer = OutputBuffering::getInstance();
$buffer->start();
if (! defined('TESTSUITE')) {
    register_shutdown_function(
        function () {
            echo OutputBuffering::getInstance()->getContents();
        }
    );
}

echo "var GotoWhitelist = [];\n";
$i = 0;
foreach (Core::$goto_whitelist as $one_whitelist) {
    echo 'GotoWhitelist[' , $i , '] = \'' , $one_whitelist , '\';' , "\n";
    $i++;
}
