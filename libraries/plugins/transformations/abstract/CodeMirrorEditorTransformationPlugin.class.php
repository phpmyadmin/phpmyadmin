<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Abstract class for syntax highlighted editors using CodeMirror
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage CodeMirrorEditor
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the transformations class */
require_once 'libraries/plugins/IOTransformationsPlugin.class.php';

/**
 * Provides common methods for all the CodeMirror syntax highlighted editors
 *
 * @package PhpMyAdmin
 */
abstract class CodeMirrorEditorTransformationsPlugin extends IOTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Syntax highlighted CodeMirror editor for the input text.'
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
}
?>