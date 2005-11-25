<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// This file includes all custom headers if they exist.

// Include theme header
if (file_exists($GLOBALS['pmaThemePath'] . 'header.inc.php')) {
    require($GLOBALS['pmaThemePath'] . 'header.inc.php');
}

// Include site header
if (file_exists('./config.header.inc.php')) {
    require('./config.header.inc.php');
}
?>
