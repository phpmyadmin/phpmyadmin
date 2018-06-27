<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of translated messages from PHP to Javascript
 *
 * @package PhpMyAdmin
 */

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

// But this one is needed for Sanitize::escapeJsString()
use PhpMyAdmin\Header;
use PhpMyAdmin\Sanitize;
// use PhpMyAdmin\Scripts;

$header = new Header();
// $scripts = new Scripts();

echo "var common_params = new Array();\n";
foreach ($header->getJsParams() as $name => $value) {
    Sanitize::printJsValue("common_params['" . $name . "']", $value);
}

// echo "var AJAX_Params = new Array();\n";
// foreach ($header->getDisplay() as $name => $value) {
//     Sanitize::printJsValue("AJAX_Params['" . $name . "']", $value);
// }

?>