<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting of common params defined in Header.php to make them available in
 * the global window object and then serialize these variables in the modules.
 *
 * @package PhpMyAdmin
 */

if (!defined('TESTSUITE')) {
    chdir('..');

    // Send correct type:
    header('Content-Type: text/javascript; charset=UTF-8');

    // Preventing caching of this file
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");

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

$header = new Header();

echo "var common_params = new Array();\n";
foreach ($header->getJsParams() as $name => $value) {
    Sanitize::printJsValue("common_params['" . $name . "']", $value);
}
