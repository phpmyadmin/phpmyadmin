<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the image upload input transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage ImageUpload
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations class */
require_once 'libraries/plugins/IOTransformationsPlugin.class.php';

/**
 * Provides common methods for all of the image upload transformations plugins.
 *
 * @package PhpMyAdmin
 */
abstract class ImageUploadTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Image upload functionality which also displays a thumbnail.'
            . ' The options are the width and height of the thumbnail'
            . ' in pixels. Defaults to 100 X 100.'
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
        return $buffer;
    }

    /**
     * Returns the html for input field to override default textarea.
     * Note: Return empty string if default textarea is required.
     *
     * @param array  $column               column details
     * @param int    $row_id               row number
     * @param string $column_name_appendix the name attribute
     * @param array  $options              transformation options
     * @param string $value                Current field value
     * @param string $text_dir             text direction
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        $column, $row_id, $column_name_appendix, $options, $value, $text_dir
    ) {
        $html = '';
        $src = '';
        if (!empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . bin2hex($value) . '"/>';
            $html .= '<input type="hidden" name="fields' . $column_name_appendix
                . '" value="' . bin2hex($value) . '"/>';
            $src = 'transformation_wrapper.php' . $options['wrapper_link'];
        }
        $html .= '<img src="' . $src . '" width="'
                . (isset($options[0]) ? $options[0] : '100') . '" height="'
                . (isset($options[1]) ? $options[1] : '100') . '" alt="'
                . __('Image preview here') . '"/>';
        $html .= '<br/><input type="file" name="fields_upload'
            . $column_name_appendix . '" accept="image/*" class="image-upload"/>';
        return $html;
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        return array(
            'transformations/image_upload.js'
        );
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Image upload";
    }
}
?>
