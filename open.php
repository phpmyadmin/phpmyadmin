<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * Gets some core libraries and displays a top message if required
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
if (strstr($get,$cfg['ThemePath'])) {
    $path_to_themes = './' . $cfg['ThemePath'] . '/';
}
if (@file_exists($get)) {
    include_once($get);
}
?>
