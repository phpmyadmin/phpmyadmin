<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The performance schema storage engine
 *
 * @package PhpMyAdmin-Engines
 */
namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The performance schema storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PerformanceSchema extends StorageEngine
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
