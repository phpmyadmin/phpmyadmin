<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @package phpMyAdmin-Engines
 */

/**
 *
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_bdb extends PMA_StorageEngine
{
    /**
     * @return  array   variable names
     */
    function getVariables()
    {
        return array(
            'version_bdb' => array(
                'title' => $GLOBALS['strVersionInformation'],
            ),
            'bdb_cache_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'bdb_home' => array(
            ),
            'bdb_log_buffer_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'bdb_logdir' => array(
            ),
            'bdb_max_lock' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'bdb_shared_data' => array(
            ),
            'bdb_tmpdir' => array(
            ),
            'bdb_data_direct' => array(
            ),
            'bdb_lock_detect' => array(
            ),
            'bdb_log_direct' => array(
            ),
            'bdb_no_recover' => array(
            ),
            'bdb_no_sync' => array(
            ),
            'skip_sync_bdb_logs' => array(
            ),
            'sync_bdb_logs' => array(
            ),
        );
    }

    /**
     * @return string   LIKE pattern
     */
    function getVariablesLikePattern()
    {
        return '%bdb%';
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return 'bdb';
    }
}

?>
