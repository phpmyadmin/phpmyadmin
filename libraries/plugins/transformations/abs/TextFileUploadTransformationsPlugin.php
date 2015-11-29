<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for the text file upload input transformations plugins
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage TextFileUpload
 */
namespace PMA\libraries\plugins\transformations\abs;

use PMA\libraries\plugins\IOTransformationsPlugin;

/**
 * Provides common methods for all of the text file upload
 * input transformations plugins.
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage TextFileUpload
 */
abstract class TextFileUploadTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'File upload functionality for TEXT columns. '
            . 'It does not have a textarea for input.'
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
     * @param int    $tabindex             tab index
     * @param int    $tabindex_for_value   offset for the values tabindex
     * @param int    $idindex              id index
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        $column,
        $row_id,
        $column_name_appendix,
        $options,
        $value,
        $text_dir,
        $tabindex,
        $tabindex_for_value,
        $idindex
    ) {
        $html = '';
        if (!empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . htmlspecialchars($value) . '"/>';
            $html .= '<input type="hidden" name="fields' . $column_name_appendix
                . '" value="' . htmlspecialchars($value) . '"/>';
        }
        $html .= '<input type="file" name="fields_upload'
            . $column_name_appendix . '"/>';

        return $html;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "Text file upload";
    }
}
