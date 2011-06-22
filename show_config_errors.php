<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple wrapper just to enable error reporting and include config
 *
 * @package phpMyAdmin
 */

require './libraries/vendor_config.php';

error_reporting(E_ALL);
/**
 * Read config file.
 */
require CONFIG_FILE;

?>
