<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * finishes HTML output
 *
 * updates javascript variables in index.php for correct working with querywindow
 * and navigation frame refreshing
 *
 * send buffered data if buffered
 *
 * WARNING: This script has to be included at the very end of your code because
 *          it will stop the script execution!
 *
 * always use $GLOBALS, as this script is also included by functions
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$footer = PMA_Footer::getInstance()->display();
?>
