<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/engines/merge.lib.php';

/**
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_mrg_myisam extends PMA_StorageEngine_merge
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'merge-storage-engine';
    }
}

?>
