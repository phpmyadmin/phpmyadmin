<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * JSON editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage JSON
 */
namespace PMA\libraries\plugins\transformations\input;

use PMA\libraries\plugins\transformations\abs\CodeMirrorEditorTransformationPlugin;

/**
 * JSON editing with syntax highlighted CodeMirror editor
 *
 * @package    PhpMyAdmin-Transformations
 * @subpackage JSON
 */
class Text_Plain_JsonEditor extends CodeMirrorEditorTransformationPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     *
     * @return string
     */
    public static function getInfo()
    {
        return __(
            'Syntax highlighted CodeMirror editor for JSON.'
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
            $scripts[] = 'codemirror/mode/javascript/javascript.js';
            $scripts[] = 'transformations/json_editor.js';
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
