<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Concatenates several js files to reduce the number of
 * http requests sent to the server
 *
 * @package PhpMyAdmin
 */

chdir('..');

// Close session early as we won't write anything there
session_write_close();

// Send correct type
header('Content-Type: text/javascript; charset=UTF-8');
// Enable browser cache for 1 hour
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

if (! empty($_GET['scripts']) && is_array($_GET['scripts'])) {
    foreach ($_GET['scripts'] as $script) {
        // Sanitise filename
        $script_name = 'js';

        $path = explode("/", $script);
        foreach ($path as $index => $filename) {
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
?>
