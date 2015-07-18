<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * XML (and HTML) editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage XML
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the CodeMirror editor transformations class */
require_once 'libraries/plugins/transformations/abstract/'
        . 'CodeMirrorEditorTransformationPlugin.class.php';

/**
 * XML (and HTML) editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage XML
 */
class Text_Plain_XmlEditor extends CodeMirrorEditorTransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Syntax highlighted CodeMirror editor for XML (and HTML).'
        );
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return array javascripts to be included
     */
    public function getScripts()
    {
        $scripts = array();
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $scripts[] = 'codemirror/lib/codemirror.js';
            $scripts[] = 'codemirror/mode/xml/xml.js';
            $scripts[] = 'transformations/xml_editor.js';
        }
        return $scripts;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     *
     * @return string
     */
    public static function getName()
    {
        return "XML";
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
