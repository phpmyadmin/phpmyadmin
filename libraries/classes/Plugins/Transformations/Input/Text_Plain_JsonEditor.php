<?php
/**
 * JSON editing with syntax highlighted CodeMirror editor
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Input;

use PhpMyAdmin\Plugins\Transformations\Abs\CodeMirrorEditorTransformationPlugin;

use function __;

/**
 * JSON editing with syntax highlighted CodeMirror editor
 */
class Text_Plain_JsonEditor extends CodeMirrorEditorTransformationPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __('Syntax highlighted CodeMirror editor for JSON.');
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return string[] javascripts to be included
     */
    public function getScripts(): array
    {
        $scripts = [];
        if ($GLOBALS['cfg']['CodemirrorEnable']) {
            $scripts[] = 'vendor/codemirror/lib/codemirror.js';
            $scripts[] = 'vendor/codemirror/mode/javascript/javascript.js';
            $scripts[] = 'transformations/json_editor.js';
        }

        return $scripts;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'JSON';
    }

    /**
     * Gets the plugin`s MIME type
     */
    public static function getMIMEType(): string
    {
        return 'Text';
    }

    /**
     * Gets the plugin`s MIME subtype
     */
    public static function getMIMESubtype(): string
    {
        return 'Plain';
    }
}
