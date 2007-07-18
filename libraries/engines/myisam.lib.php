<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 */

/**
 * the MyISAM storage engine
 */
class PMA_StorageEngine_myisam extends PMA_StorageEngine
{
    /**
     * returns array with variable names dedicated to MyISAM storage engine
     *
     * @return  array   variable names
     */
    function getVariables()
    {
        return array(
            'myisam_data_pointer_size' => array(
                'title' => $GLOBALS['strMyISAMDataPointerSize'],
                'desc'  => $GLOBALS['strMyISAMDataPointerSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'myisam_recover_options' => array(
                'title' => $GLOBALS['strMyISAMRecoverOptions'],
                'desc'  => $GLOBALS['strMyISAMRecoverOptionsDesc'],
            ),
            'myisam_max_sort_file_size' => array(
                'title' => $GLOBALS['strMyISAMMaxSortFileSize'],
                'desc'  => $GLOBALS['strMyISAMMaxSortFileSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'myisam_max_extra_sort_file_size' => array(
                'title' => $GLOBALS['strMyISAMMaxExtraSortFileSize'],
                'desc'  => $GLOBALS['strMyISAMMaxExtraSortFileSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'myisam_repair_threads' => array(
                'title' => $GLOBALS['strMyISAMRepairThreads'],
                'desc'  => $GLOBALS['strMyISAMRepairThreadsDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC,
            ),
            'myisam_sort_buffer_size' => array(
                'title' => $GLOBALS['strMyISAMSortBufferSize'],
                'desc'  => $GLOBALS['strMyISAMSortBufferSizeDesc'],
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'myisam_stats_method' => array(
            ),
            'delay_key_write' => array(
            ),
            'bulk_insert_buffer_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
            'skip_external_locking' => array(
            ),
        );
    }
}

?>
