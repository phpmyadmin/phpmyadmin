<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The NDBCLUSTER storage engine
 *
 * @package PhpMyAdmin-Engines
 */
namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The NDBCLUSTER storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class Ndbcluster extends StorageEngine
{
    /**
     * Returns array with variable names related to NDBCLUSTER storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return array(
            'ndb_connectstring' => array(),
        );
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to NDBCLUSTER storage engine
     *
     * @return string  SQL query LIKE pattern
     */
    public function getVariablesLikePattern()
    {
        return 'ndb\\_%';
    }

    /**
     * Returns string with filename for the MySQL help page
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    public function getMysqlHelpPage()
    {
        return 'ndbcluster';
    }
}

