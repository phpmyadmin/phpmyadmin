<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of $goto_whitelist from PHP to Javascript
 *
 * @package PhpMyAdmin
 */

chdir('..');

// Send correct type:
header('Content-Type: text/javascript; charset=UTF-8');

// Cache output in client - the nocache query parameter makes sure that this
// file is reloaded when config changes
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// Avoid loading the full common.inc.php because this would add many
// non-js-compatible stuff like DOCTYPE
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
// Close session early as we won't write anything there
session_write_close();

$buffer = PMA\libraries\OutputBuffering::getInstance();
$buffer->start();
register_shutdown_function(
    function () {
        echo PMA\libraries\OutputBuffering::getInstance()->getContents();
    }
);

echo "var PMA_gotoWhitelist = new Array();\n";
$i = -1;
foreach ($GLOBALS['goto_whitelist'] as $one_whitelist) {
    $i++;
    echo 'PMA_gotoWhitelist[' , $i , ']="' , $one_whitelist , '";' , "\n";
}
