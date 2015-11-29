<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The binary log storage engine
 *
 * @package PhpMyAdmin-Engines
 */
namespace PMA\libraries\engines;

use PMA\libraries\StorageEngine;

/**
 * The binary log storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class Binlog extends StorageEngine
{
    /**
     * Returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'binary-log';
    }
}

