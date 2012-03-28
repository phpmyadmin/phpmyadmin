<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple wrapper just to enable error reporting and include config
 *
 * @package phpMyAdmin
 */

require './libraries/vendor_config.php';

echo "Starting to parse config file...\n";

error_reporting(E_ALL);
/**
 * Read config file.
 */
if (is_readable(CONFIG_FILE)) {
    require CONFIG_FILE;
}

?>
