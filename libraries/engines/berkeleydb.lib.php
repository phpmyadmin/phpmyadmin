<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @package phpMyAdmin-Engines
 */

/**
 * Load BDB class.
 */
include_once './libraries/engines/bdb.lib.php';

/**
 * This is same as BDB.
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_berkeleydb extends PMA_StorageEngine_bdb
{
}

?>
