<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Engines
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load BDB class.
 */
require_once './libraries/engines/bdb.lib.php';

/**
 * This is same as BDB.
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_berkeleydb extends PMA_StorageEngine_bdb
{
}

?>
