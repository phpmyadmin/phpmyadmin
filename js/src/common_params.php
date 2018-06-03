<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Exporting the common parameters to be used within the javascript
 * code like serrver information, database name, table name, dev server port,
 * working environment etc.
 *
 * @package PhpMyAdmin
 */

if (!defined('TESTSUITE')) {
    chdir('../../');

    // Send correct type:
    header('Content-Type: text/javascript; charset=UTF-8');

    // Cache output in client - the nocache query parameter makes sure that this
    // file is reloaded when config changes
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

    // Avoid loading the full common.inc.php because this would add many
    // non-js-compatible stuff like DOCTYPE
    define('PMA_MINIMUM_COMMON', true);
    define('PMA_PATH_TO_BASEDIR', '../../');
    require_once './libraries/common.inc.php';
    // Close session early as we won't write anything there
    session_write_close();
}

// This one is needed for loading parameters data from php Header class
use PhpMyAdmin\Header;
use PhpMyAdmin\Sanitize;

$header = new Header();

/**
 * @param Array javascript array containig the common parameters to be
 * imported in javascript code.
 */
echo "var common_params = new Array();\n";
foreach ($header->getJsParams() as $name => $value) {
    Sanitize::printJsValue("common_params['" . $name . "']", $value);
}

?>