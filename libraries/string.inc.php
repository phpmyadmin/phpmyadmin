<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Include specialized String handling for phpMyAdmin
 *
 * @package PhpMyAdmin-String
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Include the string handling class
 */
require_once 'libraries/String.class.php';

$PMA_String = new PMA_String();
?>
