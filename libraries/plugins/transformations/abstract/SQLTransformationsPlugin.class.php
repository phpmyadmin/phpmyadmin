<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the SQL transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage SQL
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Provides common methods for all of the SQL transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class SQLTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Formats text as SQL query with syntax highlighting.'
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string $buffer  text to be transformed
     * @param array  $options transformation options
     * @param string $meta    meta information
     *
     * @return string
     */
    public function applyTransformation($buffer, $options = array(), $meta = '')
    {
        // see PMA_highlightSQL()
        $result = PMA_Util::formatSql($buffer);
        // Need to clear error state not to break subsequent queries display.
        PMA_SQP_resetError();
        return $result;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "SQL";
    }
}
?>
