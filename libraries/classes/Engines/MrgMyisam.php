<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The MERGE storage engine
 *
 * @package PhpMyAdmin-Engines
 */
declare(strict_types=1);

namespace PhpMyAdmin\Engines;

/**
 * The MERGE storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class MrgMyisam extends Merge
{
    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'merge-storage-engine';
    }
}
