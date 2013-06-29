<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Concatenates reveral js files to reduce the number of
 * http requests sent to the server
 *
 * @package PhpMyAdmin
 */

chdir('..');

// Avoid loading the full common.inc.php because this would add many
// non-js-compatible stuff like DOCTYPE
define('PMA_MINIMUM_COMMON', true);
require_once './libraries/common.inc.php';
// Close session early as we won't write anything there
session_write_close();

// Send correct type
header('Content-Type: text/javascript; charset=UTF-8');
// Enable browser cache for 1 hour //not
// ================WARNING=================
// This must be changed back before merging
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');

if (! empty($_GET['scripts']) && is_array($_GET['scripts'])) {
    foreach ($_GET['scripts'] as $script) {
        // Sanitise filename
        $script_name = 'js';

        $path = explode("/", $script);
        foreach ($path as $index => $filename) {
            if (! preg_match("@^\.+$@", $filename)
                && preg_match("@^[\w\.-]+$@", $filename)
            ) {
                // Disallow "." and ".." alone
                // Allow alphanumeric, "." and "-" chars only
                $script_name .= DIRECTORY_SEPARATOR . $filename;
            }
        }
        // if error reporting is enabled serve the unminified files
        if($GLOBALS['cfg']['ErrorReporting']) {
            $unminified = "sources" . DIRECTORY_SEPARATOR .  $script_name;
            // only serve the unminified files if they exist
            if (is_readable($unminified)) {
                $script_name = $unminified;
            }
        }

        // Output file contents
        if (preg_match("@\.js$@", $script_name) && is_readable($script_name)) {
            readfile($script_name);
            echo ";\n\n";
        }
    }
}
?>
