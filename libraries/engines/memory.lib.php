<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * The MEMORY (HEAP) storage engine
 *
 * @package PhpMyAdmin-Engines
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * The MEMORY (HEAP) storage engine
 *
 * @package PhpMyAdmin-Engines
 */
class PMA_StorageEngine_Memory extends PMA_StorageEngine
{
    /**
     * Returns array with variable names dedicated to MEMORY storage engine
     *
     * @return array   variable names
     */
    public function getVariables()
    {
        return array(
            'max_heap_table_size' => array(
                'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE,
            ),
        );
    }
}

