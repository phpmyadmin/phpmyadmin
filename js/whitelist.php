<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of Core::$goto_whitelist from PHP to Javascript
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\OutputBuffering;

if (!defined('TESTSUITE')) {
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
    require_once './libraries/common.inc.php';
    // Close session early as we won't write anything there
    session_write_close();
}

$buffer = OutputBuffering::getInstance();
$buffer->start();
if (!defined('TESTSUITE')) {
    register_shutdown_function(
        function () {
            echo OutputBuffering::getInstance()->getContents();
        }
    );
}

echo "var PMA_gotoWhitelist = new Array();\n";
$i = 0;
foreach (Core::$goto_whitelist as $one_whitelist) {
    echo 'PMA_gotoWhitelist[' , $i , ']="' , $one_whitelist , '";' , "\n";
    $i++;
}
