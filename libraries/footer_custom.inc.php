<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// This file includes all custom footers if they exist.

// Include site footer
if (file_exists('./config.footer.inc.php')) {
    require('./config.footer.inc.php');
}

// Include theme footer
if (file_exists($GLOBALS['pmaThemePath'] . 'footer.inc.php')) {
    require($GLOBALS['pmaThemePath'] . 'footer.inc.php');
}
?>
