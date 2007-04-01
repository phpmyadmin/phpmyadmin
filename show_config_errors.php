<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Simple wrapper just to enable error reporting and include config
 *
 * @version $Id$
 */

/**
 *
 */
echo "Starting to parse config file...\n";

error_reporting(E_ALL);
require './config.inc.php';

?>
