<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the image link transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageLink
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations interface */
require_once 'libraries/plugins/TransformationsPlugin.class.php';
/* For PMA_Transformation_globalHtmlReplace */
require_once 'libraries/transformations.lib.php';

/**
 * Provides common methods for all of the image link transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class TextImageLinkTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Displays an image and a link; the column contains the filename. The'
            . ' first option is a URL prefix like "http://www.example.com/". The'
            . ' second and third options are the width and the height in pixels.'
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
        $transform_options = array (
            'string' => '<a href="' . (isset($options[0]) ? $options[0] : '')
                . $buffer . '" target="_blank"><img src="'
                . (isset($options[0]) ? $options[0] : '') . $buffer
                . '" border="0" width="' . (isset($options[1]) ? $options[1] : 100)
                . '" height="' . (isset($options[2]) ? $options[2] : 50) . '" />'
                . $buffer . '</a>'
        );

        $buffer = PMA_Transformation_globalHtmlReplace(
            $buffer,
            $transform_options
        );

        return $buffer;
    }


    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */


    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Image Link";
    }
}
?>
