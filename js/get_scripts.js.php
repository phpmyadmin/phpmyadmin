<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Concatenates several js files to reduce the number of
 * http requests sent to the server
 *
 * @package PhpMyAdmin
 */

if (!defined('TESTSUITE')) {
    chdir('..');

    // Close session early as we won't write anything there
    session_write_close();

    // Send correct type
    header('Content-Type: text/javascript; charset=UTF-8');
    // Enable browser cache for 1 hour
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

    // When a token is not presented, even though whitelisted arrays are removed
    // in PMA_removeRequestVars(). This is a workaround for that.
    $_GET['scripts'] = json_encode($_GET['scripts']);

    // Avoid loading the full common.inc.php because this would add many
    // non-js-compatible stuff like DOCTYPE
    define('PMA_MINIMUM_COMMON', true);
    define('PMA_PATH_TO_BASEDIR', '../');
    require_once './libraries/common.inc.php';
}

$buffer = PMA\libraries\OutputBuffering::getInstance();
$buffer->start();
if (!defined('TESTSUITE')) {
    register_shutdown_function(
        function () {
            echo PMA\libraries\OutputBuffering::getInstance()->getContents();
        }
    );
}

$_GET['scripts'] = json_decode($_GET['scripts']);
if (! empty($_GET['scripts']) && is_array($_GET['scripts'])) {
    // Only up to 10 scripts as this is what we generate
    foreach (array_slice($_GET['scripts'], 0, 10) as $script) {
        // Sanitise filename
        $script_name = 'js';

        $path = explode("/", $script);
        foreach ($path as $filename) {
            // Allow alphanumeric, "." and "-" chars only, no files starting
            // with .
            if (preg_match("@^[\w][\w\.-]+$@", $filename)) {
                $script_name .= DIRECTORY_SEPARATOR . $filename;
            }
        }

        // Output file contents
        if (preg_match("@\.js$@", $script_name) && is_readable($script_name)) {
            readfile($script_name);
            echo ";\n\n";
        }
    }
}

if (isset($_GET['call_done'])) {
    echo "AJAX.scriptHandler.done();";
}
