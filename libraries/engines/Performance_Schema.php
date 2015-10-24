<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The performance schema storage engine
 *
 * @package PhpMyAdmin-Engines
 */
namespace PMA\libraries\engines;

use PMA\libraries\StorageEngine;

/**
 * The performance schema storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class Performance_Schema extends StorageEngine
{
    /**
     * Returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'performance-schema';
    }
}

