<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charsets listings
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

define('PMA_CSDROPDOWN_COLLATION', 0);
define('PMA_CSDROPDOWN_CHARSET',   1);

/**
 * shared functions for mysql charsets
 */
require_once './libraries/mysql_charsets.lib.php';

