<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @version $Id$
 * @package phpMyAdmin-Engines
 */

/**
 * the MEMORY (HEAP) storage engine
 * @package phpMyAdmin-Engines
 */
class PMA_StorageEngine_memory extends PMA_StorageEngine
{
    /**
     * returns array with variable names dedicated to MyISAM storage engine
     *
     * @return  array   variable names
     */
    function getVariables()
    {
        return array(
            'max_heap_table_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
        );
    }
}

?>
