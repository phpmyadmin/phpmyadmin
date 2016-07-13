<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the inline transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage Inline
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';
/* For PMA_Transformation_globalHtmlReplace */
require_once 'libraries/transformations.lib.php';

/**
 * Provides common methods for all of the inline transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class InlineTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays a clickable thumbnail. The options are the maximum width'
            . ' and height in pixels. The original aspect ratio is preserved.'
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
        if (PMA_IS_GD2) {
            return '<a href="transformation_wrapper.php'
                . $options['wrapper_link']
                . '" rel="noopener noreferrer" target="_blank"><img src="transformation_wrapper.php'
                . $options['wrapper_link'] . '&amp;resize=jpeg&amp;newWidth='
                . (isset($options[0]) ? intval($options[0]) : '100') . '&amp;newHeight='
                . (isset($options[1]) ? intval($options[1]) : 100)
                . '" alt="' . htmlspecialchars($buffer) . '" border="0" /></a>';
        } else {
            return '<img src="transformation_wrapper.php'
                . $options['wrapper_link']
                . '" alt="' . htmlspecialchars($buffer) . '" width="320" height="240" />';
        }
    }



    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Inline";
    }
}
?>
