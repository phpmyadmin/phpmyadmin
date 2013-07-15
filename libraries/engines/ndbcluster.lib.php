<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The NDBCLUSTER storage engine
 *
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The NDBCLUSTER storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_ndbcluster extends PMA_StorageEngine
{
    /**
     * Returns array with variable names realted to NDBCLUSTER storage engine
     *
     * @return array   variable names
     */
    function getVariables()
    {
        return array(
            'ndb_connectstring' => array(
            ),
         );
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to NDBCLUSTER storage engine
     *
     * @return string  SQL query LIKE pattern
     */
    function getVariablesLikePattern()
    {
        return 'ndb\\_%';
    }

    /**
     * Returns string with filename for the MySQL help page
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'ndbcluster';
    }
}

?>
