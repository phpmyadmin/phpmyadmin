<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JSON editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage CodeMirrorEditor
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the CodeMirror editor transformations class */
require_once 'libraries/plugins/transformations/abstract/'
        . 'CodeMirrorEditorTransformationPlugin.class.php';

/**
 * JSON editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage CodeMirrorEditor
 */
class Text_Plain_JsonEditor extends CodeMirrorEditorTransformationsPlugin
{
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
        if (!empty($value)) {
            $html = '<input type="hidden" name="fields_prev' . $column_name_appendix
                . '" value="' . $value . '"/>';
        }
        $html .= '<textarea name="fields' . $column_name_appendix . '"'
            . ' dir="' . $text_dir . '" class="transform_json_editor">'
            . $value . '</textarea>';
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
            'codemirror/mode/javascript/javascript.js',
            'transformations/json_editor.js'
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
        return "JSON";
    }

    /**
     * Gets the plugin`s MIME type
     *
     * @return string
     */
    public static function getMIMEType()
    {
        return "Text";
    }

    /**
     * Gets the plugin`s MIME subtype
     *
     * @return string
     */
    public static function getMIMESubtype()
    {
        return "Plain";
    }
}
?>