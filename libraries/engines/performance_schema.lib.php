<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_performance_schema extends PMA_StorageEngine
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'performance-schema';
    }
}

?>
