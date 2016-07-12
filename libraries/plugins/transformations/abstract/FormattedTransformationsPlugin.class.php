<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the formatted transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Formatted
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';

/**
 * Provides common methods for all of the formatted transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class FormattedTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays the contents of the column as-is, without running it'
            . ' through htmlspecialchars(). That is, the column is assumed'
            . ' to contain valid HTML.'
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
        return '<iframe srcdoc="'
            . strtr($buffer, '"', '\'')
            . '" sandbox=""></iframe>';
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Formatted";
    }
}
?>
